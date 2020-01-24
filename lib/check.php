<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;

		private static $extend_lib_checks = array(
			'amp' => array(
				'amp'                      => 'AMP',	// AMP, Better AMP, etc.
				'accelerated-mobile-pages' => 'Accelerated Mobile Pages',
			),
			'seo' => array(
				'jetpack-seo' => 'Jetpack SEO Tools',
				'rankmath'    => 'SEO by Rank Math',
				'seou'        => 'SEO Ultimate',
				'sq'          => 'Squirrly SEO',
				'wpseo-wc'    => 'Yoast WooCommerce SEO',
			),
		);

		public function __construct( &$plugin ) {

			$this->p =& $plugin;
		}

		/**
		 * Please note that get_avail() is executed *before* the debug class object is defined,
		 * so do not log any debugging messages using $this->p->debug, for example.
		 *
		 * Most PHP library files have already been loaded, even if the class objects have not
		 * yet been defined, so you can safely use static methods, like SucomUtil::get_const(),
		 * for example.
		 */
		public function get_avail() {

			$get_avail = array();	// Initialize the array to return.

			foreach ( array( 'featured', 'head_html', 'vary_ua' ) as $key ) {
				$get_avail[ '*' ][ $key ] = $this->is_avail( $key );
			}

			$lib_checks = SucomUtil::array_merge_recursive_distinct( $this->p->cf[ '*' ][ 'lib' ][ 'pro' ], self::$extend_lib_checks );

			$jetpack_mods = method_exists( 'Jetpack', 'get_active_modules' ) ? Jetpack::get_active_modules() : array();

			foreach ( $lib_checks as $sub => $lib ) {

				$get_avail[ $sub ] = array();

				$get_avail[ $sub ][ 'any' ] = false;

				foreach ( $lib as $id => $name ) {

					$chk = array();

					$get_avail[ $sub ][ $id ] = false;	// Default value.

					switch ( $sub . '-' . $id ) {

						/**
						 * 3rd Party Plugins
						 *
						 * Prefer to check for class names than plugin slugs for 
						 * compatibility with free / premium / pro versions.
						 */
						case 'amp-amp':		// AMP, Better AMP, etc.

							$chk[ 'function' ] = 'is_amp_endpoint';

							break;

						case 'amp-accelerated-mobile-pages':	// Accelerated Mobile Pages.

							$chk[ 'function' ] = 'ampforwp_is_amp_endpoint';

							break;

						case 'ecom-edd':

							$chk[ 'class' ] = 'Easy_Digital_Downloads';

							break;

						case 'ecom-jck-wssv':

							$chk[ 'class' ] = 'JCK_WSSV';

							break;

						case 'ecom-perfect-woocommerce-brands':

							$chk[ 'class' ] = '\Perfect_Woocommerce_Brands\Perfect_Woocommerce_Brands';

							break;

						case 'ecom-woocommerce':

							$chk[ 'class' ] = 'WooCommerce';

							break;

						case 'ecom-woocommerce-brands':

							$chk[ 'class' ] = 'WC_Brands';

							break;

						case 'ecom-woo-add-gtin':

							$chk[ 'class' ] = 'Woo_GTIN';

							break;

						case 'ecom-wpecommerce':

							$chk[ 'class' ] = 'WP_eCommerce';

							break;

						case 'ecom-wpm-product-gtin-wc':

							$chk[ 'class' ] = 'WPM_Product_GTIN_WC';

							break;

						case 'ecom-yith-woocommerce-brands':

							$chk[ 'class' ] = 'YITH_WCBR';

							break;

						case 'event-tribe_events':

							$chk[ 'class' ] = 'Tribe__Events__Main';

							break;

						case 'form-gravityforms':

							$chk[ 'class' ] = 'GFForms';

							break;

						case 'form-gravityview':

							$chk[ 'class' ] = 'GravityView_Plugin';

							break;

						case 'forum-bbpress':

							$chk[ 'plugin' ] = 'bbpress/bbpress.php';

							break;

						case 'job-simplejobboard':

							$chk[ 'class' ] = 'Simple_Job_Board';

							break;

						case 'job-wpjobmanager':

							$chk[ 'class' ] = 'WP_Job_Manager';

							break;

						case 'lang-polylang':

							$chk[ 'class' ] = 'Polylang';

							break;

						case 'media-ngg':	// NextGEN Gallery and NextCellent Gallery

							$chk[ 'class' ] = 'nggdb';

							break;

						case 'media-rtmedia':

							$chk[ 'plugin' ] = 'buddypress-media/index.php';

							break;

						case 'rating-wppostratings':

							$chk[ 'constant' ] = 'WP_POSTRATINGS_VERSION';

							break;

						case 'recipe-wprecipemaker':

							$chk[ 'class' ] = 'WP_Recipe_Maker';

							break;

						case 'recipe-wpultimaterecipe':

							$chk[ 'class' ] = 'WPUltimateRecipe';

							break;

						case 'review-yotpowc':

							$chk[ 'function' ] = 'wc_yotpo_init';

							break;

						case 'review-wpproductreview':

							$chk[ 'class' ] = 'WPPR';

							break;

						case 'seo-aioseop':

							$chk[ 'function' ] = 'aioseop_init_class';

							break;

						case 'seo-autodescription':

							$chk[ 'function' ] = 'the_seo_framework';

							break;

						case 'seo-jetpack-seo':

							if ( ! empty( $jetpack_mods ) ) {
								if ( in_array( 'seo-tools', $jetpack_mods ) ) {
									$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
								}
							}

							break;

						case 'seo-rankmath':

							$chk[ 'class' ] = 'RankMath';

							break;

						case 'seo-seou':

							$chk[ 'plugin' ] = 'seo-ultimate/seo-ultimate.php';

							break;

						case 'seo-sq':

							$chk[ 'plugin' ] = 'squirrly-seo/squirrly.php';

							break;

						case 'seo-wpmetaseo':

							$chk[ 'class' ] = 'WpMetaSeo';

							break;

						case 'seo-wpseo':

							$chk[ 'function' ] = 'wpseo_init';

							break;

						case 'seo-wpseo-wc':

							$chk[ 'class' ] = 'Yoast_WooCommerce_SEO';

							break;

						case 'social-buddyblog':

							$chk[ 'class' ] = 'BuddyBlog';

							break;

						case 'social-buddypress':

							$chk[ 'class' ] = 'BuddyPress';

							break;

						/**
						 * Premium version features / options.
						 */
						case 'media-facebook':
						case 'media-gravatar':
						case 'media-slideshare':
						case 'media-soundcloud':
						case 'media-vimeo':
						case 'media-wistia':
						case 'media-wpvideo':
						case 'media-youtube':

							$chk[ 'opt_key' ] = 'plugin_' . $id . '_api';

							break;

						case 'media-upscale':

							$chk[ 'opt_key' ] = 'plugin_upscale_images';

							break;

						case 'util-custom-fields':

							$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;

							break;

						case 'util-coauthors':

							$chk[ 'plugin' ] = 'co-authors-plus/co-authors-plus.php';

							break;

						case 'util-post':
						case 'util-term':
						case 'util-user':

							$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;

							break;

						case 'util-shorten':

							$chk[ 'opt_key' ] = 'plugin_shortener';

							break;

						case 'util-wpseo-meta':

							$chk[ 'opt_key' ] = 'plugin_wpseo_social_meta';

							break;
					}

					/**
					 * Check classes / functions first to include both free and pro / premium plugins, which
					 * have different plugin slugs, but use the same class / function names.
					 */
					if ( ! empty( $chk ) ) {

						if ( isset( $chk[ 'class' ] ) || isset( $chk[ 'function' ] ) || isset( $chk[ 'plugin' ] ) ) {

							if ( ( ! empty( $chk[ 'class' ] ) && class_exists( $chk[ 'class' ] ) ) ||
								( ! empty( $chk[ 'function' ] ) && function_exists( $chk[ 'function' ] ) ) ||
								( ! empty( $chk[ 'plugin' ] ) && SucomPlugin::is_plugin_active( $chk[ 'plugin' ], $use_cache = true ) ) ) {

								/**
								 * Check if an option value is also required.
								 */
								if ( isset( $chk[ 'opt_key' ] ) ) {

									if ( $this->is_opt_enabled( $chk[ 'opt_key' ] ) ) {
										$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
									}

								} else {
									$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
								}
							}

						} elseif ( isset( $chk[ 'opt_key' ] ) ) {

							if ( $this->is_opt_enabled( $chk[ 'opt_key' ] ) ) {
								$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
							}

						} elseif ( isset( $chk[ 'constant' ] ) ) {

							if ( defined( $chk[ 'constant' ] ) ) {
								$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
							}
						}
					}
				}
			}

			/**
			 * Define WPSSO_UNKNOWN_SEO_PLUGIN_ACTIVE as true to disable WPSSO's SEO related meta tags and features.
			 */
			if ( SucomUtil::get_const( 'WPSSO_UNKNOWN_SEO_PLUGIN_ACTIVE' ) ) {
				$get_avail[ 'seo' ][ 'any' ] = true;
			}

			return apply_filters( $this->p->lca . '_get_avail', $get_avail );
		}

		/**
		 * Private method to check for availability of specific features by keyword.
		 */
		private function is_avail( $key ) {

			$is_avail = false;

			switch ( $key ) {

				case 'featured':

					$is_avail = function_exists( 'has_post_thumbnail' ) ? true : false;

					break;

				case 'head_html':

					$is_avail = ! SucomUtil::get_const( 'WPSSO_HEAD_HTML_DISABLE' ) &&
						empty( $_SERVER[ 'WPSSO_HEAD_HTML_DISABLE' ] ) &&
							empty( $_GET[ 'WPSSO_HEAD_HTML_DISABLE' ] ) ?
								true : false;

					break;

				case 'vary_ua':

					$is_avail = ! SucomUtil::get_const( 'WPSSO_VARY_USER_AGENT_DISABLE' ) ? true : false;

					break;
			}

			return $is_avail;
		}

		public function is_pp( $ext = null, $rc = true ) {

			return $this->pp( $ext, $li = true, $rv = true, $rc );
		}

		public function pp( $ext = null, $li = true, $rv = true, $rc = true, $mx = null ) {

			static $lc = array();

			$ext = null === $ext ? $this->p->lca : $ext;
			$id  = $ext . '/' . $li . '/' . $rv . '/' . $mx;
			$rv  = null === $mx ? $rv : $rv * $mx;

			if ( $rc && isset( $lc[ $id ] ) ) {
				return $lc[ $id ];
			}

			$uca = strtoupper( $ext );

			if ( defined( 'WPSSO_PRO_DISABLE' ) && WPSSO_PRO_DISABLE ) {

				return $lc[ $id ] = false;

			} elseif ( defined( $uca . '_PLUGINDIR' ) ) {

				$ext_dir = constant( $uca . '_PLUGINDIR' );

			} elseif ( isset( $this->p->cf[ 'plugin' ][ $ext ][ 'slug' ] ) ) {

				$slug = $this->p->cf[ 'plugin' ][ $ext ][ 'slug' ];

				if ( ! defined ( 'WPMU_PLUGIN_DIR' ) ||
					! is_dir( $ext_dir = WPMU_PLUGIN_DIR . '/' . $slug . '/' ) ) {

					if ( ! defined ( 'WP_PLUGIN_DIR' ) ||
						! is_dir( $ext_dir = WP_PLUGIN_DIR . '/' . $slug . '/' ) ) {

						return $lc[ $id ] = false;
					}
				}

			} else {
				return $lc[ $id ] = false;
			}

			$okey = 'plugin_' . $ext . '_tid';
			$pdir = is_dir( $ext_dir . 'lib/pro/' ) ? $rv : false;

			return $lc[ $id ] = $li ? ( ( ! empty( $this->p->options[ $okey ] ) && $pdir && class_exists( 'SucomUpdate' ) && 
				( $ue = SucomUpdate::get_umsg( $ext ) ? false : $pdir ) ) ? $ue : false ) : $pdir;
		}

		public function get_ext_gen_list() {

			static $ext_list = null;
		
			if ( null !== $ext_list ) {
				return $ext_list;
			}

			$ext_list = array();

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Include only active add-ons.
					continue;
				}

				$ext_pdir    = $this->pp( $ext, $li = false );
				$ext_auth_id = $this->get_ext_auth_id( $ext );
				$ext_pp      = $ext_auth_id && $this->pp( $ext, $li = true, WPSSO_UNDEF ) === WPSSO_UNDEF ? true : false;
				$ext_stat    = ( $ext_pp ? 'L' : ( $ext_pdir ? 'U' : 'S' ) ) . ( $ext_auth_id ? '*' : '' );
				$ext_list[]  = $info[ 'short' ] . ' ' . $info[ 'version' ] . '/' . $ext_stat;
			}

			return $ext_list;
		}

		public function get_ext_auth_id( $ext ) {

			$ext_auth_type = $this->get_ext_auth_type( $ext );
			$ext_auth_key  = 'plugin_' . $ext . '_' . $ext_auth_type;

			return empty( $this->p->options[ $ext_auth_key ] ) ?
				'' : $this->p->options[ $ext_auth_key ];
		}

		public function get_ext_auth_type( $ext ) {

			return empty( $this->p->cf[ 'plugin' ][ $ext ][ 'update_auth' ] ) ?
				'none' : $this->p->cf[ 'plugin' ][ $ext ][ 'update_auth' ];
		}

		private function is_opt_enabled( $opt_key ) {

			if ( ! empty( $opt_key ) ) {	// Just in case.

				if ( ! empty( $this->p->options[ $opt_key ] ) ) {	// Not 0 or empty string.

					if ( $this->p->options[ $opt_key ] !== 'none' ) {
						return true;
					}
				}
			}

			return false;
		}
	}
}

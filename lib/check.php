<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;

		private static $extend_lib_checks = array(
			'amp' => array(
				'amp'                      => 'AMP',	// AMP, Better AMP, etc.
				'accelerated-mobile-pages' => 'Accelerated Mobile Pages',
			),
			'media' => array(
				'wp-retina-2x' => 'WP Retina 2x',
			),
			'p' => array(
				'schema'  => 'Schema Markup',
				'vary_ua' => 'Vary by User Agent',
			),
			'seo' => array(
				'jetpack-seo' => 'Jetpack SEO Tools',
				'rankmath'    => 'SEO by Rank Math',
				'seou'        => 'SEO Ultimate',
				'sq'          => 'Squirrly SEO',
				'wpseo-wc'    => 'Yoast WooCommerce SEO',
			),
			'util' => array(
				'jetpack' => 'Jetpack',
			),
			'wp' => array(
				'featured' => 'Post Thumbnail',
			),
		);

		/**
		 * The WpssoCheck class is instantiated before the SucomDebug class, so do not use the $this->p->debug class
		 * object to log status messages.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;
		}

		/**
		 * Please note that get_avail() is executed *before* the debug class object is defined, so do not log any debugging
		 * messages using $this->p->debug, for example.
		 *
		 * Most PHP library files have already been loaded, even if the class objects have not yet been defined, so you can
		 * safely use static methods, like SucomUtil::get_const(), for example.
		 */
		public function get_avail() {

			$mtime_start  = microtime( true );

			$get_avail = array();	// Initialize the array to return.

			$lib_checks = SucomUtil::array_merge_recursive_distinct( $this->p->cf[ '*' ][ 'lib' ][ 'pro' ], self::$extend_lib_checks );

			foreach ( $lib_checks as $sub => $lib ) {

				$get_avail[ $sub ] = array();

				$get_avail[ $sub ][ 'any' ] = false;

				foreach ( $lib as $id => $name ) {

					$chk = array();

					$get_avail[ $sub ][ $id ] = false;	// Default value.

					switch ( $sub ) {

						case 'amp':

							switch ( $id ) {

								/**
								 * AMP, Better AMP, etc.
								 */
								case 'amp':

									$chk[ 'function' ] = 'is_amp_endpoint';

									break;

								/**
								 * Accelerated Mobile Pages.
								 */
								case 'accelerated-mobile-pages':

									$chk[ 'function' ] = 'ampforwp_is_amp_endpoint';

									break;
							}

							break;

						case 'ecom':

							switch ( $id ) {

								case 'edd':

									$chk[ 'class' ] = 'Easy_Digital_Downloads';

									break;

								case 'jck-wssv':

									$chk[ 'class' ] = 'JCK_WSSV';

									break;

								case 'perfect-woocommerce-brands':

									$chk[ 'class' ] = '\Perfect_Woocommerce_Brands\Perfect_Woocommerce_Brands';

									break;

								case 'woocommerce':

									$chk[ 'class' ] = 'WooCommerce';

									break;

								case 'woocommerce-brands':

									$chk[ 'class' ] = 'WC_Brands';

									break;

								case 'woocommerce-currency-switcher':

									$chk[ 'class' ] = 'WOOCS';

									break;

								case 'woo-add-gtin':

									$chk[ 'class' ] = 'Woo_GTIN';

									break;

								case 'wpecommerce':

									$chk[ 'class' ] = 'WP_eCommerce';

									break;

								case 'wpm-product-gtin-wc':

									$chk[ 'class' ] = 'WPM_Product_GTIN_WC';

									break;

								case 'yith-woocommerce-brands':

									$chk[ 'class' ] = 'YITH_WCBR';

									break;
							}

							break;

						case 'event':

							switch ( $id ) {

								case 'the-events-calendar':

									$chk[ 'class' ] = 'Tribe__Events__Main';

									break;
							}

							break;

						case 'form':

							switch ( $id ) {

								case 'gravityforms':

									$chk[ 'class' ] = 'GFForms';

									break;

								case 'gravityview':

									$chk[ 'class' ] = 'GravityView_Plugin';

									break;
							}

							break;


						case 'forum':

							switch ( $id ) {

								case 'bbpress':

									$chk[ 'plugin' ] = 'bbpress/bbpress.php';

									break;
							}

							break;


						case 'job':

							switch ( $id ) {

								case 'simplejobboard':

									$chk[ 'class' ] = 'Simple_Job_Board';

									break;

								case 'wpjobmanager':

									$chk[ 'class' ] = 'WP_Job_Manager';

									break;
							}

							break;


						case 'lang':

							switch ( $id ) {

								case 'polylang':

									$chk[ 'class' ] = 'Polylang';

									break;

								case 'wpml':

									$chk[ 'class' ] = 'SitePress';

									break;
							}

							break;


						case 'media':

							switch ( $id ) {

								/**
								 * NextGEN Gallery and NextCellent Gallery.
								 */
								case 'ngg':

									$chk[ 'class' ] = 'nggdb';

									break;

								case 'rtmedia':

									$chk[ 'plugin' ] = 'buddypress-media/index.php';

									break;

								/**
								 * WP Retina 2x.
								 */
								case 'wp-retina-2x':

									$chk[ 'class' ] = 'Meow_WR2X_Core';

									break;

								/**
								 * Premium version feature / option.
								 */
								case 'facebook':
								case 'gravatar':
								case 'slideshare':
								case 'soundcloud':
								case 'vimeo':
								case 'wistia':
								case 'wpvideo':
								case 'youtube':

									$chk[ 'opt_key' ] = 'plugin_' . $id . '_api';

									break;

								/**
								 * Premium version feature / option.
								 */
								case 'upscale':

									$chk[ 'opt_key' ] = 'plugin_upscale_images';

									break;
							}

							break;

						case 'p':

							switch ( $id ) {

								case 'schema':

									$get_avail[ $sub ][ $id ] = SucomUtil::get_const( 'WPSSO_SCHEMA_MARKUP_DISABLE' ) ? false : true;

									break;

								case 'vary_ua':

									$get_avail[ $sub ][ $id ] = SucomUtil::get_const( 'WPSSO_VARY_USER_AGENT_DISABLE' ) ? false : true;

									/**
									 * Maintain backwards compatibility.
									 */
									$get_avail[ '*' ][ $id ] = $get_avail[ $sub ][ $id ];

									break;
							}

							break;

						case 'rating':

							switch ( $id ) {

								/**
								 * Rate my Post.
								 *
								 * https://wordpress.org/plugins/rate-my-post/
								 */
								case 'rate-my-post':

									$chk[ 'class' ] = 'Rate_My_Post';

									break;

								/**
								 * WP-PostRatings.
								 *
								 * https://wordpress.org/plugins/wp-postratings/
								 */
								case 'wppostratings':

									$chk[ 'constant' ] = 'WP_POSTRATINGS_VERSION';

									break;
							}

							break;

						case 'recipe':

							switch ( $id ) {

								case 'wprecipemaker':

									$chk[ 'class' ] = 'WP_Recipe_Maker';

									break;

								case 'wpultimaterecipe':

									$chk[ 'class' ] = 'WPUltimateRecipe';

									break;
							}

							break;

						case 'review':

							switch ( $id ) {

								case 'yotpowc':

									$chk[ 'function' ] = 'wc_yotpo_init';

									break;

								case 'wpproductreview':

									$chk[ 'class' ] = 'WPPR';

									break;
							}

							break;

						case 'seo':

							switch ( $id ) {

								case 'aioseop':

									$chk[ 'function' ] = 'aioseop_init_class';

									break;

								case 'autodescription':

									$chk[ 'function' ] = 'the_seo_framework';

									break;

								case 'jetpack-seo':

									$jetpack_modules = method_exists( 'Jetpack', 'get_active_modules' ) ?
										Jetpack::get_active_modules() : array();

									if ( ! empty( $jetpack_modules ) ) {

										if ( in_array( 'seo-tools', $jetpack_modules ) ) {

											$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
										}
									}

									break;

								case 'rankmath':

									$chk[ 'class' ] = 'RankMath';

									break;

								case 'seou':

									$chk[ 'plugin' ] = 'seo-ultimate/seo-ultimate.php';

									break;

								case 'sq':

									$chk[ 'plugin' ] = 'squirrly-seo/squirrly.php';

									break;

								case 'wpmetaseo':

									$chk[ 'class' ] = 'WpMetaSeo';

									break;

								case 'wpseo':

									$chk[ 'function' ] = 'wpseo_init';

									break;

								case 'wpseo-wc':

									$chk[ 'class' ] = 'Yoast_WooCommerce_SEO';

									break;
							}

							break;

						case 'social':

							switch ( $id ) {

								case 'buddyblog':

									$chk[ 'class' ] = 'BuddyBlog';

									break;

								case 'buddypress':

									$chk[ 'class' ] = 'BuddyPress';

									break;
							}

							break;

						case 'util':

							switch ( $id ) {

								case 'coauthors':

									$chk[ 'plugin' ] = 'co-authors-plus/co-authors-plus.php';

									break;

								case 'jetpack':

									$chk[ 'class' ] = 'Jetpack';

									break;

								case 'shorten':

									$chk[ 'opt_key' ] = 'plugin_shortener';

									break;

								case 'wpseo-meta':

									$chk[ 'opt_key' ] = 'plugin_wpseo_social_meta';

									break;
							}

							break;

						case 'wp':

							switch ( $id ) {

								case 'featured':

									$chk[ 'function' ] = 'has_post_thumbnail';

									break;
							}

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

			$mtime_total = microtime( true ) - $mtime_start;

			return apply_filters( $this->p->lca . '_get_avail', $get_avail );
		}

		public function is_pp( $ext = null, $rc = true ) {

			return $this->pp( $ext, $li = true, $rv = true, $rc );
		}

		public function pp( $ext = null, $li = true, $rv = true, $rc = true, $mx = null ) {

			static $lc = array();

			$ext = null === $ext ? $this->p->lca : $ext;
			$id  = '|' . $ext . '-' . $li . '-' . $rv . '-' . $mx . '|';
			$rv  = null === $mx ? $rv : $rv * $mx;

			if ( $rc && isset( $lc[ $id ] ) ) {
				return $lc[ $id ];
			} elseif ( defined( 'WPSSO_PRO_DISABLE' ) && WPSSO_PRO_DISABLE ) {
				return $lc[ $id ] = false;
			} elseif ( ! $ext_dir = WpssoConfig::get_ext_dir( $ext ) ) {
				return $lc[ $id ] = false;
			}

			$okey = 'plugin_' . $ext . '_tid';
			$pdir = is_dir( $ext_dir . 'lib/pro/' ) ? $rv : false;

			return $lc[ $id ] = $li ? ( ( ! empty( $this->p->options[ $okey ] ) && $pdir && class_exists( 'SucomUpdate' ) && 
				( $ue = SucomUpdate::get_umsg( $ext ) ? false : $pdir ) ) ? $ue : false ) : $pdir;
		}

		public function get_ext_gen_list() {

			static $local_cache = null;

			if ( null !== $local_cache ) {
				return $local_cache;
			}

			$local_cache = array();
			$ext_sorted  = WpssoConfig::get_ext_sorted();

			foreach ( $ext_sorted as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Include only active add-ons.
					continue;
				}

				$ext_pdir    = $this->pp( $ext, $li = false );
				$ext_auth_id = $this->get_ext_auth_id( $ext );
				$ext_pp      = $ext_auth_id && $this->pp( $ext, $li = true, WPSSO_UNDEF ) === WPSSO_UNDEF ? true : false;
				$ext_stat    = ( $ext_pp ? 'L' : ( $ext_pdir ? 'U' : 'S' ) ) . ( $ext_auth_id ? '*' : '' );

				$local_cache[] = $info[ 'short' ] . ' ' . $info[ 'version' ] . '/' . $ext_stat;
			}

			return $local_cache;
		}

		public function get_ext_auth_id( $ext ) {

			$ext_auth_type = $this->get_ext_auth_type( $ext );
			$ext_auth_key  = 'plugin_' . $ext . '_' . $ext_auth_type;

			return empty( $this->p->options[ $ext_auth_key ] ) ? '' : $this->p->options[ $ext_auth_key ];
		}

		public function get_ext_auth_type( $ext ) {

			return empty( $this->p->cf[ 'plugin' ][ $ext ][ 'update_auth' ] ) ? 'none' : $this->p->cf[ 'plugin' ][ $ext ][ 'update_auth' ];
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

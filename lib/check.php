<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;
		private static $pp_c = array();
		private static $extend_lib_checks = array(
			'seo' => array(
				'jetpack-seo' => 'Jetpack SEO Tools',
				'seou'        => 'SEO Ultimate',
				'sq'          => 'Squirrly SEO',
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

			$is_admin     = is_admin();
			$jetpack_mods = method_exists( 'Jetpack', 'get_active_modules' ) ? Jetpack::get_active_modules() : array();
			$get_avail    = array();	// Initialize the array to return.

			foreach ( array( 'featured', 'amp', 'p_dir', 'head_html', 'vary_ua' ) as $key ) {
				$get_avail[ '*' ][ $key ] = $this->is_avail( $key );
			}

			$lib_checks = SucomUtil::array_merge_recursive_distinct( $this->p->cf[ '*' ][ 'lib' ][ 'pro' ], self::$extend_lib_checks );

			foreach ( $lib_checks as $sub => $lib ) {

				$get_avail[ $sub ] = array();
				$get_avail[ $sub ][ 'any' ] = false;

				foreach ( $lib as $id => $name ) {

					$chk = array();
					$get_avail[$sub][$id] = false;	// default value

					switch ( $sub . '-' . $id ) {

						/**
						 * 3rd Party Plugins
						 *
						 * Prefer to check for class names than plugin slugs for 
						 * compatibility with free / premium / pro versions.
						 */
						case 'ecom-edd':

							$chk[ 'class' ] = 'Easy_Digital_Downloads';

							break;

						case 'ecom-marketpress':

							$chk[ 'class' ] = 'Marketpress';

							break;

						case 'ecom-woocommerce':

							$chk[ 'class' ] = 'WooCommerce';

							break;

						case 'ecom-wpecommerce':

							$chk[ 'class' ] = 'WP_eCommerce';

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

						case 'lang-polylang':

							$chk[ 'class' ] = 'Polylang';

							break;

						case 'media-ngg':	// NextGEN Gallery and NextCellent Gallery

							$chk[ 'class' ] = 'nggdb';

							break;

						case 'media-rtmedia':

							$chk[ 'plugin' ] = 'buddypress-media/index.php';

							break;

						case 'rating-wppostratings':			// wp-postratings

							$chk['constant'] = 'WP_POSTRATINGS_VERSION';

							break;

						case 'rating-yotpowc':				// yotpo-social-reviews-for-woocommerce

							$chk[ 'function' ] = 'wc_yotpo_init';

							break;

						case 'seo-aioseop':

							$chk[ 'function' ] = 'aioseop_init_class'; // Free and pro versions.

							break;

						case 'seo-autodescription':

							$chk[ 'function' ] = 'the_seo_framework';

							break;

						case 'seo-headspace2':

							$chk[ 'class' ] = 'HeadSpace_Plugin';

							break;

						case 'seo-jetpack-seo':

							if ( ! empty( $jetpack_mods ) ) {
								if ( in_array( 'seo-tools', $jetpack_mods ) ) {
									$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
								}
							}

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

							$chk[ 'function' ] = 'wpseo_init'; // Free and premium versions.

							break;

						case 'social-buddyblog':

							$chk[ 'class' ] = 'BuddyBlog';

							break;

						case 'social-buddypress':

							$chk[ 'class' ] = 'BuddyPress';

							break;

						/**
						 * Pro Version Features / Options
						 */
						case 'admin-general':
						case 'admin-advanced':

							// only load on the settings pages
							if ( $is_admin ) {
								$page = basename( $_SERVER['PHP_SELF'] );
								if ( $page === 'admin.php' || $page === 'options-general.php' ) {
									$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
								}
							}

							break;

						case 'admin-post':
						case 'admin-meta':

							if ( $is_admin ) {
								$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
							}

							break;

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

						/*
						case 'places-google_places':
							$chk[ 'opt_key' ] = 'plugin_google_places';
							break;
						 */

						case 'util-checkimgdims':

							$chk[ 'opt_key' ] = 'plugin_check_img_dims';

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

						case 'util-wpseo_meta':

							$chk[ 'opt_key' ] = 'plugin_wpseo_social_meta';

							break;
					}

					/**
					 * Check classes / functions first to include both free and pro / premium plugins,
					 * which have different plugin slugs, but use the same class / function names.
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

						} elseif ( isset( $chk['constant'] ) ) {

							if ( defined( $chk['constant'] ) ) {
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

				case 'amp':

					$is_avail = function_exists( 'is_amp_endpoint' ) ? true : false;

					break;

				case 'p_dir':

					$is_avail = ! SucomUtil::get_const( 'WPSSO_PRO_DISABLE' ) &&
						is_dir( WPSSO_PLUGINDIR . 'lib/pro/' ) ? true : false;

					break;

				case 'head_html':

					$is_avail = ! SucomUtil::get_const( 'WPSSO_HEAD_HTML_DISABLE' ) &&
						empty( $_SERVER['WPSSO_HEAD_HTML_DISABLE'] ) &&
							empty( $_GET['WPSSO_HEAD_HTML_DISABLE'] ) ?
								true : false;

					break;

				case 'vary_ua':

					/**
					 * The WPSSO_VARY_USER_AGENT_DISABLE constant can be defined as true to disable mobile
					 * browser detection and the creation of Pinterest-specific meta tag values.
					 *
					 * Mobile browser and Pinterest crawler detection does NOT create additional transient
					 * cache objects or cached files on disk. Transient cache objects are indexed arrays,
					 * and an additional index element - within the same transient cache object - will be
					 * added, so browser and crawler detection does not increase the number of cache objects.
					 */
					$is_avail = ! SucomUtil::get_const( 'WPSSO_VARY_USER_AGENT_DISABLE' ) ? true : false;

					break;
			}

			return $is_avail;
		}

		/**
		 * Deprecated on 2018/08/27.
		 */
		public function is_aop( $ext = '', $uc = true ) {
			return $this->is_pp( $ext, $uc );
		}

		public function is_pp( $ext = '', $uc = true ) {
			return $this->pp( $ext, true, ( isset( $this->p->avail[ '*' ][ 'p_dir' ] ) ?
				$this->p->avail[ '*' ][ 'p_dir' ] : $this->is_avail( 'p_dir' ) ), $uc );
		}

		/**
		 * Deprecated on 2018/08/27.
		 */
		public function aop( $ext = '', $lic = true, $rv = true, $uc = true ) {
			return $this->pp( $ext, $lic, $rv, $uc );
		}

		public function pp( $ext = '', $lic = true, $rv = true, $uc = true ) {

			$ext = empty( $ext ) ? $this->p->lca : $ext;
			$key = $ext . '-' . $lic . '-' . $rv;

			if ( $uc && isset( self::$pp_c[$key] ) ) {
				return self::$pp_c[$key];
			}

			$uca = strtoupper( $ext );

			if ( defined( $uca . '_PLUGINDIR' ) ) {

				$pdir = constant( $uca . '_PLUGINDIR' );

			} elseif ( isset( $this->p->cf[ 'plugin' ][$ext][ 'slug' ] ) ) {

				$slug = $this->p->cf[ 'plugin' ][$ext][ 'slug' ];

				if ( ! defined ( 'WPMU_PLUGIN_DIR' ) ||
					! is_dir( $pdir = WPMU_PLUGIN_DIR . '/' . $slug . '/' ) ) {

					if ( ! defined ( 'WP_PLUGIN_DIR' ) ||
						! is_dir( $pdir = WP_PLUGIN_DIR . '/' . $slug . '/' ) ) {
						return self::$pp_c[$key] = false;
					}
				}

			} else {
				return self::$pp_c[$key] = false;
			}

			$key = 'plugin_' . $ext . '_tid';
			$ins = is_dir( $pdir . 'lib/pro/' ) ? $rv : false;

			return self::$pp_c[$key] = true === $lic ?
				( ( ! empty( $this->p->options[$key] ) &&
					$ins && class_exists( 'SucomUpdate' ) &&
						( $uerr = SucomUpdate::get_umsg( $ext ) ?
							false : $ins ) ) ? $uerr : false ) : $ins;
		}

		public function get_ext_list() {

			$ext_list = array();

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Only active add-ons.
					continue;
				}

				$ins = $this->pp( $ext, false );

				$ext_list[] = $info[ 'short' ] . ' ' . $info[ 'version' ] . '/' . 
					( $this->is_pp( $ext ) ? 'L' : ( $ins ? 'U' : 'F' ) );
			}

			return $ext_list;
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

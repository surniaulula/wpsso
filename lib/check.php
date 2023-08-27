<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoCheck' ) ) {

	class WpssoCheck {

		private $p;	// Wpsso class object.

		private $extend_lib_checks = array(
			'amp' => array(
				'amp'                      => 'AMP',
				'accelerated-mobile-pages' => 'AMP for WP',
			),
			'cache' => array(	// Page caching plugins and services.
				'enabler'     => 'Cache Enabler',
				'comet'       => 'Comet Cache',
				'hummingbird' => 'Hummingbird Cache',
				'litespeed'   => 'LiteSpeed Cache',
				'pagely'      => 'Pagely Cache',
				'siteground'  => 'SiteGround Cache',
				'w3tc'        => 'W3 Total Cache',
				'wp-engine'   => 'WP Engine cache',
				'wp-fastest'  => 'WP Fastest Cache',
				'wp-rocket'   => 'WP Rocket Cache',
				'wp-super'    => 'WP Super Cache',
			),
			'media' => array(
				'wp-retina-2x' => 'Perfect Images + Retina',
			),
			'p' => array(
				'schema' => 'Schema Markup',
			),
			'seo' => array(
				'jetpack-seo' => 'Jetpack SEO Tools',
				'seoultimate' => 'SEO Ultimate',
				'slim-seo'    => 'Slim SEO',
				'squirrlyseo' => 'Squirrly SEO',
				'wpseo-wc'    => 'Yoast WooCommerce SEO',
			),
			'util' => array(
				'autoptimize' => 'Autoptimize',
				'jetpack'     => 'Jetpack',
			),
		);

		/*
		 * This class is instantiated before the SucomDebug class, so do not use $this->p->debug to log status messages.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;
		}

		/*
		 * This method is run once by Wpsso->set_objects() and typically runs in 0.0002 secs.
		 *
		 * Do not save or retrieve the array from the transient cache as this is slower than running the method.
		 *
		 * Please note that get_avail() is executed before the debug class object is defined, so do not log debugging
		 * messages using $this->p->debug.
		 *
		 * Non-admin PHP library files have been loaded, even if the class object variables have not been defined yet, so
		 * you can safely call static methods, like SucomUtil::get_const(), for example.
		 */
		public function get_avail() {

			$mtime_start = microtime( $get_float = true );

			$get_avail = array();	// Initialize the array to return.

			$is_admin   = is_admin();
			$lib_checks = $this->extend_lib_checks;
			$lib_checks = SucomUtil::array_merge_recursive_distinct( $lib_checks, $this->p->cf[ '*' ][ 'lib' ][ 'integ' ] );
			$lib_checks = SucomUtil::array_merge_recursive_distinct( $lib_checks, $this->p->cf[ '*' ][ 'lib' ][ 'pro' ] );

			foreach ( $lib_checks as $sub => $lib ) {

				$get_avail[ $sub ] = array();

				$get_avail[ $sub ][ 'any' ] = false;

				foreach ( $lib as $id => $name ) {

					$chk = array();

					$get_avail[ $sub ][ $id ] = false;	// Default value.

					switch ( $sub ) {

						case 'admin':

							/*
							 * Load admin modules in back-end.
							 */
							if ( $is_admin ) {

								$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
							}

							break;

						case 'amp':

							switch ( $id ) {

								/*
								 * AMP.
								 *
								 * See https://wordpress.org/plugins/amp/.
								 */
								case 'amp':

									$chk[ 'function' ] = 'is_amp_endpoint';

									break;

								/*
								 * Accelerated Mobile Pages.
								 *
								 * See https://wordpress.org/plugins/accelerated-mobile-pages/.
								 */
								case 'accelerated-mobile-pages':

									$chk[ 'function' ] = 'ampforwp_is_amp_endpoint';

									break;
							}

							break;

						case 'cache':

							switch ( $id ) {

								/*
								 * Cache Enabler.
								 *
								 * See https://wordpress.org/plugins/cache-enabler/.
								 */
								case 'enabler':

									$chk[ 'class' ] = 'Cache_Enabler';

									break;

								/*
								 * Comet Cache.
								 *
								 * See https://wordpress.org/plugins/comet-cache/.
								 */
								case 'comet':

									if ( isset( $GLOBALS[ 'comet_cache' ] ) ) {

										$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
									}

									break;

								/*
								 * Hummingbird Cache.
								 *
								 * See https://wordpress.org/plugins/hummingbird-performance/.
								 */
								case 'hummingbird':

									$chk[ 'class' ] = '\Hummingbird\WP_Hummingbird';

									break;

								/*
								 * LiteSpeed Cache.
								 *
								 * See https://wordpress.org/plugins/litespeed-cache/.
								 */
								case 'litespeed':

									$chk[ 'class' ] = 'LiteSpeed_Cache_API';

									break;

								/*
								 * Pagely Cache.
								 */
								case 'pagely':

									$chk[ 'class' ] = 'PagelyCachePurge';

									break;

								/*
								 * SiteGround Cache.
								 */
								case 'siteground':

									$chk[ 'function' ] = 'sg_cachepress_purge_cache';

									break;

								/*
								 * W3 Total Cache.
								 *
								 * See https://wordpress.org/plugins/w3-total-cache/.
								 */
								case 'w3tc':

									$chk[ 'function' ] = 'w3tc_pgcache_flush';

									break;

								/*
								 * WP Engine Cache.
								 */
								case 'wp-engine':

									$chk[ 'class' ] = 'WpeCommon';

									break;

								/*
								 * WP Fastest Cache.
								 *
								 * See https://wordpress.org/plugins/wp-fastest-cache/.
								 */
								case 'wp-fastest':

									$chk[ 'function' ] = 'wpfc_clear_all_cache';

									break;

								/*
								 * WP Rocket Cache.
								 */
								case 'wp-rocket':

									$chk[ 'function' ] = 'rocket_clean_domain';

									break;

								/*
								 * WP Super Cache.
								 *
								 * See https://wordpress.org/plugins/wp-super-cache/.
								 */
								case 'wp-super':

									$chk[ 'function' ] = 'wp_cache_clear_cache';

									break;
							}

							break;

						case 'ecom':

							switch ( $id ) {

								/*
								 * Perfect WooCommerce Brands.
								 */
								case 'perfect-woocommerce-brands':

									$chk[ 'class' ] = '\Perfect_Woocommerce_Brands\Perfect_Woocommerce_Brands';

									break;

								/*
								 * WooCommerce.
								 *
								 * See https://wordpress.org/plugins/woocommerce/.
								 */
								case 'woocommerce':

									$chk[ 'class' ] = 'WooCommerce';

									$chk[ 'version_const' ] = 'WC_VERSION';

									$chk[ 'min_version' ] = '5.0.0';

									break;

								/*
								 * WooCommerce Brands.
								 */
								case 'woocommerce-brands':

									$chk[ 'class' ] = 'WC_Brands';

									break;

								/*
								 * WooCommerce Currency Switcher.
								 */
								case 'woocommerce-currency-switcher':

									$chk[ 'class' ] = 'WOOCS';

									break;

								/*
								 * WooCommerce UPC, EAN, and ISBN.
								 */
								case 'woo-add-gtin':

									$chk[ 'class' ] = 'Woo_GTIN';

									break;

								/*
								 * Product GTIN for WooCommerce.
								 */
								case 'wpm-product-gtin-wc':

									$chk[ 'class' ] = 'WPM_Product_GTIN_WC';

									break;

								/*
								 * YITH WooCommerce Brands Add-on.
								 */
								case 'yith-woocommerce-brands':

									$chk[ 'class' ] = 'YITH_WCBR';

									break;
							}

							break;

						case 'data':

							switch ( $id ) {

								/*
								 * Import All in One SEO Pack metadata.
								 */
								case 'aioseop-meta':

									$chk[ 'opt_key' ] = 'plugin_import_aioseop_meta';

									break;

								/*
								 * Import Rank Math SEO metadata.
								 */
								case 'rankmath-meta':

									$chk[ 'opt_key' ] = 'plugin_import_rankmath_meta';

									break;

								/*
								 * Import The SEO Framework metadata.
								 */
								case 'seoframework-meta':

									$chk[ 'opt_key' ] = 'plugin_import_seoframework_meta';

									break;

								/*
								 * Import WP Meta SEO metadata.
								 */
								case 'wpmetaseo-meta':

									$chk[ 'opt_key' ] = 'plugin_import_wpmetaseo_meta';

									break;

								/*
								 * Import Yoast SEO block attrs.
								 */
								case 'wpseo-blocks':

									$chk[ 'opt_key' ] = 'plugin_import_wpseo_blocks';

									break;

								/*
								 * Import Yoast SEO metadata.
								 */
								case 'wpseo-meta':

									$chk[ 'opt_key' ] = 'plugin_import_wpseo_meta';

									break;
							}

							break;

						case 'event':

							switch ( $id ) {

								/*
								 * The Events Calendar.
								 *
								 * See https://wordpress.org/plugins/the-events-calendar/.
								 */
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

						case 'job':

							switch ( $id ) {

								/*
								 * Simple Job Board.
								 *
								 * See https://wordpress.org/plugins/simple-job-board/.
								 */
								case 'simplejobboard':

									$chk[ 'class' ] = 'Simple_Job_Board';

									break;

								/*
								 * WP Job Manager.
								 *
								 * See https://wordpress.org/plugins/wp-job-manager/.
								 */
								case 'wpjobmanager':

									$chk[ 'class' ] = 'WP_Job_Manager';

									break;
							}

							break;

						case 'lang':

							switch ( $id ) {

								/*
								 * GTranslate.
								 *
								 * See https://wordpress.org/plugins/gtranslate/.
								 */
								case 'gtranslate':

									$chk[ 'class' ] = 'GTranslate';

									break;

								/*
								 * Polylang.
								 *
								 * See https://wordpress.org/plugins/polylang/.
								 */
								case 'polylang':

									$chk[ 'class' ] = 'Polylang';

									break;

								/*
								 * WPML.
								 *
								 * See https://wpml.org/.
								 */
								case 'wpml':

									$chk[ 'class' ] = 'SitePress';

									break;
							}

							break;

						case 'media':

							switch ( $id ) {

								/*
								 * Perfect Images (aka WP Retina 2x).
								 *
								 * See https://wordpress.org/plugins/wp-retina-2x/.
								 */
								case 'wp-retina-2x':

									$chk[ 'class' ] = 'Meow_WR2X_Core';

									break;

								/*
								 * Premium edition feature / option.
								 */
								case 'facebook':		// Check for Embedded Media: Facebook Videos.
								case 'slideshare':		// Check for Embedded Media: Slideshare Presentations.
								case 'soundcloud':		// Check for Embedded Media: Soundcloud Tracks.
								case 'vimeo':			// Check for Embedded Media: Vimeo Videos.
								case 'wistia':			// Check for Embedded Media: Wistia Videos.
								case 'wpvideoshortcode':	// Check for Embedded Media: WordPress Video Shortcodes.
								case 'youtube':			// Check for Embedded Media: Youtube Videos and Playlists.

									$chk[ 'opt_key' ] = 'plugin_' . $id . '_api';

									break;

								/*
								 * Premium edition feature / option.
								 */
								case 'gravatar':	// Gravatar is Default Author Image.

									$chk[ 'opt_key' ] = 'plugin_' . $id . '_api';

									break;

								/*
								 * Premium edition feature / option.
								 */
								case 'upscale':	// Upscale Media Library Images.

									$chk[ 'opt_key' ] = 'plugin_upscale_images';

									break;
							}

							break;

						case 'p':

							switch ( $id ) {

								case 'schema':

									$schema_disable = SucomUtil::get_const( 'WPSSO_SCHEMA_MARKUP_DISABLE' );

									$get_avail[ $sub ][ $id ] = $schema_disable ? false : true;

									break;
							}

							break;

						case 'rating':

							switch ( $id ) {

								/*
								 * Rate my Post.
								 *
								 * https://wordpress.org/plugins/rate-my-post/
								 */
								case 'rate-my-post':

									$chk[ 'class' ] = 'Rate_My_Post';

									break;

								/*
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

								/*
								 * WP Recipe Maker.
								 *
								 * See https://wordpress.org/plugins/wp-recipe-maker/.
								 */
								case 'wprecipemaker':

									$chk[ 'class' ] = 'WP_Recipe_Maker';

									break;
							}

							break;

						case 'review':

							switch ( $id ) {

								case 'shopperapproved':

									$chk[ 'opt_key' ] = array(
										'plugin_ratings_reviews_svc'     => 'shopperapproved',
										'plugin_shopperapproved_site_id' => null,	// Any non-empty value.
										'plugin_shopperapproved_token'   => null,	// Any non-empty value.
									);

									break;

								case 'stamped':

									$chk[ 'opt_key' ] = array(
										'plugin_ratings_reviews_svc' => 'stamped',
										'plugin_stamped_store_hash'  => null,	// Any non-empty value.
										'plugin_stamped_key_public'  => null,	// Any non-empty value.
									);

									break;

								/*
								 * WP Product Review Lite.
								 *
								 * See https://wordpress.org/plugins/wp-product-review/.
								 */
								case 'wpproductreview':

									$chk[ 'class' ] = 'WPPR';

									break;

								/*
								 * Yotpo: Product & Photo Reviews for WooCommerce.
								 *
								 * See https://wordpress.org/plugins/yotpo-social-reviews-for-woocommerce/.
								 */
								case 'yotpowc':

									$chk[ 'function' ] = 'wc_yotpo_init';

									break;
							}

							break;

						case 'seo':

							switch ( $id ) {

								/*
								 * All in One SEO Pack.
								 *
								 * See https://wordpress.org/plugins/all-in-one-seo-pack/.
								 */
								case 'aioseop':

									$chk[ 'function' ] = 'aioseo';

									break;

								/*
								 * Jetpack SEO module.
								 *
								 * See https://wordpress.org/plugins/jetpack/.
								 */
								case 'jetpack-seo':

									if ( method_exists( 'Jetpack', 'get_active_modules' ) ) {

										$jetpack_modules = Jetpack::get_active_modules();

										if ( in_array( 'seo-tools', $jetpack_modules ) ) {

											$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
										}
									}

									break;

								/*
								 * Rank Math.
								 *
								 * See https://wordpress.org/plugins/seo-by-rank-math/.
								 */
								case 'rankmath':

									$chk[ 'class' ] = 'RankMath';

									break;

								/*
								 * The SEO Framework.
								 *
								 * See https://wordpress.org/plugins/autodescription/.
								 */
								case 'seoframework':

									$chk[ 'function' ] = 'the_seo_framework';

									break;

								/*
								 * SEOPress.
								 *
								 * See https://wordpress.org/plugins/wp-seopress/.
								 */
								case 'seopress':

									$chk[ 'function' ] = 'seopress_init';

									break;

								/*
								 * SEO Ultimate.
								 *
								 * See https://wordpress.org/plugins/seo-ultimate/.
								 */
								case 'seoultimate':

									$chk[ 'plugin' ] = 'seo-ultimate/seo-ultimate.php';

									break;

								/*
								 * Slim SEO.
								 *
								 * See https://wordpress.org/plugins/slim-seo/.
								 */
								case 'slim-seo':

									$chk[ 'plugin' ] = 'slim-seo/slim-seo.php';

									break;

								/*
								 * SEO 2020 by Squirrly.
								 *
								 * https://wordpress.org/plugins/squirrly-seo/.
								 */
								case 'squirrlyseo':

									$chk[ 'plugin' ] = 'squirrly-seo/squirrly.php';

									break;

								/*
								 * WP Meta SEO.
								 *
								 * See https://wordpress.org/plugins/wp-meta-seo/.
								 */
								case 'wpmetaseo':

									$chk[ 'class' ] = 'WpMetaSeo';

									break;

								/*
								 * Yoast SEO.
								 *
								 * See https://wordpress.org/plugins/wordpress-seo/.
								 */
								case 'wpseo':

									$chk[ 'function' ] = 'wpseo_init';

									break;

								/*
								 * Yoast WooCommerce SEO.
								 *
								 * https://yoast.com/wordpress/plugins/yoast-woocommerce-seo/.
								 */
								case 'wpseo-wc':

									$chk[ 'class' ] = 'Yoast_WooCommerce_SEO';

									break;
							}

							break;

						case 'user':

							switch ( $id ) {

								/*
								 * Co-Authors Plus.
								 *
								 * See https://wordpress.org/plugins/co-authors-plus/.
								 */
								case 'coauthors':

									$chk[ 'plugin' ] = 'co-authors-plus/co-authors-plus.php';

									break;

								/*
								 * Ultimate Member.
								 *
								 * See https://wordpress.org/plugins/ultimate-member/.
								 */
								case 'ultimate-member':

									$chk[ 'class' ] = 'UM';

									break;
							}

							break;

						case 'util':

							switch ( $id ) {

								/*
								 * Autoptimize.
								 *
								 * See https://wordpress.org/plugins/autoptimize/.
								 */
								case 'autoptimize':

									$chk[ 'class' ] = 'autoptimizeCache';

									break;

								/*
								 * Elementor.
								 */
								case 'elementor':

									$chk[ 'constant' ] = 'ELEMENTOR_VERSION';

									break;

								/*
								 * Jetpack.
								 *
								 * See https://wordpress.org/plugins/jetpack/.
								 */
								case 'jetpack':

									$chk[ 'class' ] = 'Jetpack';

									break;

								/*
								 * URL Shortening Service.
								 */
								case 'shorten':

									$chk[ 'opt_key' ] = 'plugin_shortener';

									break;
							}

							break;
					}

					/*
					 * Check for plugin classes and functions first, to include both free and pro / premium
					 * plugins that have different plugin slugs, but use the same class or function names.
					 */
					if ( ! empty( $chk ) ) {

						if ( isset( $chk[ 'class' ] ) || isset( $chk[ 'function' ] ) || isset( $chk[ 'plugin' ] ) ) {

							if ( ( ! empty( $chk[ 'class' ] ) && class_exists( $chk[ 'class' ] ) ) ||
								( ! empty( $chk[ 'function' ] ) && function_exists( $chk[ 'function' ] ) ) ||
								( ! empty( $chk[ 'plugin' ] ) && SucomPlugin::is_plugin_active( $chk[ 'plugin' ] ) ) ) {

								$version = false;

								if ( ! empty( $chk[ 'min_version' ] ) ) {

									if ( ! empty( $chk[ 'version_global' ] ) ) {

										$version = $GLOBALS[ $chk[ 'version_global' ] ];

									} elseif ( ! empty( $chk[ 'version_const' ] ) ) {

										$version = constant( $chk[ 'version_const' ] );
									}
								}

								if ( ! $version || version_compare( $version, $chk[ 'min_version' ], '>=' ) ) {

									$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
								}
							}

						} elseif ( isset( $chk[ 'opt_key' ] ) ) {

							if ( is_array( $chk[ 'opt_key' ] ) ) {

								$all_enabled = true;

								foreach ( $chk[ 'opt_key' ] as $key => $val ) {

									if ( ! $this->is_opt_enabled( $key, $val ) ) {

										$all_enabled = false;

										break;
									}
								}

								if ( $all_enabled ) {

									$get_avail[ $sub ][ 'any' ] = $get_avail[ $sub ][ $id ] = true;
								}

							} elseif ( $this->is_opt_enabled( $chk[ 'opt_key' ] ) ) {

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

			/*
			 * Define WPSSO_UNKNOWN_SEO_PLUGIN_ACTIVE as true to disable WPSSO's SEO related meta tags and features.
			 */
			if ( defined( 'WPSSO_UNKNOWN_SEO_PLUGIN_ACTIVE' ) && WPSSO_UNKNOWN_SEO_PLUGIN_ACTIVE ) {

				$get_avail[ 'seo' ][ 'any' ] = true;
			}

			/*
			 * This method is run once by Wpsso->set_objects() and typically runs in 0.0002 secs.
			 */
			$get_avail[ 'p' ][ 'avail_mtime' ] = microtime( $get_float = true ) - $mtime_start;

			$get_avail = apply_filters( 'wpsso_get_avail', $get_avail );

			return $get_avail;
		}

		public function is_pp( $ext = null, $rc = true ) {

			return $this->pp( $ext, $li = true, $rv = true, $rc );
		}

		public function pp( $ext = null, $li = true, $rv = true, $rc = true, $mx = null ) {

			static $lc = array();

			$ext = null === $ext ? $this->p->id : $ext;
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

		public function get_ext_auth_type( $ext ) {

			return empty( $this->p->cf[ 'plugin' ][ $ext ][ 'update_auth' ] ) ? 'none' : $this->p->cf[ 'plugin' ][ $ext ][ 'update_auth' ];
		}

		public function get_ext_auth_id( $ext ) {

			$ext_auth_type = $this->get_ext_auth_type( $ext );
			$ext_auth_key  = 'plugin_' . $ext . '_' . $ext_auth_type;

			return empty( $this->p->options[ $ext_auth_key ] ) ? '' : $this->p->options[ $ext_auth_key ];
		}

		private function is_opt_enabled( $key, $val = null ) {

			if ( ! empty( $key ) ) {	// Just in case.

				if ( null === $val ) {

					if ( ! empty( $this->p->options[ $key ] ) ) {	// Not 0 or empty string.

						if ( $this->p->options[ $key ] !== 'none' ) {

							return true;
						}
					}

				} elseif ( isset( $this->p->options[ $key ] ) ) {	// Just in case.

					if ( $val === $this->p->options[ $key ] ) {

						return true;
					}
				}
			}

			return false;
		}
	}
}

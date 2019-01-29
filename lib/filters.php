<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoFilters' ) ) {

	class WpssoFilters {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( is_admin() ) {

				/**
				 * Cleanup incorrect Yoast SEO notifications.
				 */
				if ( function_exists( 'wpseo_init' ) ) {	// Includes wpseo premium.
					add_action( 'admin_init', array( $this, 'cleanup_wpseo_notifications' ), 15 );
				}

				if ( class_exists( 'GFForms' ) ) {
					add_action( 'gform_noconflict_styles', array( $this, 'update_gform_noconflict_styles' ) );
					add_action( 'gform_noconflict_scripts', array( $this, 'update_gform_noconflict_scripts' ) );
				}

				if ( class_exists( 'GravityView_Plugin' ) ) {
					add_action( 'gravityview_noconflict_styles', array( $this, 'update_gform_noconflict_styles' ) );
					add_action( 'gravityview_noconflict_scripts', array( $this, 'update_gform_noconflict_scripts' ) );
				}

			} else {

				/**
				 * Disable JetPack open graph meta tags.
				 */
				if ( SucomPlugin::is_plugin_active( 'jetpack/jetpack.php', $use_cache = true ) ) {
					add_filter( 'jetpack_enable_opengraph', '__return_false', 1000 );
					add_filter( 'jetpack_enable_open_graph', '__return_false', 1000 );
					add_filter( 'jetpack_disable_twitter_cards', '__return_true', 1000 );
				}

				/**
				 * Disable Yoast SEO social meta tags.
				 *
				 * Must be executed after add_action( 'template_redirect', 'wpseo_frontend_head_init', 999 );
				 */
				if ( function_exists( 'wpseo_init' ) ) {	// Includes wpseo premium.
					add_action( 'template_redirect', array( $this, 'cleanup_wpseo_filters' ), 9000 );
				}

				/**
				 * Honor the FORCE_SSL constant on the front-end with a 301 redirect.
				 */
				if ( ! empty( $this->p->options['plugin_honor_force_ssl'] ) ) {
					if ( SucomUtil::get_const( 'FORCE_SSL' ) ) {
						add_action( 'wp_loaded', array( __CLASS__, 'force_ssl_redirect' ), -1000 );
					}
				}

				/**
				 * Prevent SNAP from adding meta tags for the Facebook user agent.
				 */
				if ( function_exists( 'nxs_initSNAP' ) ) {
					add_action( 'wp_head', array( __CLASS__, 'remove_snap_og_meta_tags_holder' ), -1000 );
				}
			}
		}

		public function update_gform_noconflict_styles( $styles ) {

			return array_merge( $styles, array(
				'jquery-ui.js',
				'jquery-qtip.js',
				'sucom-admin-page',
				'sucom-settings-table',
				'sucom-metabox-tabs',
				'wp-color-picker',
			) );
		}

		public function update_gform_noconflict_scripts( $scripts ) {

			return array_merge( $scripts, array(
				'jquery-ui-datepicker',
				'jquery-qtip',
				'sucom-metabox',
				'sucom-tooltips',
				'wp-color-picker',
				'sucom-admin-media',
			) );
		}

		/**
		 * Cleanup incorrect Yoast SEO notifications.
		 */
		public function cleanup_wpseo_notifications() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Yoast SEO only checks for a conflict with WPSSO if the Open Graph option is enabled.
			 */
			if ( method_exists( 'WPSEO_Options', 'get' ) ) {
				if ( ! WPSEO_Options::get( 'opengraph' ) ) {
					return;
				}
			}

			if ( class_exists( 'Yoast_Notification_Center' ) ) {

				$info = $this->p->cf[ 'plugin' ][$this->p->lca];
				$name = $this->p->cf[ 'plugin' ][$this->p->lca][ 'name' ];

				/**
				 * Since WordPress SEO v4.0.
				 */
				if ( method_exists( 'Yoast_Notification_Center', 'get_notification_by_id' ) ) {

					$notif_id     = 'wpseo-conflict-' . md5( $info[ 'base' ] );
					$notif_msg    = '<style>#' . $notif_id . '{display:none;}</style>';	// Hide our empty notification. ;-)
					$notif_center = Yoast_Notification_Center::get();
					$notif_obj    = $notif_center->get_notification_by_id( $notif_id );

					if ( empty( $notif_obj ) ) {
						return;
					}

					/**
					 * Not that Yoast_Notification::render() wraps the notification message with
					 * '<div class="yoast-alert"></div>'.
					 */
					if ( method_exists( 'Yoast_Notification', 'render' ) ) {
						$notif_html = $notif_obj->render();
					} else {
						$notif_html = $notif_obj->message;
					}

					if ( strpos( $notif_html, $notif_msg ) === false ) {

						update_user_meta( get_current_user_id(), $notif_obj->get_dismissal_key(), 'seen' );

						$notif_obj = new Yoast_Notification( $notif_msg, array( 'id' => $notif_id ) );

						$notif_center->add_notification( $notif_obj );
					}

				} elseif ( defined( 'Yoast_Notification_Center::TRANSIENT_KEY' ) ) {

					if ( false !== ( $wpseo_notif = get_transient( Yoast_Notification_Center::TRANSIENT_KEY ) ) ) {

						$wpseo_notif = json_decode( $wpseo_notif, $assoc = false );

						if ( ! empty( $wpseo_notif ) ) {

							foreach ( $wpseo_notif as $num => $notif_msgs ) {

								if ( isset( $notif_msgs->options->type ) && $notif_msgs->options->type == 'error' ) {

									if ( false !== strpos( $notif_msgs->message, $name ) ) {

										unset( $wpseo_notif[$num] );

										set_transient( Yoast_Notification_Center::TRANSIENT_KEY,
											json_encode( $wpseo_notif ) );
									}
								}
							}
                                        	}
					}
				}
			}
		}

		/**
		 * Disable Yoast SEO social meta tags.
		 */
		public function cleanup_wpseo_filters() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( isset( $GLOBALS['wpseo_og'] ) && is_object( $GLOBALS['wpseo_og'] ) ) {

				if ( false !== ( $prio = has_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ) ) ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'removing wpseo_head action for opengraph' );
					}

					$ret = remove_action( 'wpseo_head', array( $GLOBALS['wpseo_og'], 'opengraph' ), $prio );
				}
			}

			if ( class_exists( 'WPSEO_Twitter' ) ) {

				if ( false !== ( $prio = has_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ) ) ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'removing wpseo_head action for twitter' );
					}

					$ret = remove_action( 'wpseo_head', array( 'WPSEO_Twitter', 'get_instance' ), $prio );
				}
			}

			if ( isset( WPSEO_Frontend::$instance ) ) {

				if ( false !== ( $prio = has_action( 'wpseo_head', array( WPSEO_Frontend::$instance, 'publisher' ) ) ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'removing wpseo_head action for publisher' );
					}

					$ret = remove_action( 'wpseo_head', array( WPSEO_Frontend::$instance, 'publisher' ), $prio );
				}
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'disabling wpseo_json_ld_output filters' );
			}

			add_filter( 'wpseo_json_ld_output', '__return_empty_array', 9000 );
		}

		/**
		 * Redirect from HTTP to HTTPS if the current webpage URL is not HTTPS.
		 * A 301 redirect is considered a best practice when moving from HTTP to
		 * HTTPS. See https://en.wikipedia.org/wiki/HTTP_301 for more info.
		 */
		public static function force_ssl_redirect() {

			/**
			 * Check for web server variables in case WP is being used from the command line.
			 */
			if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {

				if ( ! SucomUtil::is_https() ) {

					wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );

					exit();
				}
			}
		}

		public static function remove_snap_og_meta_tags_holder() {

			remove_action( 'wp_head', 'nxs_addOGTagsPreHolder', 150 );
		}
	}
}

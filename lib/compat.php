<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoCompat' ) ) {

	/*
	 * Third-party plugin and theme compatibility actions and filters.
	 */
	class WpssoCompat {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->common_hooks();

			is_admin() ? $this->back_end_hooks() : $this->front_end_hooks();
		}

		public function common_hooks() {

			/*
			 * All in One SEO Pack.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'aioseop' ] ) ) {

				add_filter( 'aioseo_schema_disable', '__return_true', 1000 );
			}

			/*
			 * WooCommerce.
			 */
			if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {

				add_filter( 'woocommerce_structured_data_product', '__return_empty_array', PHP_INT_MAX );
				add_filter( 'woocommerce_structured_data_review', '__return_empty_array', PHP_INT_MAX );
				add_filter( 'woocommerce_structured_data_website', '__return_empty_array', PHP_INT_MAX );
			}
		}

		public function back_end_hooks() {

			/*
			 * Gravity Forms and Gravity View.
			 */
			if ( class_exists( 'GFForms' ) ) {

				add_action( 'gform_noconflict_styles', array( $this, 'update_gform_noconflict_styles' ) );
				add_action( 'gform_noconflict_scripts', array( $this, 'update_gform_noconflict_scripts' ) );
			}

			if ( class_exists( 'GravityView_Plugin' ) ) {

				add_action( 'gravityview_noconflict_styles', array( $this, 'update_gform_noconflict_styles' ) );
				add_action( 'gravityview_noconflict_scripts', array( $this, 'update_gform_noconflict_scripts' ) );
			}

			/*
			 * Rank Math.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'rankmath' ] ) ) {

				$this->p->util->add_plugin_filters( $this, array( 'admin_page_style_css_rankmath' => array( 'admin_page_style_css' => 1 ) ) );
			}

			/*
			 * SEOPress.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'seopress' ] ) ) {

				add_filter( 'seopress_metabox_seo_tabs', array( $this, 'cleanup_seopress_tabs' ), 1000 );
			}

			/*
			 * The SEO Framework.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'seoframework' ] ) ) {

				add_action( 'current_screen', array( $this, 'cleanup_seoframework_edit_view' ), PHP_INT_MIN, 1 );

				add_filter( 'the_seo_framework_inpost_settings_tabs', array( $this, 'cleanup_seoframework_tabs' ), 1000 );
			}

			/*
			 * Yoast SEO.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) ) {

				add_action( 'admin_init', array( $this, 'cleanup_wpseo_notifications' ), 15 );

				$this->p->util->add_plugin_filters( $this, array( 'admin_page_style_css_wpseo' => array( 'admin_page_style_css' => 1 ) ) );
			}
		}

		public function front_end_hooks() {

			/*
			 * JetPack.
			 */
			if ( ! empty( $this->p->avail[ 'util' ][ 'jetpack' ] ) ) {

				add_filter( 'jetpack_enable_opengraph', '__return_false', 1000 );
				add_filter( 'jetpack_enable_open_graph', '__return_false', 1000 );
				add_filter( 'jetpack_disable_twitter_cards', '__return_true', 1000 );
			}

			/*
			 * NextScripts: Social Networks Auto-Poster.
			 */
			if ( function_exists( 'nxs_initSNAP' ) ) {

				add_action( 'wp_head', array( $this, 'remove_snap_og_meta_tags_holder' ), -2000 );
			}

			/*
			 * Rank Math.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'rankmath' ] ) ) {

				add_action( 'rank_math/head', array( $this, 'cleanup_rankmath_actions' ), -2000 );

				add_filter( 'rank_math/json_ld', array( $this, 'cleanup_rankmath_json_ld' ), PHP_INT_MAX );
			}

			/*
			 * SEOPress.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'seopress' ] ) ) {

				add_filter( 'seopress_titles_author', '__return_empty_string', 1000 );
			}

			/*
			 * Yoast SEO.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'wpseo' ] ) ) {

				add_filter( 'wpseo_frontend_presenters', array( $this, 'cleanup_wpseo_frontend_presenters' ), 1000, 1 );
				add_filter( 'wpseo_schema_graph', array( $this, 'cleanup_wpseo_schema_graph' ), 1000, 2 );
			}
		}

		public function update_gform_noconflict_styles( $styles ) {

			return array_merge( $styles, array(
				'jquery-ui.js',
				'jquery-qtip.js',
				'sucom-metabox-tabs',
				'sucom-settings-page',
				'sucom-settings-table',
				'wp-color-picker',
				'wpsso-admin-page',
			) );
		}

		public function update_gform_noconflict_scripts( $scripts ) {

			return array_merge( $scripts, array(
				'jquery-ui-datepicker',
				'jquery-qtip',
				'sucom-admin-media',
				'sucom-admin-page',
				'sucom-metabox',
				'sucom-settings-page',
				'sucom-tooltips',
				'wp-color-picker',
				'wpsso-metabox',
				'wpsso-block-editor',
			) );
		}

		public function cleanup_seoframework_edit_view( $screen = false ) {

			if ( in_array( $screen->base, array( 'profile', 'user-edit' ) ) ) {

				/*
				 * Remove the "Authorial Info" section from the user editing page.
				 */
				SucomUtilWP::remove_filter_hook_name( 'current_screen', 'The_SEO_Framework\Load::_init_user_edit_view' );
			}
		}

		public function cleanup_seoframework_tabs( $tabs ) {

			unset( $tabs[ 'social' ] );

			return $tabs;
		}

		public function cleanup_seopress_tabs( $tabs ) {

			unset( $tabs[ 'social-tab' ] );

			return $tabs;
		}

		/*
		 * Cleanup incorrect Yoast SEO notifications.
		 */
		public function cleanup_wpseo_notifications() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Yoast SEO only checks for a conflict with WPSSO if the Open Graph option is enabled.
			 */
			if ( method_exists( 'WPSEO_Options', 'get' ) ) {

				if ( ! WPSEO_Options::get( 'opengraph' ) ) {

					return;
				}
			}

			if ( class_exists( 'Yoast_Notification_Center' ) ) {

				$info = $this->p->cf[ 'plugin' ][ $this->p->id ];
				$name = $this->p->cf[ 'plugin' ][ $this->p->id ][ 'name' ];

				if ( method_exists( 'Yoast_Notification_Center', 'get_notification_by_id' ) ) {

					$notif_id     = 'wpseo-conflict-' . md5( $info[ 'base' ] );
					$notif_msg    = '<style type="text/css">#' . $notif_id . '{display:none;}</style>';	// Hide our empty notification. ;-)
					$notif_center = Yoast_Notification_Center::get();
					$notif_obj    = $notif_center->get_notification_by_id( $notif_id );

					if ( empty( $notif_obj ) ) {

						return;
					}

					/*
					 * Note that Yoast_Notification::render() wraps the notification message with
					 * '<div class="yoast-alert"></div>'.
					 */
					if ( method_exists( 'Yoast_Notification', 'render' ) ) {

						$notif_html = $notif_obj->render();

					} else {

						$notif_html = $notif_obj->message;
					}

					if ( strpos( $notif_html, $notif_msg ) === false ) {

						update_metadata( 'user', get_current_user_id(), $notif_obj->get_dismissal_key(), 'seen' );

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

										unset( $wpseo_notif[ $num ] );

										set_transient( Yoast_Notification_Center::TRANSIENT_KEY, wp_json_encode( $wpseo_notif ) );
									}
								}
							}
                                        	}
					}
				}
			}
		}

		/*
		 * Since Yoast SEO v14.0.
		 *
		 * Disable Yoast SEO social meta tags.
		 *
		 * Yoast SEO provides two arguments to this filter, but older versions only provided one.
		 */
		public function cleanup_wpseo_frontend_presenters( $presenters ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$remove = array( 'Open_Graph', 'Slack', 'Twitter', 'WooCommerce' );

			$remove_preg = '/(' . implode( '|', $remove ) . ')/';

			foreach ( $presenters as $num => $obj ) {

				$class_name = get_class( $obj );

				if ( preg_match( $remove_preg, $class_name ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'removing presenter: ' . $class_name );
					}

					unset( $presenters[ $num ] );

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping presenter: ' . $class_name );
					}
				}
			}

			return $presenters;
		}

		public function cleanup_wpseo_schema_graph( $graph, $context ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				/*
				 * Remove everything except for the BreadcrumbList markup.
				 *
				 * The WPSSO BC add-on removes the BreadcrumbList markup.
				 */
				foreach ( $graph as $num => $piece ) {

					if ( ! empty( $piece[ '@type' ] ) ) {

						if ( 'BreadcrumbList' === $piece[ '@type' ] ) {	// Keep breadcrumbs.

							continue;
						}

					}

					unset( $graph[ $num ] );	// Remove everything else.
				}
			}

			return array_values( $graph );
		}

		/*
		 * Disable Rank Math Facebook and Twitter meta tags.
		 */
		public function cleanup_rankmath_actions() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Disable Rank Math social meta tags.
			 */
			remove_all_actions( 'rank_math/opengraph/facebook' );
			remove_all_actions( 'rank_math/opengraph/slack' );
			remove_all_actions( 'rank_math/opengraph/twitter' );
		}

		/*
		 * Disable Rank Math Schema markup.
		 */
		public function cleanup_rankmath_json_ld( $data ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Remove everything except for the BreadcrumbList markup.
			 *
			 * The WPSSO BC add-on removes the BreadcrumbList markup.
			 */
			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				return SucomUtil::preg_grep_keys( '/^BreadcrumbList$/', $data );
			}

			return $data;
		}

		public function remove_snap_og_meta_tags_holder() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Prevent SNAP from adding meta tags for the Facebook user agent.
			 */
			remove_action( 'wp_head', 'nxs_addOGTagsPreHolder', 150 );
		}

		/*
		 * Fix Rank Math CSS on back-end pages.
		 */
		public function filter_admin_page_style_css_rankmath( $custom_style_css ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Fix the width of Rank Math list table columns.
			 */
			$custom_style_css .= '
				table.wp-list-table > thead > tr > th.column-rank_math_seo_details,
				table.wp-list-table > tbody > tr > td.column-rank_math_seo_details {
					width:170px;
				}
			';

			/*
			 * The "Social" metabox tab and its options cannot be disabled, so hide them instead.
			 */
			$custom_style_css .= '
				.rank-math-tabs > div > a[href="#setting-panel-social"] { display: none; }
				.rank-math-tabs-content .setting-panel-social { display: none; }
			';

			/*
			 * The "Schema" metabox tab and its options cannot be disabled, so hide them instead.
			 */
			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				$custom_style_css .= '
					.rank-math-tabs > div > a[href="#setting-panel-richsnippet"] { display: none; }
					.rank-math-tabs-content .setting-panel-richsnippet { display: none; }
				';
			}

			return $custom_style_css;
		}

		/*
		 * Fix Yoast SEO CSS on back-end pages.
		 */
		public function filter_admin_page_style_css_wpseo( $custom_style_css ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Fix the width of Yoast SEO list table columns.
			 */
			$custom_style_css .= '
				table.wp-list-table > thead > tr > th.column-wpseo-links,
				table.wp-list-table > tbody > tr > td.column-wpseo-links,
				table.wp-list-table > thead > tr > th.column-wpseo-linked,
				table.wp-list-table > tbody > tr > td.column-wpseo-linked,
				table.wp-list-table > thead > tr > th.column-wpseo-score,
				table.wp-list-table > tbody > tr > td.column-wpseo-score,
				table.wp-list-table > thead > tr > th.column-wpseo-score-readability,
				table.wp-list-table > tbody > tr > td.column-wpseo-score-readability {
					width:40px;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-title,
				table.wp-list-table > tbody > tr > td.column-wpseo-title,
				table.wp-list-table > thead > tr > th.column-wpseo-metadesc,
				table.wp-list-table > tbody > tr > td.column-wpseo-metadesc {
					width:20%;
				}
				table.wp-list-table > thead > tr > th.column-wpseo-focuskw,
				table.wp-list-table > tbody > tr > td.column-wpseo-focuskw {
					width:8em;	/* Leave room for the sort arrow. */
				}
			';

			/*
			 * The "Schema" metabox tab and its options cannot be disabled, so hide them instead.
			 */
			if ( ! empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				$custom_style_css .= '
					#wpseo-meta-tab-schema { display: none; }
					#wpseo-meta-section-schema { display: none; }
				';
			}

			return $custom_style_css;
		}
	}
}

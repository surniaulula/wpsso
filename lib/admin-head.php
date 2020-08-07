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

if ( ! class_exists( 'WpssoAdminHead' ) ) {

	class WpssoAdminHead {

		private $p;

		/**
		 * Instantiated by WpssoAdmin->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! SucomUtil::get_const( 'DOING_AJAX' ) ) {

				add_action( 'admin_head', array( $this, 'update_count_notice' ), -200 );
				add_action( 'admin_head', array( $this, 'requires_notices' ), -100 );
				add_action( 'admin_head', array( $this, 'suggest_addons' ), 100 );
				add_action( 'admin_head', array( $this, 'timed_notices' ), 200 );
			}
		}

		/**
		 * Show a notice if there are pending WPSSO plugin updates and the user can update plugins.
		 */
		public function update_count_notice() {

			if ( ! current_user_can( 'update_plugins' ) ) {

				return;
			}

			/**
			 * Check the 'update_plugins' site transient and return the number of updates pending for a given slug
			 * prefix.
			 */
			$update_count = SucomPlugin::get_updates_count( $plugin_prefix = $this->p->lca );

			if ( $update_count > 0 ) {

				$info = $this->p->cf[ 'plugin' ][ $this->p->lca ];

				$notice_key = 'have-updates-for-' . $this->p->lca;

				$notice_msg = sprintf( _n( 'There is <a href="%1$s">%2$d pending update for the %3$s plugin and its add-on(s)</a>.',
					'There are <a href="%1$s">%2$d pending updates for the %3$s plugin and its add-on(s)</a>.', $update_count, 'wpsso' ),
						self_admin_url( 'update-core.php' ), $update_count, $info[ 'short' ] ) . ' ';

				$notice_msg .= _n( 'Please install this update at your earliest convenience.',
					'Please install these updates at your earliest convenience.', $update_count, 'wpsso' );

				$this->p->notice->inf( $notice_msg, null, $notice_key );
			}
		}

		public function requires_notices() {

			$pkg      = $this->p->admin->plugin_pkg_info();
			$um_info  = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
			$have_tid = false;

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) ) {

					$have_tid = true;	// Found at least one plugin with an auth id.

					/**
					 * If the update manager is active, its version should be available.
					 *
					 * If the update manager version is defined, the skip the warning notices and show a nag
					 * notice to install the update manager.
					 */
					if ( empty( $um_info[ 'version' ] ) ) {

						break;

					} else {

						if ( empty( $pkg[ $ext ][ 'pdir' ] ) ) {

							if ( ! empty( $info[ 'base' ] ) && ! SucomPlugin::is_plugin_installed( $info[ 'base' ], $use_cache = true ) ) {

								$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-installed', array( 'lca' => $ext ) ) );
							} else {
								$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-updated', array( 'lca' => $ext ) ) );
							}
						}
					}
				}
			}

			if ( true === $have_tid ) {

				/**
				 * If the update manager is active, its version should be available.
				 */
				if ( ! empty( $um_info[ 'version' ] ) ) {

					$rec_version = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];

					if ( version_compare( $um_info[ 'version' ], $rec_version, '<' ) ) {

						$this->p->notice->warn( $this->p->msgs->get( 'notice-um-version-recommended' ) );
					}

				/**
				 * Check if update manager is installed.
				 */
				} elseif ( SucomPlugin::is_plugin_installed( $um_info[ 'base' ], $use_cache = true ) ) {

					$this->p->notice->nag( $this->p->msgs->get( 'notice-um-activate-add-on' ) );

				/**
				 * The update manager is not active or installed.
				 */
				} else {

					$this->p->notice->nag( $this->p->msgs->get( 'notice-um-add-on-required' ) );
				}
			}

			if ( $this->p->notice->can_dismiss() && current_user_can( 'manage_options' ) ) {

				foreach ( array( 'wp', 'php' ) as $key ) {

					if ( isset( WpssoConfig::$cf[ $key ][ 'rec_version' ] ) ) {

						switch ( $key ) {

							case 'wp':

								global $wp_version;

								$app_version  = $wp_version;
								$dismiss_time = MONTH_IN_SECONDS;

								break;

							case 'php':

								$app_version  = phpversion();
								$dismiss_time = 3 * MONTH_IN_SECONDS;

								break;

							default:

								continue 2;
						}

						$app_label   = WpssoConfig::$cf[ $key ][ 'label' ];
						$rec_version = WpssoConfig::$cf[ $key ][ 'rec_version' ];

						if ( version_compare( $app_version, $rec_version, '<' ) ) {

							$warn_msg = $this->p->msgs->get( 'notice-recommend-version', array(
								'app_label'   => $app_label,
								'app_version' => $app_version,
								'rec_version' => WpssoConfig::$cf[ $key ][ 'rec_version' ],
								'version_url' => WpssoConfig::$cf[ $key ][ 'version_url' ],
							) );

							$notice_key   = 'notice-recommend-version-' . 
								WpssoConfig::get_version( $add_slug = true ) . '-' . 
									$app_label . '-' . $app_version;

							$this->p->notice->warn( $warn_msg, null, $notice_key, $dismiss_time );
						}
					}
				}
			}
		}

		public function suggest_addons() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $this->p->notice->can_dismiss() || ! current_user_can( 'manage_options' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot dismiss or cannot manage options' );
				}

				return;	// Stop here.
			}

			$this->suggest_addons_woocommerce();
		}

		/**
		 * Suggest activating the WPSSO JSON add-on for better Schema markup.
		 */
		private function suggest_addons_woocommerce() {

			if ( empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'woocommerce is not active' );
				}

				return;
			}

			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {	// Since WPSSO Core v6.23.3.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema markup is disabled' );
				}

				return;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'maybe suggest addons for woocommerce' );
			}

			$ext  = 'wpssojson';
			$info = $this->p->cf[ 'plugin' ][ $ext ];
			$pkg  = $this->p->admin->plugin_pkg_info();

			/**
			 * All good - nothing to suggest.
			 */
			if ( ! empty( $pkg[ $this->p->lca ][ 'pp' ] ) && ! empty( $pkg[ $ext ][ 'pp' ] ) ) {

				return;
			}

			$wc_version   = SucomUtil::get_const( 'WC_VERSION', 0 );
			$action_links = array();
			$notice_key   = 'suggest-' . $ext . '-for-woocommerce';

			/**
			 * Skip if already dismissed.
			 */
			if ( ! $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

				return;
			}

			/**
			 * Maybe add an activate or install link for WPSSO JSON (if not currently active).
			 */
			if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

				if ( SucomPlugin::is_plugin_installed( $info[ 'base' ], $use_cache = true ) ) {

					$url = is_multisite() ? network_admin_url( 'plugins.php', null ) :
						get_admin_url( $blog_id = null, 'plugins.php' );

					$url = add_query_arg( array( 's' => $info[ 'base' ] ), $url );

					$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Activate the %s add-on.',
						'wpsso' ), $pkg[ $ext ][ 'short' ] ) . '</a>';

				} else {

					$url = $this->p->util->get_admin_url( 'addons#' . $ext );

					$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Install and activate the %s add-on.',
						'wpsso' ), $pkg[ $ext ][ 'short' ] ) . '</a>';
				}
			}

			/**
			 * Maybe add a purchase link for the WPSSO Core Premium plugin.
			 */
			if ( empty( $pkg[ $this->p->lca ][ 'pp' ] ) ) {

				$url = add_query_arg( array( 
					'utm_source'  => $this->p->lca,
					'utm_medium'  => 'plugin',
					'utm_content' => $notice_key,
				), $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'url' ][ 'purchase' ] );

				$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Purchase the %s plugin.', 'wpsso' ),
					$pkg[ $this->p->lca ][ 'short_pro' ] ) . '</a>';
			}

			/**
			 * If we have one or more action links, show a notice message with the action links.
			 */
			if ( ! empty( $action_links ) ) {

				$settings_page_link = $this->p->util->get_admin_url( 'setup', _x( 'Setup Guide', 'lib file description', 'wpsso' ) );

				$google_tool_link = '<a href="' . __( 'https://search.google.com/test/rich-results', 'wpsso' ) . '">' .
					__( 'Google Rich Results Test Tool', 'wpsso' ) . '</a>';

				$notice_msg = sprintf( __( 'The WooCommerce v%s plugin is known to offer incomplete Schema markup for Google.', 'wpsso' ), $wc_version ) . ' ';

				$notice_msg .= __( 'The WPSSO Core Premium plugin (required for WooCommerce integration) and its WPSSO Schema JSON-LD Markup add-on provide a far better solution by offering complete Facebook / Pinterest Product meta tags and Schema Product markup for Google Rich Results &mdash; including additional product images, product variations, product information (brand, color, condition, EAN, dimensions, GTIN-8/12/13/14, ISBN, material, MPN, size, SKU, weight, etc), product reviews, product ratings, sale start / end dates, sale prices, pre-tax prices, VAT prices, and much, much more.', 'wpsso' );

				$notice_msg .= '<ul><li>' . implode( '</li><li>', $action_links ) . '</li></ul>' . ' ';

				$notice_msg .= sprintf( __( 'As suggested in the %1$s, you can (and should) submit a few product URLs to the %2$s and make sure your Schema Product markup is complete.', 'wpsso' ), $settings_page_link, $google_tool_link ) . ' ';

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
			}
		}

		public function timed_notices() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $this->p->notice->can_dismiss() || ! current_user_can( 'manage_options' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot dismiss or cannot manage options' );
				}

				return;	// Stop here.
			}

			/**
			 * Only show a single notice at a time.
			 */
			if ( ! $this->single_notice_review() ) {

				if ( ! $this->single_notice_upsell() ) {

					/**
					 * Optionally add more timed notices here.
					 */
				}
			}
		}

		/**
		 * This method is called by $this->timed_notices() only if WordPress can dismiss notices and the user can manage
		 * options.
		 *
		 * Returns 0 by default and 1 if a notice has been created.
		 */
		private function single_notice_review() {

			$form          = $this->p->admin->get_form_object( $this->p->lca );
			$user_id       = get_current_user_id();
			$ext_reg       = $this->p->util->reg->get_ext_reg();
			$week_ago_secs = time() - ( 1 * WEEK_IN_SECONDS );
			$dismiss_time  = true;	// Allow the notice to be dismissed forever.

			/**
			 * Use the transient cache to show only one notice per day.
			 */
			$cache_md5_pre  = $this->p->lca . '_';
			$cache_exp_secs = DAY_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(user_id:' . $user_id . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$notice_key = 'timed-notice-' . $ext . '-plugin-review';

				$showing_ext = get_transient( $cache_id );	// Returns an empty string or the $notice_key value.

				/**
				 * Make sure the plugin is installed (ie. it has a version number).
				 */
				if ( empty( $info[ 'version' ] ) ) {

					continue;	// Get the next plugin.

				/**
				 * Make sure we have wordpress.org review URL.
				 */
				} elseif ( empty( $info[ 'url' ][ 'review' ] ) ) {

					continue;	// Get the next plugin.

				/**
				 * The user has already dismissed this notice.
				 */
				} elseif ( $this->p->notice->is_dismissed( $notice_key, $user_id ) ) {

					/**
					 * The single notice per day period has not expired yet.
					 */
					if ( $showing_ext === $notice_key ) {

						return 0;	// Stop here.
					}

					continue;	// Get the next plugin.

				/**
				 * Make sure we have an activation time.
				 */
				} elseif ( empty( $ext_reg[ $ext . '_activate_time' ] ) ) {

					continue;	// Get the next plugin.

				} elseif ( $ext_reg[ $ext . '_activate_time' ] > $week_ago_secs ) {	// Activated less than a week ago.

					continue;	// Get the next plugin.

				/**
				 * Make sure we only show this single notice for the next day.
				 */
				} elseif ( empty( $showing_ext ) || $showing_ext === '1' ) {

					set_transient( $cache_id, $notice_key, $cache_exp_secs );

				/**
				 * We're not showing this plugin right now.
				 */
				} elseif ( $showing_ext !== $notice_key ) {

					continue;	// Get the next plugin.
				}

				$wp_plugin_link = '<a href="' . $info[ 'url' ][ 'home' ] . '">' . $info[ 'name' ] . '</a>';

				$wp_plugin_link_desc = $wp_plugin_link . ' (' . trim( $info[ 'desc' ], '.' ) . ')';

				/**
				 * The action buttons.
				 */
				$rate_plugin_label = sprintf( __( 'Yes! Rate %s 5 stars!', 'wpsso' ), $info[ 'short' ] );

				$already_rated_label = sprintf( __( 'I\'ve already rated %s.', 'wpsso' ), $info[ 'short' ] );

				$rate_plugin_clicked = '<p><b>' . __( 'Awesome!', 'wpsso' ) . '</b> ' .
					sprintf( __( 'Thank you for rating the %s plugin!', 'wpsso' ), $info[ 'name' ] ) . '</p>';

				$already_rated_clicked = '<p><b>' . __( 'Awesome!', 'wpsso' ) . '</b> ' .
					sprintf( __( 'Thank you for supporting and encouraging your developers!', 'wpsso' ), $info[ 'name' ] ) . '</p>';

				$rate_plugin_button = '<div class="notice-single-button">' .
					$form->get_button( $rate_plugin_label, 'button-primary dismiss-on-click', '', $info[ 'url' ][ 'review' ],
						true, false, array( 'dismiss-msg' => $rate_plugin_clicked ) ) . '</div>';

				$already_rated_button = '<div class="notice-single-button">' .
					$form->get_button( $already_rated_label, 'button-secondary dismiss-on-click', '', '',
						false, false, array( 'dismiss-msg' => $already_rated_clicked ) ) . '</div>';

				/**
				 * The notice message.
				 */
				$notice_msg = '<div style="display:table-cell;">';

				$notice_msg .= '<p style="margin-right:25px;">' . $this->p->admin->get_ext_img_icon( $ext ) . '</p>';

				$notice_msg .= '</div>';

				$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

				$notice_msg .= '<p>';

				$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';

				$notice_msg .= sprintf( __( 'You\'ve been using %s for a while now, which is awesome!', 'wpsso' ), $wp_plugin_link_desc );

				$notice_msg .= '</p><p>';

				$notice_msg .= sprintf( __( 'We\'ve put many years of time and effort into making %s and its add-ons the best possible.', 'wpsso' ), $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'name' ] ) . ' ';

				$notice_msg .= sprintf( __( 'It\'s great that %s is a valued addition to your site.', 'wpsso' ), $wp_plugin_link ) . ' ';

				$notice_msg .= '</p><p>';

				$notice_msg .= sprintf( __( 'Now that you\'ve been using %s for a while, could you quickly rate it on WordPress.org?', 'wpsso' ), $wp_plugin_link ) . ' ';

				$notice_msg .= '<b>' . __( 'Great ratings are an excellent way to encourage your plugin developers and support the continued development of your favorite plugins!', 'wpsso' ) . '</b>';

				$notice_msg .= '</p>';

				$notice_msg .= '<div class="notice-actions">';

				$notice_msg .= $rate_plugin_button . $already_rated_button;

				$notice_msg .= '</div>';

				$notice_msg .= '</div>';

				$this->p->notice->nag( $notice_msg, $user_id, $notice_key, $dismiss_time );

				return 1;	// Show only one notice at a time.
			}

			return 0;
		}

		/**
		 * This method is called by $this->timed_notices() only if WordPress can dismiss notices and the user can manage
		 * options.
		 *
		 * Returns 0 by default and 1 if a notice has been created.
		 */
		private function single_notice_upsell() {

			$ext = $this->p->lca;
			$pkg = $this->p->admin->plugin_pkg_info();

			if ( $pkg[ $ext ][ 'pdir' ] ) {

				return 0;
			}

			$ext_reg         = $this->p->util->reg->get_ext_reg();
			$months_ago_secs = time() - ( 2 * MONTH_IN_SECONDS );
			$months_transl   = __( 'two months', 'wpsso' );

			if ( empty( $ext_reg[ $ext . '_install_time' ] ) || $ext_reg[ $ext . '_install_time' ] > $months_ago_secs ) {

				return 0;
			}

			$form         = $this->p->admin->get_form_object( $this->p->lca );
			$user_id      = get_current_user_id();
			$info         = $this->p->cf[ 'plugin' ][ $ext ];
			$notice_key   = 'timed-notice-' . $ext . '-pro-purchase-notice';
			$dismiss_time = true;	// Allow the notice to be dismissed forever.

			$wp_plugin_link = '<a href="' . $info[ 'url' ][ 'home' ] . '">' . $info[ 'name' ] . '</a>';

			$purchase_url = add_query_arg( array(
				'utm_source'  => $ext,
				'utm_medium'  => 'plugin',
				'utm_content' => 'pro-purchase-notice',
			), $info[ 'url' ][ 'purchase' ] );

			/**
			 * The action buttons.
			 */
			$purchase_label = sprintf( __( 'Yes! I\'d like to get the %s version!', 'wpsso' ),
				_x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' ) );

			$no_thanks_label = sprintf( __( 'No thanks. I\'ll stay with the %s version.', 'wpsso' ),
				_x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' ) );

			$purchase_clicked = '<p><b>' . __( 'Awesome!', 'wpsso' ) . '</b> ' .
				sprintf( __( 'Thank you for encouraging and supporting the continued development of %s.',
					'wpsso' ), $info[ 'name' ] ) . '</p>';

			$no_thanks_clicked = '<p>' . __( 'Thank you.', 'wpsso' ) . ' ' . 
				sprintf( __( 'Hopefully you\'ll change your mind in the future and help support the continued development of %s.',
					'wpsso' ), $info[ 'name' ] ) . '</p>';

			$purchase_button  = '<div class="notice-single-button">' .
				$form->get_button( $purchase_label, 'button-primary dismiss-on-click', '', $purchase_url,
					true, false, array( 'dismiss-msg' => $purchase_clicked ) ) . '</div>';

			$no_thanks_button  = '<div class="notice-single-button">' .
				$form->get_button( $no_thanks_label, 'button-secondary dismiss-on-click', '', '',
					false, false, array( 'dismiss-msg' => $no_thanks_clicked ) ) . '</div>';

			/**
			 * The notice message.
			 */
			$notice_msg = '<div style="display:table-cell;">';

			$notice_msg .= '<p style="margin-right:25px;">' . $this->p->admin->get_ext_img_icon( $ext ) . '</p>';

			$notice_msg .= '</div>';

			$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

			$notice_msg .= '<p>';

			$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';

			$notice_msg .= sprintf( __( 'You\'ve been using %1$s for more than %2$s now, which is awesome!', 'wpsso' ), $wp_plugin_link, $months_transl );

			$notice_msg .= '</p><p>';

			$notice_msg .= sprintf( __( 'We\'ve put many years of time and effort into making %s and its add-ons the best possible.', 'wpsso' ), $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'name' ] ) . ' ';

			$notice_msg .= sprintf( __( 'I hope you\'ve enjoyed all the new features, improvements and updates over the past %s.', 'wpsso' ), $months_transl );

			$notice_msg .= '</p><p>';

			$notice_msg .= sprintf( __( 'Have you thought about purchasing the %s version? It comes with a lot of great extra features!', 'wpsso' ), _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' ) );

			$notice_msg .= '</p>';

			$notice_msg .= '<div class="notice-actions">';

			$notice_msg .= $purchase_button . $no_thanks_button;

			$notice_msg .= '</div>';

			$notice_msg .= '</div>';

			$this->p->notice->nag( $notice_msg, $user_id, $notice_key, $dismiss_time );

			return 1;
		}
	}
}

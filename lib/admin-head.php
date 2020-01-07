<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
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

				$update_core_url = self_admin_url( 'update-core.php' );

				$notice_key = 'have-updates-for-' . $this->p->lca;

				$pending_transl = _n( 'There is <a href="%1$s">%2$d pending update for the %3$s plugin and/or its add-on(s)</a>.',
					'There are <a href="%1$s">%2$d pending updates for the %3$s plugin and/or its add-on(s)</a>.', $update_count, 'wpsso' );

				$install_transl = _n( 'Please install this update at your earliest convenience.',
					'Please install these updates at your earliest convenience.', $update_count, 'wpsso' );

				$this->p->notice->inf( sprintf( $pending_transl, $update_core_url, $update_count, $info[ 'short' ] ) . ' ' .
					$install_transl, null, $notice_key );
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
					 * If the update manager is active, the version should be available. Skip individual
					 * warnings and show nag to install the update manager.
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

				if ( ! empty( $um_info[ 'version' ] ) ) {	// If update manager is active, its version should be available.

					$rec_version = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];

					if ( version_compare( $um_info[ 'version' ], $rec_version, '<' ) ) {

						$this->p->notice->warn( $this->p->msgs->get( 'notice-um-version-recommended' ) );
					}

				} elseif ( SucomPlugin::is_plugin_installed( $um_info[ 'base' ], $use_cache = true ) ) {	// Check if update manager is installed.

					$this->p->notice->nag( $this->p->msgs->get( 'notice-um-activate-add-on' ) );

				} else {	// The update manager is not active or installed.

					$this->p->notice->nag( $this->p->msgs->get( 'notice-um-add-on-required' ) );
				}
			}

			if ( current_user_can( 'manage_options' ) ) {

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

		private function suggest_addons_woocommerce() {

			if ( empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {
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
			$dismiss_time = true;	// Can be dismissed permanently.

			if ( ! $this->p->notice->is_admin_pre_notices( $notice_key ) ) { // Skip if already dismissed.
				return;
			}

			if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {
			
				if ( SucomPlugin::is_plugin_installed( $info[ 'base' ], $use_cache = true ) ) {

					$url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( null, 'plugins.php' );

					$url = add_query_arg( array( 's' => $info[ 'base' ] ), $url );

					$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Activate the %s add-on.', 'wpsso' ),
						$pkg[ $ext ][ 'short' ] ) . '</a>';

				} else {

					$url = $this->p->util->get_admin_url( 'addons#' . $ext );

					$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Install and activate the %s add-on.', 'wpsso' ),
						$pkg[ $ext ][ 'short' ] ) . '</a>';
				}
			}

			if ( empty( $pkg[ $this->p->lca ][ 'pp' ] ) ) {

				$url = add_query_arg( array( 
					'utm_source'  => $this->p->lca,
					'utm_medium'  => 'plugin',
					'utm_content' => $notice_key,
				), $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'url' ][ 'purchase' ] );

				$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Purchase the %s plugin.', 'wpsso' ),
					$pkg[ $this->p->lca ][ 'short_pro' ] ) . '</a>';
			}

			if ( empty( $pkg[ $ext ][ 'pp' ] ) ) {

				$url = add_query_arg( array( 
					'utm_source'  => $this->p->lca,
					'utm_medium'  => 'plugin',
					'utm_content' => $notice_key,
				), $info[ 'url' ][ 'purchase' ] );

				$action_links[] = '<a href="' . $url . '">' . sprintf( __( 'Purchase the %s add-on.', 'wpsso' ),
					$pkg[ $ext ][ 'short_pro' ] ) . '</a>';
			}

			if ( ! empty( $action_links ) ) {

				$settings_page_link = $this->p->util->get_admin_url( 'setup', _x( 'Setup Guide', 'lib file description', 'wpsso' ) );

				$google_tool_link = '<a href="' . __( 'https://search.google.com/structured-data/testing-tool/u/0/', 'wpsso' ) . '">' .
					__( 'Google Structured Data Testing Tool', 'wpsso' ) . '</a>';

				$notice_msg = sprintf( __( 'The WooCommerce v%s plugin is known to provide incomplete Schema markup for Google.', 'wpsso' ), $wc_version ) . ' ';
				
				$notice_msg .= __( 'The WPSSO Core Premium plugin and its WPSSO JSON Premium add-on provide a far better solution by offering complete Facebook / Pinterest Product meta tags and Schema Product markup for Google Rich Results / Rich Snippets &mdash; including additional product images, product variations, product attributes (brand, color, condition, EAN, dimensions, GTIN-8/12/13/14, ISBN, material, MPN, size, SKU, weight, etc), product reviews, product ratings, sale start / end dates, sale prices, pre-tax prices, VAT prices, and much, much more.', 'wpsso' );

				$notice_msg .= '<ul><li>' . implode( '</li><li>', $action_links ) . '</li></ul>' . ' ';

				$notice_msg .= sprintf( __( 'As suggested in the %1$s, you can (and should) submit a few product URLs to the %2$s and make sure your Schema Product markup is complete.', 'wpsso' ), $settings_page_link, $google_tool_link ) . ' ';

				$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time );
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
					 * Add more timed notices here.
					 */
				}
			}
		}

		/**
		 * This method should return 0 by default, and 1 if a notice has been added.
		 */
		private function single_notice_review() {

			$form          = $this->p->admin->get_form_object( $this->p->lca );
			$user_id       = get_current_user_id();
			$ext_reg       = $this->p->util->reg->get_ext_reg();
			$week_ago_secs = time() - ( 1 * WEEK_IN_SECONDS );

			/**
			 * Use the transient cache to show only one notice per day.
			 */
			$cache_md5_pre  = $this->p->lca . '_';
			$cache_exp_secs = DAY_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(user_id:' . $user_id . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$notice_key   = 'timed-notice-' . $ext . '-plugin-review';
				$dismiss_time = true;				// Can be dismissed permanently.
				$showing_ext  = get_transient( $cache_id );	// Returns an empty string or the $notice_key value.

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

				$wp_plugin_link = '<a href="' . $info[ 'url' ][ 'home' ] . '" title="' .
					sprintf( __( 'The %s plugin description page on WordPress.org.',
						'wpsso' ), $info[ 'short' ] ) . '">' . $info[ 'name' ] . '</a>';

				/**
				 * The action buttons.
				 */
				$rate_plugin_label   = sprintf( __( 'Yes! Rate %s 5 stars!', 'wpsso' ), $info[ 'short' ] );
				$rate_plugin_clicked = sprintf( __( 'Thank you for rating the %s plugin!', 'wpsso' ), $info[ 'short' ] );
				$rate_plugin_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0.8em 0.8em 0;">' .
					$form->get_button( $rate_plugin_label, 'button-primary dismiss-on-click', '', $info[ 'url' ][ 'review' ],
						true, false, array( 'dismiss-msg' => $rate_plugin_clicked ) ) . '</div>';

				$already_rated_label   = sprintf( __( 'I\'ve already rated %s.', 'wpsso' ), $info[ 'short' ] );
				$already_rated_clicked = sprintf( __( 'Thank you for your earlier rating of %s!', 'wpsso' ), $info[ 'short' ] );
				$already_rated_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0 0.8em 0;">' .
					$form->get_button( $already_rated_label, 'button-secondary dismiss-on-click', '', '',
						false, false, array( 'dismiss-msg' => $already_rated_clicked ) ) . '</div>';

				/**
				 * The notice message.
				 */
				$notice_msg = '<div style="display:table-cell;">';

				$notice_msg .= '<p style="margin-right:20px;">' . $this->p->admin->get_ext_img_icon( $ext ) . '</p>';

				$notice_msg .= '</div>';

				$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

				$notice_msg .= '<p class="top">';
				
				$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';
				
				$notice_msg .= sprintf( __( 'You\'ve been using <b>%s</b> for a while now, which is awesome!', 'wpsso' ), $wp_plugin_link ) . ' ';

				$notice_msg .= '</p><p>';

				$notice_msg .= sprintf( __( 'We\'ve put a lot of effort into making %s and its add-ons the best possible, so it\'s great to know that you\'re finding this plugin useful.', 'wpsso' ), $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'short' ] ) . ' :-) ';

				$notice_msg .= '</p><p>';

				$notice_msg .= sprintf( __( 'Now that you\'re familiar with %s, would you rate this plugin 5 stars on WordPress.org?', 'wpsso' ), $info[ 'short' ] ) . ' ';

				$notice_msg .= '</p><p>';

				$notice_msg .= __( 'Your rating is a great way to encourage us and it helps other WordPress users find great plugins!', 'wpsso' ) . ' ';

				$notice_msg .= '</p>';
				
				$notice_msg .= $rate_plugin_button . $already_rated_button;
					
				$notice_msg .= '</div>';

				/**
				 * The notice provides it's own dismiss button, so do not show the dismiss 'Forever' link.
				 */
				$this->p->notice->log( 'inf', $notice_msg, $user_id, $notice_key, $dismiss_time, array( 'dismiss_diff' => false ) );

				return 1;	// Show only one notice at a time.
			}

			return 0;
		}

		/**
		 * This method should return 0 by default, and 1 if a notice has been added.
		 */
		private function single_notice_upsell() {

			$ext = $this->p->lca;
			$pkg = $this->p->admin->plugin_pkg_info();

			if ( $pkg[ $ext ][ 'pdir' ] ) {
				return 0;
			}

			$ext_reg         = $this->p->util->reg->get_ext_reg();
			$months_ago_secs = time() - ( 3 * MONTH_IN_SECONDS );
			$months_transl   = __( 'three months', 'wpsso' );

			if ( empty( $ext_reg[ $ext . '_install_time' ] ) ||
				$ext_reg[ $ext . '_install_time' ] > $months_ago_secs ) {

				return 0;
			}

			$form         = $this->p->admin->get_form_object( $this->p->lca );
			$user_id      = get_current_user_id();
			$info         = $this->p->cf[ 'plugin' ][ $ext ];
			$notice_key   = 'timed-notice-' . $ext . '-pro-purchase-notice';
			$dismiss_time = 3 * MONTH_IN_SECONDS;

			/**
			 * Purchase action button and dismiss message.
			 */
			$purchase_url = add_query_arg( array(
				'utm_source'  => $ext,
				'utm_medium'  => 'plugin',
				'utm_content' => 'pro-purchase-notice',
			), $info[ 'url' ][ 'purchase' ] );

			$purchase_label   = sprintf( __( 'Yes! I\'d like to get the %s update!', 'wpsso' ),
				_x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' ) );

			$purchase_clicked = __( 'Thank you for your support!', 'wpsso' );

			$purchase_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0.8em 0.8em 0;">' .
				$form->get_button( $purchase_label, 'button-primary dismiss-on-click', '', $purchase_url,
					true, false, array( 'dismiss-msg' => $purchase_clicked ) ) . '</div>';

			/**
			 * No thanks action button and dismiss message.
			 */
			$no_thanks_label   = sprintf( __( 'No thanks, I\'ll stay with the %s version.', 'wpsso' ),
				_x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' ) );

			$no_thanks_clicked = __( 'Sorry to hear that - hopefully you\'ll change your mind later.', 'wpsso' ) . ' ;-)';

			$no_thanks_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0 0.8em 0;">' .
				$form->get_button( $no_thanks_label, 'button-secondary dismiss-on-click', '', '',
					false, false, array( 'dismiss-msg' => $no_thanks_clicked ) ) . '</div>';

			/**
			 * Upsell notice.
			 */
			$notice_msg = '<div style="display:table-cell;">';

			$notice_msg .= '<p style="margin-right:20px;">' . $this->p->admin->get_ext_img_icon( $ext ) . '</p>';

			$notice_msg .= '</div>';

			$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

			$notice_msg .= '<p class="top">';

			$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';

			$notice_msg .= sprintf( __( 'You\'ve been using the %1$s plugin for more than %2$s now, which is awesome!', 'wpsso' ),
				$info[ 'short' ], $months_transl ) . ' ';

			$notice_msg .= '</p><p>';

			$notice_msg .= sprintf( __( 'We\'ve put a lot of effort into making %1$s and its add-ons the best possible &mdash; I hope you\'ve enjoyed all the new features, improvements and updates over the past %2$s.', 'wpsso' ), $info[ 'short' ], $months_transl ) . ' :-)';

			$notice_msg .= '</p><p>';

			$notice_msg .= '<b>' . sprintf( __( 'Have you considered purchasing the %s version? It comes with a lot of extra features!', 'wpsso' ), _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' ) ) . '</b> ';

			$notice_msg .= '</p>';
			
			$notice_msg .= $purchase_button . $no_thanks_button;

			$notice_msg .= '</div>';

			/**
			 * The notice provides it's own dismiss button, so do not show the dismiss 'Forever' link.
			 */
			$this->p->notice->log( 'inf', $notice_msg, $user_id, $notice_key, $dismiss_time, array( 'dismiss_diff' => false ) );

			return 1;
		}
	}
}

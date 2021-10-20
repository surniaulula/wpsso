<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoAdminHead' ) ) {

	class WpssoAdminHead {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoAdmin->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {

				add_action( 'admin_head', array( $this, 'update_count_notice' ), -200 );
				add_action( 'admin_head', array( $this, 'requires_notices' ), -100 );
				add_action( 'admin_head', array( $this->p->util, 'log_is_functions' ), 10 );
				add_action( 'admin_head', array( $this, 'suggest_addons' ), 100 );
				add_action( 'admin_head', array( $this, 'timed_notices' ), 200 );
				add_action( 'admin_head', array( $this, 'robots_notice' ), 300 );
			}
		}

		/**
		 * Show a notice if there are pending WPSSO plugin updates and the user can update plugins.
		 */
		public function update_count_notice() {

			if ( ! current_user_can( 'update_plugins' ) ) {

				return;
			}

			$update_count = SucomPlugin::get_updates_count( $plugin_prefix = 'wpsso' );

			if ( $update_count > 0 ) {

				$p_info = $this->p->cf[ 'plugin' ][ 'wpsso' ];

				$notice_key = 'have-updates-for-wpsso';

				$notice_msg = sprintf( _n( 'There is <a href="%1$s">%2$d pending update for the %3$s plugin and its add-on(s)</a>.',
					'There are <a href="%1$s">%2$d pending updates for the %3$s plugin and its add-on(s)</a>.', $update_count, 'wpsso' ),
						self_admin_url( 'update-core.php' ), $update_count, $p_info[ 'short' ] ) . ' ';

				$notice_msg .= _n( 'Please install this update at your earliest convenience.',
					'Please install these updates at your earliest convenience.', $update_count, 'wpsso' );

				$this->p->notice->inf( $notice_msg, null, $notice_key );
			}
		}

		public function requires_notices() {

			$pkg_info = $this->p->admin->get_pkg_info();	// Returns an array from cache.
			$um_info  = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
			$have_tid = false;

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $ext_info ) {

				if ( ! empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) ) {

					$have_tid = true;	// Found at least one plugin with an auth id.

					/**
					 * If the update manager is active, its version should be available.
					 *
					 * If the update manager version is not available, skip the warning notices and show a nag
					 * notice to install the update manager.
					 */
					if ( ! empty( $um_info[ 'version' ] ) ) {

						if ( empty( $pkg_info[ $ext ][ 'pdir' ] ) ) {

							if ( ! empty( $ext_info[ 'base' ] ) && ! SucomPlugin::is_plugin_installed( $ext_info[ 'base' ] ) ) {

								$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-installed', array( 'plugin_id' => $ext ) ) );

							} else {

								$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-updated', array( 'plugin_id' => $ext ) ) );
							}
						}

					} else {

						break;
					}
				}
			}

			if ( $have_tid ) {

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
				} elseif ( SucomPlugin::is_plugin_installed( $um_info[ 'base' ] ) ) {

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

								$app_version = $wp_version;

								$dismiss_time = MONTH_IN_SECONDS;

								break;

							case 'php':

								$app_version = phpversion();

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

			if ( ! $this->p->notice->can_dismiss() || ! current_user_can( 'manage_options' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot dismiss or cannot manage options' );
				}

				return;	// Stop here.
			}

			$this->suggest_addons_woocommerce();
		}

		public function timed_notices() {

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

					 // Add more timed notices here as needed.
				}
			}
		}

		public function robots_notice() {

			if ( ! $this->p->notice->can_dismiss() || ! current_user_can( 'manage_options' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot dismiss or cannot manage options' );
				}

				return;	// Stop here.
			}

			if ( empty( $this->p->options[ 'add_meta_name_robots' ] ) ) {

				if ( empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

					$seo_other_tab_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_head_tags-tab_seo_other',
						_x( 'SSO', 'menu title', 'wpsso' ) . ' &gt; ' .
						_x( 'Advanced Settings', 'lib file description', 'wpsso' ) . ' &gt; ' .
						_x( 'HTML Tags', 'metabox title', 'wpsso' ) . ' &gt; ' .
						_x( 'SEO / Other', 'metabox tab', 'wpsso' ) );

					$notice_msg = sprintf( __( 'Please note that the <code>%s</code> HTML tag is currently disabled and a known SEO plugin has not been detected.', 'wpsso' ), 'meta name robots' ) . ' ';

					$notice_msg .= sprintf( __( 'If another SEO plugin or your theme templates are not adding the <code>%1$s</code> HTML tag to your webpages, you can re-enable this option under the %2$s tab.', 'wpsso' ), 'meta name robots', $seo_other_tab_link ) . ' ';

					$notice_key = 'advanced-robots-notice-unchecked-without-seo-plugin';

					$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );
				}
			}
		}

		/**
		 * Suggest purchasing the WPSSO Core Premium plugin and activating WooCommerce related add-ons.
		 *
		 * These private notice functions should return the number of notices shown.
		 */
		private function suggest_addons_woocommerce() {

			$notice_shown = 0;

			if ( empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'woocommerce is not active' );
				}

				return $notice_shown;
			}

			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema markup is disabled' );
				}

				return $notice_shown;
			}

			$pkg_info = $this->p->admin->get_pkg_info();	// Returns an array from cache.

			if ( empty( $pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

				$notice_key = 'suggest-premium-for-woocommerce';

				if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

					$action_links = array();	// Init a new action array for the notice message.

					$action_links[] = $this->get_purchase_plugin_link( 'wpsso' );

					$notice_msg = __( 'The WooCommerce plugin does not provide sufficient Schema JSON-LD markup for Google Rich Results.', 'wpsso' ) . ' ';

					$notice_msg .= sprintf( __( 'The %1$s plugin reads WooCommerce product data and provides complete Schema Product JSON-LD markup for Google Rich Results, including additional product images, product variations, product information (brand, color, condition, EAN, dimensions, GTIN-8/12/13/14, ISBN, material, MPN, size, SKU, volume, weight, etc), product reviews, product ratings, sale start / end dates, sale prices, pre-tax prices, VAT prices, shipping rates, shipping times, and much, much more.', 'wpsso' ), $pkg_info[ 'wpsso' ][ 'name_pro' ] ) . ' ';

					$notice_msg .= '<ul><li>' . implode( $glue = '</li> <li>', $action_links ) . '</li></ul>' . ' ';

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );

					$notice_shown++;
				}
			}

			if ( empty( $this->p->avail[ 'p_ext' ][ 'wcmd' ] ) &&
				empty( $this->p->avail[ 'ecom' ][ 'woo-add-gtin' ] ) &&
				empty( $this->p->avail[ 'ecom' ][ 'wpm-product-gtin-wc' ] ) ) {

				$notice_key = 'suggest-wpssowcmd-for-woocommerce';

				if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

					$action_links = array();	// Init a new action array for the notice message.

					if ( empty( $pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

						$action_links[] = $this->get_purchase_plugin_link( 'wpsso', __( '(required for WooCommerce integration)', 'wpsso' ) );
					}

					$action_links[] = $this->get_install_activate_addon_link( 'wpssowcmd' );

					$wcmd_info        = $this->p->cf[ 'plugin' ][ 'wpssowcmd' ];
					$wcmd_name_transl = _x( $wcmd_info[ 'name' ], 'plugin name', 'wpsso' );

					$notice_msg = __( 'Schema Product markup for Google Rich Results requires at least one unique product ID, like the product MPN (Manufacturer Part Number), UPC, EAN, GTIN, or ISBN.', 'wpsso' ) . ' ';

					$notice_msg .= __( 'The product SKU (Stock Keeping Unit) from WooCommerce is not a valid unique product ID.', 'wpsso' ) . ' ';

					$notice_msg .= sprintf( __( 'If you\'re not already using a plugin to manage unique product IDs for WooCommerce, you should activate the %s add-on.', 'wpsso' ), $wcmd_name_transl ) . ' ';

					$notice_msg .= '<ul><li>' . implode( $glue = '</li> <li>', $action_links ) . '</li></ul>' . ' ';

					$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );

					$notice_shown++;
				}
			}

			if ( empty( $this->p->avail[ 'p_ext' ][ 'wcsdt' ] ) ) {

				$notice_key = 'suggest-wpssowcsdt-for-woocommerce';

				if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) {

					$action_links = array();	// Init a new action array for the notice message.

					$shipping_continents = WC()->countries->get_shipping_continents();	// Since WC v3.6.0.
					$shipping_countries  = WC()->countries->get_shipping_countries();
					$shipping_enabled    = $shipping_continents || $shipping_countries ? true : false;

					if ( $shipping_enabled ) {

						if ( empty( $pkg_info[ 'wpsso' ][ 'pp' ] ) ) {

							$action_links[] = $this->get_purchase_plugin_link( 'wpsso', __( '(required for WooCommerce integration)', 'wpsso' ) );
						}

						$action_links[] = $this->get_install_activate_addon_link( 'wpssowcsdt' );

						$wcsdt_info        = $this->p->cf[ 'plugin' ][ 'wpssowcsdt' ];
						$wcsdt_name_transl = _x( $wcsdt_info[ 'name' ], 'plugin name', 'wpsso' );

						$notice_msg = sprintf( __( 'Product shipping features are enabled in WooCommerce, but the %s add-on is not active.', 'wpsso' ), $wcsdt_name_transl ) . ' ';

						$notice_msg .= __( 'Adding shipping details to your Schema Product markup is important if you offer free or low-cost shipping options, as this will make your products more appealing in Google search results.', 'wpsso' ) . ' ';

						$notice_msg .= '<ul><li>' . implode( $glue = '</li> <li>', $action_links ) . '</li></ul>' . ' ';

						$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );

						$notice_shown++;
					}
				}
			}

			return $notice_shown;
		}


		private function get_purchase_plugin_link( $ext, $cmt = '' ) {

			$pkg_info         = $this->p->admin->get_pkg_info();	// Returns an array from cache.
			$ext_info         = $this->p->cf[ 'plugin' ][ $ext ];
			$ext_purchase_url = $ext_info[ 'url' ][ 'purchase' ];

			if ( $cmt ) {

				// translators: %1$s is a URL, %2$s is the plugin name, and %3$s is a pre-translated comment.
				return sprintf( __( '<a href="%1$s">Purchase the %2$s plugin</a> %3$s.', 'wpsso' ), $ext_purchase_url, $pkg_info[ $ext ][ 'name_pro' ], $cmt );
			}

			return '<a href="' . $ext_purchase_url . '">' . sprintf( __( 'Purchase the %s plugin.', 'wpsso' ), $pkg_info[ $ext ][ 'name_pro' ] ) . '</a>';
		}

		private function get_install_activate_addon_link( $ext ) {

			$ext_info = $this->p->cf[ 'plugin' ][ $ext ];

			$ext_name_transl = _x( $ext_info[ 'name' ], 'plugin name', 'wpsso' );

			if ( SucomPlugin::is_plugin_installed( $ext_info[ 'base' ] ) ) {

				$search_url = is_multisite() ? network_admin_url( 'plugins.php', null ) : get_admin_url( $blog_id = null, 'plugins.php' );
				$search_url = add_query_arg( array( 's' => $ext_info[ 'slug' ] ), $search_url );

				return '<a href="' . $search_url . '">' . sprintf( __( 'Activate the %s add-on.', 'wpsso' ), $ext_name_transl ) . '</a>';

			}

			$addons_url = $this->p->util->get_admin_url( 'addons#' . $ext );

			return '<a href="' . $addons_url . '">' . sprintf( __( 'Install and activate the %s add-on.', 'wpsso' ), $ext_name_transl ) . '</a>';
		}

		/**
		 * This method is called by timed_notices() if WordPress can dismiss notices and the user can manage options.
		 *
		 * These private notice functions should return the number of notices shown.
		 */
		private function single_notice_review() {

			$form          = $this->p->admin->get_form_object( 'wpsso' );
			$user_id       = get_current_user_id();
			$ext_reg       = $this->p->util->reg->get_ext_reg();
			$week_ago_secs = time() - ( 1 * WEEK_IN_SECONDS );
			$dismiss_time  = true;	// Allow the notice to be dismissed forever.

			/**
			 * Use the transient cache to show only one notice per day.
			 */
			$cache_md5_pre  = 'wpsso_';
			$cache_exp_secs = DAY_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(user_id:' . $user_id . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			$showing_ext = get_transient( $cache_id );	// Returns an empty string or the $notice_key value.

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $ext_info ) {

				$p_info          = $this->p->cf[ 'plugin' ][ 'wpsso' ];
				$p_name_transl   = _x( $p_info[ 'name' ], 'plugin name', 'wpsso' );
				$ext_name_transl = _x( $ext_info[ 'name' ], 'plugin name', 'wpsso' );
				$ext_desc_transl = _x( $ext_info[ 'desc' ], 'plugin description', 'wpsso' );

				/**
				 * Make sure the plugin is installed (ie. it has a version number).
				 */
				if ( empty( $ext_info[ 'version' ] ) ) {

					continue;	// Get the next plugin.
				}

				/**
				 * Make sure we have wordpress.org review URL.
				 */
				if ( empty( $ext_info[ 'url' ][ 'review' ] ) ) {

					continue;	// Get the next plugin.
				}

				/**
				 * The user has already dismissed this notice.
				 */
				$notice_key  = 'timed-notice-' . $ext . '-plugin-review';

				if ( $this->p->notice->is_dismissed( $notice_key, $user_id ) ) {

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

				/**
				 * Activated less than a week ago.
				 */
				} elseif ( $ext_reg[ $ext . '_activate_time' ] > $week_ago_secs ) {

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

				$wp_plugin_link = '<a href="' . $ext_info[ 'url' ][ 'home' ] . '">' . $ext_name_transl . '</a>';

				$wp_plugin_link_desc = $wp_plugin_link . ' (' . trim( $ext_desc_transl, '.' ) . ')';

				/**
				 * Rate plugin action button.
				 */
				$rate_plugin_label = sprintf( __( 'Yes! Rate %s 5 stars!', 'wpsso' ), $ext_info[ 'short' ] );

				$rate_plugin_clicked = '<p><b>' . __( 'Awesome!', 'wpsso' ) . '</b> ' .
					sprintf( __( 'Thank you for rating the %s plugin!', 'wpsso' ), $ext_name_transl ) . '</p>';

				$rate_plugin_button = '<div class="notice-single-button">' .
					$form->get_button( $rate_plugin_label, 'button-primary dismiss-on-click', '', $ext_info[ 'url' ][ 'review' ],
						true, false, array( 'dismiss-msg' => $rate_plugin_clicked ) ) . '</div>';

				/**
				 * Already rated action button.
				 */
				$already_rated_label = sprintf( __( 'I\'ve already rated %s.', 'wpsso' ), $ext_info[ 'short' ] );

				$already_rated_clicked = '<p><b>' . __( 'Awesome!', 'wpsso' ) . '</b> ' .
					sprintf( __( 'Thank you for supporting and encouraging your developers!', 'wpsso' ), $ext_name_transl ) . '</p>';

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

				$notice_msg .= '<p><strong>';

				$notice_msg .= sprintf( __( 'Now that you\'ve been using %s for a while, you can rate the plugin on WordPress.org!', 'wpsso' ), $wp_plugin_link ) . ' ';

				$notice_msg .= '</strong></p><p>';

				$notice_msg .= __( 'Great ratings are an excellent way to ensure the continued development of your favorite plugins.', 'wpsso' ) . ' ';

				$notice_msg .= '</p><p>';

				$notice_msg .= __( 'Without new ratings, plugins and add-ons that you and your site depend on could be discontinued prematurely.', 'wpsso' ) . ' ';

				$notice_msg .= '</p><p>';

				$notice_msg .= __( 'Don\'t let that happen!', 'wpsso' ) . ' ';

				$notice_msg .= __( 'Rate your active plugins today - it only takes a few seconds to rate a plugin!', 'wpsso' ) . ' ';

				$notice_msg .= convert_smilies( ';-)' );

				$notice_msg .= '</p>' . "\n";

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
		 * This method is called by timed_notices() if WordPress can dismiss notices and the user can manage options.
		 *
		 * These private notice functions should return the number of notices shown.
		 */
		private function single_notice_upsell() {

			$pkg_info = $this->p->admin->get_pkg_info();	// Returns an array from cache.

			if ( $pkg_info[ 'wpsso' ][ 'pdir' ] ) {

				return 0;
			}

			$ext_reg           = $this->p->util->reg->get_ext_reg();
			$months_ago_secs   = time() - ( 2 * MONTH_IN_SECONDS );
			$months_ago_transl = __( 'two months', 'wpsso' );

			if ( empty( $ext_reg[ 'wpsso_install_time' ] ) || $ext_reg[ 'wpsso_install_time' ] > $months_ago_secs ) {

				return 0;
			}

			$form            = $this->p->admin->get_form_object( 'wpsso' );
			$user_id         = get_current_user_id();
			$p_info          = $this->p->cf[ 'plugin' ][ 'wpsso' ];
			$p_name_transl   = _x( $p_info[ 'name' ], 'plugin name', 'wpsso' );
			$p_purchase_url  = $p_info[ 'url' ][ 'purchase' ];
			$wp_plugin_link  = '<a href="' . $p_info[ 'url' ][ 'home' ] . '">' . $p_name_transl . '</a>';
			$dist_pro_transl = _x( $this->p->cf[ 'dist' ][ 'pro' ], 'distribution name', 'wpsso' );
			$dist_std_transl = _x( $this->p->cf[ 'dist' ][ 'std' ], 'distribution name', 'wpsso' );
			$notice_key      = 'timed-notice-wpsso-pro-purchase-notice';
			$dismiss_time    = true;	// Allow the notice to be dismissed forever.

			/**
			 * The action buttons.
			 */
			$purchase_label = sprintf( __( 'Yes! I\'d like to get the %s version!', 'wpsso' ), $dist_pro_transl );

			$no_thanks_label = sprintf( __( 'No thanks. I\'ll stay with the %s version.', 'wpsso' ), $dist_std_transl );

			$purchase_clicked = '<p><b>' . __( 'Awesome!', 'wpsso' ) . '</b> ' .
				sprintf( __( 'Thank you for encouraging and supporting the continued development of %s.',
					'wpsso' ), $p_name_transl ) . '</p>';

			$no_thanks_clicked = '<p>' . __( 'Thank you.', 'wpsso' ) . ' ' . 
				sprintf( __( 'Hopefully you\'ll change your mind in the future and help support the continued development of %s.',
					'wpsso' ), $p_name_transl ) . '</p>';

			$purchase_button  = '<div class="notice-single-button">' .
				$form->get_button( $purchase_label, 'button-primary dismiss-on-click', '', $p_purchase_url,
					true, false, array( 'dismiss-msg' => $purchase_clicked ) ) . '</div>';

			$no_thanks_button  = '<div class="notice-single-button">' .
				$form->get_button( $no_thanks_label, 'button-secondary dismiss-on-click', '', '',
					false, false, array( 'dismiss-msg' => $no_thanks_clicked ) ) . '</div>';

			/**
			 * The notice message.
			 */
			$notice_msg = '<div style="display:table-cell;">';

			$notice_msg .= '<p style="margin-right:25px;">' . $this->p->admin->get_ext_img_icon( 'wpsso' ) . '</p>';

			$notice_msg .= '</div>';

			$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

			$notice_msg .= '<p>';

			$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';

			$notice_msg .= sprintf( __( 'You\'ve been using %1$s for more than %2$s now, which is awesome!', 'wpsso' ),
				$wp_plugin_link, $months_ago_transl );

			$notice_msg .= '</p><p>';

			$notice_msg .= sprintf( __( 'We\'ve put many years of time and effort into making %s and its add-ons the best possible.', 'wpsso' ),
				$p_name_transl ) . ' ';

			$notice_msg .= sprintf( __( 'I hope you\'ve enjoyed all the new features, improvements and updates over the past %s.', 'wpsso' ),
				$months_ago_transl );

			$notice_msg .= '</p><p>';

			$notice_msg .= sprintf( __( 'Have you thought about purchasing the %s version? It comes with a lot of great extra features!', 'wpsso' ),
				$dist_pro_transl );

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

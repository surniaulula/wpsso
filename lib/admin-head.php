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

if ( ! class_exists( 'WpssoAdminHead' ) ) {

	class WpssoAdminHead {

		private $p;	// Wpsso class object.

		protected $suggest;

		/*
		 * Instantiated by WpssoAdmin->__construct().
		 */
		public function __construct( &$plugin ) {

			static $do_once = null;

			if ( true === $do_once ) {

				return;	// Stop here.
			}

			$do_once = true;

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {

				require_once WPSSO_PLUGINDIR . 'lib/admin-head-suggest.php';

				$this->suggest = new WpssoAdminHeadSuggest( $plugin );

				add_action( 'admin_head', array( $this, 'wp_config_check' ), -300 );
				add_action( 'admin_head', array( $this, 'wp_php_versions' ), -200 );	// Requires 'manage_options' capability.
				add_action( 'admin_head', array( $this, 'pending_updates' ), -100 );	// Requires 'update_plugins' capability.
				add_action( 'admin_head', array( $this->p->util, 'log_is_functions' ), 10 );
				add_action( 'admin_head', array( $this, 'timed_notices' ), 200 );	// Requires 'manage_options' capability.
			}
		}

		public function wp_config_check() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$user_id = get_current_user_id();

			if ( ! $user_id ) {	// Nobody there.

				return;	// Stop here.
			}

			/*
			 * Skip if previous check is already successful.
			 */
			if ( $passed = get_option( WPSSO_WP_CONFIG_CHECK_NAME, $default = false ) ) {

				return;	// Stop here.
			}

			/*
			 * Check for a PHP variable in the WP_HOME constant value.
			 */
			if ( $file_path = SucomUtilWP::get_wp_config_file_path() ) {

				$stripped_php = SucomUtil::get_stripped_php( $file_path );

				if ( preg_match( '/define\( *[\'"]WP_HOME[\'"][^\)]*\$/', $stripped_php ) ) {

					$notice_key = 'notice-wp-config-php-variable-home';

					$notice_msg = $this->p->msgs->get( $notice_key );

					$this->p->notice->err( $notice_msg, $user_id, $notice_key );

					return;	// Stop here.
				}
			}

			/*
			 * Check for an IP address in the home URL value.
			 */
			$is_public = get_option( 'blog_public' );

			if ( $is_public ) {

				$home_url = SucomUtilWP::raw_get_home_url();

				if ( preg_match( '/^([a-z]+):\/\/([0-9\.]+)(:[0-9]+)?$/i', $home_url ) ) {

					$general_settings_url = get_admin_url( $blog_id = null, 'options-general.php' );
					$reading_settings_url = get_admin_url( $blog_id = null, 'options-reading.php' );

					$notice_msg = sprintf( __( 'The WordPress <a href="%1$s">Search Engine Visibility</a> option is set to allow search engines and social sites to access this site, but your <a href="%2$s">Site Address URL</a> value is an IP address (%3$s).', 'wpsso' ), $reading_settings_url, $general_settings_url, $home_url ) . ' ';

					$notice_msg .= __( 'Please update your Search Engine Visibility option to discourage search engines from indexing this site, or use a fully qualified domain name as your Site Address URL.', 'wpsso' );

					$notice_key = 'notice-wp-config-home-url-ip-address';

					$this->p->notice->warn( $notice_msg, $user_id, $notice_key );

					return;	// Stop here.
				}
			}

			/*
			 * Mark all config checks as complete.
			 */
			update_option( WPSSO_WP_CONFIG_CHECK_NAME, $passed = true, $autoload = false );
		}

		public function wp_php_versions() {

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

								continue 2;	// Get another $key.
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

		/*
		 * Show a notice if there are pending WPSSO plugin updates and the user can update plugins.
		 */
		public function pending_updates() {

			if ( current_user_can( 'update_plugins' ) ) {

				$update_count = SucomPlugin::get_updates_count( $plugin_prefix = 'wpsso' );

				if ( $update_count > 0 ) {

					$p_info        = $this->p->cf[ 'plugin' ][ 'wpsso' ];
					$p_name_transl = _x( $p_info[ 'name' ], 'plugin name', 'wpsso' );

					$notice_key = 'pending-updates-for-wpsso-and-addons';

					$notice_msg = sprintf( _n( 'There is <a href="%1$s">%2$d pending update for the %3$s plugin and its add-on(s)</a>.',
						'There are <a href="%1$s">%2$d pending updates for the %3$s plugin and its add-on(s)</a>.', $update_count, 'wpsso' ),
							self_admin_url( 'update-core.php' ), $update_count, $p_name_transl ) . ' ';

					$notice_msg .= _n( 'Please install this update at your earliest convenience.',
						'Please install these updates at your earliest convenience.', $update_count, 'wpsso' );

					$this->p->notice->inf( $notice_msg, null, $notice_key );
				}
			}
		}

		/*
		 * Show a single notice at a time.
		 */
		public function timed_notices() {

			if ( current_user_can( 'manage_options' ) ) {

				if ( ! $this->single_notice_review() ) {

					if ( ! $this->single_notice_upsell() ) {

						 // Add more timed notices here as needed.
					}
				}
			}
		}

		/*
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

			/*
			 * Use the transient cache to show only one notice per day.
			 */
			$cache_md5_pre  = 'wpsso_';
			$cache_exp_secs = DAY_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(user_id:' . $user_id . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			$showing_ext = get_transient( $cache_id );	// Returns an empty string or the $notice_key value.

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $ext_info ) {

				if ( empty( $ext_info[ 'name' ] ) ) {	// Just in case.

					continue;
				}

				$p_info          = $this->p->cf[ 'plugin' ][ 'wpsso' ];
				$p_name_transl   = _x( $p_info[ 'name' ], 'plugin name', 'wpsso' );
				$ext_name_transl = _x( $ext_info[ 'name' ], 'plugin name', 'wpsso' );
				$ext_desc_transl = _x( $ext_info[ 'desc' ], 'plugin description', 'wpsso' );

				/*
				 * Make sure the plugin is installed (ie. it has a version number).
				 */
				if ( empty( $ext_info[ 'version' ] ) ) {

					continue;	// Get the next plugin.
				}

				/*
				 * Make sure we have wordpress.org review URL.
				 */
				if ( empty( $ext_info[ 'url' ][ 'review' ] ) ) {

					continue;	// Get the next plugin.
				}

				/*
				 * The user has already dismissed this notice.
				 */
				$notice_key  = 'timed-notice-' . $ext . '-plugin-review';

				if ( $this->p->notice->is_dismissed( $notice_key, $user_id ) ) {

					/*
					 * The single notice per day period has not expired yet.
					 */
					if ( $showing_ext === $notice_key ) {

						return 0;	// Stop here.
					}

					continue;	// Get the next plugin.

				/*
				 * Make sure we have an activation time.
				 */
				} elseif ( empty( $ext_reg[ $ext . '_activate_time' ] ) ) {

					continue;	// Get the next plugin.

				/*
				 * Activated less than a week ago.
				 */
				} elseif ( $ext_reg[ $ext . '_activate_time' ] > $week_ago_secs ) {

					continue;	// Get the next plugin.

				/*
				 * Make sure we only show this single notice for the next day.
				 */
				} elseif ( empty( $showing_ext ) || $showing_ext === '1' ) {

					set_transient( $cache_id, $notice_key, $cache_exp_secs );

				/*
				 * We're not showing this plugin right now.
				 */
				} elseif ( $showing_ext !== $notice_key ) {

					continue;	// Get the next plugin.
				}

				$activated_ago       = human_time_diff( time(), $ext_reg[ $ext . '_activate_time' ] );
				$ext_type_transl     = WpssoConfig::get_ext_type_transl( $ext );
				$wp_plugin_link      = '<a href="' . $ext_info[ 'url' ][ 'home' ] . '">' . $ext_name_transl . '</a>';
				$wp_plugin_link_desc = $wp_plugin_link . ' (' . trim( $ext_desc_transl, '.' ) . ')';

				/*
				 * Rate plugin action button.
				 */
				$rate_plugin_label   = sprintf( __( 'Rate the %1$s %2$s', 'wpsso' ), $ext_info[ 'short' ], $ext_type_transl );
				$rate_plugin_clicked = '<p>' . __( 'Thank you!', 'wpsso' ) . '</p>';
				$rate_plugin_button  = '<div class="notice-single-button">' . $form->get_button( $rate_plugin_label, 'button-primary dismiss-on-click',
					'', $ext_info[ 'url' ][ 'review' ], true, false, array( 'dismiss-msg' => $rate_plugin_clicked ) ) . '</div>';

				/*
				 * Already rated action button.
				 */
				$already_rated_label   = sprintf( __( 'I\'ve already rated this %2$s', 'wpsso' ), $ext_info[ 'short' ], $ext_type_transl );
				$already_rated_clicked = '<p>' . __( 'Thank you!', 'wpsso' ) . '</p>';
				$already_rated_button  = '<div class="notice-single-button">' . $form->get_button( $already_rated_label, 'button-secondary dismiss-on-click',
					'', '', false, false, array( 'dismiss-msg' => $already_rated_clicked ) ) . '</div>';

				/*
				 * The notice message.
				 */
				$notice_msg = '<div style="display:table-cell;">';

				$notice_msg .= '<p style="margin-right:30px;">' . $this->p->admin->get_ext_img_icon( $ext ) . '</p>';

				$notice_msg .= '</div>';

				$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

				$notice_msg .= '<p><strong>';

				$notice_msg .= sprintf( __( 'You\'ve been using the %1$s %2$s for a little over %3$s now.', 'wpsso' ), $wp_plugin_link,
					$ext_type_transl, $activated_ago ) . ' ';

				$notice_msg .= '</strong></p><p>';

				$notice_msg .= sprintf( __( 'Please help support and encourage your developers by rating the %1$s.', 'wpsso' ), $ext_type_transl ) . ' ';

				$notice_msg .= '</p><p>';

				$notice_msg .= __( 'It only takes a second.', 'wpsso' ) . ' ' . convert_smilies( ':-)' ) . ' ';

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

		/*
		 * This method is called by timed_notices() if WordPress can dismiss notices and the user can manage options.
		 *
		 * These private notice functions should return the number of notices shown.
		 */
		private function single_notice_upsell() {

			$pkg_info = $this->p->util->get_pkg_info();	// Uses a local cache.

			if ( $pkg_info[ 'wpsso' ][ 'pdir' ] ) {

				return 0;
			}

			$ext_reg         = $this->p->util->reg->get_ext_reg();
			$months_ago_secs = time() - ( 2 * MONTH_IN_SECONDS );

			if ( empty( $ext_reg[ 'wpsso_install_time' ] ) || $ext_reg[ 'wpsso_install_time' ] > $months_ago_secs ) {

				return 0;
			}

			$form            = $this->p->admin->get_form_object( 'wpsso' );
			$user_id         = get_current_user_id();
			$p_info          = $this->p->cf[ 'plugin' ][ 'wpsso' ];
			$p_name_transl   = _x( $p_info[ 'name' ], 'plugin name', 'wpsso' );
			$p_purchase_url  = $p_info[ 'url' ][ 'purchase' ];
			$wp_plugin_link  = '<a href="' . $p_info[ 'url' ][ 'home' ] . '">' . $p_name_transl . '</a>';
			$pkg_pro_transl  = _x( $this->p->cf[ 'packages' ][ 'pro' ], 'package name', 'wpsso' );
			$pkg_std_transl  = _x( $this->p->cf[ 'packages' ][ 'std' ], 'package name', 'wpsso' );
			$notice_key      = 'timed-notice-wpsso-pro-purchase-notice';
			$dismiss_time    = true;	// Allow the notice to be dismissed forever.
			$installed_ago   = human_time_diff( time(), $ext_reg[ 'wpsso_install_time' ] );
			$ext_type_transl = WpssoConfig::get_ext_type_transl( 'wpsso' );

			/*
			 * The action buttons.
			 */
			$purchase_label   = sprintf( __( 'View the %s edition prices', 'wpsso' ), $pkg_pro_transl );
			$purchase_clicked = '<p>' . sprintf( __( 'Thank you for supporting the continued development of %s.', 'wpsso' ), $p_name_transl ) . '</p>';
			$purchase_button  = '<div class="notice-single-button">' . $form->get_button( $purchase_label, 'button-primary dismiss-on-click',
				'', $p_purchase_url, true, false, array( 'dismiss-msg' => $purchase_clicked ) ) . '</div>';

			$no_thanks_label   = sprintf( __( 'I\'ll stay with the %s edition', 'wpsso' ), $pkg_std_transl );
			$no_thanks_clicked = '<p>' . sprintf( __( 'Please consider supporting the development of %s in the future.', 'wpsso' ), $p_name_transl ) . '</p>';
			$no_thanks_button  = '<div class="notice-single-button">' . $form->get_button( $no_thanks_label, 'button-secondary dismiss-on-click',
				'', '', false, false, array( 'dismiss-msg' => $no_thanks_clicked ) ) . '</div>';

			/*
			 * The notice message.
			 */
			$notice_msg = '<div style="display:table-cell;">';
			$notice_msg .= '<p style="margin-right:30px;">' . $this->p->admin->get_ext_img_icon( 'wpsso' ) . '</p>';
			$notice_msg .= '</div>';
			$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';
			$notice_msg .= '<p><strong>';
			$notice_msg .= sprintf( __( 'You\'ve been using the %1$s %2$s for a little over %3$s now.', 'wpsso' ), $wp_plugin_link,
				$ext_type_transl, $installed_ago ) . ' ';
			$notice_msg .= '</strong></p><p>';
			$notice_msg .= sprintf( __( 'Have you thought about purchasing the %s edition?', 'wpsso' ), $pkg_pro_transl ) . ' ';
			$notice_msg .= sprintf( __( 'The %s edition comes loaded with a lot of extra features!', 'wpsso' ), $pkg_pro_transl ) . ' ';
			$notice_msg .= convert_smilies( ':-)' ) . ' ';
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

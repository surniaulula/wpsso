<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoAdminDashboard' ) ) {

	class WpssoAdminDashboard {

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ) );
		}

		public function wp_dashboard_setup() {
		
			if ( ! wp_using_ext_object_cache() ) {

				wp_add_dashboard_widget( $widget_id = 'wpsso-cache-status', $widget_name = __( 'WPSSO Cache Status', 'wpsso' ),
					$callback = array( $this, 'show_metabox_cache_status' ), $control_callback = null, $callback_args = array() );
			}

			wp_add_dashboard_widget( $widget_id = 'wpsso-help-support', $widget_name = __( 'WPSSO Help and Support', 'wpsso' ),
				$callback = array( $this, 'show_metabox_help_support' ), $control_callback = null, $callback_args = array() );

			wp_add_dashboard_widget( $widget_id = 'wpsso-version-info', $widget_name = __( 'WPSSO Version Information', 'wpsso' ),
				$callback = array( $this, 'show_metabox_version_info' ), $control_callback = null, $callback_args = array() );
		}

		public function show_metabox_cache_status() {

			$table_cols         = 4;
			$db_transient_keys  = $this->p->util->cache->get_db_transients_keys();
			$all_transients_pre = 'wpsso_';

			echo '<table class="wpsso-dashboard-widget">';

			echo '<tr><td colspan="' . $table_cols . '"><h4>';
			echo __( 'Database Transients', 'wpsso' );
			echo '</h4></td></tr>';

			echo '<tr>';
			echo '<th class="cache-label"></th>';
			echo '<th class="cache-count">' . __( 'Count', 'wpsso' ) . '</th>';
			echo '<th class="cache-size">' . __( 'MB', 'wpsso' ) . '</th>';
			echo '<th class="cache-expiration">' . __( 'Expiration', 'wpsso' ) . '</th>';
			echo '</tr>';

			/*
			 * Sort the transient array and make sure the "All Transients" count is last.
			 */
			$transients_info = $this->p->cf[ 'wp' ][ 'cache' ][ 'transient' ];

			uasort( $transients_info, array( __CLASS__, 'sort_by_label_key' ) );

			if ( isset( $transients_info[ $all_transients_pre ] ) ) {	// Just in case.

				SucomUtil::move_to_end( $transients_info, $all_transients_pre );
			}

			foreach ( $transients_info as $cache_key => $cache_info ) {

				if ( empty( $cache_info ) ) {

					continue;

				} elseif ( empty( $cache_info[ 'label' ] ) ) {	// Skip cache info without labels.

					continue;
				}

				$cache_text_dom     = empty( $cache_info[ 'text_domain' ] ) ? $this->p->id : $cache_info[ 'text_domain' ];
				$cache_label_transl = _x( $cache_info[ 'label' ], 'option label', $cache_text_dom );
				$cache_count        = count( preg_grep( '/^' . $cache_key . '/', $db_transient_keys ) );
				$cache_size         = $this->p->util->cache->get_db_transients_size_mb( $cache_key, $decimals = 1 );
				$cache_exp_secs     = $this->p->util->get_cache_exp_secs( $cache_key, $cache_type = 'transient' );
				$human_cache_exp    = $cache_exp_secs > 0 ? human_time_diff( 0, $cache_exp_secs ) : __( 'disabled', 'wpsso' );

				echo '<tr>';
				echo '<th class="cache-label">' . $cache_label_transl . ':</th>';
				echo '<td class="cache-count">' . $cache_count . '</td>';
				echo '<td class="cache-size">' . $cache_size . '</td>';

				if ( $cache_key !== $all_transients_pre ) {

					echo '<td class="cache-expiration">' . $human_cache_exp . '</td>';
				}

				echo '</tr>' . "\n";
			}

			echo '</table>';
		}

		public function show_metabox_help_support() {

			$pkg_info = $this->p->util->get_pkg_info();	// Uses a local cache.

			echo '<table class="wpsso-dashboard-widget">';
			echo '<tr><td>';

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Exclude add-ons that are not active.

					continue;
				}

				$action_links = array();

				if ( ! empty( $info[ 'url' ][ 'faqs' ] ) ) {

					$action_links[] = sprintf( __( '<a href="%s">Frequently Asked Questions</a>', 'wpsso' ), $info[ 'url' ][ 'faqs' ] );
				}

				if ( ! empty( $info[ 'url' ][ 'notes' ] ) ) {

					$action_links[] = sprintf( __( '<a href="%s">Notes and Documentation</a>', 'wpsso' ), $info[ 'url' ][ 'notes' ] );
				}

				if ( ! empty( $info[ 'url' ][ 'support' ] ) && $pkg_info[ $ext ][ 'pp' ] ) {

					$action_links[] = sprintf( __( '<a href="%s">Priority Support Ticket</a>', 'wpsso' ), $info[ 'url' ][ 'support' ] ) .
						' (' . __( 'Premium edition', 'wpsso' ) . ')';

				} elseif ( ! empty( $info[ 'url' ][ 'forum' ] ) ) {

					$action_links[] = sprintf( __( '<a href="%s">Community Support Forum</a>', 'wpsso' ), $info[ 'url' ][ 'forum' ] );
				}

				if ( ! empty( $action_links ) ) {

					echo '<h4>' . $info[ 'name' ] . '</h4>' . "\n";

					echo SucomUtil::array_to_list_html( $action_links );
				}
			}

			echo '</td></tr>';
			echo '</table>';
		}

		public function show_metabox_version_info() {

			$table_cols  = 2;
			$label_width = '30%';

			echo '<table class="wpsso-dashboard-widget">';

			/*
			 * Required for chrome to display a fixed table layout.
			 */
			echo '<colgroup>';
			echo '<col style="width:' . $label_width . ';"/>';
			echo '<col/>';
			echo '</colgroup>';

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Filter out add-ons that are not installed.

					continue;
				}

				$plugin_version = isset( $info[ 'version' ] ) ? $info[ 'version' ] : '';	// Static value from config.
				$stable_version = __( 'Not Available', 'wpsso' );	// Default value.
				$latest_version = __( 'Not Available', 'wpsso' );	// Default value.
				$latest_notice  = '';
				$changelog_url  = isset( $info[ 'url' ][ 'changelog' ] ) ? $info[ 'url' ][ 'changelog' ] : '';
				$readme_info    = $this->p->admin->get_readme_info( $ext, $read_cache = true );
				$td_addl_class = '';

				if ( ! empty( $readme_info[ 'stable_tag' ] ) ) {

					$stable_version = $readme_info[ 'stable_tag' ];
					$is_newer_avail = version_compare( $plugin_version, $stable_version, '<' );

					if ( is_array( $readme_info[ 'upgrade_notice' ] ) ) {

						/*
						 * Hooked by the update manager to apply the version filter.
						 *
						 * See WpssoUmFilters->filter_readme_upgrade_notices().
						 */
						$upgrade_notice = apply_filters( 'wpsso_readme_upgrade_notices', $readme_info[ 'upgrade_notice' ], $ext );

						if ( ! empty( $upgrade_notice ) ) {

							reset( $upgrade_notice );

							$latest_version = key( $upgrade_notice );
							$latest_notice  = $upgrade_notice[ $latest_version ];
						}
					}

					/*
					 * Hooked by the update manager to check installed version against the latest version, in
					 * case a non-stable filter is selected for that plugin / add-on.
					 *
					 * See WpssoUmFilters->filter_newer_version_available().
					 */
					if ( apply_filters( 'wpsso_newer_version_available', $is_newer_avail, $ext, $plugin_version, $stable_version, $latest_version ) ) {

						$td_addl_class = ' newer-version-available';

					} elseif ( preg_match( '/[a-z]/', $plugin_version ) ) {		// Current but not stable (alpha chars in version).

						$td_addl_class = ' current-version-not-stable';

					} else $td_addl_class = ' current-version';
				}

				echo '<tr><td colspan="' . $table_cols . '"><h4>' . $info[ 'name' ] . '</h4></td></tr>';
				echo '<tr><th class="version-label">' . _x( 'Installed', 'version label', 'wpsso' ) . ':</th>';
				echo '<td class="version-number' . $td_addl_class . '">' . $plugin_version . '</td></tr>';

				/*
				 * Only show the stable version if the latest version is different (ie. latest is a non-stable version).
				 */
				if ( $stable_version !== $latest_version ) {

					echo '<tr><th class="version-label">' . _x( 'Stable', 'version label', 'wpsso' ) . ':</th>';
					echo '<td class="version-number">' . $stable_version . '</td></tr>';
				}

				echo '<tr><th class="version-label">' . _x( 'Latest', 'version label', 'wpsso' ) . ':</th>';
				echo '<td class="version-number">' . $latest_version . '</td></tr>';

				/*
				 * Only show the latest version notice message if there's a newer / non-matching version.
				 */
				if ( $plugin_version !== $stable_version || $plugin_version !== $latest_version ) {

					echo '<tr><th class="version-label">' . _x( 'Update Notice', 'version label', 'wpsso' ) . ':</th>';
					echo '<td class="latest-notice">';

					if ( ! empty( $latest_notice ) ) {

						echo $latest_notice . ' ';
					}

					echo '<a href="' . $changelog_url . '">' . sprintf( __( 'View %s changelog...', 'wpsso'), $info[ 'short' ] ) . '</a>';
					echo '</td></tr>';
				}
			}

			echo '</table>';
		}

		/*
		 * See WpssoSubmenuDashboard->show_metabox_cache_status().
		 */
		private static function sort_by_label_key( $a, $b ) {

			if ( isset( $a[ 'label' ] ) && isset( $b[ 'label' ] ) ) {

				return strcmp( $a[ 'label' ], $b[ 'label' ] );
			}

			return 0;	// No change.
		}
	}
}

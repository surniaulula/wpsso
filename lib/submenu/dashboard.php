<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSubmenuDashboard' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoSubmenuDashboard extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->menu_id   = $id;
			$this->menu_name = $name;
			$this->menu_lib  = $lib;
			$this->menu_ext  = $ext;
		}

		protected function add_settings_page_callbacks() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_actions( $this, array( 'form_content_metaboxes_dashboard' => 1 ) );
		}

		/*
		 * Remove all action buttons.
		 */
		protected function add_form_buttons( &$form_button_rows ) {

			$form_button_rows = array();
		}

		public function action_form_content_metaboxes_dashboard( $pagehook ) {

			/*
			 * This settings page does not have any "normal" metaboxes, so hide that container and set the container
			 * height to 0 to prevent drag-and-drop in that area, just in case.
			 */
			echo '<style type="text/css">div#' . $pagehook . ' div#normal-sortables { display:none; height:0; min-height:0; }</style>';
			echo '<div id="metabox_col_wrap">' . "\n";

			$max_cols = 2;

			foreach ( range( 1, $max_cols ) as $metabox_col ) {

				$class_last = $metabox_col === $max_cols ? ' metabox_col_last' : '';

				/*
				 * Note that CSS id values must use underscores, instead of hyphens, to sort the metaboxes.
				 */
				echo '<div id="metabox_col_' . $metabox_col . '" class="metabox_col max_cols_' . $max_cols . $class_last . '">' . "\n";

				do_meta_boxes( $pagehook, 'metabox_col_' . $metabox_col, null );

				echo '</div><!-- #metabox_col_' . $metabox_col . ' -->' . "\n";
			}

			echo '</div><!-- #metabox_col_wrap -->' . "\n";
			echo '<div style="clear:both;"></div>' . "\n";
		}

		/*
		 * Add metaboxes for this settings page.
		 *
		 * See WpssoAdmin->load_settings_page().
		 */
		protected function add_settings_page_metaboxes( $callback_args = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$metaboxes = array(
				array(
					'help_support' => _x( 'Get Help and Support', 'metabox title', 'wpsso' ),
					'version_info' => _x( 'Version Information', 'metabox title', 'wpsso' ),
					'cache_status' => wp_using_ext_object_cache() ? false : _x( 'Cache Status', 'metabox title', 'wpsso' ),
				),
				array(
					'features_status' => _x( 'Features Status', 'metabox title', 'wpsso' ),
				),
			);

			foreach ( $metaboxes as $num => $metabox_info ) {

				foreach ( $metabox_info as $metabox_id => $metabox_title ) {

					if ( $metabox_title ) {

						$metabox_col     = $num + 1;
						$metabox_screen  = $this->pagehook;
						$metabox_context = 'metabox_col_' . $metabox_col;	// Use underscores (not hyphens) to order metaboxes.
						$metabox_prio    = 'default';

						$callback_args[ 'page_id' ]       = $this->menu_id;
						$callback_args[ 'metabox_id' ]    = $metabox_id;
						$callback_args[ 'metabox_title' ] = $metabox_title;
						$callback_args[ 'network' ]       = 'sitesubmenu' === $this->menu_lib ? true : false;

						$method_name = method_exists( $this, 'show_metabox_' . $metabox_id ) ?
							'show_metabox_' . $metabox_id : 'show_metabox_table';

						add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, $method_name ),
							$metabox_screen, $metabox_context, $metabox_prio, $callback_args );

						add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_' .  $metabox_id,
							array( $this, 'add_class_postbox_menu_id' ) );
					}
				}
			}
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_help_support() {

			$pkg_info = $this->p->util->get_pkg_info();	// Uses a local cache.

			echo '<table class="sucom-settings wpsso column-metabox"><tr><td>';

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

			echo '</td></tr></table>';
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_cache_status() {

			$table_cols         = 4;
			$db_transient_keys  = $this->p->util->cache->get_db_transients_keys();
			$all_transients_pre = 'wpsso_';

			echo '<table class="sucom-settings wpsso column-metabox cache-status">';

			echo '<tr><td colspan="' . $table_cols . '"><h4>';
			echo sprintf( __( '%s Database Transients', 'wpsso' ), $this->p->cf[ 'plugin' ][ 'wpsso' ][ 'short' ] );
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
			uasort( $this->p->cf[ 'wp' ][ 'transient' ], array( __CLASS__, 'sort_by_label_key' ) );

			if ( isset( $this->p->cf[ 'wp' ][ 'transient' ][ $all_transients_pre ] ) ) {

				SucomUtil::move_to_end( $this->p->cf[ 'wp' ][ 'transient' ], $all_transients_pre );
			}

			foreach ( $this->p->cf[ 'wp' ][ 'transient' ] as $cache_md5_pre => $cache_info ) {

				if ( empty( $cache_info ) ) {

					continue;

				} elseif ( empty( $cache_info[ 'label' ] ) ) {	// Skip cache info without labels.

					continue;
				}

				$cache_text_dom     = empty( $cache_info[ 'text_domain' ] ) ? $this->p->id : $cache_info[ 'text_domain' ];
				$cache_label_transl = _x( $cache_info[ 'label' ], 'option label', $cache_text_dom );
				$cache_count        = count( preg_grep( '/^' . $cache_md5_pre . '/', $db_transient_keys ) );
				$cache_size         = $this->p->util->cache->get_db_transients_size_mb( $cache_md5_pre, $decimals = 1 );
				$cache_exp_secs     = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient' );
				$human_cache_exp    = $cache_exp_secs > 0 ? human_time_diff( 0, $cache_exp_secs ) : __( 'disabled', 'wpsso' );

				echo '<tr>';
				echo '<th class="cache-label">' . $cache_label_transl . ':</th>';
				echo '<td class="cache-count">' . $cache_count . '</td>';
				echo '<td class="cache-size">' . $cache_size . '</td>';

				if ( $cache_md5_pre !== $all_transients_pre ) {

					echo '<td class="cache-expiration">' . $human_cache_exp . '</td>';
				}

				echo '</tr>' . "\n";
			}

			echo '</table>';
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_version_info() {

			$table_cols  = 2;
			$label_width = '30%';

			echo '<table class="sucom-settings wpsso column-metabox version-info" style="table-layout:fixed;">';

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
				$readme_info    = $this->get_readme_info( $ext, $read_cache = true );
				$td_addl_class = '';

				if ( ! empty( $readme_info[ 'stable_tag' ] ) ) {

					$stable_version = $readme_info[ 'stable_tag' ];
					$is_newer_avail = version_compare( $plugin_version, $stable_version, '<' );

					if ( is_array( $readme_info[ 'upgrade_notice' ] ) ) {

						/*
						 * Hooked by the update manager to apply the version filter.
						 */
						$upgrade_notice = apply_filters( 'wpsso_readme_upgrade_notices', $readme_info[ 'upgrade_notice' ], $ext );

						if ( ! empty( $upgrade_notice ) ) {

							reset( $upgrade_notice );

							$latest_version = key( $upgrade_notice );
							$latest_notice  = $upgrade_notice[ $latest_version ];
						}
					}

					/*
					 * Hooked by the update manager to check installed version against the latest version, if a
					 * non-stable filter is selected for that plugin / add-on.
					 */
					if ( apply_filters( 'wpsso_newer_version_available', $is_newer_avail, $ext, $plugin_version, $stable_version, $latest_version ) ) {

						$td_addl_class = ' newer-version-available';

					} elseif ( preg_match( '/[a-z]/', $plugin_version ) ) {		// Current but not stable (alpha chars in version).

						$td_addl_class = ' current-version-not-stable';

					} else {

						$td_addl_class = ' current-version';
					}
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

			do_action( 'wpsso_column_metabox_version_info_table_rows', $table_cols, $this->form );

			echo '</table>';
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_features_status() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$pkg_info        = $this->p->util->get_pkg_info();	// Uses a local cache.
			$integ_tab_url   = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration' );
			$media_tab_url   = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_media' );
			$shorten_tab_url = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_shortening' );
			$review_tab_url  = $this->p->util->get_admin_url( 'advanced#sucom-tabset_services-tab_ratings_reviews' );

			echo '<table class="sucom-settings wpsso column-metabox feature-status">';

			$ext_num = 0;

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$features = array();

				foreach ( array( 'integ', 'pro' ) as $type_dir ) {

					if ( empty( $info[ 'lib' ][ $type_dir ] ) ) {

						continue;
					}

					foreach ( $info[ 'lib' ][ $type_dir ] as $sub_dir => $libs ) {

						if ( 'admin' === $sub_dir ) {	// Skip status for admin options.

							continue;

						} elseif ( ! is_array( $libs ) ) {	// Just in case.

							continue;
						}

						foreach ( $libs as $lib_name => $label ) {

							$label_transl = _x( $label, 'lib file description', $info[ 'text_domain' ] );
							$label_url    = '';
							$classname    = SucomUtil::sanitize_classname( $ext . $type_dir . $sub_dir . $lib_name, $allow_underscore = false );
							$status_off   = 'off';
							$status_on    = 'on';

							if ( 'integ' === $type_dir ) {

								if ( 'data' === $sub_dir ) {

									$label_url = $integ_tab_url;
								}

							} elseif ( 'json' === $type_dir ) {

								if ( 'type' === $sub_dir ) {

									if ( preg_match( '/^(.*) \[schema_type:(.+)\]$/', $label_transl, $match ) ) {

										$type_count   = $this->p->schema->count_schema_type_children( $match[ 2 ] );
										$label_transl = $match[ 1 ] . ' ' . sprintf( __( '(%d sub-types)', 'wpsso' ), $type_count );
									}
								}

								$status_off = 'disabled';
								$status_on  = 'on';

							} elseif ( 'pro' === $type_dir ) {

								$status_off = empty( $this->p->avail[ $sub_dir ][ $lib_name ] ) ? 'off' : 'rec';
								$status_on  = $pkg_info[ $ext ][ 'pp' ] ? 'on' : $status_off;

								if ( 'media' === $sub_dir ) {

									$label_url = $media_tab_url;

								} elseif ( 'review' === $sub_dir ) {

									$label_url = $review_tab_url;

								} elseif ( 'util' === $sub_dir && 'shorten' === $lib_name ) {

									$label_url  = $shorten_tab_url;
									$status_off = 'rec';
								}
							}

							/*
							 * Example $filter_name = 'wpsso_features_status_integ_data_wpseo_meta'.
							 */
							$filter_name     = $ext . '_features_status_' . $type_dir. '_' . $sub_dir . '_' . $lib_name;
							$filter_name     = SucomUtil::sanitize_hookname( $filter_name );
							$features_status = class_exists( $classname ) ? $status_on : $status_off;
							$features_status = apply_filters( $filter_name, $features_status );

							$features[ $label ] = array(
								'type'         => $type_dir,
								'sub'          => $sub_dir,
								'lib'          => $lib_name,
								'label_transl' => $label_transl,
								'label_url'    => $label_url,
								'status'       => $features_status,
							);
						}
					}
				}

				if ( 'wpsso' === $ext ) {

					/*
					 * SSO > Advanced Settings > Service APIs > Shortening Services > URL Shortening Service.
					 */
					foreach ( $this->p->cf[ 'form' ][ 'shorteners' ] as $svc_id => $svc_name ) {

						if ( 'none' === $svc_id ) {

							continue;
						}

						$svc_name_transl = _x( $svc_name, 'option value', 'wpsso' );
						$label_transl    = sprintf( _x( '(api) Get %s Short URL', 'lib file description', 'wpsso' ), $svc_name_transl );
						$svc_status      = 'off';	// Off unless selected or configured.

						if ( isset( $this->p->m[ 'util' ][ 'shorten' ] ) ) {	// URL shortening service is enabled.

							if ( $svc_id === $this->p->options[ 'plugin_shortener' ] ) {	// Shortener API service ID is selected.

								$svc_status = 'rec';	// Recommended if selected.

								if ( $this->p->m[ 'util' ][ 'shorten' ]->get_svc_instance( $svc_id ) ) {	// False or object.

									$svc_status = 'on';	// On if configured.
								}
							}
						}

						$features[ '(api) ' . $svc_name . ' Shortener API' ] = array(
							'label_transl' => $label_transl,
							'label_url'    => $shorten_tab_url,
							'status'       => $svc_status,
						);
					}
				}


				$filter_name = SucomUtil::sanitize_hookname( $ext . '_features_status' );
				$features    = apply_filters( $filter_name, $features, $ext, $info );

				if ( ! empty( $features ) ) {

					$ext_num++;

					echo '<tr><td colspan="3">';
					echo '<h4' . ( $ext_num > 1 ? ' style="margin-top:10px;"' : '' ) . '>';
					echo _x( $info[ 'name' ], 'plugin name', 'wpsso' );
					echo '</h4></td></tr>';

					$this->show_features_status( $ext, $info, $features );
				}
			}

			echo '</table>';
		}
	}
}

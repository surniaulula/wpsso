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

if ( ! class_exists( 'WpssoUtilCache' ) ) {

	class WpssoUtilCache {

		private $p;	// Wpsso class object.
		private $u;	// WpssoUtil class object.

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin, &$util ) {

			$this->p =& $plugin;
			$this->u =& $util;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'wp_scheduled_delete', array( $this, 'clear_cache_files_expired' ) );

			add_action( 'wpsso_refresh_cache', array( $this, 'refresh' ), 10, 1 );	// Single scheduled task.

			if ( $this->is_disabled() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'plugin cache is disabled' );
				}

				$this->u->add_plugin_filters( $this, array(
					'cache_expire_head_markup'       => '__return_zero',	// Used by WpssoHead->get_head_array().
					'cache_expire_cmcf_feed_xml'     => '__return_zero',	// Used by WpssoCmcfXml->get().
					'cache_expire_gmf_feed_xml'      => '__return_zero',	// Used by WpssoGmfXml->get().
					'cache_expire_gmf_inventory_xml' => '__return_zero',	// Used by WpssoGmfXml->get().
					'cache_expire_file_content'      => '__return_zero',
				) );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'plugin cache is enabled' );
			}
		}

		/*
		 * The WPSSO_CACHE_DISABLE constant is true or the 'plugin_cache_disable' option is checked.
		 *
		 * See Wpsso->debug_reminder().
		 * See WpssoUtilCache->__construct().
		 */
		public function is_disabled() {

			if ( defined( 'WPSSO_CACHE_DISABLE' ) ) {

				$is_disabled = WPSSO_CACHE_DISABLE ? true : false;

			} else {

				$is_disabled = empty( $this->p->options[ 'plugin_cache_disable' ] ) ? false : true;
			}

			return $is_disabled;
		}

		/*
		 * Clear cache files older than WPSSO_CACHE_FILES_EXP_SECS.
		 */
		public function clear_cache_files_expired() {

			return $this->clear_cache_files( WPSSO_CACHE_FILES_EXP_SECS );
		}

		/*
		 * Clear cache files.
		 *
		 * See WpssoUtilCache->refresh().
		 * See WpssoAdmin->load_settings_page().
		 */
		public function clear_cache_files( $file_exp_secs = null, $include = array(), $exclude = array() ) {

			$cleared_count = 0;
			$cache_files   = $this->get_cache_files();	// Excludes hidden files and index.php.

			foreach ( $cache_files as $cache_file ) {

				if ( ! empty( $include ) ) {

					foreach ( $include as $preg_match ) {

						if ( ! preg_match( $preg_match, $cache_file ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping ' . $cache_file . ' (not included)' );
							}

							continue 2;
						}
					}
				}

				if ( ! empty( $exclude ) ) {

					foreach ( $exclude as $preg_match ) {

						if ( preg_match( $preg_match, $cache_file ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'skipping ' . $cache_file . ' (excluded)' );
							}

							continue 2;
						}
					}
				}

				if ( null !== $file_exp_secs ) {

					/*
					 * Skip cache files that are newer than the expiration time.
					 */
					if ( filemtime( $cache_file ) > time() - $file_exp_secs ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'skipping ' . $cache_file . ' (newer than expiration time)' );
						}

						continue;
					}
				}

				if ( @unlink( $cache_file ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'removed the cache file ' . $cache_file );
					}

					$cleared_count++;

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error removing cache file ' . $cache_file );
					}

					$error_pre = sprintf( '%s error:', __METHOD__ );
					$error_msg = sprintf( __( 'Error removing cache file %s.', 'wpsso' ), $cache_file );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}
			}

			return $cleared_count++;
		}

		/*
		 * See WpssoSubmenuTools->add_form_buttons().
		 */
		public function count_cache_files() {

			$cache_files = $this->get_cache_files();	// Excludes hidden files and index.php.

			return count( $cache_files );
		}

		public function get_cache_files() {

			$cache_files = array();

			if ( $dh = @opendir( WPSSO_CACHE_DIR ) ) {

				while ( $file_name = @readdir( $dh ) ) {

					$cache_file = WPSSO_CACHE_DIR . $file_name;

					if ( ! preg_match( '/^(\..*|index\.php)$/', $file_name ) && is_file( $cache_file ) ) {

						$cache_files[] = $cache_file;

					}
				}

				closedir( $dh );

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'failed to open the cache folder ' . WPSSO_CACHE_DIR . ' for reading' );
				}

				$error_pre = sprintf( '%s error:', __METHOD__ );

				$error_msg = sprintf( __( 'Failed to open the cache folder %s for reading.', 'wpsso' ), WPSSO_CACHE_DIR );

				$this->p->notice->err( $error_msg );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			return $cache_files;
		}

		/*
		 * See WpssoAdmin->load_settings_page().
		 */
		public function clear_ignored_urls() {

			return $this->p->cache->clear_ignored_urls();
		}

		/*
		 * See WpssoSubmenuTools->add_form_buttons().
		 */
		public function count_ignored_urls() {

			return $this->p->cache->count_ignored_urls();
		}

		/*
		 * See WpssoSubmenuTools->add_form_buttons().
		 */
		public function get_ignored_urls() {

			return $this->p->cache->get_ignored_urls();
		}

		public function clear_db_transients_expired() {

			return $this->clear_db_transients( $key_prefix = '', $incl_shortened = true, $only_expired = true );
		}

		/*
		 * Clear database transients, excluding transients that must be preserved (key begins with 'wpsso_!_'), and
		 * optionally exclude shortened URL transients.
		 *
		 * See WpssoAdmin->load_settings_page().
		 * See WpssoUtilCache->clear_db_transients_expired().
		 */
		public function clear_db_transients( $key_prefix = '', $incl_shortened = true, $only_expired = false ) {

			$cleared_count = 0;

			$transients_subset = $this->get_db_transients_subset( $key_prefix, $incl_shortened, $only_expired );

			foreach ( $transients_subset as $key ) {

				if ( delete_transient( $key ) ) {

					$cleared_count++;
				}
			}

			return $cleared_count;
		}

		public function count_db_transients_expired() {

			return $this->count_db_transients( $key_prefix = '', $incl_shortened = true, $only_expired = true );
		}

		/*
		 * Count database transients, excluding transients that must be preserved (key begins with 'wpsso_!_'), and
		 * optionally exclude shortened URL transients.
		 *
		 * See WpssoSubmenuTools->add_form_buttons().
		 */
		public function count_db_transients( $key_prefix = '', $incl_shortened = true, $only_expired = false ) {

			$transients_subset = $this->get_db_transients_subset( $key_prefix, $incl_shortened, $only_expired );

			return count( $transients_subset );
		}

		/*
		 * A wrapper for WpssoUtilCache->get_db_transients_keys() to exclude transients that must be preserved (key begins
		 * with 'wpsso_!_'), and optionally exclude shortened URL transients.
		 *
		 * See WpssoUtilCache->clear_db_transients().
		 * See WpssoUtilCache->count_db_transients().
		 */
		public function get_db_transients_subset( $key_prefix = '', $incl_shortened = true, $only_expired = false ) {

			$transients_subset = array();

			$transients_keys = $this->get_db_transients_keys( $key_prefix, $only_expired );

			foreach ( $transients_keys as $key ) {

				if ( '' !== $key_prefix ) {				// We're only clearing a specific prefix.

					if ( 0 !== strpos( $key, $key_prefix ) ) {	// Transient does not match that prefix.

						continue;
					}
				}

				if ( 0 === strpos( $key, 'wpsso_!_' ) ) {		// Preserve transients that begin with "wpsso_!_".

					continue;

				} elseif ( ! $incl_shortened ) {				// Not clearing short URLs.

					if ( 0 === strpos( $key, 'wpsso_s_' ) ) {	// This is a shortened URL.

						continue;
					}
				}

				$transients_subset[] = $key;
			}

			return $transients_subset;
		}

		/*
		 * Get transients from the database, and optionally only those that are expired.
		 *
		 * See WpssoAdmin->show_metabox_cache_status().
		 * See WpssoUtilCache->get_db_transients_subset().
		 */
		public function get_db_transients_keys( $key_prefix = '', $only_expired = false ) {

			global $wpdb;

			$transients_keys  = array();
			$transient_prefix = $only_expired ? '_transient_timeout_' : '_transient_';
			$current_time     = isset( $_SERVER[ 'REQUEST_TIME' ] ) ? (int) $_SERVER[ 'REQUEST_TIME' ] : time() ;

			$db_query = 'SELECT option_name';
			$db_query .= ' FROM ' . $wpdb->options;
			$db_query .= ' WHERE option_name LIKE \'' . $transient_prefix . $key_prefix . '%\'';

			if ( $only_expired ) $db_query .= ' AND option_value < ' . $current_time;	// Expiration time older than current time.

			$db_query .= ';';	// End of query.

			$result = $wpdb->get_col( $db_query );

			/*
			 * Remove '_transient_' or '_transient_timeout_' prefix from option name.
			 */
			foreach( $result as $option_name ) {

				$transients_keys[] = str_replace( $transient_prefix, '', $option_name );
			}

			return $transients_keys;
		}

		/*
		 * See WpssoAdmin->show_metabox_cache_status().
		 */
		public function get_db_transients_size_mb( $key_prefix = '' ) {

			global $wpdb;

			/*
			 * The CHAR_LENGTH() function returns the number of characters in a string. It is important to note that
			 * this function counts characters, not bytes, and it is Unicode-aware — meaning it accurately counts the
			 * number of multibyte characters in a string.
			 *
			 * The LENGTH() function, on the other hand, returns the length of a string in bytes. It differs from
			 * CHAR_LENGTH() because it is not Unicode-aware and counts bytes — which can yield different results,
			 * especially for multibyte character sets.
			 */
			$db_query = 'SELECT LENGTH( option_value )';
			$db_query .= ' FROM ' . $wpdb->options;
			$db_query .= ' WHERE option_name LIKE \'_transient_' . $key_prefix . '%\'';
			$db_query .= ';';	// End of query.

			$result = $wpdb->get_col( $db_query );

			return array_sum( $result ) / 1024 / 1024;	// Return size in MB.
		}

		public function show_admin_notices() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$user_id = get_current_user_id();	// Always returns an integer.

			if ( ! $this->show_refresh_running( $user_id ) ) {

				$this->show_refresh_pending( $user_id );
			}
		}

		/*
		 * See WpssoCmcfRewrite::template_redirect().
		 * See WpssoCmcfSubmenuGmfGeneral->add_form_buttons().
		 * See WpssoCmcfSubmenuGmfGeneral->get_table_rows().
		 * See WpssoGmfRewrite::template_redirect().
		 * See WpssoGmfSubmenuGmfGeneral->add_form_buttons().
		 * See WpssoGmfSubmenuGmfGeneral->get_table_rows().
		 */
		public function is_refresh_running() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$running_task = $this->get_running_task( $task_name = 'refresh the cache', WPSSO_CACHE_REFRESH_MAX_TIME );	// Returns false or an array.

			return is_array( $running_task ) ? true : false;
		}

		/*
		 * See WpssoUtilCache->show_admin_notices().
		 */
		public function show_refresh_running( $user_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$task_name = 'refresh the cache';

			$running_task = $this->get_running_task( $task_name, WPSSO_CACHE_REFRESH_MAX_TIME );	// Returns false or an array.

			if ( is_array( $running_task ) ) {	// A task is running.

				/*
				 * Show the cache refresh status to the user who started the cache refresh.
				 */
				$user_id = $this->u->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.

				if ( $user_id === $running_task[ 'user_id' ] ) {

					$task_name_transl = _x( $task_name, 'task name', 'wpsso' );
					$notice_msg       = sprintf( __( 'A task to %s is currently running.', 'wpsso' ), $task_name_transl ) . ' ';
					$notice_key       = $task_name . '-task-info';

					if ( ! empty( $running_task[ 'task_info_transl' ] ) ) {	// Null or string.

						$notice_msg .= $running_task[ 'task_info_transl' ] . ' ';
					}

					$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
				}

				return true;
			}

			return false;
		}

		/*
		 * See WpssoUtilCache->show_admin_notices().
		 */
		public function show_refresh_pending( $user_id = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$event_hook = 'wpsso_refresh_cache';
			$crons      = _get_cron_array();

			if ( ! is_array( $crons ) ) {	// Just in case.

				return;
			}

			foreach ( $crons as $timestamp => $cron_hooks ) {

				if ( ! is_array( $cron_hooks ) ) {	// Just in case.

					continue;
				}

				foreach ( $cron_hooks as $cron_hook => $hook_args ) {

					if ( ! is_array( $hook_args ) ) {	// Just in case.

						continue;

					} elseif ( $event_hook !== $cron_hook ) {	// Only check our own event hook.

						continue;
					}

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'event hook ' . $event_hook . ' found' );

						$this->p->debug->log_arr( 'hook_args', $hook_args );
					}

					/*
					 * The $hook_key value is a checksum of the $hook_arr.
					 *
					 * The same task could be scheduled several times provided the $hook_arr elements are different.
					 */
					foreach ( $hook_args as $hook_key => $hook_arr ) {

						if ( ! is_array( $hook_arr ) ) {	// Just in case.

							continue;
						}

						$user_id          = $this->u->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
						$task_name        = 'refresh the cache';
						$task_name_transl = _x( 'refresh the cache', 'task name', 'wpsso' );
						$time_now         = time();
						$human_time       = human_time_diff( $time_now, $timestamp );
						$notice_key       = $task_name . '-task-info';
						$event_args       = $hook_arr[ 'args' ];

						if ( $time_now < $timestamp ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'task to ' . $task_name . ' for user id ' . $event_args[ 0 ] .
									' scheduled to start in ' . $human_time );
							}

							if ( $user_id === $event_args[ 0 ] ) {

								$notice_msg = sprintf( __( 'A background task will begin in %1$s to %2$s for posts, terms and users.',
									'wpsso' ), $human_time, $task_name_transl );

								$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
							}

							continue;	// Get the next $hook_args.
						}

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'task to ' . $task_name . ' for user id ' . $event_args[ 0 ] .
								' was scheduled to start ' . $human_time . ' ago' );
						}

						if ( $time_now > $timestamp + 60 && $user_id === $event_args[ 0 ] ) {	// Add a 60 second buffer.

							$notice_msg = sprintf( __( 'A background task was scheduled to begin %1$s ago to %2$s for posts, terms and users.',
								'wpsso' ), $human_time, $task_name_transl ) . ' ';

							$notice_msg .= sprintf( __( 'WordPress should have run the %s event hook at that time.',
								'wpsso' ), '<code>' . $event_hook . '</code>' ) . ' ';

							$notice_msg .= sprintf( __( 'If the task does not run, this could indicate a problem with your hosting provider\'s event scheduler and/or a lack of support for the WordPress %s function.', 'wpsso' ), '<code>wp_schedule_single_event()</code>' ) . ' ';

							$notice_msg .= sprintf( __( 'You can activate a plugin like %s to manage scheduled events.',
								'wpsso' ), '<a href="https://wordpress.org/plugins/wp-crontrol/">' .
									esc_html__( 'WP Crontrol', 'wp-crontrol' ) . '</a>' ) . ' ';

							$this->p->notice->warn( $notice_msg, $user_id, $notice_key );
						}

					}	// End of $hook_args loop.

					return true;	// Found one or more $event_hook.

				}	// End of $cron_hooks loop.

			}	// End of $crons loop.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'event hook ' . $event_hook . ' not found' );
			}

			return false;
		}

		public function schedule_refresh( $user_id = null ) {

			$user_id          = $this->u->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
			$task_name        = 'refresh the cache';
			$task_name_transl = _x( 'refresh the cache', 'task name', 'wpsso' );
			$event_time       = time() + WPSSO_SCHEDULE_SINGLE_EVENT_TIME;	// Default event time is now + 10 seconds.
			$human_time       = human_time_diff( 0, WPSSO_SCHEDULE_SINGLE_EVENT_TIME );
			$event_hook       = 'wpsso_refresh_cache';
			$event_args       = array( $user_id );

			if ( $user_id ) {	// Just in case.

				$notice_msg = sprintf( __( 'A background task will begin in %1$s to %2$s for posts, terms and users.', 'wpsso' ),
					$human_time, $task_name_transl );

				$notice_key = $task_name . '-task-info';

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			wp_schedule_single_event( $event_time, $event_hook, $event_args );

			do_action( 'wpsso_cache_refresh_scheduled', $event_time, $event_hook, $event_args );
		}

		/*
		 * See WpssoRarActions->action_refresh_cache().
		 */
		public function refresh( $user_id = null ) {

			$user_id          = $this->u->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
			$task_name        = 'refresh the cache';
			$task_name_transl = _x( 'refresh the cache', 'task name', 'wpsso' );

			if ( ! $this->task_start( $user_id, $task_name, WPSSO_CACHE_REFRESH_MAX_TIME ) ) {

				return;	// Stop here - background task already running.
			}

			if ( $user_id ) {

				$mtime_start  = microtime( $get_float = true );
				$time_on_date = SucomUtil::sprintf_date_time( _x( '%2$s on %1$s', 'time on date', 'wpsso' ) );
				$notice_msg   = sprintf( __( 'A task to %1$s was started at %2$s.', 'wpsso' ), $task_name_transl, $time_on_date );
				$notice_key   = $task_name . '-task-info';

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			if ( 0 === get_current_user_id() ) {	// User is the scheduler.

				$this->set_task_limit( $user_id, $task_name, WPSSO_CACHE_REFRESH_MAX_TIME );	// 1 hour by default.
			}

			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {

				/*
				 * Register image sizes and include WooCommerce front-end libs.
				 */
				do_action( 'wpsso_scheduled_task_started', $user_id );
			}

			/*
			 * Clear cache files but preserve HTML files, like the YouTube video webpage HTML.
			 */
			$this->clear_cache_files( $file_exp_secs = null, $include = array( '/\.(css|js|txt)$/' ) );

			/*
			 * Refresh the Schema types transient cache.
			 */
			$this->p->schema->refresh_schema_types();

			/*
			 * Refresh the minimized notice stylesheet.
			 */
			$this->p->notice->refresh_notice_style();

			/*
			 * Refresh the cache for each public post, term, and user ID.
			 */
			$total_count = array(
				'post' => 0,
				'term' => 0,
				'user' => 0,
			);

			$notice_msg  = '';
			$og_type_key = WpssoAbstractWpMeta::get_column_meta_keys( 'og_type' );	// Example: '_wpsso_head_info_og_type'.
			$abort_time  = time() + WPSSO_CACHE_REFRESH_MAX_TIME - 120;		// Leave time for 'wpsso_cache_refreshed_notice' filters.

			foreach ( $total_count as $obj_name => &$count ) {

				/*
				 * Refresh post, term, and user IDs with missing cache metadata first.
				 */
				$prio_args = array(
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => $og_type_key,
							'compare' => 'NOT EXISTS',
						),
					),
				);

				$prio_ids = call_user_func( array( 'wpsso' . $obj_name, 'get_public_ids' ), $prio_args );
				$obj_ids  = call_user_func( array( 'wpsso' . $obj_name, 'get_public_ids' ) );

				if ( ! empty( $prio_ids ) ) {

					$obj_ids = array_unique( $prio_ids + $obj_ids );
				}

				unset( $prio_args, $prio_ids );

				$obj_count = count( $obj_ids );

				foreach ( $obj_ids as $obj_num => $obj_id ) {

					$mod = $this->p->$obj_name->get_mod( $obj_id );

					$this->task_update( $task_name, sprintf( __( 'Processing %s ID #%d (%s %d of %d).', 'wpsso' ),
						$mod[ 'name_transl' ], $obj_id, $mod[ 'name_transl' ], $obj_num + 1, $obj_count ) );

					$this->refresh_mod_head_meta( $mod );

					unset( $mod );

					$count++;	// Reference to post, term, or user total count.

					if ( time() > $abort_time ) {

						$notice_msg .= sprintf( __( 'The cache refresh time limit of %s has been reached.', 'wpsso' ),
							human_time_diff( 0, WPSSO_CACHE_REFRESH_MAX_TIME ) ) . ' ';	// 1 hour by default.

						$notice_msg .= __( 'The cache refresh task was aborted prematurely.', 'wpsso' ) . ' ';

						break 2;	// Stop here
					}
				}
			}

			$this->task_update( $task_name );

			/*
			 * Create a notification for the end of this task.
			 */
			$notice_msg .= sprintf( __( 'The transient cache for %1$d posts, %2$d terms, and %3$d users has been refreshed.',
				'wpsso' ), $total_count[ 'post' ], $total_count[ 'term' ], $total_count[ 'user' ] ) . ' ';

			/*
			 * The 'wpsso_cache_refreshed_notice' filter allows add-ons to execute additional refresh tasks and append
			 * a notice message.
			 *
			 * See WpssoCmcfFilters->filter_cache_refreshed_notice().
			 * See WpssoGmfFilters->filter_cache_refreshed_notice().
			 */
			$notice_msg = trim( apply_filters( 'wpsso_cache_refreshed_notice', $notice_msg, $user_id ) ) . ' ';

			/*
			 * Clear WP cache and known cache plugins.
			 */
			$notice_msg .= $this->clear_cache();

			if ( $user_id && $notice_msg ) {

				$mtime_total = microtime( $get_float = true ) - $mtime_start;
				$human_time  = human_time_diff( 0, $mtime_total );
				$notice_msg  .= sprintf( __( 'The total execution time for this task was %s.', 'wpsso' ), $human_time ) . ' ';
				$notice_key  = $task_name . '-task-info';

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			$this->task_end( $user_id, $task_name );
		}

		/*
		 * See WpssoPost->after_insert_post().
		 * See WpssoPost->ajax_get_metabox_sso().
		 * See WpssoPost->load_meta_page().
		 * See WpssoPost->refresh_cache().
		 * See WpssoTerm->load_meta_page().
		 * See WpssoTerm->refresh_cache().
		 * See WpssoUser->load_meta_page().
		 * See WpssoUser->refresh_cache().
		 */
		public function refresh_mod_head_meta( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$use_post  = 'post' === $mod[ 'name' ] ? $mod[ 'id' ] : false;
			$head_tags = $this->p->head->get_head_array( $use_post, $mod, $read_cache = false );
			$head_info = $this->p->head->extract_head_info( $head_tags, $mod );

			return array( $head_tags, $head_info );
		}

		public function task_start( $user_id, $task_name, $cache_exp_secs ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$running_task = $this->get_running_task( $task_name, $cache_exp_secs );	// Returns false or an array.

			if ( is_array( $running_task ) ) {

				$task_name_transl = _x( $task_name, 'task name', 'wpsso' );
				$notice_msg       = sprintf( __( 'Ignoring request to %s - this task is already running.', 'wpsso' ), $task_name_transl );
				$notice_key       = $task_name . '-task-ignored';

				$this->p->notice->warn( $notice_msg, $user_id, $notice_key );

				return false;
			}

			$task_cache_id = $this->get_task_cache_id( $task_name );

			return set_transient( $task_cache_id, array(
				'user_id'    => $user_id,
				'task_name'  => $task_name,
				'task_start' => time(),
				'task_limit' => time() + $cache_exp_secs,
			), $cache_exp_secs );

			return true;
		}

		public function get_running_task( $task_name, $cache_exp_secs = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$task_cache_id = $this->get_task_cache_id( $task_name );
			$running_task  = get_transient( $task_cache_id );

			if ( is_array( $running_task ) ) {

				if ( null !== $cache_exp_secs ) {	// Check that transient is not expired.

					if ( ! empty( $running_task[ 'task_start' ] ) ) {

						if ( $running_task[ 'task_start' ] < time() - $cache_exp_secs ) {

							delete_transient( $task_cache_id );

							return false;
						}
					}
				}

				return $running_task;
			}

			return false;
		}

		public function task_update( $task_name, $task_info_transl = null ) {
			
			$running_task = $this->get_running_task( $task_name );	// Returns false or an array.
			
			if ( is_array( $running_task ) ) {

				if ( isset( $running_task[ 'task_limit' ] ) ) {	// Just in case.

					$task_cache_id  = $this->get_task_cache_id( $task_name );
					$cache_exp_secs = $running_task[ 'task_limit' ] - time();	// Time left.

					$running_task[ 'task_info_transl' ] = $task_info_transl;

					return set_transient( $task_cache_id, $running_task, $cache_exp_secs );
				}
			}

			return false;
		}

		public function task_end( $user_id, $task_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$task_cache_id = $this->get_task_cache_id( $task_name );

			return delete_transient( $task_cache_id );
		}

		public function set_task_limit( $user_id, $task_name, $cache_exp_secs ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$ret = set_time_limit( $cache_exp_secs );

			if ( ! $ret ) {

				$human_time = human_time_diff( 0, $cache_exp_secs );

				$task_name_transl = _x( $task_name, 'task name', 'wpsso' );

				$error_pre = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

				$notice_msg = sprintf( __( 'The PHP %1$s function failed to set a maximum execution time of %2$s to %3$s.', 'wpsso' ),
					'<code>set_time_limit()</code>', $human_time, $task_name_transl );

				$notice_key = $task_name . '-task-set-time-limit-error';

				$this->p->notice->err( $notice_msg, $user_id, $notice_key );

				self::safe_error_log( $error_pre . ' ' . $notice_msg, $strip_html = true );
			}

			return $ret;
		}

		/*
		 * Returns a transient cache id.
		 *
		 * See WpssoUser->add_person_role().
		 * See WpssoUser->remove_person_role().
		 * See WpssoUtilCache->refresh().
		 * See WpssoUtilCache->get_running_task().
		 */
		public function get_task_cache_id( $task_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return 'wpsso_!_' . md5( __CLASS__ . '::running-task-'. $task_name );
		}

		/*
		 * Clear WP cache and known cache plugins.
		 */
		public function clear_cache() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			wp_cache_flush();

			$cleared_msg = __( 'The cache for <strong>%s</strong> has also been cleared.', 'wpsso' ) . ' ';

			$notice_msg = '';

			/*
			 * Autoptimize.
			 *
			 * See https://wordpress.org/plugins/autoptimize/.
			 *
			 * Note that Autoptimize is not a page caching plugin - it optimizes CSS and JavaScript.
			 */
			if ( $this->p->avail[ 'util' ][ 'autoptimize' ] ) {

				if ( method_exists( 'autoptimizeCache', 'clearall' ) ) {	// Just in case.

					autoptimizeCache::clearall();

					$notice_msg .= sprintf( $cleared_msg, 'Autoptimize' );
				}
			}

			/*
			 * Cache Enabler.
			 *
			 * See https://wordpress.org/plugins/cache-enabler/.
			 */
			if ( $this->p->avail[ 'cache' ][ 'enabler' ] ) {

				if ( method_exists( 'Cache_Enabler', 'clear_total_cache') ) {

					Cache_Enabler::clear_total_cache();

					$notice_msg .= sprintf( $cleared_msg, 'Cache Enabler' );
				}
			}

			/*
			 * Comet Cache.
			 *
			 * See https://wordpress.org/plugins/comet-cache/.
			 */
			if ( $this->p->avail[ 'cache' ][ 'comet' ] ) {

				$GLOBALS[ 'comet_cache' ]->wipe_cache();

				$notice_msg .= sprintf( $cleared_msg, 'Comet Cache' );
			}

			/*
			 * Hummingbird Cache.
			 *
			 * See https://wordpress.org/plugins/hummingbird-performance/.
			 */
			if ( $this->p->avail[ 'cache' ][ 'hummingbird' ] ) {

				if ( method_exists( '\Hummingbird\WP_Hummingbird', 'flush_cache' ) ) {

					\Hummingbird\WP_Hummingbird::flush_cache();

					$notice_msg .= sprintf( $cleared_msg, 'Hummingbird Cache' );
				}
			}

			/*
			 * LiteSpeed Cache.
			 *
			 * See https://wordpress.org/plugins/litespeed-cache/.
			 */
			if ( $this->p->avail[ 'cache' ][ 'litespeed' ] ) {

				if ( method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {

					LiteSpeed_Cache_API::purge_all();

					$notice_msg .= sprintf( $cleared_msg, 'LiteSpeed Cache' );
				}
			}

			/*
			 * Pagely Cache.
			 */
			if ( $this->p->avail[ 'cache' ][ 'pagely' ] ) {

				if ( method_exists( 'PagelyCachePurge', 'purgeAll' ) ) {

					PagelyCachePurge::purgeAll();

					$notice_msg .= sprintf( $cleared_msg, 'Pagely' );
				}
			}

			/*
			 * SiteGround Cache.
			 */
			if ( $this->p->avail[ 'cache' ][ 'siteground' ] ) {

				sg_cachepress_purge_cache();

				$notice_msg .= sprintf( $cleared_msg, 'Siteground Cache' );
			}

			/*
			 * W3 Total Cache (aka W3TC).
			 */
			if ( $this->p->avail[ 'cache' ][ 'w3tc' ] ) {

				w3tc_pgcache_flush();

				if ( function_exists( 'w3tc_objectcache_flush' ) ) {

					w3tc_objectcache_flush();
				}

				$notice_msg .= sprintf( $cleared_msg, 'W3 Total Cache' );
			}

			/*
			 * WP Engine Cache.
			 */
			if ( $this->p->avail[ 'cache' ][ 'wp-engine' ] ) {

				if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {

					WpeCommon::purge_memcached();
				}

				if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {

					WpeCommon::purge_varnish_cache();
				}

				$notice_msg .= sprintf( $cleared_msg, 'WP Engine Cache' );
			}

			/*
			 * WP Fastest Cache.
			 *
			 * See https://wordpress.org/plugins/wp-fastest-cache/.
			 */
			if ( $this->p->avail[ 'cache' ][ 'wp-fastest' ] ) {

				wpfc_clear_all_cache( true );

				$notice_msg .= sprintf( $cleared_msg, 'WP Fastest Cache' );
			}

			/*
			 * WP Rocket Cache.
			 */
			if ( $this->p->avail[ 'cache' ][ 'wp-rocket' ] ) {

				rocket_clean_domain();

				$notice_msg .= sprintf( $cleared_msg, 'WP Rocket Cache' );
			}

			/*
			 * WP Super Cache.
			 *
			 * See https://wordpress.org/plugins/wp-super-cache/.
			 */
			if ( $this->p->avail[ 'cache' ][ 'wp-super' ] ) {

				wp_cache_clear_cache();

				$notice_msg .= sprintf( $cleared_msg, 'WP Super Cache' );
			}

			return $notice_msg;
		}
	}
}

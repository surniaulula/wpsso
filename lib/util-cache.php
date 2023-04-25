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

			add_action( 'wp_scheduled_delete', array( $this, 'clear_expired_db_transients' ) );
			add_action( 'wpsso_refresh_cache', array( $this, 'refresh' ), 10, 1 );	// Single scheduled task.

			if ( $this->is_disabled() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'plugin cache is disabled' );
				}

				$this->u->add_plugin_filters( $this, array(
					'cache_expire_head_markup' => '__return_zero',	// Used by WpssoHead->get_head_array().
					'cache_expire_gmf_xml'     => '__return_zero',	// Used by WpssoGmfXml->get().
				) );
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
		 * Clear cache files.
		 *
		 * See WpssoUtilCache->refresh().
		 * See WpssoAdmin->load_setting_page().
		 */
		public function clear_cache_files() {

			$count = 0;

			$cache_files = $this->get_cache_files();

			foreach ( $cache_files as $file_path ) {

				if ( @unlink( $file_path ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'removed the cache file ' . $file_path );
					}

					$count++;

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error removing cache file ' . $file_path );
					}

					$error_pre = sprintf( '%s error:', __METHOD__ );
					$error_msg = sprintf( __( 'Error removing cache file %s.', 'wpsso' ), $file_path );

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}
			}

			return $count++;
		}

		/*
		 * See WpssoSubmenuTools->filter_form_button_rows().
		 */
		public function count_cache_files() {

			$cache_files = $this->get_cache_files();

			return count( $cache_files );
		}

		public function get_cache_files() {

			$cache_files = array();

			if ( ! $dh = @opendir( WPSSO_CACHE_DIR ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'failed to open the cache folder ' . WPSSO_CACHE_DIR . ' for reading' );
				}

				$error_pre = sprintf( '%s error:', __METHOD__ );

				$error_msg = sprintf( __( 'Failed to open the cache folder %s for reading.', 'wpsso' ), WPSSO_CACHE_DIR );

				$this->p->notice->err( $error_msg );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

			} else {

				while ( $file_name = @readdir( $dh ) ) {

					$file_path = WPSSO_CACHE_DIR . $file_name;

					if ( ! preg_match( '/^(\..*|index\.php)$/', $file_name ) && is_file( $file_path ) ) {

						$cache_files[] = $file_path;

					}
				}

				closedir( $dh );
			}

			return $cache_files;
		}

		/*
		 * See WpssoAdmin->load_setting_page().
		 */
		public function clear_ignored_urls() {

			return $this->p->cache->clear_ignored_urls();
		}

		/*
		 * See WpssoSubmenuTools->filter_form_button_rows().
		 */
		public function count_ignored_urls() {

			return $this->p->cache->count_ignored_urls();
		}

		/*
		 * See WpssoAdmin->load_setting_page().
		 */
		public function clear_db_transients( $clear_short = true, $key_prefix = '' ) {

			$count = 0;

			$transient_ids = $this->get_db_transients_cache_ids( $clear_short, $key_prefix );

			foreach ( $transient_ids as $key ) {

				if ( delete_transient( $key ) ) {

					$count++;
				}
			}

			return $count;
		}

		/*
		 * See WpssoSubmenuTools->filter_form_button_rows().
		 */
		public function count_db_transients( $include_short = true, $key_prefix = '' ) {

			$transient_ids = $this->get_db_transients_cache_ids( $include_short, $key_prefix );

			return count( $transient_ids );
		}

		public function get_db_transients_cache_ids( $include_short = false, $key_prefix = '' ) {

			$transient_ids  = array();
			$transient_keys = $this->get_db_transient_keys( $only_expired = false, $key_prefix );

			foreach ( $transient_keys as $key ) {

				if ( 0 === strpos( $key_prefix, 'wpsso_' ) ) {

					if ( 0 === strpos( $key, 'wpsso_!_' ) ) {		// Preserve transients that begin with "wpsso_!_".

						continue;

					} elseif ( ! $include_short ) {				// Not clearing short URLs.

						if ( 0 === strpos( $key, 'wpsso_s_' ) ) {	// This is a shortened URL.

							continue;
						}
					}
				}

				if ( '' !== $key_prefix ) {					// We're only clearing a specific prefix.

					if ( 0 !== strpos( $key, $key_prefix ) ) {		// Transient does not match that prefix.

						continue;
					}
				}

				$transient_ids[] = $key;
			}

			return $transient_ids;
		}

		/*
		 * Hooked to WordPress 'wp_scheduled_delete' action.
		 *
		 * See WpssoUtilCache->__construct().
		 */
		public function clear_expired_db_transients() {

			$count          = 0;
			$key_prefix     = 'wpsso_';
			$transient_keys = $this->get_db_transient_keys( $only_expired = true, $key_prefix );

			foreach ( $transient_keys as $key ) {

				if ( delete_transient( $key ) ) {

					$count++;
				}
			}

			return $count;
		}

		public function get_db_transient_keys( $only_expired = false, $key_prefix = '' ) {

			global $wpdb;

			$transient_keys = array();
			$opt_row_prefix = $only_expired ? '_transient_timeout_' : '_transient_';
			$current_time   = isset( $_SERVER[ 'REQUEST_TIME' ] ) ? (int) $_SERVER[ 'REQUEST_TIME' ] : time() ;

			$db_query = 'SELECT option_name';
			$db_query .= ' FROM ' . $wpdb->options;
			$db_query .= ' WHERE option_name LIKE \'' . $opt_row_prefix . $key_prefix . '%\'';

			if ( $only_expired ) {

				$db_query .= ' AND option_value < ' . $current_time;	// Expiration time older than current time.
			}

			$db_query .= ';';	// End of query.

			$result = $wpdb->get_col( $db_query );

			/*
			 * Remove '_transient_' or '_transient_timeout_' prefix from option name.
			 */
			foreach( $result as $option_name ) {

				$transient_keys[] = str_replace( $opt_row_prefix, '', $option_name );
			}

			return $transient_keys;
		}

		/*
		 * See WpssoAdmin->show_metabox_cache_status().
		 */
		public function get_db_transient_size_mb( $decimals = 2, $dec_point = '.', $thousands_sep = ',', $key_prefix = '' ) {

			global $wpdb;

			$db_query = 'SELECT CHAR_LENGTH( option_value ) / 1024 / 1024';
			$db_query .= ', CHAR_LENGTH( option_value )';
			$db_query .= ' FROM ' . $wpdb->options;
			$db_query .= ' WHERE option_name LIKE \'_transient_' . $key_prefix . '%\'';
			$db_query .= ';';	// End of query.

			$result = $wpdb->get_col( $db_query );

			return number_format( array_sum( $result ), $decimals, $dec_point, $thousands_sep );
		}

		public function schedule_refresh( $user_id = null ) {

			$user_id          = $this->u->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
			$task_name        = 'refresh the cache';
			$task_name_transl = _x( 'refresh the cache', 'task name', 'wpsso' );
			$event_time       = time() + WPSSO_SCHEDULE_SINGLE_EVENT_TIME;	// Default event time is now + 8 seconds.
			$event_hook       = 'wpsso_refresh_cache';
			$event_args       = array( $user_id );

			if ( $user_id ) {	// Just in case.

				$notice_msg = sprintf( __( 'A background task will begin shortly to %s for posts, terms and users.', 'wpsso' ), $task_name_transl );
				$notice_key = $task_name . '-task-scheduled';

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			wp_schedule_single_event( $event_time, $event_hook, $event_args );
		}

		public function refresh( $user_id = null ) {

			$user_id          = $this->u->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
			$task_name        = 'refresh the cache';
			$task_name_transl = _x( 'refresh the cache', 'task name', 'wpsso' );
			$cache_id         = $this->get_cache_id();

			if ( ! $this->u->start_task( $user_id, $task_name, WPSSO_CACHE_REFRESH_MAX_TIME, $cache_id ) ) {

				return;	// Stop here - background task already running.
			}

			if ( $user_id ) {

				$mtime_start  = microtime( $get_float = true );
				$time_on_date = SucomUtilWP::sprintf_date_time( _x( '%2$s on %1$s', 'time on date', 'wpsso' ) );
				$notice_msg   = sprintf( __( 'A task to %1$s was started at %2$s.', 'wpsso' ), $task_name_transl, $time_on_date );
				$notice_key   = $task_name . '-task-started';

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			if ( 0 === get_current_user_id() ) {	// User is the scheduler.

				$this->u->set_task_limit( $user_id, $task_name, WPSSO_CACHE_REFRESH_MAX_TIME );
			}

			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {

				/*
				 * Register image sizes and include WooCommerce front-end libs.
				 */
				do_action( 'wpsso_scheduled_task_started', $user_id );
			}

			/*
			 * Since WPSSO Core v15.8.0.
			 *
			 * Refresh the Schema types transient cache.
			 */
			$this->p->schema->refresh_schema_types();

			/*
			 * Since WPSSO Core v14.8.0.
			 *
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

			$og_type_key = WpssoAbstractWpMeta::get_column_meta_keys( 'og_type' );	// Example: '_wpsso_head_info_og_type'.

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

				foreach ( $obj_ids as $obj_id ) {

					$mod = $this->p->$obj_name->get_mod( $obj_id );

					$this->refresh_mod_head_meta( $mod );

					$count++;	// Reference to post, term, or user total count.
				}
			}

			$notice_msg = sprintf( __( 'The transient cache for %1$d posts, %2$d terms, and %3$d users has been refreshed.',
				'wpsso' ), $total_count[ 'post' ], $total_count[ 'term' ], $total_count[ 'user' ] ) . ' ';

			/*
			 * The 'wpsso_cache_refreshed_notice' filter allows add-ons to execute refresh tasks and append a notice message.
			 *
			 * See WpssoCmcfFilters->filter_cache_refreshed_notice().
			 * See WpssoGmfFilters->filter_cache_refreshed_notice().
			 */
			$notice_msg = trim( apply_filters( 'wpsso_cache_refreshed_notice', $notice_msg, $user_id ) ) . ' ';

			/*
			 * Clear cache files.
			 */
			$cleared_count = $this->clear_cache_files();

			if ( $cleared_count > 0 ) {

				$notice_msg .= sprintf( __( '%s cache files have been cleared.', 'wpsso' ), $cleared_count ) . ' ';
			}

			/*
			 * Clear cache plugins.
			 */
			$notice_msg .= $this->clear_cache();

			if ( $user_id && $notice_msg ) {

				$mtime_total = microtime( $get_float = true ) - $mtime_start;
				$human_time  = human_time_diff( 0, $mtime_total );
				$notice_msg  .= sprintf( __( 'The total execution time for this task was %s.', 'wpsso' ), $human_time ) . ' ';
				$notice_key  = $task_name . '-task-ended';

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			delete_transient( $cache_id );
		}

		public function refresh_mod_head_meta( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$use_post  = 'post' === $mod[ 'name' ] ? $mod[ 'id' ] : false;
			$head_tags = $this->p->head->get_head_array( $use_post, $mod, $read_cache = false );
			$head_info = $this->p->head->extract_head_info( $head_tags, $mod );

			return array( $head_tags, $head_info );
		}

		public function get_cache_id() {

			return 'wpsso_!_' . md5( __CLASS__ . '::running_task_name' );
		}

		public function doing_task() {

			$cache_id = $this->get_cache_id();

			return get_transient( $cache_id );
		}

		/*
		 * Clear cache plugins.
		 */
		public function clear_cache() {

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

		/*
		 * Deprecated on 2023/02/12.
		 */
		public function schedule_clear() {

			_deprecated_function( __METHOD__ . '()', '2023/02/12', $replacement = '' );	// Deprecation message.
		}

		/*
		 * Deprecated on 2023/02/12.
		 */
		public function clear() {

			_deprecated_function( __METHOD__ . '()', '2023/02/12', $replacement = '' );	// Deprecation message.
		}

		/*
		 * Deprecated on 2023/02/12.
		 */
		public function stop_refresh() {

			_deprecated_function( __METHOD__ . '()', '2023/02/12', $replacement = '' );	// Deprecation message.
		}
	}
}

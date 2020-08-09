<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoUtilCache' ) ) {

	class WpssoUtilCache {

		private $p;

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_action( 'wp_scheduled_delete', array( $this, 'clear_expired_db_transients' ) );

			add_action( $this->p->lca . '_clear_cache', array( $this, 'clear' ), 10, 4 );		// For single scheduled task.

			add_action( $this->p->lca . '_refresh_cache', array( $this, 'refresh' ), 10, 1 );	// For single scheduled task.
		}

		/**
		 * Schedule the clearing of all caches.
		 */
		public function schedule_clear( $user_id = null, $clear_other = false, $clear_short = null, $refresh = true ) {

			$user_id = $this->p->util->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.

			$event_time = time() + 5;	// Add a 5 second event buffer.

			$event_hook = $this->p->lca . '_clear_cache';

			$event_args = array( $user_id, $clear_other, $clear_short, $refresh );

			wp_schedule_single_event( $event_time, $event_hook, $event_args );
		}

		public function clear( $user_id = null, $clear_other = false, $clear_short = null, $refresh = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $have_cleared = null;

			if ( null !== $have_cleared ) {	// Already run once.

				return;
			}

			$have_cleared = true;	// Prevent running a second time (by an external cache, for example).

			/**
			 * Get the default settings value.
			 */
			if ( null === $clear_short ) {	// Default argument value is null.

				$clear_short = isset( $this->p->options[ 'plugin_clear_short_urls' ] ) ?
					$this->p->options[ 'plugin_clear_short_urls' ] : false;
			}

			$user_id    = $this->p->util->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
			$notice_key = 'clear-cache-status';

			/**
			 * A transient is set and checked to limit the runtime and allow this process to be terminated early.
			 */
			$cache_md5_pre  = $this->p->lca . '_!_';	// Protect transient from being cleared.
			$cache_exp_secs = HOUR_IN_SECONDS;		// Prevent duplicate runs for max 1 hour.
			$cache_salt     = __CLASS__ . '::clear';	// Use a common cache salt for start / stop.
			$cache_id       = $cache_md5_pre . md5( $cache_salt );
			$cache_run_val  = 'running';
			$cache_stop_val = 'stop';

			/**
			 * Prevent concurrent execution.
			 */
			if ( false !== get_transient( $cache_id ) ) {	// Another process is already running.

				if ( $user_id ) {

					$notice_msg = __( 'Aborting task to clear the cache - another identical task is still running.', 'wpsso' );

					$this->p->notice->warn( $notice_msg, $user_id, $notice_key . '-abort' );
				}

				return;
			}

			set_transient( $cache_id, $cache_run_val, $cache_exp_secs );

			$mtime_start = microtime( true );

			if ( $user_id ) {

				$notice_msg = sprintf( __( 'A task to clear the cache was started at %s.', 'wpsso' ), gmdate( 'c' ) );

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			$this->stop_refresh();	// Just in case.

			if ( 0 === get_current_user_id() ) {		// User is the scheduler.

				set_time_limit( HOUR_IN_SECONDS );	// Set maximum PHP execution time to one hour.
			}

			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {

				/**
				 * Register image sizes and include WooCommerce front-end libs.
				 */
				do_action( $this->p->lca . '_scheduled_task_started', $user_id );
			}

			$cleared_files = $this->clear_cache_dir();

			$cleared_transients = $this->clear_db_transients( $clear_short, $transient_prefix = $this->p->lca . '_' );

			$cleared_col_meta = $this->clear_column_meta();

			wp_cache_flush();	// Clear non-database transients as well.

			/**
			 * Clear all other known caches (Comet Cache, W3TC, WP Rocket, etc.).
			 */
			$cleared_other_msg = $clear_other ? $this->clear_other() : '';

			if ( $user_id ) {

				$mtime_total = microtime( true ) - $mtime_start;

				$notice_msg = sprintf( __( '%1$d cached files, %2$d transient cache objects, %3$d column metadata, and the WordPress object cache have been cleared.', 'wpsso' ), $cleared_files, $cleared_transients, $cleared_col_meta ) . ' ' . $cleared_other_msg . ' ';

				$notice_msg .= sprintf( __( 'The total execution time for this task was %0.3f seconds.', 'wpsso' ), $mtime_total ) . ' ';

				if ( $refresh ) {

					$notice_msg .= '<strong>' . __( 'A background task will begin shortly to refresh the post, term and user transient cache objects.',
						'wpsso' ) . '</strong>';
				}

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			if ( $refresh ) {

				$this->schedule_refresh( $user_id, $read_cache = true );	// Run in the next minute.
			}

			delete_transient( $cache_id );
		}

		public function clear_cache_dir() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$cache_dir = constant( 'WPSSO_CACHEDIR' );

			$cleared_count = 0;

			if ( ! $dh = @opendir( $cache_dir ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'failed to open the cache folder ' . $cache_dir . ' for reading' );
				}

				$error_pre = sprintf( '%s error:', __METHOD__ );

				$error_msg = sprintf( __( 'Failed to open the cache folder %s for reading.', 'wpsso' ), $cache_file );

				$this->p->notice->err( $error_msg );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

			} else {

				while ( $file_name = @readdir( $dh ) ) {

					$cache_file = $cache_dir . $file_name;

					if ( ! preg_match( '/^(\..*|index\.php)$/', $file_name ) && is_file( $cache_file ) ) {

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
				}

				closedir( $dh );
			}

			return $cleared_count;
		}

		public function clear_db_transients( $clear_short = false, $transient_prefix = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$cleared_count = 0;

			$transient_keys = SucomUtilWP::get_db_transient_keys( $only_expired = false, $transient_prefix );

			foreach( $transient_keys as $cache_id ) {

				if ( 0 === strpos( $transient_prefix, $this->p->lca ) ) {

					/**
					 * Preserve transients that begin with "wpsso_!_".
					 */
					if ( 0 === strpos( $cache_id, $this->p->lca . '_!_' ) ) {

						continue;
					}

					/**
					 * Maybe delete shortened urls.
					 */
					if ( ! $clear_short ) {							// If not clearing short URLs.

						if ( 0 === strpos( $cache_id, $this->p->lca . '_s_' ) ) {	// This is a shortened URL.

							continue;						// Get the next transient.
						}
					}
				}

				/**
				 * Maybe only clear a specific transient ID prefix.
				 */
				if ( $transient_prefix ) {					// We're only clearing a specific prefix.

					if ( 0 !== strpos( $cache_id, $transient_prefix ) ) {	// The cache ID does not match that prefix.

						continue;					// Get the next transient.
					}
				}

				if ( delete_transient( $cache_id ) ) {

					$cleared_count++;
				}
			}

			return $cleared_count;
		}

		public function clear_expired_db_transients() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$cleared_count = 0;

			$transient_prefix = $this->p->lca . '_';

			$transient_keys = SucomUtilWP::get_db_transient_keys( $only_expired = true, $transient_prefix );

			foreach( $transient_keys as $cache_id ) {

				if ( delete_transient( $cache_id ) ) {

					$cleared_count++;
				}
			}

			return $cleared_count;
		}

		public function clear_column_meta() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$cleared_count = 0;

			$col_meta_keys = WpssoWpMeta::get_column_meta_keys();

			/**
			 * Delete post meta.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'deleting post column meta' );
			}

			foreach ( $col_meta_keys as $col_key => $meta_key ) {

				$cleared_count += SucomUtilWP::count_metadata( $meta_type = 'post', $meta_key );

				delete_metadata( $meta_type = 'post', $object_id = null, $meta_key, $meta_value = null, $delete_all = true );
			}

			/**
			 * Delete term meta.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'deleting term column meta' );
			}

			foreach ( $col_meta_keys as $col_key => $meta_key ) {

				foreach ( WpssoTerm::get_public_ids() as $term_id ) {

					if ( WpssoTerm::delete_term_meta( $term_id, $meta_key ) ) {

						$cleared_count++;
					}
				}
			}

			/**
			 * Delete user meta.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'deleting user column meta' );
			}

			foreach ( $col_meta_keys as $col_key => $meta_key ) {

				$cleared_count += SucomUtilWP::count_metadata( $meta_type = 'user', $meta_key );

				delete_metadata( $meta_type = 'user', $object_id = null, $meta_key, $meta_value = null, $delete_all = true );
			}

			return $cleared_count;
		}

		public function clear_other() {

			$notice_msg = '';

			$cleared_msg = __( 'The cache for <strong>%s</strong> has also been cleared.', 'wpsso' ) . ' ';

			/**
			 * Autoptimize.
			 */
			if ( class_exists( 'autoptimizeCache' ) ) {
			
				if ( method_exists( 'autoptimizeCache', 'clearall' ) ) {
				
					autoptimizeCache::clearall();

					$notice_msg .= sprintf( $cleared_msg, 'Autoptimize' );
				}
			}

			/**
			 * Cache Enabler.
			 */
			if ( class_exists( 'Cache_Enabler' ) ) {
			
				if ( method_exists('Cache_Enabler', 'clear_total_cache') ) { 

					Cache_Enabler::clear_total_cache();

					$notice_msg .= sprintf( $cleared_msg, 'Cache Enabler' );
				}
			}

			/**
			 * Comet Cache.
			 */
			if ( isset( $GLOBALS[ 'comet_cache' ] ) ) {

				$GLOBALS[ 'comet_cache' ]->wipe_cache();

				$notice_msg .= sprintf( $cleared_msg, 'Comet Cache' );
			}

			/**
			 * LiteSpeed Cache.
			 */
			if ( class_exists( 'LiteSpeed_Cache_API' ) ) {

				if ( method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {

					LiteSpeed_Cache_API::purge_all();

					$notice_msg .= sprintf( $cleared_msg, 'LiteSpeed Cache' );
				}
			}

			/**
			 * Hummingbird Cache.
			 */
			if ( class_exists( '\Hummingbird\WP_Hummingbird' ) ) {

				if ( method_exists( '\Hummingbird\WP_Hummingbird', 'flush_cache' ) ) {

					\Hummingbird\WP_Hummingbird::flush_cache();

					$notice_msg .= sprintf( $cleared_msg, 'Hummingbird Cache' );
				}
			}

			/**
			 * Pagely.
			 */
			if ( class_exists( 'PagelyCachePurge' ) ) {

				if ( method_exists( 'PagelyCachePurge', 'purgeAll' ) ) {

					PagelyCachePurge::purgeAll();

					$notice_msg .= sprintf( $cleared_msg, 'Pagely' );
				}
			}

			/**
			 * Siteground Optimizer.
			 */
			if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			
				sg_cachepress_purge_cache();

				$notice_msg .= sprintf( $cleared_msg, 'Siteground Optimizer' );
			}

			/**
			 * W3 Total Cache (aka W3TC).
			 */
			if ( function_exists( 'w3tc_pgcache_flush' ) ) {

				w3tc_pgcache_flush();

				if ( function_exists( 'w3tc_objectcache_flush' ) ) {

					w3tc_objectcache_flush();
				}

				$notice_msg .= sprintf( $cleared_msg, 'W3 Total Cache' );
			}

			/**
			 * WP Engine Cache.
			 */
			if ( class_exists( 'WpeCommon' ) ) {

				if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {

					WpeCommon::purge_memcached();
				}

				if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {

					WpeCommon::purge_varnish_cache();
				}

				$notice_msg .= sprintf( $cleared_msg, 'WP Engine Cache' );
			}

			/**
			 * WP Fastest Cache.
			 */
			if( function_exists( 'wpfc_clear_all_cache' ) ) {

				wpfc_clear_all_cache( true );

				$notice_msg .= sprintf( $cleared_msg, 'WP Fastest Cache' );
			}

			/**
			 * WP Rocket Cache.
			 */
			if ( function_exists( 'rocket_clean_domain' ) ) {

				rocket_clean_domain();

				$notice_msg .= sprintf( $cleared_msg, 'WP Rocket Cache' );
			}

			/**
			 * WP Super Cache.
			 */
			if ( function_exists( 'wp_cache_clear_cache' ) ) {

				wp_cache_clear_cache();

				$notice_msg .= sprintf( $cleared_msg, 'WP Super Cache' );
			}

			return $notice_msg;
		}

		/**
		 * Schedule the refreshing of all post, term and user transient cache objects.
		 */
		public function schedule_refresh( $user_id = null, $read_cache = false ) {

			$user_id = $this->p->util->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.

			$event_time = time() + 5;	// Add a 5 second event buffer.

			$event_hook = $this->p->lca . '_refresh_cache';

			$event_args = array( $user_id, $read_cache );

			$this->stop_refresh();	// Just in case.

			wp_schedule_single_event( $event_time, $event_hook, $event_args );
		}

		public function stop_refresh() {

			$cache_md5_pre  = $this->p->lca . '_!_';	// Protect transient from being cleared.
			$cache_exp_secs = HOUR_IN_SECONDS;		// Prevent duplicate runs for max 1 hour.
			$cache_salt     = __CLASS__ . '::refresh';	// Use a common cache salt for start / stop.
			$cache_id       = $cache_md5_pre . md5( $cache_salt );
			$cache_stop_val = 'stop';

			if ( false !== get_transient( $cache_id ) ) {				// Another process is already running.

				set_transient( $cache_id, $cache_stop_val, $cache_exp_secs );	// Signal the other process to stop.
			}
		}

		public function refresh( $user_id = null, $read_cache = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$user_id    = $this->p->util->maybe_change_user_id( $user_id );	// Maybe change textdomain for user ID.
			$notice_key = 'refresh-cache-status';

			/**
			 * A transient is set and checked to limit the runtime and allow this process to be terminated early.
			 */
			$cache_md5_pre  = $this->p->lca . '_!_';	// Protect transient from being cleared.
			$cache_exp_secs = HOUR_IN_SECONDS;		// Prevent duplicate runs for max 1 hour.
			$cache_salt     = __CLASS__ . '::refresh';	// Use a common cache salt for start / stop.
			$cache_id       = $cache_md5_pre . md5( $cache_salt );
			$cache_run_val  = 'running';
			$cache_stop_val = 'stop';

			/**
			 * Prevent concurrent execution.
			 */
			if ( false !== get_transient( $cache_id ) ) {	// Another process is already running.

				if ( $user_id ) {

					$notice_msg = __( 'Aborting task to refresh the transient cache - another identical task is still running.', 'wpsso' );

					$this->p->notice->warn( $notice_msg, $user_id, $notice_key . '-abort' );
				}

				return;
			}

			set_transient( $cache_id, $cache_run_val, $cache_exp_secs );

			$mtime_start = microtime( true );

			if ( $user_id ) {

				$notice_msg = sprintf( __( 'A task to refresh the transient cache was started at %s.', 'wpsso' ), gmdate( 'c' ) );

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			if ( 0 === get_current_user_id() ) {		// User is the scheduler.

				set_time_limit( HOUR_IN_SECONDS );	// Set maximum PHP execution time to one hour.
			}

			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {

				/**
				 * Register image sizes and include WooCommerce front-end libs.
				 */
				do_action( $this->p->lca . '_scheduled_task_started', $user_id );
			}

			$size_names = $this->p->util->get_image_size_names();

			$post_ids = call_user_func( array( $this->p->lca . 'post', 'get_public_ids' ) );	// Call static method.

			foreach ( $post_ids as $post_id ) {

				foreach ( $size_names as $size_name ) {

					/**
					 * get_mt_single_image_src() returns an og:image:url value, not an og:image:secure_url.
					 */
					$mt_ret = $this->p->media->get_featured( $num = 1, $size_name, $post_id, $check_dupes = false );
				}
			}

			unset( $post_ids );

			$total_count = array(
				'post' => 0,
				'term' => 0,
				'user' => 0,
			);

			foreach ( $total_count as $obj_name => &$count ) {

				$obj_ids = call_user_func( array( $this->p->lca . $obj_name, 'get_public_ids' ) );	// Call static method.

				foreach ( $obj_ids as $obj_id ) {

					/**
					 * Check that we are allowed to continue. Stop if cache status is not 'running'.
					 */
					if ( get_transient( $cache_id ) !== $cache_run_val ) {

						delete_transient( $cache_id );

						return;	// Stop here.
					}

					$count++;	// Reference to post, term, or user total count.

					$mod = $this->p->$obj_name->get_mod( $obj_id );

					$this->refresh_mod_head_meta( $mod, $read_cache );
				}
			}

			if ( $user_id ) {

				$mtime_total = microtime( true ) - $mtime_start;

				$notice_msg = sprintf( __( 'The transient cache for %1$d posts, %2$d terms and %3$d users has been refreshed.',
					'wpsso' ), $total_count[ 'post' ], $total_count[ 'term' ], $total_count[ 'user' ] ) . ' ';

				$notice_msg .= sprintf( __( 'The total execution time for this task was %0.3f seconds.', 'wpsso' ), $mtime_total );

				$this->p->notice->inf( $notice_msg, $user_id, $notice_key );
			}

			delete_transient( $cache_id );
		}

		/**
		 * Called by refresh_cache().
		 */
		private function refresh_mod_head_meta( array $mod, $read_cache = false ) {

			$head_tags = $this->p->head->get_head_array( $use_post = false, $mod, $read_cache );

			$head_info = $this->p->head->extract_head_info( $mod, $head_tags );

			$sleep_secs = SucomUtil::get_const( 'WPSSO_REFRESH_CACHE_SLEEP_TIME', 0.50 );

			usleep( $sleep_secs * 1000000 );	// Sleeps for 0.50 seconds by default.
		}
	}
}

<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoRegister' ) ) {

	class WpssoRegister {

		private $p;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			register_activation_hook( WPSSO_FILEPATH, array( $this, 'network_activate' ) );

			register_deactivation_hook( WPSSO_FILEPATH, array( $this, 'network_deactivate' ) );

			if ( is_multisite() ) {
				add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ), 10, 6 );
				add_action( 'wpmu_activate_blog', array( $this, 'wpmu_activate_blog' ), 10, 5 );
			}
		}

		/**
		 * Fires immediately after a new site is created.
		 */
		public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

			switch_to_blog( $blog_id );

			$this->activate_plugin();

			restore_current_blog();
		}

		/**
		 * Fires immediately after a site is activated (not called when users and sites are created by a Super Admin).
		 */
		public function wpmu_activate_blog( $blog_id, $user_id, $password, $signup_title, $meta ) {

			switch_to_blog( $blog_id );

			$this->activate_plugin();

			restore_current_blog();
		}

		public function network_activate( $sitewide ) {

			self::do_multisite( $sitewide, array( $this, 'activate_plugin' ) );
		}

		public function network_deactivate( $sitewide ) {

			self::do_multisite( $sitewide, array( $this, 'deactivate_plugin' ) );
		}

		/**
		 * uninstall.php defines constants before calling network_uninstall().
		 */
		public static function network_uninstall() {

			$sitewide = true;

			/**
			 * Uninstall from the individual blogs first.
			 */
			self::do_multisite( $sitewide, array( __CLASS__, 'uninstall_plugin' ) );

			$opts = get_site_option( WPSSO_SITE_OPTIONS_NAME, array() );

			if ( ! empty( $opts[ 'plugin_clean_on_uninstall' ] ) ) {

				delete_site_option( WPSSO_SITE_OPTIONS_NAME );
			}
		}

		private static function do_multisite( $sitewide, $method, $args = array() ) {

			if ( is_multisite() && $sitewide ) {

				global $wpdb;

				$db_query = 'SELECT blog_id FROM ' . $wpdb->blogs;
				$blog_ids = $wpdb->get_col( $db_query );

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );

					call_user_func_array( $method, array( $args ) );
				}

				restore_current_blog();

			} else {
				call_user_func_array( $method, array( $args ) );
			}
		}

		private function activate_plugin() {

			load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );

			$this->check_required( WpssoConfig::$cf );

			$this->p->set_config( $activate = true );  // Apply filters and define the $cf[ '*' ] array.
			$this->p->set_options( $activate = true ); // Read / create options and site_options.
			$this->p->set_objects( $activate = true ); // Load all the class objects.

			/**
			 * Clear all caches on activate.
			 */
			if ( ! empty( $this->p->options[ 'plugin_clear_on_activate' ] ) ) {

				$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache',
					_x( 'Clear All Caches on Activate', 'option label', 'wpsso' ) );

				$this->p->notice->upd( sprintf( __( 'A background task will begin shortly to clear all caches (the %s option is enabled).',
					'wpsso' ), $settings_page_link ) );

				$this->p->util->schedule_clear_all_cache( $user_id = get_current_user_id(), $clear_other = true );
			}

			/**
			 * Save plugin install, activation, update times.
			 */
			$plugin_version = WpssoConfig::$cf[ 'plugin' ][ 'wpsso' ][ 'version' ];

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'saving all times for wpsso v' . $plugin_version );
			}

			WpssoUtil::save_all_times( 'wpsso', $plugin_version );

			/**
			 * Add the Person role for WpssoUser::get_public_user_ids(). 
			 */
			$this->p->util->schedule_add_user_roles();

			/**
			 * End of plugin activation.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'done plugin activation' );
			}
		}

		private function deactivate_plugin() {

			/**
			 * Clear all caches on deactivate. Do not use the schedule_clear_all_cache() method
			 * since WPSSO will be deactivated before the scheduled task can begin.
			 *
			 * If 'plugin_clear_on_deactivate' is empty, then at least clear the disk cache.
			 */
			if ( ! empty( $this->p->options[ 'plugin_clear_on_deactivate' ] ) ) {

				$this->p->util->clear_all_cache( $user_id = 0, $clear_other = true, $clear_short = true, $refresh_all = false );

			} else {

				$cache_dir = constant( 'WPSSO_CACHEDIR' );

				if ( $dh = @opendir( $cache_dir ) ) {

					while ( $file_name = @readdir( $dh ) ) {

						$cache_file = $cache_dir . $file_name;

						if ( ! preg_match( '/^(\..*|index\.php)$/', $file_name ) && is_file( $cache_file ) ) {
							@unlink( $cache_file );
						}
					}

					closedir( $dh );
				}
			}

			delete_option( WPSSO_POST_CHECK_NAME );	// Remove the post duplicate check counter.
		}

		/**
		 * uninstall.php defines constants before calling network_uninstall(),
		 * which calls do_multisite(), and then calls uninstall_plugin().
		 */
		private static function uninstall_plugin() {

			$blog_id = get_current_blog_id();
			$opts    = get_option( WPSSO_OPTIONS_NAME, array() );

			delete_option( WPSSO_TS_NAME );

			if ( ! empty( $opts[ 'plugin_clean_on_uninstall' ] ) ) {

				delete_option( WPSSO_OPTIONS_NAME );

				/**
				 * Delete post settings and meta.
				 */
				delete_metadata( 'post', null, WPSSO_META_NAME, '', true );	// $delete_all is true.

				/**
				 * Delete term settings and meta.
				 */
				foreach ( WpssoTerm::get_public_term_ids() as $term_id ) {
					WpssoTerm::delete_term_meta( $term_id, WPSSO_META_NAME );
				}

				/**
				 * Delete user settings and meta.
				 */
				delete_metadata( 'user', null, WPSSO_META_NAME, '', true );	// $delete_all is true.
				delete_metadata( 'user', null, WPSSO_PREF_NAME, '', true );	// $delete_all is true.

				while ( $user_ids = SucomUtil::get_user_ids( $blog_id, '', 1000 ) ) {	// Get a maximum of 1000 user IDs at a time.

					foreach ( $user_ids as $user_id ) {

						delete_user_option( $user_id, WPSSO_DISMISS_NAME, false );	// $global is false.
						delete_user_option( $user_id, WPSSO_DISMISS_NAME, true );	// $global is true.
	
						WpssoUser::delete_metabox_prefs( $user_id );

						$user_obj = get_user_by( 'ID', $user_id );

						$user_obj->remove_role( 'person' );
					}
				}

				remove_role( 'person' );
			}

			/**
			 * Delete All Transients
			 */
			global $wpdb;

			$prefix   = '_transient_';	// Clear all transients, even if no timeout value.
			$db_query = 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE \'' . $prefix . 'wpsso_%\';';
			$expired  = $wpdb->get_col( $db_query ); 

			foreach( $expired as $option_name ) { 

				$transient_name = str_replace( $prefix, '', $option_name );

				if ( ! empty( $transient_name ) ) {
					delete_transient( $transient_name );
				}
			}
		}

		private static function check_required( $cf ) {

			$plugin_name    = $cf[ 'plugin' ][ 'wpsso' ][ 'name' ];
			$plugin_short   = $cf[ 'plugin' ][ 'wpsso' ][ 'short' ];
			$plugin_version = $cf[ 'plugin' ][ 'wpsso' ][ 'version' ];

			foreach ( array( 'wp', 'php' ) as $key ) {

				if ( empty( $cf[$key][ 'min_version' ] ) ) {
					return;
				}

				switch ( $key ) {
					case 'wp':
						global $wp_version;
						$app_version = $wp_version;
						break;
					case 'php':
						$app_version = phpversion();
						break;
				}

				$app_label   = $cf[ $key ][ 'label' ];
				$min_version = $cf[ $key ][ 'min_version' ];
				$version_url = $cf[ $key ][ 'version_url' ];

				if ( version_compare( $app_version, $min_version, '>=' ) ) {
					continue;
				}

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';
				}

				deactivate_plugins( WPSSO_PLUGINBASE, true );	// $silent is true.

				if ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {

					// translators: %s is the short plugin name.
					$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info[ 'short' ] );

					// translators: %1$s is the short plugin name, %2$s is the application name, %3$s is the application version number.
					$error_msg = sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
						'wpsso' ), $plugin_name, $app_label, $min_version );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				wp_die( 
					'<p>' . sprintf( __( 'You are using %1$s version %2$s &mdash; <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.',
						'wpsso' ), $app_label, $app_version, $version_url ) . '</p>' . 
					'<p>' . sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
						'wpsso' ), $plugin_name, $app_label, $min_version ) . '</p>' . 
					'<p>' . sprintf( __( 'Please upgrade %1$s before trying to re-activate the %2$s plugin.',
						'wpsso' ), $app_label, $plugin_name ) . '</p>'
				);
			}
		}
	}
}

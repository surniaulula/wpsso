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

		protected $p;
		protected static $wp_persons = array( 'administrator', 'author', 'editor', 'subscriber' ); // Default wp roles.

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

			if ( empty( $opts['plugin_preserve'] ) ) {
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

			$this->p->set_config( true );  // Apply filters and define $cf['*'] array ( $activate = true ).
			$this->p->set_options( true ); // Read / create options and site_options ( $activate = true ).
			$this->p->set_objects( true ); // Load all the class objects ( $activate = true ).

			if ( ! SucomUtil::get_const( 'WPSSO_REG_CLEAR_CACHE_DISABLE' ) ) {
				$this->p->util->clear_all_cache( false );
			}

			$plugin_version = WpssoConfig::$cf['plugin']['wpsso']['version'];

			WpssoUtil::save_all_times( 'wpsso', $plugin_version );

			if ( ! empty( $this->p->options['plugin_add_person_role'] ) ) {
				foreach ( SucomUtil::get_users_by_roles( self::$wp_persons ) as $user ) {
					$user->add_role( 'person' );
				}
			}
		}

		private function deactivate_plugin() {

			if ( ! SucomUtil::get_const( 'WPSSO_REG_CLEAR_CACHE_DISABLE' ) ) {
				$this->p->util->clear_all_cache( false, true, false );
			}

			/**
			 * Delete all stored notices for all users.
			 */
			$this->p->notice->trunc_all();

			if ( is_object( $this->p->admin ) ) {
				$this->p->admin->reset_check_head_count();
			}
		}

		/**
		 * uninstall.php defines constants before calling network_uninstall().
		 */
		private static function uninstall_plugin() {

			$opts = get_option( WPSSO_OPTIONS_NAME, array() );

			delete_option( WPSSO_TS_NAME );

			if ( empty( $opts['plugin_preserve'] ) ) {

				delete_option( WPSSO_OPTIONS_NAME );

				/**
				 * Delete all post meta.
				 */
				delete_post_meta_by_key( WPSSO_META_NAME );	// Since wp v2.3.

				foreach ( get_users() as $user ) {
					if ( ! empty( $user-> ID ) ) {	// Just in case.

						delete_user_option( $user->ID, WPSSO_DISMISS_NAME, false );	// $global is false.
						delete_user_option( $user->ID, WPSSO_DISMISS_NAME, true );	// $global is true.
	
						delete_user_meta( $user->ID, WPSSO_META_NAME );
						delete_user_meta( $user->ID, WPSSO_PREF_NAME );
	
						WpssoUser::delete_metabox_prefs( $user->ID );

						$user->remove_role( 'person' );
					}
				}

				remove_role( 'person' );

				foreach ( WpssoTerm::get_public_term_ids() as $term_id ) {
					if ( ! empty( $term_id ) ) {	// Just in case.
						WpssoTerm::delete_term_meta( $term_id, WPSSO_META_NAME );
					}
				}
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

			$plugin_name    = $cf['plugin']['wpsso']['name'];
			$plugin_short   = $cf['plugin']['wpsso']['short'];
			$plugin_version = $cf['plugin']['wpsso']['version'];

			foreach ( array( 'wp', 'php' ) as $key ) {

				if ( empty( $cf[$key]['min_version'] ) ) {
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

				$app_label   = $cf[$key]['label'];
				$min_version = $cf[$key]['min_version'];
				$version_url = $cf[$key]['version_url'];

				if ( version_compare( $app_version, $min_version, '>=' ) ) {
					continue;
				}

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';
				}

				deactivate_plugins( WPSSO_PLUGINBASE, true );	// $silent is true

				if ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {

					// translators: %s is the short plugin name
					$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info['short'] );

					// translators: %1$s is the short plugin name, %2$s is the application name, %3$s is the application version number
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

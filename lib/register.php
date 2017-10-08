<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoRegister' ) ) {

	class WpssoRegister {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			register_activation_hook( WPSSO_FILEPATH, array( &$this, 'network_activate' ) );
			register_deactivation_hook( WPSSO_FILEPATH, array( &$this, 'network_deactivate' ) );

			if ( is_multisite() ) {
				add_action( 'wpmu_new_blog', array( &$this, 'wpmu_new_blog' ), 10, 6 );
				add_action( 'wpmu_activate_blog', array( &$this, 'wpmu_activate_blog' ), 10, 5 );
			}
		}

		// fires immediately after a new site is created
		public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
			switch_to_blog( $blog_id );
			$this->activate_plugin();
			restore_current_blog();
		}

		// fires immediately after a site is activated
		// (not called when users and sites are created by a Super Admin)
		public function wpmu_activate_blog( $blog_id, $user_id, $password, $signup_title, $meta ) {
			switch_to_blog( $blog_id );
			$this->activate_plugin();
			restore_current_blog();
		}

		public function network_activate( $sitewide ) {
			self::do_multisite( $sitewide, array( &$this, 'activate_plugin' ) );
		}

		public function network_deactivate( $sitewide ) {
			self::do_multisite( $sitewide, array( &$this, 'deactivate_plugin' ) );
		}

		// uninstall.php defines constants before calling network_uninstall()
		public static function network_uninstall() {
			$sitewide = true;

			// uninstall from the individual blogs first
			self::do_multisite( $sitewide, array( __CLASS__, 'uninstall_plugin' ) );

			$opts = get_site_option( WPSSO_SITE_OPTIONS_NAME, array() );

			if ( empty( $opts['plugin_preserve'] ) ) {
				delete_site_option( WPSSO_SITE_OPTIONS_NAME );
			}
		}

		private static function do_multisite( $sitewide, $method, $args = array() ) {
			if ( is_multisite() && $sitewide ) {
				global $wpdb;
				$dbquery = 'SELECT blog_id FROM '.$wpdb->blogs;
				$ids = $wpdb->get_col( $dbquery );
				foreach ( $ids as $id ) {
					switch_to_blog( $id );
					call_user_func_array( $method, array( $args ) );
				}
				restore_current_blog();
			} else {
				call_user_func_array( $method, array( $args ) );
			}
		}

		private function activate_plugin() {

			$this->check_required( WpssoConfig::$cf );

			$this->p->set_config( true );			// apply filters and define $cf['*'] array ( $activate = true )
			$this->p->set_options( true );			// read / create options and site_options ( $activate = true )
			$this->p->set_objects( true );			// load all the class objects ( $activate = true )

			// clear all cached objects, transients, and any external cache
			if ( ! SucomUtil::get_const( 'WPSSO_REG_CLEAR_CACHE_DISABLE' ) ) {
				$this->p->util->clear_all_cache( true );	// clear existing cache entries ( $clear_ext = true )
			}

			$plugin_version = WpssoConfig::$cf['plugin']['wpsso']['version'];
			WpssoUtil::save_all_times( 'wpsso', $plugin_version );
		}

		private function deactivate_plugin() {

			// clear all cached objects and transients
			if ( ! SucomUtil::get_const( 'WPSSO_REG_CLEAR_CACHE_DISABLE' ) ) {
				$this->p->util->clear_all_cache( false );	// $clear_ext = false
			}

			// trunc all stored notices for all users
			$this->p->notice->trunc_all();

			if ( is_object( $this->p->admin ) ) {		// just in case
				$this->p->admin->reset_check_head_count();
			}
		}

		// uninstall.php defines constants before calling network_uninstall()
		private static function uninstall_plugin() {

			$opts = get_option( WPSSO_OPTIONS_NAME, array() );

			delete_option( WPSSO_TS_NAME );
			delete_option( WPSSO_NOTICE_NAME );

			if ( empty( $opts['plugin_preserve'] ) ) {

				delete_option( WPSSO_OPTIONS_NAME );
				delete_post_meta_by_key( WPSSO_META_NAME );	// since wp v2.3

				foreach ( get_users() as $user ) {
					if ( empty( $user-> ID ) ) {	// just in case
						// site specific user options
						delete_user_option( $user->ID, WPSSO_NOTICE_NAME );
						delete_user_option( $user->ID, WPSSO_DISMISS_NAME );
	
						// global / network user options
						delete_user_meta( $user->ID, WPSSO_META_NAME );
						delete_user_meta( $user->ID, WPSSO_PREF_NAME );
	
						WpssoUser::delete_metabox_prefs( $user->ID );
					}
				}

				foreach ( WpssoTerm::get_public_terms() as $term_id ) {
					if ( ! empty( $term_id ) ) {	// just in case
						WpssoTerm::delete_term_meta( $term_id, WPSSO_META_NAME );
					}
				}
			}

			/*
			 * Delete All Transients
			 */
			global $wpdb;
			$prefix = '_transient_';	// clear all transients, even if no timeout value
			$dbquery = 'SELECT option_name FROM '.$wpdb->options.
				' WHERE option_name LIKE \''.$prefix.'wpsso_%\';';
			$expired = $wpdb->get_col( $dbquery ); 

			foreach( $expired as $option_name ) { 
				$transient_name = str_replace( $prefix, '', $option_name );
				if ( ! empty( $transient_name ) ) {
					delete_transient( $transient_name );
				}
			}
		}

		private static function check_required( $cf ) {

			$plugin_name = $cf['plugin']['wpsso']['name'];
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

				$app_label = $cf[$key]['label'];
				$min_version = $cf[$key]['min_version'];
				$version_url = $cf[$key]['version_url'];

				if ( version_compare( $app_version, $min_version, '>=' ) ) {
					continue;
				}

				load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					require_once trailingslashit( ABSPATH ).'wp-admin/includes/plugin.php';
				}

				error_log( sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
					'wpsso' ), $plugin_name, $app_label, $min_version ) );

				deactivate_plugins( WPSSO_PLUGINBASE, true );	// $silent = true

				wp_die( 
					'<p>'.sprintf( __( 'You are using %1$s version %2$s &mdash; <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.',
						'wpsso' ), $app_label, $app_version, $version_url ).'</p>'.
					'<p>'.sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
						'wpsso' ), $plugin_name, $app_label, $min_version ).'</p>'.
					'<p>'.sprintf( __( 'Please upgrade %1$s before trying to re-activate the %2$s plugin.',
						'wpsso' ), $app_label, $plugin_name ).'</p>'
				);
			}
		}
	}
}

?>

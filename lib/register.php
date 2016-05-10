<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoRegister' ) ) {

	class WpssoRegister {

		protected $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;

			register_activation_hook( WPSSO_FILEPATH, array( &$this, 'network_activate' ) );
			register_deactivation_hook( WPSSO_FILEPATH, array( &$this, 'network_deactivate' ) );
			register_uninstall_hook( WPSSO_FILEPATH, array( __CLASS__, 'network_uninstall' ) );

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

		// can be called from network or single site
		public static function network_uninstall() {
			$sitewide = true;

			// uninstall from the individual blogs first
			self::do_multisite( $sitewide, array( __CLASS__, 'uninstall_plugin' ) );

			$var_const = WpssoConfig::get_variable_constants();
			$opts = get_site_option( $var_const['WPSSO_SITE_OPTIONS_NAME'], array() );

			if ( empty( $opts['plugin_preserve'] ) )
				delete_site_option( $var_const['WPSSO_SITE_OPTIONS_NAME'] );
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
			} else call_user_func_array( $method, array( $args ) );
		}

		private function activate_plugin() {
			$lca = WpssoConfig::$cf['lca'];
			$uca = strtoupper( $lca );
			$short = WpssoConfig::$cf['plugin'][$lca]['short'];
			$version = WpssoConfig::$cf['plugin'][$lca]['version'];

			foreach ( array( 'wp', 'php' ) as $key ) {
				switch ( $key ) {
					case 'wp':
						global $wp_version;
						$label = 'WordPress';
						$req_version = $wp_version;
						break;
					case 'php':
						$label = 'PHP';
						$req_version = phpversion();
						break;
				}
				$min_version = WpssoConfig::$cf[$key]['min_version'];
				if ( version_compare( $req_version, $min_version, '<' ) ) {
					require_once( ABSPATH.'wp-admin/includes/plugin.php' );
					deactivate_plugins( WPSSO_PLUGINBASE );
					error_log( WPSSO_PLUGINBASE.' requires '.$label.' '.$min_version.' or higher ('.$req_version.' reported).' );
					wp_die( '<p>The '.$short.' plugin cannot be activated &mdash; '.
						$short.' requires '.$label.' version '.$min_version.' or newer.</p>' );
				}
			}

			$this->p->set_config();
			$this->p->set_objects( true );	// $activate = true
			$this->p->util->clear_all_cache();

			WpssoUtil::save_all_times( $lca, $version );
			set_transient( $lca.'_activation_redirect', true, 60 * 60 );

			if ( ! is_array( $this->p->options ) || empty( $this->p->options ) ||
				( defined( $uca.'_RESET_ON_ACTIVATE' ) && constant( $uca.'_RESET_ON_ACTIVATE' ) ) ) {

				$this->p->options = $this->p->opt->get_defaults();
				delete_option( constant( $uca.'_OPTIONS_NAME' ) );
				add_option( constant( $uca.'_OPTIONS_NAME' ), $this->p->options, null, 'yes' );	// autoload = yes

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'default options have been added to the database' );

				if ( defined( $uca.'_RESET_ON_ACTIVATE' ) && constant( $uca.'_RESET_ON_ACTIVATE' ) )
					$this->p->notice->inf( $uca.'_RESET_ON_ACTIVATE constant is true &ndash; 
						plugin options have been reset to their default values.', true );
			}
		}

		private function deactivate_plugin() {

			// clear all cached objects and transients
			$this->p->util->delete_expired_db_transients( true );
			$this->p->util->delete_expired_file_cache( true );

			// trunc all stored notices for all users
			$this->p->notice->trunc_all();
		}

		private static function uninstall_plugin() {

			$var_const = WpssoConfig::get_variable_constants();
			$opts = get_option( $var_const['WPSSO_OPTIONS_NAME'], array() );

			delete_option( $var_const['WPSSO_TS_NAME'] );
			delete_option( $var_const['WPSSO_NOTICE_NAME'] );

			if ( empty( $opts['plugin_preserve'] ) ) {
				delete_option( $var_const['WPSSO_OPTIONS_NAME'] );
				delete_post_meta_by_key( $var_const['WPSSO_META_NAME'] );
				foreach ( get_users() as $user ) {

					// site specific user options
					delete_user_option( $user->ID, $var_const['WPSSO_NOTICE_NAME'] );
					delete_user_option( $user->ID, $var_const['WPSSO_DISMISS_NAME'] );

					// global / network user options
					delete_user_meta( $user->ID, $var_const['WPSSO_META_NAME'] );
					delete_user_meta( $user->ID, $var_const['WPSSO_PREF_NAME'] );

					WpssoUser::delete_metabox_prefs( $user->ID );
				}
				foreach ( WpssoTerm::get_public_terms() as $term_id )
					WpssoTerm::delete_term_meta( $term_id, $var_const['WPSSO_META_NAME'] );
			}

			// delete transients
			global $wpdb;
			$dbquery = 'SELECT option_name FROM '.$wpdb->options.' WHERE option_name LIKE \'_transient_timeout_wpsso_%\';';
			$expired = $wpdb->get_col( $dbquery ); 
			foreach( $expired as $transient ) { 
				$key = str_replace('_transient_timeout_', '', $transient);
				if ( ! empty( $key ) )
					delete_transient( $key );
			}
		}
	}
}

?>

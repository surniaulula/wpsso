<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
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

			add_action( 'wpmu_new_blog', array( &$this, 'wpmu_new_blog' ), 10, 6 );
			add_action( 'wpmu_activate_blog', array( &$this, 'wpmu_activate_blog' ), 10, 5 );
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

		public static function network_uninstall() {
			$sitewide = true;
			$cf = WpssoConfig::get_config();

			// uninstall from the individual blogs first
			self::do_multisite( $sitewide, array( __CLASS__, 'uninstall_plugin' ) );

			if ( ! defined( 'WPSSO_SITE_OPTIONS_NAME' ) )
				define( 'WPSSO_SITE_OPTIONS_NAME', $cf['lca'].'_site_options' );

			$opts = get_site_option( WPSSO_SITE_OPTIONS_NAME );

			if ( empty( $opts['plugin_preserve'] ) )
				delete_site_option( WPSSO_SITE_OPTIONS_NAME );
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
			$lca = $this->p->cf['lca'];
			foreach ( array( 'wp', 'php' ) as $key ) {
				switch ( $key ) {
					case 'wp':
						$label = 'WordPress';
						global $wp_version;
						$version = $wp_version;
						break;
					case 'php':
						$label = 'PHP';
						$version = phpversion();
						break;
				}
				$short = $this->p->cf['plugin'][$lca]['short'];
				$min_version = $this->p->cf[$key]['min_version'];

				if ( version_compare( $version, $min_version, '<' ) ) {
					require_once( ABSPATH.'wp-admin/includes/plugin.php' );
					deactivate_plugins( WPSSO_PLUGINBASE );
					error_log( WPSSO_PLUGINBASE.' requires '.$label.' '.$min_version.' or higher ('.$version.' reported).' );
					wp_die( '<p>The '.$short.' plugin cannot be activated &mdash; '.
						$short.' requires '.$label.' version '.$min_version.' or newer.</p>' );
				}
			}
			set_transient( $lca.'_activation_redirect', true, 60 * 60 );
			$this->p->set_config();
			$this->p->set_objects( true );
		}

		private function deactivate_plugin() {
			// clear all cached objects and transients
			$deleted_cache = $this->p->util->delete_expired_file_cache( true );
			$deleted_transient = $this->p->util->delete_expired_transients( true );

			// disable the cron update check
			$slug = $this->p->cf['plugin'][$this->p->cf['lca']]['slug'];
			wp_clear_scheduled_hook( 'plugin_updates-'.$slug );
		}

		private static function uninstall_plugin() {
			global $wpdb;
			$cf = WpssoConfig::get_config();

			if ( ! defined( 'WPSSO_OPTIONS_NAME' ) )
				define( 'WPSSO_OPTIONS_NAME', $cf['lca'].'_options' );

			if ( ! defined( 'WPSSO_META_NAME' ) )
				define( 'WPSSO_META_NAME', '_'.$cf['lca'].'_meta' );

			if ( ! defined( 'WPSSO_PREF_NAME' ) )
				define( 'WPSSO_PREF_NAME', '_'.$cf['lca'].'_pref' );

			$slug = $cf['plugin'][$cf['lca']]['slug'];
			$opts = get_option( WPSSO_OPTIONS_NAME );

			if ( empty( $opts['plugin_preserve'] ) ) {
				delete_option( WPSSO_OPTIONS_NAME );
				delete_post_meta_by_key( WPSSO_META_NAME );
				foreach ( array( WPSSO_META_NAME, WPSSO_PREF_NAME ) as $meta_key )
					foreach ( get_users( array( 'meta_key' => $meta_key ) ) as $user )
						delete_user_option( $user->ID, $meta_key );
				WpssoUser::delete_metabox_prefs();
			}

			// delete update options
			foreach ( $cf['plugin'] as $lca => $info ) {
				delete_option( $lca.'_umsg' );
				delete_option( $lca.'_utime' );
				delete_option( 'external_updates-'.$info['slug'] );
			}

			// delete stored notices
			foreach ( array( 'nag', 'err', 'inf' ) as $type ) {
				$msg_opt = $cf['lca'].'_notices_'.$type;
				delete_option( $msg_opt );
				foreach ( get_users( array( 'meta_key' => $msg_opt ) ) as $user )
					delete_user_option( $user->ID, $msg_opt );
			}

			// delete transients
			$dbquery = 'SELECT option_name FROM '.$wpdb->options.' WHERE option_name LIKE \'_transient_timeout_'.$cf['lca'].'_%\';';
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

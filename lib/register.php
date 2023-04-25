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

if ( ! class_exists( 'WpssoRegister' ) ) {

	class WpssoRegister {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			register_activation_hook( WPSSO_FILEPATH, array( $this, 'network_activate' ) );
			register_deactivation_hook( WPSSO_FILEPATH, array( $this, 'network_deactivate' ) );

			if ( is_multisite() ) {

				add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ), 10, 6 );
				add_action( 'wpmu_activate_blog', array( $this, 'wpmu_activate_blog' ), 10, 5 );
			}
		}

		/*
		 * Fires immediately after a new site is created.
		 */
		public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

			switch_to_blog( $blog_id );

			$this->activate_plugin();

			restore_current_blog();
		}

		/*
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

		/*
		 * uninstall.php defines constants before calling network_uninstall().
		 */
		public static function network_uninstall() {

			$sitewide = true;

			/*
			 * Uninstall from the individual blogs first.
			 */
			self::do_multisite( $sitewide, array( __CLASS__, 'uninstall_plugin' ) );

			$opts = get_site_option( WPSSO_SITE_OPTIONS_NAME, array() );

			if ( ! empty( $opts[ 'plugin_clean_on_uninstall' ] ) ) {

				delete_site_option( WPSSO_SITE_OPTIONS_NAME );
			}
		}

		/*
		 * See WpssoAdmin->activated_plugin().
		 * See WpssoRegister->network_activate()
		 * See WpssoRegister->network_deactivate()
		 * See WpssoRegister->network_uninstall()
		 */
		public static function do_multisite( $sitewide, $method, $args = array() ) {

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

			$this->p->init_textdomain();

			$this->check_required( WpssoConfig::$cf );

			$this->p->set_config( $activate = true );  // Force a refresh of the plugin config.
			$this->p->set_options( $activate = true ); // Read / create options and site_options.
			$this->p->set_objects( $activate = true ); // Load all the class objects.

			/*
			 * Returns the event timestamp, or false if the event has not been registered.
			 */
			$new_install = WpssoUtilReg::get_ext_event_time( 'wpsso', 'install' ) ? false : true;

			/*
			 * Add the "person" role for all WpssoUser::get_public_ids().
			 */
			if ( $new_install ) {

				$this->p->user->schedule_add_person_role();
			}

			/*
			 * Register plugin install, activation, update times.
			 */
			$version = WpssoConfig::get_version();

			WpssoUtilReg::update_ext_version( 'wpsso', $version );

			/*
			 * Refresh cache on activate.
			 */
			$user_id = get_current_user_id();

			$this->p->util->cache->schedule_refresh( $user_id );
		}

		private function deactivate_plugin() {

			$this->reset_admin_checks();
		}

		/*
		 * See WpssoAdmin->activated_plugin().
		 * See WpssoAdmin->after_switch_theme().
		 * See WpssoAdmin->upgrader_process_complete().
		 */
		public function reset_admin_checks() {

			delete_option( WPSSO_POST_CHECK_COUNT_NAME );
			delete_option( WPSSO_TMPL_HEAD_CHECK_NAME );
			delete_option( WPSSO_WP_CONFIG_CHECK_NAME );
		}

		/*
		 * uninstall.php defines constants before calling network_uninstall(), which calls do_multisite(), and then calls
		 * uninstall_plugin().
		 */
		private static function uninstall_plugin() {

			$blog_id = get_current_blog_id();
			$opts    = get_option( WPSSO_OPTIONS_NAME, array() );

			if ( $dh = @opendir( WPSSO_CACHE_DIR ) ) {

				while ( $file_name = @readdir( $dh ) ) {

					$cache_file = WPSSO_CACHE_DIR . $file_name;

					if ( ! preg_match( '/^(\..*|index\.php)$/', $file_name ) && is_file( $cache_file ) ) {

						@unlink( $cache_file );
					}
				}

				closedir( $dh );
			}

			if ( ! empty( $opts[ 'plugin_clean_on_uninstall' ] ) ) {

				delete_option( WPSSO_REG_TS_NAME );
				delete_option( WPSSO_OPTIONS_NAME );

				$col_meta_keys = WpssoAbstractWpMeta::get_column_meta_keys();

				foreach ( $col_meta_keys as $col_key => $meta_key ) {

					delete_metadata( $meta_type = 'post', $object_id = null, $meta_key, $meta_value = null, $delete_all = true );
				}

				/*
				 * Delete post settings and meta.
				 */
				delete_metadata( $meta_type = 'post', $object_id = null, WPSSO_META_NAME, $meta_value = null, $delete_all = true );
				delete_metadata( $meta_type = 'post', $object_id = null, WPSSO_META_ATTACHED_NAME, $meta_value = null, $delete_all = true );

				/*
				 * Delete term settings and meta.
				 */
				foreach ( WpssoTerm::get_public_ids() as $term_id ) {

					foreach ( $col_meta_keys as $col_key => $meta_key ) {

						WpssoTerm::delete_term_meta( $term_id, $meta_key );
					}

					WpssoTerm::delete_term_meta( $term_id, WPSSO_META_NAME );
					WpssoTerm::delete_term_meta( $term_id, WPSSO_META_ATTACHED_NAME );
				}

				/*
				 * Delete user settings and meta.
				 */
				foreach ( $col_meta_keys as $col_key => $meta_key ) {

					delete_metadata( $meta_type = 'user', $object_id = null, $meta_key, $meta_value = null, $delete_all = true );
				}

				delete_metadata( $meta_type = 'user', $object_id = null, WPSSO_META_NAME, $meta_value = null, $delete_all = true );
				delete_metadata( $meta_type = 'user', $object_id = null, WPSSO_META_ATTACHED_NAME, $meta_value = null, $delete_all = true );
				delete_metadata( $meta_type = 'user', $object_id = null, WPSSO_PREF_NAME, $meta_value = null, $delete_all = true );

				while ( $result = SucomUtil::get_users_ids( $blog_id, $role = '', $limit = 1000 ) ) {	// Get a maximum of 1000 user IDs at a time.

					foreach ( $result as $user_id ) {

						delete_user_option( $user_id, WPSSO_DISMISS_NAME, $global = false );
						delete_user_option( $user_id, WPSSO_NOTICES_NAME, $global = false );

						WpssoUser::delete_metabox_prefs( $user_id );

						WpssoUser::remove_role_by_id( $user_id, $role = 'person' );
					}
				}

				remove_role( 'person' );
			}

			/*
			 * Delete plugin transients.
			 */
			global $wpdb;

			$prefix   = '_transient_';
			$db_query = 'SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE \'' . $prefix . 'wpsso_%\';';
			$result   = $wpdb->get_col( $db_query );

			foreach( $result as $option_name ) {

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

				if ( empty( $cf[ $key ][ 'min_version' ] ) ) {

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

				deactivate_plugins( WPSSO_PLUGINBASE, $silent = true );

				if ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {

					$error_pre = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );

					$error_msg = sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
						'wpsso' ), $plugin_name, $app_label, $min_version );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}

				wp_die(
					'<p>' . sprintf( __( 'You are using %1$s version %2$s - <a href="%3$s">this %1$s version is outdated, unsupported, possibly insecure</a>, and may lack important updates and features.',
						'wpsso' ), $app_label, $app_version, $version_url ) . '</p>' .
					'<p>' . sprintf( __( '%1$s requires %2$s version %3$s or higher and has been deactivated.',
						'wpsso' ), $plugin_name, $app_label, $min_version ) . '</p>' .
					'<p>' . sprintf( __( 'Please upgrade %1$s before trying to re-activate the %2$s plugin.',
						'wpsso' ), $app_label, $plugin_name ) . '</p>'
				);
			}
		}

		public static function register_taxonomy_page_tag() {

			$labels = array(
				'name'                       => __( 'Page Tags', 'wpsso' ),
				'singular_name'              => __( 'Page Tag', 'wpsso' ),
				'menu_name'                  => _x( 'Page Tags', 'admin menu name', 'wpsso' ),
				'all_items'                  => __( 'All Page Tags', 'wpsso' ),
				'edit_item'                  => __( 'Edit Page Tag', 'wpsso' ),
				'view_item'                  => __( 'View Page Tag', 'wpsso' ),
				'update_item'                => __( 'Update Page Tag', 'wpsso' ),
				'add_new_item'               => __( 'Add New Page Tag', 'wpsso' ),
				'new_item_name'              => __( 'New Page Tag Name', 'wpsso' ),
				'parent_item'                => __( 'Parent Page Tag', 'wpsso' ),
				'parent_item_colon'          => __( 'Parent Page Tag:', 'wpsso' ),
				'search_items'               => __( 'Search Page Tags', 'wpsso' ),
				'popular_items'              => __( 'Popular Page Tags', 'wpsso' ),
				'separate_items_with_commas' => __( 'Separate page tags with commas', 'wpsso' ),
				'add_or_remove_items'        => __( 'Add or remove page tags', 'wpsso' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'wpsso' ),
				'not_found'                  => __( 'No page tags found.', 'wpsso' ),
				'back_to_items'              => __( '← Back to page tags', 'wpsso' ),
			);

			$args = array(
				'label'              => _x( 'Page Tags', 'Taxonomy label', 'wpsso' ),
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_admin_column'  => true,
				'show_in_quick_edit' => true,
				'show_in_rest'       => true,	// Show this taxonomy in the block editor.
				'show_tagcloud'      => false,
				'description'        => _x( 'Tags for the page post type.', 'Taxonomy description', 'wpsso' ),
				'hierarchical'       => false,
			);

			register_taxonomy( WPSSO_PAGE_TAG_TAXONOMY, array( 'page' ), $args );
		}
	}
}

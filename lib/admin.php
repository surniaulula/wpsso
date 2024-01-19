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

if ( ! class_exists( 'SucomPlugin' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/com/plugin.php';
}

if ( ! class_exists( 'WpssoAdmin' ) ) {

	class WpssoAdmin {

		protected $p;		// Wpsso class object.
		protected $head;	// WpssoAdminHead class object.
		protected $filters;	// WpssoAdminFilters class object.
		protected $menu_id;
		protected $menu_name;
		protected $menu_lib;
		protected $menu_ext;	// Lowercase acronyn for plugin or add-on.
		protected $menu_metaboxes;
		protected $pagehook;
		protected $pageref_url;
		protected $pageref_title;
		protected $toolbar_notice_types = array( 'err', 'warn', 'inf', 'upd' );

		public static $readme = array();

		public $form    = null;
		public $lang    = array();
		public $submenu = array();

		/*
		 * Instantiated by Wpsso->set_objects() when is_admin() is true.
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$doing_ajax = SucomUtilWP::doing_ajax();

			require_once WPSSO_PLUGINDIR . 'lib/admin-head.php';

			$this->head = new WpssoAdminHead( $plugin );

			require_once WPSSO_PLUGINDIR . 'lib/admin-filters.php';

			$this->filters = new WpssoAdminFilters( $plugin );

			/*
			 * The WpssoScript add_iframe_inline_script() method includes jQuery in the thickbox iframe to add the
			 * iframe_parent arguments when the Install or Update button is clicked.
			 *
			 * These class properties are used by both the WpssoAdmin plugin_complete_actions() and
			 * plugin_complete_redirect() methods to direct the user back to the thickbox iframe parent after plugin
			 * installation / activation / update.
			 */
			if ( ! empty( $_GET[ 'wpsso_pageref_title' ] ) ) {

				$this->pageref_title = esc_html( urldecode( $_GET[ 'wpsso_pageref_title' ] ) );
			}

			if ( ! empty( $_GET[ 'wpsso_pageref_url' ] ) ) {

				$this->pageref_url = esc_url_raw( urldecode( $_GET[ 'wpsso_pageref_url' ] ) );
			}

			add_action( 'activated_plugin', array( $this, 'activated_plugin' ), 10, 2 );
			add_action( 'after_switch_theme', array( $this, 'after_switch_theme' ), 10, 2 );
			add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 10, 2 );

			/*
			 * Refresh the cache if/when the WordPress home URL is changed.
			 *
			 * See WpssAdmin->wp_site_option_changed().
			 * See SucomUpdate->wp_site_option_changed().
			 * See SucomUtilWP->raw_do_option().
			 * See SucomUpdateUtilWP->raw_do_option().
			 */
			add_action( 'update_option_home', array( $this, 'wp_site_option_changed' ), PHP_INT_MAX, 3 );
			add_action( 'update_option_blog_public', array( $this, 'wp_site_option_changed' ), PHP_INT_MAX, 3 );
			add_action( 'sucom_update_option_home', array( $this, 'wp_site_option_changed' ), PHP_INT_MAX, 3 );

			/*
			 * This filter re-sorts (if necessary) the active plugins array to load WPSSO Core before its add-ons.
			 */
			add_filter( 'pre_update_option_active_plugins', array( $this, 'pre_update_active_plugins' ), 10, 3 );
			add_filter( 'pre_update_option_active_sitewide_plugins', array( $this, 'pre_update_active_plugins' ), 10, 3 );

			/*
			 * Define and disable the "Expect: 100-continue" header.
			 */
			add_filter( 'http_request_args', array( $this, 'add_expect_header' ), 1000, 2 );

			add_filter( 'http_request_host_is_external', array( $this, 'allow_safe_hosts' ), 1000, 3 );

			/*
			 * Provides plugin data / information from the readme.txt for additional add-ons. Don't hook the
			 * 'plugins_api_result' filter if the update manager is active as it provides more complete plugin data
			 * than what's available from the readme.txt.
			 */
			if ( empty( $this->p->avail[ 'p_ext' ][ 'um' ] ) ) {	// Since WPSSO UM v1.6.0.

				add_filter( 'plugins_api_result', array( $this, 'external_plugin_data' ), 1000, 3 );
			}

			/*
			 * Add plugin / add-on settings links to the WordPress Plugins page. Hook this filter even when doing ajax
			 * since the WordPress plugin search results are created using an ajax call.
			 *
			 * The 5th argument is $menu_lib = 'submenu', so always limit the method arguments to 4.
			 */
			add_filter( 'plugin_action_links', array( $this, 'add_plugin_action_links' ), 1000, 4 );

			if ( is_multisite() ) {

				/*
				 * The 5th argument is $menu_lib = 'sitesubmenu', so always limit the method arguments to 4.
				 */
				add_filter( 'network_admin_plugin_action_links', array( $this, 'add_site_plugin_action_links' ), 1000, 4 );
			}

			if ( ! $doing_ajax ) {

				/*
				 * The admin_menu action is run before admin_init.
				 */
				add_action( 'admin_menu', array( $this, 'load_menu_objects' ), -1000 );
				add_action( 'admin_menu', array( $this, 'add_admin_menus' ), WPSSO_ADD_MENU_PRIORITY, 0 );
				add_action( 'admin_menu', array( $this, 'add_admin_submenus' ), WPSSO_ADD_SUBMENU_PRIORITY, 0 );

				add_action( 'admin_init', array( $this, 'init_check_options' ), -1000 );
				add_action( 'admin_init', array( $this, 'add_plugins_page_upgrade_notice' ) );
				add_action( 'admin_init', array( $this, 'register_setting' ) );

				if ( is_multisite() ) {

					add_action( 'network_admin_menu', array( $this, 'load_network_menu_objects' ), -1000 );
					add_action( 'network_admin_menu', array( $this, 'add_network_admin_menus' ), WPSSO_ADD_MENU_PRIORITY, 0 );
					add_action( 'network_admin_edit_' . WPSSO_SITE_OPTIONS_NAME, array( $this, 'save_site_settings' ) );
				}

				add_filter( 'install_plugin_complete_actions', array( $this, 'plugin_complete_actions' ), 1000, 1 );
				add_filter( 'update_plugin_complete_actions', array( $this, 'plugin_complete_actions' ), 1000, 1 );

				add_filter( 'wp_redirect', array( $this, 'plugin_complete_redirect' ), 1000, 1 );
				add_filter( 'wp_redirect', array( $this, 'profile_updated_redirect' ), -100, 2 );

				add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu_notices' ), WPSSO_TB_NOTICE_MENU_ORDER );
				add_action( 'admin_bar_menu', array( $this, 'check_admin_bar_menu_notices' ), PHP_INT_MAX );

				add_filter( 'sucom_toolbar_notice_types', array( $this, 'filter_toolbar_notice_types' ) );
			}
		}

		/*
		 * Since WPSSO Core v9.3.0.
		 *
		 * This action is run by WordPress after any plugin is activated.
		 *
		 * If a plugin is silently activated (such as during an update), this action does not run.
		 */
		public function activated_plugin( $plugin_base, $network_activation ) {

			$this->p->reg->reset_admin_checks();

			/*
			 * Since WPSSO Core v15.11.0.
			 *
			 * If the array of supported plugins has changed, then refresh the cache.
			 */
			$old_avail = $this->p->avail;
			$new_avail = $this->p->check->get_avail();

			foreach ( array( 'p', 'p_ext' ) as $key ) {	// Remove plugin and add-on information.

				unset( $old_avail[ $key], $new_avail[ $key ] );
			}

			if ( $old_avail !== $new_avail ) {

				$user_id = get_current_user_id();

				WpssoRegister::do_multisite( $network_activation, array( $this->p->util->cache, 'schedule_refresh' ), $args = array( $user_id ) );
			}
		}

		/*
		 * Since WPSSO Core v9.3.0.
		 *
		 * This action is run by WordPress multiple times and the parameters differ according to the context (ie. if the
		 * old theme exists or not). If the old theme is missing, the parameter will be the slug of the old theme.
		 */
		public function after_switch_theme( $old_name, $old_theme ) {

			$this->p->reg->reset_admin_checks();
		}

		/*
		 * Since WPSSO Core v9.3.0.
		 *
		 * This action is run by WordPress when the upgrader process is complete.
		 */
		public function upgrader_process_complete( $wp_upgrader_obj, $hook_extra ) {

			$this->p->reg->reset_admin_checks();
		}

		/*
		 * Refresh the cache if/when the WordPress home URL is changed.
		 *
		 * See WpssAdmin->wp_site_option_changed().
		 * See SucomUpdate->wp_site_option_changed().
		 * See SucomUtilWP->raw_do_option().
		 * See SucomUpdateUtilWP->raw_do_option().
		 */
		public function wp_site_option_changed( $old_value, $new_value, $option_name ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Standardize old and new values for string comparison.
			 */
			$old_value = untrailingslashit( strtolower( $old_value ) );
			$new_value = untrailingslashit( strtolower( $new_value ) );

			if ( $old_value === $new_value ) {	// Nothing to do.

				return;	// Stop here.
			}

			$user_id = get_current_user_id();

			if ( ! $user_id ) {	// Nobody there.

				return;	// Stop here.
			}

			if ( 'home' === $option_name ) {

				$notice_msg = sprintf( __( 'The Site Address URL value has been changed from %1$s to %2$s.', 'wpsso' ), $old_value, $new_value );
				$notice_key = __FUNCTION__ . '_' . $old_value . '_' . $new_value;

				$this->p->notice->upd( $notice_msg, $user_id, $notice_key );
			}

			static $do_once = null;

			if ( true === $do_once ) {

				return;	// Stop here.
			}

			$do_once = true;

			$this->p->util->cache->schedule_refresh( $user_id );
		}

		public function load_network_menu_objects() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->load_menu_objects( array( 'submenu', 'sitesubmenu' ) );
		}

		public function load_menu_objects( $menu_libs = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $menu_libs ) ) {

				$menu_libs = array( 'dashboard', 'plugins', 'profile', 'settings', 'submenu', 'tools', 'users' );
			}

			foreach ( $menu_libs as $menu_lib ) {

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! isset( $info[ 'lib' ][ $menu_lib ] ) ) {	// Not all add-ons have submenus.

						continue;
					}

					foreach ( $info[ 'lib' ][ $menu_lib ] as $menu_id => $menu_name ) {

						$add_menu_item = true;

						if ( empty( $this->p->cf[ 'menu' ][ 'must_load' ][ $menu_id ] ) ) {	// Settings page can be disabled.

							$opt_key       = SucomUtil::sanitize_key( 'plugin_add_' . $menu_lib . '_' . $menu_id );
							$filter_name   = SucomUtil::sanitize_hookname( 'wpsso_add_menu_' . $menu_lib . '_item_' . $menu_id );
							$add_menu_item = isset( $this->p->options[ $opt_key ] ) ? (bool) $this->p->options[ $opt_key ] : true;
							$add_menu_item = apply_filters( $filter_name, $add_menu_item );
						}

						if ( $add_menu_item ) {

							$classname = apply_filters( $ext . '_load_lib', false, $menu_lib . '/' . $menu_id );

							if ( is_string( $classname ) && class_exists( $classname ) ) {

								if ( $this->p->debug->enabled ) {

									$this->p->debug->log( 'loading classname ' . $classname . ' for menu id ' . $menu_id );
								}

								$this->submenu[ $menu_id ] = new $classname( $this->p, $menu_id, $menu_name, $menu_lib, $ext );

							} elseif ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'classname not found for menu lib ' . $menu_lib . '/' . $menu_id );
							}
						}
					}
				}
			}
		}

		public function add_network_admin_menus() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->add_admin_menus( 'sitesubmenu' );
		}

		/*
		 * Add a new main menu and its sub-menu items.
		 */
		public function add_admin_menus( $menu_lib = 'submenu' ) {

			foreach ( $this->p->cf[ '*' ][ 'lib' ][ $menu_lib ] as $this->menu_id => $this->menu_name ) {

				if ( isset( $this->submenu[ $this->menu_id ] ) ) {

					$menu_slug = 'wpsso-' . $this->menu_id;

					$this->submenu[ $this->menu_id ]->add_menu_page( $menu_slug );

					break;
				}
			}

			$menu_args = $this->get_submenu_args( $menu_lib );

			foreach ( $menu_args as $menu_id => $args ) {

				if ( isset( $this->submenu[ $menu_id ] ) ) {	// Just in case.

					call_user_func_array( array( $this->submenu[ $menu_id ], 'add_submenu_page' ), $args );
				}
			}
		}

		/*
		 * Add sub-menu items to existing WordPress menus.
		 */
		public function add_admin_submenus() {

			foreach ( array( 'dashboard', 'plugins', 'profile', 'settings', 'tools', 'users' ) as $menu_lib ) {

				$menu_args = $this->get_submenu_args( $menu_lib );

				foreach ( $menu_args as $menu_id => $args ) {

					if ( isset( $this->submenu[ $menu_id ] ) ) {	// Just in case.

						call_user_func_array( array( $this->submenu[ $menu_id ], 'add_submenu_page' ), $args );
					}
				}
			}
		}

		/*
		 * Return and maybe set/reset the WpssoAdmin->form value.
		 */
		public function &get_form_object( $menu_ext ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! isset( $this->form ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'form object not defined' );
				}

				$this->set_form_object( $menu_ext );

			} elseif ( $this->form->get_ext_id() !== $menu_ext ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'form object does not match' );
				}

				$this->set_form_object( $menu_ext );
			}

			return $this->form;
		}

		/*
		 * Set the WpssoAdmin->form value.
		 */
		protected function set_form_object( $menu_ext ) {	// $menu_ext required for text_domain.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'setting form object for ' . $menu_ext );
			}

			$defs = $this->p->opt->get_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_OPTIONS_NAME, $this->p->options, $defs, $menu_ext );
		}

		public function register_setting() {

			register_setting( 'wpsso_settings', WPSSO_OPTIONS_NAME, $args = array(
				'sanitize_callback' => array( $this, 'settings_sanitation' ),
			) );
		}

		public function init_check_options() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * set_options() may have loaded the static defaults for new or missing options.
			 *
			 * After all objects have been loaded, and all filter / action hooks registered, check to see if the
			 * options array needs to be reloaded from the filtered defaults.
			 */
			if ( ! empty( $this->p->options[ '__reload_defaults' ] ) ) {

				$this->p->options = $this->p->opt->get_defaults();
			}

			if ( is_multisite() ) {	// Load the site options array.

				if ( ! empty( $this->p->site_options[ '__reload_defaults' ] ) ) {

					$this->p->site_options = $this->p->opt->get_site_defaults();
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'checking ' . WPSSO_OPTIONS_NAME . ' options' );
			}

			$this->p->options = $this->p->opt->check_options( WPSSO_OPTIONS_NAME, $this->p->options, $network = false );

			if ( is_multisite() ) {	// Load the site options array.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking ' . WPSSO_SITE_OPTIONS_NAME . ' options' );
				}

				$this->p->site_options = $this->p->opt->check_options( WPSSO_SITE_OPTIONS_NAME, $this->p->site_options, $network = true );
			}

			/*
			 * Init option checks.
			 *
			 * See WpssoWcmd->init_check_options().
			 */
			do_action( 'wpsso_init_check_options' );
		}

		public function add_plugins_page_upgrade_notice() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! empty( $info[ 'base' ] ) ) {

					/*
					 * Fires at the end of the update message container in each row of the plugins list table.
					 *
					 * See wordpress/wp-admin/includes/update.php:587.
					 */
					add_action( 'in_plugin_update_message-' . $info[ 'base' ], array( $this, 'show_upgrade_notice' ), 10, 2 );
				}
			}
		}

		public function show_upgrade_notice( $data, $response ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( isset( $data[ 'upgrade_notice' ] ) ) {	// Just in case.

				echo '<span style="display:table;border-collapse:collapse;margin-left:26px;">';
				echo '<span style="display:table-cell;">' . strip_tags( $data[ 'upgrade_notice' ] ) . '</span>';
				echo '</span>';
			}
		}

		protected function add_menu_page( $menu_slug ) {

			$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
			$page_title  = $pkg_info[ 'wpsso' ][ 'short_pkg' ] . ' - ' . $this->menu_name;
			$menu_title  = $this->get_menu_title();
			$cf_wp_admin = $this->p->cf[ 'wp' ][ 'admin' ];
			$capability  = isset( $cf_wp_admin[ $this->menu_lib ][ 'cap' ] ) ? $cf_wp_admin[ $this->menu_lib ][ 'cap' ] : 'manage_options';
			$icon_url    = 'none';	// Icon provided by WpssoStyle::admin_register_page_styles().
			$function    = array( $this, 'show_settings_page' );
			$position    = WPSSO_MENU_ORDER;

			$this->pagehook = add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

			add_action( 'load-' . $this->pagehook, array( $this, 'load_settings_page' ) );
		}

		protected function add_submenu_page( $parent_slug, $menu_id, $menu_title, $menu_lib, $menu_ext, $css_class = '' ) {

			$css_class   = trim( 'wpsso-menu-item wpsso-' . $menu_id . ' ' . $css_class );
			$menu_title  = '<div class="' . $css_class . ' menu-item-label">' . $menu_title . '</div>';	// Wrap the title string.
			$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
			$page_title  = $pkg_info[ $menu_ext ][ 'short_pkg' ] . ' - ' . $menu_title;
			$cf_wp_admin = $this->p->cf[ 'wp' ][ 'admin' ];
			$capability  = isset( $cf_wp_admin[ $menu_lib ][ 'cap' ] ) ? $cf_wp_admin[ $menu_lib ][ 'cap' ] : 'manage_options';
			$menu_slug   = 'wpsso-' . $menu_id;
			$function    = array( $this, 'show_settings_page' );
			$position    = null;

			if ( isset( $cf_wp_admin[ $menu_lib ][ 'sub' ][ $menu_id ] ) ) {

				$cf_menu_id = $cf_wp_admin[ $menu_lib ][ 'sub' ][ $menu_id ];
				$capability = isset( $cf_menu_id[ 'cap' ] ) ? $cf_menu_id[ 'cap' ] : $capability;
				$position   = isset( $cf_menu_id[ 'pos' ] ) ? $cf_menu_id[ 'pos' ] : $position;
			}

			$this->pagehook = add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function, $position );

			add_action( 'load-' . $this->pagehook, array( $this, 'load_settings_page' ) );
		}

		/*
		 * Add plugin links for the WordPress network plugins page.
		 */
		public function add_site_plugin_action_links( $action_links, $plugin_base, $plugin_data, $context, $menu_lib = 'sitesubmenu' ) {

			return $this->add_plugin_action_links( $action_links, $plugin_base, $plugin_data, $context, $menu_lib );
		}

		/*
		 * Add plugin links for the WordPress plugins page.
		 *
		 * $plugin_data is an array of plugin data. See get_plugin_data().
		 *
		 * $context can be 'all', 'active', 'inactive', 'recently_activated', 'upgrade', 'mustuse', 'dropins', or 'search'.
		 */
		public function add_plugin_action_links( $action_links, $plugin_base, $plugin_data, $context, $menu_lib = 'submenu'  ) {

			if ( ! isset( $this->p->cf[ '*' ][ 'base' ][ $plugin_base ] ) ) {

				return $action_links;
			}

			$ext               = $this->p->cf[ '*' ][ 'base' ][ $plugin_base ];
			$info              = $this->p->cf[ 'plugin' ][ $ext ];
			$settings_page     = empty( $info[ 'lib' ][ $menu_lib ] ) ? '' : key( $info[ 'lib' ][ $menu_lib ] );
			$settings_page_url = $this->p->util->get_admin_url( $settings_page );

			switch ( $ext ) {

				case 'wpsso':

					$addons_page     = 'sitesubmenu' === $menu_lib ? 'site-addons' : 'addons';
					$addons_page_url = $this->p->util->get_admin_url( $addons_page );

					if ( ! empty( $settings_page ) ) {

						$action_links[] = '<a href="' . $settings_page_url . '">' . _x( 'Plugin Settings', 'plugin action link', 'wpsso' ) . '</a>';
					}

					$action_links[] = '<a href="' . $addons_page_url . '">' . _x( 'Plugin Add-ons', 'plugin action link', 'wpsso' ) . '</a>';

					break;

				default:

					if ( ! empty( $settings_page ) ) {

						$action_links[] = '<a href="' . $settings_page_url . '">' . _x( 'Add-on Settings', 'plugin action link', 'wpsso' ) . '</a>';
					}

					break;
			}

			return $action_links;
		}

		/*
		 * Define and disable the "Expect: 100-continue" header.
		 *
		 * $parsed_args should be an array, so make sure other filters aren't giving us a string or boolean.
		 */
		public function add_expect_header( $parsed_args, $url ) {

			if ( ! is_array( $parsed_args ) ) {	// Just in case.

				$parsed_args = array();
			}

			if ( empty( $parsed_args[ 'headers' ] ) ) {	// Just in case.

				$parsed_args[ 'headers' ] = array();

			/*
			 * WordPress allows passing headers as a string -- fix that issue here so we can update the 'headers' array
			 * properly.
			 *
			 * See https://core.trac.wordpress.org/browser/tags/5.7.1/src/wp-includes/class-http.php#L310.
			 */
			} elseif ( ! is_array( $parsed_args[ 'headers' ] ) ) {

				$processedHeaders = WP_Http::processHeaders( $parsed_args[ 'headers' ] );

				$parsed_args[ 'headers' ] = $processedHeaders[ 'headers' ];
			}

			$parsed_args[ 'headers' ][ 'Expect' ] = '';

			return $parsed_args;
		}

		public function allow_safe_hosts( $is_allowed, $ip, $url ) {

			if ( $is_allowed ) {	// Already allowed.

				return $is_allowed;
			}

			if ( isset( $this->p->cf[ 'extend' ] ) ) {

				foreach ( $this->p->cf[ 'extend' ] as $host ) {

					if ( 0 === strpos( $url, $host ) ) {

						return true;
					}
				}
			}

			return $is_allowed;
		}

		/*
		 * Provides plugin data / information from the readme.txt for additional add-ons.
		 */
		public function external_plugin_data( $result, $action = null, $args = null ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( 'plugin_information' !== $action  ) {	// This filter only provides plugin data.

				return $result;

			} elseif ( empty( $args->slug ) ) {	// Make sure we have a slug in the request.

				return $result;

			} elseif ( empty( $this->p->cf[ '*' ][ 'slug' ][ $args->slug ] ) ) {	// Make sure the plugin slug is one of ours.

				return $result;

			} elseif ( isset( $result->slug ) && $result->slug === $args->slug ) {	// If the object from WordPress looks complete, return it as-is.

				return $result;
			}

			$ext = $this->p->cf[ '*' ][ 'slug' ][ $args->slug ];	// Get the add-on acronym to read its config.

			if ( empty( $this->p->cf[ 'plugin' ][ $ext ] ) ) {	// Make sure we have a config for that acronym.

				return $result;
			}

			$plugin_data = $this->get_plugin_data( $ext, $read_cache = true );	// Get plugin data from the plugin readme.

			if ( empty( $plugin_data ) ) {	// Make sure we have some data to return.

				return $result;
			}

			$plugin_data->external = true;	// Let WordPress known that this is not a wordpress.org plugin.

			return $plugin_data;
		}

		/*
		 * This method receives only a partial options array, so re-create a full one.
		 *
		 * WordPress handles the actual saving of the options to the database table.
		 */
		public function settings_sanitation( $opts ) {

			$current_user_id = get_current_user_id();	// Always returns an integer.

			/*
			 * Clear any old notices for the current user before sanitation checks.
			 */
			$this->p->notice->clear();

			/*
			 * Make sure the input is an array.
			 */
			if ( ! is_array( $opts ) ) {

				$opts = array();
			}

			$defs = $this->p->opt->get_defaults();
			$opts = SucomUtil::restore_checkboxes( $opts );
			$opts = SucomUtil::array_merge_recursive_distinct( $this->p->options, $opts );
			$opts = $this->p->opt->sanitize( $opts, $defs, $network = false );
			$opts = apply_filters( 'wpsso_save_settings_options', $opts, $network = false, $upgrading = false );

			/*
			 * Maybe clear dismissed notices for enabled options that were previously disabled.
			 *
			 * See WpssoAdminHeadSuggestOptions->suggest_options_integration().
			 */
			foreach ( array(
				'add_link_rel_canonical'    => 'suggest-options-seo-add_link_rel_canonical',
				'add_link_rel_shortlink'    => 'suggest-options-seo-add_link_rel_shortlink',
				'add_meta_name_description' => 'suggest-options-seo-add_meta_name_description',
				'add_meta_name_robots'      => 'suggest-options-seo-add_meta_name_robots',
				'plugin_filter_content'     => 'notice-content-filters-disabled',
				'plugin_check_img_dims'     => 'notice-check-img-dims-disabled',
				'plugin_inherit_featured'   => 'notice-wc-inherit-featured-disabled',
			) as $opt_key => $notice_key ) {

				if ( ! empty( $opts[ $opt_key ] ) ) {	// Option is enabled.

					$this->p->notice->clear_dismissed( $notice_key, $current_user_id );
				}
			}

			/*
			 * Update the current options with any changes.
			 */
			$this->p->options = $opts;

			$this->settings_saved_notice();

			return $opts;
		}

		public function settings_saved_notice() {

			$cache_md5_pre  = 'wpsso_h_';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'transient' );
			$user_id        = get_current_user_id();
			$notice_msg     = '<strong>' . __( 'Plugin settings have been saved.', 'wpsso' ) . '</strong>' . ' ';
			$notice_key     = 'settings-saved';

			if ( $cache_exp_secs > 0 ) {

				$refresh_cache_url  = $this->p->util->get_admin_url( $menu_id = 'tools' );
				$refresh_cache_url  = add_query_arg( 'wpsso-action', 'refresh_cache', $refresh_cache_url );
				$refresh_cache_url  = wp_nonce_url( $refresh_cache_url, WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );
				$refresh_cache_link = '<a href="' . $refresh_cache_url . '">' . __( 'refresh the cache now', 'wpsso' ) . '</a>';
				$human_cache_exp    = human_time_diff( 0, $cache_exp_secs );

				$notice_msg .= sprintf( __( 'You can %1$s or let the cache refresh over the next %2$s.', 'wpsso' ), $refresh_cache_link, $human_cache_exp );
			}

			$this->p->notice->upd( $notice_msg, $user_id, $notice_key );
		}

		public function save_site_settings() {

			$default_page     = key( $this->p->cf[ '*' ][ 'lib' ][ 'sitesubmenu' ] );
			$default_page_url = $this->p->util->get_admin_url( $default_page );
			$setting_page     = SucomUtil::get_request_value( 'page', 'POST', $default_page );
			$setting_page_url = $this->p->util->get_admin_url( $setting_page );

			if ( empty( $_POST[ WPSSO_NONCE_NAME ] ) ) {	// WPSSO_NONCE_NAME is an md5() string.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'nonce token validation post field missing' );
				}

				wp_redirect( $default_page_url );	// Do not trust the 'page' request value.

				exit;

			} elseif ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

				$this->p->notice->err( __( 'Nonce token validation failed for network options (update ignored).', 'wpsso' ) );

				wp_redirect( $default_page_url );	// Do not trust the 'page' request value.

				exit;

			} elseif ( ! current_user_can( 'manage_network_options' ) ) {

				$this->p->notice->err( __( 'Insufficient privileges to modify network options.', 'wpsso' ) );

				wp_redirect( $setting_page_url );

				exit;
			}

			/*
			 * Clear any old notices for the current user before sanitation checks.
			 */
			$this->p->notice->clear();

			/*
			 * Make sure the input is an array.
			 */
			$opts = array();

			if ( isset( $_POST[ WPSSO_SITE_OPTIONS_NAME ] ) && is_array( $_POST[ WPSSO_SITE_OPTIONS_NAME ] ) ) {

				$opts = $_POST[ WPSSO_SITE_OPTIONS_NAME ];
			}

			$defs = $this->p->opt->get_site_defaults();
			$opts = SucomUtil::restore_checkboxes( $opts );
			$opts = SucomUtil::array_merge_recursive_distinct( $this->p->site_options, $opts );	// Complete the array with previous options.
			$opts = $this->p->opt->sanitize( $opts, $defs, $network = true );
			$opts = apply_filters( 'wpsso_save_settings_options', $opts, $network = true, $upgrading = false );

			/*
			 * Update the current options with any changes.
			 */
			$this->p->site_options = $opts;

			update_site_option( WPSSO_SITE_OPTIONS_NAME, $opts );

			$this->p->notice->upd( '<strong>' . __( 'Network plugin settings have been saved.', 'wpsso' ) . '</strong>' );

			$setting_page_url = add_query_arg( 'settings-updated', 'true', $setting_page_url );

			wp_redirect( $setting_page_url );

			exit;	// Stop after redirect.
		}

		public function load_settings_page() {

			$user_id      = get_current_user_id();
			$action_query = 'wpsso-action';
			$action_value = SucomUtil::get_request_value( $action_query ) ;		// POST or GET with sanitize_text_field().
			$action_value = SucomUtil::sanitize_hookname( $action_value );
			$nonce_value  = SucomUtil::get_request_value( WPSSO_NONCE_NAME ) ;	// POST or GET with sanitize_text_field().

			$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

			wp_enqueue_script( 'postbox' );

			if ( ! empty( $action_value ) ) {

				if ( empty( $nonce_value ) ) {	// WPSSO_NONCE_NAME is an md5() string.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'nonce token validation field missing' );
					}

				} elseif ( ! wp_verify_nonce( $nonce_value, WpssoAdmin::get_nonce_action() ) ) {

					$notice_msg = sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".', 'wpsso' ), 'admin', $action_value );

					$this->p->notice->err( $notice_msg, $user_id );

				} else {

					switch ( $action_value ) {

						case 'refresh_cache':

							$this->p->util->cache->schedule_refresh( $user_id );

							break;

						case 'clear_cache_files':

							$cleared_count = $this->p->util->cache->clear_cache_files();

							$notice_msg = sprintf( __( '%s cache files have been cleared.', 'wpsso' ), $cleared_count );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'clear_ignored_urls':

							$cleared_count = $this->p->util->cache->clear_ignored_urls();

							$notice_msg = sprintf( __( '%s failed URL connections have been cleared.', 'wpsso' ), $cleared_count );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'clear_db_transients':

							$cleared_count = $this->p->util->cache->clear_db_transients( $key_prefix = '', $clear_short = true );

							$notice_msg = sprintf( __( '%s database transients have been cleared.', 'wpsso' ), $cleared_count );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'clear_cron_jobs':

							$count_cron_jobs = number_format_i18n( $this->p->util->count_cron_jobs() );

							update_option( 'cron', '' );

							$notice_msg = sprintf( __( '%s WordPress cron jobs have been cleared.', 'wpsso' ), $count_cron_jobs );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'flush_rewrite_rules':

							/*
							 * Update .htaccess and the 'rewrite_rules' option.
							 *
							 * This is an expensive operation so it should only be used when necessary.
							 */
							flush_rewrite_rules( $hard = true );

							$notice_msg = __( 'The WordPress rewrite rules have been flushed.', 'wpsso' );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'add_persons':

							$this->p->user->schedule_add_person_role( $user_id );

							break;

						case 'remove_persons':

							$this->p->user->schedule_remove_person_role( $user_id );

							break;

						case 'reset_user_metabox_layout':

							WpssoUser::delete_metabox_prefs( $user_id );

							$user_obj = get_userdata( $user_id );

							$user_name = $user_obj->display_name;

							$notice_msg = sprintf( __( 'Metabox layout preferences for user ID #%d "%s" have been reset.', 'wpsso' ),
								$user_id, $user_name );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'reset_user_dismissed_notices':

							$this->p->notice->reset_dismissed( $user_id );

							$user_obj = get_userdata( $user_id );

							$user_name = $user_obj->display_name;

							$notice_msg = sprintf( __( 'Dismissed notices for user ID #%d "%s" have been reset.', 'wpsso' ),
								$user_id, $user_name );

							$this->p->notice->upd( $notice_msg, $user_id );

							break;

						case 'change_show_options':

							$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( 'show-opts' ) );

							$show_opts_key = isset( $_GET[ 'show-opts' ] ) ? SucomUtil::sanitize_key( $_GET[ 'show-opts' ] ) : null;

							if ( isset( $this->p->cf[ 'form' ][ 'show_options' ][ $show_opts_key ] ) ) {

								$show_name_transl  = _x( $this->p->cf[ 'form' ][ 'show_options' ][ $show_opts_key ], 'option value', 'wpsso' );

								WpssoUser::save_pref( array( 'show_opts' => $show_opts_key ) );

								$notice_msg = '<strong>' . __( 'Option preference has been saved.', 'wpsso' ) . '</strong>';

								$notice_msg .= sprintf( __( 'Now viewing "%s" by default.', 'wpsso' ), $show_name_transl );

								$this->p->notice->upd( $notice_msg, $user_id );
							}

							break;

						case 'reload_default_image_sizes':

							$defs = $this->p->opt->get_defaults();

							$img_defs = SucomUtil::preg_grep_keys( '/_img_(width|height|crop|crop_x|crop_y)$/', $defs );

							$this->p->options = SucomUtil::array_merge_recursive_distinct( $this->p->options, $img_defs );

							$this->p->opt->save_options( WPSSO_OPTIONS_NAME, $this->p->options, $network = false );

							$this->p->notice->upd( __( 'Image size settings have been reloaded with their default values and saved.', 'wpsso' ) );

							break;

						case 'export_plugin_settings_json':

							$this->export_plugin_settings_json();

							break;

						case 'import_plugin_settings_json':

							$this->import_plugin_settings_json();

							break;

						default:

							do_action( 'wpsso_load_settings_page_' . $action_value, $this->pagehook,
								$this->menu_id, $this->menu_name, $this->menu_lib );

							break;
					}
				}
			}

			$menu_ext = empty( $this->menu_ext ) ? $this->p->id : $this->menu_ext;

			$this->get_form_object( $menu_ext );	// Return and maybe set/reset the WpssoAdmin->form value.

			$this->add_settings_page_callbacks();

			if ( method_exists( $this, 'add_meta_boxes' ) ) {	// Deprecated on 2023/11/05.

				$this->add_meta_boxes();
			}

			$this->add_settings_page_metaboxes();

			$this->add_settings_page_footer();
		}

		/*
		 * Add actions and filters for this settings page.
		 *
		 * See WpssoAdmin->load_settings_page().
		 */
		protected function add_settings_page_callbacks() {
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

			/*
			 * Defined in the child construct method of this settings page.
			 */
			if ( empty( $this->menu_metaboxes ) ) {

				return;
			}

			foreach ( $this->menu_metaboxes as $metabox_id => $metabox_title ) {

				$metabox_screen  = $this->pagehook;
				$metabox_context = 'normal';
				$metabox_prio    = 'default';

				$callback_args[ 'page_id' ]       = $this->menu_id;
				$callback_args[ 'metabox_id' ]    = $metabox_id;
				$callback_args[ 'metabox_title' ] = $metabox_title;
				$callback_args[ 'network' ]       = 'sitesubmenu' === $this->menu_lib ? true : false;

				$method_name = method_exists( $this, 'show_metabox_' . $metabox_id ) ?
					'show_metabox_' . $metabox_id : 'show_metabox_table';

				add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title, array( $this, $method_name ),
					$metabox_screen, $metabox_context, $metabox_prio, $callback_args );

				/*
				 * Add a class to set a minimum width for the network postboxes.
				 */
				if ( 'sitesubmenu' === $this->menu_lib ) {

					add_filter( 'postbox_classes_' . $this->pagehook . '_' . $this->pagehook . '_' . $metabox_id,
						array( $this, 'add_class_postbox_network' ) );
				}
			}
		}

		/*
		 * Include add-on name and version in settings page footer.
		 */
		protected function add_settings_page_footer() {

			add_filter( 'admin_footer_text', array( $this, 'admin_footer_ext' ) );

			add_filter( 'update_footer', array( $this, 'admin_footer_host' ) );
		}

		protected function maybe_show_language_notice() {

			$current_locale = SucomUtilWP::get_locale();
			$default_locale = SucomUtilWP::get_locale( 'default' );

			if ( $current_locale && $default_locale && $current_locale !== $default_locale ) {

				$notice_msg = sprintf( __( 'Your current language selection [%1$s] is different from the default site language [%2$s].', 'wpsso' ),
					$current_locale, $default_locale ) . ' ';

				$notice_msg .= sprintf( __( 'Localized option values [%1$s] will be used for webpages and content in that language.', 'wpsso' ),
					$current_locale );

				$notice_key = $this->menu_id . '-language-notice-' . $current_locale . '-' . $default_locale;

				$this->p->notice->inf( $notice_msg, null, $notice_key, $dismiss_time = true );
			}
		}

		public function show_settings_page() {

			if ( ! $this->is_settings() ) {	// Default check is for $this->menu_id.

				settings_errors( WPSSO_OPTIONS_NAME );
			}

			if ( ! $this->is_plugin_found() ) {	// Just in case.

				return;
			}

			$side_col_boxes = $this->get_side_col_boxes();
			$dashicon_html  = $this->get_menu_dashicon_html( $this->menu_id );

			/*
			 * Settings page wrapper.
			 */
			echo '<div id="' . $this->pagehook . '" class="wrap">' . "\n";

			/*
			 * Settings page header.
			 */
			echo '<div id="wpsso-setting-page-header">' . "\n";
			echo '<h1>' . $dashicon_html . ' '. $this->menu_name . '</h1>' . "\n";
			echo '</div><!-- #wpsso-setting-page-header -->' . "\n";

			/*
			 * Settings page content.
			 */
			echo '<div id="wpsso-setting-page-content" class="' . ( empty( $side_col_boxes ) ? 'no' : 'has' ) . '-side-column">' . "\n";

			/*
			 * Metaboxes.
			 */
			echo '<div id="poststuff" class="metabox-holder no-right-sidebar">' . "\n";
			echo '<div id="post-body" class="no-sidebar">' . "\n";
			echo '<div id="post-body-content" class="no-sidebar-content">' . "\n";

			$this->show_post_body_settings_form();

			echo '</div><!-- #post-body-content -->' . "\n";
			echo '</div><!-- #post-body -->' . "\n";
			echo '</div><!-- #poststuff -->' . "\n";

			/*
			 * Information boxes.
			 */
			if ( ! empty( $side_col_boxes ) ) {

				echo '<div id="side-column">' . "\n";

				foreach ( $side_col_boxes as $box ) {

					echo '<table class="sucom-settings wpsso side-box">' . "\n";
					echo '<tr><td>' . "\n";
					echo $box;
					echo '</td></tr>' . "\n";
					echo '</table><!-- .side-box -->' . "\n";
				}

				echo '</div><!-- #side-column -->' . "\n";
			}

			echo '</div><!-- #wpsso-setting-page-content -->' . "\n";
			echo '</div><!-- #' . $this->pagehook .' -->' . "\n";

			/*
			 * The type="text/javascript" attribute is unnecessary for JavaScript resources and creates warnings in the W3C validator.
			 */
			?><script>

				jQuery( function(){

					jQuery( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );	// Postboxes that should be closed.

					postboxes.add_postbox_toggles( '<?php echo $this->pagehook; ?>' );	// Postboxes setup.
				});

			</script><?php
		}

		public function profile_updated_redirect( $url, $status ) {

			if ( false !== strpos( $url, 'updated=' ) && strpos( $url, 'wp_http_referer=' ) ) {

				/*
				 * Match WordPress behavior (users page for admins, profile page for everyone else).
				 */
				$menu_lib      = current_user_can( 'list_users' ) ? 'users' : 'profile';
				$parent_slug   = $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ];
				$referer_match = '/' . $parent_slug . '?page=wpsso-';

				/*
				 * Parses string as if it were the query string passed via a URL and sets variables in the current
				 * scope (or in the array if result is provided).
				 *
				 * See https://www.php.net/manual/en/function.parse-str.php.
				 */
				parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $parts );

				if ( strpos( $parts[ 'wp_http_referer' ], $referer_match ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$this->p->notice->upd( __( 'Profile updated.' ) );

					$url = add_query_arg( 'updated', true, $parts[ 'wp_http_referer' ] );
				}
			}

			return $url;
		}

		protected function show_post_body_settings_form() {

			$menu_hookname = SucomUtil::sanitize_hookname( $this->menu_id );
			$form_css_id   = 'wpsso_settings_form_' . $menu_hookname;

			switch ( $this->menu_lib ) {

				case 'profile':

					$user_id     = get_current_user_id();
					$user_obj    = get_user_to_edit( $user_id );
					$admin_color = get_user_option( 'admin_color', $user_id );	// Note that $user_id is the second argument.

					if ( empty( $admin_color ) ) {

						$admin_color = 'fresh';
					}

					/*
					 * Match WordPress behavior (users page for admins, profile page for everyone else).
					 */
					$referer_admin_url = current_user_can( 'list_users' ) ?
						$this->p->util->get_admin_url( $this->menu_id, null, 'users' ) :
						$this->p->util->get_admin_url( $this->menu_id, null, $this->menu_lib );

					echo '<form name="wpsso" id="' . $form_css_id . '" action="user-edit.php" method="post">' . "\n";
					echo '<input type="hidden" name="wp_http_referer" value="' . $referer_admin_url . '" />' . "\n";
					echo '<input type="hidden" name="action" value="update" />' . "\n";
					echo '<input type="hidden" name="user_id" value="' . $user_id . '" />' . "\n";
					echo '<input type="hidden" name="nickname" value="' . $user_obj->nickname . '" />' . "\n";
					echo '<input type="hidden" name="email" value="' . $user_obj->user_email . '" />' . "\n";
					echo '<input type="hidden" name="admin_color" value="' . $admin_color . '" />' . "\n";
					echo '<input type="hidden" name="rich_editing" value="' . $user_obj->rich_editing . '" />' . "\n";
					echo '<input type="hidden" name="comment_shortcuts" value="' . $user_obj->comment_shortcuts . '" />' . "\n";
					echo '<input type="hidden" name="admin_bar_front" value="' . _get_admin_bar_pref( 'front', $user_id ) . '" />' . "\n";

					wp_nonce_field( 'update-user_' . $user_id );

					break;

				case 'dashboard':
				case 'plugins':
				case 'settings':
				case 'submenu':
				case 'tools':

					echo '<form name="wpsso" id="' . $form_css_id . '" action="options.php" method="post">' . "\n";

					settings_fields( 'wpsso_settings' );

					break;

				case 'sitesubmenu':

					echo '<form name="wpsso" id="' . $form_css_id . '" action="edit.php?action=' . WPSSO_SITE_OPTIONS_NAME . '" method="post">' . "\n";
					echo '<input type="hidden" name="page" value="' . $this->menu_id . '" />' . "\n";

					break;

				default:

					return;
			}

			echo "\n" . '<!-- wpsso nonce fields -->' . "\n";

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			echo "\n";

			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

			echo "\n";

			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			echo "\n";

			do_meta_boxes( $this->pagehook, $context = 'normal', $object = null );

			/*
			 * Hooked by WpssoSubmenuDashboard->action_form_content_metaboxes_dashboard().
			 */
			do_action( 'wpsso_form_content_metaboxes_' . $menu_hookname, $this->pagehook );

			echo $this->get_form_buttons();

			echo '</form>', "\n";
		}

		protected function get_side_col_boxes() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			$local_cache = array();
			$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
			$icon_px     = 128;

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Exclude add-ons that are not active.

					continue;

				} elseif ( empty( $info[ 'url' ][ 'purchase' ] ) ) {

					continue;

				} elseif ( $pkg_info[ $ext ][ 'pp' ] ) {

					continue;
				}

				$box = '<div class="side-box-header">' . "\n";
				// translators: %s is the Premium add-on short name.
				$box .= '<h2>' . sprintf( __( 'Upgrade to %s', 'wpsso' ), $pkg_info[ $ext ][ 'short_pro' ] ) . '</h2>' . "\n";
				$box .= '</div><!-- .side-box-header -->' . "\n";

				$box .= '<div class="side-box-icon">' . "\n";
				$box .= $this->get_ext_img_icon( $ext, $icon_px ) . "\n";
				$box .= '</div><!-- .side-box-icon -->' . "\n";

				$box .= '<div class="side-box-content has-buttons">' . "\n";
				$box .= $this->p->msgs->get( 'column-purchase-' . $ext, $info ) . "\n";
				$box .= '</div><!-- .side-box-content -->' . "\n";

				$box .= '<div class="side-box-buttons">' . "\n";
				$box .= $this->form->get_button( sprintf( _x( 'Get %s', 'submit button', 'wpsso' ), $pkg_info[ $ext ][ 'short_pro' ] ),
					'button-secondary', 'column-purchase', $info[ 'url' ][ 'purchase' ], true ) . "\n";
				$box .= '</div><!-- .side-box-buttons -->' . "\n";

				$local_cache[] = $box;
			}

			return $local_cache;
		}

		/*
		 * Called by WpssoAdmin->show_post_body_settings_form().
		 * Called by WpssoSubmenuTools->show_post_body_settings_form().
		 */
		protected function get_form_buttons() {

			$this->add_form_buttons_submit( $form_button_rows );
			$this->add_form_buttons_change_show_options( $form_button_rows );
			$this->add_form_buttons( $form_button_rows );

			$filter_name = 'wpsso_form_button_rows';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$form_button_rows = apply_filters( $filter_name, $form_button_rows, $this->menu_id );

			$buttons_html   = '';
			$button_row_num = 1;

			foreach ( $form_button_rows as $key => $buttons_row ) {

				if ( $button_row_num >= 3 ) {

					$css_class = 'button-secondary';			// Third+ row.

				} elseif ( $button_row_num >= 2 ) {

					$css_class = 'button-secondary button-alt';		// Second row.

				} else $css_class = 'button-secondary button-highlight';	// First row.

				$buttons_html .= '<div class="submit-buttons">';

				foreach ( $buttons_row as $action_value => $mixed ) {

					if ( empty( $action_value ) || empty( $mixed ) ) {	// Just in case.

						continue;
					}

					if ( is_string( $mixed ) ) {

						if ( 'submit' === $action_value ) {

							$buttons_html .= $this->form->get_submit( $mixed, 'button-primary' );

						} else {

							$action_url   = $this->p->util->get_admin_url( '?wpsso-action=' . $action_value );
							$button_url   = wp_nonce_url( $action_url, WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );
							$buttons_html .= $this->form->get_button( $mixed, $css_class, '', $button_url );
						}

					} elseif ( is_array( $mixed ) ) {

						if ( ! empty( $mixed[ 'html' ] ) ) {

							$buttons_html .= $mixed[ 'html' ];
						}
					}
				}

				$buttons_html .= '</div>';

				$button_row_num++;
			}

			return $buttons_html;
		}

		protected function add_form_buttons_submit( &$form_button_rows ) {

			if ( $this->menu_lib === 'profile' ) {

				$submit_label_transl = _x( 'Save Profile Settings', 'submit button', 'wpsso' );

			} else $submit_label_transl = _x( 'Save Plugin Settings', 'submit button', 'wpsso' );

			$form_button_rows = array(
				array(
					'submit' => $submit_label_transl,
				),
			);
		}

		protected function add_form_buttons_change_show_options( &$form_button_rows ) {

			$change_show_next_key     = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf[ 'form' ][ 'show_options' ] );
			$change_show_name_transl  = _x( $this->p->cf[ 'form' ][ 'show_options' ][ $change_show_next_key ], 'option value', 'wpsso' );
			$change_show_label_transl = sprintf( _x( 'Change to "%s" View', 'submit button', 'wpsso' ), $change_show_name_transl );

			$form_button_rows[ 0 ][ 'change_show_options&show-opts=' . $change_show_next_key ] = $change_show_label_transl;
		}

		protected function add_form_buttons( &$form_button_rows ) {
		}

		/*
		 * Callback method must be public for add_meta_box() hook.
		 *
		 * See WpssoAdmin->add_settings_page_metaboxes().
		 */
		public function show_metabox_table( $obj, $mb ) {

			$args          = isset( $mb[ 'args' ] ) ? $mb[ 'args' ] : array();
			$page_id       = isset( $args[ 'page_id' ] ) ? $args[ 'page_id' ] : '';
			$metabox_id    = isset( $args[ 'metabox_id' ] ) ? $args[ 'metabox_id' ] : '';
			$filter_prefix = 'wpsso_mb_' . $page_id . '_' . $metabox_id;
			$filter_name   = SucomUtil::sanitize_hookname( $filter_prefix . '_rows' );
			$table_rows    = $this->get_table_rows( $page_id, $metabox_id, $tab_key = '', $args );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$table_rows = apply_filters( $filter_name, $table_rows, $this->form, $args );

			$this->p->util->metabox->do_table( $table_rows, 'metabox-' . $page_id . '-' . $metabox_id );
		}

		/*
		 * Since WPSSO Core v17.0.0.
		 */
		protected function show_metabox_tabbed( $obj, $mb, array $tabs ) {

			$args          = isset( $mb[ 'args' ] ) ? $mb[ 'args' ] : array();
			$page_id       = isset( $args[ 'page_id' ] ) ? $args[ 'page_id' ] : '';
			$metabox_id    = isset( $args[ 'metabox_id' ] ) ? $args[ 'metabox_id' ] : '';
			$filter_prefix = 'wpsso_mb_' . $page_id . '_' . $metabox_id;
			$filter_name   = SucomUtil::sanitize_hookname( $filter_prefix . '_tabs' );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			$tabs = apply_filters( $filter_name, $tabs );

			if ( empty( $tabs ) ) {

				return $this->show_metabox_table( $obj, $mb );
			}

			$table_rows = array();

			foreach ( $tabs as $tab_key => $tab_title ) {

				$table_rows[ $tab_key ] = $this->get_table_rows( $page_id, $metabox_id, $tab_key, $args );

				$filter_name = SucomUtil::sanitize_hookname( $filter_prefix . '_' . $tab_key . '_rows' );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
				}

				$table_rows[ $tab_key ] = apply_filters( $filter_name, $table_rows[ $tab_key ], $this->form, $args );
			}

			$this->p->util->metabox->do_tabbed( $metabox_id, $tabs, $table_rows );
		}

		/*
		 * This method is extended by each submenu page with additional arguments:
		 *
		 * get_table_rows( $page_id, $metabox_id, $tab_key = '', $args = array() );
		 */
		protected function get_table_rows( $page_id, $metabox_id ) {

			return array();
		}

		public function get_table_rows_cols( array $table_cells, $row_cols = 2 ) {

			sort( $table_cells );

			$per_col = ceil( count( $table_cells ) / $row_cols );

			$table_rows = array();

			foreach ( $table_cells as $num => $cell ) {

				if ( empty( $table_rows[ $num % $per_col ] ) ) {	// Initialize the array element.

					$table_rows[ $num % $per_col ] = '';
				}

				$table_rows[ $num % $per_col ] .= $cell;	// Create the html for each row.
			}

			return $table_rows;
		}

		/*
		 * Always call as WpssoAdmin::get_nonce_action() to have a reliable __METHOD__ value.
		 */
		public static function get_nonce_action() {

			$salt = __FILE__ . __LINE__ . __METHOD__;

			return md5( $salt );
		}

		private function is_plugin_found() {
			
			if ( ! SucomPlugin::is_plugin_active( 'wpsso/wpsso.php' ) ) {

				$pkg_info   = $this->p->util->get_pkg_info();	// Uses a local cache.
				$notice_msg = sprintf( __( 'The %s plugin is not active and/or not found.', 'wpsso' ), $pkg_info[ 'wpsso' ][ 'name' ] );
				$notice_key = 'wpsso-not-found';

				$this->p->notice->err( $notice_msg, $user_id = null, $notice_key );

				return false;
			}

			return true;
		}

		private function is_settings( $menu_id = false ) {

			return $this->is_lib( 'settings', $menu_id );
		}

		private function is_lib( $lib_name, $menu_id = false ) {

			if ( false === $menu_id ) {

				$menu_id = $this->menu_id;
			}

			return isset( $this->p->cf[ '*' ][ 'lib' ][ $lib_name ][ $menu_id ] ) ? true : false;
		}

		public function add_admin_bar_menu_notices( $wp_admin_bar ) {

			$menu_icon  = '<span class="ab-icon" id="wpsso-toolbar-notices-icon"></span>';
			$menu_count = '<span class="ab-label" id="wpsso-toolbar-notices-count">0</span>';
			$menu_text  = sprintf( __( 'Fetching %s notifications...', 'wpsso' ), $this->get_menu_title() );

			$wp_admin_bar->add_node( array(
				'id'     => 'wpsso-toolbar-notices',
				'title'  => $menu_icon . $menu_count,
				'parent' => false,
				'href'   => false,
				'group'  => false,
				'meta'   => array(),
			) );

			$wp_admin_bar->add_node( array(
				'id'     => 'wpsso-toolbar-notices-container',
				'title'  => $menu_text,
				'parent' => 'wpsso-toolbar-notices',
				'href'   => false,
				'group'  => false,
				'meta'   => array(),
			) );
		}

		public function check_admin_bar_menu_notices( $wp_admin_bar ) {

			if ( ! $wp_admin_bar->get_node( 'wpsso-toolbar-notices' ) ) {

				$this->toolbar_notice_types = array();
			}
		}

		/*
		 * $toolbar_notice_types may be false if is_admin_bar_showing() is false.
		 */
		public function filter_toolbar_notice_types( $toolbar_notice_types ) {

			return is_array( $toolbar_notice_types ) ? $this->toolbar_notice_types : $toolbar_notice_types;
		}

		public function admin_footer_ext( $footer_html ) {

			if ( SucomUtil::get_const( 'WPSSO_ADMIN_FOOTER_DISABLE' ) ) {

				return '';
			}

			$pkg_info    = $this->p->util->get_pkg_info();	// Uses a local cache.
			$footer_html = '<div class="admin-footer-ext">';

			if ( isset( $pkg_info[ $this->menu_ext ][ 'name_pkg' ] ) ) {

				$footer_html .= $pkg_info[ $this->menu_ext ][ 'name_pkg' ] . '<br/>';
			}

			if ( isset( $pkg_info[ $this->menu_ext ][ 'gen' ] ) ) {

				$footer_html .= $pkg_info[ $this->menu_ext ][ 'gen' ] . '<br/>';
			}

			$footer_html .= '</div>';

			return $footer_html;
		}

		public function admin_footer_host( $footer_html ) {

			if ( SucomUtil::get_const( 'WPSSO_ADMIN_FOOTER_DISABLE' ) ) {

				return '';
			}

			global $wp_version;

			$home_url  = SucomUtilWP::raw_get_home_url();
			$home_path = preg_replace( '/^[a-z]+:\/\//i', '', $home_url );

			$footer_html = '<div class="admin-footer-host">';
			$footer_html .= $home_path . '<br/>';
			$footer_html .= 'WordPress ' . $wp_version . '<br/>';
			$footer_html .= 'PHP ' . phpversion() . '<br/>';
			$footer_html .= '</div>';

			return $footer_html;
		}

		/*
		 * WordPress sorts the active plugins array before updating the 'active_plugins' option. The default PHP sort order
		 * loads WPSSO add-ons before the WPSSO Core plugin. This filter re-sorts (if necessary) the active plugins array
		 * to load WPSSO Core before its add-ons. This allows WPSSO Core to load the latest WpssoAbstractAddOn and
		 * SucomAbstractAddon classes (for example) before any (possibly older) add-on does.
		 *
		 * When activating a plugin, the activate_plugin() function executes the following:
		 *
		 *	$current   = get_option( 'active_plugins', array() );	// Get current plugins array.
		 *	$current[] = $plugin;					// Add the new plugin.
		 *	sort( $current );					// Sort the plugin array.
		 *	update_option( 'active_plugins', $current );		// Save the plugin array.
		 *
		 * See the activate_plugin() function in wordpress/wp-admin/includes/plugin.php for additional context.
		 */
		public function pre_update_active_plugins( $current, $old_value, $option = 'active_plugins' ) {

			usort( $current, array( __CLASS__, 'sort_active_plugins' ) );

			return $current;
		}

		/*
		 * Since WPSSO Core v13.4.1.
		 */
		public static function get_option_unit_comment( $opt_key ) {

			if ( $unit_text = WpssoUtilUnits::get_mixed_label( $opt_key ) ) {

				return ' ' . sprintf( _x( 'in %s', 'option comment', 'wpsso' ), $unit_text );
			}

			return '';
		}

		public static function get_option_site_use( $name, $form, $network = false, $is_enabled = false ) {

			$html = '';

			if ( $network ) {

				$wpsso      =& Wpsso::get_instance();
				$pkg_info   = $wpsso->util->get_pkg_info();	// Uses a local cache.
				$is_enabled = $is_enabled || $pkg_info[ 'wpsso' ][ 'pp' ] ? true : false;

				$html .= $form->get_th_html( _x( 'Site Use', 'option label (very short)', 'wpsso' ), 'site-use' );
				$html .= $is_enabled ? '<td class="site-use">' : '<td class="blank site-use">';
				$html .= $is_enabled ? $form->get_select( $name . ':use', WpssoConfig::$cf[ 'form' ][ 'site_option_use' ], $css_class = 'site-use' ) :
					$form->get_no_select( $name . ':use', WpssoConfig::$cf[ 'form' ][ 'site_option_use' ], $css_class = 'site-use' );
				$html .= '</td>';
			}

			return $html;
		}

		public function plugin_complete_actions( $actions ) {

			if ( ! empty( $this->pageref_url ) && ! empty( $this->pageref_title ) ) {

				foreach ( $actions as $action => &$html ) {

					switch ( $action ) {

						case 'plugins_page':

							$html = '<a href="' . $this->pageref_url . '" target="_parent">' .
								sprintf( __( 'Return to %s', 'wpsso' ), $this->pageref_title ) . '</a>';

							break;

						default:

							if ( preg_match( '/^(.*href=["\'])([^"\']+)(["\'].*)$/', $html, $matches ) ) {

								$url = add_query_arg( array(
									'wpsso_pageref_url'   => urlencode( $this->pageref_url ),
									'wpsso_pageref_title' => urlencode( $this->pageref_title ),
								), $matches[ 2 ] );

								$html = $matches[ 1 ] . $url . $matches[ 3 ];
							}

							break;
					}
				}
			}

			return $actions;
		}

		public function plugin_complete_redirect( $url ) {

			if ( strpos( $url, '?activate=true' ) ) {

				if ( ! empty( $this->pageref_url ) ) {

					// translators: Please ignore - translation uses a different text domain.
					$this->p->notice->upd( __( 'Plugin <strong>activated</strong>.' ) );

					$url = $this->pageref_url;
				}
			}

			return $url;
		}

		public function get_check_for_updates_link( $get_notice = true ) {

			$check_url  = '';
			$notice_msg = '';

			if ( class_exists( 'WpssoUm' ) ) {

				$pkg_info  = $this->p->util->get_pkg_info();	// Uses a local cache.
				$check_url = $this->p->util->get_admin_url( 'um-general?wpsso-action=check_for_updates' );
				$check_url = wp_nonce_url( $check_url, WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

				// translators: %1$s is the URL, %2$s is the short plugin name.
				$notice_transl = __( 'You may <a href="%1$s">refresh the update information for %2$s and its add-ons</a> to check if newer versions are available.', 'wpsso' );

				$notice_msg = sprintf( $notice_transl, $check_url, $pkg_info[ 'wpsso' ][ 'short_pkg' ] );

			} elseif ( empty( $_GET[ 'force-check' ] ) ) {

				$check_url = self_admin_url( 'update-core.php?force-check=1' );

				// translators: %1$s is the URL.
				$notice_transl = __( 'You may <a href="%1$s">refresh the update information for WordPress (plugins, themes and translations)</a> to check if newer versions are available.', 'wpsso' );

				$notice_msg = sprintf( $notice_transl, $check_url );
			}

			return $get_notice ? $notice_msg : $check_url;
		}

		/*
		 * Called from the network settings pages.
		 *
		 * Add a class to set a minimum width for the network postboxes.
		 */
		public function add_class_postbox_network( $classes ) {

			$classes[] = 'postbox-network';

			return $classes;
		}

		public function add_class_postbox_menu_id( $classes ) {

			global $wp_current_filter;

			$filter_name  = end( $wp_current_filter );
			$postbox_name = preg_replace( '/^.*-(' . $this->menu_id . '_.*)$/u', '$1', $filter_name );

			$classes[] = 'postbox-' . $this->menu_id;
			$classes[] = 'postbox-' . $postbox_name;

			return $classes;
		}

		public function export_plugin_settings_json() {

			$date_slug    = date( 'YmdHiT' );
			$home_slug    = SucomUtil::sanitize_hookname( preg_replace( '/^.*\/\//', '', get_home_url() ) );
			$mime_type_gz = 'application/x-gzip';
			$file_name_gz = WpssoConfig::get_version( $add_slug = true ) . '-' . $home_slug . '-' . $date_slug . '.json.gz';
			$opts_encoded = wp_json_encode( $this->p->options );
			$gzdata       = gzencode( $opts_encoded, 9, FORCE_GZIP );
			$filesize     = strlen( $gzdata );
			$disposition  = 'attachment';
			$chunksize    = 1024 * 32;	// 32kb per fread().

			session_write_close();

			ignore_user_abort();

			set_time_limit( 300 );	// 5 minutes.

			ini_set( 'zlib.output_compression', 0 );

			if ( function_exists( 'apache_setenv' ) ) {

				apache_setenv( 'no-gzip', 1 );
			}

			ob_implicit_flush( true );
			ob_end_flush();

			/*
			 * Remove all dots, except last one, for MSIE clients.
			 */
			if ( strstr( $_SERVER[ 'HTTP_USER_AGENT' ], 'MSIE' ) ) {

				$file_name_gz = preg_replace( '/\./', '%2e', $file_name_gz, substr_count( $file_name_gz, '.' ) - 1 );
			}

			if ( isset( $_SERVER[ 'HTTPS' ] ) ) {

				$gmt_date = gmdate( 'D, d M Y H:i:s' ) . ' GMT';

				header( 'Pragma: ' );
				header( 'Cache-Control: ' );
				header( 'Expires: ' . $gmt_date );
				header( 'Last-Modified: ' . $gmt_date );
				header( 'Cache-Control: no-store, no-cache, must-revalidate' );
				header( 'Cache-Control: post-check=0, pre-check=0', false );

			} elseif ( $disposition == 'attachment' ) {

				header( 'Cache-control: private' );

			} else {

				header( 'Cache-Control: no-cache, must-revalidate' );
				header( 'Pragma: no-cache' );
			}

			header( 'Content-Type: application/' . $mime_type_gz );
			header( 'Content-Disposition: ' . $disposition . '; filename="' . $file_name_gz . '"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Content-Length: ' . $filesize );

			echo $gzdata;

			flush();

			sleep( 1 );

			exit();
		}

		public function import_plugin_settings_json() {

			$mime_type_gz  = 'application/x-gzip';
			$dot_file_ext  = '.json.gz';
			$max_file_size = 100000;	// 100K.

			if ( ! isset( $_FILES[ 'file' ][ 'error' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'incomplete post method upload' );
				}

				return false;

			} elseif ( $_FILES[ 'file' ][ 'error' ] === UPLOAD_ERR_NO_FILE ) {

				$this->p->notice->err( sprintf( __( 'Please select a %1$s settings file to import.', 'wpsso' ), $dot_file_ext ) );

				return false;

			} elseif ( $_FILES[ 'file' ][ 'type' ] !== 'application/x-gzip' ) {

				$this->p->notice->err( sprintf( __( 'The %1$s settings file to import must be an "%2$s" mime type.', 'wpsso' ),
					$dot_file_ext, $mime_type_gz ) );

				return false;

			} elseif ( $_FILES[ 'file' ][ 'size' ] > $max_file_size ) {	// Just in case.

				$this->p->notice->err( sprintf( __( 'The %1$s settings file is larger than the maximum of %2$d bytes allowed.', 'wpsso' ),
					$dot_file_ext, $max_file_size ) );

				return false;
			}

			$gzdata = file_get_contents( $_FILES[ 'file' ][ 'tmp_name' ] );

			@unlink( $_FILES[ 'file' ][ 'tmp_name' ] );

			$opts_encoded = gzdecode( $gzdata );

			if ( empty( $opts_encoded ) ) {	// false or empty array.

				$this->p->notice->err( sprintf( __( 'The %1$s settings file is appears to be empty or corrupted.', 'wpsso' ), $dot_file_ext ) );

				return false;
			}

			$opts = json_decode( $opts_encoded, $assoc = true );

			if ( empty( $opts ) || ! is_array( $opts ) ) {	// false or empty array.

				$this->p->notice->err( sprintf( __( 'The %1$s settings file could not be decoded into a settings array.', 'wpsso' ), $dot_file_ext ) );

				return false;
			}

			$this->p->options = $this->p->opt->check_options( WPSSO_OPTIONS_NAME, $opts );

			$this->p->opt->save_options( WPSSO_OPTIONS_NAME, $opts, $network = false );

			$this->p->notice->upd( __( 'Import of plugin and add-on settings is complete.', 'wpsso' ) );

			return true;
		}

		public function get_menu_title() {

			$menu_title = _x( $this->p->cf[ 'menu' ][ 'title' ], 'menu title', 'wpsso' );

			$filter_name = 'wpsso_menu_title';

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			return apply_filters( 'wpsso_menu_title', $menu_title );
		}

		public function get_menu_dashicon_html( $menu_id, $css_class = '' ) {

			$dashicon = $this->get_menu_dashicon( $menu_id );

			return '<div class="' . trim( $css_class . ' dashicons-before dashicons-' . $dashicon ) . '"></div>';
		}

		public function get_menu_dashicon( $menu_id ) {

			$dashicon = 'admin-generic';

			if ( ! empty( $this->p->cf[ 'menu' ][ 'dashicons' ][ $menu_id ] ) ) {

				$dashicon = $this->p->cf[ 'menu' ][ 'dashicons' ][ $menu_id ];

			} elseif ( ! empty( $this->p->cf[ 'menu' ][ 'dashicons' ][ '*' ] ) ) {

				$dashicon = $this->p->cf[ 'menu' ][ 'dashicons' ][ '*' ];
			}

			return $dashicon;
		}

		public function get_submenu_title( $info, $menu_lib, $menu_id ) {

			$menu_title = '';

			if ( ! empty( $info[ 'lib' ][ $menu_lib ][ $menu_id ] ) ) {	// Just in case.

				$menu_title = $info[ 'lib' ][ $menu_lib ][ $menu_id ];
			}

			if ( ! empty( $info[ 'text_domain' ] ) ) {

				$menu_title = _x( $menu_title, 'lib file description', $info[ 'text_domain' ] );
			}

			/*
			 * Example $filter_name = 'wpsso_menu_your_sso_title'
			 */
			$filter_name = SucomUtil::sanitize_hookname( 'wpsso_menu_' . $menu_id . '_title' );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'applying filters \'' . $filter_name . '\'' );
			}

			return apply_filters( $filter_name, $menu_title );
		}

		/*
		 * $menu_lib = 'dashboard', 'plugins', 'profile', 'settings', 'submenu', 'sitesubmenu', 'tools', or 'users'
		 */
		public function get_submenu_args( $menu_lib = 'submenu' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $menu_lib ] ) ) {

				return $local_cache[ $menu_lib ];
			}

			$local_cache[ $menu_lib ] = array();

			$top_first_id = false;
			$top_last_id  = false;
			$ext_first_id = false;
			$ext_last_id  = false;

			$sorted_menu   = array();
			$unsorted_menu = array();

			if ( 'profile' === $menu_lib && current_user_can( 'list_users' ) ) {	// Match WordPress behavior.

				$parent_slug = $this->p->cf[ 'wp' ][ 'admin' ][ 'users' ][ 'page' ];

			} elseif ( 'submenu' === $menu_lib || 'sitesubmenu' === $menu_lib ) {	// Plugin submenu.

				$parent_slug = 'wpsso-' . $this->menu_id;

			} else {

				$parent_slug = $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ];
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $info[ 'lib' ][ $menu_lib ] ) ) {	// Not all add-ons have submenus.

					continue;
				}

				foreach ( $info[ 'lib' ][ $menu_lib ] as $menu_id => $menu_name ) {

					$menu_title  = $this->get_submenu_title( $info, $menu_lib, $menu_id );

					/*
					 * Leave WPSSO Core submenu items in their original order.
					 */
					if ( 'wpsso' === $ext ) {

						$unsorted_menu[] = array( $parent_slug, $menu_id, $menu_title, $menu_lib, $ext );

						if ( false === $top_first_id ) $top_first_id = $menu_id;

						$top_last_id = $menu_id;

					/*
					 * Sort add-on submenu items.
					 */
					} else {

						$sorted_menu[ $menu_title . '-' . $menu_id ] = array( $parent_slug, $menu_id, $menu_title, $menu_lib, $ext );

						if ( false === $ext_first_id ) $ext_first_id = $menu_id;

						$ext_last_id = $menu_id;
					}
				}
			}

			SucomUtil::natksort( $sorted_menu );

			foreach ( array_merge( $unsorted_menu, $sorted_menu ) as $sort_key => $args ) {

				$menu_id = $args[ 1 ];

				if ( 'submenu' === $menu_lib || 'sitesubmenu' === $menu_lib ) {

					if ( $menu_id === $top_first_id ) {

						$args[ 5 ] = 'top-first-submenu-page';

					} elseif ( $menu_id === $top_last_id ) {

						$args[ 5 ] = 'top-last-submenu-page' . ( empty( $ext_first_id ) ? ' no-add-ons' : ' with-add-ons' );

					} elseif ( $menu_id === $ext_first_id ) {

						$args[ 5 ] = 'ext-first-submenu-page';

					} elseif ( $menu_id === $ext_last_id ) {

						$args[ 5 ] = 'ext-last-submenu-page';
					}
				}

				$local_cache[ $menu_lib ][ $menu_id ] = $args;
			}

			return $local_cache[ $menu_lib ];
		}

		/*
		 * Get the plugin readme and convert array elements to a plugin data object.
		 */
		public function get_plugin_data( $ext, $read_cache = true ) {

			$data = new StdClass;

			$info = $this->p->cf[ 'plugin' ][ $ext ];

			$readme = $this->get_readme_info( $ext, $read_cache );

			if ( empty( $readme ) ) {	// Make sure we got something back.

				return array();
			}

			foreach ( array(

				/*
				 * Readme array => Plugin object.
				 */
				'plugin_name'       => 'name',
				'plugin_slug'       => 'slug',
				'base'              => 'plugin',
				'stable_tag'        => 'version',
				'tested_up_to'      => 'tested',
				'requires_at_least' => 'requires',
				'home'              => 'homepage',
				'download'          => 'download_link',
				'author'            => 'author',
				'upgrade_notice'    => 'upgrade_notice',
				'last_updated'      => 'last_updated',
				'sections'          => 'sections',
				'remaining_content' => 'other_notes',	// Added to sections.
				'banners'           => 'banners',
				'icons'             => 'icons',
			) as $readme_key => $prop_name ) {

				switch ( $readme_key ) {

					case 'base':	// From plugin config.

						if ( ! empty( $info[ $readme_key ] ) ) {

							$data->$prop_name = $info[ $readme_key ];
						}

						break;

					case 'home':	// From plugin config.

						if ( ! empty( $info[ 'url' ][ 'purchase' ] ) ) {	// Check for purchase url first.

							$data->$prop_name = $info[ 'url' ][ 'purchase' ];

						} elseif ( ! empty( $info[ 'url' ][ $readme_key ] ) ) {

							$data->$prop_name = $info[ 'url' ][ $readme_key ];
						}

						break;

					case 'download':	// From plugin config.

						if ( ! empty( $info[ 'url' ][ $readme_key ] ) ) {

							$data->$prop_name = $info[ 'url' ][ $readme_key ];
						}

						break;

					case 'banners':	// From plugin config.
					case 'icons':	// From plugin config.

						if ( ! empty( $info[ 'assets' ][ $readme_key ] ) ) {

							$data->$prop_name = $info[ 'assets' ][ $readme_key ];	// Array with 1x / 2x images.
						}

						break;

					case 'remaining_content':

						if ( ! empty( $readme[ $readme_key ] ) ) {

							$data->sections[ $prop_name ] = $readme[ $readme_key ];
						}

						break;

					default:

						if ( ! empty( $readme[ $readme_key ] ) ) {

							$data->$prop_name = $readme[ $readme_key ];
						}

						break;
				}
			}

			return $data;
		}

		public function get_readme_info( $ext, $read_cache = true ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'ext'        => $ext,
					'read_cache' => $read_cache,
				) );
			}

			$rel_file       = 'readme.txt';
			$file_content   = '';
			$cache_md5_pre  = 'wpsso_r_';
			$cache_salt     = __METHOD__ . '(ext:' . $ext . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'file' );
			$readme_info    = array();

			/*
			 * Maybe get parsed readme from transient cache.
			 */
			if ( $cache_exp_secs > 0 && $read_cache ) {

				$readme_info = get_transient( $cache_id );

				if ( ! empty( $readme_info ) && is_array( $readme_info ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'readme_info retrieved from transient' );
					}

					return $readme_info;	// Stop here.
				}

			} else delete_transient( $cache_id );	// Just in case.

			$file_content = $this->get_ext_file_content( $ext, $rel_file );

			if ( ! empty( $file_content ) ) {

				require_once WPSSO_PLUGINDIR . 'lib/ext/parse-readme.php';

				$readme_parser =& SuextParseReadme::get_instance();

				$readme_info = $readme_parser->parse_content( $file_content );
			}

			/*
			 * Save the parsed readme to transient cache.
			 */
			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $readme_info, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'readme_info saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			return is_array( $readme_info ) ? $readme_info : array();	// Just in case.
		}

		/*
		 * See WpssoSubmenuSetup->show_metabox_setup_guide().
		 */
		public function get_ext_file_content( $ext, $rel_file ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'ext'      => $ext,
					'rel_file' => $rel_file,
				) );
			}

			$file_path      = WpssoConfig::get_ext_file_path( $ext, $rel_file );
			$file_url       = WpssoConfig::get_ext_file_url( $ext, $rel_file );	// Returns sanitized URL or false.
			$file_content   = '';
			$text_domain    = WpssoConfig::get_ext_text_domain( $ext );
			$cache_md5_pre  = 'wpsso_c_';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'file' );

			if ( $file_url ) {	// Sanitized URL or false.

				if ( $cache_exp_secs > 0 ) {

					$file_content = $this->p->cache->get( $file_url, 'raw', 'file', $cache_exp_secs );

				} else {

					$this->p->cache->clear( $file_url );

					$file_content = wp_remote_retrieve_body( wp_remote_get( $file_url ) );
				}
			}

			if ( empty( $file_content ) ) {	// No content from the file URL.

				if ( $file_path && file_exists( $file_path ) && $fh = @fopen( $file_path, 'rb' ) ) {

					$file_content = fread( $fh, filesize( $file_path ) );

					fclose( $fh );
				}
			}

			if ( $text_domain ) {	// False or text domain string.

				/*
				 * Translate HTML headers, paragraphs, list items, and blockquotes.
				 */
				$file_content = SucomUtil::get_html_transl( $file_content, $text_domain );
			}

			return $file_content;
		}

		/*
		 * Plugin links for the addons and licenses settings page.
		 */
		public function get_ext_action_links( $ext, $info, &$tabindex = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$pkg_info     = $this->p->util->get_pkg_info();	// Uses a local cache.
			$action_links = array();

			if ( ! empty( $info[ 'base' ] ) ) {

				$install_url = is_multisite() ?
					network_admin_url( 'plugin-install.php', null ) :
					get_admin_url( $blog_id = null, 'plugin-install.php' );

				$details_url = add_query_arg( array(
					'plugin'    => $info[ 'slug' ],
					'tab'       => 'plugin-information',
					'TB_iframe' => 'true',
					'width'     => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'width' ],
					'height'    => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'height' ],
				), $install_url );

				if ( SucomPlugin::is_plugin_installed( $info[ 'base' ] ) ) {	// Uses a local cache.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $info[ 'base' ] . ' is installed' );
					}

					if ( SucomPlugin::have_plugin_update( $info[ 'base' ] ) ) {	// Uses a local cache.

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $info[ 'base' ] . ' has an update' );
						}

						$action_links[] = '<a href="' . $details_url . '" class="thickbox" tabindex="' . ++$tabindex . '">' .
							'<font color="red">' . ( 'wpsso' === $ext ? _x( 'Plugin Details and Update',
								'plugin action link', 'wpsso' ) : _x( 'Add-on Details and Update',
									'plugin action link', 'wpsso' ) ) . '</font></a>';
					} else {

						$action_links[] = '<a href="' . $details_url . '" class="thickbox" tabindex="' . ++$tabindex . '">' .
							( 'wpsso' === $ext ? _x( 'Plugin Details', 'plugin action link', 'wpsso' ) :
								_x( 'Add-on Details', 'plugin action link', 'wpsso' ) ) . '</a>';
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( $info[ 'base' ] . ' is not installed' );
					}

					$action_links[] = '<a href="' . $details_url . '" class="thickbox" tabindex="' . ++$tabindex . '">' .
						( 'wpsso' === $ext ? _x( 'Plugin Details and Install', 'plugin action link', 'wpsso' ) :
							_x( 'Add-on Details and Install', 'plugin action link', 'wpsso' ) ) . '</a>';
				}

			} elseif ( ! empty( $info[ 'url' ][ 'home' ] ) ) {

				$action_links[] = '<a href="' . $info[ 'url' ][ 'home' ] . '" tabindex="' . ++$tabindex . '">' .
					_x( 'About Page', 'plugin action link', 'wpsso' ) . '</a>';
			}

			if ( ! empty( $info[ 'url' ][ 'docs' ] ) ) {

				$action_links[] = '<a href="' . $info[ 'url' ][ 'docs' ] . '"' .
					( false !== $tabindex ? ' tabindex="' . ++$tabindex . '"' : '' ) . '>' .
						_x( 'Documentation', 'plugin action link', 'wpsso' ) . '</a>';
			}

			if ( ! empty( $info[ 'url' ][ 'support' ] ) && ! empty( $pkg_info[ $ext ][ 'pp' ] ) ) {

				$action_links[] = '<a href="' . $info[ 'url' ][ 'support' ] . '"' .
					( false !== $tabindex ? ' tabindex="' . ++$tabindex . '"' : '' ) . '>' .
						sprintf( _x( '%s Support', 'plugin action link', 'wpsso' ),
							_x( $this->p->cf[ 'packages' ][ 'pro' ], 'package name', 'wpsso' ) ) . '</a>';

			} elseif ( ! empty( $info[ 'url' ][ 'forum' ] ) ) {

				$action_links[] = '<a href="' . $info[ 'url' ][ 'forum' ] . '"' .
					( false !== $tabindex ? ' tabindex="' . ++$tabindex . '"' : '' ) . '>' .
						_x( 'Community Forum', 'plugin action link', 'wpsso' ) . '</a>';
			}

			if ( ! empty( $info[ 'url' ][ 'purchase' ] ) ) {

				$action_links[] = $this->p->msgs->get( 'pro-purchase-link', array(
					'ext'      => $ext,
					'url'      => $info[ 'url' ][ 'purchase' ],
					'tabindex' => false !== $tabindex ? ++$tabindex : false,
				) );
			}

			return $action_links;
		}

		/*
		 * Returns a 128x128px image by default.
		 */
		public function get_ext_img_icon( $ext, $icon_px = 128 ) {

			/*
			 * The default image is a transparent 1px gif.
			 */
			$img_src    = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
			$img_srcset = '';

			if ( ! empty( $this->p->cf[ 'plugin' ][ $ext ][ 'assets' ][ 'icons' ] ) ) {

				$icons = $this->p->cf[ 'plugin' ][ $ext ][ 'assets' ][ 'icons' ];

				if ( ! empty( $icons[ '1x' ] ) ) {

					$img_src    = $icons[ '1x' ];
					$img_srcset .= $icons[ '1x' ] . ' 128w, ';
				}

				if ( ! empty( $icons[ '2x' ] ) ) {

					$img_srcset .= $icons[ '2x' ] . ' 256w, ';
				}
			}

			return '<img src="' . $img_src . '" srcset="' . trim( $img_srcset, $chars = ', ' ) . '" '
				. 'width="' . $icon_px . '" height="' . $icon_px . '" style="width:' . $icon_px . 'px; height:' . $icon_px . 'px;"/>';
		}

		/*
		 * See WpssoSubmenuDashboard->show_metabox_features_status().
		 */
		protected function show_features_status( &$ext = '', &$info = array(), &$features = array() ) {

			$status_titles = array(
				'disabled'    => __( 'Feature is disabled.', 'wpsso' ),
				'off'         => __( 'Feature is not active.', 'wpsso' ),
				'on'          => __( 'Feature is active.', 'wpsso' ),
				'rec'         => __( 'Feature is recommended but not active.', 'wpsso' ),
				'recommended' => __( 'Feature is recommended but not active.', 'wpsso' ),
			);

			foreach ( $features as $label => $arr ) {

				if ( ! empty( $arr[ 'label_transl' ] ) ) {

					$label_transl = $arr[ 'label_transl' ];

					unset( $features[ $label ], $arr[ 'label_transl' ] );

					$features[ $label_transl ] = $arr;
				}
			}

			uksort( $features, array( __CLASS__, 'sort_plugin_features' ) );

			foreach ( $features as $label_transl => $arr ) {

				if ( isset( $arr[ 'status' ] ) ) {	// Use provided status before class or constant check.

					$status_key = $arr[ 'status' ];

				} elseif ( isset( $arr[ 'classname' ] ) ) {

					$status_key = class_exists( $arr[ 'classname' ] ) ? 'on' : 'off';

				} elseif ( isset( $arr[ 'constant' ] ) ) {

					$status_key = SucomUtil::get_const( $arr[ 'constant' ] ) ? 'on' : 'off';

				} else {

					$status_key = '';
				}

				if ( ! empty( $status_key ) ) {

					$dashicon_title = '';
					$dashicon_name  = preg_match( '/^\(([a-z\-]+)\) (.*)/', $label_transl, $match ) ? $match[ 1 ] : 'admin-generic';
					$label_transl   = empty( $match[ 2 ] ) ? $label_transl : $match[ 2 ];
					$label_url      = empty( $arr[ 'label_url' ] ) ? '' : $arr[ 'label_url' ];
					$td_class       = empty( $arr[ 'td_class' ] ) ? '' : ' ' . $arr[ 'td_class' ];
					$td_class_is    = ' ' . SucomUtil::sanitize_key( 'module-is-' . $status_key );

					switch ( $dashicon_name ) {

						case 'api':

							$dashicon_title = __( 'Service API', 'wpsso' );
							$dashicon_name  = 'download';

							break;

						case 'code':

							$dashicon_title = __( 'Structured Data', 'wpsso' );
							$dashicon_name  = 'media-code';

							break;

						case 'code-plus':

							$dashicon_title = __( 'Structured Data Property', 'wpsso' );
							$dashicon_name  = 'welcome-add-page';

							break;

						case 'feature':

							$dashicon_title = __( 'Additional Feature', 'wpsso' );
							$dashicon_name  = 'pressthis';

							break;

						case 'plugin':

							$dashicon_title = __( 'Plugin Integration', 'wpsso' );
							$dashicon_name  = 'admin-plugins';

							break;

						case 'sharing':

							$dashicon_title = __( 'Sharing Functionality', 'wpsso' );
							$dashicon_name  = 'share';

							break;
					}

					echo '<tr>';

					echo '<td class="module-icon' . $td_class_is . '">';
					echo '<span class="dashicons-before dashicons-' . $dashicon_name . '" title="' . $dashicon_title . '"></span>';
					echo '</td>';

					echo '<td class="' . trim( 'module-label ' . $td_class . $td_class_is ) . '">';
					echo $label_url ? '<a href="' . $label_url . '">' : '';
					echo $label_transl;
					echo $label_url ? '</a>' : '';
					echo '</td>';

					echo '<td class="module-status' . $td_class_is .'">';
					echo '<div class="status-light" title="';
					echo isset( $status_titles[ $status_key ] ) ? $status_titles[ $status_key ] : '';
					echo '"></div>';
					echo '</td>';

					echo '</tr>' . "\n";
				}
			}
		}

		/*
		 * See WpssoSubmenuDashboard->show_metabox_cache_status().
		 */
		protected static function sort_by_label_key( $a, $b ) {

			if ( isset( $a[ 'label' ] ) && isset( $b[ 'label' ] ) ) {

				return strcmp( $a[ 'label' ], $b[ 'label' ] );
			}

			return 0;	// No change.
		}

		private static function sort_plugin_features( $feature_a, $feature_b ) {

			return strnatcasecmp( self::feature_priority( $feature_a ), self::feature_priority( $feature_b ) );
		}

		private static function feature_priority( $feature ) {

			if ( strpos( $feature, '(feature)' ) === 0 ) {

				return '(10) ' . $feature;
			}

			return $feature;
		}

		/*
		 * Sort the WPSSO Core plugin slug before the WPSSO add-on slugs.
		 */
		private static function sort_active_plugins( $a, $b ) {

			$plugin_prefix = 'wpsso/';
			$addon_prefix  = 'wpsso-';

			if ( 0 === strpos( $a, $plugin_prefix ) && 0 === strpos( $b, $addon_prefix ) ) {

				return -1;	// Sort the WPSSO Core plugin before.

			} elseif ( 0 === strpos( $a, $addon_prefix ) && 0 === strpos( $b, $plugin_prefix ) ) {

				return 1;	// Sort the WSSO add-on after.

			} elseif ( 0 === strpos( $a, 'jsm-show-' ) ) {

				return 1;	// Sort the JSM plugin after.

			} else return strcmp( $a, $b );	
		}
	}
}

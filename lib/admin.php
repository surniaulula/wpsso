<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoAdmin' ) ) {

	class WpssoAdmin {

		protected $p;
		protected $menu_id;
		protected $menu_name;
		protected $menu_lib;
		protected $menu_ext;
		protected $pagehook;
		protected $pageref_url;
		protected $pageref_title;

		public static $pkg    = array();
		public static $readme = array();

		public $form    = null;
		public $lang    = array();
		public $submenu = array();

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * The WpssoScript add_iframe_inline_script() method includes jQuery in the thickbox iframe 
			 * to add the iframe_parent arguments when the Install or Update button is clicked.
			 *
			 * These class properties are used by both the WpssoAdmin plugin_complete_actions() and 
			 * plugin_complete_redirect() methods to direct the user back to the thickbox iframe parent
			 * (aka the plugin licenses settings page) after plugin installation / activation / update.
			 */
			if ( ! empty( $_GET[ $this->p->lca . '_pageref_title' ] ) ) {
				$this->pageref_title = esc_html( urldecode( $_GET[ $this->p->lca . '_pageref_title' ] ) );
			}

			if ( ! empty( $_GET[ $this->p->lca . '_pageref_url' ] ) ) {
				$this->pageref_url = esc_url_raw( urldecode( $_GET[ $this->p->lca . '_pageref_url' ] ) );
			}

			add_action( 'activated_plugin', array( $this, 'reset_check_head_count' ), 10 );
			add_action( 'after_switch_theme', array( $this, 'reset_check_head_count' ), 10 );
			add_action( 'upgrader_process_complete', array( $this, 'reset_check_head_count' ), 10 );

			add_action( 'after_switch_theme', array( $this, 'check_tmpl_head_attributes' ), 20 );
			add_action( 'upgrader_process_complete', array( $this, 'check_tmpl_head_attributes' ), 20 );

			if ( SucomUtil::get_const( 'DOING_AJAX' ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'DOING_AJAX is true' );
				}

				/**
				 * Nothing to do.
				 */

			} else {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'DOING_AJAX is false' );
				}

				/**
				 * The admin_menu action is run before admin_init.
				 */
				add_action( 'admin_menu', array( $this, 'load_menu_objects' ), -1000 );
				add_action( 'admin_menu', array( $this, 'add_admin_menus' ), WPSSO_ADD_MENU_PRIORITY );
				add_action( 'admin_menu', array( $this, 'add_admin_submenus' ), WPSSO_ADD_SUBMENU_PRIORITY );
				add_action( 'admin_init', array( $this, 'add_plugins_page_upgrade_notice' ) );
				add_action( 'admin_init', array( $this, 'register_setting' ) );

				/**
				 * Hook admin_head to allow for setting changes, plugin activation / loading, etc.
				 */
				if ( ! SucomUtilWP::doing_block_editor() ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'not doing the block editor - checking for conflicts and required' );
					}

					add_action( 'admin_head', array( $this, 'conflict_warnings' ), -1000 );
					add_action( 'admin_head', array( $this, 'required_notices' ), -500 );
					add_action( 'admin_head', array( $this, 'update_count_notice' ), 0 );
				}

				/**
				 * WPSSO_TOOLBAR_NOTICES can be true, false, or an array of notice types to include in the menu.
				 */
				if ( SucomUtil::get_const( 'WPSSO_TOOLBAR_NOTICES' ) ) {
					add_action( 'admin_bar_menu', array( $this, 'add_admin_tb_notices_menu_item' ), WPSSO_TB_NOTICE_MENU_ORDER );
				}

				add_filter( 'current_screen', array( $this, 'maybe_show_screen_notices' ) );
				add_filter( 'plugin_action_links', array( $this, 'append_wp_plugin_action_links' ), 10, 2 );
				add_filter( 'wp_redirect', array( $this, 'profile_updated_redirect' ), -100, 2 );

				if ( is_multisite() ) {
					add_action( 'network_admin_menu', array( $this, 'load_network_menu_objects' ), -1000 );
					add_action( 'network_admin_menu', array( $this, 'add_network_admin_menus' ), WPSSO_ADD_MENU_PRIORITY );
					add_action( 'network_admin_edit_' . WPSSO_SITE_OPTIONS_NAME, array( $this, 'save_site_options' ) );
					add_filter( 'network_admin_plugin_action_links', array( $this, 'append_site_wp_plugin_action_links' ), 10, 2 );
				}

		 		/**
				 * Provide plugin data / information from the readme.txt for additional add-ons.
				 * Don't hook the 'plugins_api_result' filter if the update manager is active as it
				 * provides more complete plugin data than what's available from the readme.txt.
				 */
				if ( empty( $this->p->avail[ 'p_ext' ][ 'um' ] ) ) {	// Since um v1.6.0.
					add_filter( 'plugins_api_result', array( $this, 'external_plugin_data' ), 1000, 3 );	// Since wp v2.7.
				}

				add_filter( 'http_request_args', array( $this, 'add_expect_header' ), 1000, 2 );
				add_filter( 'http_request_host_is_external', array( $this, 'maybe_allow_hosts' ), 1000, 3 );
				add_filter( 'install_plugin_complete_actions', array( $this, 'plugin_complete_actions' ), 1000, 1 );
				add_filter( 'update_plugin_complete_actions', array( $this, 'plugin_complete_actions' ), 1000, 1 );
				add_filter( 'wp_redirect', array( $this, 'plugin_complete_redirect' ), 1000, 1 );
			}
		}

		public function load_network_menu_objects() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Some network menu pages extend the site menu pages.
			 */
			$this->load_menu_objects( array( 'submenu', 'sitesubmenu' ) );
		}

		public function load_menu_objects( $menu_libs = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->set_plugin_pkg_info();

			if ( empty( $menu_libs ) ) {

				/**
				 * Note that 'setting' MUST follow 'submenu' to extend submenu/advanced.php.
				 */
				$menu_libs = array( 'submenu', 'setting', 'profile' );
			}

			foreach ( $menu_libs as $menu_lib ) {	// profile, setting, submenu, or sitesubmenu

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! isset( $info[ 'lib' ][ $menu_lib ] ) ) {	// Not all add-ons have submenus.
						continue;
					}

					foreach ( $info[ 'lib' ][ $menu_lib ] as $menu_id => $menu_name ) {

						$classname = apply_filters( $ext . '_load_lib', false, $menu_lib . '/' . $menu_id );

						if ( is_string( $classname ) && class_exists( $classname ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'loading classname ' . $classname . ' for menu id ' . $menu_id );
							}

							if ( ! empty( $info[ 'text_domain' ] ) ) {
								$menu_name = _x( $menu_name, 'lib file description', $info[ 'text_domain' ] );
							}

							$this->submenu[ $menu_id ] = new $classname( $this->p, $menu_id, $menu_name, $menu_lib, $ext );

						} elseif ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'classname not found for menu lib ' . $menu_lib . '/' . $menu_id );
						}
					}
				}
			}
		}

		public function set_plugin_pkg_info() {

			if ( ! empty( self::$pkg ) ) {
				return;
			}

			$has_pdir = $this->p->avail[ '*' ][ 'p_dir' ];
			$has_pp   = $this->p->check->pp( $this->p->lca, true, $has_pdir );

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				self::$pkg[ $ext ][ 'pdir' ] = $this->p->check->pp( $ext, false, $has_pdir );

				self::$pkg[ $ext ][ 'pp' ] = ! empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) &&
					$has_pp && $this->p->check->pp( $ext, true, WPSSO_UNDEF ) === WPSSO_UNDEF ? true : false;

				self::$pkg[ $ext ][ 'type' ] = self::$pkg[ $ext ][ 'pp' ] ?
					_x( 'Pro', 'package type', 'wpsso' ) : _x( 'Free', 'package type', 'wpsso' );

				self::$pkg[ $ext ][ 'short' ] = $info[ 'short' ] . ' ' . self::$pkg[ $ext ][ 'type' ];

				self::$pkg[ $ext ][ 'name' ] = SucomUtil::get_pkg_name( $info[ 'name' ], self::$pkg[ $ext ][ 'type' ] );

				self::$pkg[ $ext ][ 'status' ] = self::$pkg[ $ext ][ 'pp' ] ? 'L' : ( self::$pkg[ $ext ][ 'pdir' ] ? 'U' : 'F' );

				self::$pkg[ $ext ][ 'gen' ] = $info[ 'short' ] . ' ' . ( isset( $info[ 'version' ] ) ?
					$info[ 'version' ] . '/' . self::$pkg[ $ext ][ 'status' ] : '' );
			}
		}

		public function add_network_admin_menus() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$this->add_admin_menus( 'sitesubmenu' );
		}

		/**
		 * Add a new main menu, and its sub-menu items.
		 *
		 * $menu_lib = profile | setting | submenu | sitesubmenu
		 */
		public function add_admin_menus( $menu_lib = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( empty( $menu_lib ) ) {
				$menu_lib = 'submenu';
			}

			$libs = $this->p->cf[ '*' ][ 'lib' ][ $menu_lib ];

			$this->menu_id   = key( $libs );
			$this->menu_name = $libs[ $this->menu_id ];
			$this->menu_lib  = $menu_lib;
			$this->menu_ext  = $this->p->lca;

			if ( isset( $this->submenu[ $this->menu_id ] ) ) {
				$menu_slug = $this->p->lca . '-' . $this->menu_id;
				$this->submenu[ $this->menu_id ]->add_menu_page( $menu_slug );
			}

			$sorted_menu   = array();
			$unsorted_menu = array();

			$top_first_id = false;
			$top_last_id  = false;
			$ext_first_id = false;
			$ext_last_id  = false;

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $info[ 'lib' ][ $menu_lib ] ) ) {	// not all add-ons have submenus
					continue;
				}

				foreach ( $info[ 'lib' ][ $menu_lib ] as $menu_id => $menu_name ) {

					$ksort_key = $menu_name . '-' . $menu_id;

					$parent_slug = $this->p->lca . '-' . $this->menu_id;

					if ( $ext === $this->p->lca ) {

						$unsorted_menu[] = array( $parent_slug, $menu_id, $menu_name, $menu_lib, $ext );

						if ( false === $top_first_id ) {
							$top_first_id = $menu_id;
						}

						$top_last_id = $menu_id;

					} else {

						$sorted_menu[ $ksort_key ] = array( $parent_slug, $menu_id, $menu_name, $menu_lib, $ext );

						if ( false === $ext_first_id ) {
							$ext_first_id = $menu_id;
						}

						$ext_last_id = $menu_id;
					}
				}
			}

			ksort( $sorted_menu );

			foreach ( array_merge( $unsorted_menu, $sorted_menu ) as $key => $arg ) {

				if ( $arg[1] === $top_first_id ) {

					$css_class = 'top-first-submenu-page';

				} elseif ( $arg[1] === $top_last_id ) {

					$css_class = 'top-last-submenu-page';	// Underlined with add-ons.

					if ( empty( $ext_first_id ) ) {
						$css_class .= ' no-add-ons';
					} else {
						$css_class .= ' with-add-ons';
					}

				} elseif ( $arg[1] === $ext_first_id ) {

					$css_class = 'ext-first-submenu-page';

				} elseif ( $arg[1] === $ext_last_id ) {

					$css_class = 'ext-last-submenu-page';

				} else {

					$css_class = '';
				}

				if ( isset( $this->submenu[ $arg[1] ] ) ) {
					$this->submenu[ $arg[1] ]->add_submenu_page( $arg[0], '', '', '', '', $css_class );
				} else {
					$this->add_submenu_page( $arg[0], $arg[1], $arg[2], $arg[3], $arg[4], $css_class );
				}
			}
		}

		/**
		 * Add sub-menu items to existing menus (profile and setting).
		 */
		public function add_admin_submenus() {

			foreach ( array( 'profile', 'setting' ) as $menu_lib ) {

				/**
				 * Match WordPress behavior (users page for admins, profile page for everyone else).
				 */
				if ( $menu_lib === 'profile' && current_user_can( 'list_users' ) ) {
					$parent_slug = $this->p->cf[ 'wp' ][ 'admin' ][ 'users' ][ 'page' ];
				} else {
					$parent_slug = $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ];
				}

				$sorted_menu = array();

				foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

					if ( ! isset( $info[ 'lib' ][ $menu_lib ] ) ) {	// not all add-ons have submenus
						continue;
					}

					foreach ( $info[ 'lib' ][ $menu_lib ] as $menu_id => $menu_name ) {

						$ksort_key = $menu_name . '-' . $menu_id;

						$sorted_menu[ $ksort_key ] = array( $parent_slug, $menu_id, $menu_name, $menu_lib, $ext );
					}
				}

				ksort( $sorted_menu );

				foreach ( $sorted_menu as $key => $arg ) {
					if ( isset( $this->submenu[ $arg[1] ] ) ) {
						$this->submenu[ $arg[1] ]->add_submenu_page( $arg[0] );
					} else {
						$this->add_submenu_page( $arg[0], $arg[1], $arg[2], $arg[3], $arg[4] );
					}
				}
			}
		}

		/**
		 * Called by show_setting_page() and extended by the sitesubmenu classes to load site options instead.
		 */
		protected function set_form_object( $menu_ext ) {	// $menu_ext required for text_domain

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'setting form object for ' . $menu_ext );
			}

			$def_opts = $this->p->opt->get_defaults();

			$this->form = new SucomForm( $this->p, WPSSO_OPTIONS_NAME, $this->p->options, $def_opts, $menu_ext );
		}

		protected function &get_form_object( $menu_ext ) {	// $menu_ext required for text_domain

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! isset( $this->form ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'form object not defined' );
				}

				$this->set_form_object( $menu_ext );

			} elseif ( $this->form->get_menu_ext() !== $menu_ext ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'form object text domain does not match' );
				}

				$this->set_form_object( $menu_ext );
			}

			return $this->form;
		}

		public function register_setting() {

			register_setting( $this->p->lca . '_setting', WPSSO_OPTIONS_NAME, array( $this, 'registered_setting_sanitation' ) );
		}

		public function add_plugins_page_upgrade_notice() {

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {
				if ( ! empty( $info[ 'base' ] ) ) {
					add_action( 'in_plugin_update_message-' . $info[ 'base' ], array( $this, 'show_upgrade_notice' ), 10, 2 );
				}
			}
		}

		public function show_upgrade_notice( $data, $response ) {

			if ( isset( $data[ 'upgrade_notice' ] ) ) {	// Just in case.
				echo '<span style="display:table;border-collapse:collapse;margin-left:26px;">';
				echo '<span style="display:table-cell;">' . strip_tags( $data[ 'upgrade_notice' ] ) . '</span>';
				echo '</span>';
			}
		}

		protected function add_menu_page( $menu_slug ) {

			global $wp_version;

			$page_title = self::$pkg[ $this->p->lca ][ 'short' ] . ' &mdash; ' . $this->menu_name;
			$menu_title = _x( $this->p->cf[ 'menu' ][ 'title' ], 'menu title', 'wpsso' ) . ' ' . self::$pkg[ $this->p->lca ][ 'type' ]; // Pre-translated.
			$cap_name   = isset( $this->p->cf[ 'wp' ][ 'admin' ][ $this->menu_lib ][ 'cap' ] ) ?	// Just in case.
				$this->p->cf[ 'wp' ][ 'admin' ][ $this->menu_lib ][ 'cap' ] : 'manage_options';
			$icon_url   = null;	// Icon is provided by WpssoStyle::add_admin_page_style(). 
			$function   = array( $this, 'show_setting_page' );

			$this->pagehook = add_menu_page( $page_title, $menu_title, $cap_name, $menu_slug, $function, $icon_url, WPSSO_MENU_ORDER );

			add_action( 'load-' . $this->pagehook, array( $this, 'load_setting_page' ) );
		}

		protected function add_submenu_page( $parent_slug, $menu_id = '', $menu_name = '', $menu_lib = '', $menu_ext = '', $css_class = '' ) {

			if ( empty( $menu_id ) ) {
				$menu_id = $this->menu_id;
			}

			if ( empty( $menu_name ) ) {
				$menu_name = $this->menu_name;
			}

			if ( empty( $menu_lib ) ) {
				$menu_lib = $this->menu_lib;
			}

			if ( empty( $menu_ext ) ) {

				$menu_ext = $this->menu_ext;	// lowercase acronyn for plugin or add-on

				if ( empty( $menu_ext ) ) {
					$menu_ext = $this->p->lca;
				}
			}

			global $wp_version;

			/**
			 * WordPress version 3.8 is required for dashicons.
			 */
			if ( ( $menu_lib === 'submenu' || $menu_lib === 'sitesubmenu' ) && version_compare( $wp_version, '3.8', '>=' ) ) {

				if ( empty( $this->p->cf[ 'menu' ][ 'dashicons' ][ $menu_id ] ) ) {

					if ( $menu_ext === $this->p->lca ) {
						$dashicon = 'admin-settings';	// use settings dashicon by default
					} else {
						$dashicon = 'admin-plugins';	// use plugin dashicon by default for add-ons
					}

				} else {
					$dashicon = $this->p->cf[ 'menu' ][ 'dashicons' ][ $menu_id ];
				}

				$css_class  = $this->p->lca . '-menu-item' . ( $css_class ? ' ' . $css_class : '' );
				$menu_title = '<div class="' . $css_class . ' dashicons-before dashicons-' . $dashicon . '"></div>' .
					'<div class="' . $css_class . ' menu-item-label">' . $menu_name . '</div>';

			} else {
				$menu_title = $menu_name;
			}
	
			$page_title = self::$pkg[ $menu_ext ][ 'short' ] . ' &mdash; ' . $menu_name;
			$cap_name   = isset( $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'cap' ] ) ?	// Just in case.
				$this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'cap' ] : 'manage_options';
			$menu_slug  = $this->p->lca . '-' . $menu_id;
			$function   = array( $this, 'show_setting_page' );

			$this->pagehook = add_submenu_page( $parent_slug, $page_title, $menu_title, $cap_name, $menu_slug, $function );

			if ( $function ) {
				add_action( 'load-' . $this->pagehook, array( $this, 'load_setting_page' ) );
			}
		}

		/**
		 * Plugin links for the WordPress network plugins page.
		 */
		public function append_site_wp_plugin_action_links( $action_links, $plugin_base, $menu_lib = 'sitesubmenu' ) {

			return $this->append_wp_plugin_action_links( $action_links, $plugin_base, $menu_lib );
		}

		/**
		 * Plugin links for the WordPress plugins page.
		 */
		public function append_wp_plugin_action_links( $action_links, $plugin_base, $menu_lib = 'submenu'  ) {

			if ( ! isset( $this->p->cf[ '*' ][ 'base' ][ $plugin_base ] ) ) {
				return $action_links;
			}

			foreach ( $action_links as $key => $val ) {
				if ( false !== strpos( $val, '>Edit<' ) ) {
					unset ( $action_links[ $key ] );
				}
			}

			$ext = $this->p->cf[ '*' ][ 'base' ][ $plugin_base ];

			$settings_page = empty( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $menu_lib ] ) ?
				'' : key( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $menu_lib ] );

			$addons_page = 'sitesubmenu' === $menu_lib ? 'site-addons' : 'addons';

			$dashboard_page = 'sitesubmenu' === $menu_lib ? '' : 'dashboard';	// No dashboard for network admin.

			if ( ! empty( $settings_page ) ) {

				$settings_page_transl  = _x( $this->p->cf[ 'plugin' ][ $ext ][ 'lib' ][ $menu_lib ][ $settings_page ], 'lib file description', 'wpsso' );
				$settings_label_transl = sprintf( _x( '%s Settings', 'plugin action link', 'wpsso' ), $settings_page_transl );

				$action_links[] = '<a href="' . $this->p->util->get_admin_url( $settings_page ) . '">' . $settings_label_transl . '</a>';
			}

			if ( ! empty( $dashboard_page ) ) {
				if ( $ext === $this->p->lca ) {	// Only add for the core plugin.
					$action_links[] = '<a href="' . $this->p->util->get_admin_url( $dashboard_page ) . '">' . 
						_x( 'Dashboard', 'plugin action link', 'wpsso' ) . '</a>';
				}
			}

			if ( ! empty( $addons_page ) ) {
				if ( $ext === $this->p->lca ) {	// Only add for the core plugin.
					$action_links[] = '<a href="' . $this->p->util->get_admin_url( $addons_page ) . '">' . 
						_x( 'Add-ons', 'plugin action link', 'wpsso' ) . '</a>';
				}
			}

			return $action_links;
		}

		/**
		 * Plugin links for the addons and licenses settings page.
		 */
		public function get_ext_action_links( $ext, $info, &$tabindex = false ) {

			$action_links = array();

			if ( ! empty( $info[ 'base' ] ) ) {

				$install_url = is_multisite() ?
					network_admin_url( 'plugin-install.php', null ) :
					get_admin_url( null, 'plugin-install.php' );

				$details_url = add_query_arg( array(
					'plugin'    => $info[ 'slug' ],
					'tab'       => 'plugin-information',
					'TB_iframe' => 'true',
					'width'     => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'width' ],
					'height'    => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'height' ],
				), $install_url );

				if ( SucomPlugin::is_plugin_installed( $info[ 'base' ], $use_cache = true ) ) {

					if ( SucomPlugin::have_plugin_update( $info[ 'base' ] ) ) {
						$action_links[] = '<a href="' . $details_url . '" class="thickbox" tabindex="' . ++$tabindex . '">' .
							'<font color="red">' . _x( 'Plugin Details and Update', 'plugin action link',
								'wpsso' ) . '</font></a>';
					} else {
						$action_links[] = '<a href="' . $details_url . '" class="thickbox" tabindex="' . ++$tabindex . '">' .
							_x( 'Plugin Details', 'plugin action link', 'wpsso' ) . '</a>';
					}

				} else {
					$action_links[] = '<a href="' . $details_url . '" class="thickbox" tabindex="' . ++$tabindex . '">' .
						_x( 'Plugin Details and Install', 'plugin action link', 'wpsso' ) . '</a>';
				}

			} elseif ( ! empty( $info[ 'url' ][ 'home' ] ) ) {
				$action_links[] = '<a href="' . $info[ 'url' ][ 'home' ] . '" tabindex="' . ++$tabindex . '">' .
					_x( 'Plugin Description', 'plugin action link', 'wpsso' ) . '</a>';
			}

			if ( ! empty( $info[ 'url' ][ 'docs' ] ) ) {
				$action_links[] = '<a href="' . $info[ 'url' ][ 'docs' ] . '"' .
					( false !== $tabindex ? ' tabindex="' . ++$tabindex . '"' : '' ) . '>' .
						_x( 'Documentation', 'plugin action link', 'wpsso' ) . '</a>';
			}

			if ( ! empty( $info[ 'url' ][ 'support' ] ) && self::$pkg[ $ext ][ 'pp' ] ) {
				$action_links[] = '<a href="' . $info[ 'url' ][ 'support' ] . '"' .
					( false !== $tabindex ? ' tabindex="' . ++$tabindex . '"' : '' ) . '>' .
						_x( 'Pro Support', 'plugin action link', 'wpsso' ) . '</a>';

			} elseif ( ! empty( $info[ 'url' ][ 'forum' ] ) ) {
				$action_links[] = '<a href="' . $info[ 'url' ][ 'forum' ] . '"' .
					( false !== $tabindex ? ' tabindex="' . ++$tabindex . '"' : '' ) . '>' .
						_x( 'Community Forum', 'plugin action link', 'wpsso' ) . '</a>';
			}

			if ( ! empty( $info[ 'url' ][ 'purchase' ] ) ) {

				$purchase_url = add_query_arg( 'utm_source', 'licenses-action-links', $info[ 'url' ][ 'purchase' ] );

				$action_links[] = $this->p->msgs->get( 'pro-purchase-link', array(
					'ext'      => $ext,
					'url'      => $purchase_url, 
					'tabindex' => false !== $tabindex ? ++$tabindex : false,
				) );
			}

			return $action_links;
		}

		/**
		 * Define and disable the "Expect: 100-continue" header. $req should be an array,
		 * so make sure other filters aren't giving us a string or boolean.
		 */
		public function add_expect_header( $req, $url ) {

			if ( ! is_array( $req ) ) {
				$req = array();
			}

			if ( ! isset( $req[ 'headers' ] ) || ! is_array( $req[ 'headers' ] ) ) {
				$req[ 'headers' ] = array();
			}

			$req[ 'headers' ][ 'Expect' ] = '';

			return $req;
		}

		public function maybe_allow_hosts( $is_allowed, $ip, $url ) {

			if ( $is_allowed ) {	// Already allowed.
				return $is_allowed;
			}

			if ( isset( $this->p->cf[ 'extend' ] ) ) {
				foreach ( $this->p->cf[ 'extend' ] as $host ) {
					if ( strpos( $url, $host ) === 0 ) {
						return true;
					}
				}
			}

			return $is_allowed;
		}

		/**
		 * Provide plugin data / information from the readme.txt for additional add-ons.
		 */
		public function external_plugin_data( $res, $action = null, $args = null ) {

			if ( $action !== 'plugin_information' ) {	// this filter only provides plugin data
				return $res;
			} elseif ( empty( $args->slug ) ) {	// make sure we have a slug in the request
				return $res;
			} elseif ( empty( $this->p->cf[ '*' ][ 'slug' ][ $args->slug ] ) ) {	// make sure the plugin slug is one of ours
				return $res;
			} elseif ( isset( $res->slug ) && $res->slug === $args->slug ) {	// if the object from WordPress looks complete, return it as-is
				return $res;
			}

			/**
			 * Get the add-on acronym to read its config.
			 */
			$ext = $this->p->cf[ '*' ][ 'slug' ][ $args->slug ];

			/**
			 * Make sure we have a config for that slug.
			 */
			if ( empty( $this->p->cf[ 'plugin' ][ $ext ] ) ) {
				return $res;
			}

			/**
			 * Get plugin data from the plugin readme.
			 */
			$plugin_data = $this->get_plugin_data( $ext, true );

			/**
			 * Make sure we have something to return.
			 */
			if ( empty( $plugin_data ) ) {
				return $res;
			}

			/**
			 * Let WordPress known that this is not a wordpress.org plugin.
			 */
			$plugin_data->external = true;

			return $plugin_data;
		}

		/**
		 * Get the plugin readme and convert array elements to a plugin data object.
		 */
		public function get_plugin_data( $ext, $read_cache = true ) {

			$data   = new StdClass;
			$info   = $this->p->cf[ 'plugin' ][ $ext ];
			$readme = $this->get_readme_info( $ext, $read_cache );

			// make sure we got something back
			if ( empty( $readme ) ) {
				return array();
			}

			foreach ( array(
				// readme array => plugin object
				'plugin_name'       => 'name',
				'plugin_slug'       => 'slug',
				'base'              => 'plugin',
				'stable_tag'        => 'version',
				'tested_up_to'      => 'tested',
				'requires_at_least' => 'requires',
				'home'              => 'homepage',
				'latest'            => 'download_link',
				'author'            => 'author',
				'upgrade_notice'    => 'upgrade_notice',
				'last_updated'      => 'last_updated',
				'sections'          => 'sections',
				'remaining_content' => 'other_notes',	// added to sections
				'banners'           => 'banners',
			) as $readme_key => $prop_name ) {

				switch ( $readme_key ) {

					case 'base':	// from plugin config

						if ( ! empty( $info[ $readme_key ] ) ) {
							$data->$prop_name = $info[ $readme_key ];
						}

						break;

					case 'home':	// from plugin config

						if ( ! empty( $info[ 'url' ][ 'purchase' ] ) ) {	// check for purchase url first

							$data->$prop_name = $info[ 'url' ][ 'purchase' ];

							break;
						}

						// no break - override with 'home' url from config (if one is defined)

					case 'latest':	// from plugin config

						if ( ! empty( $info[ 'url' ][ $readme_key ] ) ) {
							$data->$prop_name = $info[ 'url' ][ $readme_key ];
						}

						break;

					case 'banners':	// from plugin config

						if ( ! empty( $info[ 'img' ][ $readme_key ] ) ) {
							$data->$prop_name = $info[ 'img' ][ $readme_key ];	// array with low/high images
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

		/**
		 * This method receives only a partial options array, so re-create a full one.
		 * WordPress handles the actual saving of the options to the database table.
		 */
		public function registered_setting_sanitation( $opts ) {

			if ( ! is_array( $opts ) ) {

				add_settings_error( WPSSO_OPTIONS_NAME, 'notarray', '<b>' . strtoupper( $this->p->lca ) . ' Error</b> : ' .
					__( 'Submitted options are not an array.', 'wpsso' ), 'error' );

				return $opts;
			}

			$def_opts = $this->p->opt->get_defaults();	// Get default values, including css from default stylesheets.

			/**
			 * Clear any old notices for the current user before sanitation checks.
			 */
			$this->p->notice->clear();

			$opts = SucomUtil::restore_checkboxes( $opts );
			$opts = array_merge( $this->p->options, $opts );
			$opts = $this->p->opt->sanitize( $opts, $def_opts, $network = false );	// Sanitation updates image width/height info.
			$opts = apply_filters( $this->p->lca . '_save_options', $opts, WPSSO_OPTIONS_NAME, $network = false, $doing_upgrade = false );

			$this->p->options = $opts;	// Update the options with any changes.

			if ( empty( $this->p->options[ 'plugin_clear_on_save' ] ) ) {

				/**
				 * Note that get_admin_url() will use the essential settings URL if we're not on a settings page.
				 */
				$clear_cache_link = $this->p->util->get_admin_url( wp_nonce_url( '?' . $this->p->lca . '-action=clear_all_cache',
					WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME ), _x( 'Clear All Caches', 'submit button', 'wpsso' ) );
	
				$this->p->notice->upd( '<strong>' . __( 'Plugin settings have been saved.', 'wpsso' ) . '</strong> ' .
					sprintf( __( 'Note that some caches may take several days to expire and reflect these changes (or %s now).',
						'wpsso' ), $clear_cache_link ) );

			} else {

				$settings_page_link = $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_cache',
					_x( 'Clear All Caches on Save Settings', 'option label', 'wpsso' ) );

				$this->p->notice->upd( '<strong>' . __( 'Plugin settings have been saved.', 'wpsso' ) . '</strong> ' .
					sprintf( __( 'A background task will begin shortly to clear all caches (the %s option is enabled).',
						'wpsso' ), $settings_page_link ) );

				$this->p->util->schedule_clear_all_cache( $user_id = get_current_user_id(), $clear_other = true );

			}

			if ( empty( $opts[ 'plugin_filter_content' ] ) ) {

				$get_msg_key = 'notice-content-filters-disabled';
				$notice_key  = $get_msg_key . '-reminder';

				$this->p->notice->warn( $this->p->msgs->get( $get_msg_key ), null, $notice_key, true );	// Can be dismissed.
			}

			$this->check_tmpl_head_attributes();

			return $opts;
		}

		public function save_site_options() {

			if ( ! $page = SucomUtil::get_request_value( 'page', 'POST' ) ) {	// Uses sanitize_text_field.
				$page = key( $this->p->cf[ '*' ][ 'lib' ][ 'sitesubmenu' ] );
			}

			if ( empty( $_POST[ WPSSO_NONCE_NAME ] ) ) {	// WPSSO_NONCE_NAME is an md5() string.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'nonce token validation post field missing' );
				}

				wp_redirect( $this->p->util->get_admin_url( $page ) );

				exit;

			} elseif ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

				$this->p->notice->err( __( 'Nonce token validation failed for network options (update ignored).', 'wpsso' ) );

				wp_redirect( $this->p->util->get_admin_url( $page ) );

				exit;

			} elseif ( ! current_user_can( 'manage_network_options' ) ) {

				$this->p->notice->err( __( 'Insufficient privileges to modify network options.', 'wpsso' ) );

				wp_redirect( $this->p->util->get_admin_url( $page ) );

				exit;
			}

			/**
			 * Clear any old notices for the current user before sanitation checks.
			 */
			$this->p->notice->clear();

			$def_opts = $this->p->opt->get_site_defaults();

			$opts = empty( $_POST[WPSSO_SITE_OPTIONS_NAME] ) ? $def_opts : SucomUtil::restore_checkboxes( $_POST[WPSSO_SITE_OPTIONS_NAME] );
			$opts = array_merge( $this->p->site_options, $opts );
			$opts = $this->p->opt->sanitize( $opts, $def_opts, $network = true );
			$opts = apply_filters( $this->p->lca . '_save_site_options', $opts, $def_opts, $network = true );

			update_site_option( WPSSO_SITE_OPTIONS_NAME, $opts );

			$this->p->notice->upd( '<strong>' . __( 'Plugin settings have been saved.', 'wpsso' ) . '</strong>' );

			wp_redirect( $this->p->util->get_admin_url( $page ) . '&settings-updated=true' );

			exit;	// Stop after redirect.
		}

		public function load_setting_page() {

			$action_query = $this->p->lca . '-action';
			$action_value = SucomUtil::get_request_value( $action_query ) ;		// POST or GET with sanitize_text_field().
			$action_value = SucomUtil::sanitize_hookname( $action_value );
			$nonce_value  = SucomUtil::get_request_value( WPSSO_NONCE_NAME ) ;	// POST or GET with sanitize_text_field().

			wp_enqueue_script( 'postbox' );

			if ( ! empty( $action_value ) ) {

				$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

				if ( empty( $nonce_value ) ) {	// WPSSO_NONCE_NAME is an md5() string.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'nonce token validation field missing' );
					}

				} elseif ( ! wp_verify_nonce( $nonce_value, WpssoAdmin::get_nonce_action() ) ) {

					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".',
						'wpsso' ), 'admin', $action_value ) );

				} else {

					switch ( $action_value ) {

						case 'clear_all_cache':

							$this->p->notice->upd( __( 'A background task will begin shortly to clear all caches.', 'wpsso' ) );

							$this->p->util->schedule_clear_all_cache( get_current_user_id(), $clear_other = true );

							break;

						case 'clear_all_cache_and_short_urls':

							$this->p->notice->upd( __( 'A background task will begin shortly to clear all caches and short URLs.', 'wpsso' ) );

							$this->p->util->schedule_clear_all_cache( get_current_user_id(), $clear_other = true, $clear_short = true );

							break;

						case 'reset_user_metabox_layout':

							$user_id   = get_current_user_id();
							$user_obj  = get_userdata( $user_id );
							$user_name = $user_obj->display_name;

							WpssoUser::delete_metabox_prefs( $user_id );

							$this->p->notice->upd( sprintf( __( 'Metabox layout preferences for user ID #%d "%s" have been reset.',
								'wpsso' ), $user_id, $user_name ) );

							break;

						case 'reset_user_dismissed_notices':

							$user_id   = get_current_user_id();
							$user_obj  = get_userdata( $user_id );
							$user_name = $user_obj->display_name;

							delete_user_option( $user_id, WPSSO_DISMISS_NAME, false );	// $global = false
							delete_user_option( $user_id, WPSSO_DISMISS_NAME, true );	// $global = true

							$this->p->notice->upd( sprintf( __( 'Dismissed notices for user ID #%d "%s" have been reset.',
								'wpsso' ), $user_id, $user_name ) );

							break;

						case 'change_show_options':

							$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( 'show-opts' ) );

							if ( isset( $this->p->cf[ 'form' ][ 'show_options' ][ $_GET[ 'show-opts' ] ] ) ) {

								$this->p->notice->upd( sprintf( __( 'Option preference saved &mdash; viewing "%s" by default.',
									'wpsso' ), $this->p->cf[ 'form' ][ 'show_options' ][ $_GET[ 'show-opts' ] ] ) );

								WpssoUser::save_pref( array( 'show_opts' => $_GET[ 'show-opts' ] ) );
							}

							break;

						case 'modify_tmpl_head_attributes':

							$this->modify_tmpl_head_attributes();

							break;

						case 'reload_default_image_sizes':

							$opts     =& $this->p->options;	// Update the existing options array.
							$def_opts = $this->p->opt->get_defaults();
							$img_opts = SucomUtil::preg_grep_keys( '/_img_(width|height|crop|crop_x|crop_y)$/', $def_opts );
							$opts     = array_merge( $this->p->options, $img_opts );

							$this->p->opt->save_options( WPSSO_OPTIONS_NAME, $opts );

							$this->p->notice->upd( __( 'Image size settings have been reloaded with their default values and saved.',
								'wpsso' ) );

							break;

						case 'export_plugin_settings_json':

							$this->export_plugin_settings_json();

							break;

						case 'import_plugin_settings_json':

							$this->import_plugin_settings_json();

							break;

						default:

							do_action( $this->p->lca . '_load_setting_page_' . $action_value,
								$this->pagehook, $this->menu_id, $this->menu_name, $this->menu_lib );

							break;
					}
				}
			}

			$this->add_footer_hooks();	// Include add-on name and version to settings page footer.
			$this->add_plugin_hooks();
			$this->add_side_meta_boxes();	// Add side metaboxes before main metaboxes.
			$this->add_meta_boxes();	// Add last to move any duplicate side metaboxes.
		}

		protected function add_footer_hooks() {

			add_filter( 'admin_footer_text', array( $this, 'admin_footer_ext' ) );
			add_filter( 'update_footer', array( $this, 'admin_footer_host' ) );
		}

		/**
		 * This method is extended by each submenu page.
		 */
		protected function add_plugin_hooks() {
		}

		protected function add_side_meta_boxes() {

			if ( ! self::$pkg[ $this->p->lca ][ 'pp' ] ) {

				$metabox_id      = 'purchase_pro';
				$metabox_title   = _x( 'Pro Version Available', 'metabox title', 'wpsso' );
				$metabox_screen  = $this->pagehook;
				$metabox_context = 'side_fixed';
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback function / method.
				);

				add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
					array( $this, 'show_metabox_purchase_pro' ), $metabox_screen,
						$metabox_context, $metabox_prio, $callback_args );

				$metabox_id      = 'status_pro';
				$metabox_title   = _x( 'Pro Version Features', 'metabox title', 'wpsso' );
				$metabox_screen  = $this->pagehook;
				$metabox_context = 'side';
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback function / method.
				);

				add_meta_box( $this->pagehook . '_' . $metabox_id, $metabox_title,
					array( $this, 'show_metabox_status_pro' ), $metabox_screen,
						$metabox_context, $metabox_prio, $callback_args );

				WpssoUser::reset_metabox_prefs( $this->pagehook, array( 'purchase_pro' ), '', '', true );
			}
		}

		/**
		 * This method is extended by each submenu page.
		 */
		protected function add_meta_boxes() {
		}

		/**
		 * This method is extended by each submenu page.
		 */
		protected function get_table_rows( $metabox_id, $tab_key ) {
		}

		/**
		 * Called from the add_meta_boxes() method in specific settings pages (essential, general, etc.).
		 */
		protected function maybe_show_language_notice() {

			$current_locale = SucomUtil::get_locale( 'current' );
			$default_locale = SucomUtil::get_locale( 'default' );

			if ( $current_locale && $default_locale && $current_locale !== $default_locale ) {

				$notice_key = $this->menu_id . '-language-notice-current-' . $current_locale . '-default-' . $default_locale;

				$this->p->notice->inf( sprintf( __( 'Please note that your current language is different from the default site language (%s).', 'wpsso' ), $default_locale ) . ' ' . sprintf( __( 'Localized option values (%s) are used for webpages and content in that language only (not for the default language, or any other language).', 'wpsso' ), $current_locale ), null, $notice_key, true );
			}
		}

		public function show_setting_page() {

			if ( ! $this->is_setting() ) {
				settings_errors( WPSSO_OPTIONS_NAME );
			}

			$menu_ext = $this->menu_ext;	// Lowercase acronyn for plugin or add-on.

			if ( empty( $menu_ext ) ) {
				$menu_ext = $this->p->lca;
			}

			$this->get_form_object( $menu_ext );

			echo '<div class="wrap" id="' . $this->pagehook . '">' . "\n";
			echo '<h1>';
			echo self::$pkg[ $this->menu_ext ][ 'short' ] . ' ';
			echo '<span class="qualifier">&ndash; ';
			echo $this->menu_name;
			echo '</span></h1>' . "\n";

			if ( ! self::$pkg[ $this->p->lca ][ 'pp' ] ) {

				echo '<div id="poststuff" class="metabox-holder has-right-sidebar">' . "\n";
				echo '<div id="side-info-column" class="inner-sidebar">' . "\n";

				do_meta_boxes( $this->pagehook, $context = 'side_top', $object = null );
				do_meta_boxes( $this->pagehook, $context = 'side_fixed', $object = null );
				do_meta_boxes( $this->pagehook, $context = 'side', $object = null );

				echo '</div><!-- #side-info-column -->' . "\n";
				echo '<div id="post-body" class="has-sidebar">' . "\n";
				echo '<div id="post-body-content" class="has-sidebar-content">' . "\n";

			} else {

				echo '<div id="poststuff" class="metabox-holder no-right-sidebar">' . "\n";
				echo '<div id="post-body" class="no-sidebar">' . "\n";
				echo '<div id="post-body-content" class="no-sidebar-content">' . "\n";
			}

			$this->show_form_content(); ?>

						</div><!-- #post-body-content -->
					</div><!-- #post-body -->
				</div><!-- #poststuff -->
			</div><!-- .wrap -->
			<script type="text/javascript">
				jQuery( document ).ready(
					function( $ ) {
						// close postboxes that should be closed
						$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
						// postboxes setup
						postboxes.add_postbox_toggles( '<?php echo $this->pagehook; ?>' );
					}
				);
			</script>
			<?php
		}

		public function profile_updated_redirect( $url, $status ) {

			if ( false !== strpos( $url, 'updated=' ) && strpos( $url, 'wp_http_referer=' ) ) {

				/**
				 * Match WordPress behavior (users page for admins, profile page for everyone else).
				 */
				$menu_lib      = current_user_can( 'list_users' ) ? 'users' : 'profile';
				$parent_slug   = $this->p->cf[ 'wp' ][ 'admin' ][ $menu_lib ][ 'page' ];
				$referer_match = '/' . $parent_slug . '?page=' . $this->p->lca . '-';

				parse_str( parse_url( $url, PHP_URL_QUERY ), $parts );

				if ( strpos( $parts[ 'wp_http_referer' ], $referer_match ) ) {

					// translators: please ignore - translation uses a different text domain
					$this->p->notice->upd( __( 'Profile updated.' ) );

					$url = add_query_arg( 'updated', true, $parts[ 'wp_http_referer' ] );
				}
			}

			return $url;
		}

		protected function show_form_content() {

			$menu_hookname = SucomUtil::sanitize_hookname( $this->menu_id );

			if ( $this->menu_lib === 'profile' ) {

				$user_id       = get_current_user_id();
				$user_obj      = get_user_to_edit( $user_id );
				$admin_color   = get_user_option( 'admin_color', $user_id );

				if ( empty( $admin_color ) ) {
					$admin_color = 'fresh';
				}

				/**
				 * Match WordPress behavior (users page for admins, profile page for everyone else).
				 */
				$referer_admin_url = current_user_can( 'list_users' ) ?
					$this->p->util->get_admin_url( $this->menu_id, null, 'users' ) :
					$this->p->util->get_admin_url( $this->menu_id, null, $this->menu_lib );

				echo '<form name="' . $this->p->lca . '" ' .
					'id="' . $this->p->lca . '_setting_form_' . $menu_hookname . '" ' .
					'action="user-edit.php" method="post">' . "\n";

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

			} elseif ( $this->menu_lib === 'setting' || $this->menu_lib === 'submenu' ) {

				echo '<form name="' . $this->p->lca . '" ' .
					'id="' . $this->p->lca . '_setting_form_' . $menu_hookname . '" ' .
					'action="options.php" method="post">' . "\n";

				settings_fields( $this->p->lca . '_setting' );

			} elseif ( $this->menu_lib === 'sitesubmenu' ) {

				echo '<form name="' . $this->p->lca . '" ' .
					'id="' . $this->p->lca . '_setting_form_' . $menu_hookname . '" ' .
					'action="edit.php?action=' . WPSSO_SITE_OPTIONS_NAME . '" method="post">' . "\n";

				echo '<input type="hidden" name="page" value="' . $this->menu_id . '" />' . "\n";

			} else {
				return;
			}

			echo "\n" . '<!-- ' . $this->p->lca . ' nonce fields -->' . "\n";

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			echo "\n";

			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

			echo "\n";

			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			echo "\n";

			do_meta_boxes( $this->pagehook, $context = 'normal', $object = null );

			do_action( $this->p->lca . '_form_content_metaboxes_' . $menu_hookname, $this->pagehook );

			if ( $this->menu_lib === 'profile' ) {
				echo $this->get_form_buttons( _x( 'Save All Profile Settings', 'submit button', 'wpsso' ) );
			} else {
				echo $this->get_form_buttons();
			}

			echo '</form>', "\n";
		}

		protected function get_form_buttons( $submit_label_transl = '' ) {

			if ( empty( $submit_label_transl ) ) {
				$submit_label_transl = _x( 'Save All Plugin Settings', 'submit button', 'wpsso' );
			}

			$view_next_key     = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf[ 'form' ][ 'show_options' ] );
			$view_name_transl  = _x( $this->p->cf[ 'form' ][ 'show_options' ][ $view_next_key ], 'option value', 'wpsso' );
			$view_label_transl = sprintf( _x( 'View "%s" by Default', 'submit button', 'wpsso' ), $view_name_transl );

			/**
			 * A two dimentional array of button rows. The 'submit' button will be assigned a class of 'button-primary',
			 * while all other 1st row buttons will be 'button-secondary button-highlight'. The 2nd+ row buttons will be
			 * assigned a class of 'button-secondary'.
			 */
			$form_button_rows = array(
				array(
					'submit' => $submit_label_transl,
					'change_show_options&show-opts=' . $view_next_key => $view_label_transl,
				),
			);

			$form_button_rows = apply_filters( $this->p->lca . '_form_button_rows',
				$form_button_rows, $this->menu_id, $this->menu_name, $this->menu_lib, $this->menu_ext );

			$row_num = 0;

			$buttons_html = '';

			foreach ( $form_button_rows as $key => $buttons_row ) {

				if ( $row_num >= 2 ) {
					$css_class = 'button-secondary';			// Third+ row.
				} elseif ( $row_num >= 1 ) {
					$css_class = 'button-secondary button-alt';		// Second row.
				} else {
					$css_class = 'button-secondary button-highlight';	// First row.
				}

				$buttons_html .= '<div class="submit-buttons">';

				foreach ( $buttons_row as $action_value => $mixed ) {

					if ( empty( $action_value ) || empty( $mixed ) ) {	// Just in case.
						continue;
					}

					if ( is_string( $mixed ) ) {

						if ( $action_value === 'submit' ) {

							$buttons_html .= '<input type="submit" class="button-primary" value="' . $mixed . '" />' . "\n";

						} else {

							$action_url = $this->p->util->get_admin_url( '?' . $this->p->lca . '-action=' . $action_value );
							$button_url = wp_nonce_url( $action_url, WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

							$buttons_html .= $this->form->get_button( $mixed, $css_class, '', $button_url );
						}

					} elseif ( is_array( $mixed ) ) {

						if ( ! empty( $mixed[ 'html' ] ) ) {
							$buttons_html .= $mixed[ 'html' ];
						}
					}
				}

				$buttons_html .= '</div>';

				$row_num++;
			}

			return $buttons_html;
		}

		public function show_metabox_cache_status() {

			$info           = $this->p->cf[ 'plugin' ][ $this->p->lca ];
			$table_cols     = 3;
			$transient_keys = $this->p->util->get_db_transient_keys();

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox cache-status">';

			echo '<tr><td colspan="' . $table_cols . '"><h4>';
			echo sprintf( __( '%s Database Transients', 'wpsso' ), $info[ 'short' ] );
			echo '</h4></td></tr>';

			echo '<tr>';
			echo '<th class="cache-label"></th>';
			echo '<th class="cache-count">' . __( 'Count', 'wpsso' ) . '</th>';
			echo '<th class="cache-expiration">' . __( 'Expiration', 'wpsso' ) . '</th>';
			echo '</tr>';

			/**
			 * Make sure the "All Transients" count is last.
			 */
			if ( isset( $this->p->cf[ 'wp' ][ 'transient' ][ $this->p->lca . '_' ] ) ) {	
				SucomUtil::move_to_end( $this->p->cf[ 'wp' ][ 'transient' ], $this->p->lca . '_' );
			}

			$short_urls_count  = 0;
			$have_filtered_exp = false;

			foreach ( $this->p->cf[ 'wp' ][ 'transient' ] as $cache_md5_pre => $cache_info ) {

				if ( empty( $cache_info ) ) {
					continue;
				} elseif ( empty( $cache_info[ 'label' ] ) ) {	// Skip cache info without labels.
					continue;
				}

				$cache_text_dom     = empty( $cache_info[ 'text_domain' ] ) ? $this->p->lca : $cache_info[ 'text_domain' ];
				$cache_label_transl = _x( $cache_info[ 'label' ], 'option label', $cache_text_dom );
				$cache_count        = count( preg_grep( '/^' . $cache_md5_pre . '/', $transient_keys ) );
				$cache_opt_key      = isset( $cache_info[ 'opt_key' ] ) ? $cache_info[ 'opt_key' ] : false;
				$cache_exp_secs     = $cache_opt_key && isset( $this->p->options[ $cache_opt_key ] ) ? $this->p->options[ $cache_opt_key ] : 0;
				$cache_exp_html     = $cache_opt_key ? $cache_exp_secs : '';
				
				if ( $cache_md5_pre === $this->p->lca . '_s_' ) {
					$short_urls_count = $cache_count;
				}

				if ( ! empty( $cache_info[ 'filter' ] ) ) {

					$filter_name        = $cache_info[ 'filter' ];
					$cache_exp_filtered = (int) apply_filters( $filter_name, $cache_exp_secs );

					if ( $cache_exp_secs !== $cache_exp_filtered ) {
						$cache_exp_html    = $cache_exp_filtered . ' [F]';	// Show that value has changed.
						$have_filtered_exp = true;
					}
				}

				echo '<th class="cache-label">' . $cache_label_transl . ':</th>';
				echo '<td class="cache-count">' . $cache_count . '</td>';
				echo '<td class="cache-expiration">' . $cache_exp_html . '</td>';
				echo '</tr>' . "\n";
			}

			do_action( $this->p->lca . '_column_metabox_cache_status_table_rows', $table_cols, $this->form, $transient_keys );

			if ( $have_filtered_exp ) {
				if ( self::$pkg[ $this->p->lca ][ 'pp' ] ) {
					echo '<tr><td></td></tr>' . "\n";
					echo '<tr><td colspan="' . $table_cols . '"><p><small>[F] ' .
						__( 'The expiration option value is modified by a filter.',
							'wpsso' ) . '</small></p></td></tr>' . "\n";
				}
			}

			echo '</table>';
		}

		public function show_metabox_version_info() {

			$table_cols  = 2;
			$label_width = '25%';

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox version-info" style="table-layout:fixed;">';

			/**
			 * Required for chrome to display a fixed table layout.
			 */
			echo '<colgroup>';
			echo '<col style="width:' . $label_width . ';"/>';
			echo '<col/>';
			echo '</colgroup>';

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// Only active add-ons.
					continue;
				}

				$installed_version = isset( $info[ 'version' ] ) ? $info[ 'version' ] : ''; // Static value from config.
				$installed_style   = '';
				$stable_version    = __( 'Not Available', 'wpsso' ); // Default value.
				$latest_version    = __( 'Not Available', 'wpsso' ); // Default value.
				$latest_notice     = '';
				$changelog_url     = isset( $info[ 'url' ][ 'changelog' ] ) ? $info[ 'url' ][ 'changelog' ] : '';
				$readme_info       = $this->get_readme_info( $ext, true ); // $read_cache is true.

				if ( ! empty( $readme_info[ 'stable_tag' ] ) ) {

					$stable_version = $readme_info[ 'stable_tag' ];
					$newer_avail = version_compare( $installed_version, $stable_version, '<' );

					if ( is_array( $readme_info[ 'upgrade_notice' ] ) ) {

						/**
						 * Hooked by the update manager to apply the version filter.
						 */
						$upgrade_notice = apply_filters( $this->p->lca . '_readme_upgrade_notices', $readme_info[ 'upgrade_notice' ], $ext );

						if ( ! empty( $upgrade_notice ) ) {

							reset( $upgrade_notice );

							$latest_version = key( $upgrade_notice );
							$latest_notice  = $upgrade_notice[ $latest_version ];
						}
					}

					/**
					 * Hooked by the update manager to check installed version against the latest version, 
					 * if a non-stable filter is selected for that plugin / add-on.
					 */
					if ( apply_filters( $this->p->lca . '_newer_version_available',
						$newer_avail, $ext, $installed_version, $stable_version, $latest_version ) ) {

						$installed_style = 'style="background-color:#f00;"';	// red

					} elseif ( preg_match( '/[a-z]/', $installed_version ) ) {	// current but not stable (alpha chars in version)

						$installed_style = 'style="background-color:#ff0;"';	// yellow
					} else {
						$installed_style = 'style="background-color:#0f0;"';	// green
					}
				}

				echo '<tr><td colspan="' . $table_cols . '"><h4>' . $info[ 'name' ] . '</h4></td></tr>';

				echo '<tr><th class="version-label">' . _x( 'Installed', 'option label', 'wpsso' ) . ':</th>
					<td class="version-number" ' . $installed_style . '>' . $installed_version . '</td></tr>';

				echo '<tr><th class="version-label">' . _x( 'Stable', 'option label', 'wpsso' ) . ':</th>
					<td class="version-number">' . $stable_version . '</td></tr>';

				echo '<tr><th class="version-label">' . _x( 'Latest', 'option label', 'wpsso' ) . ':</th>
					<td class="version-number">' . $latest_version . '</td></tr>';

				echo '<tr><td colspan="' . $table_cols . '" class="latest-notice">' .
					( empty( $latest_notice ) ? '' : '<p><em><strong>Version ' .
						$latest_version . '</strong> ' . $latest_notice . '</em></p>' ).
					'<p><a href="' . $changelog_url . '">' . sprintf( __( 'View %s changelog...',
						'wpsso' ), $info[ 'short' ] ) . '</a></p></td></tr>';
			}

			do_action( $this->p->lca . '_column_metabox_version_info_table_rows', $table_cols, $this->form );

			echo '</table>';
		}

		public function show_metabox_status_gpl() {

			$ext_num    = 0;
			$table_cols = 3;

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox module-status">';

			/**
			 * GPL version features
			 */
			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $info[ 'lib' ][ 'gpl' ] ) ) {
					continue;
				}

				$ext_num++;

				if ( $ext === $this->p->lca ) {	// features for this plugin
					$features = array(
						'(feature) Debug Logging Enabled' => array(
							'classname' => 'SucomDebug',
						),
						'(feature) Use Filtered (SEO) Title' => array(
							'status' => $this->p->options[ 'plugin_filter_title' ] ? 'on' : 'off',
						),
						'(feature) Use WordPress Content Filters' => array(
							'status' => $this->p->options[ 'plugin_filter_content' ] ? 'on' : 'rec',
						),
						'(feature) Use WordPress Excerpt Filters' => array(
							'status' => $this->p->options[ 'plugin_filter_excerpt' ] ? 'on' : 'off',
						),
						'(code) Facebook / Open Graph Meta Tags' => array(
							'status' => class_exists( $this->p->lca . 'opengraph' ) ? 'on' : 'rec',
						),
						'(code) Knowledge Graph Person Markup' => array(
							'status' => $this->p->options[ 'schema_add_home_person' ] ? 'on' : 'off',
						),
						'(code) Knowledge Graph Organization Markup' => array(
							'status' => $this->p->options[ 'schema_add_home_organization' ] ? 'on' : 'off',
						),
						'(code) Knowledge Graph WebSite Markup' => array(
							'status' => $this->p->options[ 'schema_add_home_website' ] ? 'on' : 'rec',
						),
						'(code) Schema Meta Property Containers' => array(
							'status' => $this->p->schema->is_noscript_enabled() ? 'on' : 'off',
						),
						'(code) Twitter Card Meta Tags' => array(
							'status' => class_exists( $this->p->lca . 'twittercard' ) ? 'on' : 'rec',
						),
					);
				} else {
					$features = array();
				}

				self::$pkg[ $ext ][ 'purchase' ] = '';

				$features = apply_filters( $ext . '_status_gpl_features', $features, $ext, $info, self::$pkg[ $ext ] );

				if ( ! empty( $features ) ) {

					echo '<tr><td colspan="' . $table_cols . '">';
					echo '<h4' . ( $ext_num > 1 ? ' style="margin-top:10px;"' : '' ) . '>';
					echo $info[ 'name' ];
					echo '</h4></td></tr>';

					$this->show_plugin_status( $ext, $info, $features );
				}
			}

			echo '</table>';
		}

		public function show_metabox_status_pro() {

			$ext_num = 0;

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox module-status">';

			/**
			 * Pro version features.
			 */
			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! isset( $info[ 'lib' ][ 'pro' ] ) ) {
					continue;
				}

				$ext_num++;

				$features = array();

				if ( ! empty( $info[ 'url' ][ 'purchase' ] ) ) {
					self::$pkg[ $ext ][ 'purchase' ] = add_query_arg( 'utm_source', 'status-pro-feature', $info[ 'url' ][ 'purchase' ] );
				} else {
					self::$pkg[ $ext ][ 'purchase' ] = '';
				}

				foreach ( $info[ 'lib' ][ 'pro' ] as $sub => $libs ) {

					if ( $sub === 'admin' ) {	// Skip status for admin menus and tabs.
						continue;
					}

					foreach ( $libs as $id_key => $label ) {

						/**
						 * Example:
						 *	'article'              => 'Schema Type Article',
						 *	'article#news:no_load' => 'Schema Type NewsArticle',
						 *	'article#tech:no_load' => 'Schema Type TechArticle',
						 */
						list( $id, $stub, $action ) = SucomUtil::get_lib_stub_action( $id_key );

						$classname  = SucomUtil::sanitize_classname( $ext . 'pro' . $sub . $id, $allow_underscore = false );
						$status_off = $this->p->avail[ $sub ][ $id ] ? 'rec' : 'off';

						$features[ $label ] = array(
							'sub'      => $sub,
							'lib'      => $id,
							'stub'     => $stub,
							'action'   => $action,
							'td_class' => self::$pkg[ $ext ][ 'pp' ] ? '' : 'blank',
							'purchase' => self::$pkg[ $ext ][ 'purchase' ],
							'status'   => class_exists( $classname ) ? ( self::$pkg[ $ext ][ 'pp' ] ? 'on' : $status_off ) : $status_off,
						);
					}
				}

				$features = apply_filters( $ext . '_status_pro_features', $features, $ext, $info, self::$pkg[ $ext ] );

				if ( ! empty( $features ) ) {

					echo '<tr><td colspan="3">';
					echo '<h4' . ( $ext_num > 1 ? ' style="margin-top:10px;"' : '' ) . '>';
					echo $info[ 'name' ];
					echo '</h4></td></tr>';

					$this->show_plugin_status( $ext, $info, $features );
				}
			}

			echo '</table>';
		}

		private function show_plugin_status( &$ext = '', &$info = array(), &$features = array() ) {

			$status_info = array(
				'on' => array(
					'img' => 'green-circle.png',
					'title' => __( 'Feature is enabled.', 'wpsso' ),
				),
				'off' => array(
					'img' => 'gray-circle.png',
					'title' => __( 'Feature is disabled.', 'wpsso' ),
				),
				'rec' => array(
					'img' => 'red-circle.png',
					'title' => __( 'Feature is recommended but disabled.', 'wpsso' ),
				),
			);

			uksort( $features, array( __CLASS__, 'sort_plugin_features' ) );

			foreach ( $features as $label => $arr ) {

				if ( isset( $arr[ 'classname' ] ) ) {
					$status_key = class_exists( $arr[ 'classname' ] ) ? 'on' : 'off';
				} elseif ( isset( $arr[ 'constant' ] ) ) {
					$status_key = SucomUtil::get_const( $arr[ 'constant' ] ) ? 'on' : 'off';
				} elseif ( isset( $arr[ 'status' ] ) ) {
					$status_key = $arr[ 'status' ];
				} else {
					$status_key = '';
				}

				if ( ! empty( $status_key ) ) {

					$td_class     = empty( $arr[ 'td_class' ] ) ? '' : ' ' . $arr[ 'td_class' ];
					$icon_type    = preg_match( '/^\(([a-z\-]+)\) (.*)/', $label, $match ) ? $match[1] : 'admin-generic';
					$icon_title   = __( 'Generic feature module', 'wpsso' );
					$label_text   = empty( $match[2] ) ? $label : $match[2];
					$label_text   = empty( $arr[ 'label' ] ) ? $label_text : $arr[ 'label' ];
					$purchase_url = $status_key === 'rec' && ! empty( $arr[ 'purchase' ] ) ? $arr[ 'purchase' ] : '';

					switch ( $icon_type ) {

						case 'api':

							$icon_type  = 'cloud';
							$icon_title = __( 'Service API module', 'wpsso' );

							break;

						case 'code':

							$icon_type  = 'editor-code';
							$icon_title = __( 'Meta tag and markup module', 'wpsso' );

							break;

						case 'plugin':

							$icon_type  = 'admin-plugins';
							$icon_title = __( 'Plugin integration module', 'wpsso' );

							break;

						case 'sharing':

							$icon_type  = 'screenoptions';
							$icon_title = __( 'Sharing functionality module', 'wpsso' );

							break;

						case 'tool':	// Deprecated on 2018/10/02.
						case 'feature':

							$icon_type  = 'admin-generic';
							$icon_title = __( 'Additional functionality module', 'wpsso' );

							break;
					}

					echo '<tr>' .
					'<td><span class="dashicons dashicons-' . $icon_type . '" title="' . $icon_title . '"></span></td>' .
					'<td class="' . trim( $td_class ) . '">' . $label_text . '</td>' .
					'<td>' .
						( $purchase_url ? '<a href="' . $purchase_url . '">' : '' ).
						'<img src="' . WPSSO_URLPATH . 'images/' .
							$status_info[ $status_key ][ 'img' ] . '" width="12" height="12" title="' .
							$status_info[ $status_key ][ 'title' ] . '"/>' .
						( $purchase_url ? '</a>' : '' ).
					'</td>' .
					'</tr>' . "\n";
				}
			}
		}

		private static function sort_plugin_features( $feature_a, $feature_b ) {

			return strcasecmp( self::feature_priority( $feature_a ), self::feature_priority( $feature_b ) );
		}

		private static function feature_priority( $feature ) {

			if ( strpos( $feature, '(feature)' ) === 0 ) {
				return '(10) ' . $feature;
			} else {
				return $feature;
			}
		}

		public function show_metabox_purchase_pro() {

			$info =& $this->p->cf[ 'plugin' ][ $this->p->lca ];

			if ( ! empty( $info[ 'url' ][ 'purchase' ] ) ) {
				$purchase_url = add_query_arg( 'utm_source', 'column-purchase-pro', $info[ 'url' ][ 'purchase' ] );
			} else {
				$purchase_url = '';
			}

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox"><tr><td>';

			echo '<div class="column-metabox-icon">';
			echo $this->get_ext_img_icon( $this->p->lca );
			echo '</div>';

			echo '<div class="column-metabox-content has-buttons">';
			echo $this->p->msgs->get( 'column-purchase-pro' );
			echo '</div>';

			echo '<div class="column-metabox-buttons">';
			echo $this->form->get_button( _x( 'Purchase Pro Version', 'submit button', 'wpsso' ),
				'button-primary', 'column-purchase-pro', $purchase_url, true );
			echo '</div>';

			echo '</td></tr></table>';
		}

		public function show_metabox_help_support() {

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox"><tr><td>';

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// filter out add-ons that are not installed
					continue;
				}

				$action_links = array();

				if ( ! empty( $info[ 'url' ][ 'faqs' ] ) ) {
					$action_links[] = sprintf( __( '<a href="%s">Frequently Asked Questions</a>', 'wpsso' ), $info[ 'url' ][ 'faqs' ] );
				}
						
				if ( ! empty( $info[ 'url' ][ 'notes' ] ) ) {
					$action_links[] = sprintf( __( '<a href="%s">Advanced Documentation and Notes</a>', 'wpsso' ), $info[ 'url' ][ 'notes' ] );
				}

				if ( ! empty( $info[ 'url' ][ 'support' ] ) && self::$pkg[ $ext ][ 'pp' ] ) {

					$action_links[] = sprintf( __( '<a href="%s">Priority Support Ticket</a>', 'wpsso' ), $info[ 'url' ][ 'support' ] ) .
						' (' . __( 'Pro version', 'wpsso' ) . ')';

				} elseif ( ! empty( $info[ 'url' ][ 'forum' ] ) ) {

					$action_links[] = sprintf( __( '<a href="%s">Community Support Forum</a>', 'wpsso' ), $info[ 'url' ][ 'forum' ] );
				}

				if ( ! empty( $action_links ) ) {
					echo '<h4>' . $info[ 'name' ] . '</h4>' . "\n";
					echo '<ul><li>' . implode( '</li><li>', $action_links ) . '</li></ul>' . "\n";
				}
			}

			echo '</td></tr></table>';
		}

		public function show_metabox_rate_review() {

			echo '<table class="sucom-settings ' . $this->p->lca . ' column-metabox"><tr><td>';
			echo $this->p->msgs->get( 'column-rate-review' );
			echo '<h4>' . __( 'Rate these plugins', 'option label', 'wpsso' ) . ':</h4>' . "\n";

			$action_links = array();

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( empty( $info[ 'version' ] ) ) {	// filter out add-ons that are not installed
					continue;
				}

				if ( ! empty( $info[ 'url' ][ 'review' ] ) ) {
					$action_links[] = '<a href="' . $info[ 'url' ][ 'review' ] . '">' . $info[ 'name' ] . '</a>';
				}
			}

			if ( ! empty( $action_links ) ) {
				echo '<ul><li>' . implode( '</li><li>', $action_links ) . '</li></ul>' . "\n";
			}

			echo '</td></tr></table>';
		}

		/**
		 * Call as WpssoAdmin::get_nonce_action() to have a reliable __METHOD__ value.
		 */
		public static function get_nonce_action() {

			$salt = __FILE__.__METHOD__.__LINE__;

			foreach ( array( 'AUTH_SALT', 'NONCE_SALT' ) as $const ) {
				$salt .= defined( $const ) ? constant( $const ) : '';
			}

			return md5( $salt );
		}

		private function is_profile( $menu_id = false ) {

			return $this->is_lib( 'profile', $menu_id );
		}

		private function is_setting( $menu_id = false ) {

			return $this->is_lib( 'setting', $menu_id );
		}

		private function is_submenu( $menu_id = false ) {

			return $this->is_lib( 'submenu', $menu_id );
		}

		private function is_sitesubmenu( $menu_id = false ) {

			return $this->is_lib( 'sitesubmenu', $menu_id );
		}

		private function is_lib( $lib_name, $menu_id = false ) {

			if ( false === $menu_id ) {
				$menu_id = $this->menu_id;
			}

			return isset( $this->p->cf[ '*' ][ 'lib' ][ $lib_name ][ $menu_id ] ) ? true : false;
		}

		public function addons_metabox_content( $network = false ) {

			$ext_sorted = WpssoConfig::get_ext_sorted();

			unset( $ext_sorted[ $this->p->lca ] );

			$tabindex  = 0;
			$ext_num   = 0;
			$ext_total = count( $ext_sorted );
			$charset   = get_bloginfo( 'charset' );

			echo '<table class="sucom-settings ' . $this->p->lca . ' addons-metabox" style="padding-bottom:10px">' . "\n";

			foreach ( $ext_sorted as $ext => $info ) {

				$ext_num++;
				$ext_links  = $this->get_ext_action_links( $ext, $info, $tabindex );
				$table_rows = array();

				/**
				 * Plugin name, description, and links.
				 */
				$plugin_name_html = '<h4>' . $info[ 'name' ] . '</h4>';

				$plugin_desc_html = empty( $info[ 'desc' ] ) ?
					'' : '<p>' . htmlentities( _x( $info[ 'desc' ], 'plugin description', 'wpsso' ),
						ENT_QUOTES, $charset, false ) . '</p>';

				$table_rows[ 'plugin_name' ] = '<td class="ext-info-plugin-name" id="ext-info-plugin-name-' . $ext . '">' .
					$plugin_name_html . $plugin_desc_html . ( empty( $ext_links ) ? '' : '<div class="row-actions visible">' .
						implode( ' | ', $ext_links ) . '</div>' ) . '</td>';

				/**
				 * Plugin separator.
				 */
				if ( $ext_num < $ext_total ) {
					$table_rows[ 'dotted_line' ] = '<td class="ext-info-plugin-separator"></td>';
				} else {
					$table_rows[] = '<td></td>';
				}

				/**
				 * Show the plugin icon and table rows.
				 */
				foreach ( $table_rows as $key => $row ) {

					echo '<tr>';

					if ( $key === 'plugin_name' ) {

						$span_rows = count( $table_rows );

						echo '<td class="ext-info-plugin-icon" id="ext-info-plugin-icon-' . $ext . '"' .
							' width="168" rowspan="' . $span_rows . '" valign="top" align="left">' . "\n";
						echo '<a class="ext-anchor" id="' . $ext . '"></a>' . "\n";	// Add an anchor for the add-on.
						echo $this->get_ext_img_icon( $ext );
						echo '</td>';
					}

					echo $row . '</tr>' . "\n";
				}
			}

			echo '</table>' . "\n";
		}

		public function licenses_metabox_content( $network = false ) {

			$ext_sorted = WpssoConfig::get_ext_sorted();

			foreach ( $ext_sorted as $ext => $info ) {
				if ( empty( $info[ 'update_auth' ] ) ) {	// Only show plugins with Pro versions.
					unset( $ext_sorted[ $ext ] );
				}
			}

			$tabindex  = 0;
			$ext_num   = 0;
			$ext_total = count( $ext_sorted );
			$charset   = get_bloginfo( 'charset' );

			echo '<table class="sucom-settings ' . $this->p->lca . ' licenses-metabox" style="padding-bottom:10px">' . "\n";
			echo '<tr><td colspan="3">' . $this->p->msgs->get( 'info-plugin-tid' . ( $network ? '-network' : '' ) ) . '</td></tr>' . "\n";

			foreach ( $ext_sorted as $ext => $info ) {

				$ext_num++;
				$ext_links = $this->get_ext_action_links( $ext, $info, $tabindex );
				$table_rows = array();

				/**
				 * Plugin Name, Description, and Links
				 */
				$plugin_name_html = '<h4>' . $info[ 'name' ] . '</h4>';

				$table_rows[ 'plugin_name' ] = '<td colspan="2" class="ext-info-plugin-name" id="ext-info-plugin-name-' . $ext . '">' .
					$plugin_name_html . ( empty( $ext_links ) ? '' : '<div class="row-actions visible">' .
						implode( ' | ', $ext_links ) . '</div>' ) . '</td>';

				/**
				 * Plugin authentication ID and license information.
				 */
				$table_rows[ 'plugin_tid' ] = $this->form->get_th_html( sprintf( _x( '%s Authentication ID',
					'option label', 'wpsso' ), $info[ 'short' ] ), 'medium nowrap' );

				if ( $this->p->lca === $ext || self::$pkg[ $this->p->lca ][ 'pp' ] ) {

					$table_rows[ 'plugin_tid' ] .= '<td width="100%">' .
						$this->form->get_input( 'plugin_' . $ext . '_tid', 'tid mono', '', 0, 
							'', false, ++$tabindex ) . '</td>';

					if ( $network ) {

						$table_rows[ 'site_use' ] = self::get_option_site_use( 'plugin_' . $ext . '_tid', $this->form, $network, true );

					} elseif ( class_exists( 'SucomUpdate' ) ) {	// Required to use SucomUpdate::get_option().

						$show_update_opts = array(
							//'exp_date' => _x( 'Support and Updates Expire', 'option label', 'wpsso' ),
							'qty_used' => _x( 'License Information', 'option label', 'wpsso' ),
						);

						foreach ( $show_update_opts as $key => $label ) {

							$val = SucomUpdate::get_option( $ext, $key );

							if ( empty( $val ) ) {	// Add an empty row for empty values.
								
								$label = $val = '&nbsp;';

							} elseif ( $key === 'exp_date' ) {

								if ( $val === '0000-00-00 00:00:00' ) {
									$val = _x( 'Never', 'option value', 'wpsso' );
								}

							} elseif ( $key === 'qty_used' ) {

								/**
								 * The default 'qty_used' value is a 'n/n' string.
								 */
								$val = sprintf( __( '%s site addresses registered', 'wpsso' ), $val );

								/**
								 * Use a better '# of #' string translation if possible.
								 */
								if ( version_compare( WpssoUmConfig::get_version(), '1.10.1', '>=' ) ) {

									$qty_reg   = SucomUpdate::get_option( $ext, 'qty_reg' );
									$qty_total = SucomUpdate::get_option( $ext, 'qty_total' );

									if ( $qty_reg !== null && $qty_total !== null ) {
										$val = sprintf( __( '%d of %d site addresses registered', 'wpsso' ),
											$qty_reg, $qty_total );
									}
								}

								/**
								 * Add a license information link (thickbox). 
								 */
								if ( ! empty( $info[ 'url' ][ 'info' ] ) ) {

									$locale = is_admin() && function_exists( 'get_user_locale' ) ?
										get_user_locale() : get_locale();

									$info_url = add_query_arg( array(
										'tid'       => $this->p->options[ 'plugin_' . $ext . '_tid' ],
										'locale'    => $locale,
										'TB_iframe' => 'true',
										'width'     => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'width' ],
										'height'    => $this->p->cf[ 'wp' ][ 'tb_iframe' ][ 'height' ],
									), $info[ 'url' ][ 'purchase' ] . 'info/' );

									$val = '<a href="' . $info_url . '" class="thickbox">' . $val . '</a>';
								}
							}

							$table_rows[ $key ] = '<th class="medium nowrap">' . $label . '</th><td width="100%">' . $val . '</td>';
						}

					} else {

						$table_rows[] = '<th class="medium nowrap">&nbsp;</th><td width="100%">&nbsp;</td>';
					}

				} else {

					$table_rows[ 'plugin_tid' ] .= '<td width="100%" class="blank">' .
						( empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) ?
							$this->form->get_no_input( 'plugin_' . $ext . '_tid', 'tid mono' ) :
							$this->form->get_input( 'plugin_' . $ext . '_tid', 'tid mono', '', 0, '', false, ++$tabindex ) ) .
						'</td>';

					$table_rows[] = '<th class="medium nowrap">&nbsp;</th><td width="100%">&nbsp;</td>';
				}

				/**
				 * Plugin separator.
				 */
				if ( $ext_num < $ext_total ) {
					$table_rows[ 'dotted_line' ] = '<td colspan="2" class="ext-info-plugin-separator"></td>';
				} else {
					$table_rows[] = '<td></td>';
				}

				/**
				 * Show the plugin icon and table rows.
				 */
				foreach ( $table_rows as $key => $row ) {

					echo '<tr>';

					if ( $key === 'plugin_name' ) {

						$span_rows = count( $table_rows );

						echo '<td class="ext-info-plugin-icon" id="ext-info-plugin-icon-' . $ext . '"' .
							' width="168" rowspan="' . $span_rows . '" valign="top" align="left">' . "\n";
						echo $this->get_ext_img_icon( $ext );
						echo '</td>';
					}

					echo $row . '</tr>';
				}
			}

			echo '</table>' . "\n";
		}

		public function add_admin_tb_notices_menu_item( $wp_admin_bar ) {

			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			$menu_icon  = '<span class="ab-icon" id="' . $this->p->lca . '-toolbar-notices-icon"></span>';
			$menu_count = '<span id="' . $this->p->lca . '-toolbar-notices-count">0</span>';

			$no_notices_text = sprintf( __( 'No new %s notifications.', 'wpsso' ), $this->p->cf[ 'menu' ][ 'title' ] );

			$wp_admin_bar->add_node( array(	// Since wp 3.1
				'id'     => $this->p->lca . '-toolbar-notices',
				'title'  => $menu_icon . $menu_count,
				'parent' => false,
				'href'   => false,
				'group'  => false,
				'meta'   => array(),
			) );

			$wp_admin_bar->add_node( array(
				'id'     => $this->p->lca . '-toolbar-notices-container',
				'title'  => $no_notices_text,
				'parent' => $this->p->lca . '-toolbar-notices',
				'href'   => false,
				'group'  => false,
				'meta'   => array(),
			) );
		}

		public function conflict_warnings() {

			if ( ! is_admin() ) { 	// Just in case.
				return;
			}

			$this->conflict_check_db();
			$this->conflict_check_php();
			$this->conflict_check_wp();
			$this->conflict_check_seo();
		}

		private function conflict_check_db() {

			global $wpdb;

			$query = 'SHOW VARIABLES LIKE "%s";';
			$args  = array( 'max_allowed_packet' );

			$query = $wpdb->prepare( $query, $args );

			/**
			 * OBJECT_K returns an associative array of objects.
			 */
			$result = $wpdb->get_results( $query, OBJECT_K );

			/**
			 * https://dev.mysql.com/doc/refman/8.0/en/program-variables.html
			 * https://dev.mysql.com/doc/refman/8.0/en/packet-too-large.html
			 */
			if ( isset( $result[ 'max_allowed_packet' ]->Value ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'db max_allowed_packet value is "' . $result[ 'max_allowed_packet' ]->Value . '"' );
				}

				$min_bytes = 1 * 1024 * 1024;	// 1MB in bytes.
				$def_bytes = 16 * 1024 * 1024;	// 16MB in bytes.

				if ( $result[ 'max_allowed_packet' ]->Value < $min_bytes ) {

					$error_msg = sprintf( __( 'Your database is configured for a "%1$s" size of %2$d bytes, which is less than the recommended minimum value of %3$d bytes (a common default value is %4$d bytes).', 'wpsso' ), 'max_allowed_packet', $result[ 'max_allowed_packet' ]->Value, $min_bytes, $def_bytes ) . ' ';

					$error_msg .= sprintf( __( 'Please contact your hosting provider and have the "%1$s" database option adjusted to a larger and safer value.', 'wpsso' ), 'max_allowed_packet' ) . ' ';

					$error_msg .= sprintf( __( 'See the %1$s sections %2$s and %3$s for more information on this database option.', 'wpsso' ), 'MySQL 8.0 Reference Manual', '<a href="https://dev.mysql.com/doc/refman/8.0/en/program-variables.html">Using Options to Set Program Variables</a>', '<a href="https://dev.mysql.com/doc/refman/8.0/en/packet-too-large.html">Packet Too Large</a>', 'max_allowed_packet' ) . ' ';

					$this->p->notice->err( $error_msg );
				}
			}
		}

		private function conflict_check_php() {

			/**
			 * Load the WP class libraries to avoid triggering a known bug in EWWW
			 * when applying the 'wp_image_editors' filter.
			 */
			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

			$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );
			$php_extensions  = $this->p->cf[ 'php' ][ 'extensions' ];

			foreach ( $php_extensions as $php_ext => $php_info ) {

				/**
				 * Skip image extensions for WordPress image editors that are not used.
				 */
				if ( ! empty( $php_info[ 'wp_image_editor' ][ 'class' ] ) ) {
					if ( ! in_array( $php_info[ 'wp_image_editor' ][ 'class' ], $implementations ) ) {
						continue;
					}
				}

				$error_msg = '';	// Clear any previous error message.

				/**
				 * Check for the extension first, then maybe check for its functions.
				 */
				if ( ! extension_loaded( $php_ext ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'php ' . $php_ext . ' extension module is not loaded' );
					}

					/**
					 * If this is a WordPress image editing extension, add information about the WordPress image editing class.
					 */
					if ( ! empty( $php_info[ 'wp_image_editor' ][ 'class' ] ) ) {

						/**
						 * If we have a WordPress reference URL for this image editing class, link the image editor class name.
						 */
						if ( ! empty( $php_info[ 'wp_image_editor' ][ 'url' ] ) ) {
							$editor_class = '<a href="' . $php_info[ 'wp_image_editor' ][ 'url' ] . '">' .
								$php_info[ 'wp_image_editor' ][ 'class' ] . '</a>';
						} else {
							$editor_class = $php_info[ 'wp_image_editor' ][ 'class' ];
						}

						$error_msg .= sprintf( __( 'WordPress is configured to use the %1$s image editing class but the <a href="%2$s">PHP %3$s extension module</a> is not loaded:', 'wpsso' ), $editor_class, $php_info[ 'url' ], $php_info[ 'label' ] ) . ' ';

					} else {

						$error_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s extension module</a> is not loaded:', 'wpsso' ),
							$php_info[ 'url' ], $php_info[ 'label' ] ) . ' ';
					}

					/**
					 * Add additional / mode specific information about this check for the hosting provider.
					 */
					$error_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s function</a> for "%3$s" returned false.', 'wpsso' ),
						__( 'https://secure.php.net/manual/en/function.extension-loaded.php', 'wpsso' ),
							'<code>extension_loaded()</code>', $php_ext ) . ' ';


					/**
					 * If we are checking for the ImageMagick PHP extension, make sure the user knows the
					 * difference between the OS package and the PHP extension.
					 */
					if ( $php_ext === 'imagick' ) {
						$error_msg .= sprintf( __( 'Note that the ImageMagick application and the PHP "%1$s" extension are two different products &mdash; this error is for the PHP "%1$s" extension, not the ImageMagick application.', 'wpsso' ), $php_ext ) . ' ';
					}

					$error_msg .= sprintf( __( 'Please contact your hosting provider to have the missing PHP "%1$s" extension installed and enabled.', 'wpsso' ), $php_ext );

				/**
				 * If the PHP extension is loaded, then maybe check to make sure the extension is complete. ;-)
				 */
				} elseif ( ! empty( $php_info[ 'functions' ] ) && is_array( $php_info[ 'functions' ] ) ) {

					foreach ( $php_info[ 'functions' ] as $func_name ) {

						if ( ! function_exists( $func_name ) ) {

							if ( $this->p->debug->enabled ) {
								$this->p->debug->log( 'php ' . $func_name . ' function is missing' );
							}

							$error_msg .= sprintf( __( 'The <a href="%1$s">PHP %2$s extension module</a> is loaded but the %3$s function is missing.', 'wpsso' ), $php_info[ 'url' ], $php_info[ 'label' ], '<code>' . $func_name . '()</code>' ) . ' ';

							$error_msg .= __( 'Please contact your hosting provider to have the missing PHP function installed.', 'wpsso' );
						}
					}
				}

				if ( ! empty( $error_msg ) ) {
					$this->p->notice->err( $error_msg );
				}
			}
		}

		private function conflict_check_wp() {

			if ( ! get_option( 'blog_public' ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'wp blog_public option is disabled' );
				}

				$notice_key = 'wordpress-search-engine-visibility-disabled';

				if ( $this->p->notice->is_admin_pre_notices( $notice_key ) ) { // Don't bother if already dismissed.

					$this->p->notice->warn( sprintf( __( 'The WordPress <a href="%s">Search Engine Visibility</a> option is set to discourage search engine and social crawlers from indexing this site. This is not compatible with the purpose of sharing content on social sites &mdash; please uncheck the option to allow search engines and social crawlers to access your content.', 'wpsso' ), get_admin_url( null, 'options-reading.php' ) ), null, $notice_key, MONTH_IN_SECONDS * 3 );
				}
			}
		}

		private function conflict_check_seo() {

			$err_pre =  __( 'Plugin conflict detected', 'wpsso' ) . ' &mdash; ';
			$log_pre = 'seo plugin conflict detected - ';

			/**
			 * All in One SEO Pack
			 */
			if ( $this->p->avail[ 'seo' ][ 'aioseop' ] ) {

				$opts = get_option( 'aioseop_options' );

				if ( ! empty( $opts[ 'modules' ][ 'aiosp_feature_manager_options' ][ 'aiosp_feature_manager_enable_opengraph' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Social Meta', 'all-in-one-seo-pack' ) . '</strong>';
					$settings_url = get_admin_url( null, 'admin.php?page=all-in-one-seo-pack%2Fmodules%2Faioseop_feature_manager.php' );
					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Feature Manager', 'all-in-one-seo-pack' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'aioseop social meta feature is enabled' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please deactivate the %1$s feature in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				if ( isset( $opts[ 'aiosp_google_disable_profile' ] ) && empty( $opts[ 'aiosp_google_disable_profile' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Disable Google Plus Profile', 'all-in-one-seo-pack' ) . '</strong>';
					$settings_url = get_admin_url( null, 'admin.php?page=all-in-one-seo-pack%2Faioseop_class.php' );
					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'General Settings', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Google Settings', 'all-in-one-seo-pack' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'aioseop google plus profile is enabled' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please check the %1$s option in the %2$s metabox.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				if ( ! empty( $opts[ 'aiosp_schema_markup' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Use Schema.org Markup', 'all-in-one-seo-pack' ) . '</strong>';
					$settings_url = get_admin_url( null, 'admin.php?page=all-in-one-seo-pack%2Faioseop_class.php' );
					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'All in One SEO', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'General Settings', 'all-in-one-seo-pack' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'General Settings', 'all-in-one-seo-pack' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'aioseop schema markup option is checked' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please uncheck the %1$s option in the %2$s metabox.',
						'wpsso' ), $label_transl, $settings_link ) );
				}
			}

			/**
			 * SEO Ultimate
			 */
			if ( $this->p->avail[ 'seo' ][ 'seou' ] ) {

				$opts = get_option( 'seo_ultimate' );

				$settings_url = get_admin_url( null, 'admin.php?page=seo' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'SEO Ultimate', 'seo-ultimate' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Modules', 'seo-ultimate' ) . '</a>';

				if ( ! empty( $opts[ 'modules' ] ) && is_array( $opts[ 'modules' ] ) ) {

					if ( array_key_exists( 'opengraph', $opts[ 'modules' ] ) && $opts[ 'modules' ][ 'opengraph' ] !== -10 ) {

						// translators: please ignore - translation uses a different text domain
						$label_transl = '<strong>' . __( 'Open Graph Integrator', 'seo-ultimate' ) . '</strong>';

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'seo ultimate opengraph module is enabled' );
						}

						$this->p->notice->err( $err_pre . sprintf( __( 'please disable the %1$s module in the %2$s settings.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}
			}

			/**
			 * Squirrly SEO
			 */
			if ( $this->p->avail[ 'seo' ][ 'sq' ] ) {

				$opts = json_decode( get_option( 'sq_options' ), $assoc = true );

				/**
				 * Squirrly SEO > SEO Settings > Social Media > Social Media Options Metabox
				 */
				$settings_url = get_admin_url( null, 'admin.php?page=sq_seo#socials' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'Squirrly', 'squirrly-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Social Media', 'squirrly-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Social Media Options', 'squirrly-seo' ) . '</a>';

				foreach ( array(
					'sq_auto_facebook' => '"<strong>' . __( 'Add the Social Open Graph protocol so that your Facebook shares look good.',
						'wpsso' ) . '</strong>"',
					'sq_auto_twitter' => '"<strong>' . __( 'Add the Twitter card in your tweets.',
						'wpsso' ) . '</strong>"',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'squirrly seo ' . $opt_key . ' option is enabled' );
						}

						$this->p->notice->err( $err_pre . sprintf( __( 'please disable the %1$s option in the %2$s metabox.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				/**
				 * Squirrly SEO > SEO Settings > SEO Settings > Let Squirrly SEO Optimize This Blog Metabox
				 */
				$settings_url = get_admin_url( null, 'admin.php?page=sq_seo#seo' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'Squirrly', 'squirrly-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'SEO Settings', 'squirrly-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Let Squirrly SEO Optimize This Blog', 'squirrly-seo' ) . '</a>';

				foreach ( array(
					'sq_auto_jsonld' => '"<strong>' . __( 'adds the Json-LD metas for Semantic SEO', 'wpsso' ) . '</strong>"',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'squirrly seo ' . $opt_key . ' option is enabled' );
						}

						$this->p->notice->err( $err_pre . sprintf( __( 'please disable the %1$s option in the %2$s metabox.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}
			}

			/**
			 * The SEO Framework
			 */
			if ( $this->p->avail[ 'seo' ][ 'autodescription' ] ) {

				$tsf = the_seo_framework();

				$opts = $tsf->get_all_options();

				/**
				 * The SEO Framework > Social Meta Settings Metabox
				 */
				$settings_url = get_admin_url( null, 'admin.php?page=theseoframework-settings' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'The SEO Framework', 'autodescription' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Social Meta Settings', 'autodescription' ) . '</a>';

				// translators: please ignore - translation uses a different text domain
				$posts_i18n = __( 'Posts', 'autodescription' );

				foreach ( array(
					// translators: please ignore - translation uses a different text domain
					'og_tags'       => '<strong>' . __( 'Output Open Graph meta tags?', 'autodescription' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'facebook_tags' => '<strong>' . __( 'Output Facebook meta tags?', 'autodescription' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'twitter_tags'  => '<strong>' . __( 'Output Twitter meta tags?', 'autodescription' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'post_publish_time' => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
						'article:published_time', $posts_i18n ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'post_modify_time' => '<strong>' . sprintf( __( 'Add %1$s to %2$s?', 'autodescription' ),
						'article:modified_time', $posts_i18n ) . '</strong>',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'autodescription ' . $opt_key . ' option is checked' );
						}

						$this->p->notice->err( $err_pre . sprintf( __( 'please uncheck the %1$s option in the %2$s metabox.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				/**
				 * The SEO Framework > Schema Settings Metabox
				 */
				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'The SEO Framework', 'autodescription' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Schema Settings', 'autodescription' ) . '</a>';

				if ( ! empty( $opts[ 'knowledge_output' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Output Authorized Presence?', 'autodescription' ) . '</strong>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'autodescription knowledge_output option is checked' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please uncheck the %1$s option in the %2$s metabox.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

			}

			/**
			 * WP Meta SEO
			 */
			if ( $this->p->avail[ 'seo' ][ 'wpmetaseo' ] ) {

				$opts = get_option( '_metaseo_settings' );

				/**
				 * WP Meta SEO > Settings > Global
				 */
				$settings_url = get_admin_url( null, 'admin.php?page=metaseo_settings' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'WP Meta SEO', 'wp-meta-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Settings', 'wp-meta-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Global', 'wp-meta-seo' ) . '</a>';

				foreach ( array(
					// translators: please ignore - translation uses a different text domain
					'metaseo_showfacebook' => '<strong>' . __( 'Facebook profile URL', 'wp-meta-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'metaseo_showfbappid'  => '<strong>' . __( 'Facebook App ID', 'wp-meta-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'metaseo_showtwitter'  => '<strong>' . __( 'Twitter Username', 'wp-meta-seo' ) . '</strong>',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'wpmetaseo ' . $opt_key . ' option is not empty' );
						}

						$this->p->notice->err( $err_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				if ( ! empty( $opts[ 'metaseo_showsocial' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Social sharing block', 'wp-meta-seo' ) . '</strong>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpmetaseo metaseo_showsocial option is enabled' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please disable the %1$s option in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}
			}

			/**
			 * Yoast SEO
			 */
			if ( $this->p->avail[ 'seo' ][ 'wpseo' ] ) {

				$opts = get_option( 'wpseo_social' );

				/**
				 * Yoast SEO > Social > Accounts Tab
				 */
				$settings_url = get_admin_url( null, 'admin.php?page=wpseo_social#top#accounts' );

				$settings_link = '<a href="' . $settings_url . '">' .
					// translators: please ignore - translation uses a different text domain
					__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
					// translators: please ignore - translation uses a different text domain
					__( 'Accounts', 'wordpress-seo' ) . '</a>';

				foreach ( array(
					// translators: please ignore - translation uses a different text domain
					'facebook_site'   => '<strong>' . __( 'Facebook Page URL', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'twitter_site'    => '<strong>' . __( 'Twitter Username', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'instagram_url'   => '<strong>' . __( 'Instagram URL', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'linkedin_url'    => '<strong>' . __( 'LinkedIn URL', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'myspace_url'     => '<strong>' . __( 'MySpace URL', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'pinterest_url'   => '<strong>' . __( 'Pinterest URL', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'youtube_url'     => '<strong>' . __( 'YouTube URL', 'wordpress-seo' ) . '</strong>',
					// translators: please ignore - translation uses a different text domain
					'google_plus_url' => '<strong>' . __( 'Google+ URL', 'wordpress-seo' ) . '</strong>',
				) as $opt_key => $label_transl ) {

					if ( ! empty( $opts[ $opt_key ] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( $log_pre . 'wpseo ' . $opt_key . ' option is not empty' );
						}

						$this->p->notice->err( $err_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
							'wpsso' ), $label_transl, $settings_link ) );
					}
				}

				/**
				 * Yoast SEO > Social > Faceboook Tab
				 */
				if ( ! empty( $opts[ 'opengraph' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Add Open Graph meta data', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( null, 'admin.php?page=wpseo_social#top#facebook' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Facebook', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo opengraph option is enabled' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please disable the %1$s option in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				if ( ! empty( $opts[ 'fbadminapp' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Facebook App ID', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( null, 'admin.php?page=wpseo_social#top#facebook' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Facebook', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo fbadminapp option is not empty' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				/**
				 * Yoast SEO > Social > Twitter Tab
				 */
				if ( ! empty( $opts[ 'twitter' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Add Twitter Card meta data', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( null, 'admin.php?page=wpseo_social#top#twitterbox' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Twitter', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo twitter option is enabled' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please disable the %1$s option in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}

				/**
				 * Yoast SEO > Social > Google+ Tab
				 */
				if ( ! empty( $opts[ 'plus-publisher' ] ) ) {

					// translators: please ignore - translation uses a different text domain
					$label_transl = '<strong>' . __( 'Google Publisher Page', 'wordpress-seo' ) . '</strong>';

					$settings_url = get_admin_url( null, 'admin.php?page=wpseo_social#top#google' );

					$settings_link = '<a href="' . $settings_url . '">' .
						// translators: please ignore - translation uses a different text domain
						__( 'Yoast SEO', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Social', 'wordpress-seo' ) . ' &gt; ' .
						// translators: please ignore - translation uses a different text domain
						__( 'Google+', 'wordpress-seo' ) . '</a>';

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( $log_pre . 'wpseo plus-publisher option is not empty' );
					}

					$this->p->notice->err( $err_pre . sprintf( __( 'please remove the %1$s option value in the %2$s settings.',
						'wpsso' ), $label_transl, $settings_link ) );
				}
			}
		}

		public function admin_footer_ext( $footer_html ) {

			$footer_html = '<div class="admin-footer-ext">';

			if ( isset( self::$pkg[ $this->menu_ext ][ 'name' ] ) ) {
				$footer_html .= self::$pkg[ $this->menu_ext ][ 'name' ] . '<br/>';
			}

			if ( isset( self::$pkg[ $this->menu_ext ][ 'gen' ] ) ) {
				$footer_html .= self::$pkg[ $this->menu_ext ][ 'gen' ] . '<br/>';
			}

			$footer_html .= '</div>';

			return $footer_html;
		}

		public function admin_footer_host( $footer_html ) {

			global $wp_version;

			$footer_html = '<div class="admin-footer-host">';

			$home_url  = strtolower( SucomUtilWP::raw_get_home_url() );
			$host_name = preg_replace( '/^[^:]*:\/\//', '', $home_url );

			$footer_html .= $host_name . '<br/>';

			$footer_html .= 'WordPress ' . $wp_version . '<br/>';

			$footer_html .= 'PHP ' . phpversion() . '<br/>';

			$footer_html .= '</div>';

			return $footer_html;
		}

		/**
		 * Only show notices on the dashboard and the settings pages.
		 * Hooked to 'current_screen' filter, so return the $screen object.
		 */
		public function maybe_show_screen_notices( $screen ) {

			$screen_id = SucomUtil::get_screen_id( $screen );

			/**
			 * If adding notices in the toolbar, show the notice on
			 * all pages, otherwise only show on the dashboard and
			 * settings pages.
			 */
			if ( SucomUtil::get_const( 'WPSSO_TOOLBAR_NOTICES' ) ) {

				$this->maybe_show_timed_notices();

			} else {

				switch ( $screen_id ) {

					case 'dashboard':

					case ( false !== strpos( $screen_id, '_page_' . $this->p->lca . '-' ) ? true : false ):

						$this->maybe_show_timed_notices();

						break;
				}
			}

			return $screen;
		}

		public function maybe_show_timed_notices() {

			if ( ! $this->p->notice->can_dismiss() || ! current_user_can( 'manage_options' ) ) {
				return;	// Stop here.
			}

			$lca     = $this->p->lca;
			$short   = $this->p->cf[ 'plugin' ][ $lca ][ 'short' ];
			$user_id = get_current_user_id();

			$all_times           = $this->p->util->get_all_times();
			$one_week_ago_secs   = time() - WEEK_IN_SECONDS;
			$six_months_ago_secs = time() - ( 6 * MONTH_IN_SECONDS );
			$one_year_ago_secs   = time() - YEAR_IN_SECONDS;

			$cache_md5_pre  = $lca . '_';
			$cache_exp_secs = 2 * DAY_IN_SECONDS;
			$cache_salt     = __METHOD__ . '(user_id:' . $user_id . ')';
			$cache_id       = $cache_md5_pre . md5( $cache_salt );

			$this->get_form_object( $lca );

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				$notice_key   = 'timed-notice-' . $ext . '-plugin-review';
				$dismiss_time = true;
				$showing_ext  = get_transient( $cache_id );				// Returns empty string or $notice_key value. 

				if ( empty( $info[ 'version' ] ) ) {					// Plugin not installed.

					continue;

				} elseif ( empty( $info[ 'url' ][ 'review' ] ) ) {				// Must be hosted on wordpress.org.

					continue;

				} elseif ( $this->p->notice->is_dismissed( $notice_key, $user_id ) ) {	// User has dismissed.

					if ( $showing_ext === $notice_key ) {				// Notice was dismissed $cache_exp_secs ago.
						break;							// Stop here.
					}

					continue;							// Get the next plugin.

				} elseif ( empty( $all_times[ $ext . '_activate_time' ] ) ) {		// Never activated.

					continue;

				} elseif ( $all_times[ $ext . '_activate_time' ] > $one_week_ago_secs ) {	// Activated less than time ago.

					continue;

				} elseif ( empty( $showing_ext ) || $showing_ext === '1' ) {		// Show this notice for $cache_exp_secs.

					set_transient( $cache_id, $notice_key, $cache_exp_secs );

				} elseif ( $showing_ext !== $notice_key ) {				// We're not showing this plugin right now.

					continue;							// Get the next plugin.
				}

				$wp_plugin_link = '<a href="' . $info[ 'url' ][ 'home' ] . '" title="' .
					sprintf( __( 'The %s plugin description page on WordPress.org.',
						'wpsso' ), $info[ 'short' ] ) . '">' . $info[ 'name' ] . '</a>';

				/**
				 * The action buttons.
				 */
				$rate_plugin_label   = sprintf( __( 'Yes! Rate %s 5 stars!', 'wpsso' ), $info[ 'short' ] );
				$rate_plugin_clicked = sprintf( __( 'Thank you for rating the %s plugin! You\'re awesome!', 'wpsso' ), $info[ 'short' ] );
				$rate_plugin_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0.8em 0.8em 0;">' .
					$this->form->get_button( $rate_plugin_label, 'button-primary dismiss-on-click', '', $info[ 'url' ][ 'review' ],
						true, false, array( 'dismiss-msg' => $rate_plugin_clicked ) ) . '</div>';

				$already_rated_label   = sprintf( __( 'I\'ve already rated %s.', 'wpsso' ), $info[ 'short' ] );
				$already_rated_clicked = sprintf( __( 'Thanks again for that earlier rating of %s! You\'re awesome!', 'wpsso' ), $info[ 'short' ] );
				$already_rated_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0 0.8em 0;">' .
					$this->form->get_button( $already_rated_label, 'button-secondary dismiss-on-click', '', '',
						false, false, array( 'dismiss-msg' => $already_rated_clicked ) ) . '</div>';

				/**
				 * The notice message.
				 */
				$notice_msg = '<div style="display:table-cell;">';
				
				$notice_msg .= '<p style="margin-right:20px;">' . $this->get_ext_img_icon( $ext ) . '</p>';
				
				$notice_msg .= '</div>';

				$notice_msg .= '<div style="display:table-cell;vertical-align:top;">';

				$notice_msg .= '<p class="top">';
				
				$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';
				
				$notice_msg .= sprintf( __( 'You\'ve been using <b>%s</b> for a while now, which is awesome!', 'wpsso' ), $wp_plugin_link );

				$notice_msg .= '</p><p>';

				$notice_msg .= sprintf( __( 'We\'ve put a lot of effort into making %s and its add-ons the best possible, so it\'s great to know that you\'re finding this plugin useful.', 'wpsso' ), $short ) . ' :-)';

				$notice_msg .= '</p><p>';

				$notice_msg .= sprintf( __( 'Now that you\'re familiar with %s, could you do me a small favor?', 'wpsso' ), $info[ 'short' ] ) . ' ';

				$notice_msg .= __( 'Just for me?', 'wpsso' );

				$notice_msg .= '</p><p>';

				$notice_msg .= __( 'Would you rate this plugin on WordPress.org?', 'wpsso' ) . ' :-)';

				$notice_msg .= '</p><p>';

				$notice_msg .= __( 'Your rating is a great way to encourage us, and it helps other WordPress users find great plugins as well!', 'wpsso' );

				$notice_msg .= '</p>';
				
				$notice_msg .= $rate_plugin_button . $already_rated_button;
					
				$notice_msg .= '</div>';

				/**
				 * The notice provides it's own dismiss button, so do not show the dismiss 'Forever' link.
				 */
				$this->p->notice->log( 'inf', $notice_msg, $user_id, $notice_key, $dismiss_time, array( 'dismiss_diff' => false ) );

				return;	// Show only one notice at a time.
			}

			if ( ! self::$pkg[ $lca ][ 'pp' ] ) {

				if ( ! empty( $all_times[ $lca . '_install_time' ] ) && $all_times[ $lca . '_install_time' ] < $six_months_ago_secs ) {

					$info         = $this->p->cf[ 'plugin' ][ $lca ];
					$purchase_url = add_query_arg( 'utm_source', 'pro-purchase-notice', $info[ 'url' ][ 'purchase' ] );
					$notice_key   = 'timed-notice-' . $lca . '-pro-purchase-notice';
					$dismiss_time = 3 * MONTH_IN_SECONDS;

					$purchase_label   = __( 'Yes! Get the Pro update in just moments!', 'wpsso' );
					$purchase_clicked = __( 'Thank you for your support! You\'re awesome!', 'wpsso' );
					$purchase_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0.8em 0.8em 0;">' .
						$this->form->get_button( $purchase_label, 'button-primary dismiss-on-click', '', $purchase_url,
							true, false, array( 'dismiss-msg' => $purchase_clicked ) ) . '</div>';

					$no_thanks_label   = __( 'No thanks, I\'ll stay with the Free version for now.', 'wpsso' );
					$no_thanks_clicked = __( 'I\'m sorry to hear that &mdash; maybe you\'ll change your mind later.', 'wpsso' ) . ' ;-)';
					$no_thanks_button  = '<div style="display:inline-block;vertical-align:top;margin:1.2em 0 0.8em 0;">' .
						$this->form->get_button( $no_thanks_label, 'button-secondary dismiss-on-click', '', '',
							false, false, array( 'dismiss-msg' => $no_thanks_clicked ) ) . '</div>';

					$notice_msg = '<p class="top">';

					$notice_msg .= '<b>' . __( 'Fantastic!', 'wpsso' ) . '</b> ';

					$notice_msg .= sprintf( __( 'You\'ve been using the %1$s plugin for more than %2$s now, which is awesome!', 'wpsso' ),
						$info[ 'short' ], __( 'six months', 'wpsso' ) ) . ' ';

					$notice_msg .= '</p><p>';

					$notice_msg .= sprintf( __( 'We\'ve put a lot of effort into making %1$s and its add-ons the best possible &mdash; I hope you\'ve enjoyed all the new features, improvements, and updates over the past %2$s.', 'wpsso' ), $info[ 'short' ], __( 'six months', 'wpsso' ) ) . ' :-)';

					$notice_msg .= '</p><p>';

					$notice_msg .= '<b>' . __( 'Have you considered purchasing the Pro version?', 'wpsso' ) . '</b> ';

					$notice_msg .= __( 'The Pro version comes with a lot of new and exciting extra features!', 'wpsso' );

					$notice_msg .= '</p>';
					
					$notice_msg .= $purchase_button . $no_thanks_button;

					/**
					 * The notice provides it's own dismiss button, so do not show the dismiss 'Forever' link.
					 */
					$this->p->notice->log( 'inf', $notice_msg, $user_id, $notice_key, $dismiss_time, array( 'dismiss_diff' => false ) );

					return;	// Show only one notice at a time.
				}
			}
		}

		public function required_notices() {

			$has_pdir = $this->p->avail[ '*' ][ 'p_dir' ];
			$version  = $this->p->cf[ 'plugin' ][ $this->p->lca ][ 'version' ];
			$um_info  = $this->p->cf[ 'plugin' ][ 'wpssoum' ];
			$have_tid = false;

			if ( $has_pdir && empty( $this->p->options[ 'plugin_' . $this->p->lca . '_tid' ] ) &&
				( empty( $this->p->options[ 'plugin_' . $this->p->lca . '_tid:is' ] ) ||
					$this->p->options[ 'plugin_' . $this->p->lca . '_tid:is' ] !== 'disabled' ) ) {

				$this->p->notice->nag( $this->p->msgs->get( 'notice-pro-tid-missing' ) );
			}

			foreach ( $this->p->cf[ 'plugin' ] as $ext => $info ) {

				if ( ! empty( $this->p->options[ 'plugin_' . $ext . '_tid' ] ) ) {

					$have_tid = true;	// Found at least one plugin with an auth id

					/**
					 * If the update manager is active, the version should be available.
					 * Skip individual warnings and show nag to install the update manager.
					 */
					if ( empty( $um_info[ 'version' ] ) ) {
						break;
					} else {
						if ( ! self::$pkg[ $ext ][ 'pdir' ] ) {
							if ( ! empty( $info[ 'base' ] ) && ! SucomPlugin::is_plugin_installed( $info[ 'base' ], $use_cache = true ) ) {
								$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-installed', array( 'lca' => $ext ) ) );
							} else {
								$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-updated', array( 'lca' => $ext ) ) );
							}
						}
					}
				}
			}

			if ( true === $have_tid ) {

				if ( ! empty( $um_info[ 'version' ] ) ) {	// If UM is active, its version should be available.

					$um_rec_version = WpssoConfig::$cf[ 'um' ][ 'rec_version' ];

					if ( version_compare( $um_info[ 'version' ], $um_rec_version, '<' ) ) {
						$this->p->notice->err( $this->p->msgs->get( 'notice-um-version-recommended',
							array( 'um_rec_version' => $um_rec_version ) ) );
					}

				} elseif ( SucomPlugin::is_plugin_installed( $um_info[ 'base' ], $use_cache = true ) ) {	// Check if UM is installed.

					$this->p->notice->nag( $this->p->msgs->get( 'notice-um-activate-add-on' ) );

				} else {	// UM is not active OR installed.

					$this->p->notice->nag( $this->p->msgs->get( 'notice-um-add-on-required' ) );
				}
			}

			if ( current_user_can( 'manage_options' ) ) {

				foreach ( array( 'wp', 'php' ) as $key ) {

					if ( isset( WpssoConfig::$cf[ $key ][ 'rec_version' ] ) ) {

						switch ( $key ) {

							case 'wp':

								global $wp_version;

								$app_version  = $wp_version;
								$dismiss_time = MONTH_IN_SECONDS;

								break;

							case 'php':

								$app_version  = phpversion();
								$dismiss_time = 3 * MONTH_IN_SECONDS;

								break;

							default:

								continue 2;
						}

						$app_label   = WpssoConfig::$cf[ $key ][ 'label' ];
						$rec_version = WpssoConfig::$cf[ $key ][ 'rec_version' ];

						if ( version_compare( $app_version, $rec_version, '<' ) ) {

							$warn_msg = $this->p->msgs->get( 'notice-recommend-version', array(
								'app_label'   => $app_label,
								'app_version' => $app_version,
								'rec_version' => WpssoConfig::$cf[ $key ][ 'rec_version' ],
								'version_url' => WpssoConfig::$cf[ $key ][ 'version_url' ],
							) );

							$notice_key   = 'notice-recommend-version-' . $this->p->lca . '-' . $version . '-' . $app_label . '-' . $app_version;

							$this->p->notice->warn( $warn_msg, null, $notice_key, $dismiss_time );
						}
					}
				}
			}
		}

		public function update_count_notice() {

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			$update_count = SucomPlugin::get_updates_count( $plugin_prefix = $this->p->lca );

			if ( $update_count > 0 ) {

				$info       = $this->p->cf[ 'plugin' ][ $this->p->lca ];
				$link_url   = self_admin_url( 'update-core.php' );
				$notice_key = 'have-updates-for-' . $this->p->lca;

				$this->p->notice->inf( sprintf( _n( 'There is <a href="%1$s">%2$d pending update for the %3$s plugin and/or its add-on(s)</a>.', 'There are <a href="%1$s">%2$d pending updates for the %3$s plugin and/or its add-on(s)</a>.', $update_count, 'wpsso' ), $link_url, $update_count, $info[ 'short' ] ) . ' ' . _n( 'Please install this update at your earliest convenience.', 'Please install these updates at your earliest convenience.', $update_count, 'wpsso' ), null, $notice_key, DAY_IN_SECONDS * 3 );
			}
		}

		public function reset_check_head_count() {

			delete_option( WPSSO_POST_CHECK_NAME );
		}

		public function check_tmpl_head_attributes() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * Only check if using the default filter name.
			 */
			if ( empty( $this->p->options[ 'plugin_head_attr_filter_name' ] ) ||
				$this->p->options[ 'plugin_head_attr_filter_name' ] !== 'head_attributes' ) {

				return;	// exit early
			}

			$header_files = SucomUtilWP::get_theme_header_files();

			foreach ( $header_files as $tmpl_base => $tmpl_file ) {

				$html_stripped = SucomUtil::get_stripped_php( $tmpl_file );

				if ( empty( $html_stripped ) ) {	// empty string or false

					continue;

				} elseif ( false !== strpos( $html_stripped, '<head>' ) ) {

					if ( $this->p->notice->is_admin_pre_notices() ) {

						$error_msg  = $this->p->msgs->get( 'notice-header-tmpl-no-head-attr' );
						$notice_key = 'notice-header-tmpl-no-head-attr-' . SucomUtilWP::get_theme_slug_version();

						$this->p->notice->warn( $error_msg, null, $notice_key, true );
					}

					break;
				}
			}
		}

		public function modify_tmpl_head_attributes() {

			$have_changes    = false;
			$header_files    = SucomUtilWP::get_theme_header_files();
			$head_action_php = '<head <?php do_action( \'add_head_attributes\' ); ?' . '>>';	// Breakup the closing php string for vim.

			if ( empty( $header_files ) ) {

				$this->p->notice->err( __( 'No header templates found in the parent or child theme directories.', 'wpsso' ) );

				return;	// Exit early.
			}

			foreach ( $header_files as $tmpl_base => $tmpl_file ) {

				$tmpl_base     = basename( $tmpl_file );
				$backup_file   = $tmpl_file . '~backup-' . date( 'Ymd-His' );
				$backup_base   = basename( $backup_file );
				$html_stripped = SucomUtil::get_stripped_php( $tmpl_file );
	
				/**
				 * Double check in case of reloads etc.
				 */
				if ( empty( $html_stripped ) || strpos( $html_stripped, '<head>' ) === false ) {
					$this->p->notice->err( sprintf( __( 'No %1$s HTML tag found in the %2$s template.', 'wpsso' ), '&lt;head&gt;', $tmpl_file ) );
					continue;
				}

				/**
				 * Make a backup of the original.
				 */
				if ( ! copy( $tmpl_file, $backup_file ) ) {
					$this->p->notice->err( sprintf( __( 'Error copying %1$s to %2$s.', 'wpsso' ), $tmpl_file, $backup_base ) );
					continue;
				}

				$tmpl_contents = file_get_contents( $tmpl_file );
				$tmpl_contents = str_replace( '<head>', $head_action_php, $tmpl_contents );

				if ( ! $tmpl_fh = @fopen( $tmpl_file, 'wb' ) ) {
					$this->p->notice->err( sprintf( __( 'Failed to open template file %s for writing.', 'wpsso' ), $tmpl_file ) );
					continue;
				}

				if ( fwrite( $tmpl_fh, $tmpl_contents ) ) {

					$this->p->notice->upd( sprintf( __( 'The %1$s template has been successfully modified and saved. A backup copy of the original template is available as %2$s in the same folder.', 'wpsso' ), $tmpl_file, $backup_base ) );

					$have_changes = true;

				} else {
					$this->p->notice->err( sprintf( __( 'Failed to write the %1$s template. You may need to restore the original template saved as %2$s in the same folder.', 'wpsso' ), $tmpl_file, $backup_base ) );
				}

				fclose( $tmpl_fh );
			}

			if ( $have_changes ) {

				$notice_key  = 'notice-header-tmpl-no-head-attr-' . SucomUtilWP::get_theme_slug_version();
				$admin_roles = $this->p->cf[ 'wp' ][ 'roles' ][ 'admin' ];
				$user_ids    = SucomUtilWP::get_user_ids_for_roles( $admin_roles );

				$this->p->notice->clear_key( $notice_key, $user_ids );	// Just in case.
			}
		}

		/**
		 * Called from the WpssoSubmenuGeneral class.
		 */
		protected function add_og_types_table_rows( array &$table_rows, array $hide_in_view = array(), $og_types = null ) {

			if ( ! is_array( $og_types ) ) {
				$og_types = $this->p->og->get_og_types_select( $add_none = true );
			}

			foreach ( array( 
				'home_index'   => _x( 'Type for Blog Front Page', 'option label', 'wpsso' ),
				'home_page'    => _x( 'Type for Static Front Page', 'option label', 'wpsso' ),
				'user_page'    => _x( 'Type for User / Author Page', 'option label', 'wpsso' ),
				'search_page'  => _x( 'Type for Search Results Page', 'option label', 'wpsso' ),
				'archive_page' => _x( 'Type for Other Archive Page', 'option label', 'wpsso' ),
			) as $type_name => $th_label ) {

				$tr_html = '';
				$opt_key = 'og_type_for_' . $type_name;

				if ( ! empty( $hide_in_view[ $opt_key ] ) ) {
					$tr_html = $this->form->get_tr_hide( $hide_in_view[ $opt_key ], $opt_key );
				}

				$table_rows[ $opt_key ] = $tr_html . $this->form->get_th_html( $th_label, '', $opt_key ) . 
				'<td>' . $this->form->get_select( $opt_key, $og_types, 'og_type' ) . '</td>';
			}

			/**
			 * Type by Post Type
			 */
			$type_select = '';
			$type_keys = array();

			foreach ( $this->p->util->get_post_types( 'objects' ) as $pt ) {

				$type_keys[] = $opt_key = 'og_type_for_' . $pt->name;

				$type_select .= '<p>' . $this->form->get_select( $opt_key, $og_types, 'og_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $pt->label ) . '</p>' . "\n";
			}

			$type_keys[] = $opt_key = 'og_type_for_post_archive';

			$type_select .= '<p>' . $this->form->get_select( $opt_key, $og_types, 'og_type' ) . ' ' .
				sprintf( _x( 'for %s', 'option comment', 'wpsso' ), _x( '(Post Type) Archive Page', 'option comment', 'wpsso' ) ) . '</p>' . "\n";

			$tr_html  = '';
			$tr_key   = 'og_type_for_ptn';
			$th_label = _x( 'Type by Post Type', 'option label', 'wpsso' );

			if ( ! empty( $hide_in_view[ $tr_key ] ) ) {
				$tr_html = $this->form->get_tr_hide( $hide_in_view[ $tr_key ], $type_keys );
			}

			$table_rows[ $tr_key ] = $tr_html . $this->form->get_th_html( $th_label, '', $tr_key ) .
			'<td>' . $type_select . '</td>';

			unset( $type_select, $type_keys );	// Just in case.

			/**
			 * Type by Term Taxonomy
			 */
			$type_select = '';
			$type_keys = array();

			foreach ( $this->p->util->get_taxonomies( 'objects' ) as $tax ) {

				$type_keys[] = $opt_key = 'og_type_for_tax_' . $tax->name;

				$type_select .= '<p>' . $this->form->get_select( $opt_key, $og_types, 'og_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $tax->label ) . '</p>' . "\n";
			}

			$tr_html  = '';
			$tr_key   = 'og_type_for_ttn';
			$th_label = _x( 'Type by Term Taxonomy', 'option label', 'wpsso' );

			if ( ! empty( $hide_in_view[ $tr_key ] ) ) {
				$tr_html = $this->form->get_tr_hide( $hide_in_view[ $tr_key ], $type_keys );
			}

			$table_rows[ $tr_key ] = $tr_html . 
			$this->form->get_th_html( $th_label, '', $tr_key ) . 
			'<td>' . $type_select . '</td>';

			unset( $type_select, $type_keys );	// Just in case.
		}

		/**
		 * Called from the WpssoSubmenuGeneral and WpssoJsonSubmenuSchemaJsonLd classes.
		 */
		protected function add_schema_item_props_table_rows( array &$table_rows ) {

			$table_rows[ 'schema_logo_url' ] = '' . 
			$this->form->get_th_html( '<a href="https://developers.google.com/structured-data/customize/logos">' .
			_x( 'Organization Logo URL', 'option label', 'wpsso' ) . '</a>', '', 'schema_logo_url', array( 'is_locale' => true ) ) . 
			'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'schema_logo_url', $this->p->options ), 'wide' ) . '</td>';

			$table_rows[ 'schema_banner_url' ] = '' . 
			$this->form->get_th_html( _x( 'Organization Banner URL', 'option label', 'wpsso' ), '', 'schema_banner_url', array( 'is_locale' => true ) ) . 
			'<td>' . $this->form->get_input( SucomUtil::get_key_locale( 'schema_banner_url', $this->p->options ), 'wide' ) . '</td>';

			$table_rows[ 'schema_img_max' ] = $this->form->get_tr_hide( 'basic', 'schema_img_max' ) . 
			$this->form->get_th_html( _x( 'Maximum Images to Include', 'option label', 'wpsso' ), '', 'schema_img_max' ) . 
			'<td>' . $this->form->get_select( 'schema_img_max', range( 0, $this->p->cf[ 'form' ][ 'max_media_items' ] ), 'short', '', true ) . 
			( empty( $this->form->options[ 'og_vid_prev_img' ] ) ? '' : ' <em>' . _x( 'video preview images are enabled (and included first)',
				'option comment', 'wpsso' ) . '</em>' ) . '</td>';

			$table_rows[ 'schema_img' ] = '' . 
			$this->form->get_th_html( _x( 'Schema Image Dimensions', 'option label', 'wpsso' ), '', 'schema_img_dimensions' ) . 
			'<td>' . $this->form->get_input_image_dimensions( 'schema_img' ) . '</td>';	// $use_opts = false

			$table_rows[ 'schema_desc_max_len' ] = $this->form->get_tr_hide( 'basic', 'schema_desc_max_len' ) . 
			$this->form->get_th_html( _x( 'Maximum Description Length', 'option label', 'wpsso' ), '', 'schema_desc_max_len' ) . 
			'<td>' . $this->form->get_input( 'schema_desc_max_len', 'short' ) . ' ' . _x( 'characters or less', 'option comment', 'wpsso' ) . '</td>';
		}

		/**
		 * Called from the WpssoSubmenuGeneral and WpssoJsonSubmenuSchemaJsonLd classes.
		 */
		protected function add_schema_item_types_table_rows( array &$table_rows, array $hide_in_view = array(), $schema_types = null ) {

			if ( ! is_array( $schema_types ) ) {
				$schema_types = $this->p->schema->get_schema_types_select( null, true );	// $add_none = true
			}

			foreach ( array( 
				'home_index'   => _x( 'Type for Blog Front Page', 'option label', 'wpsso' ),
				'home_page'    => _x( 'Type for Static Front Page', 'option label', 'wpsso' ),
				'user_page'    => _x( 'Type for User / Author Page', 'option label', 'wpsso' ),
				'search_page'  => _x( 'Type for Search Results Page', 'option label', 'wpsso' ),
				'archive_page' => _x( 'Type for Other Archive Page', 'option label', 'wpsso' ),
			) as $type_name => $th_label ) {

				$tr_html = '';
				$opt_key = 'schema_type_for_' . $type_name;

				if ( ! empty( $hide_in_view[ $opt_key ] ) ) {
					$tr_html = $this->form->get_tr_hide( $hide_in_view[ $opt_key ], $opt_key );
				}

				$table_rows[ $opt_key ] = $tr_html . 
				$this->form->get_th_html( $th_label, '', $opt_key ) . 
				'<td>' . $this->form->get_select( $opt_key, $schema_types, 'schema_type' ) . '</td>';
			}

			/**
			 * Type by Post Type
			 */
			$type_select = '';
			$type_keys = array();

			foreach ( $this->p->util->get_post_types( 'objects' ) as $pt ) {

				$type_keys[] = $opt_key = 'schema_type_for_' . $pt->name;

				$type_select .= '<p>' . $this->form->get_select( $opt_key, $schema_types, 'schema_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $pt->label ) . '</p>' . "\n";
			}

			$type_keys[] = $opt_key = 'schema_type_for_post_archive';

			$type_select .= '<p>' . $this->form->get_select( $opt_key, $schema_types, 'schema_type' ) . ' ' .
				sprintf( _x( 'for %s', 'option comment', 'wpsso' ), _x( '(Post Type) Archive Page', 'option comment', 'wpsso' ) ) . '</p>' . "\n";

			$tr_html  = '';
			$tr_key   = 'schema_type_for_ptn';
			$th_label = _x( 'Type by Post Type', 'option label', 'wpsso' );

			if ( ! empty( $hide_in_view[ $tr_key ] ) ) {
				$tr_html = $this->form->get_tr_hide( $hide_in_view[ $tr_key ], $type_keys );
			}

			$table_rows[ $tr_key ] = $tr_html . 
			$this->form->get_th_html( $th_label, '', $tr_key ) . 
			'<td>' . $type_select . '</td>';

			unset( $type_select, $type_keys );	// Just in case.

			/**
			 * Type by Term Taxonomy
			 */
			$type_select   = '';
			$type_keys = array();

			foreach ( $this->p->util->get_taxonomies( 'objects' ) as $tax ) {

				$type_keys[] = $opt_key = 'schema_type_for_tax_' . $tax->name;

				$type_select .= '<p>' . $this->form->get_select( $opt_key, $schema_types, 'schema_type' ) . ' ' .
					sprintf( _x( 'for %s', 'option comment', 'wpsso' ), $tax->label ) . '</p>' . "\n";
			}

			$tr_html  = '';
			$tr_key   = 'schema_type_for_ttn';
			$th_label = _x( 'Type by Term Taxonomy', 'option label', 'wpsso' );

			if ( ! empty( $hide_in_view[ $tr_key ] ) ) {
				$tr_html = $this->form->get_tr_hide( $hide_in_view[ $tr_key ], $type_keys );
			}

			$table_rows[ $tr_key ] = $tr_html . $this->form->get_th_html( $th_label, '', $tr_key ) . 
			'<td>' . $type_select . '</td>';

			unset( $type_select, $type_keys );	// Just in case.
		}

		/**
		 * Called from the WpssoSubmenuEssential, WpssoSubmenuGeneral, and WpssoJsonSubmenuSchemaJsonLd classes.
		 */
		protected function add_schema_knowledge_graph_table_rows( array &$table_rows ) {

			$table_rows[ 'schema_knowledge_graph' ] = '' . 
			$this->form->get_th_html( _x( 'Knowledge Graph for Home Page', 'option label', 'wpsso' ), '', 'schema_knowledge_graph' ) . 
			'<td>' .
			'<p>' . $this->form->get_checkbox( 'schema_add_home_website' ) . ' ' .
				sprintf( __( 'Include <a href="%s">WebSite Information</a> for Google Search',
					'wpsso' ), 'https://developers.google.com/structured-data/site-name' ) . '</p>' .
			'<p>' . $this->form->get_checkbox( 'schema_add_home_organization' ) . ' ' .
				sprintf( __( 'Include <a href="%s">Organization Social Profile</a> for a Business Site',
					'wpsso' ), 'https://developers.google.com/structured-data/customize/social-profiles' ) . '</p>' .
			'<p>' . $this->form->get_checkbox( 'schema_add_home_person' ) . ' ' .
				sprintf( __( 'Include <a href="%s">Person Social Profile</a> for a Personal Site',
					'wpsso' ), 'https://developers.google.com/structured-data/customize/social-profiles' ) . '</p>' .
			'</td>';

			$owner_roles = $this->p->cf[ 'wp' ][ 'roles' ][ 'owner' ];

			$site_owners = SucomUtilWP::get_user_select_for_roles( $owner_roles );

			$table_rows[ 'schema_home_person_id' ] = '' . 
			$this->form->get_th_html( _x( 'User for Person Social Profile', 'option label', 'wpsso' ), '', 'schema_home_person_id' ) . 
			'<td>' . $this->form->get_select( 'schema_home_person_id', $site_owners, '', '', true ) . '</td>';
		}

		/**
		 * Called from the WpssoSubmenuEssential, WpssoSubmenuAdvanced, and WpssoSitesubmenuSiteadvanced classes.
		 * Note that the essential settings page will unset() some table rows to keep the options list to a minimum.
		 */
		protected function add_optional_advanced_table_rows( array &$table_rows, $network = false ) {

			$table_rows[ 'plugin_clean_on_uninstall' ] = '' .
			$this->form->get_th_html( _x( 'Remove All Settings on Uninstall', 'option label', 'wpsso' ), '', 'plugin_clean_on_uninstall' ) . 
			'<td>' .
				$this->form->get_checkbox( 'plugin_clean_on_uninstall' ) . ' ' .
				_x( 'including any custom meta for posts, terms, and users', 'option comment', 'wpsso' ) . ' ' . 
			'</td>' .
			self::get_option_site_use( 'plugin_clean_on_uninstall', $this->form, $network, true );

			$table_rows[ 'plugin_debug' ] = '' .
			$this->form->get_th_html( _x( 'Add Hidden Debug Messages', 'option label', 'wpsso' ), '', 'plugin_debug' ) . 
			'<td>'  .  ( ! $network && SucomUtil::get_const( 'WPSSO_HTML_DEBUG' ) ? $this->form->get_no_checkbox( 'plugin_debug' ) .
				' <em>' . _x( 'WPSSO_HTML_DEBUG constant is true', 'option comment', 'wpsso' ) . '</em>' :
					$this->form->get_checkbox( 'plugin_debug' ) ) .
			'</td>' .
			self::get_option_site_use( 'plugin_debug', $this->form, $network, true );

			if ( $network || ! $this->p->check->pp( $this->p->lca, false, $this->p->avail[ '*' ][ 'p_dir' ] ) ) {

				$table_rows[ 'plugin_hide_pro' ] = '' .
				$this->form->get_th_html( _x( 'Hide All Pro Version Options', 'option label', 'wpsso' ), '', 'plugin_hide_pro' ) .
				'<td>' . $this->form->get_checkbox( 'plugin_hide_pro' ) . '</td>' .
				self::get_option_site_use( 'plugin_show_opts', $this->form, $network, true );

			} else {
				$this->form->get_hidden( 'plugin_hide_pro', 0, true );
			}

			$table_rows[ 'plugin_show_opts' ] = '' .
			$this->form->get_th_html( _x( 'Options to Show by Default', 'option label', 'wpsso' ), '', 'plugin_show_opts' ) .
			'<td>' . $this->form->get_select( 'plugin_show_opts', $this->p->cf[ 'form' ][ 'show_options' ] ) . '</td>' .
			self::get_option_site_use( 'plugin_show_opts', $this->form, $network, true );
		}

		public static function get_option_site_use( $name, $form, $network = false, $enabled = false ) {
			if ( $network ) {
				return $form->get_th_html( _x( 'Site Use', 'option label (very short)', 'wpsso' ), 'site_use' ) . 
					( $enabled || self::$pkg[ 'wpsso' ][ 'pp' ] ? '<td class="site_use">' . $form->get_select( $name . ':use',
						WpssoConfig::$cf[ 'form' ][ 'site_option_use' ], 'site_use' ) . '</td>' :
					'<td class="blank site_use">' . $form->get_select( $name . ':use',
						WpssoConfig::$cf[ 'form' ][ 'site_option_use' ], 'site_use', '', true, true ) . '</td>' );
			} else {
				return '';
			}
		}

		public function get_readme_info( $ext, $read_cache = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'ext'        => $ext,
					'read_cache' => $read_cache,
				) );
			}

			$file_name   = 'readme.txt';
			$file_key    = SucomUtil::sanitize_hookname( $file_name );	// Rename readme.txt to readme_txt.
			$file_dir    = SucomUtil::get_const( strtoupper( $ext ) . '_PLUGINDIR' );
			$file_local  = $file_dir ? trailingslashit( $file_dir ) . $file_name : false;
			$file_remote = isset( $this->p->cf[ 'plugin' ][ $ext ][ 'url' ][ $file_key ] ) ? 
				$this->p->cf[ 'plugin' ][ $ext ][ 'url' ][ $file_key ] : false;

			$file_local_transl = SucomUtil::get_file_path_locale( $file_local );

			if ( file_exists( $file_local_transl ) ) {
				$file_local  = $file_local_transl;
				$file_remote = SucomUtil::get_file_path_locale( $file_remote );
			}

			static $cache_exp_secs = null;

			$cache_md5_pre = $this->p->lca . '_';

			if ( ! isset( $cache_exp_secs ) ) {
				$cache_exp_filter = $this->p->lca . '_cache_expire_' . $file_key;	// Example: 'wpsso_cache_expire_readme_txt'.
				$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, DAY_IN_SECONDS );
			}

			$cache_salt = __METHOD__ . '(ext:' . $ext . ')';
			$cache_id   = $cache_md5_pre . md5( $cache_salt );

			$readme_info     = false;
			$readme_content  = false;
			$readme_from_url = false;

			if ( $cache_exp_secs > 0 ) {

				if ( $read_cache ) {

					$readme_info = get_transient( $cache_id );

					if ( is_array( $readme_info ) ) {
						return $readme_info;	// Stop here.
					}
				}

				if ( $file_remote && strpos( $file_remote, '://' ) ) {

					/**
					 * Clear the cache first if reading the cache is disabled.
					 */
					if ( ! $read_cache ) {
						$this->p->cache->clear( $file_remote );
					}

					$readme_from_url = true;
					$readme_content  = $this->p->cache->get( $file_remote, 'raw', 'file', $cache_exp_secs );
				}
			} else {
				delete_transient( $cache_id );	// Just in case.
			}

			if ( empty( $readme_content ) ) {

				if ( $file_local && file_exists( $file_local ) && $fh = @fopen( $file_local, 'rb' ) ) {

					$readme_from_url = false;
					$readme_content  = fread( $fh, filesize( $file_local ) );

					fclose( $fh );
				}
			}

			if ( empty( $readme_content ) ) {

				$readme_info = array();	// save an empty array

			} else {

				$parser = new SuextParseReadme( $this->p->debug );

				$readme_info = $parser->parse_readme_contents( $readme_content );
	
				/**
				 * Remove possibly inaccurate information from the local readme file.
				 */
				if ( ! $readme_from_url && is_array( $readme_info ) ) {
					foreach ( array( 'stable_tag', 'upgrade_notice' ) as $key ) {
						unset ( $readme_info[ $key ] );
					}
				}
			}

			/**
			 * Save the parsed readme to the transient cache.
			 */
			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $readme_info, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'readme_info saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			return is_array( $readme_info ) ? $readme_info : array();	// Just in case.
		}

		public function get_config_url_content( $ext, $file_name, $cache_exp_secs = null ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array( 
					'ext'            => $ext,
					'file_name'      => $file_name,
					'cache_exp_secs' => $cache_exp_secs,
				) );
			}

			$file_name   = SucomUtil::sanitize_file_path( $file_name );
			$file_key    = SucomUtil::sanitize_hookname( basename( $file_name ) );	// html/setup.html -> setup_html
			$file_dir    = SucomUtil::get_const( strtoupper( $ext ) . '_PLUGINDIR' );
			$file_local  = $file_dir ? trailingslashit( $file_dir ) . $file_name : false;
			$file_remote = isset( $this->p->cf[ 'plugin' ][ $ext ][ 'url' ][ $file_key ] ) ? 
				$this->p->cf[ 'plugin' ][ $ext ][ 'url' ][ $file_key ] : false;

			$file_local_transl = SucomUtil::get_file_path_locale( $file_local );

			if ( file_exists( $file_local_transl ) ) {
				$file_local  = $file_local_transl;
				$file_remote = SucomUtil::get_file_path_locale( $file_remote );
			}

			if ( null === $cache_exp_secs ) {
				$cache_exp_secs = WEEK_IN_SECONDS;
			}

			$cache_exp_filter = $this->p->lca . '_cache_expire_' . $file_key;	// 'wpsso_cache_expire_setup_html'
			$cache_exp_secs   = (int) apply_filters( $cache_exp_filter, $cache_exp_secs );
			$cache_content    = false;

			if ( $cache_exp_secs > 0 ) {
				if ( $file_remote && strpos( $file_remote, '://' ) ) {
					$cache_content = $this->p->cache->get( $file_remote, 'raw', 'file', $cache_exp_secs );
				}
			}

			if ( empty( $cache_content ) ) {
				if ( $file_local && file_exists( $file_local ) && $fh = @fopen( $file_local, 'rb' ) ) {
					$cache_content = fread( $fh, filesize( $file_local ) );
					fclose( $fh );
				}
			}

			return $cache_content;
		}

		public function plugin_complete_actions( $actions ) {

			if ( ! empty( $this->pageref_url ) && ! empty( $this->pageref_title ) ) {

				foreach ( $actions as $action => &$html ) {

					switch ( $action ) {

						case 'plugins_page':

							$html = '<a href="' . urlencode( $this->pageref_url ) . '" target="_parent">' .
								sprintf( __( 'Return to %s', 'wpsso' ), $this->pageref_title ) . '</a>';

							break;

						default:

							if ( preg_match( '/^(.*href=["\'])([^"\']+)(["\'].*)$/', $html, $matches ) ) {

								$url = add_query_arg( array(
									$this->p->lca . '_pageref_url'   => urlencode( $this->pageref_url ),
									$this->p->lca . '_pageref_title' => urlencode( $this->pageref_title ),
								), $matches[2] );

								$html = $matches[1] . $url . $matches[3];
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

					// translators: please ignore - translation uses a different text domain
					$this->p->notice->upd( __( 'Plugin <strong>activated</strong>.' ) );

					$url = $this->pageref_url;
				}
			}

			return $url;
		}

		public function get_check_for_updates_link( $only_url = false ) {

			$link_url  = '';
			$link_html = '';

			if ( class_exists( 'WpssoUm' ) ) {

				$this->set_plugin_pkg_info();

				$link_url = wp_nonce_url( $this->p->util->get_admin_url( 'um-general?' . $this->p->lca . '-action=check_for_updates' ),
					WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

				// translators: %1$s is the URL, %2$s is the short plugin name
				$link_html = sprintf( __( 'You may <a href="%1$s">refresh the update information for %2$s and its add-ons</a> to check if newer versions are available.', 'wpsso' ), $link_url, self::$pkg[ $this->p->lca ][ 'short' ] );

			} elseif ( empty( $_GET[ 'force-check' ] ) ) {

				$link_url = self_admin_url( 'update-core.php?force-check=1' );

				// translators: %1$s is the URL
				$link_html = sprintf( __( 'You may <a href="%1$s">refresh the update information for WordPress (plugins, themes, and translations)</a> to check if newer versions are available.', 'wpsso' ), $link_url );

			}

			return $only_url ? $link_url : $link_html;
		}

		/**
		 * Returns a 128x128px image.
		 */
		public function get_ext_img_icon( $ext ) {

			/**
			 * The default image is a transparent 1px gif.
			 */
			$img_src = 'src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="';

			if ( ! empty( $this->p->cf[ 'plugin' ][ $ext ][ 'img' ][ 'icons' ] ) ) {

				$icons = $this->p->cf[ 'plugin' ][ $ext ][ 'img' ][ 'icons' ];

				if ( ! empty( $icons[ 'low' ] ) ) {
					$img_src = 'src="' . $icons[ 'low' ] . '"';
				}

				if ( ! empty( $icons[ 'high' ] ) ) {
					$img_src .= ' srcset="' . $icons[ 'high' ] . ' 256w"';
				}
			}

			return '<img ' . $img_src . ' width="128" height="128" style="width:128px; height:128px;"/>';
		}

		/**
		 * If an add-on is not available, return a short sentence that this add-on is required.
		 *
		 * $mixed = wpssojson, json, etc.
		 */
		public function get_ext_required_msg( $mixed ) {

			$html = '';

			if ( ! is_string( $mixed ) ) {						// Just in case.
				return $html;
			}

			if ( strpos( $mixed, $this->p->lca ) === 0 ) {				// A complete lower case acronym was provided.
				$p_ext = substr( $ext, 0, strlen( $this->p->lca ) );		// Change 'wpssojson' to 'json'
				$ext   = $mixed;
			} else {
				$p_ext = $mixed;
				$ext   = $this->p->lca . $p_ext;				// Change 'json' to 'wpssojson'
			}

			if ( $this->p->lca === $mixed ) {					// The main plugin is not considered an add-on.
				return $html;
			} elseif ( ! empty( $this->p->avail[ 'p_ext' ][ $p_ext ] ) ) {		// Add-on is already active.
				return $html;
			} elseif ( empty( $this->p->cf[ 'plugin' ][ $ext ][ 'short' ] ) ) {	// Add-on config is not defined.
				return $html;
			}

			$html .= ' <span class="ext-req-msg">';

			$html .= $this->p->util->get_admin_url( 'addons#' . $ext, 
				sprintf( _x( '%s add-on required', 'option comment', 'wpsso' ),
					$this->p->cf[ 'plugin' ][ $ext ][ 'short' ] ) );

			$html .= '</span>';

			return $html;
		}

		/**
		 * Called from the network settings pages.
		 */
		public function add_class_postbox_network( $classes ) {

			$classes[] = 'postbox-network';

			return $classes;
		}

		public function export_plugin_settings_json() {

			$date_slug    = date( 'YmdHiT' );
			$home_slug    = SucomUtil::sanitize_hookname( preg_replace( '/^.*\/\//', '', get_home_url() ) );
			$mime_type_gz = 'application/x-gzip';
			$file_name_gz = WpssoConfig::get_version( $add_slug = true ) . '-' . $home_slug . '-' . $date_slug . '.json.gz';
			$opts_encoded = SucomUtil::json_encode_array( $this->p->options );
			$gzdata       = gzencode( $opts_encoded, 9, FORCE_GZIP );

			session_write_close();
			@ignore_user_abort();
			@set_time_limit( 0 );
			@apache_setenv( 'no-gzip', 1 );
			@ini_set( 'zlib.output_compression', 0 );
			@ini_set( 'implicit_flush', 1 );
			@ob_end_flush();

			$filesize    = strlen( $gzdata );
			$disposition = 'attachment';
			$chunksize   = 1024 * 32;	// 32kb per fread().

			/**
			 * Remove all dots, except last one, for MSIE clients.
			 */
			if ( strstr( $_SERVER[ 'HTTP_USER_AGENT' ], 'MSIE' ) ) {
				$file_name_gz = preg_replace( '/\./', '%2e', $file_name_gz, substr_count( $file_name_gz, '.' ) - 1 );
			}
	
			if ( isset( $_SERVER[ 'HTTPS' ] ) ) {

				header( 'Pragma: ' );
				header( 'Cache-Control: ' );
				header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
				header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT' );
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
			$max_file_size = 100000;	// 100K

			if ( ! isset( $_FILES[ 'file' ][ 'error' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'incomplete post method upload' );
				}

				return false;

			} elseif ( $_FILES[ 'file' ][ 'error' ] === UPLOAD_ERR_NO_FILE ) {

				$this->p->notice->err( sprintf( __( 'Please select a %1$s settings file to import.',
					'wpsso' ), $dot_file_ext ) );

				return false;

			} elseif ( $_FILES[ 'file' ][ 'type' ] !== 'application/x-gzip' ) {

				$this->p->notice->err( sprintf( __( 'The %1$s settings file to import must be an "%2$s" mime type.',
					'wpsso' ), $dot_file_ext, $mime_type_gz ) );

				return false;

			} elseif ( $_FILES[ 'file' ][ 'size' ] > $max_file_size ) {	// Just in case.

				$this->p->notice->err( sprintf( __( 'The %1$s settings file is larger than the maximum of %2$d bytes allowed.',
					'wpsso' ), $dot_file_ext, $max_file_size ) );

				return false;
			}

			$gzdata = file_get_contents( $_FILES[ 'file' ][ 'tmp_name' ] );

			@unlink( $_FILES[ 'file' ][ 'tmp_name' ] );

			$opts_encoded = gzdecode( $gzdata );

			if ( empty( $opts_encoded ) ) {	// false or empty array.

				$this->p->notice->err( sprintf( __( 'The %1$s settings file is appears to be empty or corrupted.',
					'wpsso' ), $dot_file_ext ) );

				return false;
			}

			$opts = json_decode( $opts_encoded, $assoc = true );

			if ( empty( $opts ) || ! is_array( $opts ) ) {	// false or empty array.

				$this->p->notice->err( sprintf( __( 'The %1$s settings file could not be decoded into a settings array.',
					'wpsso' ), $dot_file_ext ) );

				return false;
			}

			$this->p->options = $this->p->opt->check_options( WPSSO_OPTIONS_NAME, $opts );

			$this->p->opt->save_options( WPSSO_OPTIONS_NAME, $opts );

			$this->p->notice->upd( __( 'Import of plugin and add-on settings is complete.', 'wpsso' ) );

			return true;
		}
	}
}

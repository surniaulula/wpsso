<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoAdmin' ) ) {

	class WpssoAdmin {

		protected $p;
		protected $menu_id = null;
		protected $menu_name = null;
		protected $menu_lib = null;
		protected $menu_ext = null;
		protected $pagehook = null;

		public static $pkg = array();
		public static $readme = array();	// array for the readme of each extension

		public $form;
		public $lang = array();
		public $submenu = array();

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( SucomUtil::get_const( 'DOING_AJAX' ) ) {
				// nothing to do
			} else {
				load_plugin_textdomain( 'wpsso', false, 'wpsso/languages/' );

				$this->set_objects();

				add_action( 'admin_init', array( &$this, 'register_setting' ) );
				add_action( 'admin_menu', array( &$this, 'add_admin_menus' ), WPSSO_ADD_MENU_PRIORITY );
				add_action( 'admin_menu', array( &$this, 'add_admin_submenus' ), WPSSO_ADD_SUBMENU_PRIORITY );

				// hook in_admin_header to allow for setting changes, plugin activation / loading, etc.
				add_action( 'in_admin_header', array( &$this, 'conflict_warnings' ), 10 );
				add_action( 'in_admin_header', array( &$this, 'required_notices' ), 20 );

				add_action( 'after_switch_theme', array( &$this, 'reset_check_head_exec_count' ) );
				add_action( 'upgrader_process_complete', array( &$this, 'reset_check_head_exec_count' ) );

				add_action( 'after_switch_theme', array( &$this, 'check_tmpl_head_attributes' ) );
				add_action( 'upgrader_process_complete', array( &$this, 'check_tmpl_head_attributes' ) );

				add_filter( 'plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );
				add_filter( 'wp_redirect', array( &$this, 'wp_profile_updated_redirect' ), -100, 2 );

				if ( is_multisite() ) {
					add_action( 'network_admin_menu', array( &$this, 'add_network_admin_menus' ), WPSSO_ADD_MENU_PRIORITY );
					add_action( 'network_admin_edit_'.WPSSO_SITE_OPTIONS_NAME, array( &$this, 'save_site_options' ) );
					add_filter( 'network_admin_plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );
				}
			}

		}

		// load all submenu classes into the $this->submenu array
		// the id of each submenu item must be unique
		private function set_objects() {

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				self::$pkg[$ext]['aop'] = $this->p->check->aop( $this->p->cf['lca'], 
					true, $this->p->is_avail['aop'] ) && $this->p->check->aop( $ext, 
						true, -1 ) === -1 ? true : false;
				self::$pkg[$ext]['type'] = self::$pkg[$ext]['aop'] ?
					_x( 'Pro', 'package type', 'wpsso' ) :
					_x( 'Free', 'package type', 'wpsso' );
				self::$pkg[$ext]['short'] = $info['short'].' '.self::$pkg[$ext]['type'];
				self::$pkg[$ext]['name'] = $info['name'].' '.self::$pkg[$ext]['type'];
			}

			$menu_libs = array( 
				'submenu', 
				'setting',		// setting must be after submenu to extend submenu/advanced.php
				'profile', 
			);

			if ( is_multisite() )
				$menu_libs[] = 'sitesubmenu';

			foreach ( $menu_libs as $menu_lib ) {
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( ! isset( $info['lib'][$menu_lib] ) )	// not all extensions have submenus
						continue;
					foreach ( $info['lib'][$menu_lib] as $menu_id => $menu_name ) {
						$classname = apply_filters( $ext.'_load_lib', false, $menu_lib.'/'.$menu_id );
						if ( is_string( $classname ) && class_exists( $classname ) ) {
							if ( ! empty( $info['text_domain'] ) )
								$menu_name = _x( $menu_name, 'lib file description', $info['text_domain'] );
							$this->submenu[$menu_id] = new $classname( $this->p, 
								$menu_id, $menu_name, $menu_lib, $ext );
						}
					}
				}
			}
		}

		// extended by the sitesubmenu library classes to define / load site options instead
		protected function set_form_property( $menu_ext ) {
			$def_opts = $this->p->opt->get_defaults();
			$this->form = new SucomForm( $this->p, WPSSO_OPTIONS_NAME, $this->p->options, $def_opts, $menu_ext );
		}

		protected function &get_form_reference() {	// returns a reference
			return $this->form;
		}

		public function register_setting() {
			register_setting( $this->p->cf['lca'].'_setting', WPSSO_OPTIONS_NAME, 
				array( &$this, 'registered_setting_sanitation' ) );
		} 

		public static function set_readme_info( $read_cache = true ) {
			$wpsso =& Wpsso::get_instance();
			foreach ( array_keys( $wpsso->cf['plugin'] ) as $ext ) {
				if ( empty( self::$readme[$ext] ) )
					self::$readme[$ext] = $wpsso->util->get_readme_info( $ext, $read_cache );
			}
		}

		// add sub-menu items to existing menus (profile and setting)
		public function add_admin_submenus() {
			foreach ( array( 'profile', 'setting' ) as $menu_lib ) {

				// match wordpress behavior (users page for admins, profile page for everyone else)
				if ( $menu_lib === 'profile' && 
					current_user_can( 'list_users' ) )
						$parent_slug = $this->p->cf['wp']['admin']['users']['page'];
				else $parent_slug = $this->p->cf['wp']['admin'][$menu_lib]['page'];

				$sorted_menu = array();
				foreach ( $this->p->cf['plugin'] as $ext => $info ) {
					if ( ! isset( $info['lib'][$menu_lib] ) )	// not all extensions have submenus
						continue;
					foreach ( $info['lib'][$menu_lib] as $menu_id => $menu_name ) {
						$name_text = wp_strip_all_tags( $menu_name, true );	// just in case
						$sorted_menu[$name_text.'-'.$menu_id] = array( $parent_slug, 
							$menu_id, $menu_name, $menu_lib, $ext );	// add_submenu_page() args
					}
				}
				ksort( $sorted_menu );

				foreach ( $sorted_menu as $key => $arg ) {
					if ( isset( $this->submenu[$arg[1]] ) )
						$this->submenu[$arg[1]]->add_submenu_page( $arg[0] );
					else $this->add_submenu_page( $arg[0], $arg[1], $arg[2], $arg[3], $arg[4] );
				}
			}
		}

		public function add_network_admin_menus() {
			$this->add_admin_menus( 'sitesubmenu' );
		}

		// add a new main menu, and its sub-menu items
		public function add_admin_menus( $menu_lib = '' ) {
			$menu_lib = empty( $menu_lib ) ?
				'submenu' : $menu_lib;

			$lca = $this->p->cf['lca'];
			$libs = $this->p->cf['*']['lib'][$menu_lib];
			$this->menu_id = key( $libs );
			$this->menu_name = $libs[$this->menu_id];
			$this->menu_lib = $menu_lib;
			$this->menu_ext = $lca;

			if ( isset( $this->submenu[$this->menu_id] ) ) {
				$menu_slug = $lca.'-'.$this->menu_id;
				$this->submenu[$this->menu_id]->add_menu_page( $menu_slug );
			}

			$sorted_menu = array();
			$unsorted_menu = array();
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( ! isset( $info['lib'][$menu_lib] ) )	// not all extensions have submenus
					continue;
				foreach ( $info['lib'][$menu_lib] as $menu_id => $menu_name ) {
					$parent_slug = $this->p->cf['lca'].'-'.$this->menu_id;
					if ( $lca === $ext )
						$unsorted_menu[] = array( $parent_slug, $menu_id, $menu_name, $menu_lib, $ext );
					else {
						$name_text = wp_strip_all_tags( $menu_name, true );	// just in case
						$sorted_key = $name_text.'-'.$menu_id;
						$sorted_menu[$sorted_key] = array( $parent_slug, $menu_id, $menu_name, $menu_lib, $ext );
					}
				}
			}
			ksort( $sorted_menu );

			foreach ( array_merge( $unsorted_menu, $sorted_menu ) as $key => $arg ) {
				if ( isset( $this->submenu[$arg[1]] ) )
					$this->submenu[$arg[1]]->add_submenu_page( $arg[0] );
				else $this->add_submenu_page( $arg[0], $arg[1], $arg[2], $arg[3], $arg[4] );
			}
		}

		protected function add_menu_page( $menu_slug ) {
			global $wp_version;
			$lca = $this->p->cf['lca'];

			// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
			$this->pagehook = add_menu_page( 
				self::$pkg[$lca]['short'].' &mdash; '.$this->menu_name, 
				$this->p->cf['menu_label'].' '.self::$pkg[$lca]['type'], 
				( isset( $this->p->cf['wp']['admin'][$this->menu_lib]['cap'] ) ?
					$this->p->cf['wp']['admin'][$this->menu_lib]['cap'] :
					'manage_options' ),	// fallback to manage_options capability
				$menu_slug, 
				array( &$this, 'show_setting_page' ), 
				( version_compare( $wp_version, 3.8, '<' ) ? null : 'dashicons-share' ),
				WPSSO_MENU_ORDER
			);
			add_action( 'load-'.$this->pagehook, array( &$this, 'load_setting_page' ) );
		}

		protected function add_submenu_page( $parent_slug, $menu_id = '', $menu_name = '', $menu_lib = '', $menu_ext = '' ) {

			if ( empty( $menu_id ) )
				$menu_id = $this->menu_id;
			if ( empty( $menu_name ) )
				$menu_name = $this->menu_name;
			if ( empty( $menu_lib ) )
				$menu_lib = $this->menu_lib;
			if ( empty( $menu_ext ) ) {
				$menu_ext = $this->menu_ext;
				if ( empty( $menu_ext ) )
					$menu_ext = $this->p->cf['lca'];
			}

			global $wp_version;
			if ( $menu_ext === $this->p->cf['lca'] )		// plugin menu and sub-menu items
				$menu_title = $menu_name;
			elseif ( version_compare( $wp_version, 3.8, '<' ) )	// wp v3.8 required for dashicons
				$menu_title = $menu_name;
			else $menu_title = '<div class="extension-plugin'.	// add plugin icon for extensions
				' dashicons-before dashicons-admin-plugins"></div>'.
				'<div class="extension-plugin">'.$menu_name.'</div>';

			if ( strpos( $menu_title, '<color>' ) !== false )
				$menu_title = preg_replace( array( '/<color>/', '/<\/color>/' ),
					array( '<span style="color:#'.$this->p->cf['menu_color'].';">', '</span>' ), $menu_title );

			$menu_slug = $this->p->cf['lca'].'-'.$menu_id;
			$page_title = self::$pkg[$menu_ext]['short'].' &mdash; '.$menu_title;
			$function = array( &$this, 'show_setting_page' );

			// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
			$this->pagehook = add_submenu_page( $parent_slug, $page_title, $menu_title,
				( isset( $this->p->cf['wp']['admin'][$menu_lib]['cap'] ) ?
					$this->p->cf['wp']['admin'][$menu_lib]['cap'] : 'manage_options' ),
						$menu_slug, $function );	// fallback to manage_options capability

			if ( $function )
				add_action( 'load-'.$this->pagehook, array( &$this, 'load_setting_page' ) );
		}

		// add links on the main plugins page
		public function add_plugin_action_links( $links, $file ) {

			if ( ! isset( $this->p->cf['*']['base'][$file] ) )
				return $links;

			$ext = $this->p->cf['*']['base'][$file];
			$info = $this->p->cf['plugin'][$ext];

			foreach ( $links as $num => $val )
				if ( strpos( $val, '>Edit<' ) !== false )
					unset ( $links[$num] );

			if ( ! empty( $info['url']['faq'] ) )
				$links[] = '<a href="'.$info['url']['faq'].'">'.
					_x( 'FAQ', 'plugin action link', 'wpsso' ).'</a>';

			if ( ! empty( $info['url']['notes'] ) )
				$links[] = '<a href="'.$info['url']['notes'].'">'.
					_x( 'Notes', 'plugin action link', 'wpsso' ).'</a>';

			if ( ! empty( $info['url']['latest'] ) )
				$links[] = '<a href="'.$info['url']['latest'].'">'.
					_x( 'Download Latest', 'plugin action link', 'wpsso' ).'</a>';

			if ( ! empty( $info['url']['support'] ) &&
				$this->p->check->aop( $ext, true, $this->p->is_avail['aop'] ) ) {
					$links[] = '<a href="'.$info['url']['support'].'">'.
						_x( 'Pro Support', 'plugin action link', 'wpsso' ).'</a>';
			} else {
				if ( ! empty( $info['url']['forum'] ) )
					$links[] = '<a href="'.$info['url']['forum'].'">'.
						_x( 'Support Forum', 'plugin action link', 'wpsso' ).'</a>';

				if ( ! empty( $info['url']['purchase'] ) ) {
					$purchase_url = add_query_arg( 'utm_source', 'plugin-action-links', $info['url']['purchase'] );
					if ( $this->p->check->aop( $ext, false, $this->p->is_avail['aop'] ) )
						$links[] = '<a href="'.$purchase_url.'">'.
							_x( 'Purchase Pro License(s)', 'plugin action link', 'wpsso' ).'</a>';
					else $links[] = '<a href="'.$purchase_url.'">'.
						_x( 'Purchase Pro Version', 'plugin action link', 'wpsso' ).'</a>';
				}
			}

			return $links;
		}

		// this method receives only a partial options array, so re-create a full one
		// wordpress handles the actual saving of the options
		public function registered_setting_sanitation( $opts ) {
			$network = false;

			if ( ! is_array( $opts ) ) {
				add_settings_error( WPSSO_OPTIONS_NAME, 'notarray',
					'<b>'.strtoupper( $this->p->cf['lca'] ).' Error</b> : '.
					__( 'Submitted options are not an array.', 'wpsso' ), 'error' );
				return $opts;
			}

			// get default values, including css from default stylesheets
			$def_opts = $this->p->opt->get_defaults();
			$opts = SucomUtil::restore_checkboxes( $opts );
			$opts = array_merge( $this->p->options, $opts );
			$this->p->notice->trunc();	// clear all messages before sanitation checks
			$opts = $this->p->opt->sanitize( $opts, $def_opts, $network );
			$opts = apply_filters( $this->p->cf['lca'].'_save_options', $opts, WPSSO_OPTIONS_NAME, $network );

			if ( empty( $this->p->options['plugin_clear_on_save'] ) ) {
				// admin url will redirect to essential settings since we're not on a settings page here
				$clear_cache_link = wp_nonce_url( $this->p->util->get_admin_url( '?'.$this->p->cf['lca'].
					'-action=clear_all_cache' ), WpssoAdmin::get_nonce(), WPSSO_NONCE );
	
				$this->p->notice->upd( '<strong>'.__( 'Plugin settings have been saved.', 'wpsso' ).'</strong> <em>'.
					__( 'Please note that webpage content may take several days to reflect changes.', 'wpsso' ).' '.
					sprintf( __( '<a href="%1$s">%2$s</a> now to force a refresh.', 'wpsso' ),
						$clear_cache_link, _x( 'Clear All Cache(s)', 'submit button', 'wpsso' ) ).'</em>' );
			} else {
				$this->p->notice->upd( '<strong>'.__( 'Plugin settings have been saved.', 'wpsso' ).'</strong> '.
					__( 'All caches have also been cleared.', 'wpsso' ) );
				$this->p->util->clear_all_cache( true, __FUNCTION__, true );
			}

			$this->check_tmpl_head_attributes();

			return $opts;
		}

		public function save_site_options() {
			$network = true;

			$page = empty( $_POST['page'] ) ? 
				key( $this->p->cf['*']['lib']['sitesubmenu'] ) :
				$_POST['page'];

			if ( empty( $_POST[ WPSSO_NONCE ] ) ) {	// WPSSO_NONCE is an md5() string
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'nonce token validation post field missing' );
				wp_redirect( $this->p->util->get_admin_url( $page ) );
				exit;
			} elseif ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE ], WpssoAdmin::get_nonce() ) ) {
				$this->p->notice->err( __( 'Nonce token validation failed for network options (update ignored).', 'wpsso' ) );
				wp_redirect( $this->p->util->get_admin_url( $page ) );
				exit;
			} elseif ( ! current_user_can( 'manage_network_options' ) ) {
				$this->p->notice->err( __( 'Insufficient privileges to modify network options.', 'wpsso' ) );
				wp_redirect( $this->p->util->get_admin_url( $page ) );
				exit;
			}

			$def_opts = $this->p->opt->get_site_defaults();
			$opts = empty( $_POST[WPSSO_SITE_OPTIONS_NAME] ) ? $def_opts : 
				SucomUtil::restore_checkboxes( $_POST[WPSSO_SITE_OPTIONS_NAME] );
			$opts = array_merge( $this->p->site_options, $opts );
			$this->p->notice->trunc();	// clear all messages before sanitation checks
			$opts = $this->p->opt->sanitize( $opts, $def_opts, $network );
			$opts = apply_filters( $this->p->cf['lca'].'_save_site_options', $opts, $def_opts, $network );
			update_site_option( WPSSO_SITE_OPTIONS_NAME, $opts );
			$this->p->notice->upd( '<strong>'.__( 'Plugin settings have been saved.', 'wpsso' ).'</strong>' );
			wp_redirect( $this->p->util->get_admin_url( $page ).'&settings-updated=true' );
			exit;	// stop here
		}

		public function load_single_page() {
			wp_enqueue_script( 'postbox' );
			$this->p->admin->submenu[$this->menu_id]->add_meta_boxes();
		}

		public function load_setting_page() {
			$lca = $this->p->cf['lca'];
			$action_query = $lca.'-action';
			wp_enqueue_script( 'postbox' );

			if ( ! empty( $_GET[$action_query] ) ) {
				$action_name = SucomUtil::sanitize_hookname( $_GET[$action_query] );
				if ( empty( $_GET[ WPSSO_NONCE ] ) ) {	// WPSSO_NONCE is an md5() string
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'nonce token validation query field missing' );
				} elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE ], WpssoAdmin::get_nonce() ) ) {
					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".',
						'wpsso' ), 'admin', $action_name ) );
				} else {
					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE ) );
					switch ( $action_name ) {
						case 'check_for_updates': 
							$info = $this->p->cf['plugin'][$lca];
							$um_info = $this->p->cf['plugin'][$lca.'um'];

							if ( $this->p->is_avail['util']['um'] ) {
								WpssoAdmin::set_readme_info( false );	// $read_cache = false
								$wpssoum =& WpssoUm::get_instance();
								if ( isset( $wpssoum->update ) )
									$wpssoum->update->check_for_updates( null, true, false );	// $use_cache = false
								else $this->p->notice->err( sprintf( __( 'The <b>%2$s</b> extension is not initialized properly. Please make sure you are using the latest versions of %1$s and %2$s.', 'wpsso' ), $info['name'], $um_info['name'] ) );
							} else $this->p->notice->err( sprintf( __( 'The <b>%1$s</b> extension must be active in order to check for Pro version updates.', 'wpsso' ), $um_info['name'] ) );
							break;

						case 'clear_all_cache': 
							$this->p->util->clear_all_cache( true );	// $clear_external = true
							break;

						case 'clear_metabox_prefs': 
							$user_id = get_current_user_id();
							$user = get_userdata( $user_id );
							//$user_name = trim( $user->first_name.' '.$user->last_name );
							$user_name = $user->display_name;
							WpssoUser::delete_metabox_prefs( $user_id );
							$this->p->notice->upd( sprintf( __( 'Metabox layout preferences for user ID #%d "%s" have been reset.',
								'wpsso' ), $user_id, $user_name ) );
							break;

						case 'clear_hidden_notices': 
							$user_id = get_current_user_id();
							$user = get_userdata( $user_id );
							//$user_name = trim( $user->first_name.' '.$user->last_name );
							$user_name = $user->display_name;
							delete_user_option( $user_id, WPSSO_DISMISS_NAME );
							$this->p->notice->upd( sprintf( __( 'Hidden notices for user ID #%d "%s" have been cleared.',
								'wpsso' ), $user_id, $user_name ) );
							break;

						case 'change_show_options': 
							if ( isset( $this->p->cf['form']['show_options'][$_GET['show-opts']] ) ) {
								$this->p->notice->upd( sprintf( 'Option preference saved &mdash; viewing "%s" by default.',
									$this->p->cf['form']['show_options'][$_GET['show-opts']] ) );
								WpssoUser::save_pref( array( 'show_opts' => $_GET['show-opts'] ) );
							}
							$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'show-opts' ) );
							break;

						case 'modify_tmpl_head_attributes': 
							$this->modify_tmpl_head_attributes();
							break;

						default: 
							do_action( $lca.'_load_setting_page_'.$action_name, 
								$this->pagehook, $this->menu_id, $this->menu_name, $this->menu_lib );
							break;
					}
				}
			}

			// add child metaboxes first, since they contain the default reset_metabox_prefs()
			$this->p->admin->submenu[$this->menu_id]->add_meta_boxes();

			// show the purchase metabox on all pages
			if ( empty( $this->p->options['plugin_'.$lca.'_tid'] ) || 
				! $this->p->check->aop( $lca, true, $this->p->is_avail['aop'] ) ) {

				add_meta_box( $this->pagehook.'_purchase',
					_x( 'Pro / Power-User Version', 'metabox title (side)', 'wpsso' ), 
						array( &$this, 'show_metabox_purchase' ), $this->pagehook, 'side' );

				$this->p->m['util']['user']->reset_metabox_prefs( $this->pagehook, 
					array( 'purchase' ), null, 'side', true );
			}

			$this->p->admin->submenu[$this->menu_id]->add_side_meta_boxes();
		}

		protected function add_side_meta_boxes() {

			// show the help metabox on all pages
			add_meta_box( $this->pagehook.'_help',
				_x( 'Help and Support', 'metabox title (side)', 'wpsso' ), 
					array( &$this, 'show_metabox_help' ), $this->pagehook, 'side' );

			// only show under in the plugin settings pages
			// (don't show under the WordPress settings menu or in network settings pages)
			if ( $this->menu_lib === 'submenu' ) {
				add_meta_box( $this->pagehook.'_status_gpl',
					_x( 'Free / Basic Features', 'metabox title (side)', 'wpsso' ), 
						array( &$this, 'show_metabox_status_gpl' ), $this->pagehook, 'side' );

				add_meta_box( $this->pagehook.'_status_pro',
					_x( 'Pro Version Features', 'metabox title (side)', 'wpsso' ), 
						array( &$this, 'show_metabox_status_pro' ), $this->pagehook, 'side' );
			}
		}

		protected function add_meta_boxes() {
		}

		public function show_setting_page( $sidebar = true ) {

			if ( ! $this->is_setting() )
				settings_errors( WPSSO_OPTIONS_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->show_html( print_r( $this->p->is_avail, true ), 'available features' );
				$this->p->debug->show_html( print_r( WpssoUtil::active_plugins(), true ), 'active plugins' );
				$this->p->debug->show_html( null, 'debug log' );
			}

			$menu_ext = $this->menu_ext;
			if ( empty( $menu_ext ) )
				$menu_ext = $this->p->cf['lca'];

			$this->set_form_property( $menu_ext );	// set form for side boxes and show_form_content()

			echo '<div class="wrap" id="'.$this->pagehook.'">'."\n";
			echo '<h1>'.self::$pkg[$this->menu_ext]['short'].' &ndash; '.
				$this->menu_name.'</h1>'."\n";

			if ( $sidebar === false ) {
				echo '<div id="poststuff" class="metabox-holder">'."\n";
				echo '<div id="post-body">'."\n";
				echo '<div id="post-body-content">'."\n";
			} else {
				echo '<div id="poststuff" class="metabox-holder has-right-sidebar">'."\n";
				echo '<div id="side-info-column" class="inner-sidebar">'."\n";
				do_meta_boxes( $this->pagehook, 'side', null );
				echo '</div><!-- .inner-sidebar -->'."\n";
				echo '<div id="post-body" class="has-sidebar">'."\n";
				echo '<div id="post-body-content" class="has-sidebar-content">'."\n";
			}

			$this->show_form_content();
			?>
						</div><!-- .post-body-content -->
					</div><!-- .post-body -->
				</div><!-- .metabox-holder -->
			</div><!-- .wrap -->
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( 
					function($) {
						// close postboxes that should be closed
						$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
						// postboxes setup
						postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
					}
				);
				//]]>
			</script>
			<?php
		}

		public function wp_profile_updated_redirect( $location, $status ) {
			if ( strpos( $location, 'updated=' ) !== false && 
				strpos( $location, 'wp_http_referer=' ) ) {

				// match wordpress behavior (users page for admins, profile page for everyone else)
				$menu_lib = current_user_can( 'list_users' ) ? 'users' : 'profile';
				$parent_slug = $this->p->cf['wp']['admin'][$menu_lib]['page'];
				$referer_match = '/'.$parent_slug.'?page='.$this->p->cf['lca'].'-';
				parse_str( parse_url( $location, PHP_URL_QUERY ), $parts );

				if ( strpos( $parts['wp_http_referer'], $referer_match ) ) {
					$this->p->notice->upd( __( 'Profile updated.' ) );
					return add_query_arg( 'updated', true, $parts['wp_http_referer'] );
				}
			}
			return $location;
		}

		protected function show_form_content() {

			$lca = $this->p->cf['lca'];

			if ( $this->menu_lib === 'profile' ) {

				$user_id = get_current_user_id();
				$profileuser = get_user_to_edit( $user_id );
				$current_color = get_user_option( 'admin_color', $user_id );
				if ( empty( $current_color ) )
					$current_color = 'fresh';

				// match wordpress behavior (users page for admins, profile page for everyone else)
				$admin_url = current_user_can( 'list_users' ) ? 
					$this->p->util->get_admin_url( $this->menu_id, null, 'users' ) :
					$this->p->util->get_admin_url( $this->menu_id, null, $this->menu_lib );

				echo '<form name="'.$lca.'" id="'.$lca.'_setting_form" action="user-edit.php" method="post">'."\n";
				echo '<input type="hidden" name="wp_http_referer" value="'.$admin_url.'" />'."\n";
				echo '<input type="hidden" name="action" value="update" />'."\n";
				echo '<input type="hidden" name="user_id" value="'.$user_id.'" />'."\n";
				echo '<input type="hidden" name="nickname" value="'.$profileuser->nickname.'" />'."\n";
				echo '<input type="hidden" name="email" value="'.$profileuser->user_email.'" />'."\n";
				echo '<input type="hidden" name="admin_color" value="'.$current_color.'" />'."\n";
				echo '<input type="hidden" name="rich_editing" value="'.$profileuser->rich_editing.'" />'."\n";
				echo '<input type="hidden" name="comment_shortcuts" value="'.$profileuser->comment_shortcuts.'" />'."\n";
				echo '<input type="hidden" name="admin_bar_front" value="'._get_admin_bar_pref( 'front', $user_id ).'" />'."\n";

				wp_nonce_field( 'update-user_'.$user_id );

			} elseif ( $this->menu_lib === 'setting' || $this->menu_lib === 'submenu' ) {

				echo '<form name="'.$lca.'" id="'.$lca.'_setting_form" action="options.php" method="post">'."\n";

				settings_fields( $lca.'_setting' ); 

			} elseif ( $this->menu_lib === 'sitesubmenu' ) {

				echo '<form name="'.$lca.'" id="'.$lca.'_setting_form" action="edit.php?action='.
					WPSSO_SITE_OPTIONS_NAME.'" method="post">'."\n";
				echo '<input type="hidden" name="page" value="'.$this->menu_id.'" />';

			} else return;

			wp_nonce_field( WpssoAdmin::get_nonce(), WPSSO_NONCE );	// WPSSO_NONCE is an md5() string
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			do_meta_boxes( $this->pagehook, 'normal', null ); 

			do_action( $this->p->cf['lca'].'_form_content_metaboxes_'.
				SucomUtil::sanitize_hookname( $this->menu_id ), $this->pagehook );

			switch ( $this->menu_id ) {
				case 'setup':
				case 'sitesetup':
					break;
				default:
					if ( $this->menu_lib === 'profile' )
						echo $this->get_submit_buttons( _x( 'Save All Profile Settings',
							'submit button', 'wpsso' ) );
					else echo $this->get_submit_buttons();
					break;
			}
			echo '</form>', "\n";
		}

		protected function get_submit_buttons( $submit_text = '', $class = 'submit-buttons' ) {

			$lca = $this->p->cf['lca'];
			$show_opts_next = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf['form']['show_options'] );
			$show_opts_text = sprintf( _x( 'View %s by Default', 'submit button', 'wpsso' ),
				_x( $this->p->cf['form']['show_options'][$show_opts_next], 'option value', 'wpsso' ) );
			$show_opts_url = $this->p->util->get_admin_url( '?'.$lca.'-action=change_show_options&show-opts='.$show_opts_next );

			if ( empty( $submit_text ) ) 
				$submit_text = _x( 'Save All Plugin Settings', 'submit button', 'wpsso' );

			// Save All Plugin Settings and View All / Basic Options by Default
			$action_buttons = '<input type="submit" class="button-primary" value="'.$submit_text.'" />'.
				$this->form->get_button( $show_opts_text, 'button-secondary button-highlight', null, 
					wp_nonce_url( $show_opts_url, WpssoAdmin::get_nonce(), WPSSO_NONCE ) ).'<br/>';	// WPSSO_NONCE is an md5() string

			// Secondary Action Buttons
			foreach ( apply_filters( $lca.'_secondary_action_buttons', array(
				'clear_all_cache' => _x( 'Clear All Cache(s)', 'submit button', 'wpsso' ),
				'check_for_updates' => _x( 'Check for Pro Update(s)', 'submit button', 'wpsso' ),
				'clear_metabox_prefs' => _x( 'Reset Metabox Layout', 'submit button', 'wpsso' ),
				'clear_hidden_notices' => _x( 'Reset Hidden Notices', 'submit button', 'wpsso' ),
			), $this->menu_id, $this->menu_name, $this->menu_lib ) as $action => $label ) {

				switch ( $action ) {
					case 'clear_all_cache':
						// only show the clear_all_cache button on setting and submenu pages
						if ( $this->menu_lib !== 'setting' && 
							$this->menu_lib !== 'submenu' )
								continue 2;
						break;
					case 'check_for_updates':
						// don't show the check_for_updates button on profile pages
						if ( $this->menu_lib === 'profile' ||
							empty( $this->p->options['plugin_'.$lca.'_tid'] ) )
								continue 2;
						break;
				}

				$action_buttons .= $this->form->get_button( $label, 'button-secondary', null, 
					wp_nonce_url( $this->p->util->get_admin_url( '?'.$lca.'-action='.$action ),
						WpssoAdmin::get_nonce(), WPSSO_NONCE ) );	// WPSSO_NONCE is an md5() string
			}

			return '<div class="'.$class.'">'.$action_buttons.'</div>';
		}

		public function show_metabox_version_info() {

			$lca = $this->p->cf['lca'];
			WpssoAdmin::set_readme_info();	// $read_cache = true
			echo '<table class="sucom-setting '.$lca.' side">';
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( empty( $info['version'] ) )	// only active extensions
					continue;

				$installed_version = $info['version'];	// static value from config
				$installed_style = '';
				$stable_version = __( 'Not Available', 'wpsso' );	// default value
				$latest_version = __( 'Not Available', 'wpsso' );	// default value
				$latest_notice = '';
				$changelog_url = $info['url']['changelog'];

				if ( ! empty( self::$readme[$ext]['stable_tag'] ) ) {
					$stable_version = self::$readme[$ext]['stable_tag'];

					if ( is_array( self::$readme[$ext]['upgrade_notice'] ) ) {
						// hooked by the update manager to apply the version filter
						$upgrade_notice = apply_filters( $lca.'_readme_upgrade_notices',
							self::$readme[$ext]['upgrade_notice'], $ext );
						reset( $upgrade_notice );
						$latest_version = key( $upgrade_notice );
						$latest_notice = $upgrade_notice[$latest_version];
					}

					// hooked by the update manager to check installed version against
					// the latest version, if a non-stable filter is selected for that
					// plugin / extension
					if ( apply_filters( $lca.'_newer_version_available',
						version_compare( $installed_version, $stable_version, '<' ),
							$ext, $installed_version, $stable_version, $latest_version ) )
								$installed_style = 'style="background-color:#f00;"';	// red
					// version is current but not stable (alpha characters found in version string)
					elseif ( preg_match( '/[a-z]/', $installed_version ) )
						$installed_style = 'style="background-color:#ff0;"';	// yellow
					// version is current
					else $installed_style = 'style="background-color:#0f0;"';	// green
				}

				echo '<tr><td colspan="2"><h4>'.self::$pkg[$ext]['short'].'</h4></td></tr>';

				echo '<tr><th class="side">'._x( 'Installed', 'plugin status label', 'wpsso' ).':</th>
					<td class="side_version" '.$installed_style.'>'.$installed_version.'</td></tr>';

				echo '<tr><th class="side">'._x( 'Stable', 'plugin status label', 'wpsso' ).':</th>
					<td class="side_version">'.$stable_version.'</td></tr>';

				echo '<tr><th class="side">'._x( 'Latest', 'plugin status label', 'wpsso' ).':</th>
					<td class="side_version">'.$latest_version.'</td></tr>';

				echo '<tr><td colspan="2" class="latest_notice"><p>Version '.$latest_version.' '.$latest_notice.'</p>'.
					'<p><a href="'.$changelog_url.'" target="_blank">'.
						sprintf( _x( 'View %s changelog...', 'following plugin status version numbers',
							'wpsso' ), $info['short'] ).'</a></p></td></tr>';
			}
			echo '</table>';
		}

		public function show_metabox_status_gpl() {

			$lca = $this->p->cf['lca'];
			$metabox = 'status';
			$plugin_count = 0;

			foreach ( $this->p->cf['plugin'] as $ext => $info )
				if ( isset( $info['lib']['gpl'] ) )
					$plugin_count++;

			echo '<table class="sucom-setting '.$lca.' side" style="margin-bottom:10px;">';

			/*
			 * GPL version features
			 */
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( ! isset( $info['lib']['gpl'] ) )
					continue;

				if ( $ext === $lca ) {	// features for this plugin
					$features = array(
						'(tool) Debug Logging Enabled' => array(
							'classname' => 'SucomDebug',
						),
						'(code) Facebook / Open Graph Meta Tags' => array( 
							'status' => class_exists( $lca.'opengraph' ) ? 'on' : 'rec',
						),
						'(code) Google Author / Person Markup' => array( 
							'status' => $this->p->options['schema_person_json'] ? 'on' : 'off',
						),
						'(code) Google Publisher / Organization Markup' => array(
							'status' => $this->p->options['schema_organization_json'] ? 'on' : 'off',
						),
						'(code) Google Website Markup' => array(
							'status' => $this->p->options['schema_website_json'] ? 'on' : 'rec',
						),
						'(code) Schema Meta Property Containers' => array(
							'status' => apply_filters( $lca.'_add_schema_noscript_array', 
								$this->p->options['schema_add_noscript'] ) ? 'on' : 'off',
						),
						'(code) Twitter Card Meta Tags' => array( 
							'status' => class_exists( $lca.'twittercard' ) ? 'on' : 'rec',
						),
					);
				} else $features = array();

				self::$pkg[$ext]['purchase'] = '';

				$features = apply_filters( $ext.'_'.$metabox.'_gpl_features', $features, $ext, $info, self::$pkg[$ext] );

				if ( ! empty( $features ) ) {
					if ( $plugin_count > 1 )
						echo '<tr><td colspan="3"><h4>'.$info['short'].'</h4></td></tr>';
					$this->show_plugin_status( $ext, $info, $features );
				}
			}
			echo '</table>';
		}

		public function show_metabox_status_pro() {

			$lca = $this->p->cf['lca'];
			$metabox = 'status';
			$plugin_count = 0;

			foreach ( $this->p->cf['plugin'] as $ext => $info )
				if ( isset( $info['lib']['pro'] ) )
					$plugin_count++;

			echo '<table class="sucom-setting '.$lca.' side" style="margin-bottom:10px;">';

			/*
			 * Pro version features
			 */
			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( ! isset( $info['lib']['pro'] ) )
					continue;

				$features = array();

				self::$pkg[$ext]['purchase'] = empty( $info['url']['purchase'] ) ? 
					'' : add_query_arg( 'utm_source', 'status-pro-feature', $info['url']['purchase'] );

				foreach ( $info['lib']['pro'] as $sub => $libs ) {
					if ( $sub === 'admin' )	// skip status for admin menus and tabs
						continue;
					foreach ( $libs as $id_key => $label ) {
						/* 
						 * Example:
						 *	'article' => 'Item Type Article',
						 *	'article#news:no_load' => 'Item Type NewsArticle',
						 *	'article#tech:no_load' => 'Item Type TechArticle',
						 */
						list( $id, $stub, $action ) = SucomUtil::get_lib_stub_action( $id_key );
						$classname = SucomUtil::sanitize_classname( $ext.'pro'.$sub.$id, false );	// $underscore = false
						$status_off = $this->p->is_avail[$sub][$id] ? 'rec' : 'off';
						$features[$label] = array( 
							'td_class' => self::$pkg[$ext]['aop'] ? '' : 'blank',
							'purchase' => self::$pkg[$ext]['purchase'],
							'status' => class_exists( $classname ) ?
								( self::$pkg[$ext]['aop'] ?
									'on' : $status_off ) : $status_off,
						);
					}
				}

				$features = apply_filters( $ext.'_'.$metabox.'_pro_features', $features, $ext, $info, self::$pkg[$ext] );

				if ( ! empty( $features ) ) {
					if ( $plugin_count > 1 )
						echo '<tr><td colspan="3"><h4>'.$info['short'].'</h4></td></tr>';
					$this->show_plugin_status( $ext, $info, $features );
				}
			}
			echo '</table>';
		}

		private function show_plugin_status( &$ext = '', &$info = array(), &$features = array() ) {

			$status_info = array( 
				'on' => array(
					'img' => 'green-circle.png',
					'title' => __( 'Module is enabled', 'wpsso' ),
				),
				'off' => array(
					'img' => 'gray-circle.png',
					'title' => __( 'Module is disabled / not loaded', 'wpsso' ),
				),
				'rec' => array(
					'img' => 'red-circle.png',
					'title' => __( 'Module recommended but disabled / not available', 'wpsso' ),
				),
			);

			uksort( $features, array( __CLASS__, 'sort_plugin_features' ) );

			foreach ( $features as $label => $arr ) {
				if ( isset( $arr['classname'] ) )
					$status_key = class_exists( $arr['classname'] ) ? 'on' : 'off';
				elseif ( isset( $arr['constant'] ) )
					$status_key = SucomUtil::get_const( $arr['constant'] ) ? 'on' : 'off';
				elseif ( isset( $arr['status'] ) )
					$status_key = $arr['status'];
				else $status_key = '';

				if ( ! empty( $status_key ) ) {
					$td_class = empty( $arr['td_class'] ) ? '' : ' '.$arr['td_class'];
					$icon_type = preg_match( '/^\(([a-z\-]+)\) (.*)/', $label, $match ) ? $match[1] : 'admin-generic';
					$icon_title = __( 'Generic feature module', 'wpsso' );
					$label_text = empty( $match[2] ) ? $label : $match[2];
					$label_text = empty( $arr['label'] ) ? $label_text : $arr['label'];
					$purchase_url = $status_key === 'rec' && ! empty( $arr['purchase'] ) ? $arr['purchase'] : '';

					switch ( $icon_type ) {
						case 'api':
							$icon_type = 'controls-repeat';
							$icon_title = __( 'Service API module', 'wpsso' );
							break;
						case 'code':
							$icon_type = 'editor-code';
							$icon_title = __( 'Meta tag and markup module', 'wpsso' );
							break;
						case 'plugin':
							$icon_type = 'admin-plugins';
							$icon_title = __( 'Plugin integration module', 'wpsso' );
							break;
						case 'sharing':
							$icon_type = 'screenoptions';
							$icon_title = __( 'Sharing functionality module', 'wpsso' );
							break;
						case 'tool':
							$icon_type = 'admin-tools';
							$icon_title = __( 'Additional functionality module', 'wpsso' );
							break;
					}

					echo '<tr>'.
					'<td class="side"><span class="dashicons dashicons-'.$icon_type.'" title="'.$icon_title.'"></span></td>'.
					'<td class="side'.$td_class.'">'.$label_text.'</td>'.
					'<td class="side">'.
						( $purchase_url ? '<a href="'.$purchase_url.'" target="_blank">' : '' ).
						'<img src="'.WPSSO_URLPATH.'images/'.
							$status_info[$status_key]['img'].'" width="12" height="12" title="'.
							$status_info[$status_key]['title'].'"/>'.
						( $purchase_url ? '</a>' : '' ).
					'</td>'.
					'</tr>'."\n";
				}
			}
		}

		private static function sort_plugin_features( $feature_a, $feature_b ) {
			return strcasecmp( self::feature_priority( $feature_a ), 
				self::feature_priority( $feature_b ) );
		}

		private static function feature_priority( $feature ) {
			if ( strpos( $feature, '(tool)' ) === 0 )
				return '(10) '.$feature;
			else return $feature;
		}

		public function show_metabox_purchase() {
			$lca = $this->p->cf['lca'];
			$info =& $this->p->cf['plugin'][$lca];
			$purchase_url = empty( $info['url']['purchase'] ) ? 
				'' : add_query_arg( 'utm_source', 'side-purchase', $info['url']['purchase'] );
			echo '<table class="sucom-setting '.$lca.' side"><tr><td>';
			echo $this->p->msgs->get( 'side-purchase' );
			echo '<p class="centered">';
			echo $this->form->get_button( ( $this->p->is_avail['aop'] ? 
				_x( 'Purchase Pro License(s)', 'submit button', 'wpsso' ) :
				_x( 'Purchase Pro Version', 'submit button', 'wpsso' ) ), 
					'button-primary', null, $purchase_url, true );
			echo '</p></td></tr></table>';
		}

		public function show_metabox_help() {
			$lca = $this->p->cf['lca'];
			echo '<table class="sucom-setting '.$lca.' side"><tr><td>';
			$this->show_follow_icons();
			echo $this->p->msgs->get( 'side-help-support' );

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( empty( $info['version'] ) )	// filter out extensions that are not installed
					continue;

				$help_links = '';

				if ( ! empty( $info['url']['faq'] ) ) {
					$help_links .= '<li>'.sprintf( __( 'Review the <a href="%s" target="_blank">Frequently Asked Questions</a>',
						'wpsso' ), $info['url']['faq'] );
					if ( ! empty( $info['url']['notes'] ) )
						$help_links .= ' '.sprintf( __( 'and <a href="%s" target="_blank">Additional Documentation</a>',
							'wpsso' ), $info['url']['notes'] );
					$help_links .= '</li>'."\n";
				}

				if ( ! empty( $info['url']['support'] ) && self::$pkg[$ext]['aop'] )
					$help_links .= '<li>'.sprintf( __( 'Open a <a href="%s" target="_blank">Priority Support Ticket</a> (Pro version)',
						'wpsso' ), $info['url']['support'] ).'</li>'."\n";
				elseif ( ! empty( $info['url']['forum'] ) )
					$help_links .= '<li>'.sprintf( __( 'Post in the <a href="%s" target="_blank">Community Support Forum</a> (Free version)',
						'wpsso' ), $info['url']['forum'] ).'</li>'."\n";

				if ( ! empty( $help_links ) ) {
					echo '<p><strong>'.$info['short'].'</strong></p>'."\n";
					echo '<ul>'.$help_links.'</ul>'."\n";
				}
			}

			echo '</td></tr></table>';
		}

		protected function show_follow_icons() {
			echo '<div class="follow_icons">';
			$img_size = $this->p->cf['follow']['size'];
			foreach ( $this->p->cf['follow']['src'] as $img_rel => $url )
				echo '<a href="'.$url.'" target="_blank"><img src="'.WPSSO_URLPATH.$img_rel.'" 
					width="'.$img_size.'" height="'.$img_size.'" border="0" /></a> ';
			echo '</div>';
		}

		public static function get_nonce() {
			return __FILE__.
				SucomUtil::get_const( 'NONCE_KEY' ).
				SucomUtil::get_const( 'NONCE_SALT' );
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
			if ( $menu_id === false )
				$menu_id = $this->menu_id;
			return isset( $this->p->cf['*']['lib'][$lib_name][$menu_id] ) ? true : false;
		}

		public function licenses_metabox_content( $network = false ) {

			$num = 0;
			$lca = $this->p->cf['lca'];
			$total = count( $this->p->cf['plugin'] );

			echo '<table class="sucom-setting '.$lca.' licenses-metabox"
				style="padding-bottom:10px">'."\n";
			echo '<tr><td colspan="'.( $network ? 5 : 4 ).'">'.
				$this->p->msgs->get( 'info-plugin-tid'.
					( $network ? '-network' : '' ) ).'</td></tr>'."\n";

			foreach ( WpssoConfig::get_ext_sorted( true ) as $ext => $info ) {
				$num++;
				$links = '';
				$img_href = '';
				$view_text = _x( 'View Plugin Details',
					'plugin action link', 'wpsso' );

				if ( ! empty( $info['slug'] ) && 
					( empty( $info['url']['latest'] ) ||
						$this->p->is_avail['util']['um'] ) ) {

					$img_href = add_query_arg( array(
						'tab' => 'plugin-information',
						'plugin' => $info['slug'],
						'TB_iframe' => 'true',
						'width' => 600,
						'height' => 550
					), is_multisite() ? 
						network_admin_url( 'plugin-install.php', null ) : 
						get_admin_url( null, 'plugin-install.php' ) );

					// check to see if plugin is installed or not
					if ( is_dir( WP_PLUGIN_DIR.'/'.$info['slug'] ) ) {
						$update_plugins = get_site_transient( 'update_plugins' );
						if ( isset( $update_plugins->response ) ) {
							foreach ( (array) $update_plugins->response as $file => $plugin ) {
								if ( $plugin->slug === $info['slug'] ) {
									$view_text = '<font color="red">'._x( 'View Plugin Details + Update',
										'plugin action link', 'wpsso' ).'</font>';
									break;
								}
							}
						}
					} else $view_text = _x( 'View Plugin Details + Install', 'plugin action link', 'wpsso' );

					$links .= ' | <a href="'.$img_href.'" class="thickbox">'.$view_text.'</a>';

				} elseif ( ! empty( $info['url']['download'] ) ) {
					$img_href = $info['url']['download'];
					$links .= ' | <a href="'.$img_href.'" target="_blank">'._x( 'Plugin Description Page',
						'plugin action link', 'wpsso' ).'</a>';
				}

				if ( ! empty( $info['url']['latest'] ) )
					$links .= ' | <a href="'.$info['url']['latest'].'">'._x( 'Download Latest Version',
						'plugin action link', 'wpsso' ).'</a> (ZIP)';

				if ( ! empty( $info['url']['purchase'] ) ) {
					$purchase_url = add_query_arg( 'utm_source', 'license-action-links', $info['url']['purchase'] );
					if ( $lca === $ext || $this->p->check->aop( $lca, false, $this->p->is_avail['aop'] ) )
						$links .= ' | <a href="'.$purchase_url.'" target="_blank">'.
							_x( 'Purchase Pro License(s)', 'plugin action link', 'wpsso' ).'</a>';
					else $links .= ' | <em>'._x( 'Purchase Pro License(s)', 'plugin action link', 'wpsso' ).'</em>';
				}

				if ( ! empty( $info['img']['icon_small'] ) )
					$img_src = 'src="'.$info['img']['icon_small'].'"';
				else $img_src = 'src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="';

				if ( ! empty( $info['img']['icon_medium'] ) )
					$img_src .= ' srcset="'.$info['img']['icon_medium'].' 256w"';

				// logo image
				echo '<tr><td style="width:148px; padding:10px;" rowspan="3" valign="top" align="left">'."\n";
				if ( ! empty( $img_href ) ) 
					echo '<a href="'.$img_href.'"'.( strpos( $img_href, 'TB_iframe' ) ?
						' class="thickbox"' : ' target="_blank"' ).'>';
				echo '<img '.$img_src.' width="128" height="128">';
				if ( ! empty( $img_href ) ) 
					echo '</a>';
				echo '</td>'."\n";

				// plugin name
				echo '<td colspan="'.( $network ? 4 : 3 ).'" style="padding:10px 0 0 0;">
					<p><strong>'.$info['name'].'</strong></p>';

				if ( ! empty( $info['desc'] ) )
					echo '<p>'._x( $info['desc'], 'plugin description', 'wpsso' ).'</p>';

				if ( ! empty( $links ) )
					echo '<p>'.trim( $links, ' |' ).'</p>';

				echo '</td></tr>'."\n";

				if ( $network ) {
					if ( ! empty( $info['update_auth'] ) || 
						! empty( $this->p->options['plugin_'.$ext.'_tid'] ) ) {

						if ( $lca === $ext || self::$pkg[$lca]['aop'] ) {
							echo '<tr>'.$this->form->get_th_html( _x( 'Pro Authentication ID',
								'option label', 'wpsso' ), 'medium nowrap' ).
							'<td class="tid">'.$this->form->get_input( 'plugin_'.$ext.'_tid', 'tid mono' ).'</td>'.
							$this->p->admin->get_site_use( $this->form, true, 'plugin_'.$ext.'_tid', true );
						} else {
							echo '<tr>'.$this->form->get_th_html( _x( 'Pro Authentication ID',
								'option label', 'wpsso' ), 'medium nowrap' ).
							'<td class="blank">'.( empty( $this->p->options['plugin_'.$ext.'_tid'] ) ?
								$this->form->get_no_input( 'plugin_'.$ext.'_tid', 'tid mono' ) :
								$this->form->get_input( 'plugin_'.$ext.'_tid', 'tid mono' ) ).
							'</td><td colspan="2">'.( self::$pkg[$ext]['aop'] ? '' :
								$this->p->msgs->get( 'pro-option-msg' ) ).'</td></tr>'."\n";
						}
					} else echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>'."\n";
				} else {
					if ( ! empty( $info['update_auth'] ) || 
						! empty( $this->p->options['plugin_'.$ext.'_tid'] ) ) {

						if ( $lca === $ext || self::$pkg[$lca]['aop'] ) {
							$qty_used = class_exists( 'SucomUpdate' ) ?
								SucomUpdate::get_option( $ext, 'qty_used' ) : false;
							echo '<tr>'.$this->form->get_th_html( _x( 'Pro Authentication ID',
								'option label', 'wpsso' ), 'medium nowrap' ).
							'<td class="tid">'.$this->form->get_input( 'plugin_'.$ext.'_tid', 'tid mono' ).
							'</td><td><p>'.( empty( $qty_used ) ? '' :
								$qty_used.' Licenses Assigned' ).'</p></td></tr>'."\n";
						} else {
							echo '<tr>'.$this->form->get_th_html( _x( 'Pro Authentication ID',
								'option label', 'wpsso' ), 'medium nowrap' ).
							'<td class="blank">'.( empty( $this->p->options['plugin_'.$ext.'_tid'] ) ?
								$this->form->get_no_input( 'plugin_'.$ext.'_tid', 'tid mono' ) :
								$this->form->get_input( 'plugin_'.$ext.'_tid', 'tid mono' ) ).
							'</td><td>'.( self::$pkg[$ext]['aop'] ? '' :
								$this->p->msgs->get( 'pro-option-msg' ) ).'</td></tr>'."\n";
						}
					} else echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</tr>'."\n";
				}

				echo '<tr><td'.( $num < $total ? ' style="border-bottom:1px dotted #ddd;"' : '' ).
					' colspan="'.( $network ? 4 : 3 ).'">&nbsp;</td></tr>'."\n";
			}
			echo '</table>'."\n";
		}

		public function conflict_warnings() {
			if ( ! is_admin() ) 	// just in case
				return;

			$lca = $this->p->cf['lca'];
			$err_pre =  __( 'Plugin conflict detected', 'wpsso' ) . ' &mdash; ';
			$log_pre = 'plugin conflict detected - ';	// don't translate the debug 

			// PHP
			if ( ! extension_loaded( 'curl' ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'php curl extension is not loaded' );
				$this->p->notice->err( sprintf( __( '<a href="%s" target="_blank">PHP Client URL Library (cURL) extension</a> is not loaded.', 'wpsso' ), 'http://php.net/manual/en/book.curl.php' ).' '.__( 'please contact your hosting provider to have the missing PHP extension installed and/or enabled.', 'wpsso' ) );
			}
			if ( ! extension_loaded( 'json' ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'php json extension is not loaded' );
				$this->p->notice->err( sprintf( __( '<a href="%s" target="_blank">PHP JavaScript Object Notation (JSON) extension</a> is not loaded.', 'wpsso' ), 'http://php.net/manual/en/book.json.php' ).' '.__( 'please contact your hosting provider to have the missing PHP extension installed and/or enabled.', 'wpsso' ) );
			}
			if ( ! extension_loaded( 'mbstring' ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'php mbstring extension is not loaded' );
				$this->p->notice->err( sprintf( __( '<a href="%s" target="_blank">PHP Multibyte String extension</a> is not loaded.', 'wpsso' ), 'http://php.net/manual/en/book.mbstring.php' ).' '.__( 'please contact your hosting provider to have the missing PHP extension installed and/or enabled.', 'wpsso' ) );
			}

			// WordPress
			if ( ! get_option( 'blog_public' ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'blog_public option is disabled' );
				$this->p->notice->err( sprintf( __( 'The WordPress <a href="%s">Search Engine Visibility</a> option is set to discourage search engine and social crawlers from indexing this site. This is not compatible with the purpose of sharing content on social websites &mdash; please uncheck that option to allow search engines and social crawlers to access your content.', 'wpsso' ), get_admin_url( null, 'options-reading.php' ) ) );
			}

			// Yoast SEO
			if ( $this->p->is_avail['seo']['wpseo'] ) {
				$opts = get_option( 'wpseo_social' );
				if ( ! empty( $opts['opengraph'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'wpseo opengraph meta data option is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>Add Open Graph meta data</strong> option under the <a href="%s">Yoast SEO / Social / Facebook</a> settings tab.', 'wpsso' ), get_admin_url( null, 'admin.php?page=wpseo_social#top#facebook' ) ) );
				}
				if ( ! empty( $opts['twitter'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'wpseo twitter meta data option is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>Add Twitter card meta data</strong> option under the <a href="%s">Yoast SEO / Social / Twitter</a> settings tab.', 'wpsso' ), get_admin_url( null, 'admin.php?page=wpseo_social#top#twitterbox' ) ) );
				}
				if ( ! empty( $opts['googleplus'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'wpseo googleplus meta data option is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>Add Google+ specific post meta data</strong> option under the <a href="%s">Yoast SEO / Social / Google+</a> settings tab.', 'wpsso' ), get_admin_url( null, 'admin.php?page=wpseo_social#top#google' ) ) );
				}
				if ( ! empty( $opts['plus-publisher'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'wpseo google plus publisher option is defined' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please remove the <strong>Google Publisher Page</strong> value entered under the <a href="%s">Yoast SEO / Social / Google+</a> settings tab.', 'wpsso' ), get_admin_url( null, 'admin.php?page=wpseo_social#top#google' ) ) );
				}
			}

			// SEO Ultimate
			if ( $this->p->is_avail['seo']['seou'] ) {
				$opts = get_option( 'seo_ultimate' );
				if ( ! empty( $opts['modules'] ) && is_array( $opts['modules'] ) ) {
					if ( array_key_exists( 'opengraph', $opts['modules'] ) && $opts['modules']['opengraph'] !== -10 ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $log_pre.'seo ultimate opengraph module is enabled' );
						$this->p->notice->err( $err_pre.sprintf( __( 'please disable the <strong>Open Graph Integrator</strong> module in the <a href="%s">SEO Ultimate Module Manager</a>.', 'wpsso' ), get_admin_url( null, 'admin.php?page=seo' ) ) );
					}
				}
			}

			// All in One SEO Pack
			if ( $this->p->is_avail['seo']['aioseop'] ) {
				$opts = get_option( 'aioseop_options' );
				if ( ! empty( $opts['modules']['aiosp_feature_manager_options']['aiosp_feature_manager_enable_opengraph'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'aioseop social meta feature is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please deactivate the <strong>Social Meta</strong> feature in the <a href="%s">All in One SEO Pack Feature Manager</a>.', 'wpsso' ), get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/modules/aioseop_feature_manager.php' ) ) );
				}
				if ( isset( $opts['aiosp_google_disable_profile'] ) && empty( $opts['aiosp_google_disable_profile'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'aioseop google plus profile is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please check the <strong>Disable Google Plus Profile</strong> option in the <a href="%s">All in One SEO Pack General Settings</a>.', 'wpsso' ), get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_class.php' ) ) );
				}
				if ( isset( $opts['aiosp_schema_markup'] ) && ! empty( $opts['aiosp_schema_markup'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'aioseop schema markup is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>Use Schema.org Markup</strong> option in the <a href="%s">All in One SEO Pack General Settings</a>.', 'wpsso' ), get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_class.php' ) ) );
				}
			}

			// The SEO Framework
			if ( $this->p->is_avail['seo']['autodescription'] ) {
				$the_seo_framework = the_seo_framework();
				if ( $the_seo_framework->use_og_tags() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'autodescription open graph meta tags are enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>%1$s</strong> option in <a href="%2$s">The SEO Framework</a> Social Meta Settings.', 'wpsso' ), 'Output Open Graph meta tags?', get_admin_url( null, 'admin.php?page=autodescription-settings' ) ) );
				}
				if ( $the_seo_framework->use_facebook_tags() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'autodescription facebook meta tags are enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>%1$s</strong> option in <a href="%2$s">The SEO Framework</a> Social Meta Settings.', 'wpsso' ), 'Output Facebook meta tags?', get_admin_url( null, 'admin.php?page=autodescription-settings' ) ) );
				}
				if ( $the_seo_framework->use_twitter_tags() ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'autodescription twitter meta tags are enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>%1$s</strong> option in <a href="%2$s">The SEO Framework</a> Social Meta Settings.', 'wpsso' ), 'Output Twitter meta tags?', get_admin_url( null, 'admin.php?page=autodescription-settings' ) ) );
				}
				if ( $the_seo_framework->is_option_checked( 'knowledge_output' ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'autodescription knowledge graph is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>Output Knowledge tags?</strong> option in <a href="%s">The SEO Framework</a> Knowledge Graph Settings.', 'wpsso' ), get_admin_url( null, 'admin.php?page=autodescription-settings' ) ) );
				}
				foreach ( array(
					'post_publish_time' => 'Add article:published_time to Posts',
					'page_publish_time' => 'Add article:published_time to Pages',
					'home_publish_time' => 'Add article:published_time to Home Page',
					'post_modify_time' => 'Add article:modified_time to Posts',
					'page_modify_time' => 'Add article:modified_time to Pages',
					'home_modify_time' => 'Add article:modified_time to Home Page',
				) as $key => $label ) {
					if ( $the_seo_framework->get_option( $key ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( $log_pre.'autodescription '.$key.' option is enabled' );
						$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>%1$s</strong> option in <a href="%2$s">The SEO Framework</a> Social Meta Settings.', 'wpsso' ), $label, get_admin_url( null, 'admin.php?page=autodescription-settings' ) ) );
					}
				}
			}

			// Squirrly SEO
			if ( $this->p->is_avail['seo']['sq'] ) {
				$opts = json_decode( get_option( 'sq_options' ), true );
				if ( ! empty( $opts['sq_auto_facebook'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'squirrly seo open graph meta tags are enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck <strong>Add the Social Open Graph objects</strong> in the <a href="%s">Squirrly SEO</a> Social Media Options.', 'wpsso' ), get_admin_url( null, 'admin.php?page=sq_seo' ) ) );
				}
				if ( ! empty( $opts['sq_auto_twitter'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'squirrly seo twitter card meta tags are enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck <strong>Add the Twitter card in your tweets</strong> in the <a href="%s">Squirrly SEO</a> Social Media Options.', 'wpsso' ), get_admin_url( null, 'admin.php?page=sq_seo' ) ) );
				}
				if ( ! empty( $opts['sq_auto_jsonld'] ) ) {
					if ( $this->p->debug->enabled )
						$this->p->debug->log( $log_pre.'squirrly seo json-ld markup is enabled' );
					$this->p->notice->err( $err_pre.sprintf( __( 'please uncheck the <strong>adds the JSON-LD metas for Semantic SEO</strong> option in the <a href="%s">Squirrly SEO</a> settings.', 'wpsso' ), get_admin_url( null, 'admin.php?page=sq_seo' ) ) );
				}
			}
		}

		public function required_notices() {
			$lca = $this->p->cf['lca'];
			$version = $this->p->cf['plugin'][$lca]['version'];
			$have_ext_tid = false;

			if ( $this->p->is_avail['aop'] === true && 
				empty( $this->p->options['plugin_'.$lca.'_tid'] ) && 
					( empty( $this->p->options['plugin_'.$lca.'_tid:is'] ) || 
						$this->p->options['plugin_'.$lca.'_tid:is'] !== 'disabled' ) )
							$this->p->notice->nag( $this->p->msgs->get( 'notice-pro-tid-missing' ) );

			foreach ( $this->p->cf['plugin'] as $ext => $info ) {
				if ( ! empty( $this->p->options['plugin_'.$ext.'_tid'] ) &&
					isset( $info['base'] ) && SucomUtil::active_plugins( $info['base'] ) ) {

					$have_ext_tid = true;	// found at least one active plugin with an auth id
					if ( ! $this->p->check->aop( $ext, false ) )
						$this->p->notice->warn( $this->p->msgs->get( 'notice-pro-not-installed', 
							array( 'lca' => $ext ) ) );	// message uses $ext for its $info
				}
			}

			if ( $have_ext_tid === true ) {
				if ( $this->p->is_avail['util']['um'] &&
					isset( $this->p->cf['plugin']['wpssoum']['version'] ) ) {

					$min_version = WpssoConfig::$cf['um']['min_version'];
					if ( version_compare( $this->p->cf['plugin']['wpssoum']['version'], $min_version, '<' ) )
						$this->p->notice->err( $this->p->msgs->get( 'notice-um-version-required', 
							array( 'min_version' => $min_version ) ) );
				} else {
					if ( ! function_exists( 'get_plugins' ) )
						require_once( ABSPATH.'wp-admin/includes/plugin.php' );

					$installed_plugins = get_plugins();

					if ( ! empty( $this->p->cf['plugin']['wpssoum']['base'] ) &&
						is_array( $installed_plugins[$this->p->cf['plugin']['wpssoum']['base']] ) )
							$this->p->notice->nag( $this->p->msgs->get( 'notice-um-activate-extension' ) );
					else $this->p->notice->nag( $this->p->msgs->get( 'notice-um-extension-required' ) );
				}
			}

			if ( current_user_can( 'manage_options' ) ) {
				foreach ( array( 'wp', 'php' ) as $key ) {
					switch ( $key ) {
						case 'wp':
							global $wp_version;
							$app_label = 'WordPress';
							$cur_version = $wp_version;
							break;
						case 'php':
							$app_label = 'PHP';
							$cur_version = phpversion();
							break;
					}
					if ( isset( WpssoConfig::$cf[$key]['rec_version'] ) ) {
						if ( version_compare( $cur_version, WpssoConfig::$cf[$key]['rec_version'], '<' ) ) {
							$this->p->notice->log( 'warn', $this->p->msgs->get( 'notice-recommend-version', array(
								'app_label' => $app_label,
								'cur_version' => $cur_version,
								'rec_version' => WpssoConfig::$cf[$key]['rec_version'],
								'sup_version_url' => WpssoConfig::$cf[$key]['sup_version_url'],
							) ), true, 'notice-recommend-version-'.$lca.'-'.$version.'-'.$app_label.'-'.$cur_version,
								2592000, array( 'silent' => true ) );	// dismiss for 30 days
						}
					}
				}
			}
		}

		public function reset_check_head_exec_count() {
			$lca = $this->p->cf['lca'];
			delete_option( $lca.'_post_head_count' );
		}

		public function check_tmpl_head_attributes() {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			// only check if using the default filter name
			if ( empty( $this->p->options['plugin_head_attr_filter_name'] ) ||
				$this->p->options['plugin_head_attr_filter_name'] !== 'head_attributes' ||
					! apply_filters( $this->p->cf['lca'].'_add_schema_head_attributes', true ) )
						return;	// exit early

			foreach ( SucomUtil::get_header_files() as $tmpl_file ) {
				if ( ( $html = SucomUtil::get_stripped_php( $tmpl_file ) ) === false )
					continue;
				elseif ( strpos( $html, '<head>' ) !== false ) {
					if ( $this->p->notice->is_admin_pre_notices() ) {	// skip if notices already shown
						$this->p->notice->warn( $this->p->msgs->get( 'notice-header-tmpl-no-head-attr' ),
							true, 'notice-header-tmpl-no-head-attr-'.SucomUtil::get_theme_slug_version(), true );
					}
					break;
				}
			}
		}

		public function modify_tmpl_head_attributes() {

			$have_changes = false;
			$header_files = SucomUtil::get_header_files();
			$head_action_php = '<head <?php do_action( \'add_head_attributes\' ); ?'.'>>';	// breakup closing php for vim syntax highlighting

			if ( empty( $header_files ) ) {
				$this->p->notice->err( __( 'No header templates found in the parent or child theme directories.', 'wpsso' ) );
				return;	// exit early
			}

			foreach ( $header_files as $tmpl_file ) {
				$tmpl_base = basename( $tmpl_file );
				$backup_file = $tmpl_file.'~backup-'.date( 'Ymd-His' );
				$backup_base = basename( $backup_file );
	
				// double check in case of reloads etc.
				if ( ( $html = SucomUtil::get_stripped_php( $tmpl_file ) ) === false ||
					strpos( $html, '<head>' ) === false ) {
					$this->p->notice->err( sprintf( __( 'No &lt;head&gt; HTML element found in the %s template.', 'wpsso' ), $tmpl_file ) );
					continue;
				}

				// make a backup of the original
				if ( ! copy( $tmpl_file, $backup_file ) ) {
					$this->p->notice->err( sprintf( __( 'Error copying %1$s to %2$s.', 'wpsso' ), $tmpl_file, $backup_base ) );
					continue;
				}

				$tmpl_contents = file_get_contents( $tmpl_file );
				$tmpl_contents = str_replace( '<head>', $head_action_php, $tmpl_contents );

				if ( ! $tmpl_fh = @fopen( $tmpl_file, 'wb' ) ) {
					$this->p->notice->err( sprintf( __( 'Failed to open %s for writing.', 'wpsso' ), $tmpl_file ) );
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

			if ( $have_changes )
				$this->p->notice->trunc_id( 'notice-header-tmpl-no-head-attr-'.SucomUtil::get_theme_slug_version(), 'all' );	// just in case
		}

		public function get_site_use( &$form, $network = false, $name, $force = false ) {
			if ( $network !== true )
				return '';
			return $form->get_th_html( _x( 'Site Use', 'option label (very short)', 'wpsso' ), 'site_use' ).
			( $this->p->check->aop( $this->p->cf['lca'], true, $this->p->is_avail['aop'] ) || $force ?
				'<td>'.$form->get_select( $name.':use', $this->p->cf['form']['site_option_use'], 'site_use' ).'</td>' :
				'<td class="site_use blank">'.$form->get_select( $name.':use', 
					$this->p->cf['form']['site_option_use'], 'site_use', null, true, true ).'</td>' );
		}
	}
}

?>

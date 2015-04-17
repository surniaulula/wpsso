<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoAdmin' ) ) {

	class WpssoAdmin {
	
		protected $p;
		protected $menu_id;
		protected $menu_name;
		protected $pagehook;
		protected $readme_info = array();

		public $form;
		public $lang = array();
		public $submenu = array();

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->p->check->conflict_warnings();
			$this->set_objects();
			$this->req_notices();

			add_action( 'admin_init', array( &$this, 'register_setting' ) );
			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ), WPSSO_ADD_MENU_PRIORITY );
			add_action( 'admin_menu', array( &$this, 'add_admin_settings' ), WPSSO_ADD_SETTINGS_PRIORITY );
			add_action( 'activated_plugin', array( &$this, 'trunc_notices' ), 10, 2 );

			add_filter( 'plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );

			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( &$this, 'add_network_admin_menus' ), WPSSO_ADD_MENU_PRIORITY );
				add_action( 'network_admin_edit_'.WPSSO_SITE_OPTIONS_NAME, array( &$this, 'save_site_options' ) );
				add_filter( 'network_admin_plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );
			}
		}

		// load all submenu classes into the $this->submenu array
		// the id of each submenu item must be unique
		private function set_objects() {
			$menus = array( 
				'submenu', 
				'setting'	// setting must be last to extend submenu/advanced.php
			);
			if ( is_multisite() )
				$menus[] = 'sitesubmenu';
			foreach ( $menus as $sub ) {
				foreach ( $this->p->cf['plugin'] as $lca => $info ) {
					if ( isset( $info['lib'][$sub] ) ) {
						foreach ( $info['lib'][$sub] as $id => $name ) {
							if ( strpos( $id, 'separator' ) !== false ) continue;
							$classname = apply_filters( $lca.'_load_lib', false, $sub.'/'.$id );
							if ( $classname !== false && class_exists( $classname ) )
								$this->submenu[$id] = new $classname( $this->p, $id, $name );
						}
					}
				}
			}
		}

		private function req_notices() {
			// check that wpsso pro has an authentication id
			$lca = $this->p->cf['lca'];
			if ( $this->p->is_avail['aop'] === true && empty( $this->p->options['plugin_'.$lca.'_tid'] ) && 
				( empty( $this->p->options['plugin_'.$lca.'_tid:is'] ) || 
					$this->p->options['plugin_'.$lca.'_tid:is'] !== 'disabled' ) )
						$this->p->notice->nag( $this->p->msgs->get( 'pro-activate-msg' ) );
			// check all *active* plugins / extensions to make sure pro version is installed
			$has_tid = false;
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( ! empty( $this->p->options['plugin_'.$lca.'_tid'] ) &&
					isset( $info['base'] ) && WpssoUtil::active_plugins( $info['base'] ) ) {
					$has_tid = true;
					if ( ! $this->p->check->aop( $lca, false ) )
						$this->p->notice->inf( $this->p->msgs->get( 'pro-not-installed', array( 'lca' => $lca ) ), true );
				}
			}
			// if we have at least one tid, make sure the update manager is installed
			if ( $has_tid === true && ! $this->p->is_avail['util']['um'] )
				$this->p->notice->nag( $this->p->msgs->get( 'pro-um-extension-required' ), true );
		}

		public function trunc_notices( $plugin = false, $sitewide = false ) {
			$um_lca = $this->p->cf['lca'].'um';
			$um_base = $this->p->cf['plugin'][$um_lca]['base'];
			if ( $plugin === $um_base )
				$this->p->notice->trunc( 'nag' );
		}

		protected function set_form() {
			$def_opts = $this->p->opt->get_defaults();
			$this->form = new SucomForm( $this->p, WPSSO_OPTIONS_NAME, $this->p->options, $def_opts );
		}

		protected function &get_form_reference() {	// returns a reference
			return $this->form;
		}

		public function register_setting() {
			register_setting( $this->p->cf['lca'].'_setting', WPSSO_OPTIONS_NAME, array( &$this, 'sanitize_options' ) );
		} 

		public function set_readme_info( $expire_secs = 86400 ) {
			foreach ( array_keys( $this->p->cf['plugin'] ) as $lca ) {
				if ( empty( $this->readme_info[$lca] ) )
					$this->readme_info[$lca] = $this->p->util->parse_readme( $lca, $expire_secs );
			}
		}

		public function add_admin_settings() {
			foreach ( $this->p->cf['*']['lib']['setting'] as $id => $name ) {
				$parent_slug = 'options-general.php';
				if ( array_key_exists( $id, $this->submenu ) ) {
					$this->submenu[$id]->add_submenu_page( $parent_slug );
				} else $this->add_submenu_page( $parent_slug, $id, $name );
			}
		}

		public function add_network_admin_menus() {
			$this->add_admin_menus( $this->p->cf['*']['lib']['sitesubmenu'] );
		}

		public function add_admin_menus( $submenus = false ) {

			if ( ! is_array( $submenus ) )
				$submenus = $this->p->cf['*']['lib']['submenu'];

			$this->menu_id = key( $submenus );
			$this->menu_name = $submenus[ $this->menu_id ];

			if ( array_key_exists( $this->menu_id, $this->submenu ) ) {
				$menu_slug = $this->p->cf['lca'].'-'.$this->menu_id;
				$this->submenu[$this->menu_id]->add_menu_page( $menu_slug );
			}

			foreach ( $submenus as $id => $name ) {
				$parent_slug = $this->p->cf['lca'].'-'.$this->menu_id;
				if ( array_key_exists( $id, $this->submenu ) )
					$this->submenu[$id]->add_submenu_page( $parent_slug );
				else $this->add_submenu_page( $parent_slug, $id, $name );
			}
		}

		protected function add_menu_page( $menu_slug ) {
			global $wp_version;
			$short_aop = $this->p->cf['plugin'][$this->p->cf['lca']]['short'].
				( $this->p->check->aop( $this->p->cf['lca'] ) ? ' Pro' : ' Free' );

			// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
			$this->pagehook = add_menu_page( 
				$short_aop.' : '.$this->menu_name, 
				$this->p->cf['menu'], 
				'manage_options', 
				$menu_slug, 
				array( &$this, 'show_form_page' ), 
				( version_compare( $wp_version, 3.8, '<' ) ? null : 'dashicons-share' ),
				WPSSO_MENU_ORDER
			);
			add_action( 'load-'.$this->pagehook, array( &$this, 'load_form_page' ) );
		}

		protected function add_submenu_page( $parent_slug, $menu_id = false, $menu_name = false ) {
			$menu_id = $menu_id === false ? $this->menu_id : $menu_id;
			$menu_name = $menu_name === false ? $this->menu_name : $menu_name;
			$short_aop = $this->p->cf['plugin'][$this->p->cf['lca']]['short'].
				( $this->p->check->aop( $this->p->cf['lca'] ) ? ' Pro' : ' Free' );
			if ( strpos( $menu_id, 'separator' ) !== false ) {
				$menu_title = '<div style="z-index:999;
					padding:2px 0;
					margin:0;
					cursor:default;
					border-bottom:1px dotted;
					color:#666;" onclick="return false;">'.
						$menu_name.'</div>';
				$menu_slug = '';
				$page_title = '';
				$function = '';
			} else {
				// highlight the "extension plugins" part of the menu title
				if ( strpos( $menu_name, 'Extension Plugins' ) !== false )
					$menu_title = preg_replace( '/(Extension Plugins)/',
						'<div style="color:#'.$this->p->cf['color'].';">$1</div>', $menu_name );
				else $menu_title = $menu_name;
				$menu_slug = $this->p->cf['lca'].'-'.$menu_id;
				$page_title = $short_aop.' : '.$menu_title;
				$function = array( &$this, 'show_form_page' );
			}
			// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
			$this->pagehook = add_submenu_page( 
				$parent_slug, 
				$page_title, 
				$menu_title, 
				'manage_options', 
				$menu_slug, 
				$function
			);
			if ( $function )
				add_action( 'load-'.$this->pagehook, array( &$this, 'load_form_page' ) );
		}

		// add links on the main plugins page
		public function add_plugin_action_links( $links, $file ) {
			// only add links when filter is called for this plugin
			if ( $file == WPSSO_PLUGINBASE ) {
				// remove the Edit link
				foreach ( $links as $num => $val ) {
					if ( preg_match( '/>Edit</', $val ) )
						unset ( $links[$num] );
				}
				$urls = $this->p->cf['plugin'][$this->p->cf['lca']]['url'];
				array_push( $links, '<a href="'.$urls['faq'].'">'.__( 'FAQ', WPSSO_TEXTDOM ).'</a>' );
				array_push( $links, '<a href="'.$urls['notes'].'">'.__( 'Notes', WPSSO_TEXTDOM ).'</a>' );
				if ( $this->p->check->aop() ) {
					array_push( $links, '<a href="'.$urls['pro_support'].'">'.__( 'Pro Support', WPSSO_TEXTDOM ).'</a>' );
				} else {
					array_push( $links, '<a href="'.$urls['wp_support'].'">'.__( 'Support Forum', WPSSO_TEXTDOM ).'</a>' );
					if ( $this->p->is_avail['aop'] ) 
						array_push( $links, '<a href="'.$urls['purchase'].'">'.__( 'Purchase License', WPSSO_TEXTDOM ).'</a>' );
					else array_push( $links, '<a href="'.$urls['purchase'].'">'.__( 'Purchase Pro', WPSSO_TEXTDOM ).'</a>' );
				}
			}
			return $links;
		}

		// this method receives only a partial options array, so re-create a full one
		// wordpress handles the actual saving of the options
		public function sanitize_options( $opts ) {
			if ( ! is_array( $opts ) ) {
				add_settings_error( WPSSO_OPTIONS_NAME, 'notarray', '<b>'.$this->p->cf['uca'].' Error</b> : '.
					__( 'Submitted settings are not an array.', WPSSO_TEXTDOM ), 'error' );
				return $opts;
			}
			// get default values, including css from default stylesheets
			$def_opts = $this->p->opt->get_defaults();
			$opts = SucomUtil::restore_checkboxes( $opts );
			$opts = array_merge( $this->p->options, $opts );
			$this->p->notice->trunc();				// flush all messages before sanitation checks
			$opts = $this->p->opt->sanitize( $opts, $def_opts );
			$opts = apply_filters( $this->p->cf['lca'].'_save_options', $opts, WPSSO_OPTIONS_NAME );
			$this->p->notice->inf( __( 'Plugin settings have been updated.', WPSSO_TEXTDOM ).' '.sprintf( __( 'Wait %d seconds for cache objects to expire (default) or use the \'Clear All Cache(s)\' button.', WPSSO_TEXTDOM ), $this->p->options['plugin_object_cache_exp'] ), true );
			return $opts;
		}

		public function save_site_options() {
			$page = empty( $_POST['page'] ) ? 
				key( $this->p->cf['*']['lib']['sitesubmenu'] ) : $_POST['page'];

			if ( empty( $_POST[ WPSSO_NONCE ] ) ) {
				$this->p->debug->log( 'nonce token validation post field missing' );
				wp_redirect( $this->p->util->get_admin_url( $page ) );
				exit;
			} elseif ( ! wp_verify_nonce( $_POST[ WPSSO_NONCE ], $this->get_nonce() ) ) {
				$this->p->notice->err( __( 'Nonce token validation failed for network options (update ignored).', WPSSO_TEXTDOM ), true );
				wp_redirect( $this->p->util->get_admin_url( $page ) );
				exit;
			} elseif ( ! current_user_can( 'manage_network_options' ) ) {
				$this->p->notice->err( __( 'Insufficient privileges to modify network options.', WPSSO_TEXTDOM ), true );
				wp_redirect( $this->p->util->get_admin_url( $page ) );
				exit;
			}

			$def_opts = $this->p->opt->get_site_defaults();
			$opts = empty( $_POST[WPSSO_SITE_OPTIONS_NAME] ) ? $def_opts : 
				SucomUtil::restore_checkboxes( $_POST[WPSSO_SITE_OPTIONS_NAME] );
			$opts = array_merge( $this->p->site_options, $opts );
			$this->p->notice->trunc();				// flush all messages before sanitation checks
			$opts = $this->p->opt->sanitize( $opts, $def_opts );	// cleanup excess options and sanitize
			$opts = apply_filters( $this->p->cf['lca'].'_save_site_options', $opts );
			update_site_option( WPSSO_SITE_OPTIONS_NAME, $opts );
			$this->p->notice->inf( __( 'Plugin settings have been updated.', WPSSO_TEXTDOM ), true );
			wp_redirect( $this->p->util->get_admin_url( $page ).'&settings-updated=true' );
			exit;	// stop here
		}

		public function load_single_page() {
			wp_enqueue_script( 'postbox' );

			$this->p->admin->submenu[ $this->menu_id ]->add_meta_boxes();
		}

		public function load_form_page() {
			wp_enqueue_script( 'postbox' );
			$upload_dir = wp_upload_dir();		// returns assoc array with path info
			$user_opts = $this->p->mods['util']['user']->get_options();

			if ( ! empty( $_GET['action'] ) ) {
				if ( empty( $_GET[ WPSSO_NONCE ] ) )
					$this->p->debug->log( 'nonce token validation query field missing' );
				elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE ], $this->get_nonce() ) )
					$this->p->notice->err( __( 'Nonce token validation failed for plugin action (action ignored).', WPSSO_TEXTDOM ) );
				else {
					switch ( $_GET['action'] ) {
						case 'check_for_updates': 
							if ( $this->p->is_avail['util']['um'] ) {
								$this->readme_info = array();
								$wpssoum = WpssoUm::get_instance();
								$wpssoum->update->check_for_updates( null, true, false );
							} else {
								$um_lca = $this->p->cf['lca'].'um';
								$um_name = $this->p->cf['plugin'][$um_lca]['name'];
								$this->p->notice->err( 'The <strong>'.$um_name.'</strong> extension plugin is required to check for plugin and extension updates.' );
							}
							break;

						case 'clear_all_cache': 
							wp_cache_flush();
							$deleted_cache = $this->p->util->delete_expired_file_cache( true );
							$deleted_transient = $this->p->util->delete_expired_transients( true );
							$this->p->notice->inf( __( $this->p->cf['uca'].' cached files, transient cache, and the WordPress object cache have all been cleared.', WPSSO_TEXTDOM ) );

							if ( function_exists( 'w3tc_pgcache_flush' ) ) {	// w3 total cache
								w3tc_pgcache_flush();
								$this->p->notice->inf( __( 'W3 Total Cache has been cleared as well.', WPSSO_TEXTDOM ) );
							}
							if ( function_exists( 'wp_cache_clear_cache' ) ) {	// wp super cache
								wp_cache_clear_cache();
								$this->p->notice->inf( __( 'WP Super Cache has been cleared as well.', WPSSO_TEXTDOM ) );
							}
							if ( isset( $GLOBALS['zencache'] ) ) {		// zencache
								$GLOBALS['zencache']->wipe_cache();
								$this->p->notice->inf( __( 'ZenCache has been cleared as well.', WPSSO_TEXTDOM ) );
							}
							break;

						case 'clear_metabox_prefs': 
							WpssoUser::delete_metabox_prefs( get_current_user_id() );
							break;

						case 'change_show_options': 
							if ( isset( $this->p->cf['form']['show_options'][$_GET['show_opts']] ) )
								WpssoUser::save_pref( array( 'show_opts' => $_GET['show_opts'] ) );
							break;
					}
				}
			}

			// the plugin information metabox on all settings pages needs this
			$this->p->admin->set_readme_info( $this->feed_cache_expire() );

			// add child metaboxes first, since they contain the default reset_metabox_prefs()
			$this->p->admin->submenu[ $this->menu_id ]->add_meta_boxes();

			if ( empty( $this->p->options['plugin_'.$this->p->cf['lca'].'_tid'] ) || ! $this->p->check->aop() ) {
				add_meta_box( $this->pagehook.'_purchase', __( 'Pro Version', WPSSO_TEXTDOM ), 
					array( &$this, 'show_metabox_purchase' ), $this->pagehook, 'side' );
				add_filter( 'postbox_classes_'.$this->pagehook.'_'.$this->pagehook.'_purchase', 
					array( &$this, 'add_class_postbox_highlight_side' ) );
				$this->p->mods['util']['user']->reset_metabox_prefs( $this->pagehook, 
					array( 'purchase' ), null, 'side', true );
			}

			add_meta_box( $this->pagehook.'_info', __( 'Version Information', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_info' ), $this->pagehook, 'side' );

			add_meta_box( $this->pagehook.'_status_gpl', __( 'Basic / Common Features', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_status_gpl' ), $this->pagehook, 'side' );

			add_meta_box( $this->pagehook.'_status_pro', __( 'Pro Version Features', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_status_pro' ), $this->pagehook, 'side' );

			add_meta_box( $this->pagehook.'_help', __( 'Help and Support', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_help' ), $this->pagehook, 'side' );

		}

		public function show_single_page() {
			?>
			<div class="wrap" id="<?php echo $this->pagehook; ?>">
				<h2>
					<?php $this->show_follow_icons(); ?>
					<?php echo $this->menu_name; ?>
				</h2>
				<div id="poststuff" class="metabox-holder">
					<div id="post-body" class="">
						<div id="post-body-content" class="">
							<?php $this->show_single_content(); ?>
						</div><!-- .post-body-content -->
					</div><!-- .post-body -->
				</div><!-- .metabox-holder -->
			</div><!-- .wrap -->
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( 
					function($) {
						$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
						postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
					}
				);
				//]]>
			</script>
			<?php
		}

		public function show_form_page() {
			$short_aop = $this->p->cf['plugin'][$this->p->cf['lca']]['short'].
				( $this->p->check->aop( $this->p->cf['lca'] ) ? ' Pro' : ' Free' );

			if ( ! $this->is_setting( $this->menu_id ) )	// the "setting" pages display their own error messages
				settings_errors( WPSSO_OPTIONS_NAME );	// display "error" and "updated" messages

			$this->set_form();				// define form for side boxes and show_form_content()

			if ( $this->p->debug->enabled ) {
				$this->p->debug->show_html( print_r( $this->p->is_avail, true ), 'available features' );
				$this->p->debug->show_html( print_r( WpssoUtil::active_plugins(), true ), 'active plugins' );
				$this->p->debug->show_html( null, 'debug log' );
			}
			?>

			<div class="wrap" id="<?php echo $this->pagehook; ?>">
				<h2><?php $this->show_follow_icons(); echo $short_aop.' &ndash; '.$this->menu_name; ?></h2>
				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes( $this->pagehook, 'side', null ); ?>
					</div><!-- .inner-sidebar -->
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<?php $this->show_form_content(); ?>
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

		public function add_class_postbox_highlight_side( $classes ) {
			array_push( $classes, 'postbox_highlight_side' );
			return $classes;
		}

		protected function show_single_content() {
			do_meta_boxes( $this->pagehook, 'normal', null ); 
		}

		protected function show_form_content() {

			if ( $this->is_submenu( $this->menu_id ) ||
				$this->is_setting( $this->menu_id ) ) {

				echo '<form name="'.$this->p->cf['lca'].'" 
					id="'.$this->p->cf['lca'].'_settings_form" 
					action="options.php" method="post">';

				settings_fields( $this->p->cf['lca'].'_setting' ); 

			} elseif ( $this->is_sitesubmenu( $this->menu_id ) ) {

				echo '<form name="'.$this->p->cf['lca'].'" 
					id="'.$this->p->cf['lca'].'_settings_form" 
					action="edit.php?action='.WPSSO_SITE_OPTIONS_NAME.'" method="post">';
				echo '<input type="hidden" name="page" value="'.$this->menu_id.'">';
			}

			echo $this->form->get_hidden( 'options_version', $this->p->cf['opt']['version'] );
			echo $this->form->get_hidden( 'plugin_version', $this->p->cf['plugin'][$this->p->cf['lca']]['version'] );

			wp_nonce_field( $this->get_nonce(), WPSSO_NONCE );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

			do_meta_boxes( $this->pagehook, 'normal', null ); 

			if ( isset( $this->p->admin->submenu[ $this->menu_id ]->website ) ) {
				foreach ( range( 1, ceil( count( $this->p->admin->submenu[ $this->menu_id ]->website ) / 2 ) ) as $row ) {
					echo '<div class="website-row">', "\n";
					foreach ( range( 1, 2 ) as $col ) {
						$pos_id = 'website-row-'.$row.'-col-'.$col;
						echo '<div class="website-col-', $col, '" id="', $pos_id, '" >';
						do_meta_boxes( $this->pagehook, $pos_id, null ); 
						echo '</div>', "\n";
					}
					echo '</div>', "\n";
				}
				echo '<div style="clear:both;"></div>';
			}

			//do_meta_boxes( $this->pagehook, 'bottom', null ); 

			switch ( $this->menu_id ) {
				case 'readme':
				case 'setup':
				case 'sitereadme':
				case 'sitesetup':
					break;
				default:
					echo $this->get_submit_buttons();
					break;
			}
			echo '</form>', "\n";
		}

		public function feed_cache_expire( $seconds = 0 ) {
			return empty( $this->p->cf['feed_cache_expire'] ) ? 
				86400 : $this->p->cf['feed_cache_expire'] * 3600;
		}

		public function show_metabox_info() {
			echo '<table class="sucom-setting">';
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {

				if ( empty( $info['version'] ) )	// filter out extensions that are not active
					continue;

				$stable_version = __( 'N/A', WPSSO_TEXTDOM );
				$latest_version = __( 'N/A', WPSSO_TEXTDOM );
				$installed_version = $info['version'];
				$installed_style = '';
				$latest_notice = '';
				$changelog_url = $info['url']['changelog'];

				// the readme_info array is populated by set_readme_info(), which is called from load_form_page()
				if ( ! empty( $this->p->admin->readme_info[$lca]['stable_tag'] ) ) {
					$stable_version = $this->p->admin->readme_info[$lca]['stable_tag'];
					$upgrade_notice = $this->p->admin->readme_info[$lca]['upgrade_notice'];
					if ( is_array( $upgrade_notice ) ) {
						reset( $upgrade_notice );
						$latest_version = key( $upgrade_notice );
						$latest_notice = $upgrade_notice[$latest_version];
					}
					$installed_style = version_compare( $installed_version, $stable_version, '<' ) ?
						'style="background-color:#f00;"' : 
						'style="background-color:#0f0;"';
				}

				echo '<tr><td colspan="2"><h4>'.$info['short'].
					( $this->p->check->aop( $lca ) ? ' Pro' : ' Free' ).'</h4></td></tr>';
				echo '<tr><th class="side">'.__( 'Installed', WPSSO_TEXTDOM ).':</th>
					<td class="side_version" '.$installed_style.'>'.$installed_version.'</td></tr>';
				echo '<tr><th class="side">'.__( 'Stable', WPSSO_TEXTDOM ).':</th>
					<td class="side_version">'.$stable_version.'</td></tr>';
				echo '<tr><th class="side">'.__( 'Latest', WPSSO_TEXTDOM ).':</th>
					<td class="side_version">'.$latest_version.'</td></tr>';
				echo '<tr><td colspan="2" id="latest_notice"><p>'.$latest_notice.'</p>'.
					'<p><a href="'.$changelog_url.'" target="_blank">'.
						sprintf( __( 'View %s changelog...', WPSSO_TEXTDOM ), $info['short'] ).'</a></p></td></tr>';
			}
			echo '</table>';
		}

		public function show_metabox_status_gpl() {
			$metabox = 'status';
			$plugin_count = 0;
			foreach ( $this->p->cf['plugin'] as $lca => $info )
				if ( isset( $info['lib']['gpl'] ) )
					$plugin_count++;
			echo '<table class="sucom-setting" style="margin-bottom:10px;">';
			/*
			 * GPL version features
			 */
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( ! isset( $info['lib']['gpl'] ) )
					continue;
				if ( $lca === $this->p->cf['lca'] )	// features for this plugin
					$features = array(
						'Author JSON-LD' => array( 
							'status' => $this->p->options['schema_author_json'] ? 'on' : 'rec',
						),
						'Debug Messages' => array(
							'classname' => 'SucomDebug',
						),
						'Non-Persistant Cache' => array(
							'status' => $this->p->is_avail['cache']['object'] ? 'on' : 'rec',
						),
						'Open Graph / Rich Pin' => array( 
							'status' => class_exists( $this->p->cf['lca'].'opengraph' ) ? 'on' : 'rec',
						),
						'Publisher JSON-LD' => array(
							'status' => $this->p->options['schema_publisher_json'] ? 'on' : 'rec',
						),
						'Transient Cache' => array(
							'status' => $this->p->is_avail['cache']['transient'] ? 'on' : 'rec',
						),
					);
				else $features = array();
				$features = apply_filters( $lca.'_'.$metabox.'_gpl_features', $features, $lca, $info );
				if ( ! empty( $features ) ) {
					if ( $plugin_count > 1 )
						echo '<tr><td><h4>'.$this->p->cf['plugin'][$lca]['short'].'</h4></td></tr>';
					$this->show_plugin_status( $features );
				}
			}
			echo '</table>';
		}

		public function show_metabox_status_pro() {
			$metabox = 'status';
			$plugin_count = 0;
			foreach ( $this->p->cf['plugin'] as $lca => $info )
				if ( isset( $info['lib']['pro'] ) )
					$plugin_count++;
			echo '<table class="sucom-setting" style="margin-bottom:10px;">';
			/*
			 * Pro version features
			 */
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( ! isset( $info['lib']['pro'] ) )
					continue;
				$features = array();
				$aop = $this->p->check->aop( $lca );
				foreach ( $info['lib']['pro'] as $sub => $libs ) {
					if ( $sub === 'admin' ) 
						continue;	// skip status for admin menus and tabs
					foreach ( $libs as $id => $name ) {
						$off = $this->p->is_avail[$sub][$id] ? 'rec' : 'off';
						$features[$name] = array( 
							'status' => class_exists( $lca.'pro'.$sub.$id ) ? ( $aop ? 'on' : $off ) : $off,
							'tooltip' => 'If the '.$name.' plugin is detected, '.$this->p->cf['plugin'][$lca]['short'].' Pro will load an integration modules to provide additional support and features for '.$name.'.',
							'td_class' => $aop ? '' : 'blank',
						);
					}
				}
				$features = apply_filters( $lca.'_'.$metabox.'_pro_features', $features, $lca, $info );
				if ( ! empty( $features ) ) {
					if ( $plugin_count > 1 )
						echo '<tr><td><h4>'.$this->p->cf['plugin'][$lca]['short'].'</h4></td></tr>';
					$this->show_plugin_status( $features );
				}
			}
			echo '</table>';
		}

		private function show_plugin_status( $features = array() ) {
			$images = array( 
				'on' => 'green-circle.png',
				'off' => 'gray-circle.png',
				'rec' => 'red-circle.png',
			);
			uksort( $features, 'strcasecmp' );
			$first = key( $features );
			foreach ( $features as $name => $arr ) {

				$td_class = empty( $arr['td_class'] ) ? '' : ' '.$arr['td_class'];

				if ( array_key_exists( 'classname', $arr ) )
					$status = class_exists( $arr['classname'] ) ? 'on' : 'off';
				elseif ( array_key_exists( 'status', $arr ) )
					$status = $arr['status'];
				else $status = '';

				if ( ! empty( $status ) ) {
					$tooltip_text = empty( $arr['tooltip'] ) ? '' : $arr['tooltip'];
					$tooltip_text = $this->p->msgs->get( 'tooltip-side-'.$name, $tooltip_text, 'sucom_tooltip_side' );

					echo '<tr><td class="side'.$td_class.'">'.
					$tooltip_text.( $status == 'rec' ? '<strong>'.$name.'</strong>' : $name ).
					'</td><td style="min-width:0;text-align:center;" class="'.$td_class.'">
					<img src="'.WPSSO_URLPATH.'images/'.$images[$status].'" width="12" height="12" /></td></tr>';
				}
			}
		}

		public function show_metabox_purchase() {
			$purchase_url = $this->p->cf['plugin'][$this->p->cf['lca']]['url']['purchase'];
			echo '<table class="sucom-setting"><tr><td>';
			echo $this->p->msgs->get( 'side-purchase' );
			echo '<p class="centered">';
			echo $this->form->get_button( 
				( $this->p->is_avail['aop'] ? 
					__( 'Purchase Pro License(s)', WPSSO_TEXTDOM ) :
					__( 'Purchase Pro Version', WPSSO_TEXTDOM ) ), 
				'button-primary', null, $purchase_url, true );
			echo '</p></td></tr></table>';
		}

		public function show_metabox_help() {
			echo '<table class="sucom-setting"><tr><td>';
			echo $this->p->msgs->get( 'side-help' );
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( empty( $info['version'] ) )	// filter out extensions that are not installed
					continue;

				$help_links = '';
				if ( ! empty( $info['url']['faq'] ) ) {
					$help_links .= '<li>Review <a href="'.$info['url']['faq'].'" target="_blank">FAQs</a>';
					if ( ! empty( $info['url']['notes'] ) )
						$help_links .= ' and <a href="'.$info['url']['notes'].'" target="_blank">Notes</a>';
					$help_links .= '</li>';
				}
				if ( ! empty( $info['url']['pro_ticket'] ) && $this->p->check->aop( $lca ) )
					$help_links .= '<li><a href="'.$info['url']['pro_ticket'].'" 
						target="_blank">Submit a Support Ticket</a></li>';
				elseif ( ! empty( $info['url']['wp_support'] ) )
					$help_links .= '<li><a href="'.$info['url']['wp_support'].'" 
						target="_blank">Post in Support Forum</a></li>';

				if ( ! empty( $help_links ) ) {
					echo '<p><strong>'.$info['short'].
						( $this->p->check->aop( $lca ) ? ' Pro' : ' Free' ).' Help</strong></p>';
					echo '<ul>'.$help_links.'</ul>';
				}
			}
			echo '</td></tr></table>';
		}

		protected function show_follow_icons() {
			echo '<div class="follow_icons">';
			$img_size = $this->p->cf['follow']['size'];
			foreach ( $this->p->cf['follow']['src'] as $img => $url )
				echo '<a href="'.$url.'" target="_blank"><img src="'.WPSSO_URLPATH.'images/'.$img.'" 
					width="'.$img_size.'" height="'.$img_size.'" /></a> ';
			echo '</div>';
		}

		protected function get_submit_buttons( $submit_text = '', $class = 'submit-buttons' ) {
			if ( empty( $submit_text ) ) 
				$submit_text = __( 'Save All Changes', WPSSO_TEXTDOM );

			$show_opts_next = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf['form']['show_options'] );
			$show_opts_text = 'Prefer '.$this->p->cf['form']['show_options'][$show_opts_next].' View';
			$show_opts_url = $this->p->util->get_admin_url( '?action=change_show_options&show_opts='.$show_opts_next );

			$action_buttons = '<input type="submit" class="button-primary" value="'.$submit_text.'" />'.
				$this->form->get_button( $show_opts_text, 'button-secondary button-highlight', null, 
					wp_nonce_url( $show_opts_url, $this->get_nonce(), WPSSO_NONCE ) ).'<br/>';

			if ( empty( $this->p->cf['*']['lib']['sitesubmenu'][$this->menu_id] ) )	// don't show on the network admin pages
				$action_buttons .= $this->form->get_button( __( 'Clear All Cache(s)', WPSSO_TEXTDOM ), 
					'button-secondary', null, wp_nonce_url( $this->p->util->get_admin_url( '?action=clear_all_cache' ),
						$this->get_nonce(), WPSSO_NONCE ) );

			$action_buttons .= $this->form->get_button( __( 'Check for Update(s)', WPSSO_TEXTDOM ), 'button-secondary', null,
				wp_nonce_url( $this->p->util->get_admin_url( '?action=check_for_updates' ), $this->get_nonce(), WPSSO_NONCE ),
				false, ( $this->p->is_avail['util']['um'] ? false : true )	// disable button if update manager is not available
			);

			if ( empty( $this->p->cf['*']['lib']['sitesubmenu'][$this->menu_id] ) )	// don't show on the network admin pages
				$action_buttons .= $this->form->get_button( __( 'Reset Metabox Layout', WPSSO_TEXTDOM ), 
					'button-secondary', null, wp_nonce_url( $this->p->util->get_admin_url( '?action=clear_metabox_prefs' ),
						$this->get_nonce(), WPSSO_NONCE ) );

			return '<div class="'.$class.'">'.$action_buttons.'</div>';
		}

		protected function get_nonce() {
			return ( defined( 'NONCE_KEY' ) ? NONCE_KEY : '' ).plugin_basename( __FILE__ );
		}

		private function is_setting( $menu_id ) {
			return isset( $this->p->cf['*']['lib']['setting'][$menu_id] ) ? true : false;
		}

		private function is_submenu( $menu_id ) {
			return isset( $this->p->cf['*']['lib']['submenu'][$menu_id] ) ? true : false;
		}

		private function is_sitesubmenu( $menu_id ) {
			return isset( $this->p->cf['*']['lib']['sitesubmenu'][$menu_id] ) ? true : false;
		}

		public function licenses_metabox( $network = false ) {
			echo '<table class="sucom-setting licenses-metabox" style="padding-bottom:10px">'."\n";
			echo '<tr><td colspan="'.( $network ? 5 : 4 ).'">'.
				$this->p->msgs->get( 'info-plugin-tid' ).'</td></tr>'."\n";

			$num = 0;
			$total = count( $this->p->cf['plugin'] );
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				$num++;
				$links = '';
				$img_href = '';

				$view_text = 'View Plugin Details';
				if ( ! empty( $info['slug'] ) && 
					( empty( $info['url']['latest_zip'] ) ||
						$this->p->is_avail['util']['um'] ) ) {

					$img_href = add_query_arg( array(
						'tab' => 'plugin-information',
						'plugin' => $info['slug'],
						'TB_iframe' => 'true',
						'width' => 600,
						'height' => 550
					), get_admin_url( null, 'plugin-install.php' ) );
					if ( is_dir( WP_PLUGIN_DIR.'/'.$info['slug'] ) ) {
						$update_plugins = get_site_transient('update_plugins');
						if ( isset( $update_plugins->response ) ) {
							foreach ( (array) $update_plugins->response as $file => $plugin ) {
								if ( $plugin->slug === $info['slug'] ) {
									$view_text = '<strong>View Plugin Details and Update</strong>';
									break;
								}
							}
						}
					} else $view_text = '<em>View Plugin Details and Install</em>';
					$links .= ' | <a href="'.$img_href.'" class="thickbox">'.$view_text.'</a>';
				} else {
					if ( ! empty( $info['url']['download'] ) ) {
						$img_href = $info['url']['download'];
						$links .= ' | <a href="'.$img_href.'" target="_blank">Plugin Description Page</a>';
					}
				}

				if ( ! empty( $info['url']['latest_zip'] ) ) {
					$img_href = $info['url']['latest_zip'];
					$links .= ' | <a href="'.$img_href.'">Download the Latest Version</a> (zip file)';
				}

				if ( ! empty( $info['url']['purchase'] ) ) {
					$img_href = $info['url']['purchase'];
					if ( $this->p->cf['lca'] === $lca || $this->p->check->aop() )
						$links .= ' | <a href="'.$img_href.'" target="_blank">Purchase Pro License(s)</a>';
					else $links .= ' | <em>Purchase Pro License(s)</em>';
				}

				if ( ! empty( $info['img']['icon_small'] ) )
					$img_icon = $info['img']['icon_small'];
				else $img_icon = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

				// logo image
				echo '<tr><td style="width:148px; padding:10px;" rowspan="3" valign="top" align="left">';
				if ( ! empty( $img_href ) ) 
					echo '<a href="'.$img_href.'"'.( strpos( $img_href, 'TB_iframe' ) ?
						' class="thickbox"' : ' target="_blank"' ).'>';
				echo '<img src="'.$img_icon.'" width="128" height="128">';
				if ( ! empty( $img_href ) ) 
					echo '</a>';
				echo '</td>';

				// plugin name
				echo '<td colspan="'.( $network ? 4 : 3 ).'" style="padding:10px 0 0 0;">
					<p><strong>'.$info['name'].'</strong></p>';

				if ( ! empty( $info['desc'] ) )
					echo '<p>'.$info['desc'].'</p>';

				if ( ! empty( $links ) )
					echo '<p>'.trim( $links, ' |' ).'</p>';

				echo '</td></tr>'."\n";

				if ( $network ) {
					if ( ! empty( $info['url']['purchase'] ) || 
						! empty( $this->p->options['plugin_'.$lca.'_tid'] ) ) {
						if ( $this->p->cf['lca'] === $lca || $this->p->check->aop() ) {
							echo '<tr>'.$this->p->util->th( 'Authentication ID', 'medium nowrap' ).'<td class="tid">'.
								$this->form->get_input( 'plugin_'.$lca.'_tid', 'tid mono' ).'</td>'.
								$this->p->util->th( 'Site Use', 'site_use' ).'<td>'.
								$this->form->get_select( 'plugin_'.$lca.'_tid:use', 
									$this->p->cf['form']['site_option_use'], 'site_use' ).'</td></tr>'."\n";
						} else {
							echo '<tr>'.$this->p->util->th( 'Authentication ID', 'medium nowrap' ).'<td class="blank">'.
								$this->form->get_no_input( 'plugin_'.$lca.'_tid', 'tid mono' ).'</td><td>'.
								$this->p->msgs->get( 'pro-option-msg' ).'</td>
									<td>&nbsp;</td><td>&nbsp;</td></tr>'."\n";
						}
					} else echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>'."\n";
				} else {
					if ( ! empty( $info['url']['purchase'] ) || 
						! empty( $this->p->options['plugin_'.$lca.'_tid'] ) ) {
						if ( $this->p->cf['lca'] === $lca || $this->p->check->aop() ) {
							echo '<tr>'.$this->p->util->th( 'Authentication ID', 'medium nowrap' ).'<td class="tid">'.
								$this->form->get_input( 'plugin_'.$lca.'_tid', 'tid mono' ).'</td><td><p>'.
								( empty( $qty_used ) ? '' : $qty_used.' Licenses Assigned' ).'</p></td></tr>'."\n";
						} else {
							echo '<tr>'.$this->p->util->th( 'Authentication ID', 'medium nowrap' ).'<td class="blank">'.
								$this->form->get_no_input( 'plugin_'.$lca.'_tid', 'tid mono' ).'</td><td>'.
								$this->p->msgs->get( 'pro-option-msg' ).'</td></tr>'."\n";
						}
					} else echo '<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</tr>'."\n";
				}

				if ( $num < $total )
					echo '<tr><td style="border-bottom:1px dotted #ddd;" colspan="'.( $network ? 4 : 3 ).'">&nbsp;</td></tr>'."\n";
				else echo '<tr><td colspan="'.( $network ? 4 : 3 ).'">&nbsp;</td></tr>'."\n";
			}
			echo '</table>'."\n";
		}
	}
}

?>

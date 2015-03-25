<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
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

			add_action( 'admin_init', array( &$this, 'register_setting' ) );
			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ), -20 );
			add_action( 'admin_menu', array( &$this, 'add_admin_settings' ), -10 );
			add_filter( 'plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );

			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( &$this, 'add_network_admin_menus' ), -20 );
				add_action( 'network_admin_edit_'.WPSSO_SITE_OPTIONS_NAME, array( &$this, 'save_site_options' ) );
				add_filter( 'network_admin_plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );
			}
		}

		// load all submenu classes into the $this->submenu array
		// the id of each submenu item must be unique
		private function set_objects() {
			$menus = array( 
				'submenu', 
				'setting'	// setting must be last to extend submenu/advanced
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
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( empty( $this->readme_info[$lca] ) )
					$this->readme_info[$lca] = $this->p->util->parse_readme( $lca, $expire_secs );
			}
		}

		public function add_admin_settings() {
			foreach ( $this->p->cf['*']['lib']['setting'] as $id => $name ) {
				if ( array_key_exists( $id, $this->submenu ) ) {
					$parent_slug = 'options-general.php';
					$this->submenu[$id]->add_submenu_page( $parent_slug );
				}
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
				WPSSO_MENU_PRIORITY
			);
			add_action( 'load-'.$this->pagehook, array( &$this, 'load_form_page' ) );
		}

		protected function add_submenu_page( $parent_slug, $menu_id = '', $menu_name = '' ) {
			$short_aop = $this->p->cf['plugin'][$this->p->cf['lca']]['short'].
				( $this->p->check->aop( $this->p->cf['lca'] ) ? ' Pro' : ' Free' );

			if ( strpos ( $menu_id, 'separator' ) !== false ) {
				$menu_title = '<div style="z-index:999;
					padding:2px 0;
					margin:0;
					cursor:default;
					border-bottom:1px dotted;
					color:#666;" onclick="return false;"></div>';
				$menu_slug = '';
				$page_title = '';
				$function = '';
			} else {
				$menu_title = empty ( $menu_name ) ? $this->menu_name : $menu_name;
				$menu_slug = $this->p->cf['lca'].'-'.( empty( $menu_id ) ? $this->menu_id : $menu_id );
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

		// display a settings link on the main plugins page
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
				if ( $this->p->is_avail['aop'] ) {
					array_push( $links, '<a href="'.$urls['pro_support'].'">'.__( 'Support', WPSSO_TEXTDOM ).'</a>' );
					if ( ! $this->p->check->aop() ) 
						array_push( $links, '<a href="'.$urls['purchase'].'">'.__( 'Purchase License', WPSSO_TEXTDOM ).'</a>' );
				} else {
					array_push( $links, '<a href="'.$urls['wp_support'].'">'.__( 'Forum', WPSSO_TEXTDOM ).'</a>' );
					array_push( $links, '<a href="'.$urls['purchase'].'">'.__( 'Purchase Pro', WPSSO_TEXTDOM ).'</a>' );
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
			$opts = $this->p->opt->sanitize( $opts, $def_opts );
			$opts = apply_filters( $this->p->cf['lca'].'_save_options', $opts, WPSSO_OPTIONS_NAME );
			$this->p->notice->inf( __( 'Plugin settings have been updated.', WPSSO_TEXTDOM ).' '.
				sprintf( __( 'Wait %d seconds for cache objects to expire (default) or use the \'Clear All Cache(s)\' button.', WPSSO_TEXTDOM ), $this->p->options['plugin_object_cache_exp'] ), true );
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
			$opts = $this->p->opt->sanitize( $opts, $def_opts );	// cleanup excess options and sanitize

			$opts = apply_filters( $this->p->cf['lca'].'_save_site_options', $opts );
			update_site_option( WPSSO_SITE_OPTIONS_NAME, $opts );

			// store message in user options table
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
			$upload_dir = wp_upload_dir();	// returns assoc array with path info
			$user_opts = $this->p->mods['util']['user']->get_options();

			if ( ! empty( $_GET['action'] ) ) {
				if ( empty( $_GET[ WPSSO_NONCE ] ) )
					$this->p->debug->log( 'nonce token validation query field missing' );
				elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE ], $this->get_nonce() ) )
					$this->p->notice->err( __( 'Nonce token validation failed for plugin action (action ignored).', WPSSO_TEXTDOM ) );
				else {
					switch ( $_GET['action'] ) {
						case 'check_for_updates': 
							if ( ! empty( $this->p->options['plugin_'.$this->p->cf['lca'].'_tid'] ) ) {
								$this->readme_info = array();
								$this->p->update->check_for_updates( null, true );
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

			add_meta_box( $this->pagehook.'_help', __( 'Help and Support', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_help' ), $this->pagehook, 'side' );

			add_meta_box( $this->pagehook.'_info', __( 'Version Information', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_info' ), $this->pagehook, 'side' );

			add_meta_box( $this->pagehook.'_status_gpl', __( 'Standard Features', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_status_gpl' ), $this->pagehook, 'side' );

			add_meta_box( $this->pagehook.'_status_pro', __( 'Pro Features', WPSSO_TEXTDOM ), 
				array( &$this, 'show_metabox_status_pro' ), $this->pagehook, 'side' );
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

			if ( $this->menu_id !== 'contact' )		// the "settings" page displays its own error messages
				settings_errors( WPSSO_OPTIONS_NAME );	// display "error" and "updated" messages
			$this->set_form();				// define form for side boxes and show_form_content()
			if ( $this->p->debug->is_on() ) {
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
			if ( ! empty( $this->p->cf['*']['lib']['submenu'][$this->menu_id] ) ) {
				echo '<form name="'.$this->p->cf['lca'].'" 
					id="'.$this->p->cf['lca'].'_settings_form" 
					method="post" action="options.php">';
				echo $this->form->get_hidden( 'options_version', 
					$this->p->cf['opt']['version'] );
				echo $this->form->get_hidden( 'plugin_version', 
					$this->p->cf['plugin'][$this->p->cf['lca']]['version'] );
				settings_fields( $this->p->cf['lca'].'_setting' ); 

			} elseif ( ! empty( $this->p->cf['*']['lib']['sitesubmenu'][$this->menu_id] ) ) {
				echo '<form name="'.$this->p->cf['lca'].'" 
					id="'.$this->p->cf['lca'].'_settings_form" 
					method="post" action="edit.php?action='.WPSSO_SITE_OPTIONS_NAME.'">';
				echo '<input type="hidden" name="page" value="'.$this->menu_id.'">';
				echo $this->form->get_hidden( 'options_version', 
					$this->p->cf['opt']['version'] );
				echo $this->form->get_hidden( 'plugin_version', 
					$this->p->cf['plugin'][$this->p->cf['lca']]['version'] );
			}
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
			return empty( $this->p->cf['update_check_hours'] ) ? 
				86400 : $this->p->cf['update_check_hours'] * 3600;
		}

		public function show_metabox_info() {
			echo '<table class="sucom-setting">';
			foreach ( $this->p->cf['plugin'] as $lca => $info ) {
				if ( empty( $info['version'] ) )	// filter out extensions that are not installed
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

				//$update_info = class_exists( 'SucomUpdate' ) ?
				//	SucomUpdate::get_option( $lca ) : false;
	
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
						'Debug Messages' => array( 'classname' => 'SucomDebug' ),
						'Non-Persistant Cache' => array( 'status' => $this->p->is_avail['cache']['object'] ? 'on' : 'rec' ),
						'Open Graph / Rich Pin' => array( 'status' => class_exists( $this->p->cf['lca'].'opengraph' ) ? 'on' : 'rec' ),
						'Pro Update Check' => array( 'classname' => 'SucomUpdate' ),
						'Transient Cache' => array( 'status' => $this->p->is_avail['cache']['transient'] ? 'on' : 'rec' ),
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
				echo '<p><strong>Need Help with '.$info['short'].
					( $this->p->check->aop( $lca ) ? ' Pro' : ' Free' ).'?</strong></p>';
				echo '<ul>';
				if ( ! empty( $info['url']['faq'] ) ) {
					echo '<li>Review <a href="'.$info['url']['faq'].'" target="_blank">FAQs</a>';
					if ( ! empty( $info['url']['notes'] ) )
						echo ' and <a href="'.$info['url']['notes'].'" target="_blank">Notes</a>';
					echo '</li>';
				}
				if ( $this->p->check->aop( $lca ) && 
					! empty( $info['url']['pro_ticket'] ) )
						echo '<li><a href="'.$info['url']['pro_ticket'].'" 
							target="_blank">Submit a Support Ticket</a></li>';
				elseif ( ! empty( $info['url']['wp_support'] ) )
					echo '<li><a href="'.$info['url']['wp_support'].'" 
						target="_blank">Post in Support Forum</a></li>';
				echo '</ul>';
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
			$action_buttons = '<input type="submit" class="button-primary" value="'.$submit_text.'" />';

			$show_opts_next = SucomUtil::next_key( WpssoUser::show_opts(), $this->p->cf['form']['show_options'] );
			$show_opts_text = 'Show '.$this->p->cf['form']['show_options'][$show_opts_next];
			$show_opts_url = $this->p->util->get_admin_url( '?action=change_show_options&show_opts='.$show_opts_next );

			$action_buttons .= $this->form->get_button( $show_opts_text, 
				'button-secondary', null, wp_nonce_url( $show_opts_url,
					$this->get_nonce(), WPSSO_NONCE ) );

			if ( empty( $this->p->cf['*']['lib']['sitesubmenu'][$this->menu_id] ) )	// don't show on the network admin pages
				$action_buttons .= $this->form->get_button( __( 'Clear All Cache(s)', WPSSO_TEXTDOM ), 
					'button-secondary', null, wp_nonce_url( $this->p->util->get_admin_url( '?action=clear_all_cache' ),
						$this->get_nonce(), WPSSO_NONCE ) );

			if ( ! empty( $this->p->options['plugin_'.$this->p->cf['lca'].'_tid'] ) )
				$action_buttons .= $this->form->get_button( __( 'Check for Pro Update', WPSSO_TEXTDOM ), 
					'button-secondary', null, wp_nonce_url( $this->p->util->get_admin_url( '?action=check_for_updates' ), 
						$this->get_nonce(), WPSSO_NONCE ) );

			if ( empty( $this->p->cf['*']['lib']['sitesubmenu'][$this->menu_id] ) )	// don't show on the network admin pages
				$action_buttons .= $this->form->get_button( __( 'Reset Metabox Layout', WPSSO_TEXTDOM ), 
					'button-secondary', null, wp_nonce_url( $this->p->util->get_admin_url( '?action=clear_metabox_prefs' ),
						$this->get_nonce(), WPSSO_NONCE ) );

			return '<div class="'.$class.'">'.$action_buttons.'</div>';
		}

		protected function get_nonce() {
			return ( defined( 'NONCE_KEY' ) ? NONCE_KEY : '' ).plugin_basename( __FILE__ );
		}
	}
}

?>

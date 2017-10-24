<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoUser' ) ) {

	/*
	 * This class is extended by gpl/util/user.php or pro/util/user.php
	 * and the class object is created as $this->p->m['util']['user'].
	 */
	class WpssoUser extends WpssoMeta {

		protected static $pref = array();

		public function __construct() {
		}

		protected function add_actions() {

			$fb_cm_name_value = $this->p->options['plugin_cm_fb_name'];

			add_filter( 'user_contactmethods', array( &$this, 'add_contact_methods' ), 20, 2 );
			add_filter( 'user_'.$fb_cm_name_value.'_label', array( &$this, 'fb_contact_label' ), 20, 1 );

			if ( is_admin() ) {
				/**
				 * Hook a minimum number of admin actions to maximize performance.
				 * The user_id argument is always present when we're editing a user,
				 * but missing when viewing our own profile page.
				 */

				if ( ! empty( $_GET ) && ! isset( $_GET['updated'] ) ) {
					// common to both profile and user editing pages
					add_action( 'admin_init', array( &$this, 'add_meta_boxes' ) );
					// load_meta_page() priorities: 100 post, 200 user, 300 term
					// sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties
					add_action( 'current_screen', array( &$this, 'load_meta_page' ), 200, 1 );
				}

				add_filter( 'manage_users_columns', 
					array( &$this, 'add_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );

				add_filter( 'manage_users_sortable_columns', 
					array( &$this, 'add_sortable_columns' ), 10, 1 );

				add_filter( 'manage_users_custom_column', 
					array( &$this, 'get_column_content',), 10, 3 );

				/*
				 * The 'parse_query' action is hooked ONCE in the WpssoPost class
				 * to set the column orderby for post, term, and user edit tables.
				 *
				 * add_action( 'parse_query', array( &$this, 'set_column_orderby' ), 10, 1 );
				 */
				add_action( 'get_user_metadata', array( &$this, 'check_sortable_metadata' ), 10, 4 );

				// exit here if not a user or profile page
				$user_id = SucomUtil::get_request_value( 'user_id' );	// uses sanitize_text_field
				if ( empty( $user_id ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: empty user_id' );
					}
					return;
				}

				// hooks for user and profile editing
				add_action( 'edit_user_profile', array( &$this, 'show_metabox_section' ), 20 );

				add_action( 'edit_user_profile_update', array( &$this, 'sanitize_submit_cm' ), 5 );
				add_action( 'edit_user_profile_update', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_user_profile_update', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				add_action( 'personal_options_update', array( &$this, 'sanitize_submit_cm' ), 5 ); 
				add_action( 'personal_options_update', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY ); 
				add_action( 'personal_options_update', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY ); 
			}
		}

		public function get_mod( $mod_id ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = WpssoMeta::$mod_defaults;
			$mod['id'] = (int) $mod_id;
			$mod['name'] = 'user';
			$mod['obj'] =& $this;
			/*
			 * User
			 */
			$mod['is_user'] = true;

			return apply_filters( $this->p->cf['lca'].'_get_user_mod', $mod, $mod_id );
		}

		public function get_posts( array $mod, $posts_per_page = false, $paged = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$lca = $this->p->cf['lca'];

			if ( $posts_per_page === false ) {
				$posts_per_page = apply_filters( $lca.'_posts_per_page', get_option( 'posts_per_page' ), $mod );
			}

			if ( $paged === false ) {
				$paged = get_query_var( 'paged' );
			}

			if ( ! $paged > 1 ) {
				$paged = 1;
			}

			$posts = get_posts( array(
				'posts_per_page' => $posts_per_page,
				'paged' => $paged,
				'post_status' => 'publish',
				'post_type' => 'any',
				'has_password' => false,	// since wp 3.9
				'author' => $mod['id'],
			) );

			return $posts;
		}

		public function add_column_headings( $columns ) { 
			return $this->add_mod_column_headings( $columns, 'user' );
		}

		public function get_column_content( $value, $column_name, $user_id ) {
			$lca = $this->p->cf['lca'];
			if ( ! empty( $user_id ) ) {	// just in case
				$col_idx = str_replace( $lca.'_', '', $column_name );
				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						$value = (string) get_user_meta( $user_id, $col_info['meta_key'], true );	// $single = true
						if ( $value === 'none' ) {
							$value = '';
						}
					}
				}
			}
			return $value;
		}

		public function update_sortable_meta( $user_id, $col_idx, $content ) { 
			if ( ! empty( $user_id ) ) {	// just in case
				if ( ( $sort_cols = self::get_sortable_columns( $col_idx ) ) !== null ) {
					if ( isset( $sort_cols['meta_key'] ) ) {	// just in case
						update_user_meta( $user_id, $sort_cols['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $user_id, $meta_key, $single ) {

			$lca = $this->p->cf['lca'];
			static $do_once = array();

			if ( strpos( $meta_key, '_'.$lca.'_head_info_' ) !== 0 ) {	// example: _wpsso_head_info_og_img_thumb
				return $value;	// return null
			}

			if ( isset( $do_once[$user_id][$meta_key] ) ) {
				return $value;	// return null
			} else {
				$do_once[$user_id][$meta_key] = true;	// prevent recursion
			}

			if ( get_user_meta( $user_id, $meta_key, true ) === '' ) {	// returns empty string if meta not found
				$mod = $this->get_mod( $user_id );
				$head_meta_tags = $this->p->head->get_head_array( false, $mod, true );	// $read_cache = true
				$head_meta_info = $this->p->head->extract_head_info( $mod, $head_meta_tags );
			}

			return $value;	// return null
		}

		// hooked into the current_screen action
		// sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			// all meta modules set this property, so use it to optimize code execution
			if ( WpssoMeta::$head_meta_tags !== false || ! isset( $screen->id ) ) {
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'screen id: '.$screen->id );
			}

			$lca = $this->p->cf['lca'];

			switch ( $screen->id ) {
				case 'profile':		// user profile page
				case 'user-edit':	// user editing page
				case ( strpos( $screen->id, 'profile_page_' ) === 0 ? true : false ):		// your profile page
				case ( strpos( $screen->id, 'users_page_'.$lca ) === 0 ? true : false ):	// custom social settings page
					break;
				default:
					return;
					break;
			}

			$user_id = SucomUtil::get_user_object( false, 'id' );
			$mod = $this->get_mod( $user_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = '.get_option( 'home' ) );
				$this->p->debug->log( 'locale default = '.SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = '.SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = '.SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_user' ] ) ? false : true;
			if ( apply_filters( $lca.'_add_metabox_user', $add_metabox, $user_id ) ) {

				do_action( $lca.'_admin_user_head', $mod, $screen->id );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'setting head_meta_info static property' );
				}

				// $read_cache is false to generate notices etc.
				WpssoMeta::$head_meta_tags = $this->p->head->get_head_array( false, $mod, false );
				WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoMeta::$head_meta_tags );

				// check for missing open graph image and description values
				foreach ( array( 'image', 'description' ) as $mt_suffix ) {
					if ( empty( WpssoMeta::$head_meta_info['og:'.$mt_suffix] ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'og:'.$mt_suffix.' meta tag is value empty and required' );
						}
						if ( $this->p->notice->is_admin_pre_notices() ) {	// skip if notices already shown
							$this->p->notice->err( $this->p->msgs->get( 'notice-missing-og-'.$mt_suffix ) );
						}
					}
				}
			}

			$action_query = $lca.'-action';
			if ( ! empty( $_GET[$action_query] ) ) {
				$action_name = SucomUtil::sanitize_hookname( $_GET[$action_query] );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'found action query: '.$action_name );
				if ( empty( $_GET[ WPSSO_NONCE_NAME ] ) ) {	// WPSSO_NONCE_NAME is an md5() string
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'nonce token query field missing' );
				} elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {
					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".',
						'wpsso' ), 'user', $action_name ) );
				} else {
					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );
					switch ( $action_name ) {
						default: 
							do_action( $lca.'_load_meta_page_user_'.$action_name, $user_id, $screen->id );
							break;
					}
				}
			}
		}

		public function add_meta_boxes() {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$user_id = SucomUtil::get_user_object( false, 'id' );

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to add metabox for user ID '.$user_id );
				}
				return;
			}

			$lca = $this->p->cf['lca'];
			$metabox_id = $this->p->cf['meta']['id'];
			$metabox_title = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_user' ] ) ? false : true;

			if ( apply_filters( $this->p->cf['lca'].'_add_metabox_user', $add_metabox, $user_id ) ) {
				add_meta_box( $lca.'_'.$metabox_id, $metabox_title,
					array( &$this, 'show_metabox_custom_meta' ),
						$lca.'-user', 'normal', 'low' );
			}
		}

		public function show_metabox_section( $user ) {
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! current_user_can( 'edit_user', $user->ID ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: current user does not have edit privileges for user ID '.$user->ID );
				}
				return;
			}

			$lca = $this->p->cf['lca'];

			echo "\n".'<!-- '.$lca.' user metabox section begin -->'."\n";
			echo '<h3 id="'.$lca.'-metaboxes">'.WpssoAdmin::$pkg[$lca]['short'].'</h3>'."\n";
			echo '<div id="poststuff">'."\n";

			do_meta_boxes( $lca.'-user', 'normal', $user );

			echo "\n".'</div><!-- .poststuff -->'."\n";
			echo '<!-- '.$lca.' user metabox section end -->'."\n";
		}

		public function show_metabox_custom_meta( $user_obj ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$lca = $this->p->cf['lca'];
			$metabox_id = $this->p->cf['meta']['id'];
			$mod = $this->get_mod( $user_obj->ID );
			$tabs = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts = $this->get_options( $user_obj->ID );
			$def_opts = $this->get_defaults( $user_obj->ID );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id.' table rows' );	// start timer
			}

			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox_id, $key, WpssoMeta::$head_meta_info, $mod ), 
					apply_filters( $lca.'_'.$mod['name'].'_'.$key.'_rows', array(), $this->form, WpssoMeta::$head_meta_info, $mod ) );
			}
			$this->p->util->do_metabox_tabs( $metabox_id, $tabs, $table_rows );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id.' table rows' );	// end timer
			}
		}

		public function get_form_display_names( $roles = array( 'author', 'editor', 'administrator' ) ) {
			foreach ( $roles as $role ) {
				$query_args = array( 
					'role' => $role,
					'fields' => array( 'ID', 'display_name' ),
				);
				foreach ( get_users( $query_args ) as $user ) 
					$user_ids[$user->ID] = $user->display_name;
			}
			asort( $user_ids );
			return array_merge( array( 0 => 'none' ), $user_ids );
		}

		public function get_form_contact_fields( $fields = array() ) { 
			return array_merge( 
				array( 'none' => '[None]' ), 	// make sure none is first
				$this->add_contact_methods( array( 
					'author' => 'Author Archive', 
					'url' => 'Website'
				) )
			);
		}

		public function add_contact_methods( $fields = array(), $user = null ) { 

			$lca = $this->p->cf['lca'];
			$aop = $this->p->check->aop( $lca, true, $this->p->avail['*']['p_dir'] );

			// unset built-in contact fields and/or update their labels
			if ( ! empty( $this->p->cf['wp']['cm_names'] ) && is_array( $this->p->cf['wp']['cm_names'] ) && $aop ) {

				foreach ( $this->p->cf['wp']['cm_names'] as $id => $desc ) {

					$cm_enabled_key = 'wp_cm_'.$id.'_enabled';
					$cm_label_key = 'wp_cm_'.$id.'_label';

					if ( isset( $this->p->options[$cm_enabled_key] ) ) {
						if ( ! empty( $this->p->options[$cm_enabled_key] ) ) {
							$cm_label_value = SucomUtil::get_key_value( $cm_label_key, $this->p->options );
							if ( ! empty( $cm_label_value ) ) {	// just in case
								$fields[$id] = $cm_label_value;
							}
						} else {
							unset( $fields[$id] );
						}
					}
				}
			}

			// loop through each social website option prefix
			if ( ! empty( $this->p->cf['opt']['cm_prefix'] ) && is_array( $this->p->cf['opt']['cm_prefix'] ) ) {

				foreach ( $this->p->cf['opt']['cm_prefix'] as $id => $opt_pre ) {

					$cm_enabled_key = 'plugin_cm_'.$opt_pre.'_enabled';
					$cm_name_key = 'plugin_cm_'.$opt_pre.'_name';
					$cm_label_key = 'plugin_cm_'.$opt_pre.'_label';

					// not all social websites have a contact fields, so check
					if ( isset( $this->p->options[$cm_name_key] ) ) {
						if ( ! empty( $this->p->options[$cm_enabled_key] ) && ! empty( $this->p->options[$cm_name_key] ) ) {
							$cm_label_value = SucomUtil::get_key_value( $cm_label_key, $this->p->options );
							if ( ! empty( $cm_label_value ) ) {	// just in case
								$fields[$this->p->options[$cm_name_key]] = $cm_label_value;
							}
						}
					}
				}
			}

			asort( $fields );	// sort associative array by value

			return $fields;
		}

		public function fb_contact_label( $label ) { 
			return $label.'<br/><span class="description">'.
				__( '(not a Facebook Page URL)', 'wpsso' ).'</span>';
		}

		public function sanitize_submit_cm( $user_id ) {

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}

			foreach ( $this->p->cf['opt']['cm_prefix'] as $id => $opt_pre ) {

				// not all social websites have contact fields, so check
				if ( isset( $this->p->options['plugin_cm_'.$opt_pre.'_name'] ) ) {

					$cm_enabled_value = $this->p->options['plugin_cm_'.$opt_pre.'_enabled'];
					$cm_name_value = $this->p->options['plugin_cm_'.$opt_pre.'_name'];

					// sanitize values only for those enabled contact methods
					if ( isset( $_POST[$cm_name_value] ) && ! empty( $cm_enabled_value ) && ! empty( $cm_name_value ) ) {

						$value = wp_filter_nohtml_kses( $_POST[$cm_name_value] );

						if ( ! empty( $value ) ) {
							switch ( $cm_name_value ) {
								case $this->p->options['plugin_cm_skype_name']:
									// no change
									break;
								case $this->p->options['plugin_cm_twitter_name']:
									$value = SucomUtil::get_at_name( $value );
									break;
								default:
									// all other contact methods are assumed to be URLs
									if ( strpos( $value, '://' ) === false ) {
										$value = '';
									}
									break;
							}
						}
						$_POST[$cm_name_value] = $value;
					}
				}
			}
			return $user_id;
		}

		// provides backwards compatibility for wp 3.0
		public static function get_user_id_contact_methods( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( function_exists( 'wp_get_user_contact_methods' ) )	// since wp 3.7
				return wp_get_user_contact_methods( $user );
			else {
				$methods = array();
				if ( get_site_option( 'initial_db_version' ) < 23588 ) {
					$methods = array(
						'aim'    => __( 'AIM' ),
						'jabber' => __( 'Jabber / Google Talk' ),
						'yim'    => __( 'Yahoo Messenger' )
					); 
				}
				return apply_filters( 'user_contactmethods', $methods, $user );
			}
		}

		// returns an array of urls (or author names for the pinterest crawler)
		public function get_og_profile_urls( $user_ids, $crawler_name = false ) {
			$ret = array();

			if ( empty( $user_ids ) ) {
				return $ret;
			}

			if ( $crawler_name === false ) {
				$crawler_name = SucomUtil::get_crawler_name();
			}

			if ( ! is_array( $user_ids ) ) {
				$user_ids = array( $user_ids );
			}

			foreach ( $user_ids as $user_id ) {
				if ( empty( $user_id ) ) {
					continue;
				}

				if ( $crawler_name === 'pinterest' ) {
					$value = $this->get_author_meta( $user_id, $this->p->options['p_author_name'] );
				} else {
					$value = $this->get_author_website( $user_id, $this->p->options['og_author_field'] );
				}

				if ( ! empty( $value ) ) {	// make sure we don't add empty values
					$ret[] = $value;
				}
			}

			return $ret;
		}

		public static function get_author_id( array $mod ) {
			$author_id = false;

			if ( $mod['is_post'] ) {
				if ( $mod['post_author'] ) {
					$author_id = $mod['post_author'];
				}
			} elseif ( $mod['is_user'] ) {
				$author_id = $mod['id'];
			}

			return $author_id;
		}

		public function get_author_meta( $user_id, $field_id ) {
			$value = '';
			$is_user = SucomUtil::user_exists( $user_id );
			if ( $is_user ) {
				switch ( $field_id ) {
					case 'none':
						break;
					case 'fullname':
						$value = get_the_author_meta( 'first_name', $user_id ).' '.
							get_the_author_meta( 'last_name', $user_id );
						break;
					case 'description':
						$value = preg_replace( '/[\s\n\r]+/s', ' ', 
							get_the_author_meta( $field_id, $user_id ) );
						break;
					default:
						$value = get_the_author_meta( $field_id, $user_id );
						break;
				}
				$value = trim( $value );	// just in case
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id '.$user_id.' is not a wordpress user' );
			}

			$value = apply_filters( $this->p->cf['lca'].'_get_author_meta', $value, $user_id, $field_id, $is_user );

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'user id '.$user_id.' '.$field_id.': '.$value );

			return $value;
		}

		public function get_author_website( $user_id, $field_id = 'url' ) {
			$url = '';
			$is_user = SucomUtil::user_exists( $user_id );
			if ( $is_user ) {
				switch ( $field_id ) {
					case 'none':
						break;
					case 'index':
						$url = get_author_posts_url( $user_id );
						break;
					default:
						$url = get_the_author_meta( $field_id, $user_id );
	
						// if empty or not a url, then fallback to the author index page,
						// if the requested field is the opengraph or link author field
						if ( empty( $url ) || ! preg_match( '/:\/\//', $url ) ) {
							if ( $this->p->options['og_author_fallback'] && 
								( $field_id === $this->p->options['og_author_field'] || 
									$field_id === $this->p->options['seo_author_field'] ) ) {
	
								if ( $this->p->debug->enabled )
									$this->p->debug->log( 'fetching the author index page url as fallback' );
								$url = get_author_posts_url( $user_id );
							}
						}
						break;
				}
				$url = trim( $url );	// just in case
			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id '.$user_id.' is not a wordpress user' );
			}
			$url = apply_filters( $this->p->cf['lca'].'_get_author_website', $url, $user_id, $field_id, $is_user );
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id '.$user_id.' '.$field_id.': '.$url );
			}
			return $url;
		}

		public static function reset_metabox_prefs( $pagehook, $box_ids = array(), $meta_name = '', $context = '', $force = false ) {
			$user_id = get_current_user_id();	// since wp 3.0
			switch ( $meta_name ) {
				case 'order':
				case 'meta-box-order':
					$meta_states = array( 'meta-box-order' );
					break;
				case 'hidden':
				case 'metaboxhidden':
					$meta_states = array( 'metaboxhidden' );
					break;
				case 'closed':
				case 'closedpostboxes':
					$meta_states = array( 'closedpostboxes' );
					break;
				default:
					$meta_states = array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' );
					break;
			}
			foreach ( $meta_states as $state ) {
				$meta_key = $state.'_'.$pagehook; 
				if ( $force && empty( $box_ids ) ) {
					delete_user_option( $user_id, $meta_key, true );
				}
				$is_changed = false;
				$is_default = false;
				$user_opts = get_user_option( $meta_key, $user_id );
				if ( empty( $user_opts ) ) {
					$is_changed = true;
					$is_default = true;
					$user_opts = array();
				}
				if ( $is_default || $force ) {
					foreach ( $box_ids as $box_id ) {
						// change the order only if forced (default is controlled by add_meta_box() order)
						if ( $force && $state == 'meta-box-order' && ! empty( $user_opts[$context] ) ) {
							// don't proceed if the metabox is already first
							if ( strpos( $user_opts[$context], $pagehook.'_'.$box_id ) !== 0 ) {
								$boxes = explode( ',', $user_opts[$context] );
								// remove the box, no matter its position in the array
								if ( $key = array_search( $pagehook.'_'.$box_id, $boxes ) !== false ) {
									unset( $boxes[$key] );
								}
								// assume we want to be top-most
								array_unshift( $boxes, $pagehook.'_'.$box_id );
								$user_opts[$context] = implode( ',', $boxes );
								$is_changed = true;
							}
						} else {
							// check to see if the metabox is present for that state
							$key = array_search( $pagehook.'_'.$box_id, $user_opts );

							// if we're not targetting, then clear it
							if ( empty( $meta_name ) && $key !== false ) {
								unset( $user_opts[$key] );
								$is_changed = true;
							// otherwise if we want a state, add if it's missing
							} elseif ( ! empty( $meta_name ) && $key === false ) {
								$user_opts[] = $pagehook.'_'.$box_id;
								$is_changed = true;
							}
						}
					}
				}
				if ( $is_default || $is_changed ) {
					update_user_option( $user_id, $meta_key, array_unique( $user_opts ), true );
				}
			}
		}

		// called by the WpssoRegister::uninstall_plugin() method
		public static function delete_metabox_prefs( $user_id = false ) {

			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;

			$cf = WpssoConfig::get_config( false, true );

			$parent_slug = 'options-general.php';
			foreach ( array_keys( $cf['*']['lib']['setting'] ) as $lib_id ) {
				$menu_slug = $cf['lca'].'-'.$lib_id;
				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}

			$parent_slug = $cf['lca'].'-'.key( $cf['*']['lib']['submenu'] );
			foreach ( array_keys( $cf['*']['lib']['submenu'] ) as $lib_id ) {
				$menu_slug = $cf['lca'].'-'.$lib_id;
				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}
		}

		private static function delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug ) {
			$pagehook = get_plugin_page_hookname( $menu_slug, $parent_slug);
			foreach ( array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' ) as $state ) {
				$meta_key = $state.'_'.$pagehook;
				if ( $user_id === false ) {
					foreach ( get_users( array( 'meta_key' => $meta_key ) ) as $user ) {
						delete_user_option( $user->ID, $meta_key, true );
					}
				} elseif ( is_numeric( $user_id ) ) {
					delete_user_option( $user_id, $meta_key, true );
				}
			}
		}

		public static function save_pref( $user_prefs, $user_id = false ) {
			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;

			if ( ! current_user_can( 'edit_user', $user_id ) )
				return false;

			if ( ! is_array( $user_prefs ) || 
				empty( $user_prefs ) )
					return false;

			$old_prefs = self::get_pref( false, $user_id );	// get all prefs for user
			$new_prefs = array_merge( $old_prefs, $user_prefs );

			// don't bother saving unless we have to
			if ( $old_prefs !== $new_prefs ) {
				self::$pref[$user_id] = $new_prefs;	// update the pref cache
				unset( $new_prefs['prefs_filtered'] );
				update_user_meta( $user_id, WPSSO_PREF_NAME, $new_prefs );
				return true;
			} else return false;
		}

		public static function get_pref( $idx = false, $user_id = false ) {
			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;

			if ( ! isset( self::$pref[$user_id]['prefs_filtered'] ) || self::$pref[$user_id]['prefs_filtered'] !== true ) {

				$wpsso = Wpsso::get_instance();

				self::$pref[$user_id] = get_user_meta( $user_id, WPSSO_PREF_NAME, true );

				if ( ! is_array( self::$pref[$user_id] ) ) {
					self::$pref[$user_id] = array();
				}

				self::$pref[$user_id]['prefs_filtered'] = true;	// set before calling filter to prevent recursion
				self::$pref[$user_id] = apply_filters( $wpsso->cf['lca'].'_get_user_pref', self::$pref[$user_id], $user_id );

				if ( ! isset( self::$pref[$user_id]['show_opts'] ) ) {
					self::$pref[$user_id]['show_opts'] = $wpsso->options['plugin_show_opts'];
				}
			}

			if ( $idx !== false ) {
				if ( isset( self::$pref[$user_id][$idx] ) ) {
					return self::$pref[$user_id][$idx];
				} else {
					return false;
				}
			} else {
				return self::$pref[$user_id];
			}
		}

		public static function is_show_all( $user_id = false ) {
			return $this->show_opts( 'all', $user_id );
		}

		public static function get_show_val( $user_id = false ) {
			return $this->show_opts( false, $user_id );
		}

		// returns the value for show_opts, or return true/false if a value to compare is provided
		public static function show_opts( $compare = false, $user_id = false ) {
			$user_id = $user_id === false ? 
				get_current_user_id() : $user_id;
			$value = self::get_pref( 'show_opts' );
			if ( $compare !== false )
				return $compare === $value ? true : false;
			else return $value;
		}

		public function clear_cache( $user_id, $rel_id = false ) {
			$lca = $this->p->cf['lca'];
			$mod = $this->get_mod( $user_id );
			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$cache_salt = SucomUtil::get_mod_salt( $mod, $sharing_url );

			$transients = array( 'WpssoHead::get_head_array' => array( $cache_salt ) );
			$transients = apply_filters( $lca.'_user_cache_transients', $transients, $mod, $sharing_url );

			$wp_objects = array();
			$wp_objects = apply_filters( $lca.'_user_cache_objects', $wp_objects, $mod, $sharing_url );

			$deleted = $this->p->util->clear_cache_objects( $transients, $wp_objects );

			if ( ! empty( $this->p->options['plugin_show_purge_count'] ) && $deleted > 0 ) {
				$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.', 
					true, __FUNCTION__.'_items_removed', true );	// can be dismissed
			}

			return $user_id;
		}
	}
}

?>

<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoUser' ) ) {

	/**
	 * This class is extended by gpl/util/user.php or pro/util/user.php
	 * and the class object is created as $this->p->m[ 'util' ][ 'user' ].
	 */
	class WpssoUser extends WpssoWpMeta {

		protected static $cache_pref = array();

		public function __construct() {
		}

		protected function add_actions() {

			$is_admin   = is_admin();
			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;
			$cm_fb_name = $this->p->options['plugin_cm_fb_name'];

			if ( ! SucomUtilWP::role_exists( 'person' ) ) {
				add_role( 'person', _x( 'Person', 'user role', 'wpsso' ), array() );
			}

			if ( ! empty( $this->p->options['plugin_new_user_is_person'] ) ) {
				if ( is_multisite() ) {
					add_action( 'wpmu_new_user', array( __CLASS__, 'add_person_role' ), 20, 1 );
				} else {
					add_action( 'user_register', array( __CLASS__, 'add_person_role' ), 20, 1 );
				}
			}

			add_filter( 'user_contactmethods', array( $this, 'add_contact_methods' ), 20, 2 );
			add_filter( 'user_' . $cm_fb_name . '_label', array( $this, 'fb_contact_label' ), 20, 1 );

			/**
			 * Hook a minimum number of admin actions to maximize performance. The user_id argument is 
			 * always present when we're editing a user, but missing when viewing our own profile page.
			 */
			if ( $is_admin ) {

				if ( ! empty( $_GET ) ) {

					/**
					 * Common to both profile and user editing pages.
					 */
					add_action( 'admin_init', array( $this, 'add_meta_boxes' ) );

					/**
					 * Sets the WpssoWpMeta::$head_meta_tags and WpssoWpMeta::$head_meta_info class properties.
					 * load_meta_page() priorities: 100 post, 200 user, 300 term.
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 200, 1 );
				}

				add_filter( 'views_users', array( $this, 'add_person_view' ) );
				add_filter( 'manage_users_columns', array( $this, 'add_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );
				add_filter( 'manage_users_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );
				add_filter( 'manage_users_custom_column', array( $this, 'get_column_content',), 10, 3 );

				/**
				 * The 'parse_query' action is hooked ONCE in the WpssoPost class
				 * to set the column orderby for post, term, and user edit tables.
				 *
				 * add_action( 'parse_query', array( $this, 'set_column_orderby' ), 10, 1 );
				 */
				add_action( 'get_user_metadata', array( $this, 'check_sortable_metadata' ), 10, 4 );

				/**
				 * Exit here if not a user or profile page.
				 */
				$user_id = SucomUtil::get_request_value( 'user_id' );	// uses sanitize_text_field

				if ( empty( $user_id ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: empty user_id' );
					}

					return;
				}

				/**
				 * Hooks for user and profile editing.
				 */
				add_action( 'edit_user_profile', array( $this, 'show_metabox_section' ), 20 );

				add_action( 'edit_user_profile_update', array( $this, 'sanitize_submit_cm' ), 5 );
				add_action( 'edit_user_profile_update', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_user_profile_update', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				add_action( 'personal_options_update', array( $this, 'sanitize_submit_cm' ), 5 );
				add_action( 'personal_options_update', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'personal_options_update', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );
			}
		}

		public function get_mod( $mod_id ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = WpssoWpMeta::$mod_defaults;

			/**
			 * Common elements.
			 */
			$mod[ 'id' ]   = is_numeric( $mod_id ) ? (int) $mod_id : 0;	// Cast as integer.
			$mod[ 'name' ] = 'user';
			$mod[ 'obj' ]  =& $this;

			/**
			 * User elements.
			 */
			$mod[ 'is_user' ] = true;

			return apply_filters( $this->p->lca . '_get_user_mod', $mod, $mod_id );
		}

		public static function get_public_user_ids() {

			$wpsso =& Wpsso::get_instance();

			/**
			 * Default 'writer' roles are:
			 *
			 * 'writer' => array(	// Users that can write posts.
			 *	'administrator',
			 *	'editor',
			 *	'author',
			 *	'contributor',
			 * );
			 */
			$roles = $wpsso->cf['wp']['roles']['writer'];

			return SucomUtilWP::get_user_ids_for_roles( $roles );
		}

		public static function get_person_names( $add_none = true ) {

			$wpsso =& Wpsso::get_instance();

			$roles = $wpsso->cf['wp']['roles']['person'];

			return SucomUtilWP::get_user_select_for_roles( $roles, null, $add_none );
		}

		public function get_posts_ids( array $mod, $ppp = false, $paged = false, array $posts_args = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( false === $ppp ) {
				$ppp = apply_filters( $this->p->lca . '_posts_per_page', get_option( 'posts_per_page' ), $mod );
			}

			if ( false === $paged ) {
				$paged = get_query_var( 'paged' );
			}

			if ( ! $paged > 1 ) {
				$paged = 1;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'calling get_posts() for posts authored by ' . 
					$mod[ 'name' ] . ' id ' . $mod[ 'id' ] . ' (posts_per_page is ' . $ppp . ')' );
			}

			$posts_args = array_merge( array(
				'has_password'   => false,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'paged'          => $paged,
				'post_status'    => 'publish',		// Only 'publish', not 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or 'trash'.
				'post_type'      => 'any',		// Return post, page, or any custom post type.
				'posts_per_page' => $ppp,
				'author'         => $mod[ 'id' ],
			), $posts_args, array( 'fields' => 'ids' ) );	// Return an array of post ids.

			$mtime_max   = SucomUtil::get_const( 'WPSSO_GET_POSTS_MAX_TIME', 0.10 );
			$mtime_start = microtime( true );
			$post_ids    = get_posts( $posts_args );
			$mtime_total = microtime( true ) - $mtime_start;

			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

				$info = $this->p->cf[ 'plugin' ][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow query detected - WordPress get_posts() took %1$0.3f secs'.
						' to get posts authored by user ID %2$d', $mtime_total, $mod[ 'id' ] ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $mtime_max );

				// translators: %1$0.3f is a number of seconds, %2$d is an ID number, %3$s is a recommended max
				$error_msg = sprintf( __( 'Slow query detected - WordPress get_posts() took %1$0.3f secs to get posts authored by user ID %2$d (%3$s).',
					'wpsso' ), $mtime_total, $mod[ 'id' ], $rec_max_msg );

				/**
				 * Show an admin warning notice, if notices not already shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {
					$this->p->notice->warn( $error_msg );
				}

				// translators: %s is the short plugin name
				$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info[ 'short' ] );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( count( $post_ids ) . ' post ids returned in ' . sprintf( '%0.3f secs', $mtime_total ) );
			}

			return $post_ids;
		}

		public static function add_person_role( $user_id ) {

			$user_obj = get_user_by( 'ID', $user_id );

			$user_obj->add_role( 'person' );
		}

		public static function remove_person_role( $user_id ) {

			$user_obj = get_user_by( 'ID', $user_id );

			$user_obj->remove_role( 'person' );
		}

		public function add_person_view( $user_views ) {

			$user_views    = array_reverse( $user_views );
			$all_view_link = $user_views['all'];

			unset( $user_views['all'], $user_views['person'] );

			$role_label = _x( 'Person', 'user role', 'wpsso' );
			$role_view  = add_query_arg( 'role', 'person', admin_url( 'users.php' ) );
			$user_query = new WP_User_Query( array( 'role' => 'person' ) );
			$user_count = $user_query->get_total();

			$user_views['person'] = '<a href="' . $role_view . '">' .  $role_label . '</a> (' . $user_count . ')';

			$user_views['all'] = $all_view_link;

			$user_views = array_reverse( $user_views );

			return $user_views;
		}

		public function add_column_headings( $columns ) {
			return $this->add_mod_column_headings( $columns, 'user' );
		}

		public function get_column_content( $value, $column_name, $user_id ) {

			if ( ! empty( $user_id ) && strpos( $column_name, $this->p->lca . '_' ) === 0 ) {	// just in case

				$col_key = str_replace( $this->p->lca . '_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						$value = $this->get_meta_cache_value( $user_id, $col_info['meta_key'] );
					}
				}
			}
			return $value;
		}

		public function get_meta_cache_value( $user_id, $meta_key, $none = '' ) {

			$meta_cache = wp_cache_get( $user_id, 'user_meta' );	// optimize and check wp_cache first

			if ( isset( $meta_cache[$meta_key][0] ) ) {
				$value = (string) maybe_unserialize( $meta_cache[$meta_key][0] );
			} else {
				$value = (string) get_user_meta( $user_id, $meta_key, true );	// $single = true
			}

			if ( $value === 'none' ) {
				$value = $none;
			}

			return $value;
		}

		public function update_sortable_meta( $user_id, $col_key, $content ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $user_id ) ) {	// just in case
				if ( ( $sort_cols = self::get_sortable_columns( $col_key ) ) !== null ) {
					if ( isset( $sort_cols['meta_key'] ) ) {	// just in case
						update_user_meta( $user_id, $sort_cols['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $user_id, $meta_key, $single ) {

			static $do_once = array();

			if ( strpos( $meta_key, '_' . $this->p->lca . '_head_info_' ) !== 0 ) {	// example: _wpsso_head_info_og_img_thumb
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

		/**
		 * Hooked into the current_screen action.
		 * Sets the WpssoWpMeta::$head_meta_tags and WpssoWpMeta::$head_meta_info class properties.
		 */
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * All meta modules set this property, so use it to optimize code execution.
			 */
			if ( false !== WpssoWpMeta::$head_meta_tags || ! isset( $screen->id ) ) {
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'screen id is ' . $screen->id );
			}

			switch ( $screen->id ) {
				case 'profile':		// User profile page.
				case 'user-edit':	// User editing page.
				case ( strpos( $screen->id, 'profile_page_' ) === 0 ? true : false ):		// Your profile page.
				case ( strpos( $screen->id, 'users_page_' . $this->p->lca ) === 0 ? true : false ):	// Custom social settings page.
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'screen id can show metabox' );
					}
					break;
				default:
					return;
					break;
			}

			$user_id = SucomUtil::get_user_object( false, 'id' );
			$mod = $this->get_mod( $user_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale default = ' . SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			WpssoWpMeta::$head_meta_tags = array();

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_user' ] ) ? false : true;
			$add_metabox = apply_filters( $this->p->lca . '_add_metabox_user', $add_metabox, $user_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for user ID ' . $user_id . ' is ' . 
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {

				do_action( $this->p->lca . '_admin_user_head', $mod, $screen->id );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'setting head_meta_info static property' );
				}

				/**
				 * $read_cache is false to generate notices etc.
				 */
				WpssoWpMeta::$head_meta_tags = $this->p->head->get_head_array( false, $mod, false );
				WpssoWpMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoWpMeta::$head_meta_tags );

				/**
				 * Check for missing open graph image and description values.
				 */
				foreach ( array( 'image', 'description' ) as $mt_suffix ) {

					if ( empty( WpssoWpMeta::$head_meta_info['og:' . $mt_suffix] ) ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'og:' . $mt_suffix . ' meta tag is value empty and required' );
						}

						if ( $this->p->notice->is_admin_pre_notices() ) {	// Skip if notices already shown.

							$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;
							$error_msg  = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

							$this->p->notice->err( $error_msg, null, $notice_key );
						}
					}
				}
			}

			$action_query = $this->p->lca . '-action';

			if ( ! empty( $_GET[$action_query] ) ) {

				$action_name = SucomUtil::sanitize_hookname( $_GET[$action_query] );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found action query: ' . $action_name );

				}

				if ( empty( $_GET[ WPSSO_NONCE_NAME ] ) ) {	// WPSSO_NONCE_NAME is an md5() string

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'nonce token query field missing' );
					}

				} elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".',
						'wpsso' ), 'user', $action_name ) );

				} else {

					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

					switch ( $action_name ) {

						default:

							do_action( $this->p->lca . '_load_meta_page_user_' . $action_name, $user_id, $screen->id );

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
					$this->p->debug->log( 'insufficient privileges to add metabox for user ID ' . $user_id );
				}
				return;
			}

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_user' ] ) ? false : true;
			$add_metabox = apply_filters( $this->p->lca . '_add_metabox_user', $add_metabox, $user_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for user ID ' . $user_id . ' is ' . 
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {

				$metabox_id      = $this->p->cf['meta'][ 'id' ];
				$metabox_title   = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
				$metabox_screen  = $this->p->lca . '-user';
				$metabox_context = 'normal';
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback.
					'__block_editor_compatible_meta_box' => true,
				);

				add_meta_box( $this->p->lca . '_' . $metabox_id, $metabox_title,
					array( $this, 'show_metabox_custom_meta' ), $metabox_screen,
						$metabox_context, $metabox_prio, $callback_args );
			}
		}

		public function show_metabox_section( $user_obj ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! isset( $user_obj->ID ) ) {	// Just in case.
				return;
			}

			if ( ! current_user_can( 'edit_user', $user_obj->ID ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: current user does not have edit privileges for user ID ' . $user_obj->ID );
				}
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'doing metabox for ' . $this->p->lca . '-user' );
			}

			$metabox_screen  = $this->p->lca . '-user';
			$metabox_context = 'normal';

			echo "\n" . '<!-- ' . $this->p->lca . ' user metabox section begin -->' . "\n";
			echo '<h3 id="' . $this->p->lca . '-metaboxes">' . WpssoAdmin::$pkg[$this->p->lca][ 'short' ] . '</h3>' . "\n";
			echo '<div id="poststuff">' . "\n";

			do_meta_boxes( $metabox_screen, $metabox_context, $user_obj );

			echo "\n" . '</div><!-- .poststuff -->' . "\n";
			echo '<!-- ' . $this->p->lca . ' user metabox section end -->' . "\n";
		}

		public function ajax_metabox_custom_meta() {
			die( '-1' );	// Nothing to do.
		}

		public function show_metabox_custom_meta( $user_obj ) {
			echo $this->get_metabox_custom_meta( $user_obj );
		}

		public function get_metabox_custom_meta( $user_obj ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
			$metabox_id = $this->p->cf['meta'][ 'id' ];
			$mod        = $this->get_mod( $user_obj->ID );
			$tabs       = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $user_obj->ID );
			$def_opts   = $this->get_defaults( $user_obj->ID );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id . ' table rows' );	// start timer
			}

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = $this->p->lca . '_' . $mod[ 'name' ] . '_' . $tab_key . '_rows';

				$table_rows[ $tab_key ] = array_merge(
					$this->get_table_rows( $metabox_id, $tab_key, WpssoWpMeta::$head_meta_info, $mod ),
					(array) apply_filters( $filter_name, array(), $this->form, WpssoWpMeta::$head_meta_info, $mod )
				);
			}

			$tabbed_args = array(
				'layout' => 'vertical',
			);

			$metabox_html = $this->p->util->get_metabox_tabbed( $metabox_id, $tabs, $table_rows, $tabbed_args );

			if ( $doing_ajax ) {
				$metabox_html .= '<script type="text/javascript">sucomInitTooltips();</script>' . "\n";
			}

			$container_id = $this->p->lca . '_metabox_' . $metabox_id . '_inside';
			$metabox_html = "\n" . '<div id="' . $container_id . '">' . $metabox_html . '</div><!-- #'. $container_id . ' -->' . "\n";

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id . ' table rows' );	// End timer.
			}

			return $metabox_html;
		}

		public function get_form_contact_fields( $fields = array() ) {

			return array( 'none' => '[None]' ) + $this->add_contact_methods( array(
				'author' => 'Author Archive',
				'url' => 'WebSite'
			) );
		}

		public function add_contact_methods( $fields = array(), $user = null ) {

			$has_pdir = $this->p->avail[ '*' ][ 'p_dir' ];
			$has_pp   = $this->p->check->pp( $this->p->lca, true, $has_pdir );

			/**
			 * Unset built-in contact fields and/or update their labels.
			 */
			if ( ! empty( $this->p->cf['wp']['cm_names'] ) && is_array( $this->p->cf['wp']['cm_names'] ) && $has_pp ) {

				foreach ( $this->p->cf['wp']['cm_names'] as $id => $desc ) {

					$cm_enabled_key = 'wp_cm_' . $id . '_enabled';
					$cm_label_key   = 'wp_cm_' . $id . '_label';

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

			/**
			 * Loop through each social website option prefix.
			 */
			if ( ! empty( $this->p->cf['opt']['cm_prefix'] ) && is_array( $this->p->cf['opt']['cm_prefix'] ) ) {

				foreach ( $this->p->cf['opt']['cm_prefix'] as $id => $opt_pre ) {

					$cm_enabled_key = 'plugin_cm_' . $opt_pre . '_enabled';
					$cm_name_key    = 'plugin_cm_' . $opt_pre . '_name';
					$cm_label_key   = 'plugin_cm_' . $opt_pre . '_label';

					/**
					 * Not all social websites have a contact fields, so check.
					 */
					if ( isset( $this->p->options[$cm_name_key] ) ) {
						if ( ! empty( $this->p->options[$cm_enabled_key] ) && ! empty( $this->p->options[$cm_name_key] ) ) {
							$cm_label_value = SucomUtil::get_key_value( $cm_label_key, $this->p->options );
							if ( ! empty( $cm_label_value ) ) {	// Just in case.
								$fields[$this->p->options[$cm_name_key]] = $cm_label_value;
							}
						}
					}
				}
			}

			asort( $fields );	// Sort associative array by value.

			return $fields;
		}

		public function fb_contact_label( $label ) {
			return $label . '<br/><span class="description">' . 
				__( '(not a Facebook Page URL)', 'wpsso' ) . '</span>';
		}

		public function sanitize_submit_cm( $user_id ) {

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}

			foreach ( $this->p->cf['opt']['cm_prefix'] as $id => $opt_pre ) {

				/**
				 * Not all social websites have contact fields, so check.
				 */
				if ( isset( $this->p->options['plugin_cm_' . $opt_pre . '_name'] ) ) {

					$cm_enabled_value = $this->p->options['plugin_cm_' . $opt_pre . '_enabled'];
					$cm_name_value = $this->p->options['plugin_cm_' . $opt_pre . '_name'];

					/**
					 * Sanitize values only for those enabled contact methods.
					 */
					if ( isset( $_POST[$cm_name_value] ) && ! empty( $cm_enabled_value ) && ! empty( $cm_name_value ) ) {

						$value = wp_filter_nohtml_kses( $_POST[$cm_name_value] );

						if ( ! empty( $value ) ) {

							switch ( $cm_name_value ) {

								case $this->p->options['plugin_cm_skype_name']:

									/**
									 * No change.
									 */

									break;

								case $this->p->options['plugin_cm_twitter_name']:

									$value = SucomUtil::get_at_name( $value );

									break;

								default:

									/**
									 * All other contact methods are assumed to be URLs.
									 */

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

		/**
		 * Provides backwards compatibility for wp 3.0.
		 */
		public static function get_user_id_contact_methods( $user_id ) {

			$user_obj = get_user_by( 'ID', $user_id );

			if ( function_exists( 'wp_get_user_contact_methods' ) ) {	// Since WP v3.7.

				return wp_get_user_contact_methods( $user_obj );

			} else {

				$methods = array();

				if ( get_site_option( 'initial_db_version' ) < 23588 ) {
					$methods = array(
						'aim'    => __( 'AIM' ),
						'jabber' => __( 'Jabber / Google Talk' ),
						'yim'    => __( 'Yahoo Messenger' )
					);
				}

				return apply_filters( 'user_contactmethods', $methods, $user_obj );
			}
		}

		/**
		 * Returns an array of author website URLs, or author names for the pinterest crawler.
		 */
		public function get_og_profile_urls( $user_ids, $crawler_name = false ) {

			$ret = array();

			if ( empty( $user_ids ) ) {	// Just in case.
				return $ret;
			}

			if ( ! is_array( $user_ids ) ) {
				$user_ids = array( $user_ids );
			}

			foreach ( $user_ids as $user_id ) {

				if ( empty( $user_id ) ) {
					continue;
				}

				$value = $this->get_author_website( $user_id, $this->p->options['og_author_field'] );

				if ( ! empty( $value ) ) {	// Make sure we don't add empty values.
					$ret[] = $value;
				}
			}

			return $ret;
		}

		public static function get_author_id( array $mod ) {

			$author_id = false;

			if ( $mod[ 'is_post' ] ) {
				if ( $mod[ 'post_author' ] ) {
					$author_id = $mod[ 'post_author' ];
				}
			} elseif ( $mod[ 'is_user' ] ) {
				$author_id = $mod[ 'id' ];
			}

			return $author_id;
		}

		public function get_author_meta( $user_id, $field_id ) {

			$user_exists = SucomUtilWP::user_exists( $user_id );
			$author_meta = '';

			if ( $user_exists ) {

				switch ( $field_id ) {

					case 'none':

						break;

					case 'fullname':

						$author_meta = get_the_author_meta( 'first_name', $user_id ) . ' ' . 
							get_the_author_meta( 'last_name', $user_id );

						break;

					case 'description':

						$author_meta = preg_replace( '/[\s\n\r]+/s', ' ',
							get_the_author_meta( $field_id, $user_id ) );

						break;

					default:

						$author_meta = get_the_author_meta( $field_id, $user_id );

						break;
				}

				$author_meta = trim( $author_meta );	// Just in case.

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id ' . $user_id . ' is not a WordPress user' );
			}

			$author_meta = apply_filters( $this->p->lca . '_get_author_meta', $author_meta, $user_id, $field_id, $user_exists );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id ' . $user_id . ' ' . $field_id . ': ' . $author_meta );
			}

			return $author_meta;
		}

		public function get_author_website( $user_id, $field_id = 'url' ) {

			$user_exists = SucomUtilWP::user_exists( $user_id );
			$website_url = '';

			if ( $user_exists ) {

				switch ( $field_id ) {

					case 'none':

						break;

					case 'index':

						$website_url = get_author_posts_url( $user_id );

						break;

					case 'twitter':

						$website_url = get_the_author_meta( $field_id, $user_id );

						if ( filter_var( $website_url, FILTER_VALIDATE_URL ) === false ) {
							$website_url = 'https://twitter.com/' . preg_replace( '/^@/', '', $website_url );
						}

						break;

					default:

						$website_url = get_the_author_meta( $field_id, $user_id );

						break;
				}

				$website_url = trim( $website_url );	// just in case

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id ' . $user_id . ' is not a WordPress user' );
			}

			$website_url = apply_filters( $this->p->lca . '_get_author_website', $website_url, $user_id, $field_id, $user_exists );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'user id ' . $user_id . ' ' . $field_id . ' = ' . $website_url );
			}

			return $website_url;
		}

		public static function reset_metabox_prefs( $pagehook, $box_ids = array(), $meta_name = '', $context = '', $force = false ) {

			$user_id = get_current_user_id();	// Since WP v3.0.

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

				$meta_key = $state . '_' . $pagehook;

				if ( $force && empty( $box_ids ) ) {
					delete_user_option( $user_id, $meta_key, false );	// $global = false
					delete_user_option( $user_id, $meta_key, true );	// $global = true
				}

				$is_changed = false;
				$is_default = false;
				$user_opts  = get_user_option( $meta_key, $user_id );

				if ( empty( $user_opts ) ) {
					$is_changed = true;
					$is_default = true;
					$user_opts = array();
				}

				if ( $is_default || $force ) {

					foreach ( $box_ids as $box_id ) {

						/**
						 * Change the order only if forced (default is controlled by add_meta_box() order).
						 */
						if ( $force && $state == 'meta-box-order' && ! empty( $user_opts[$context] ) ) {

							/**
							 * Don't proceed if the metabox is already first.
							 */
							if ( strpos( $user_opts[$context], $pagehook . '_' . $box_id ) !== 0 ) {

								$boxes = explode( ',', $user_opts[$context] );

								/**
								 * Remove the box, no matter its position in the array.
								 */
								if ( false !== ( $key = array_search( $pagehook . '_' . $box_id, $boxes ) ) ) {
									unset( $boxes[ $key ] );
								}

								/**
								 * Assume we want to be top-most.
								 */
								array_unshift( $boxes, $pagehook . '_' . $box_id );
								$user_opts[$context] = implode( ',', $boxes );
								$is_changed = true;
							}

						} else {

							/**
							 * Check to see if the metabox is present for that state.
							 */
							$key = array_search( $pagehook . '_' . $box_id, $user_opts );

							/**
							 * If we're not targetting, then clear it, otherwise if we want a state, add if it's missing.
							 */
							if ( empty( $meta_name ) && false !== $key ) {

								unset( $user_opts[ $key ] );

								$is_changed = true;

							} elseif ( ! empty( $meta_name ) && false === $key ) {

								$user_opts[] = $pagehook . '_' . $box_id;

								$is_changed = true;
							}
						}
					}
				}

				if ( $is_default || $is_changed ) {
					update_user_option( $user_id, $meta_key, array_unique( $user_opts ), true );	// $global = true
				}
			}
		}

		/**
		 * Called by the WpssoRegister::uninstall_plugin() method.
		 * Do not use Wpsso::get_instance() since the Wpsso class may not exist.
		 */
		public static function delete_metabox_prefs( $user_id = false, $slug_prefix = 'wpsso' ) {

			$cf = WpssoConfig::get_config( false, true );

			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

			$parent_slug = 'options-general.php';

			foreach ( array_keys( $cf[ '*' ][ 'lib' ]['setting'] ) as $lib_id ) {

				$menu_slug = $slug_prefix . '-' . $lib_id;

				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}

			$parent_slug = $slug_prefix . '-' . key( $cf[ '*' ][ 'lib' ]['submenu'] );

			foreach ( array_keys( $cf[ '*' ][ 'lib' ]['submenu'] ) as $lib_id ) {

				$menu_slug = $slug_prefix . '-' . $lib_id;

				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}
		}

		private static function delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug ) {

			$pagehook = get_plugin_page_hookname( $menu_slug, $parent_slug);

			foreach ( array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' ) as $state ) {

				$meta_key = $state . '_' . $pagehook;

				if ( false === $user_id ) {

					$users_args = array(
						'role'     => 'contributor',	// Contributors can delete_posts, edit_posts, read.
						'orderby'  => 'ID',
						'order'    => 'DESC',		// Newest user first.
						'meta_key' => $meta_key,	// The meta_key in the wp_usermeta table.
						'fields'   => array(		// Save memory and only return only specific fields.
							'ID',
						),
					);

					foreach ( get_users( $users_args ) as $user_obj ) {
						if ( ! empty( $user_obj->ID ) ) {	// Just in case.
							delete_user_option( $user_obj->ID, $meta_key, false );	// $global is false.
							delete_user_option( $user_obj->ID, $meta_key, true );	// $global is true.
						}
					}

				} elseif ( is_numeric( $user_id ) ) {
					delete_user_option( $user_id, $meta_key, false );	// $global is false.
					delete_user_option( $user_id, $meta_key, true );	// $global is true.
				}
			}
		}

		public static function save_pref( $user_prefs, $user_id = false ) {

			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}

			if ( ! is_array( $user_prefs ) || empty( $user_prefs ) ) {
				return false;
			}

			$old_prefs = self::get_pref( false, $user_id );	// get all prefs for user
			$new_prefs = array_merge( $old_prefs, $user_prefs );

			/**
			 * Don't bother saving unless we have to.
			 */
			if ( $old_prefs !== $new_prefs ) {

				self::$cache_pref[$user_id] = $new_prefs;	// update the pref cache

				unset( $new_prefs['prefs_filtered'] );

				update_user_meta( $user_id, WPSSO_PREF_NAME, $new_prefs );

				return true;

			} else {
				return false;
			}
		}

		public static function get_pref( $pref_key = false, $user_id = false ) {

			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

			if ( ! isset( self::$cache_pref[$user_id]['prefs_filtered'] ) || true !== self::$cache_pref[$user_id]['prefs_filtered'] ) {

				$wpsso =& Wpsso::get_instance();

				self::$cache_pref[$user_id] = get_user_meta( $user_id, WPSSO_PREF_NAME, true );

				if ( ! is_array( self::$cache_pref[$user_id] ) ) {
					self::$cache_pref[$user_id] = array();
				}

				self::$cache_pref[$user_id]['prefs_filtered'] = true;	// set before calling filter to prevent recursion

				self::$cache_pref[$user_id] = apply_filters( $wpsso->lca . '_get_user_pref', self::$cache_pref[$user_id], $user_id );

				if ( ! isset( self::$cache_pref[$user_id]['show_opts'] ) ) {
					self::$cache_pref[$user_id]['show_opts'] = $wpsso->options['plugin_show_opts'];
				}
			}

			if ( false !== $pref_key ) {
				if ( isset( self::$cache_pref[$user_id][$pref_key] ) ) {
					return self::$cache_pref[$user_id][$pref_key];
				} else {
					return false;
				}
			} else {
				return self::$cache_pref[$user_id];
			}
		}

		public static function is_show_all( $user_id = false ) {

			return self::show_opts( 'all', $user_id );
		}

		public static function get_show_val( $user_id = false ) {

			return self::show_opts( false, $user_id );
		}

		/**
		 * Returns the value for show_opts, or return true/false if a value to compare is provided.
		 */
		public static function show_opts( $compare = false, $user_id = false ) {

			$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

			$show_opts = self::get_pref( 'show_opts' );

			if ( $compare ) {
				return $compare === $show_opts ? true : false;
			} else {
				return $show_opts;
			}
		}

		public function clear_cache( $user_id, $rel_id = false ) {

			$mod           = $this->get_mod( $user_id );
			$col_meta_keys = WpssoWpMeta::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_key => $meta_key ) {
				delete_user_meta( $user_id, $meta_key );
			}

			$this->clear_mod_cache_types( $mod );
		}

		public function user_can_edit( $user_id, $rel_id = false ) {

			$user_can_edit = false;

			if ( ! $this->verify_submit_nonce() ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}
				return $user_can_edit;
			}

			if ( ! $user_can_edit = current_user_can( 'edit_user', $user_id ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to save settings for user ID ' . $user_id );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {
					$this->p->notice->err( sprintf( __( 'Insufficient privileges to save settings for user ID %1$s.',
						'wpsso' ), $user_id ) );
				}
			}

			return $user_can_edit;
		}
	}
}

<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoTerm' ) ) {

	class WpssoTerm extends WpssoWpMeta {

		protected $query_term_id  = 0;
		protected $query_tax_slug = '';
		protected $query_tax_obj  = false;

		public function __construct() {
		}

		protected function add_actions() {

			$is_admin   = is_admin();
			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;

			if ( $is_admin ) {

				/**
				 * Hook a minimum number of admin actions to maximize performance.
				 * The taxonomy and tag_ID arguments are always present when we're
				 * editing a category and/or tag page, so return immediately if
				 * they're not present.
				 */
				if ( ( $this->query_tax_slug = SucomUtil::get_request_value( 'taxonomy' ) ) === '' ) {	// uses sanitize_text_field
					return;
				}

				$this->query_tax_obj = get_taxonomy( $this->query_tax_slug );

				if ( empty( $this->query_tax_obj->public ) ) {
					return;
				}

				add_filter( 'manage_edit-' . $this->query_tax_slug . '_columns',
					array( $this, 'add_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );

				/**
				 * Enable orderby meta_key only if we have a meta table.
				 */
				if ( self::use_meta_table() ) {
					add_filter( 'manage_edit-' . $this->query_tax_slug . '_sortable_columns', 
						array( $this, 'add_sortable_columns' ), 10, 1 );
				}

				add_filter( 'manage_' . $this->query_tax_slug . '_custom_column',
					array( $this, 'get_column_content' ), 10, 3 );

				/**
				 * The 'parse_query' action is hooked ONCE in the WpssoPost class
				 * to set the column orderby for post, term, and user edit tables.
				 *
				 * add_action( 'parse_query', array( $this, 'set_column_orderby' ), 10, 1 );
				 */
				add_action( 'get_term_metadata', array( $this, 'check_sortable_metadata' ), 10, 4 );

				if ( ( $this->query_term_id = SucomUtil::get_request_value( 'tag_ID' ) ) === '' ) {	// uses sanitize_text_field
					return;
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'tax_slug / term_id = ' . $this->query_tax_slug . ' / ' . $this->query_term_id );
				}

				/**
				 * Available taxonomy and term actions:
				 *
				 * do_action( "create_$taxonomy",  $term_id, $tt_id );
				 * do_action( "created_$taxonomy", $term_id, $tt_id );
				 * do_action( "edited_$taxonomy",  $term_id, $tt_id );
				 * do_action( "delete_$taxonomy",  $term_id, $tt_id, $deleted_term );
				 *
				 * do_action( "create_term",       $term_id, $tt_id, $taxonomy );
				 * do_action( "created_term",      $term_id, $tt_id, $taxonomy );
				 * do_action( "edited_term",       $term_id, $tt_id, $taxonomy );
				 * do_action( 'delete_term',       $term_id, $tt_id, $taxonomy, $deleted_term );
				 */
				if ( ! empty( $_GET ) ) {

					add_action( 'admin_init', array( $this, 'add_meta_boxes' ) );

					/**
					 * Sets the WpssoWpMeta::$head_meta_tags and WpssoWpMeta::$head_meta_info class properties.
					 * load_meta_page() priorities: 100 post, 200 user, 300 term
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 300, 1 );
					add_action( $this->query_tax_slug . '_edit_form', array( $this, 'show_metaboxes' ), 100, 1 );
				}

				add_action( 'created_' . $this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'created_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );

				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );

				add_action( 'delete_' . $this->query_tax_slug, array( $this, 'delete_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'delete_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
			}
		}

		public function get_mod( $mod_id, $tax_slug = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = WpssoWpMeta::$mod_defaults;

			/**
			 * Common elements.
			 */
			$mod[ 'id' ]   = is_numeric( $mod_id ) ? (int) $mod_id : 0;	// Cast as integer.
			$mod[ 'name' ] = 'term';
			$mod[ 'obj' ]  =& $this;

			/**
			 * Term elements.
			 */
			$mod[ 'is_term' ]  = true;
			$mod[ 'tax_slug' ] = SucomUtil::get_term_object( $mod[ 'id' ], (string) $tax_slug, 'taxonomy' );

			return apply_filters( $this->p->lca . '_get_term_mod', $mod, $mod_id, $tax_slug );
		}

		public static function get_public_term_ids( $tax_name = null ) {

			global $wp_version;

			$terms_args = array(
				'fields' => 'ids',	// 'ids' (returns an array of ids).
			);

			$add_tax_in_args = version_compare( $wp_version, '4.5.0', '>=' ) ? true : false;
			$public_term_ids = array();

			foreach ( self::get_public_tax_names( $tax_name ) as $term_tax_name ) {
				
				if ( $add_tax_in_args ) {	// Since WP v4.5.
					$terms_args[ 'taxonomy' ] = $term_tax_name;
					$term_ids = get_terms( $terms_args );
				} else {
					$term_ids = get_terms( $term_tax_name, $terms_args );
				}

				foreach ( $term_ids as $term_id ) {
					$public_term_ids[ $term_id ] = $term_id;
				}
			}

			rsort( $public_term_ids );	// Newest id first.

			return $public_term_ids;
		}

		public static function get_public_tax_names( $tax_name = null ) {

			$get_tax_args = array(
				'public'  => 1,
				'show_ui' => 1,
			);

			if ( is_string( $tax_name ) ) {
				$get_tax_args[ 'name' ] = $tax_name;
			}

			$tax_oper = 'and';

			return get_taxonomies( $get_tax_args, 'names', $tax_oper );
		}

		/**
		 * Note that this method returns posts of child terms as well.
		 */
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
				$this->p->debug->log( 'calling get_posts() for ' . $mod[ 'name' ] . ' id ' . $mod[ 'id' ] . 
					' in taxonomy ' . $mod[ 'tax_slug' ] . ' (posts_per_page is ' . $ppp . ')' );
			}

			$posts_args = array_merge( array(
				'has_password'   => false,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'paged'          => $paged,
				'post_status'    => 'publish',		// Only 'publish', not 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or 'trash'.
				'post_type'      => 'any',		// Return post, page, or any custom post type.
				'posts_per_page' => $ppp,
				'tax_query'      => array(
				        array(
						'taxonomy'         => $mod[ 'tax_slug' ],
						'field'            => 'term_id',
						'terms'            => $mod[ 'id' ],
						'include_children' => true
					)
				),
			), $posts_args, array( 'fields' => 'ids' ) );	// Return an array of post ids.

			$mtime_max   = SucomUtil::get_const( 'WPSSO_GET_POSTS_MAX_TIME', 0.10 );
			$mtime_start = microtime( true );
			$post_ids    = get_posts( $posts_args );
			$mtime_total = microtime( true ) - $mtime_start;

			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

				$info = $this->p->cf[ 'plugin' ][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow query detected - WordPress get_posts() took %1$0.3f secs'.
						' to get posts for term ID %2$d in taxonomy %3$s', $mtime_total, $mod[ 'id' ], $mod[ 'tax_slug' ] ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $mtime_max );

				// translators: %1$0.3f is a number of seconds, %2$d is an ID number, %3$s is a taxonomy name, %4$s is a recommended max
				$error_msg = sprintf( __( 'Slow query detected - WordPress get_posts() took %1$0.3f secs to get posts for term ID %2$d in taxonomy %3$s (%4$s).',
					'wpsso' ), $mtime_total, $mod[ 'id' ], $mod[ 'tax_slug' ], $rec_max_msg );

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

		public function add_column_headings( $columns ) {
			return $this->add_mod_column_headings( $columns, 'term' );
		}

		public function get_column_content( $value, $column_name, $term_id ) {

			if ( ! empty( $term_id ) && strpos( $column_name, $this->p->lca . '_' ) === 0 ) {	// just in case

				$col_key = str_replace( $this->p->lca . '_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						$value = $this->get_meta_cache_value( $term_id, $col_info['meta_key'] );
					}
				}
			}

			return $value;
		}

		public function get_meta_cache_value( $term_id, $meta_key, $none = '' ) {

			$meta_cache = wp_cache_get( $term_id, 'term_meta' );	// optimize and check wp_cache first

			if ( isset( $meta_cache[$meta_key][0] ) ) {
				$value = (string) maybe_unserialize( $meta_cache[$meta_key][0] );
			} else {
				$value = (string) self::get_term_meta( $term_id, $meta_key, true );	// $single = true
			}

			if ( $value === 'none' ) {
				$value = $none;
			}

			return $value;
		}

		public function update_sortable_meta( $term_id, $col_key, $content ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $term_id ) ) {	// just in case
				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						self::update_term_meta( $term_id, $col_info['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $term_id, $meta_key, $single ) {

			static $do_once = array();

			if ( strpos( $meta_key, '_' . $this->p->lca . '_head_info_' ) !== 0 ) {	// example: _wpsso_head_info_og_img_thumb
				return $value;	// return null
			}

			if ( isset( $do_once[$term_id][$meta_key] ) ) {
				return $value;	// return null
			} else {
				$do_once[$term_id][$meta_key] = true;	// prevent recursion
			}

			if ( self::get_term_meta( $term_id, $meta_key, true ) === '' ) {	// returns empty string if meta not found

				$mod = $this->get_mod( $term_id );

				$head_meta_tags = $this->p->head->get_head_array( false, $mod, true );	// $read_cache = true
				$head_meta_info = $this->p->head->extract_head_info( $mod, $head_meta_tags );
			}

			if ( ! self::use_meta_table( $term_id ) ) {
				return self::get_term_meta( $term_id, $meta_key, $single );	// provide the options value
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
				$this->p->debug->log( 'screen id: ' . $screen->id );
			}

			switch ( $screen->id ) {
				case 'edit-' . $this->query_tax_slug:
					break;
				default:
					return;
					break;
			}

			$mod = $this->get_mod( $this->query_term_id, $this->query_tax_slug );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale default = ' . SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			WpssoWpMeta::$head_meta_tags = array();

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_term' ] ) ? false : true;
			$add_metabox = apply_filters( $this->p->lca . '_add_metabox_term', $add_metabox, $this->query_term_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for term ID ' . $this->query_term_id . ' is ' . 
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {

				do_action( $this->p->lca . '_admin_term_head', $mod, $screen->id );

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
						'wpsso' ), 'term', $action_name ) );

				} else {

					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

					switch ( $action_name ) {

						default:

							do_action( $this->p->lca . '_load_meta_page_term_' . $action_name, $this->query_term_id );

							break;
					}
				}
			}
		}

		public function add_meta_boxes() {

			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to add metabox for term ' . $this->query_term_id );
				}

				return;
			}

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_term' ] ) ? false : true;
			$add_metabox = apply_filters( $this->p->lca . '_add_metabox_term', $add_metabox, $this->query_term_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for term ID ' . $this->query_term_id . ' is ' . 
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {

				$metabox_id      = $this->p->cf['meta'][ 'id' ];
				$metabox_title   = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
				$metabox_screen  = $this->p->lca . '-term';
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

		public function show_metaboxes( $term_obj ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {
				return;
			}

			$metabox_screen  = $this->p->lca . '-term';
			$metabox_context = 'normal';

			echo "\n" . '<!-- ' . $this->p->lca . ' term metabox section begin -->' . "\n";
			echo '<h3 id="' . $this->p->lca . '-metaboxes">' . WpssoAdmin::$pkg[$this->p->lca][ 'short' ] . '</h3>' . "\n";
			echo '<div id="poststuff">' . "\n";

			do_meta_boxes( $metabox_screen, 'normal', $term_obj );

			echo "\n" . '</div><!-- .poststuff -->' . "\n";
			echo '<!-- ' . $this->p->lca . ' term metabox section end -->' . "\n";
		}

		public function ajax_metabox_custom_meta() {
			die( '-1' );	// Nothing to do.
		}

		public function show_metabox_custom_meta( $term_obj ) {
			echo $this->get_metabox_custom_meta( $term_obj );
		}

		public function get_metabox_custom_meta( $term_obj ) {

			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
			$metabox_id = $this->p->cf['meta'][ 'id' ];
			$mod        = $this->get_mod( $term_obj->term_id, $this->query_tax_slug );
			$tabs       = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $term_obj->term_id );
			$def_opts   = $this->get_defaults( $term_obj->term_id );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id . ' table rows' );	// start timer
			}

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$filter_name = $this->p->lca . '_' . $mod[ 'name' ] . '_' . $tab_key . '_rows';

				$table_rows[$tab_key] = array_merge(
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

		/**
		 * Hooked to these actions:
		 *
		 * do_action( "created_$taxonomy", $term_id, $tt_id );
		 * do_action( "edited_$taxonomy",  $term_id, $tt_id );
		 * do_action( "delete_$taxonomy",  $term_id, $tt_id, $deleted_term );
		 *
		 * Also called by WpssoPost::clear_cache() to clear the post term cache.
		 */
		public function clear_cache( $term_id, $term_tax_id = false ) {

			$taxonomy = get_term_by( 'term_taxonomy_id', $term_tax_id );

			if ( isset( $taxonomy->slug ) ) {	// Just in case.
				$mod = $this->get_mod( $term_id, $taxonomy->slug );
			} else {
				$mod = $this->get_mod( $term_id );
			}

			$col_meta_keys = WpssoWpMeta::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_key => $meta_key ) {
				self::delete_term_meta( $term_id, $meta_key );
			}

			$this->clear_mod_cache_types( $mod );
		}

		public function user_can_edit( $term_id, $term_tax_id = false ) {

			$user_can_edit = false;

			if ( ! $this->verify_submit_nonce() ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}
				return $user_can_edit;
			}

			if ( ! $user_can_edit = current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to save settings for term ID ' . $term_id );
				}
				if ( $this->p->notice->is_admin_pre_notices() ) {
					$this->p->notice->err( sprintf( __( 'Insufficient privileges to save settings for term ID %1$s.',
						'wpsso' ), $term_id ) );
				}
			}

			return $user_can_edit;
		}

		public static function get_term_meta( $term_id, $key_name, $single = false ) {

			$term_meta = false === $single ? array() : '';

			if ( self::use_meta_table( $term_id ) ) {

				$term_meta = get_term_meta( $term_id, $key_name, $single );	// Since WP v4.4.

				/**
				 * Fallback to checking for deprecated term meta in the options table.
				 */
				if ( ( $single && $term_meta === '' ) || ( ! $single && $term_meta === array() ) ) {

					/**
					 * If deprecated meta is found, update the meta table and delete the deprecated meta.
					 */
					if ( ( $opt_term_meta = get_option( $key_name . '_term_' . $term_id, null ) ) !== null ) {

						$updated = update_term_meta( $term_id, $key_name, $opt_term_meta );	// Since WP v4.4.

						if ( ! is_wp_error( $updated ) ) {
							delete_option( $key_name . '_term_' . $term_id );
							$term_meta = get_term_meta( $term_id, $key_name, $single );
						} else {
							$term_meta = false === $single ? array( $opt_term_meta ) : $opt_term_meta;
						}
					}
				}
			} elseif ( ( $opt_term_meta = get_option( $key_name . '_term_' . $term_id, null ) ) !== null ) {
				$term_meta = false === $single ? array( $opt_term_meta ) : $opt_term_meta;
			}

			return $term_meta;
		}

		public static function update_term_meta( $term_id, $key_name, $opts ) {
			if ( self::use_meta_table( $term_id ) ) {
				return update_term_meta( $term_id, $key_name, $opts );	// Since WP v4.4.
			} else {
				return update_option( $key_name . '_term_' . $term_id, $opts );
			}
		}

		public static function delete_term_meta( $term_id, $key_name ) {
			if ( self::use_meta_table( $term_id ) ) {
				return delete_term_meta( $term_id, $key_name );	// Since WP v4.4.
			} else {
				return delete_option( $key_name . '_term_' . $term_id );
			}
		}

		public static function use_meta_table( $term_id = false ) {
			static $local_cache = null;
			if ( null === $local_cache )	{	// optimize and check only once
				if ( function_exists( 'get_term_meta' ) && get_option( 'db_version' ) >= 34370 ) {
					if ( false === $term_id || ! wp_term_is_shared( $term_id ) ) {
						$local_cache = true;
					} else {
						$local_cache = false;
					}
				} else {
					$local_cache = false;
				}
			}
			return $local_cache;
		}
	}
}

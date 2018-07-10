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

	class WpssoTerm extends WpssoMeta {

		protected $query_term_id = 0;
		protected $query_tax_slug = '';
		protected $query_tax_obj = false;

		public function __construct() {
		}

		protected function add_actions() {

			if ( is_admin() ) {

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

				add_filter( 'manage_edit-'.$this->query_tax_slug.'_columns',
					array( $this, 'add_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );

				/**
				 * Enable orderby meta_key only if we have a meta table.
				 */
				if ( self::use_meta_table() ) {
					add_filter( 'manage_edit-'.$this->query_tax_slug.'_sortable_columns', 
						array( $this, 'add_sortable_columns' ), 10, 1 );
				}

				add_filter( 'manage_'.$this->query_tax_slug.'_custom_column',
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
					$this->p->debug->log( 'tax_slug / term_id = '.$this->query_tax_slug.' / '.$this->query_term_id );
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
					 * Sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties.
					 * load_meta_page() priorities: 100 post, 200 user, 300 term
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 300, 1 );
					add_action( $this->query_tax_slug.'_edit_form', array( $this, 'show_metaboxes' ), 100, 1 );
				}

				add_action( 'created_'.$this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'created_'.$this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );

				add_action( 'edited_'.$this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'edited_'.$this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );

				add_action( 'delete_'.$this->query_tax_slug, array( $this, 'delete_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'delete_'.$this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
			}
		}

		public function get_mod( $mod_id, $tax_slug = '' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = WpssoMeta::$mod_defaults;

			$mod['id']   = (int) $mod_id;
			$mod['name'] = 'term';
			$mod['obj']  =& $this;

			/**
			 * Term
			 */
			$mod['is_term']  = true;
			$mod['tax_slug'] = SucomUtil::get_term_object( $mod['id'], (string) $tax_slug, 'taxonomy' );

			return apply_filters( $this->p->lca.'_get_term_mod', $mod, $mod_id, $tax_slug );
		}

		public static function get_public_term_ids( $tax_name = false ) {

			$term_args = array( 'fields' => 'ids' );
			$term_oper = 'and';
			$term_ids  = array();

			foreach ( self::get_public_tax_names( $tax_name ) as $tax_name ) {
				foreach ( get_terms( $tax_name, $term_args, $term_oper ) as $term_val ) {
					$term_ids[] = $term_val;
				}
			}

			rsort( $term_ids );	// newest id first

			return $term_ids;
		}

		public static function get_public_tax_names( $tax_name = false ) {

			$obj_filter = array( 'public' => 1, 'show_ui' => 1 );

			if ( $tax_name !== false ) {
				$tax_filter['name'] = $tax_name;
			}

			$tax_names = array();

			foreach ( get_taxonomies( $obj_filter, 'names' ) as $tax_name ) {
				$tax_names[] = $tax_name;
			}

			return $tax_names;
		}

		public function get_posts( array $mod, $posts_per_page = false, $paged = false, array $get_posts_args = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( false === $posts_per_page ) {
				$posts_per_page = apply_filters( $this->p->lca.'_posts_per_page', get_option( 'posts_per_page' ), $mod );
			}

			if ( false === $paged ) {
				$paged = get_query_var( 'paged' );
			}

			if ( ! $paged > 1 ) {
				$paged = 1;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'calling get_posts() for '.$mod['name'].' id '.$mod['id'].
					' in taxonomy '.$mod['tax_slug'].' (posts_per_page is '.$posts_per_page.')' );
			}

			$get_posts_args = array_merge( array(
				'has_password'   => false,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'paged'          => $paged,
				'post_status'    => 'publish',
				'post_type'      => 'any',		// Post, page, or custom post type.
				'posts_per_page' => $posts_per_page,
				'tax_query'      => array(
				        array(
						'taxonomy'         => $mod['tax_slug'],
						'field'            => 'term_id',
						'terms'            => $mod['id'],
						'include_children' => false
					)
				),
			), $get_posts_args );

			$max_time   = SucomUtil::get_const( 'WPSSO_GET_POSTS_MAX_TIME', 0.10 );
			$start_time = microtime( true );
			$term_posts = get_posts( $get_posts_args );
			$total_time = microtime( true ) - $start_time;

			if ( $max_time > 0 && $total_time > $max_time ) {

				$info = $this->p->cf['plugin'][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow query detected - WordPress get_posts() took %1$0.3f secs'.
						' to get posts for term ID %2$d in taxonomy %3$s', $total_time, $mod['id'], $mod['tax_slug'] ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $max_time );

				// translators: %1$0.3f is a number of seconds, %2$d is an ID number, %3$s is a taxonomy name, %4$s is a recommended max
				$error_msg = sprintf( __( 'Slow query detected - WordPress get_posts() took %1$0.3f secs to get posts for term ID %2$d in taxonomy %3$s (%4$s).',
					'wpsso' ), $total_time, $mod['id'], $mod['tax_slug'], $rec_max_msg );

				/**
				 * Show an admin warning notice, if notices not already shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {
					$this->p->notice->warn( $error_msg );
				}

				// translators: %s is the short plugin name
				$error_pre = sprintf( __( '%s warning:', 'wpsso' ), $info['short'] );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( count( $term_posts ).' post objects returned in '.sprintf( '%0.3f secs', $total_time ) );
			}

			return $term_posts;
		}

		public function add_column_headings( $columns ) {
			return $this->add_mod_column_headings( $columns, 'term' );
		}

		public function get_column_content( $value, $column_name, $term_id ) {

			if ( ! empty( $term_id ) && strpos( $column_name, $this->p->lca.'_' ) === 0 ) {	// just in case

				$col_idx = str_replace( $this->p->lca.'_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {
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

		public function update_sortable_meta( $term_id, $col_idx, $content ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $term_id ) ) {	// just in case
				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// just in case
						self::update_term_meta( $term_id, $col_info['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $term_id, $meta_key, $single ) {

			static $do_once = array();

			if ( strpos( $meta_key, '_'.$this->p->lca.'_head_info_' ) !== 0 ) {	// example: _wpsso_head_info_og_img_thumb
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
		 * Sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties.
		 */
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			/**
			 * All meta modules set this property, so use it to optimize code execution.
			 */
			if ( WpssoMeta::$head_meta_tags !== false || ! isset( $screen->id ) ) {
				return;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'screen id: '.$screen->id );
			}

			switch ( $screen->id ) {
				case 'edit-'.$this->query_tax_slug:
					break;
				default:
					return;
					break;
			}

			$mod = $this->get_mod( $this->query_term_id, $this->query_tax_slug );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = '.get_option( 'home' ) );
				$this->p->debug->log( 'locale default = '.SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = '.SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = '.SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			WpssoMeta::$head_meta_tags = array();

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_term' ] ) ? false : true;
			$add_metabox = apply_filters( $this->p->lca.'_add_metabox_term', $add_metabox, $this->query_term_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for term ID '.$this->query_term_id.' is '.
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {

				do_action( $this->p->lca.'_admin_term_head', $mod, $screen->id );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'setting head_meta_info static property' );
				}

				/**
				 * $read_cache is false to generate notices etc.
				 */
				WpssoMeta::$head_meta_tags = $this->p->head->get_head_array( false, $mod, false );
				WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoMeta::$head_meta_tags );

				/**
				 * Check for missing open graph image and description values.
				 */
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

			$action_query = $this->p->lca.'-action';

			if ( ! empty( $_GET[$action_query] ) ) {

				$action_name = SucomUtil::sanitize_hookname( $_GET[$action_query] );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'found action query: '.$action_name );
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
							do_action( $this->p->lca.'_load_meta_page_term_'.$action_name, $this->query_term_id );
							break;
					}
				}
			}
		}

		public function add_meta_boxes() {

			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to add metabox for term '.$this->query_term_id );
				}
				return;
			}

			$metabox_id      = $this->p->cf['meta']['id'];
			$metabox_title   = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
			$metabox_screen  = $this->p->lca . '-term';
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$add_metabox     = empty( $this->p->options[ 'plugin_add_to_term' ] ) ? false : true;
			$add_metabox     = apply_filters( $this->p->lca.'_add_metabox_term', $add_metabox, $this->query_term_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for term ID '.$this->query_term_id.' is '.
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {
				add_meta_box( $this->p->lca.'_'.$metabox_id, $metabox_title,
					array( $this, 'show_metabox_custom_meta' ), $metabox_screen,
						$metabox_context, $metabox_prio );
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

			echo "\n" . '<!-- '.$this->p->lca.' term metabox section begin -->' . "\n";
			echo '<h3 id="'.$this->p->lca.'-metaboxes">'.WpssoAdmin::$pkg[$this->p->lca]['short'].'</h3>' . "\n";
			echo '<div id="poststuff">' . "\n";

			do_meta_boxes( $metabox_screen, 'normal', $term_obj );

			echo "\n" . '</div><!-- .poststuff -->' . "\n";
			echo '<!-- '.$this->p->lca.' term metabox section end -->' . "\n";
		}

		public function show_metabox_custom_meta( $term_obj ) {
			echo $this->get_metabox_custom_meta( $term_obj );
		}

		public function get_metabox_custom_meta( $term_obj ) {

			$metabox_id = $this->p->cf['meta']['id'];
			$mod        = $this->get_mod( $term_obj->term_id, $this->query_tax_slug );
			$tabs       = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $term_obj->term_id );
			$def_opts   = $this->get_defaults( $term_obj->term_id );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id.' table rows' );	// start timer
			}

			$table_rows = array();

			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox_id, $key, WpssoMeta::$head_meta_info, $mod ),
					apply_filters( $this->p->lca.'_'.$mod['name'].'_'.$key.'_rows', array(), $this->form, WpssoMeta::$head_meta_info, $mod ) );
			}

			$metabox_html = $this->p->util->get_metabox_tabbed( $metabox_id, $tabs, $table_rows );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id.' table rows' );	// end timer
			}

			return "\n" . '<div id="' . $this->p->lca . '_metabox_' . $metabox_id . '">' . $metabox_html . '</div>' . "\n";
		}

		public function clear_cache( $term_id, $term_tax_id = false ) {

			$tax = get_term_by( 'term_taxonomy_id', $term_tax_id );
			$mod = $this->get_mod( $term_id, $tax->slug );
			$col_meta_keys = WpssoMeta::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_idx => $meta_key ) {
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
					$this->p->debug->log( 'insufficient privileges to save settings for term ID '.$term_id );
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
				$term_meta = get_term_meta( $term_id, $key_name, $single );	// since wp v4.4

				/**
				 * Fallback to checking for deprecated term meta in the options table.
				 */
				if ( ( $single && $term_meta === '' ) || ( ! $single && $term_meta === array() ) ) {

					/**
					 * If deprecated meta is found, update the meta table and delete the deprecated meta.
					 */
					if ( ( $opt_term_meta = get_option( $key_name.'_term_'.$term_id, null ) ) !== null ) {

						$updated = update_term_meta( $term_id, $key_name, $opt_term_meta );	// since wpv4.4

						if ( ! is_wp_error( $updated ) ) {
							delete_option( $key_name.'_term_'.$term_id );
							$term_meta = get_term_meta( $term_id, $key_name, $single );
						} else {
							$term_meta = false === $single ? array( $opt_term_meta ) : $opt_term_meta;
						}
					}
				}
			} elseif ( ( $opt_term_meta = get_option( $key_name.'_term_'.$term_id, null ) ) !== null ) {
				$term_meta = false === $single ? array( $opt_term_meta ) : $opt_term_meta;
			}

			return $term_meta;
		}

		public static function update_term_meta( $term_id, $key_name, $opts ) {
			if ( self::use_meta_table( $term_id ) ) {
				return update_term_meta( $term_id, $key_name, $opts );	// since wpv4.4
			} else {
				return update_option( $key_name.'_term_'.$term_id, $opts );
			}
		}

		public static function delete_term_meta( $term_id, $key_name ) {
			if ( self::use_meta_table( $term_id ) ) {
				return delete_term_meta( $term_id, $key_name );	// since wp v4.4
			} else {
				return delete_option( $key_name.'_term_'.$term_id );
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

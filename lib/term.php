<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

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
				if ( ( $this->query_tax_slug = SucomUtil::get_request_value( 'taxonomy' ) ) === '' )
					return;

				$this->query_tax_obj = get_taxonomy( $this->query_tax_slug );
				if ( ! $this->query_tax_obj->public )
					return;

				add_filter( 'manage_edit-'.$this->query_tax_slug.'_columns', 
					array( &$this, 'add_column_headings' ), 10, 1 );

				// enable orderby meta_key only if we have a meta table
				if ( self::has_meta_table() ) {
					add_filter( 'manage_edit-'.$this->query_tax_slug.'_sortable_columns', 
						array( &$this, 'add_sortable_columns' ), 10, 1 );
				}

				add_filter( 'manage_'.$this->query_tax_slug.'_custom_column', 
					array( &$this, 'get_column_content' ), 10, 3 );

				/*
				 * The 'parse_query' action is hooked ONCE in the WpssoPost class
				 * to set the column orderby for post, term, and user edit tables.
				 *
				 * add_action( 'parse_query', array( &$this, 'set_column_orderby' ), 10, 1 );
				 */

				$this->p->util->add_plugin_filters( $this, array( 
					'schema_id_term_column_content' => 3,
					'og_img_term_column_content' => 3,
					'og_desc_term_column_content' => 3,
				) );

				if ( ( $this->query_term_id = SucomUtil::get_request_value( 'tag_ID' ) ) === '' )
					return;

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'tax_slug / term_id = '.
						$this->query_tax_slug.' / '.$this->query_term_id );

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
					add_action( 'admin_init', array( &$this, 'add_metaboxes' ) );
					// load_meta_page() priorities: 100 post, 200 user, 300 term
					// sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties
					add_action( 'current_screen', array( &$this, 'load_meta_page' ), 300, 1 );
					add_action( $this->query_tax_slug.'_edit_form', array( &$this, 'show_metaboxes' ), 100, 1 );
				}

				add_action( 'created_'.$this->query_tax_slug, array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'created_'.$this->query_tax_slug, array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
				add_action( 'edited_'.$this->query_tax_slug, array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'edited_'.$this->query_tax_slug, array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
				add_action( 'delete_'.$this->query_tax_slug, array( &$this, 'delete_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'delete_'.$this->query_tax_slug, array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
			}
		}

		public function get_mod( $mod_id, $tax_slug = '' ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$mod = WpssoMeta::$mod_array;
			$mod['id'] = (int) $mod_id;
			$mod['name'] = 'term';
			$mod['obj'] =& $this;
			/*
			 * Term
			 */
			$mod['is_term'] = true;
			$mod['tax_slug'] = SucomUtil::get_term_object( $mod['id'], (string) $tax_slug, 'taxonomy' );

			return apply_filters( $this->p->cf['lca'].'_get_term_mod', $mod, $mod_id, $tax_slug );
		}

		public function get_posts( array $mod, $posts_per_page = false, $paged = false ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];

			if ( $posts_per_page === false )
				$posts_per_page = apply_filters( $lca.'_posts_per_page', 
					get_option( 'posts_per_page' ), $mod );

			if ( $paged === false )
				$paged = get_query_var( 'paged' );

			if ( ! $paged > 1 )
				$paged = 1;

			return get_posts( array(
				'posts_per_page' => $posts_per_page,
				'paged' => $paged,
				'post_status' => 'publish',
				'has_password' => false,	// since wp 3.9
				'tax_query' => array(
				        array(
						'taxonomy' => $mod['tax_slug'],
						'field' => 'term_id',
						'terms' => $mod['id'],
						'include_children' => false
					)
				)
			) );
		}

		public function add_column_headings( $columns ) { 
			return $this->add_mod_column_headings( $columns, 'term' );
		}

		public function get_column_content( $value, $column_name, $term_id ) {
			$lca = $this->p->cf['lca'];
			$mod = $this->get_mod( $term_id );
			$content = $this->get_mod_column_content( $value, $column_name, $mod );

			// save sortable column values as post meta
			if ( strpos( $column_name, $lca.'_' ) === 0 ) {
				$column_key = str_replace( $lca.'_', '', $column_name );
				$this->update_sortable_meta( $mod, $column_key, $content );
			}

			return $content;
		}

		public function update_sortable_meta( $mod, $column_key, $content ) { 
			// update orderby meta_key only if we have a meta table
			if ( ! self::has_meta_table() )
				return;

			if ( ! empty( $mod['id'] ) ) {
				if ( ( $sort_info = $this->get_sortable_columns( $column_key ) ) !== null ) {
					if ( isset( $sort_info['meta_key'] ) ) {	// just in case
						if ( self::get_term_meta( $mod['id'], $sort_info['meta_key'], true ) !== $content ) {
							self::update_term_meta( $mod['id'], $sort_info['meta_key'], $content );
						}
					}
				}
			}
		}

		public function filter_schema_id_term_column_content( $value, $column_name, $mod ) {
			if ( ! empty( $value ) )
				return $value;
			return $this->p->schema->get_mod_schema_type( $mod, true );	// example: article.tech
		}

		public function filter_og_img_term_column_content( $value, $column_name, $mod ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( ! empty( $value ) )
				return $value;

			// use the open graph image dimensions to reject images that are too small
			$size_name = $this->p->cf['lca'].'-opengraph';
			$check_dupes = false;	// using first image we find, so dupe checking is useless
			$force_regen = false;
			$md_pre = 'og';
			$og_image = array();

			if ( empty( $og_image ) )
				$og_image = $this->get_og_video_preview_image( $mod, $check_dupes, $md_pre );

			// get_og_images() also provides filter hooks for additional image ids and urls
			if ( empty( $og_image ) )
				$og_image = $this->get_og_image( 1, $size_name, $mod['id'], $check_dupes, $force_regen, $md_pre );

			if ( empty( $og_image ) )
				$og_image = $this->p->media->get_default_image( 1, $size_name, $check_dupes, $force_regen );

			if ( ! empty( $og_image ) && is_array( $og_image ) ) {
				$image = reset( $og_image );
				$value = $this->get_og_img_column_html( $image );
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'no image found for column value' );

			return $value;
		}

		public function filter_og_desc_term_column_content( $desc, $column_name, $mod ) {
			if ( ! empty( $desc ) )
				return $desc;

			$term_obj = get_term_by( 'id', $mod['id'], $mod['tax_slug'], OBJECT, 'raw' );
			if ( empty( $term_obj->term_id ) )
				return $desc;

			$desc = $this->get_options( $mod['id'], 'og_desc' );

			if ( $this->p->debug->enabled ) {
				if ( empty( $desc ) )
					$this->p->debug->log( 'no custom description found' );
				else $this->p->debug->log( 'custom description = "'.$desc.'"' );
			}

			if ( empty( $desc ) ) {
				if ( is_tag( $mod['id'] ) ) {
					if ( ! $desc = tag_description( $mod['id'] ) )
						$desc = sprintf( 'Tagged with %s', $term_obj->name );

				} elseif ( is_category( $mod['id'] ) ) { 
					if ( ! $desc = category_description( $mod['id'] ) )
						$desc = sprintf( '%s Category', $term_obj->name ); 

				} else {
					if ( ! empty( $term_obj->description ) )
						$desc = $term_obj->description;
					elseif ( ! empty( $term_obj->name ) )
						$desc = $term_obj->name.' Archives';
				}
			}

			return $desc;
		}

		// hooked into the current_screen action
		// sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties
		public function load_meta_page( $screen = false ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			// all meta modules set this property, so use it to optimize code execution
			if ( WpssoMeta::$head_meta_tags !== false || ! isset( $screen->id ) )
				return;

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'screen id: '.$screen->id );

			switch ( $screen->id ) {
				case 'edit-'.$this->query_tax_slug:
					break;
				default:
					return;
					break;
			}

			$lca = $this->p->cf['lca'];
			$mod = $this->get_mod( $this->query_term_id, $this->query_tax_slug );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = '.get_option( 'home' ) );
				$this->p->debug->log( 'locale default = '.SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = '.SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = '.SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_term' ] ) ? false : true;
			if ( apply_filters( $lca.'_add_metabox_term', $add_metabox, $this->query_term_id ) ) {

				do_action( $lca.'_admin_term_head', $mod, $screen->id );

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'setting head_meta_info static property' );

				// $use_post = false, $read_cache = false to generate notices etc.
				WpssoMeta::$head_meta_tags = $this->p->head->get_head_array( false, $mod, false );
				WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( WpssoMeta::$head_meta_tags );

				// save the schema id for later sorting in the edit table
				if ( ! empty( WpssoMeta::$head_meta_info['schema:type:id'] ) )
					$this->update_sortable_meta( $mod, 'schema_id', WpssoMeta::$head_meta_info['schema:type:id'] );

				// check for missing open graph image and issue warning
				if ( empty( WpssoMeta::$head_meta_info['og:image'] ) )
					$this->p->notice->err( $this->p->msgs->get( 'notice-missing-og-image' ) );

				if ( empty( WpssoMeta::$head_meta_info['og:description'] ) )
					$this->p->notice->err( $this->p->msgs->get( 'notice-missing-og-description' ) );
			}

			$action_query = $lca.'-action';
			if ( ! empty( $_GET[$action_query] ) ) {
				$action_name = SucomUtil::sanitize_hookname( $_GET[$action_query] );
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'found action query: '.$action_name );
				if ( empty( $_GET[ WPSSO_NONCE ] ) ) {	// WPSSO_NONCE is an md5() string
					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'nonce token query field missing' );
				} elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE ], WpssoAdmin::get_nonce() ) ) {
					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".',
						'wpsso' ), 'term', $action_name ) );
				} else {
					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE ) );
					switch ( $action_name ) {
						default: 
							do_action( $lca.'_load_meta_page_term_'.$action_name, $this->query_term_id );
							break;
					}
				}
			}
		}

		public function add_metaboxes() {

			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'insufficient privileges to add metabox for term '.$this->query_term_id );
				return;
			}

			$lca = $this->p->cf['lca'];
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_term' ] ) ? false : true;
			if ( apply_filters( $this->p->cf['lca'].'_add_metabox_term', $add_metabox, $this->query_term_id ) ) {
				add_meta_box( $lca.'_social_settings', _x( 'Social Settings', 'metabox title', 'wpsso' ),
					array( &$this, 'show_metabox_social_settings' ), $lca.'-term', 'normal', 'low' );
			}
		}

		public function show_metaboxes( $term ) {
			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) )
				return;
			$lca = $this->p->cf['lca'];
			echo "\n".'<!-- '.$lca.' term metabox section begin -->'."\n";
			echo '<h3 id="'.$lca.'-metaboxes">'.WpssoAdmin::$pkg[$lca]['short'].'</h3>'."\n";
			echo '<div id="poststuff">'."\n";
			do_meta_boxes( $lca.'-term', 'normal', $term );
			echo "\n".'</div><!-- .poststuff -->'."\n";
			echo '<!-- '.$lca.' term metabox section end -->'."\n";
		}

		public function show_metabox_social_settings( $term_obj ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$lca = $this->p->cf['lca'];
			$metabox = 'social_settings';
			$mod = $this->get_mod( $term_obj->term_id, $this->query_tax_slug );
			$tabs = $this->get_social_tabs( $metabox, $mod );
			$opts = $this->get_options( $term_obj->term_id );
			$def_opts = $this->get_defaults( $term_obj->term_id );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts );
			wp_nonce_field( WpssoAdmin::get_nonce(), WPSSO_NONCE );

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( $metabox.' table rows' );	// start timer

			$table_rows = array();
			foreach ( $tabs as $key => $title ) {
				$table_rows[$key] = array_merge( $this->get_table_rows( $metabox, $key, WpssoMeta::$head_meta_info, $mod ), 
					apply_filters( $lca.'_'.$mod['name'].'_'.$key.'_rows', array(), $this->form, WpssoMeta::$head_meta_info, $mod ) );
			}
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( $metabox.' table rows' );	// end timer
		}

		public function clear_cache( $term_id, $term_tax_id = false ) {
			$lca = $this->p->cf['lca'];
			$tax = get_term_by( 'term_taxonomy_id', $term_tax_id );
			$mod = $this->get_mod( $term_id, $tax->slug );
			$sharing_url = $this->p->util->get_sharing_url( $mod );
			$cache_salt = SucomUtil::get_mod_salt( $mod, $sharing_url );

			$transients = array(
				'WpssoHead::get_head_array' => array( $cache_salt ),
				'WpssoMeta::get_mod_column_content' => array( $cache_salt ),
			);
			$transients = apply_filters( $lca.'_term_cache_transients', $transients, $mod, $sharing_url );

			$deleted = $this->p->util->clear_cache_objects( $transients );
			if ( ! empty( $this->p->options['plugin_cache_info'] ) && $deleted > 0 )
				$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.',
					true, __FUNCTION__.'_items_removed', true );

			return $term_id;
		}

		public static function get_public_terms( $tax_name = false, $term_fields = 'ids' ) {
			$ret = array();
			$tax_filter = array( 'public' => 1, 'show_ui' => 1 );
			if ( $tax_name !== false )
				$tax_filter['name'] = $tax_name;
			$term_args = array( 'fields' => $term_fields );
			$term_oper = 'and';
			foreach ( get_taxonomies( $tax_filter, 'names' ) as $tax_name ) {
				foreach ( get_terms( $tax_name, $term_args, $term_oper ) as $term_val ) {
					$ret[] = $term_val;
				}
			}
			sort( $ret );
			return $ret;
		}

		public static function get_term_meta( $term_id, $key_name, $single = false ) {
			$term_meta = $single === false ? array() : '';
			if ( self::has_meta_table() && ! wp_term_is_shared( $term_id ) ) {
				$term_meta = get_term_meta( $term_id, $key_name, $single );
				/*
				 * If the $term_id is invalid, false is returned.
				 * If the meta value isn't set, an empty string or array is returned.
				 */
				if ( ( $single && $term_meta === '' ) || 
					( ! $single && $term_meta === array() ) ) {
					$term_meta = get_option( $key_name.'_term_'.$term_id, false );
					if ( is_array( $term_meta ) ) {
						$updated = update_term_meta( $term_id, $key_name, $term_meta );
						if ( ! is_wp_error( $updated ) )
							delete_option( $key_name.'_term_'.$term_id );
					}
				}
			} else {
				/*
				 * Re-create the return value of get_term_meta().
				 *
				 * If the meta value does not exist and $single is true the function will return an empty string.
				 * If $single is false an empty array is returned.
				 */
				$term_meta = get_option( $key_name.'_term_'.$term_id, 
					( $single === false ? array() : '' ) );
			}
			return $term_meta;
		}

		public static function update_term_meta( $term_id, $key_name, $opts ) {
			if ( self::has_meta_table() && ! wp_term_is_shared( $term_id ) )
				return update_term_meta( $term_id, $key_name, $opts );
			else return update_option( $key_name.'_term_'.$term_id, $opts );
		}

		public static function delete_term_meta( $term_id, $key_name ) {
			if ( self::has_meta_table() && ! wp_term_is_shared( $term_id ) )
				return delete_term_meta( $term_id, $key_name );
			else return delete_option( $key_name.'_term_'.$term_id );
		}

		public static function has_meta_table() {
			static $has_meta_table = null;
			if ( $has_meta_table === null )	// optimize and check only once
				$has_meta_table = get_option( 'db_version' ) >= 34370 ?
					true : false;
			return $has_meta_table;
		}
	}
}

?>

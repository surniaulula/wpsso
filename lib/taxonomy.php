<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoTaxonomy' ) ) {

	/*
	 * This class is extended by gpl/util/taxonomy.php or pro/util/taxonomy.php
	 * and the class object is created as $this->p->mods['util']['taxonomy'].
	 */
	class WpssoTaxonomy extends WpssoMeta {

		protected $tax_slug = false;
		protected $tax_obj = false;
		protected $term_id = false;

		protected function add_actions() {
			if ( is_admin() ) {
				/**
				 * Hook a minimum number of admin actions to maximize performance.
				 * The taxonomy and tag_ID arguments are always present when we're
				 * editing a category and/or tag page, so return immediately if
				 * they're not present.
				 */
				if ( ( $this->tax_slug = SucomUtil::get_req_val( 'taxonomy' ) ) === '' )
					return;

				$this->tax_obj = get_taxonomy( $this->tax_slug );
				if ( ! $this->tax_obj->public )
					return;

				add_filter( 'manage_edit-'.$this->tax_slug.'_columns', array( $this, 'add_column_headings' ), 10, 1 );
				add_filter( 'manage_'.$this->tax_slug.'_custom_column', array( $this, 'get_taxonomy_column_content' ), 10, 3 );

				$this->p->util->add_plugin_filters( $this, array( 
					'og_image_taxonomy_column_content' => 4,
					'og_desc_taxonomy_column_content' => 4,
				) );

				if ( ( $this->term_id = SucomUtil::get_req_val( 'tag_ID' ) ) === '' )
					return;

				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'tax_slug/term_id values: '.
						$this->tax_slug.'/'.$this->term_id );

				/**
				 * Available term and taxonomy actions:
				 *
				 * do_action( "create_term",       $term_id, $tt_id, $taxonomy );
				 * do_action( "created_term",      $term_id, $tt_id, $taxonomy );
				 * do_action( "edited_term",       $term_id, $tt_id, $taxonomy );
				 * do_action( 'delete_term',       $term_id, $tt_id, $taxonomy, $deleted_term );
				 * do_action( "create_$taxonomy",  $term_id, $tt_id );
				 * do_action( "created_$taxonomy", $term_id, $tt_id );
				 * do_action( "edited_$taxonomy",  $term_id, $tt_id );
				 * do_action( "delete_$taxonomy",  $term_id, $tt_id, $deleted_term );
				 */

				add_action( 'admin_init', array( &$this, 'add_metaboxes' ) );
				add_action( 'admin_head', array( &$this, 'set_head_meta_tags' ) );
				add_action( $this->tax_slug.'_edit_form', array( &$this, 'show_metaboxes' ), 100, 1 );
				add_action( 'created_'.$this->tax_slug, array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'created_'.$this->tax_slug, array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
				add_action( 'edited_'.$this->tax_slug, array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'edited_'.$this->tax_slug, array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
				add_action( 'delete_'.$this->tax_slug, array( &$this, 'delete_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'delete_'.$this->tax_slug, array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );
			}
		}

		public function get_term_images( $num = 0, $size_name = 'thumbnail', $term_id,
			$check_dupes = true, $force_regen = false, $md_pre = 'og', $mt_pre = 'og' ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->args( array( 
					'num' => $num,
					'size_name' => $size_name,
					'term_id' => $term_id,
					'check_dupes' => $check_dupes,
					'force_regen' => $force_regen,
					'md_pre' => $md_pre,
					'mt_pre' => $mt_pre,
				) );
			}

			$meta_ret = array();
			$meta_image = SucomUtil::meta_image_tags( $mt_pre );

			if ( empty( $term_id ) )
				return $meta_ret;

			foreach ( apply_filters( $this->p->cf['lca'].'_term_image_ids', array(), $term_id ) as $pid ) {
				if ( $pid > 0 ) {
					list( 
						$meta_image[$mt_pre.':image'],
						$meta_image[$mt_pre.':image:width'],
						$meta_image[$mt_pre.':image:height'],
						$meta_image[$mt_pre.':image:cropped'],
						$meta_image[$mt_pre.':image:id']
					) = $this->p->media->get_attachment_image_src( $pid, $size_name, $check_dupes, $force_regen );

					if ( ! empty( $meta_image[$mt_pre.':image'] ) &&
						$this->p->util->push_max( $meta_ret, $meta_image, $num ) )
							return $meta_ret;
				}
			}
			return $meta_ret;
		}

		public function get_taxonomy_column_content( $value, $column_name, $id ) {
			return $this->get_mod_column_content( $value, $column_name, $id, 'taxonomy' );
		}

		public function filter_og_image_taxonomy_column_content( $value, $column_name, $id, $mod ) {

			if ( ! empty( $value ) )
				return $value;

			// use the open graph image dimensions to reject images that are too small
			$size_name = $this->p->cf['lca'].'-opengraph';
			$check_dupes = false;	// using first image we find, so dupe checking is useless
			$force_regen = false;
			$md_pre = 'og';
			$og_image = array();

			if ( empty( $og_image ) )
				$og_image = $this->get_og_video_preview_image( $id, $mod, $check_dupes, $md_pre );

			if ( empty( $og_image ) )
				$og_image = $this->get_og_image( 1, $size_name, $id, $check_dupes, $force_regen, $md_pre );

			if ( empty( $og_image ) )
				$og_image = $this->get_term_images( 1, $size_name, $id, $check_dupes, $force_regen, $md_pre );

			if ( empty( $og_image ) )
				$og_image = $this->p->media->get_default_image( 1, $size_name, $check_dupes, $force_regen );

			if ( ! empty( $og_image ) && is_array( $og_image ) ) {
				$image = reset( $og_image );
				if ( ! empty( $image['og:image'] ) )
					$value = $this->get_og_image_column_html( $image );
			}

			return $value;
		}

		public function filter_og_desc_taxonomy_column_content( $value, $column_name, $id, $mod ) {
			if ( ! empty( $value ) )
				return $value;

			$term = get_term_by( 'id', $id, $this->tax_slug, OBJECT, 'raw' );
			if ( empty( $term->term_id ) )
				return $value;

			$value = $this->p->util->get_mod_options( 'taxonomy', $term->term_id, 'og_desc' );

			if ( ! empty( $term->description ) )
				$value = $term->description;

			if ( empty( $value ) && ! empty( $term->name ) ) {
				if ( strpos( $term->taxonomy, '_tag' ) !== false )
					$value = sprintf( 'Tagged with %s', $term->name );
				elseif ( $term->taxonomy === 'category' ||
					strpos( $term->taxonomy, '_cat' ) !== false )
						$value = sprintf( '%s Category', $term->name ); 
				else $value = $term->name.' Archives';
			}

			return $value;
		}

		// hooked into the admin_head action
		public function set_head_meta_tags() {

			if ( ! empty( $this->head_meta_tags ) )	// only set header tags once
				return;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$screen_id = SucomUtil::get_screen_id();
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'screen id = '.$screen_id );

			switch ( $screen_id ) {
				case 'edit-'.$this->tax_slug:
					$add_metabox = empty( $this->p->options[ 'plugin_add_to_taxonomy' ] ) ? false : true;
					if ( apply_filters( $this->p->cf['lca'].'_add_metabox_taxonomy', 
						$add_metabox, $this->term_id, $screen_id ) === true ) {

						do_action( $this->p->cf['lca'].'_admin_taxonomy_header', $this->term_id, $screen_id );

						// use_post is false since this isn't a post
						// read_cache is false to generate notices etc.
						$this->head_meta_tags = $this->p->head->get_header_array( false );
						$this->head_info = $this->p->head->extract_head_info( $this->head_meta_tags );

						if ( empty( $this->head_info['og:image'] ) )
							$this->p->notice->err( $this->p->msgs->get( 'info-missing-og-image' ) );
					}
					break;
			}
		}

		public function add_metaboxes() {
			if ( ! current_user_can( $this->tax_obj->cap->edit_terms ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'insufficient privileges to add metabox for taxonomy '.$this->tax_slug );
				return;
			}
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_taxonomy' ] ) ? false : true;
			if ( apply_filters( $this->p->cf['lca'].'_add_metabox_taxonomy', $add_metabox ) === true )
				add_meta_box( WPSSO_META_NAME, 'Social Settings', array( &$this, 'show_metabox_taxonomy' ), 
					'taxonomy', 'normal', 'high' );
		}

		public function show_metaboxes( $term ) {
			if ( ! current_user_can( $this->tax_obj->cap->edit_terms ) )
				return;
			echo '<div id="poststuff">';
			do_meta_boxes( 'taxonomy', 'normal', $term );
			echo '</div>';
		}

		public function show_metabox_taxonomy( $term ) {
			$opts = $this->get_options( $term->term_id );
			$def_opts = $this->get_defaults();
			$this->head_info['post_id'] = false;

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts );
			wp_nonce_field( $this->get_nonce(), WPSSO_NONCE );

			$metabox = 'taxonomy';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', $this->default_tabs );
			if ( empty( $this->p->is_avail['mt'] ) )
				unset( $tabs['tags'] );

			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key, $this->head_info ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, $this->head_info ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function clear_cache( $term_id, $term_tax_id = false ) {
			$post_id = 0;
			$lca = $this->p->cf['lca'];
			$lang = SucomUtil::get_locale();
			$sharing_url = $this->p->util->get_sharing_url( false );
			$transients = array(
				'WpssoHead::get_header_array' => array( 
					'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url,
					'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url.'_crawler:pinterest',
				),
				'WpssoMeta::get_mod_column_content' => array( 
					'lang:'.$lang.'_id:'.$term_id.'_mod:taxonomy_column:'.$lca.'_og_image',
				),
			);
			$transients = apply_filters( $this->p->cf['lca'].'_taxonomy_cache_transients', 
				$transients, $term_id, $lang, $sharing_url );

			$deleted = $this->p->util->clear_cache_objects( $transients );

			if ( ! empty( $this->p->options['plugin_cache_info'] ) && $deleted > 0 )
				$this->p->notice->inf( $deleted.' items removed from the WordPress object and transient caches.', true );

			return $term_id;
		}

		public static function get_public_terms( $tax_name = false, $fields = 'ids' ) {
			$ret = array();
			$tax_filter = array(
				'public' => 1,
			);
			if ( $tax_name !== false )
				$tax_filter['name'] = $tax_name;
			$term_args = array(
				'fields' => $fields,
			);
			$oper = 'and';
			foreach ( get_taxonomies( $tax_filter, 'names' ) as $tax_name ) {
				foreach ( get_terms( $tax_name, $term_args, $oper ) as $term_val ) {
					$ret[] = $term_val;
				}
			}
			sort( $ret );
			return $ret;
		}

		public static function get_term_meta( $term_id, $options_name, $single ) {
			$options_name .= '_term_'.$term_id;
			/**
			 * re-create the return value of get_post_meta() and get_user_meta():
			 *
			 * If the meta value does not exist and $single is true the function will return an empty string.
			 * If $single is false an empty array is returned.
			 */
			return get_option( $options_name, ( $single === false ? array() : '' ) );
		}

		public static function update_term_meta( $term_id, $options_name, $opts ) {
			$options_name .= '_term_'.$term_id;
			return update_option( $options_name, $opts );
		}

		public static function delete_term_meta( $term_id, $options_name ) {
			$options_name .= '_term_'.$term_id;
			return delete_option( $options_name );
		}
	}
}

?>

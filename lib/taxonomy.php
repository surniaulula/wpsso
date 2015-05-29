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
				if ( ( $this->tax_slug = SucomUtil::get_req_val( 'taxonomy' ) ) === '' ||
					( $this->term_id = SucomUtil::get_req_val( 'tag_ID' ) ) === '' )
						return;

				$this->tax_obj = get_taxonomy( $this->tax_slug );
				if ( ! $this->tax_obj->public )
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

		// hooked into the admin_head action
		public function set_head_meta_tags() {
			if ( ! empty( $this->head_meta_tags ) )	// only set header tags once
				return;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$screen = get_current_screen();
			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'screen id = '.$screen->id );

			switch ( $screen->id ) {
				case 'edit-'.$this->tax_slug:
					$add_metabox = empty( $this->p->options[ 'plugin_add_to_taxonomy' ] ) ? false : true;
					if ( apply_filters( $this->p->cf['lca'].'_add_metabox_taxonomy', 
						$add_metabox, $this->term_id, $screen->id ) === true ) {

						// set custom image dimensions for this term id
						$this->p->util->add_plugin_image_sizes( $this->term_id, array(), true, 'taxonomy' );

						do_action( $this->p->cf['lca'].'_admin_taxonomy_header', $this->term_id, $screen->id );

						// use_post is false since this isn't a post
						// read_cache is false to generate notices etc.
						$this->head_meta_tags = $this->p->head->get_header_array( false );
						$this->head_info = $this->p->head->extract_head_info( $this->head_meta_tags );

						if ( empty( $this->head_info['og_image']['og:image'] ) )
							$this->p->notice->err( 'A Facebook / Open Graph image meta tag for this webpage could not be generated. Facebook and other social websites require at least one image meta tag to render their shared content correctly.', true );
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
			$screen = get_current_screen();
			$this->head_info['ptn'] = ucfirst( $screen->id );
			$this->head_info['id'] = false;

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts );
			wp_nonce_field( $this->get_nonce(), WPSSO_NONCE );

			$metabox = 'taxonomy';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', $this->default_tabs );
			if ( empty( $this->p->is_avail['metatags'] ) )
				unset( $tabs['tags'] );

			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key, $this->head_info ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
						array(), $this->form, $this->head_info ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		public function clear_cache( $term_id, $term_tax_id = false ) {
			$lang = SucomUtil::get_locale();
			$post_id = 0;
			$sharing_url = $this->p->util->get_sharing_url( false );
			$transients = array(
				'WpssoHead::get_header_array' => array( 
					'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url,
					'lang:'.$lang.'_post:'.$post_id.'_url:'.$sharing_url.'_crawler:pinterest',
				),
			);
			$transients = apply_filters( $this->p->cf['lca'].'_taxonomy_cache_transients', 
				$transients, $post_id, $lang, $sharing_url );

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

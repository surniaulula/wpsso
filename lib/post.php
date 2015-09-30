<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2015 - Jean-Sebastien Morisset - http://surniaulula.com/
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoPost' ) ) {

	/*
	 * This class is extended by gpl/util/post.php or pro/util/post.php
	 * and the class object is created as $this->p->mods['util']['post'].
	 */
	class WpssoPost extends WpssoMeta {

		protected function add_actions() {
			if ( is_admin() ) {
				/**
				 * Hook a minimum number of admin actions to maximize performance.
				 * The post or post_ID arguments are always present when we're
				 * editing a post and/or page.
				 */
				if ( SucomUtil::is_post_page() ) {
				
					add_action( 'add_meta_boxes', array( &$this, 'add_metaboxes' ) );
					add_action( 'admin_head', array( &$this, 'set_head_meta_tags' ) );
					add_action( 'save_post', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
					add_action( 'save_post', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );
					add_action( 'edit_attachment', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
					add_action( 'edit_attachment', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				} else {

					// only check registered front-end post types (to avoid menu items, product variations, etc.)
					$post_types = $this->p->util->get_post_types( 'frontend', 'names' );

					if ( is_array( $post_types ) ) {
						foreach ( $post_types as $ptn ) {
							add_filter( 'manage_'.$ptn.'_posts_columns', 
								array( $this, 'add_column_headings' ), 10, 1 );
							add_action( 'manage_'.$ptn.'_posts_custom_column', 
								array( $this, 'show_post_column_content',), 10, 2 );
						}
					}

					$this->p->util->add_plugin_filters( $this, array( 
						'og_image_post_column_content' => 4,
						'og_desc_post_column_content' => 4,
					) );
				}
			}
		}

		public function show_post_column_content( $column_name, $id ) {
			echo $this->get_mod_column_content( '', $column_name, $id, 'post' );
		}

		public function filter_og_image_post_column_content( $value, $column_name, $id, $mod ) {
			if ( ! empty( $value ) )
				return $value;

			// use the open graph image dimensions to reject images that are too small
			$size_name = $this->p->cf['lca'].'-opengraph';
			$check_dupes = false;	// use first image we find, so dupe checking is useless
			$force_regen = false;
			$md_pre = 'og';
			$og_image = array();

			if ( empty( $og_image ) )
				$og_image = $this->get_og_video_preview_image( $id, $mod, $check_dupes, $md_pre );

			if ( empty( $og_image ) ) {
				$og_image = $this->p->og->get_all_images( 1, $size_name, $id, $check_dupes, $md_pre );
				if ( empty( $og_image ) )
					$og_image = $this->p->media->get_default_image( 1, $size_name, $check_dupes, $force_regen );
			}

			if ( ! empty( $og_image ) && is_array( $og_image ) ) {
				$image = reset( $og_image );
				if ( ! empty( $image['og:image'] ) )
					$value = $this->get_og_image_column_html( $image );
			}

			return $value;
		}

		public function filter_og_desc_post_column_content( $value, $column_name, $id, $mod ) {
			if ( ! empty( $value ) )
				return $value;

			return $this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', $id );
		}

		// hooked into the admin_head action
		public function set_head_meta_tags() {

			if ( ! empty( $this->head_meta_tags ) )	// only set header tags once
				return;

			$screen_id = SucomUtil::get_screen_id();
			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->util->log_is_functions();
				$this->p->debug->log( 'screen id = '.$screen_id );
			}

			// check for post/page/media editing lists
			if ( strpos( $screen_id, 'edit-' ) !== false ||
				$screen_id === 'upload' )
					return;

			if ( ( $obj = $this->p->util->get_post_object() ) === false ||
				empty( $obj->post_type ) )
					return;

			$post_id = empty( $obj->ID ) ? 0 : $obj->ID;
			if ( isset( $obj->post_status ) && 
				$obj->post_status !== 'auto-draft' ) {

				$post_type = get_post_type_object( $obj->post_type );
				$add_metabox = empty( $this->p->options[ 'plugin_add_to_'.$post_type->name ] ) ? false : true;
				if ( apply_filters( $this->p->cf['lca'].'_add_metabox_post', 
					$add_metabox, $post_id, $post_type->name ) === true ) {

					// hook used by woocommerce module to load front-end libraries and start a session
					do_action( $this->p->cf['lca'].'_admin_post_header', $post_id, $post_type->name );

					// read_cache is false to generate notices etc.
					$this->head_meta_tags = $this->p->head->get_header_array( $post_id, false );
					$this->head_info = $this->p->head->extract_head_info( $this->head_meta_tags );

					if ( $obj->post_status == 'publish' ) {
						if ( empty( $this->head_info['og:image'] ) )
							$this->p->notice->err( $this->p->msgs->get( 'info-missing-og-image' ) );
						// check for duplicates once the post has been published and we have a functioning permalink
						if ( ! empty( $this->p->options['plugin_check_head'] ) )
							$this->check_post_header( $post_id, $obj );
					}
				}
			}
		}

		public function check_post_header( $post_id = true, &$obj = false ) {

			if ( empty( $this->p->options['plugin_check_head'] ) )
				return $post_id;

			if ( ! is_object( $obj ) &&
				( $obj = $this->p->util->get_post_object( $post_id ) ) === false )
					return $post_id;

			// only check published posts, so we have a permalink to check
			if ( ! isset( $obj->post_status ) || 
				$obj->post_status !== 'publish' )
					return $post_id;

			// only check registered front-end post types (to avoid menu items, product variations, etc.)
			$post_types = $this->p->util->get_post_types( 'frontend', 'names' );
			if ( empty( $obj->post_type ) || 
				! in_array( $obj->post_type, $post_types ) )
					return $post_id;

			$permalink = get_permalink( $post_id );
			$permalink_no_meta = add_query_arg( array( 'WPSSO_META_TAGS_DISABLE' => 1 ), $permalink );
			$check_opts = apply_filters( $this->p->cf['lca'].'_check_head_meta_options',
				SucomUtil::preg_grep_keys( '/^add_/', $this->p->options, false, '' ), $post_id );

			// use the permalink and have get_head_meta() remove our own meta tags
			// to avoid issues with caching plugins that ignore query arguments
			if ( ( $metas = $this->p->util->get_head_meta( $permalink, 
				'/html/head/link|/html/head/meta', true ) ) !== false ) {

				foreach( array(
					'link' => array( 'rel' ),
					'meta' => array( 'name', 'itemprop', 'property' ),
				) as $tag => $types ) {
					if ( isset( $metas[$tag] ) ) {
						foreach( $metas[$tag] as $m ) {
							foreach( $types as $t ) {
								if ( isset( $m[$t] ) && $m[$t] !== 'generator' && 
									! empty( $check_opts[$tag.'_'.$t.'_'.$m[$t]] ) )
										$this->p->notice->err( 'Possible conflict detected &mdash; your theme or another plugin is adding a <code>'.$tag.' '.$t.'="'.$m[$t].'"</code> HTML tag to the head section of this webpage.', true );
							}
						}
					}
				}
			}
			return $post_id;
		}

		public function add_metaboxes() {
			if ( ( $obj = $this->p->util->get_post_object() ) === false ||
				empty( $obj->post_type ) )
					return;
			$post_id = empty( $obj->ID ) ? 0 : $obj->ID;
			$post_type = get_post_type_object( $obj->post_type );
			$user_can_edit = false;		// deny by default
			switch ( $post_type ) {
				case 'page' :
					if ( current_user_can( 'edit_page', $post_id ) )
						$user_can_edit = true;
					break;
				default :
					if ( current_user_can( 'edit_post', $post_id ) )
						$user_can_edit = true;
					break;
			}
			if ( $user_can_edit === false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'insufficient privileges to add metabox for '.$post_type.' ID '.$post_id );
				return;
			}
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_'.$post_type->name ] ) ? false : true;
			if ( apply_filters( $this->p->cf['lca'].'_add_metabox_post', $add_metabox, $post_id ) === true )
				add_meta_box( WPSSO_META_NAME, 'Social Settings', array( &$this, 'show_metabox_post' ),
					$post_type->name, 'advanced', 'high' );
		}

		public function show_metabox_post( $post ) {
			$opts = $this->get_options( $post->ID );	// sanitize when saving, not reading
			$def_opts = $this->get_defaults();
			$post_type = get_post_type_object( $post->post_type );	// since 3.0
			$this->head_info['ptn'] = ucfirst( $post_type->name );
			$this->head_info['post_id'] = $post->ID;

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts );
			wp_nonce_field( $this->get_nonce(), WPSSO_NONCE );

			$metabox = 'post';
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

		protected function get_rows( $metabox, $key, &$head_info ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'post-preview':
					if ( get_post_status( $head_info['post_id'] ) !== 'auto-draft' )
						$rows = $this->get_rows_social_preview( $this->form, $head_info );
					else $rows[] = '<td><p class="centered">Save a draft version or publish the '.
						$head_info['ptn'].' to display the open graph social preview.</p></td>';
					break;

				case 'post-tags':	
					if ( get_post_status( $head_info['post_id'] ) !== 'auto-draft' ) {
						$rows = $this->get_rows_head_tags( $this->head_meta_tags );
					} else $rows[] = '<td><p class="centered">Save a draft version or publish the '.
						$head_info['ptn'].' to display the header preview.</p></td>';
					break; 

				case 'post-validate':
					if ( get_post_status( $head_info['post_id'] ) === 'publish' ||
						get_post_type( $head_info['post_id'] ) === 'attachment' )
							$rows = $this->get_rows_validate( $this->form, $head_info );
					else $rows[] = '<td><p class="centered">The validation links will be available when the '.
						$head_info['ptn'].' is published with public visibility.</p></td>';
					break; 
			}
			return $rows;
		}

		public function clear_cache( $post_id, $rel_id = false ) {
			$this->p->util->clear_post_cache( $post_id );
			return $post_id;
		}

	}
}

?>

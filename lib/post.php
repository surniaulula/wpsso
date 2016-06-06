<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoPost' ) ) {

	/*
	 * This class is extended by gpl/util/post.php or pro/util/post.php
	 * and the class object is created as $this->p->m['util']['post'].
	 */
	class WpssoPost extends WpssoMeta {

		public function __construct() {
		}

		protected function add_actions() {

			// admin post or attachment editing page
			if ( is_admin() && SucomUtil::is_post_page() ) {

				add_action( 'add_meta_boxes', array( &$this, 'add_metaboxes' ) );
				// load_meta_page() priorities: 100 post, 200 user, 300 term
				add_action( 'current_screen', array( &$this, 'load_meta_page' ), 100, 1 );
				add_action( 'save_post', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'save_post', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );
				add_action( 'edit_attachment', array( &$this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_attachment', array( &$this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				if ( ! empty( $this->p->options['plugin_shortlink'] ) )
					add_action( 'get_shortlink', array( &$this, 'get_shortlink' ), 9000, 4 );
			}

			// add the columns when doing AJAX as well to allow Quick Edit to add the required columns
			if ( ( is_admin() || SucomUtil::get_const( 'DOING_AJAX' ) ) &&
				( ! empty( $this->p->options['plugin_og_img_col_post'] ) || 
					! empty( $this->p->options['plugin_og_desc_col_post'] ) ) ) {

				$post_type_names = $this->p->util->get_post_types( 'names' );
				if ( is_array( $post_type_names ) ) {
					foreach ( $post_type_names as $post_type ) {
						add_filter( 'manage_'.$post_type.'_posts_columns', 
							array( $this, 'add_column_headings' ), 10, 1 );
						add_action( 'manage_'.$post_type.'_posts_custom_column', 
							array( $this, 'show_column_content',), 10, 2 );
					}
				}
				$this->p->util->add_plugin_filters( $this, array( 
					'og_img_post_column_content' => 4,
					'og_desc_post_column_content' => 4,
				) );
			}
		}

		public function get_mod( $mod_id ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			$mod = WpssoMeta::$mod_array;
			$mod['id'] = (int) $mod_id;
			$mod['name'] = 'post';
			$mod['obj'] =& $this;
			/*
			 * Post
			 */
			$mod['is_post'] = true;
			$mod['is_home_page'] = SucomUtil::is_home_page( $mod_id );			// static home page (have post ID)
			$mod['is_home_index'] = is_home() && ! $mod['is_home_page'] ? true : false;	// blog index page (archive)
			$mod['is_home'] = $mod['is_home_page'] || $mod['is_home_index'] ? true : false;	// home page (any)
			$mod['post_type'] = get_post_type( $mod_id );					// post type name
			$mod['post_status'] = get_post_status( $mod_id );				// post status name
			$mod['post_author'] = (int) get_post_field( 'post_author', $mod_id );		// post author id

			// hooked by the 'coauthors' pro module
			return apply_filters( $this->p->cf['lca'].'_get_post_mod', $mod, $mod_id );
		}

		public function get_shortlink( $shortlink, $post_id, $context, $allow_slugs ) {
			if ( isset( $this->p->options['plugin_shortener'] ) &&
				$this->p->options['plugin_shortener'] !== 'none' ) {

					$post_type = get_post_type( $post_id );				// post type name
					$post_status = get_post_status( $post_id );			// post status name

					if ( empty( $post_type ) || empty( $post_status ) ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'exiting early: incomplete post object' );
						return $shortlink;
					} elseif ( $post_status === 'auto-draft' ) {
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'exiting early: post is an auto-draft' );
						return $shortlink;
					}

					$long_url = $this->p->util->get_sharing_url( $post_id );
					$short_url = apply_filters( $this->p->cf['lca'].'_shorten_url',
						$long_url, $this->p->options['plugin_shortener'] );
					if ( $long_url !== $short_url )	// just in case
						return $short_url;
			}
			return $shortlink;
		}

		public function add_column_headings( $columns ) { 
			return $this->add_mod_column_headings( $columns, 'post' );
		}

		public function show_column_content( $column_name, $post_id ) {
			$mod = $this->get_mod( $post_id );
			echo $this->get_mod_column_content( '', $column_name, $mod );
		}

		public function filter_og_img_post_column_content( $value, $column_name, $mod ) {
			if ( ! empty( $value ) )
				return $value;

			// use the open graph image dimensions to reject images that are too small
			$size_name = $this->p->cf['lca'].'-opengraph';
			$check_dupes = false;	// use first image we find, so dupe checking is useless
			$force_regen = false;
			$md_pre = 'og';
			$og_image = array();

			if ( empty( $og_image ) )
				$og_image = $this->get_og_video_preview_image( $mod, $check_dupes, $md_pre );

			if ( empty( $og_image ) )
				$og_image = $this->p->og->get_all_images( 1, $size_name, $mod, $check_dupes, $md_pre );

			if ( empty( $og_image ) )
				$og_image = $this->p->media->get_default_image( 1, $size_name, $check_dupes, $force_regen );

			if ( ! empty( $og_image ) && 
				is_array( $og_image ) ) {

				$image = reset( $og_image );
				if ( ! empty( $image['og:image'] ) )
					$value = $this->get_og_img_column_html( $image );
			}

			return $value;
		}

		public function filter_og_desc_post_column_content( $value, $column_name, $mod ) {
			if ( ! empty( $value ) )
				return $value;

			return $this->p->webpage->get_description( $this->p->options['og_desc_len'], '...', $mod );
		}

		// hooked into the current_screen action
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			// all meta modules set this property, so use it to optimize code execution
			if ( ! empty( WpssoMeta::$head_meta_tags ) 
				|| ! isset( $screen->id ) )
					return;

			if ( $this->p->debug->enabled )
				$this->p->debug->log( 'screen id: '.$screen->id );

			switch ( $screen->id ) {
				case 'upload':
				case ( strpos( $screen->id, 'edit-' ) === 0 ? true : false ):	// posts list table
					return;
					break;
			}

			$post_obj = SucomUtil::get_post_object();
			$post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;

			// make sure we have at least a post type and post status
			if ( $post_obj === false || 
				empty( $post_obj->post_type ) || 
					empty( $post_obj->post_status ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: incomplete post object' );
				return;
			}

			$lca = $this->p->cf['lca'];
			$mod = $this->get_mod( $post_id );
			if ( $this->p->debug->enabled )
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );

			if ( $post_obj->post_status !== 'auto-draft' ) {
				$add_metabox = empty( $this->p->options['plugin_add_to_'.$post_obj->post_type] ) ? false : true;
				if ( apply_filters( $lca.'_add_metabox_post', $add_metabox, $post_id ) ) {

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'adding metabox for post' );

					// hooked by woocommerce module to load front-end libraries and start a session
					do_action( $lca.'_admin_post_header', $mod, $screen->id );

					if ( $this->p->debug->enabled )
						$this->p->debug->log( 'setting head_meta_info static property' );

					// $read_cache = false to generate notices etc.
					WpssoMeta::$head_meta_tags = $this->p->head->get_header_array( $post_id, $mod, false );
					WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( WpssoMeta::$head_meta_tags );

					// check for missing open graph image and issue warning
					if ( $post_obj->post_status === 'publish' ) {
						if ( empty( WpssoMeta::$head_meta_info['og:image'] ) )
							$this->p->notice->err( $this->p->msgs->get( 'notice-missing-og-image' ) );

						// check duplicates only when the post is available publicly and we have a valid permalink
						if ( apply_filters( $lca.'_check_post_header', $this->p->options['plugin_check_head'], $post_id, $post_obj ) )
							$this->check_post_header( $post_id, $post_obj );
					}
				}
			} elseif ( $this->p->debug->enabled )
				$this->p->debug->log( 'skipped head meta: post_status is auto-draft' );

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
						'wpsso' ), 'post', $action_name ) );
				} else {
					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE ) );
					switch ( $action_name ) {
						default: 
							do_action( $lca.'_load_meta_page_post_'.$action_name, $post_id, $post_obj );
							break;
					}
				}
			}
		}

		public function check_post_header( $post_id = true, &$post_obj = false ) {
			if ( $this->p->debug->enabled )
				$this->p->debug->mark();

			if ( empty( $this->p->options['plugin_check_head'] ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'exiting early: plugin_check_head option not enabled');
				return $post_id;
			}

			if ( ! is_object( $post_obj ) &&
				( $post_obj = SucomUtil::get_post_object( $post_id ) ) === false ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'exiting early: unable to determine the post_id');
				return $post_id;
			}

			// only check publicly available posts
			if ( ! isset( $post_obj->post_status ) || $post_obj->post_status !== 'publish' ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'exiting early: post_status \''.$post_obj->post_status.'\' not published');
				return $post_id;
			}

			// only check public post types (to avoid menu items, product variations, etc.)
			$post_type_names = $this->p->util->get_post_types( 'names' );
			if ( empty( $post_obj->post_type ) || ! in_array( $post_obj->post_type, $post_type_names ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->mark( 'exiting early: post_type \''.$post_obj->post_type.'\' not public' );
				return $post_id;
			}

			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'check head meta' );

			$lca = $this->p->cf['lca'];
			$charset = get_bloginfo( 'charset' );
			$permalink = get_permalink( $post_id );
			$permalink_html = SucomUtil::encode_emoji( htmlentities( urldecode( $permalink ), 
				ENT_QUOTES, $charset, false ) );	// double_encode = false
			$permalink_no_meta = add_query_arg( array( 'WPSSO_META_TAGS_DISABLE' => 1 ), $permalink );
			$check_opts = apply_filters( $lca.'_check_head_meta_options',
				SucomUtil::preg_grep_keys( '/^add_/', $this->p->options, false, '' ), $post_id );

			if ( current_user_can( 'manage_options' ) &&
				$this->p->check->aop( $lca, true, $this->p->is_avail['aop'] ) )
					$notice_suffix = ' ('.sprintf( __( 'can be disabled in the <a href="%s">WP / Theme Integration</a> Advanced settings',
						'wpsso' ), $this->p->util->get_admin_url( 'advanced#sucom-tabset_plugin-tab_integration' ) ).')';
			else $notice_suffix = '';

			$this->p->notice->inf( sprintf( __( 'Checking %1$s for duplicate meta tags', 'wpsso' ), 
				'<a href="'.$permalink.'">'.$permalink_html.'</a>' ).$notice_suffix.'...', true );

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
										$this->p->notice->err( sprintf( __( 'Possible conflict detected &mdash; your theme or another plugin is adding a <code>%1$s</code> HTML tag to the head section of this webpage.', 'wpsso' ), $tag.' '.$t.'="'.$m[$t].'"' ), true );
							}
						}
					}
				}
			}
			if ( $this->p->debug->enabled )
				$this->p->debug->mark( 'check head meta' );

			return $post_id;
		}

		public function add_metaboxes() {

			if ( ( $post_obj = SucomUtil::get_post_object() ) === false || 
				empty( $post_obj->post_type ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'exiting early: object without post type' );
				return;
			} else $post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;

			if ( ( $post_obj->post_type === 'page' && ! current_user_can( 'edit_page', $post_id ) ) || 
				! current_user_can( 'edit_post', $post_id ) ) {
				if ( $this->p->debug->enabled )
					$this->p->debug->log( 'insufficient privileges to add metabox for '.$post_obj->post_type.' ID '.$post_id );
				return;
			}

			$lca = $this->p->cf['lca'];
			$add_metabox = empty( $this->p->options[ 'plugin_add_to_'.$post_obj->post_type ] ) ? false : true;

			if ( apply_filters( $lca.'_add_metabox_post', $add_metabox, $post_id ) ) {
				add_meta_box( $lca.'_social_settings', _x( 'Social Settings', 'metabox title', 'wpsso' ),
					array( &$this, 'show_metabox_social_settings' ), $post_obj->post_type, 'normal', 'low' );
			}
		}

		public function show_metabox_social_settings( $post_obj ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
				$this->p->debug->log( 'post_id is '.( empty( $post_obj->ID ) ? 0 : $post_obj->ID ) );
				$this->p->debug->log( 'post_type is '.( empty( $post_obj->post_type ) ? 'empty' : $post_obj->post_type ) );
				$this->p->debug->log( 'post_status is '.( empty( $post_obj->post_status ) ? 'empty' : $post_obj->post_status ) );
			}

			$lca = $this->p->cf['lca'];
			$metabox = 'social_settings';
			$mod = $this->get_mod( $post_obj->ID );
			$tabs = $this->get_social_tabs( $metabox, $mod );
			$opts = $this->get_options( $post_obj->ID );
			$def_opts = $this->get_defaults( $post_obj->ID );
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

		protected function get_table_rows( &$metabox, &$key, &$head, &$mod ) {

			$is_auto_draft = empty( $mod['post_status'] ) || 
				$mod['post_status'] === 'auto-draft' ? true : false;
			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to update this value.',
				'wpsso' ), ucfirst( $mod['post_type'] ) );

			$table_rows = array();
			switch ( $key ) {
				case 'preview':
					$table_rows = $this->get_rows_social_preview( $this->form, $head, $mod );
					break;

				case 'tags':	
					if ( $is_auto_draft )
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">'.
							$auto_draft_msg.'</p></blockquote></td>';
					else $table_rows = $this->get_rows_head_tags( $this->form, $head, $mod );
					break; 

				case 'validate':
					if ( $is_auto_draft )
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">'.
							$auto_draft_msg.'</p></blockquote></td>';
					else $table_rows = $this->get_rows_validate( $this->form, $head, $mod );
					break; 
			}
			return $table_rows;
		}

		public function clear_cache( $post_id, $rel_id = false ) {
			$this->p->util->clear_post_cache( $post_id );
			return $post_id;
		}

	}
}

?>

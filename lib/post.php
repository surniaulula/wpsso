<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoPost' ) ) {

	/**
	 * This class is extended by gpl/util/post.php or pro/util/post.php
	 * and the class object is created as $this->p->m['util']['post'].
	 */
	class WpssoPost extends WpssoMeta {

		protected static $cache_short_url = null;
		protected static $cache_shortlinks = array();

		public function __construct() {
		}

		protected function add_actions() {

			if ( is_admin() ) {

				add_action( 'wp_ajax_' . $this->p->lca . '_get_metabox_post', array( $this, 'ajax_get_metabox_post' ) );

				if ( ! empty( $_GET ) || basename( $_SERVER['PHP_SELF'] ) === 'post-new.php' ) {

					/**
					 * load_meta_page() priorities: 100 post, 200 user, 300 term.
					 * Sets the WpssoMeta::$head_meta_tags and WpssoMeta::$head_meta_info class properties.
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 100, 1 );
					add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
				}

				add_action( 'save_post', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'save_post', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				add_action( 'edit_attachment', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_attachment', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				if ( ! empty( $this->p->options['add_meta_name_robots'] ) ) {
					add_action( 'post_submitbox_misc_actions', array( $this, 'show_robots_options' ) );
					add_action( 'save_post', array( $this, 'save_robots_options' ) );
				}
			}

			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;

			/**
			 * Add the columns when doing AJAX as well to allow Quick Edit to add the required columns.
			 */
			if ( is_admin() || $doing_ajax ) {

				/**
				 * Only use public post types (to avoid menu items, product variations, etc.).
				 */
				$ptns = $this->p->util->get_post_types( 'names' );

				if ( is_array( $ptns ) ) {
					foreach ( $ptns as $ptn ) {

						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'adding column filters for post type ' . $ptn );
						}

						/**
						 * See https://codex.wordpress.org/Plugin_API/Filter_Reference/manage_$post_type_posts_columns.
						 */
						add_filter( 'manage_' . $ptn . '_posts_columns', array( $this, 'add_post_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );
						add_filter( 'manage_edit-' . $ptn . '_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );

						/**
						 * See https://codex.wordpress.org/Plugin_API/Action_Reference/manage_$post_type_posts_custom_column.
						 */
						add_action( 'manage_' . $ptn . '_posts_custom_column', array( $this, 'show_column_content' ), 10, 2 );
					}
				}

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding column filters for media library' );
				}

				add_filter( 'manage_media_columns', array( $this, 'add_media_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );
				add_filter( 'manage_upload_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );
				add_action( 'manage_media_custom_column', array( $this, 'show_column_content' ), 10, 2 );

				/**
				 * The 'parse_query' action is hooked ONCE in the WpssoPost class
				 * to set the column orderby for post, term, and user edit tables.
				 */
				add_action( 'parse_query', array( $this, 'set_column_orderby' ), 10, 1 );
				add_action( 'get_post_metadata', array( $this, 'check_sortable_metadata' ), 10, 4 );
			}

			if ( ! empty( $this->p->options['plugin_shortener'] ) && $this->p->options['plugin_shortener'] !== 'none' ) {

				if ( ! empty( $this->p->options['plugin_wp_shortlink'] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'adding pre_get_shortlink filters to shorten the sharing url' );
					}

					add_filter( 'pre_get_shortlink', array( $this, 'get_sharing_shortlink' ), SucomUtil::get_min_int(), 4 );
					add_filter( 'pre_get_shortlink', array( $this, 'maybe_restore_shortlink' ), SucomUtil::get_max_int(), 4 );

					if ( function_exists( 'wpme_get_shortlink_handler' ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'removing the jetpack pre_get_shortlink filter hook' );
						}
						remove_filter( 'pre_get_shortlink', 'wpme_get_shortlink_handler', 1 );
					}
				}
			}

			if ( ! empty( $this->p->options['plugin_clear_for_comment'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'adding clear cache for comment actions' );
				}

				/**
				 * Fires when a comment is inserted into the database.
				 */
				add_action ( 'comment_post', array( $this, 'clear_cache_for_new_comment' ), 10, 2 );

				/**
				 * Fires before transitioning a comment's status.
				 */
				add_action ( 'wp_set_comment_status', array( $this, 'clear_cache_for_comment_status' ), 10, 2 );
			}
		}

		public function get_mod( $mod_id ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$mod = WpssoMeta::$mod_defaults;

			$mod['id']   = (int) $mod_id;
			$mod['name'] = 'post';
			$mod['obj']  =& $this;

			/**
			 * Post
			 */
			$mod['is_post']        = true;
			$mod['is_home_page']   = SucomUtil::is_home_page( $mod_id );
			$mod['is_home_index']  = $mod['is_home_page'] ? false : SucomUtil::is_home_index( $mod_id );
			$mod['is_home']        = $mod['is_home_page'] || $mod['is_home_index'] ? true : false;
			$mod['post_slug']      = get_post_field( 'post_name', $mod_id );		// post name (aka slug)
			$mod['post_type']      = get_post_type( $mod_id );				// post type name
			$mod['post_mime']      = get_post_mime_type( $mod_id );				// post mime type (ie. image/jpg)
			$mod['post_status']    = get_post_status( $mod_id );				// post status name
			$mod['post_author']    = (int) get_post_field( 'post_author', $mod_id );	// post author id
			$mod['post_coauthors'] = array();

			/**
			 * Hooked by the 'coauthors' pro module.
			 */
			return apply_filters( $this->p->lca . '_get_post_mod', $mod, $mod_id );
		}

		public static function get_public_post_ids() {

			$post_ids = array();

			$post_posts = get_posts( array(
				'has_password'   => false,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'paged'          => false,
				'post_status'    => 'publish',
				'post_type'      => 'any',		// Post, page, or custom post type.
				'posts_per_page' => -1,
			) );

			foreach ( $post_posts as $post ) {
				if ( ! empty( $post-> ID ) ) {	// just in case
					$post_ids[] = $post-> ID;
				}
			}

			return $post_ids;
		}

		public function get_posts( array $mod, $posts_per_page = false, $paged = false, array $get_posts_args = array() ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( false === $posts_per_page ) {
				$posts_per_page = apply_filters( $this->p->lca . '_posts_per_page', get_option( 'posts_per_page' ), $mod );
			}

			if ( false === $paged ) {
				$paged = get_query_var( 'paged' );
			}

			if ( ! $paged > 1 ) {
				$paged = 1;
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'calling get_posts() for direct children of ' . 
					$mod['name'] . ' id ' . $mod['id'] . ' (posts_per_page is ' . $posts_per_page . ')' );
			}

			$get_posts_args = array_merge( array(
				'has_password'   => false,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'paged'          => $paged,
				'post_status'    => 'publish',
				'post_type'      => 'any',		// Post, page, or custom post type.
				'posts_per_page' => $posts_per_page,
				'post_parent'    => $mod['id'],
				'child_of'       => $mod['id'],		// Only include direct children.
			), $get_posts_args );

			$max_time   = SucomUtil::get_const( 'WPSSO_GET_POSTS_MAX_TIME', 0.10 );
			$start_time = microtime( true );
			$post_posts = get_posts( $get_posts_args );
			$total_time = microtime( true ) - $start_time;

			if ( $max_time > 0 && $total_time > $max_time ) {

				$info = $this->p->cf['plugin'][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow query detected - WordPress get_posts() took %1$0.3f secs' . 
						' to get the children of post ID %2$d', $total_time, $mod['id'] ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $max_time );

				// translators: %1$0.3f is a number of seconds, %2$d is an ID number, %3$s is a recommended max
				$error_msg = sprintf( __( 'Slow query detected - WordPress get_posts() took %1$0.3f secs to get the children of post ID %2$d (%3$s).',
					'wpsso' ), $total_time, $mod['id'], $rec_max_msg );

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
				$this->p->debug->log( count( $post_posts ) . ' post objects returned in ' . sprintf( '%0.3f secs', $total_time ) );
			}

			return $post_posts;
		}

		/**
		 * Filters the wp shortlink for a post - returns the shortened sharing URL.
		 * The wp_shortlink_wp_head() function calls wp_get_shortlink( 0, 'query' );
		 */
		public function get_sharing_shortlink( $shortlink = false, $post_id = 0, $context = 'post', $allow_slugs = true ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log_args( array(
					'shortlink'   => $shortlink,
					'post_id'     => $post_id,
					'context'     => $context,
					'allow_slugs' => $allow_slugs,
				) );
			}

			self::$cache_short_url = null;	// Just in case.

			if ( isset( self::$cache_shortlinks[$post_id][$context][$allow_slugs] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'returning shortlink (from static cache) = ' . 
						self::$cache_shortlinks[$post_id][$context][$allow_slugs] );
				}
				return self::$cache_short_url = self::$cache_shortlinks[$post_id][$context][$allow_slugs];
			}

			/**
			 * Check to make sure we have a plugin shortener selected.
			 */
			if ( empty( $this->p->options['plugin_shortener'] ) || $this->p->options['plugin_shortener'] === 'none' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: no shortening service defined' );
				}
				return $shortlink;	// return original shortlink
			}

			/**
			 * The WordPress link-template.php functions call wp_get_shortlink() with a post ID of 0.
			 * Recreate the same code here to get a real post ID and create a default shortlink (if required).
			 */
			if ( $post_id === 0 ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'provided post id is 0 (current post)' );
				}

				if ( $context === 'query' && is_singular() ) {	// wp_get_shortlink() uses the same logic
					$post_id = get_queried_object_id();
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting post id ' . $post_id . ' from queried object' );
					}
				} elseif ( $context === 'post' ) {
					$post_obj = get_post();
					if ( empty( $post_obj->ID ) ) {
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'exiting early: post object ID is empty' );
						}
						return $shortlink;	// return original shortlink
					} else {
						$post_id = $post_obj->ID;
						if ( $this->p->debug->enabled ) {
							$this->p->debug->log( 'setting post id ' . $post_id . ' from post object' );
						}
					}
				}

				if ( empty( $post_id ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: unable to determine the post id' );
					}
					return $shortlink;	// return original shortlink
				}

				if ( empty( $shortlink ) ) {
					if ( get_post_type( $post_id ) === 'page' && get_option( 'page_on_front' ) == $post_id && get_option( 'show_on_front' ) == 'page' ) {
						$shortlink = home_url( '/' );
					} else {
						$shortlink = home_url( '?p=' . $post_id );
					}
				}

			} elseif ( ! is_numeric( $post_id ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_id argument is not numeric' );
				}
				return $shortlink;	// return original shortlink
			}

			$mod = $this->get_mod( $post_id );

			if ( empty( $mod['post_type'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type is empty' );
				}
				return $shortlink;	// return original shortlink
			} elseif ( empty( $mod['post_status'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is empty' );
				}
				return $shortlink;	// return original shortlink
			} elseif ( $mod['post_status'] === 'auto-draft' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is auto-draft' );
				}
				return $shortlink;	// return original shortlink
			} elseif ( $mod['post_status'] === 'trash' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is trash' );
				}
				return $shortlink;	// return original shortlink
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod, false );	// $add_page = false
			$service_key = $this->p->options['plugin_shortener'];
			$short_url   = apply_filters( $this->p->lca . '_get_short_url', $sharing_url, $service_key, $mod, $context );

			if ( filter_var( $short_url, FILTER_VALIDATE_URL ) === false ) {	// invalid url
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: invalid short URL (' . $short_url . ') returned by filters' );
				}
				return $shortlink;	// return original shortlink
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returning shortlink = ' . $short_url );
			}

			return self::$cache_short_url = self::$cache_shortlinks[$post_id][$context][$allow_slugs] = $short_url;	// success - return short url
		}

		public function maybe_restore_shortlink( $shortlink = false, $post_id = 0, $context = 'post', $allow_slugs = true ) {

			if ( self::$cache_short_url === $shortlink ) {	// Shortlink value has not changed.

				self::$cache_short_url = null;	// Just in case.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: shortlink value has not changed' );
				}

				return $shortlink;
			}

			self::$cache_short_url = null;	// Just in case.

			if ( isset( self::$cache_shortlinks[$post_id][$context][$allow_slugs] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'restoring shortlink ' . $shortlink . ' to ' . 
						self::$cache_shortlinks[$post_id][$context][$allow_slugs] );
				}
				return self::$cache_shortlinks[$post_id][$context][$allow_slugs];
			}

			return $shortlink;
		}

		public function add_post_column_headings( $columns ) {
			return $this->add_mod_column_headings( $columns, 'post' );
		}

		public function add_media_column_headings( $columns ) {
			return $this->add_mod_column_headings( $columns, 'media' );
		}

		public function show_column_content( $column_name, $post_id ) {
			echo $this->get_column_content( '', $column_name, $post_id );
		}

		public function get_column_content( $value, $column_name, $post_id ) {

			if ( ! empty( $post_id ) && strpos( $column_name, $this->p->lca.'_' ) === 0 ) {	// Just in case.

				$col_idx = str_replace( $this->p->lca.'_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {

					if ( isset( $col_info['meta_key'] ) ) {	// Just in case.
						$value = $this->get_meta_cache_value( $post_id, $col_info['meta_key'] );
					}

					if ( isset( $col_info['post_callbacks'] ) && is_array( $col_info['post_callbacks'] ) ) {

						foreach( $col_info['post_callbacks'] as $input_name => $input_callback ) {

							if ( ! empty( $input_callback ) ) {
								$value .= "\n".'<input name="'.$input_name.'" type="hidden" value="'.
									call_user_func( $input_callback, $post_id ).'" readonly="readonly" />';
							}
						}
					}
				}
			}

			return $value;
		}

		public function get_meta_cache_value( $post_id, $meta_key, $none = '' ) {

			$meta_cache = wp_cache_get( $post_id, 'post_meta' );	// optimize and check wp_cache first

			if ( isset( $meta_cache[$meta_key][0] ) ) {
				$value = (string) maybe_unserialize( $meta_cache[$meta_key][0] );
			} else {
				$value = (string) get_post_meta( $post_id, $meta_key, true );	// $single = true
			}

			if ( $value === 'none' ) {
				$value = $none;
			}

			return $value;
		}

		public function update_sortable_meta( $post_id, $col_idx, $content ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $post_id ) ) {	// Just in case.
				if ( ( $col_info = self::get_sortable_columns( $col_idx ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// Just in case.
						update_post_meta( $post_id, $col_info['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $post_id, $meta_key, $single ) {

			static $do_once = array();

			if ( strpos( $meta_key, '_'.$this->p->lca.'_head_info_' ) !== 0 ) {	// example: _wpsso_head_info_og_img_thumb
				return $value;	// return null
			}

			if ( isset( $do_once[$post_id][$meta_key] ) ) {
				return $value;	// return null
			} else {
				$do_once[$post_id][$meta_key] = true;	// prevent recursion
			}

			if ( get_post_meta( $post_id, $meta_key, true ) === '' ) {	// returns empty string if meta not found
				$mod = $this->get_mod( $post_id );
				$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, true );	// $read_cache = true
				$head_meta_info = $this->p->head->extract_head_info( $mod, $head_meta_tags );
			}

			return $value;	// return null
		}

		public function ajax_get_metabox_post() {

			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;

			$doing_autosave = defined( 'DOING_AUTOSAVE' ) ? DOING_AUTOSAVE : false;

			if ( ! $doing_ajax ) {
				return;
			} elseif ( $doing_autosave ) {
				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', true );

			if ( empty( $_POST['post_id'] ) ) {
				die( '-1' );
			}

			$post_id = $_POST['post_id'];
			$post_obj = SucomUtil::get_post_object( $post_id );

			if ( ! is_object( $post_obj ) ) {
				die( '-1' );
			} elseif ( empty( $post_obj->post_type ) ) {
				die( '-1' );
			} elseif ( empty( $post_obj->post_status ) ) {
				die( '-1' );
			} elseif ( $post_obj->post_status === 'auto-draft' ) {
				die( '-1' );
			} elseif ( $post_obj->post_status === 'trash' ) {
				die( '-1' );
			}

			$mod = $this->get_mod( $post_id );

			/**
			 * $r_cache is false to generate notices etc.
			 */
			WpssoMeta::$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, false );
			WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoMeta::$head_meta_tags );

			if ( $post_obj->post_status === 'publish' ) {

				/**
				 * Check for missing open graph image and description values.
				 */
				foreach ( array( 'image', 'description' ) as $mt_suffix ) {
					if ( empty( WpssoMeta::$head_meta_info['og:'.$mt_suffix] ) ) {
						$error_msg = $this->p->msgs->get( 'notice-missing-og-'.$mt_suffix );
						$this->p->notice->err( $error_msg );
					}
				}
			}

			$metabox_html = $this->get_metabox_custom_meta( $post_obj );

			die( $metabox_html );
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
				case 'upload':
				case ( strpos( $screen->id, 'edit-' ) === 0 ? true : false ):	// posts list table
					return;
			}

			$post_obj = SucomUtil::get_post_object( true );
			$post_id  = empty( $post_obj->ID ) ? 0 : $post_obj->ID;

			/**
			 * Make sure we have at least a post type and status.
			 */
			if ( ! is_object( $post_obj ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_obj is not an object' );
				}
				return;
			} elseif ( empty( $post_obj->post_type ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type is empty' );
				}
				return;
			} elseif ( empty( $post_obj->post_status ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is empty' );
				}
				return;
			}

			/**
			 * Define the DOING_BLOCK_EDITOR constant.
			 */
			$doing_block_editor = SucomUtil::is_doing_block_editor( $post_obj->post_type );

			$mod = $this->get_mod( $post_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = '.get_option( 'home' ) );
				$this->p->debug->log( 'locale default = '.SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = '.SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = '.SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			WpssoMeta::$head_meta_tags = array();

			if ( $post_obj->post_status === 'auto-draft' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head meta skipped: post_status is auto-draft' );
				}
			} elseif ( $post_obj->post_status === 'trash' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head meta skipped: post_status is trash' );
				}
			} elseif ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'trash' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head meta skipped: post is being trashed' );
				}
			} elseif ( $doing_block_editor && ! empty( $_REQUEST['meta_box'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head meta skipped: doing block editor for meta box' );
				}
			} else {

				$add_metabox = empty( $this->p->options['plugin_add_to_'.$post_obj->post_type] ) ? false : true;
				$add_metabox = apply_filters( $this->p->lca.'_add_metabox_post', $add_metabox, $post_id, $post_obj->post_type );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'add metabox for post ID '.$post_id.' of type '.$post_obj->post_type.' is '.
						( $add_metabox ? 'true' : 'false' ) );
				}

				if ( $add_metabox ) {

					/**
					 * Hooked by woocommerce module to load front-end libraries and start a session.
					 */
					do_action( $this->p->lca.'_admin_post_head', $mod, $screen->id );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting head_meta_info static property' );
					}

					/**
					 * $read_cache is false to generate notices etc.
					 */
					WpssoMeta::$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, false );
					WpssoMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoMeta::$head_meta_tags );

					if ( $post_obj->post_status === 'publish' ) {

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

						/**
						 * Check duplicates only when the post is available publicly and we have a valid permalink.
						 */
						if ( current_user_can( 'manage_options' ) ) {
							if ( apply_filters( $this->p->lca.'_check_post_head', $this->p->options['plugin_check_head'], $post_id, $post_obj ) ) {
								$this->check_post_head_duplicates( $post_id, $post_obj );
							}
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
						'wpsso' ), 'post', $action_name ) );
				} else {
					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );
					switch ( $action_name ) {
						default:
							do_action( $this->p->lca.'_load_meta_page_post_'.$action_name, $post_id, $post_obj );
							break;
					}
				}
			}
		}

		public function check_post_head_duplicates( $post_id = true, $post_obj = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$is_admin = is_admin();	// check once
			$short = $this->p->cf['plugin'][$this->p->lca]['short'];

			if ( empty( $this->p->options['plugin_check_head'] ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: plugin_check_head option is disabled');
				}
				return;	// stop here
			}

			if ( ! apply_filters( $this->p->lca.'_add_meta_name_'.$this->p->lca.':mark', true ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: '.$this->p->lca.':mark meta tags are disabled');
				}
				return;	// stop here
			}

			if ( empty( $post_id ) ) {
				$post_id = true;
			}

			if ( ! is_object( $post_obj ) ) {
				$post_obj = SucomUtil::get_post_object( $post_id );
				if ( empty( $post_obj ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: unable to get the post object');
					}
					return;	// stop here
				}
			}

			if ( ! is_numeric( $post_id ) ) {	// Just in case post_id is true/false
				if ( empty( $post_obj->ID ) ) {
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: post id in post object is empty');
					}
					return;	// stop here
				}
				$post_id = $post_obj->ID;
			}

			/**
			 * Only check publicly available posts.
			 */
			if ( ! isset( $post_obj->post_status ) || $post_obj->post_status !== 'publish' ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status \''.$post_obj->post_status.'\' not published');
				}
				return;	// stop here
			}

			/**
			 * Only check public post types (to avoid menu items, product variations, etc.).
			 */
			$ptns = $this->p->util->get_post_types( 'names' );

			if ( empty( $post_obj->post_type ) || ! in_array( $post_obj->post_type, $ptns ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type \''.$post_obj->post_type.'\' not public' );
				}
				return;	// stop here
			}

			$exec_count = $this->p->debug->enabled ? 0 : (int) get_option( WPSSO_POST_CHECK_NAME );		// cast to change false to 0
			$max_count = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT' );

			if ( $exec_count >= $max_count ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: exec_count of '.$exec_count.' exceeds max_count of '.$max_count );
				}
				return;	// stop here
			}

			if ( ini_get( 'open_basedir' ) ) {	// cannot follow redirects
				$check_url = $this->p->util->get_sharing_url( $post_id, false );	// $add_page = false
			} else {
				$check_url = SucomUtilWP::wp_get_shortlink( $post_id, 'post' );	// $context = post
			}

			$check_url_htmlenc = SucomUtil::encode_html_emoji( urldecode( $check_url ) );

			if ( empty( $check_url ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: invalid shortlink' );
				}
				return;	// stop here
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'checking '.$check_url.' head meta for duplicates' );
			}

			$clear_shortlink = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_CLEAR_SHORTLINK', true );

			if ( $clear_shortlink ) {
				$this->p->cache->clear( $check_url );	// clear cache before fetching shortlink url
			}

			if ( $is_admin ) {
				if ( $clear_shortlink ) {
					$this->p->notice->inf( sprintf( __( 'Checking %1$s for duplicate meta tags...', 'wpsso' ),
						'<a href="'.$check_url.'">'.$check_url_htmlenc.'</a>' ) );
				} else {
					$this->p->notice->inf( sprintf( __( 'Checking %1$s for duplicate meta tags (webpage could be from cache)...', 'wpsso' ),
						'<a href="'.$check_url.'">'.$check_url_htmlenc.'</a>' ) );
				}
			}

			/**
			 * Fetch HTML using the Facebook user agent to get Open Graph meta tags.
			 */
			$curl_opts    = array( 'CURLOPT_USERAGENT' => WPSSO_PHP_CURL_USERAGENT_FACEBOOK );
			$html         = $this->p->cache->get( $check_url, 'raw', 'transient', false, '', $curl_opts );
			$url_time     = $this->p->cache->get_url_time( $check_url );
			$warning_time = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_WARNING_TIME', 2.5 );
			$timeout_time = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_TIMEOUT_TIME', 3.0 );

			if ( true === $url_time ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched '.$check_url.' from transient cache' );
				}
			} elseif ( false === $url_time ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched '.$check_url.' returned a failure' );
				}
			} else {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched '.$check_url.' in '.$url_time.' secs' );
				}
				if ( is_admin() && $url_time > $warning_time ) {
					$this->p->notice->warn(
						sprintf( __( 'Retrieving the HTML document for %1$s took %2$s seconds.', 'wpsso' ),
							'<a href="'.$check_url.'">'.$check_url_htmlenc.'</a>', $url_time ).' '.
						sprintf( __( 'This exceeds the recommended limit of %1$s seconds (crawlers often time-out after %2$s seconds).',
							'wpsso' ), $warning_time, $timeout_time ).' '.
						__( 'Please consider improving the speed of your site.', 'wpsso' ).' '.
						__( 'As an added benefit, a faster site will also improve ranking in search results.', 'wpsso' ).' ;-)'
					);
				}
			}

			if ( empty( $html ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: error retrieving webpage from '.$check_url );
				}
				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'Error retrieving webpage from <a href="%1$s">%1$s</a>.',
						'wpsso' ), $check_url ) );
				}
				return;	// stop here
			} elseif ( stripos( $html, '<html' ) === false ) {	// webpage must have an <html> tag
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: <html> tag not found in '.$check_url );
				}
				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'An &lt;html&gt; tag was not found in <a href="%1$s">%1$s</a>.',
						'wpsso' ), $check_url ) );
				}
				return;	// stop here
			} elseif ( ! preg_match( '/<meta[ \n]/i', $html ) ) {	// webpage must have one or more <meta/> tags
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: No <meta/> HTML tags were found in '.$check_url );
				}
				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'No %1$s HTML tags were found in <a href="%2$s">%2$s</a>.',
						'wpsso' ), '&lt;meta/&gt;', $check_url ) );
				}
				return;	// stop here
			} elseif ( strpos( $html, $this->p->lca.' meta tags begin' ) === false ) {	// webpage should include our own meta tags
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: '.$this->p->lca.' meta tag section not found in '.$check_url );
				}
				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'A %2$s meta tag section was not found in <a href="%1$s">%1$s</a> &mdash; perhaps a webpage caching plugin or service needs to be refreshed?', 'wpsso' ), $check_url, $short ) );
				}
				return;	// stop here
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'removing '.$this->p->lca.' meta tag section' );
			}

			$html = preg_replace( $this->p->head->get_mt_mark( 'preg' ), '', $html, -1, $mark_count );

			if ( ! $mark_count ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: preg_replace() function failed to remove the meta tag section' );
				}
				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'The PHP preg_replace() function failed to remove the %1$s meta tag section &mdash; this could be an indication of a problem with PHP\'s PCRE library or a webpage filter corrupting the %1$s meta tags.', 'wpsso' ), $short ) );
				}
				return;	// stop here
			}

			/**
			 * Providing html, so no need to specify a user agent.
			 */
			$metas = $this->p->util->get_head_meta( $html, '/html/head/link|/html/head/meta', true );	// false on error
			$check_opts = SucomUtil::preg_grep_keys( '/^add_/', $this->p->options, false, '' );
			$conflicts_msg = __( 'Conflict detected &mdash; your theme or another plugin is adding %1$s to the head section of this webpage.', 'wpsso' );
			$conflicts_found = 0;

			if ( is_array( $metas ) ) {
				if ( empty( $metas ) ) {	// no link or meta tags found
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'error parsing head meta for '.$check_url );
					}
					if ( $is_admin ) {
						$validator_url = 'https://validator.w3.org/nu/?doc='.urlencode( $check_url );
						$settings_page_url = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest' );

						$this->p->notice->err( sprintf( __( 'An error occured parsing the head meta tags from <a href="%1$s">%1$s</a>.', 'wpsso' ), $check_url ).' '.sprintf( __( 'The webpage may contain serious HTML syntax errors &mdash; please review the <a href="%1$s">W3C Markup Validation Service</a> results and correct any errors.', 'wpsso' ), $validator_url ).' '.sprintf( __( 'You may safely ignore any "nopin" attribute errors, or disable the "nopin" attribute under the <a href="%s">Pinterest settings tab</a>.', 'wpsso' ), $settings_page_url ) );
					}
				} else {
					foreach( array(
						'link' => array( 'rel' ),
						'meta' => array( 'name', 'property', 'itemprop' ),
					) as $tag => $types ) {
						if ( isset( $metas[$tag] ) ) {
							foreach( $metas[$tag] as $meta ) {
								foreach( $types as $type ) {
									if ( isset( $meta[$type] ) && $meta[$type] !== 'generator' &&
										! empty( $check_opts[$tag.'_'.$type.'_'.$meta[$type]] ) ) {
										$conflicts_found++;
										$conflicts_tag = '<code>'.$tag.' '.$type.'="'.$meta[$type].'"</code>';
										$this->p->notice->err( sprintf( $conflicts_msg, $conflicts_tag ) );
									}
								}
							}
						}
					}
					if ( $is_admin ) {

						$exec_count++;

						if ( $conflicts_found ) {

							$warn_msg = sprintf( __( '%1$d duplicate meta tags found.', 'wpsso' ), $conflicts_found ) . ' ';
							$warn_msg .= sprintf( __( 'Check %1$d of %2$d failed (will retry)...', 'wpsso' ), $exec_count, $max_count );

							$this->p->notice->warn( $warn_msg );

						} else {

							$inf_msg = __( 'Awesome! No duplicate meta tags found.', 'wpsso' ) . ' :-) ';

							if ( $this->p->debug->enabled ) {
								$inf_msg .= __( 'Debug option is enabled - will keep repeating duplicate check...', 'wpsso' );
							} else {
								$inf_msg .= sprintf( __( 'Check %1$d of %2$d successful...', 'wpsso' ), $exec_count, $max_count );
							}

							update_option( WPSSO_POST_CHECK_NAME, $exec_count, false );	// Autoload is false.

							$this->p->notice->inf( $inf_msg );
						}
					}
				}
			}
		}

		public function add_meta_boxes() {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ( $post_obj = SucomUtil::get_post_object( true ) ) === false || empty( $post_obj->post_type ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: object without post type' );
				}
				return;
			} else {
				$post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;
			}

			if ( ( $post_obj->post_type === 'page' && ! current_user_can( 'edit_page', $post_id ) ) || ! current_user_can( 'edit_post', $post_id ) ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to add metabox for '.$post_obj->post_type.' ID '.$post_id );
				}
				return;
			}

			$metabox_id      = $this->p->cf['meta']['id'];
			$metabox_title   = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
			$metabox_screen  = $post_obj->post_type;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$add_metabox     = empty( $this->p->options[ 'plugin_add_to_'.$post_obj->post_type ] ) ? false : true;
			$add_metabox     = apply_filters( $this->p->lca.'_add_metabox_post', $add_metabox, $post_id, $post_obj->post_type );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for post ID '.$post_id.' of type '.$post_obj->post_type.' is '.
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {
				add_meta_box( $this->p->lca.'_'.$metabox_id, $metabox_title,
					array( $this, 'show_metabox_custom_meta' ), $metabox_screen,
						$metabox_context, $metabox_prio );
			}
		}

		public function show_metabox_custom_meta( $post_obj ) {
			echo $this->get_metabox_custom_meta( $post_obj );
		}

		public function get_metabox_custom_meta( $post_obj ) {

			$metabox_id = $this->p->cf['meta']['id'];
			$mod        = $this->get_mod( $post_obj->ID );
			$tabs       = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $post_obj->ID );
			$def_opts   = $this->get_defaults( $post_obj->ID );
			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id.' table rows' );	// start timer
			}

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {
				$table_rows[$tab_key] = array_merge( $this->get_table_rows( $metabox_id, $tab_key, WpssoMeta::$head_meta_info, $mod ),
					apply_filters( $this->p->lca.'_'.$mod['name'].'_'.$tab_key.'_rows', array(), $this->form, WpssoMeta::$head_meta_info, $mod ) );
			}

			$metabox_html = $this->p->util->get_metabox_tabbed( $metabox_id, $tabs, $table_rows );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id.' table rows' );	// end timer
			}

			return "\n" . '<div id="' . $this->p->lca . '_metabox_' . $metabox_id . '">' . $metabox_html . '</div>' . "\n";
		}

		protected function get_table_rows( $metabox_id, $tab_key, $head, $mod ) {

			$is_auto_draft  = empty( $mod['post_status'] ) || $mod['post_status'] === 'auto-draft' ? true : false;
			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to display these options.', 'wpsso' ),
				SucomUtil::titleize( $mod['post_type'] ) );

			$table_rows = array();

			switch ( $tab_key ) {

				case 'preview':

					$table_rows = $this->get_rows_social_preview( $this->form, $head, $mod );

					break;

				case 'tags':

					if ( $is_auto_draft ) {
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">' .
							$auto_draft_msg . '</p></blockquote></td>';
					} else {
						$table_rows = $this->get_rows_head_tags( $this->form, $head, $mod );
					}

					break;

				case 'validate':

					if ( $is_auto_draft ) {
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">' .
							$auto_draft_msg . '</p></blockquote></td>';
					} else {
						$table_rows = $this->get_rows_validate( $this->form, $head, $mod );
					}

					break;
			}

			return $table_rows;
		}

		public function clear_cache_for_new_comment( $comment_id, $comment_approved ) {
			if ( $comment_id && $comment_approved === 1 ) {
				if ( ( $comment = get_comment( $comment_id ) ) && $comment->comment_post_ID ) {
					$post_id = $comment->comment_post_ID;
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'clearing post_id ' . $post_id . ' cache for comment_id ' . $comment_id );
					}
					$this->clear_cache( $post_id );
				}
			}
		}

		public function clear_cache_for_comment_status( $comment_id, $comment_status ) {
			if ( $comment_id ) {	// Just in case.
				if ( ( $comment = get_comment( $comment_id ) ) && $comment->comment_post_ID ) {
					$post_id = $comment->comment_post_ID;
					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'clearing post_id ' . $post_id . ' cache for comment_id ' . $comment_id );
					}
					$this->clear_cache( $post_id );
				}
			}
		}

		public function clear_cache( $post_id, $rel_id = false ) {

			switch ( get_post_status( $post_id ) ) {
				case 'draft':
				case 'pending':
				case 'future':
				case 'private':
				case 'publish':
					break;	// Stop here.
				case 'auto-draft':
				case 'trash':
				default:
					return;
			}

			$mod           = $this->get_mod( $post_id );
			$cache_types   = array();
			$cache_md5_pre = $this->p->lca . '_';
			$permalink     = get_permalink( $post_id );
			$col_meta_keys = WpssoMeta::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_idx => $meta_key ) {
				delete_post_meta( $post_id, $meta_key );
			}

			if ( ini_get( 'open_basedir' ) ) {
				$check_url = $this->p->util->get_sharing_url( $post_id, false );	// $add_page = false
			} else {
				$check_url = SucomUtilWP::wp_get_shortlink( $post_id, 'post' );	// $context = post
			}

			$cache_types['transient'][] = array(
				'id' => $cache_md5_pre . md5( 'SucomCache::get(url:' . $permalink . ')' ),
				'pre' => $cache_md5_pre,
				'salt' => 'SucomCache::get(url:' . $permalink . ')',
			);

			if ( $permalink !== $check_url ) {
				$cache_types['transient'][] = array(
					'id' => $cache_md5_pre . md5( 'SucomCache::get(url:' . $check_url . ')' ),
					'pre' => $cache_md5_pre,
					'salt' => 'SucomCache::get(url:' . $check_url . ')',
				);
			}

			$this->clear_mod_cache_types( $mod, $cache_types );

			if ( function_exists( 'w3tc_pgcache_flush_post' ) ) {	// w3 total cache
				w3tc_pgcache_flush_post( $post_id );
			}

			if ( function_exists( 'wp_cache_post_change' ) ) {	// wp super cache
				wp_cache_post_change( $post_id );
			}
		}

		public function show_robots_options( $post ) {

			if ( empty( $post->ID ) ) {	// Just in case.
				return;
			}

			$post_type = $post->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$can_publish = current_user_can( $post_type_object->cap->publish_posts );

			$mod = $this->get_mod( $post->ID );
			$robots_content = $this->p->util->get_robots_content( $mod );
			$robots_css_id  = $this->p->lca . '-robots';

			echo "\n";
			echo '<!-- ' .  $this->p->lca . ' nonce fields -->' . "\n";
			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );	// WPSSO_NONCE_NAME is an md5() string
			echo "\n";
			echo '<div class="misc-pub-section misc-pub-robots sucom-sidebox ' . $robots_css_id . '-options" id="post-' . $robots_css_id . '">' . "\n";
			echo '<div id="post-' . $robots_css_id . '-label">';
			echo _x( 'Robots', 'option label', 'wpsso' ) . ':';
			echo '</div>' . "\n";
			echo '<div id="post-' . $robots_css_id . '-display">' . "\n";
			echo '<div id="post-' . $robots_css_id . '-content">' . $robots_content;

			if ( $can_publish ) {
				echo ' <a href="#" class="hide-if-no-js" role="button" onClick="' .
					'jQuery(\'div#post-' . $robots_css_id . '-content\').hide();' .
					'jQuery(\'div#post-' . $robots_css_id . '-select\').show();">';
				echo '<span aria-hidden="true">' . __( 'Edit', 'wpsso' ) . '</span>'."\n";
				echo '<span class="screen-reader-text">' . __( 'Edit robots' ) . '</span>';
				echo '</a>' . "\n";
			}

			echo '</div><!-- #post-' . $robots_css_id . '-content -->' . "\n";

			if ( $can_publish ) {

				echo '<div id="post-' . $robots_css_id . '-select">' . "\n";

				foreach ( array(
					'noindex'   => _x( 'No index', 'option label', 'wpsso' ),
					'nofollow'  => _x( 'No follow', 'option label', 'wpsso' ),
					'noarchive' => _x( 'No archive', 'option label', 'wpsso' ),
					'nosnippet' => _x( 'No snippet', 'option label', 'wpsso' ),
				) as $meta_name => $meta_label ) {

					$meta_css_id = $this->p->lca . '_' . $meta_name;
					$meta_key    = '_' . $meta_css_id;
					$meta_value  = $this->get_meta_cache_value( $post->ID, $meta_key );

					echo '<input type="hidden" name="is_checkbox' . $meta_key . '" value="1"/>' . "\n";
					echo '<input type="checkbox" name="' . $meta_key . '" id="' . $meta_css_id . '"' .
						checked( $meta_value, 1, false ) . '/>' . "\n";
					echo '<label for="' . $meta_css_id . '" class="selectit">' . $meta_label . '</label>' . "\n";
					echo '<br />' . "\n";
				}

			  	echo '</div><!-- #post-' . $robots_css_id . '-select -->' . "\n";
			}

			echo '</div><!-- #post-' . $robots_css_id . '-display -->' . "\n";
			echo '</div><!-- #post-' . $robots_css_id . ' -->' . "\n";
		}

		public function save_robots_options( $post_id, $rel_id = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! $this->user_can_edit( $post_id, $rel_id ) ) {
				return;
			}

			foreach ( array( 'noindex', 'nofollow', 'noarchive', 'nosnippet' ) as $meta_name ) {

				$meta_key = '_' . $this->p->lca . '_' . $meta_name;

				if ( isset( $_POST['is_checkbox' . $meta_key] ) ) {
					if ( empty( $_POST[$meta_key] ) ) {
						delete_post_meta( $post_id, $meta_key );
					} else {
						update_post_meta( $post_id, $meta_key, 1 );
					}
				}
			}
		}

		public function user_can_edit( $post_id, $rel_id = false ) {

			$user_can_edit = false;

			if ( ! $this->verify_submit_nonce() ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}
				return $user_can_edit;
			}

			if ( ! $post_type = SucomUtil::get_request_value( 'post_type', 'POST' ) ) {	// uses sanitize_text_field
				$post_type = 'post';
			}

			switch ( $post_type ) {
				case 'page' :
					$user_can_edit = current_user_can( 'edit_' . $post_type, $post_id );
					break;
				default :
					$user_can_edit = current_user_can( 'edit_post', $post_id );
					break;
			}

			if ( ! $user_can_edit ) {
				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'insufficient privileges to save settings for ' . $post_type . ' ID ' . $post_id );
				}
				if ( $this->p->notice->is_admin_pre_notices() ) {
					$this->p->notice->err( sprintf( __( 'Insufficient privileges to save settings for %1$s ID %2$s.',
						'wpsso' ), $post_type, $post_id ) );
				}
			}

			return $user_can_edit;
		}

		public function get_og_type_reviews( $post_id, $og_type = 'product', $rating_meta = 'rating' ) {

			static $reviews_per_page_max = null;

			$ret = array();

			if ( empty( $post_id ) ) {
				return $ret;
			}

			$comments = get_comments( array(
				'post_id' => $post_id,
				'status'  => 'approve',
				'parent'  => 0,					// Parent ID of comment to retrieve children of (0 = don't get replies).
				'order'   => 'DESC',
				'number'  => get_option( 'comments_per_page' ),	// Maximum number of comments to retrieve.
			) );

			if ( is_array( $comments ) ) {

				foreach( $comments as $num => $comment_obj ) {

					$og_review = $this->get_og_review_mt( $comment_obj, $og_type, $rating_meta );

					if ( ! empty( $og_review ) ) {	// Just in case.
						$ret[] = $og_review;
					}
				}

				if ( ! isset( $reviews_per_page_max ) ) {	// only set the value once
					$reviews_per_page_max = SucomUtil::get_const( 'WPSSO_SCHEMA_REVIEWS_PER_PAGE_MAX', 30 );
				}

				if ( count( $ret ) > $reviews_per_page_max ) {
					if ( $wpsso->debug->enabled ) {
						$wpsso->debug->log( count( $ret ) . ' reviews found (adjusted to ' . $reviews_per_page_max . ')' );
					}
					$ret = array_slice( $ret, 0, $reviews_per_page_max );
				}
			}

			return $ret;
		}

		public function get_og_review_mt( $comment_obj, $og_type = 'product', $rating_meta = 'rating' ) {

			$ret = array();
			$rating_value = (float) get_comment_meta( $comment_obj->comment_ID, $rating_meta, true );

			$ret[$og_type . ':review:id']           = $comment_obj->comment_ID;
			$ret[$og_type . ':review:url']          = get_comment_link( $comment_obj->comment_ID );
			$ret[$og_type . ':review:author:id']    = $comment_obj->user_id;	// author ID if registered (0 otherwise)
			$ret[$og_type . ':review:author:name']  = $comment_obj->comment_author;	// author display name
			$ret[$og_type . ':review:created_time'] = mysql2date( 'c', $comment_obj->comment_date_gmt );
			$ret[$og_type . ':review:excerpt']      = get_comment_excerpt( $comment_obj->comment_ID );

			/**
			 * Rating values must be larger than 0 to include rating info.
			 */
			if ( $rating_value > 0 ) {
				$ret[$og_type . ':review:rating:value'] = $rating_value;
				$ret[$og_type . ':review:rating:worst'] = 1;
				$ret[$og_type . ':review:rating:best']  = 5;
			}

			return $ret;
		}
	}
}

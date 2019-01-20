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
	 * and the class object is created as $this->p->m[ 'util' ][ 'post' ].
	 */
	class WpssoPost extends WpssoWpMeta {

		protected static $cache_short_url = null;
		protected static $cache_shortlinks = array();

		public function __construct() {
		}

		protected function add_actions() {

			$is_admin   = is_admin();
			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX ? true : false;

			if ( $is_admin ) {

				$metabox_id   = $this->p->cf['meta'][ 'id' ];
				$container_id = $this->p->lca . '_metabox_' . $metabox_id . '_inside';

				add_action( 'wp_ajax_update_container_id_' . $container_id, array( $this, 'ajax_metabox_custom_meta' ) );

				if ( ! empty( $_GET ) || basename( $_SERVER['PHP_SELF'] ) === 'post-new.php' ) {

					/**
					 * load_meta_page() priorities: 100 post, 200 user, 300 term.
					 * Sets the WpssoWpMeta::$head_meta_tags and WpssoWpMeta::$head_meta_info class properties.
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 100, 1 );
					add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
				}

				/**
				 * The 'save_post' action is run after other post type specific actions,
				 * so we can use it to save post meta for any post type.
				 */
				add_action( 'save_post', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );

				/**
				 * Don't hook the 'clean_post_cache' action since 'save_post' is run after
				 * 'clean_post_cache' and our custom post meta has not been saved yet.
				 */
				add_action( 'save_post', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				/**
				 * The wp_insert_post() function returns after running the 'edit_attachment' action,
				 * so the 'save_post' action is never run for attachments.
				 */
				add_action( 'edit_attachment', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );
				add_action( 'edit_attachment', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );

				if ( ! empty( $this->p->options['add_meta_name_robots'] ) ) {
					add_action( 'post_submitbox_misc_actions', array( $this, 'show_robots_options' ) );
					add_action( 'save_post', array( $this, 'save_robots_options' ) );
				}
			}

			/**
			 * Add the columns when doing AJAX as well to allow Quick Edit to add the required columns.
			 */
			if ( $is_admin || $doing_ajax ) {

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

			$mod = WpssoWpMeta::$mod_defaults;

			$post_type = get_post_type( $mod_id );

			/**
			 * Common elements.
			 */
			$mod[ 'id' ]   = is_numeric( $mod_id ) ? (int) $mod_id : 0;	// Cast as integer.
			$mod[ 'name' ] = 'post';
			$mod[ 'obj' ]  =& $this;

			/**
			 * Post elements.
			 */
			$mod[ 'is_post' ]              = true;
			$mod[ 'is_home_page' ]         = SucomUtil::is_home_page( $mod_id );
			$mod[ 'is_home_index' ]        = $mod['is_home_page'] ? false : SucomUtil::is_home_index( $mod_id );
			$mod[ 'is_home' ]              = $mod['is_home_page'] || $mod['is_home_index'] ? true : false;
			$mod[ 'is_post_type_archive' ] = is_post_type_archive( $post_type );			// Post is an archive.
			$mod[ 'post_slug' ]            = get_post_field( 'post_name', $mod_id );		// Post name (aka slug).
			$mod[ 'post_type' ]            = $post_type;						// Post type name.
			$mod[ 'post_mime' ]            = get_post_mime_type( $mod_id );				// Post mime type (ie. image/jpg).
			$mod[ 'post_status' ]          = get_post_status( $mod_id );				// Post status name.
			$mod[ 'post_author' ]          = (int) get_post_field( 'post_author', $mod_id );	// Post author id.
			$mod[ 'post_coauthors' ]       = array();

			/**
			 * Hooked by the 'coauthors' pro module.
			 */
			return apply_filters( $this->p->lca . '_get_post_mod', $mod, $mod_id );
		}

		public static function get_public_post_ids() {

			$posts_args = array(
				'has_password'   => false,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'paged'          => false,
				'post_status'    => 'publish',		// Only 'publish', not 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or 'trash'.
				'post_type'      => 'any',		// Return post, page, or any custom post type.
				'posts_per_page' => -1,
				'fields'         => 'ids',		// Return an array of post ids.
			);

			return get_posts( $posts_args );
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
				$this->p->debug->log( 'calling get_posts() for direct children of ' . 
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
				'post_parent'    => $mod[ 'id' ],
				'child_of'       => $mod[ 'id' ],	// Only include direct children.
			), $posts_args, array( 'fields' => 'ids' ) );	// Return an array of post ids.

			$mtime_max   = SucomUtil::get_const( 'WPSSO_GET_POSTS_MAX_TIME', 0.10 );
			$mtime_start = microtime( true );
			$post_ids    = get_posts( $posts_args );
			$mtime_total = microtime( true ) - $mtime_start;

			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

				$info = $this->p->cf[ 'plugin' ][$this->p->lca];

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( sprintf( 'slow query detected - WordPress get_posts() took %1$0.3f secs' . 
						' to get the children of post ID %2$d', $mtime_total, $mod[ 'id' ] ) );
				}

				// translators: %1$0.3f is a number of seconds
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $mtime_max );

				// translators: %1$0.3f is a number of seconds, %2$d is an ID number, %3$s is a recommended max
				$error_msg = sprintf( __( 'Slow query detected - WordPress get_posts() took %1$0.3f secs to get the children of post ID %2$d (%3$s).',
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
				return $shortlink;	// Return original shortlink.
			}

			/**
			 * The WordPress link-template.php functions call wp_get_shortlink() with a post ID of 0.
			 * Recreate the same code here to get a real post ID and create a default shortlink (if required).
			 */
			if ( $post_id === 0 ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'provided post id is 0 (current post)' );
				}

				if ( $context === 'query' && is_singular() ) {	// wp_get_shortlink() uses the same logic.
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
						return $shortlink;	// Return original shortlink.
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
					return $shortlink;	// Return original shortlink.
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
				return $shortlink;	// Return original shortlink.
			}

			$mod = $this->get_mod( $post_id );

			if ( empty( $mod[ 'post_type' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type is empty' );
				}

				return $shortlink;	// Return original shortlink.

			} elseif ( empty( $mod[ 'post_status' ] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is empty' );
				}

				return $shortlink;	// Return original shortlink.

			} elseif ( $mod[ 'post_status' ] === 'auto-draft' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is auto-draft' );
				}

				return $shortlink;	// Return original shortlink.

			} elseif ( $mod[ 'post_status' ] === 'trash' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status is trash' );
				}

				return $shortlink;	// Return original shortlink.
			}

			$sharing_url = $this->p->util->get_sharing_url( $mod, $add_page = false );

			$short_url = apply_filters( $this->p->lca . '_get_short_url', $sharing_url,
				$this->p->options[ 'plugin_shortener' ], $mod );

			if ( filter_var( $short_url, FILTER_VALIDATE_URL ) === false ) {	// Invalid url.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: invalid short URL (' . $short_url . ') returned by filters' );
				}

				return $shortlink;	// Return original shortlink.
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'returning shortlink = ' . $short_url );
			}

			return self::$cache_short_url = self::$cache_shortlinks[$post_id][$context][$allow_slugs] = $short_url;	// Success - return short url.
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

			if ( ! empty( $post_id ) && strpos( $column_name, $this->p->lca . '_' ) === 0 ) {	// Just in case.

				$col_key = str_replace( $this->p->lca . '_', '', $column_name );

				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {

					if ( isset( $col_info['meta_key'] ) ) {	// Just in case.
						$value = $this->get_meta_cache_value( $post_id, $col_info['meta_key'] );
					}

					if ( isset( $col_info['post_callbacks'] ) && is_array( $col_info['post_callbacks'] ) ) {

						foreach( $col_info['post_callbacks'] as $input_name => $input_callback ) {

							if ( ! empty( $input_callback ) ) {
								$value .= "\n" . '<input name="' . $input_name . '" type="hidden" value="' . 
									call_user_func( $input_callback, $post_id ) . '" readonly="readonly" />';
							}
						}
					}
				}
			}

			return $value;
		}

		public function get_meta_cache_value( $post_id, $meta_key, $none = '' ) {

			$meta_cache = wp_cache_get( $post_id, 'post_meta' );	// Optimize and check wp_cache first.

			if ( isset( $meta_cache[$meta_key][0] ) ) {
				$value = (string) maybe_unserialize( $meta_cache[$meta_key][0] );
			} else {
				$value = (string) get_post_meta( $post_id, $meta_key, true );	// $single is true.
			}

			if ( $value === 'none' ) {
				$value = $none;
			}

			return $value;
		}

		public function update_sortable_meta( $post_id, $col_key, $content ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			if ( ! empty( $post_id ) ) {	// Just in case.
				if ( ( $col_info = self::get_sortable_columns( $col_key ) ) !== null ) {
					if ( isset( $col_info['meta_key'] ) ) {	// Just in case.
						update_post_meta( $post_id, $col_info['meta_key'], $content );
					}
				}
			}
		}

		public function check_sortable_metadata( $value, $post_id, $meta_key, $single ) {

			static $do_once = array();

			if ( strpos( $meta_key, '_' . $this->p->lca . '_head_info_' ) !== 0 ) {	// Example: _wpsso_head_info_og_img_thumb.
				return $value;	// Return null.
			}

			if ( isset( $do_once[ $post_id ][ $meta_key ] ) ) {
				return $value;	// Return null.
			} else {
				$do_once[ $post_id ][ $meta_key ] = true;	// Prevent recursion.
			}

			if ( get_post_meta( $post_id, $meta_key, true ) === '' ) {	// Returns empty string if meta not found.

				$mod = $this->get_mod( $post_id );

				$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, true );	// $read_cache = true
				$head_meta_info = $this->p->head->extract_head_info( $mod, $head_meta_tags );
			}

			return $value;	// Return null.
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
				case 'upload':
				case ( strpos( $screen->id, 'edit-' ) === 0 ? true : false ):	// Posts list table.
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

			$mod = $this->get_mod( $post_id );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale default = ' . SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomDebug::pretty_array( $mod ) );
			}

			WpssoWpMeta::$head_meta_tags = array();

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

			} elseif ( SucomUtilWP::doing_block_editor() && ! empty( $_REQUEST['meta_box'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'head meta skipped: doing block editor for meta box' );
				}

			} else {

				$add_metabox = empty( $this->p->options['plugin_add_to_' . $post_obj->post_type] ) ? false : true;
				$add_metabox = apply_filters( $this->p->lca . '_add_metabox_post', $add_metabox, $post_id, $post_obj->post_type );

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'add metabox for post ID ' . $post_id . ' of type ' . $post_obj->post_type . ' is ' . 
						( $add_metabox ? 'true' : 'false' ) );
				}

				if ( $add_metabox ) {

					/**
					 * Hooked by woocommerce module to load front-end libraries and start a session.
					 */
					do_action( $this->p->lca . '_admin_post_head', $mod, $screen->id );

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'setting head_meta_info static property' );
					}

					/**
					 * $read_cache is false to generate notices etc.
					 */
					WpssoWpMeta::$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, false );
					WpssoWpMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoWpMeta::$head_meta_tags );

					if ( $mod[ 'post_status' ] === 'publish' ) {

						$this->p->notice->set_ref( WpssoWpMeta::$head_meta_info['og:url'], $mod,
							sprintf( __( 'checking meta tags for %1$s ID %2$s', 'wpsso' ),
								$mod[ 'post_type' ], $mod[ 'id' ] ) );

						/**
						 * Check for missing open graph image and description values.
						 */
						foreach ( array( 'image', 'description' ) as $mt_suffix ) {

							if ( empty( WpssoWpMeta::$head_meta_info['og:' . $mt_suffix] ) ) {

								if ( $this->p->debug->enabled ) {
									$this->p->debug->log( 'og:' . $mt_suffix . ' meta tag is value empty and required' );
								}

								$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;
								$error_msg  = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

								$this->p->notice->err( $error_msg, null, $notice_key );
							}
						}

						$this->p->notice->unset_ref( WpssoWpMeta::$head_meta_info['og:url'] );

						/**
						 * Check duplicates only when the post is available publicly and we have a valid permalink.
						 */
						if ( current_user_can( 'manage_options' ) ) {

							if ( apply_filters( $this->p->lca . '_check_post_head',
								$this->p->options[ 'plugin_check_head' ], $post_id, $post_obj ) ) {

								$this->check_post_head_duplicates( $post_id, $post_obj );
							}
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
						'wpsso' ), 'post', $action_name ) );

				} else {

					$_SERVER['REQUEST_URI'] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

					switch ( $action_name ) {

						default:

							do_action( $this->p->lca . '_load_meta_page_post_' . $action_name, $post_id, $post_obj );

							break;
					}
				}
			}
		}

		public function check_post_head_duplicates( $post_id = true, $post_obj = false ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$is_admin = is_admin();	// Call the function only once.
			$short    = $this->p->cf[ 'plugin' ][$this->p->lca][ 'short' ];

			if ( empty( $this->p->options['plugin_check_head'] ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: plugin_check_head option is disabled');
				}

				return;	// Stop here.
			}

			if ( ! apply_filters( $this->p->lca . '_add_meta_name_' . $this->p->lca . ':mark', true ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: ' . $this->p->lca . ':mark meta tags are disabled');
				}

				return;	// Stop here.
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
					return;	// Stop here.
				}
			}

			if ( ! is_numeric( $post_id ) ) {	// Just in case the post_id is true/false.

				if ( empty( $post_obj->ID ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'exiting early: post id in post object is empty');
					}

					return;	// Stop here.
				}
				$post_id = $post_obj->ID;
			}

			/**
			 * Only check publicly available posts.
			 */
			if ( ! isset( $post_obj->post_status ) || $post_obj->post_status !== 'publish' ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_status "' . $post_obj->post_status . '" is not publish' );
				}

				return;	// Stop here.
			}

			/**
			 * Only check public post types (to avoid menu items, product variations, etc.).
			 */
			$ptns = $this->p->util->get_post_types( 'names' );

			if ( empty( $post_obj->post_type ) || ! in_array( $post_obj->post_type, $ptns ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: post_type "' . $post_obj->post_type . '" not public' );
				}

				return;	// Stop here.
			}

			$exec_count = $this->p->debug->enabled ? 0 : (int) get_option( WPSSO_POST_CHECK_NAME );		// Cast to change false to 0.
			$max_count  = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT' );

			if ( $exec_count >= $max_count ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: exec_count of ' . $exec_count . ' exceeds max_count of ' . $max_count );
				}

				return;	// Stop here.
			}

			if ( ini_get( 'open_basedir' ) ) {	// Cannot follow redirects.
				$check_url = $this->p->util->get_sharing_url( $post_id, false );	// $add_page is false.
			} else {
				$check_url = SucomUtilWP::wp_get_shortlink( $post_id, 'post' );	// $context is post.
			}

			$check_url_htmlenc = SucomUtil::encode_html_emoji( urldecode( $check_url ) );

			if ( empty( $check_url ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: invalid shortlink' );
				}

				return;	// Stop here.
			}

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'getting html for ' . $check_url );
			}

			if ( $is_admin ) {
				$this->p->notice->inf( sprintf( __( 'Checking %1$s for duplicate meta tags...', 'wpsso' ),
					'<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>' ) );
			}

			/**
			 * Fetch HTML using the Facebook user agent to get Open Graph meta tags.
			 */
			$curl_opts = array( 'CURLOPT_USERAGENT' => WPSSO_PHP_CURL_USERAGENT_FACEBOOK );

			$this->p->cache->clear( $check_url );	// Clear the cached webpage, just in case.

			$exp_secs     = $this->p->debug->enabled ? false : null;
			$webpage_html = $this->p->cache->get( $check_url, 'raw', 'transient', $exp_secs, '', $curl_opts );
			$url_mtime    = $this->p->cache->get_url_mtime( $check_url );

			$warning_time = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_WARNING_TIME', 2.5 );
			$timeout_time = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_TIMEOUT_TIME', 3.0 );

			if ( true === $url_mtime ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched ' . $check_url . ' from transient cache' );
				}

			} elseif ( false === $url_mtime ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched ' . $check_url . ' returned a failure' );
				}

			} else {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'fetched ' . $check_url . ' in ' . $url_mtime . ' secs' );
				}

				if ( is_admin() && $url_mtime > $warning_time ) {
					$this->p->notice->warn(
						sprintf( __( 'Retrieving the HTML document for %1$s took %2$s seconds.', 'wpsso' ),
							'<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>', $url_mtime ) . ' ' . 
						sprintf( __( 'This exceeds the recommended limit of %1$s seconds (crawlers often time-out after %2$s seconds).',
							'wpsso' ), $warning_time, $timeout_time ) . ' ' . 
						__( 'Please consider improving the speed of your site.', 'wpsso' ) . ' ' . 
						__( 'As an added benefit, a faster site will also improve ranking in search results.', 'wpsso' ) . ' ;-)'
					);
				}
			}

			if ( empty( $webpage_html ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: error retrieving webpage from ' . $check_url );
				}

				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'Error retrieving webpage from <a href="%1$s">%1$s</a>.',
						'wpsso' ), $check_url ) );
				}

				return;	// Stop here.

			} elseif ( stripos( $webpage_html, '<html' ) === false ) {	// Webpage must have an <html> tag.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: <html> tag not found in ' . $check_url );
				}

				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'An &lt;html&gt; tag was not found in <a href="%1$s">%1$s</a>.',
						'wpsso' ), $check_url ) );
				}

				return;	// Stop here

			} elseif ( ! preg_match( '/<meta[ \n]/i', $webpage_html ) ) {	// Webpage must have one or more <meta/> tags.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: No <meta/> HTML tags were found in ' . $check_url );
				}

				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'No %1$s HTML tags were found in <a href="%2$s">%2$s</a>.',
						'wpsso' ), '&lt;meta/&gt;', $check_url ) );
				}

				return;	// Stop here.

			} elseif ( false === strpos( $webpage_html, $this->p->lca . ' meta tags begin' ) ) {	// Webpage should include our own meta tags.

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: ' . $this->p->lca . ' meta tag section not found in ' . $check_url );
				}

				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'A %2$s meta tag section was not found in <a href="%1$s">%1$s</a> &mdash; perhaps a webpage caching plugin or service needs to be refreshed?', 'wpsso' ), $check_url, $short ) );
				}

				return;	// Stop here.
			}

			/**
			 * Remove WPSSO meta tags and Schema markup from the webpage html to check for duplicate meta tags and markup.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'removing the ' . $this->p->lca . ' meta tag section from the webpage html' );
			}

			$html_stripped = preg_replace( $this->p->head->get_mt_mark( 'preg' ), '', $webpage_html, -1, $mark_count );

			if ( ! $mark_count ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'exiting early: preg_replace() function failed to remove the meta tag section' );
				}

				if ( $is_admin ) {
					$this->p->notice->err( sprintf( __( 'The PHP preg_replace() function failed to remove the %1$s meta tag section &mdash; this could be an indication of a problem with PHP\'s PCRE library or a webpage filter corrupting the %1$s meta tags.', 'wpsso' ), $short ) );
				}

				return;	// Stop here.
			}

			/**
			 * Check the stripped webpage HTML for ld+json script(s) and if not found, then suggest enabling the WPSSO JSON add-on.
			 */
			$json_info       = $this->p->cf[ 'plugin' ][ 'wpssojson' ];
			$json_addon_link = $this->p->util->get_admin_url( 'addons#wpssojson', $json_info[ 'name' ] );

			if ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) || ! $this->p->check->pp( 'wpssojson' ) ) {

				if ( $this->p->debug->enabled ) {
					$this->p->debug->log( 'checking the stripped webpage html for ld+json script(s)' );
				}

				$scripts_json = SucomUtil::get_json_scripts( $html_stripped, $do_decode = false );	// Return the json encoded containers.

				if ( ! empty( $scripts_json ) && is_array( $scripts_json ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( count( $scripts_json ) . ' application/ld+json script(s) found in the webpage' );
					}

					if ( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) {

						$id_marker = WpssoSchema::get_wc_product_id_marker();

						foreach ( $scripts_json as $script_md5 => $single_json ) {

							if ( strpos( $single_json, $id_marker ) ) {

								if ( $is_admin ) {

									$notice_key = 'application-ld-json-script-wc-product-found';
						
									$notice_msg = sprintf( __( 'The webpage at %1$s includes only basic and incomplete Schema JSON-LD product markup added by the WooCommerce plugin.', 'wpsso' ), '<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>' ) . ' ';

									$notice_msg .= __( 'Complete and accurate Schema JSON-LD markup is highly recommended for better ranking and click-through in Google search results.', 'wpsso' ) . ' ';
						
									$notice_msg .= sprintf( __( 'You should consider purchasing the %1$s Pro version add-on to include better Schema JSON-LD markup for WooCommerce products.', 'wpsso' ), $json_addon_link );

									$this->p->notice->warn( $notice_msg, null, $notice_key, true );
								}

								break;
							}
						}
					}

				} elseif ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'no application/ld+json script(s) found in the webpage' );
					}

					if ( $is_admin ) {

						$notice_key = 'application-ld-json-script-not-found';
	
						$notice_msg = sprintf( __( 'The webpage at %1$s does not include any Schema JSON-LD script(s).', 'wpsso' ), '<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>' ) . ' ';
						
						$notice_msg .= __( 'Complete and accurate Schema JSON-LD markup is highly recommended for better ranking and click-through in Google search results.', 'wpsso' ) . ' ';
						
						$notice_msg .= sprintf( __( 'You should consider activating the %1$s add-on to include Schema JSON-LD markup in the webpage for Google.', 'wpsso' ), $json_addon_link );

						$this->p->notice->warn( $notice_msg, null, $notice_key, true );
					}
				}
			}

			/**
			 * Check the stripped webpage HTML for duplicate html tags.
			 */
			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'checking the stripped webpage html for duplicates' );
			}

			$metas           = $this->p->util->get_head_meta( $html_stripped, '/html/head/link|/html/head/meta', true );	// False on error.
			$check_opts      = SucomUtil::preg_grep_keys( '/^add_/', $this->p->options, false, '' );
			$conflicts_msg   = __( 'Conflict detected &mdash; your theme or another plugin is adding %1$s to the head section of this webpage.', 'wpsso' );
			$conflicts_found = 0;

			if ( is_array( $metas ) ) {

				if ( empty( $metas ) ) {	// No link or meta tags found.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'error parsing head meta for ' . $check_url );
					}

					if ( $is_admin ) {

						$validator_url     = 'https://validator.w3.org/nu/?doc=' . urlencode( $check_url );
						$settings_page_url = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest' );

						$this->p->notice->err( sprintf( __( 'An error occured parsing the head meta tags from <a href="%1$s">%1$s</a>.', 'wpsso' ), $check_url ) . ' ' . sprintf( __( 'The webpage may contain serious HTML syntax errors &mdash; please review the <a href="%1$s">W3C Markup Validation Service</a> results and correct any errors.', 'wpsso' ), $validator_url ) . ' ' . sprintf( __( 'You may safely ignore any "nopin" attribute errors, or disable the "nopin" attribute under the <a href="%s">Pinterest settings tab</a>.', 'wpsso' ), $settings_page_url ) );
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
										! empty( $check_opts[$tag . '_' . $type . '_' . $meta[$type]] ) ) {

										$conflicts_found++;

										$conflicts_tag = '<code>' . $tag . ' ' . $type . '="' . $meta[$type] . '"</code>';

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
					$this->p->debug->log( 'insufficient privileges to add metabox for ' . $post_obj->post_type . ' ID ' . $post_id );
				}

				return;
			}

			$add_metabox = empty( $this->p->options[ 'plugin_add_to_' . $post_obj->post_type ] ) ? false : true;
			$add_metabox = apply_filters( $this->p->lca . '_add_metabox_post', $add_metabox, $post_id, $post_obj->post_type );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'add metabox for post ID ' . $post_id . ' of type ' . $post_obj->post_type . ' is ' . 
					( $add_metabox ? 'true' : 'false' ) );
			}

			if ( $add_metabox ) {

				$metabox_id      = $this->p->cf['meta'][ 'id' ];
				$metabox_title   = _x( $this->p->cf['meta']['title'], 'metabox title', 'wpsso' );
				$metabox_screen  = $post_obj->post_type;
				$metabox_context = 'normal';
				$metabox_prio    = 'default';
				$callback_args   = array(	// Second argument passed to the callback function / method.
					'__block_editor_compatible_meta_box' => true,
				);

				add_meta_box( $this->p->lca . '_' . $metabox_id, $metabox_title,
					array( $this, 'show_metabox_custom_meta' ), $metabox_screen,
						$metabox_context, $metabox_prio, $callback_args );
			}
		}

		public function ajax_metabox_custom_meta() {

			$doing_ajax     = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
			$doing_autosave = defined( 'DOING_AUTOSAVE' ) ? DOING_AUTOSAVE : false;

			if ( ! $doing_ajax ) {
				return;
			} elseif ( $doing_autosave ) {
				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', true );

			if ( empty( $_POST[ 'post_id' ] ) ) {
				die( '-1' );
			}

			$post_id  = $_POST[ 'post_id' ];
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
			 * $read_cache is false to generate notices etc.
			 */
			WpssoWpMeta::$head_meta_tags = $this->p->head->get_head_array( $post_id, $mod, false );
			WpssoWpMeta::$head_meta_info = $this->p->head->extract_head_info( $mod, WpssoWpMeta::$head_meta_tags );

			if ( $mod[ 'post_status' ] === 'publish' ) {

				$this->p->notice->set_ref( WpssoWpMeta::$head_meta_info['og:url'], $mod,
					sprintf( __( 'checking meta tags for %1$s ID %2$s', 'wpsso' ),
						$mod[ 'post_type' ], $mod[ 'id' ] ) );


				/**
				 * Check for missing open graph image and description values.
				 */
				foreach ( array( 'image', 'description' ) as $mt_suffix ) {

					if ( empty( WpssoWpMeta::$head_meta_info['og:' . $mt_suffix] ) ) {

						$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;
						$error_msg  = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

						$this->p->notice->err( $error_msg, null, $notice_key );
					}
				}

				$this->p->notice->unset_ref( WpssoWpMeta::$head_meta_info['og:url'] );
			}

			$metabox_html = $this->get_metabox_custom_meta( $post_obj );

			die( $metabox_html );
		}

		public function show_metabox_custom_meta( $post_obj ) {
			echo $this->get_metabox_custom_meta( $post_obj );
		}

		public function get_metabox_custom_meta( $post_obj ) {

			$metabox_id = $this->p->cf['meta'][ 'id' ];
			$mod        = $this->get_mod( $post_obj->ID );
			$tabs       = $this->get_custom_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $post_obj->ID );
			$def_opts   = $this->get_defaults( $post_obj->ID );
			$doing_ajax = defined( 'DOING_AJAX' ) ? DOING_AJAX : false;

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->lca );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark( $metabox_id . ' table rows' );	// Start timer.
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

		protected function get_table_rows( $metabox_id, $tab_key, $head, $mod ) {

			$is_auto_draft  = empty( $mod[ 'post_status' ] ) || $mod[ 'post_status' ] === 'auto-draft' ? true : false;
			$auto_draft_msg = sprintf( __( 'Save a draft version or publish the %s to display these options.', 'wpsso' ),
				SucomUtil::titleize( $mod[ 'post_type' ] ) );

			$table_rows = array();

			switch ( $tab_key ) {

				case 'preview':

					$table_rows = $this->get_rows_preview_tab( $this->form, $head, $mod );

					break;

				case 'head':

					if ( $is_auto_draft ) {
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">' .
							$auto_draft_msg . '</p></blockquote></td>';
					} else {
						$table_rows = $this->get_rows_head_tab( $this->form, $head, $mod );
					}

					break;

				case 'validate':

					if ( $is_auto_draft ) {
						$table_rows[] = '<td><blockquote class="status-info"><p class="centered">' .
							$auto_draft_msg . '</p></blockquote></td>';
					} else {
						$table_rows = $this->get_rows_validate_tab( $this->form, $head, $mod );
					}

					break;
			}

			return $table_rows;
		}

		public function clear_cache_for_new_comment( $comment_id, $comment_approved ) {

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

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

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

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

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}

			$post_status = get_post_status( $post_id );

			switch ( $post_status ) {

				case 'draft':
				case 'pending':
				case 'future':
				case 'private':
				case 'publish':

					break;	// Cache clearing allowed.

				case 'auto-draft':
				case 'trash':
				default:

					return;	// Stop here.
			}

			$mod           = $this->get_mod( $post_id );
			$permalink     = get_permalink( $post_id );
			$col_meta_keys = WpssoWpMeta::get_column_meta_keys();
			$cache_types   = array();
			$cache_md5_pre = $this->p->lca . '_';

			foreach ( $col_meta_keys as $col_key => $meta_key ) {
				delete_post_meta( $post_id, $meta_key );
			}

			if ( ini_get( 'open_basedir' ) ) {
				$check_url = $this->p->util->get_sharing_url( $post_id, false );	// $add_page = false
			} else {
				$check_url = SucomUtilWP::wp_get_shortlink( $post_id, 'post' );	// $context = post
			}

			$cache_types['transient'][] = array(
				'id'   => $cache_md5_pre . md5( 'SucomCache::get(url:' . $permalink . ')' ),
				'pre'  => $cache_md5_pre,
				'salt' => 'SucomCache::get(url:' . $permalink . ')',
			);

			if ( $permalink !== $check_url ) {
				$cache_types['transient'][] = array(
					'id'   => $cache_md5_pre . md5( 'SucomCache::get(url:' . $check_url . ')' ),
					'pre'  => $cache_md5_pre,
					'salt' => 'SucomCache::get(url:' . $check_url . ')',
				);
			}

			$this->clear_mod_cache_types( $mod, $cache_types );

			/**
			 * Clear the post terms (categories, tags, etc.) for published (aka public) posts.
			 */
			if ( $post_status === 'publish' ) {

				if ( ! empty( $this->p->options['plugin_clear_post_terms'] ) ) {

					$post_taxonomies = get_post_taxonomies( $post_id );

					foreach ( $post_taxonomies as $tax_slug ) {
		
						$post_terms = wp_get_post_terms( $post_id, $tax_slug );
		
						foreach ( $post_terms as $post_term ) {
							$this->p->m[ 'util' ][ 'term' ]->clear_cache( $post_term->term_id, $post_term->term_taxonomy_id );
						}
					}
				}
			}
		}

		public function show_robots_options( $post ) {

			if ( empty( $post->ID ) ) {	// Just in case.
				return;
			}

			$post_type        = $post->post_type;
			$post_type_object = get_post_type_object( $post_type );
			$can_publish      = current_user_can( $post_type_object->cap->publish_posts );
			$mod              = $this->get_mod( $post->ID );
			$robots_content   = $this->p->util->get_robots_content( $mod );
			$robots_css_id    = $this->p->lca . '-robots';

			echo "\n";
			echo '<!-- ' .  $this->p->lca . ' nonce fields -->' . "\n";
			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );	// WPSSO_NONCE_NAME is an md5() string
			echo "\n";
			echo '<div class="misc-pub-section misc-pub-robots sucom-sidebox ' . $robots_css_id . '-options" id="post-' . $robots_css_id . '">' . "\n";

			echo '<div id="post-' . $robots_css_id . '-label">';
			echo _x( 'Robots', 'option label', 'wpsso' ) . ':';
			echo '</div><!-- #post-' . $robots_css_id . '-label -->' . "\n";

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

			if ( ! $post_type = SucomUtil::get_request_value( 'post_type', 'POST' ) ) {	// Uses sanitize_text_field.
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

			static $reviews_max = null;

			if ( null === $reviews_max ) {	// Only set the value once.
				$reviews_max = SucomUtil::get_const( 'WPSSO_SCHEMA_REVIEWS_PER_PAGE_MAX', 30 );
			}

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

				if ( count( $ret ) > $reviews_max ) {
					if ( $wpsso->debug->enabled ) {
						$wpsso->debug->log( count( $ret ) . ' reviews found (adjusted to ' . $reviews_max . ')' );
					}
					$ret = array_slice( $ret, 0, $reviews_max );
				}
			}

			return $ret;
		}

		public function get_og_review_mt( $comment_obj, $og_type = 'product', $rating_meta = 'rating' ) {

			$ret = array();
			$rating_value = (float) get_comment_meta( $comment_obj->comment_ID, $rating_meta, true );

			$ret[$og_type . ':review:id']           = $comment_obj->comment_ID;
			$ret[$og_type . ':review:url']          = get_comment_link( $comment_obj->comment_ID );
			$ret[$og_type . ':review:author:id']    = $comment_obj->user_id;	// Author ID if registered (0 otherwise).
			$ret[$og_type . ':review:author:name']  = $comment_obj->comment_author;	// Author display name.
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

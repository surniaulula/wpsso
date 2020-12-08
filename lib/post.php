<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoPost' ) ) {

	class WpssoPost extends WpssoWpMeta {

		private static $saved_shortlink_url = null;	// Used by get_sharing_shortlink() and maybe_restore_shortlink().
		private static $cache_shortlinks    = array();	// Used by get_sharing_shortlink() and maybe_restore_shortlink().

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Maybe enable WP post excerpt for pages.
			 */
			if ( ! empty( $this->p->options[ 'plugin_page_excerpt' ] ) ) {

				add_post_type_support( 'page', array( 'excerpt' ) );
			}

			/**
			 * Maybe enable WP post tags for pages.
			 */
			if ( ! empty( $this->p->options[ 'plugin_page_tags' ] ) ) {

				register_taxonomy_for_object_type( 'post_tag', 'page' );
			}

			add_action( 'wp_loaded', array( $this, 'add_wp_hooks' ) );
		}

		/**
		 * Add WordPress action and filters hooks.
		 */
		public function add_wp_hooks() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$is_admin = is_admin();	// Only check once.

			$doing_ajax = SucomUtil::get_const( 'DOING_AJAX' );

			if ( $is_admin ) {

				$metabox_id   = $this->p->cf[ 'meta' ][ 'id' ];

				$mb_container_id = 'wpsso_metabox_' . $metabox_id . '_inside';

				add_action( 'wp_ajax_get_container_id_' . $mb_container_id, array( $this, 'ajax_get_metabox_document_meta' ) );

				if ( ! empty( $_GET ) || basename( $_SERVER[ 'PHP_SELF' ] ) === 'post-new.php' ) {

					/**
					 * load_meta_page() priorities: 100 post, 200 user, 300 term.
					 *
					 * Sets the parent::$head_tags and parent::$head_info class properties.
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 100, 1 );

					add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
				}

				/**
				 * The 'save_post' action is run after other post type specific actions, so we can use it to save
				 * post meta for any post type.
				 */
				add_action( 'save_post', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );	// Default is -100.

				/**
				 * Don't hook the 'clean_post_cache' action since 'save_post' is run after 'clean_post_cache' and
				 * our custom post meta has not been saved yet.
				 */
				add_action( 'save_post', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );	// Default is -10.

				/**
				 * The wp_insert_post() function returns after running the 'edit_attachment' action, so the
				 * 'save_post' action is never run for attachments.
				 */
				add_action( 'edit_attachment', array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY );	// Default is -100.
				add_action( 'edit_attachment', array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY );	// Default is -10.
			}

			/**
			 * Add the columns when doing AJAX as well to allow Quick Edit to add the required columns.
			 */
			if ( $is_admin || $doing_ajax ) {

				$post_type_names = SucomUtilWP::get_post_types( 'names' );

				if ( is_array( $post_type_names ) ) {

					foreach ( $post_type_names as $post_type_name ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding column filters for post type ' . $post_type_name );
						}

						/**
						 * See https://codex.wordpress.org/Plugin_API/Filter_Reference/manage_$post_type_posts_columns.
						 */
						add_filter( 'manage_' . $post_type_name . '_posts_columns', array( $this, 'add_post_column_headings' ),
							WPSSO_ADD_COLUMN_PRIORITY, 1 );

						add_filter( 'manage_edit-' . $post_type_name . '_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );

						/**
						 * See https://codex.wordpress.org/Plugin_API/Action_Reference/manage_$post_type_posts_custom_column.
						 */
						add_action( 'manage_' . $post_type_name . '_posts_custom_column', array( $this, 'show_column_content' ), 10, 2 );
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding column filters for media library' );
				}

				add_filter( 'manage_media_columns', array( $this, 'add_media_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );

				add_filter( 'manage_upload_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );

				add_action( 'manage_media_custom_column', array( $this, 'show_column_content' ), 10, 2 );

				/**
				 * The 'parse_query' action is hooked once in the WpssoPost class to set the column orderby for
				 * post, term, and user edit tables.
				 */
				add_action( 'parse_query', array( $this, 'set_column_orderby' ), 10, 1 );

				add_action( 'get_post_metadata', array( $this, 'check_sortable_post_metadata' ), 10, 4 );
			}

			if ( ! empty( $this->p->options[ 'plugin_shortener' ] ) && $this->p->options[ 'plugin_shortener' ] !== 'none' ) {

				if ( ! empty( $this->p->options[ 'plugin_wp_shortlink' ] ) ) {	// Use Shortened URL for WP Shortlink.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding pre_get_shortlink filters to shorten the sharing url' );
					}

					$min_int = SucomUtil::get_min_int();
					$max_int = SucomUtil::get_max_int();

					add_filter( 'pre_get_shortlink', array( $this, 'get_sharing_shortlink' ), $min_int, 4 );
					add_filter( 'pre_get_shortlink', array( $this, 'maybe_restore_shortlink' ), $max_int, 4 );

					if ( function_exists( 'wpme_get_shortlink_handler' ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'removing the jetpack pre_get_shortlink filter hook' );
						}

						remove_filter( 'pre_get_shortlink', 'wpme_get_shortlink_handler', 1 );
					}
				}
			}

			if ( ! empty( $this->p->options[ 'plugin_clear_for_comment' ] ) ) {

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

		/**
		 * Get the $mod object for a post ID.
		 */
		public function get_mod( $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $post_id ] ) ) {

				return $local_cache[ $post_id ];
			}

			$mod = parent::$mod_defaults;

			/**
			 * Common elements.
			 */
			$mod[ 'id' ]          = is_numeric( $post_id ) ? (int) $post_id : 0;	// Cast as integer.
			$mod[ 'name' ]        = 'post';
			$mod[ 'name_transl' ] = _x( 'post', 'module name', 'wpsso' );
			$mod[ 'obj' ]         =& $this;

			/**
			 * Post elements.
			 */
			$mod[ 'is_post' ]       = true;
			$mod[ 'is_home_page' ]  = SucomUtil::is_home_page( $post_id );
			$mod[ 'is_home_posts' ] = $mod[ 'is_home_page' ] ? false : SucomUtil::is_home_posts( $post_id );
			$mod[ 'is_home' ]       = $mod[ 'is_home_page' ] || $mod[ 'is_home_posts' ] ? true : false;

			if ( $mod[ 'id' ] ) {	// Just in case.

				$mod[ 'post_slug' ]            = get_post_field( 'post_name', $mod[ 'id' ] );		// Post name (aka slug).
				$mod[ 'post_type' ]            = get_post_type( $mod[ 'id' ] );				// Post type name.
				$mod[ 'post_mime' ]            = get_post_mime_type( $mod[ 'id' ] );			// Post mime type (ie. image/jpg).
				$mod[ 'post_status' ]          = get_post_status( $mod[ 'id' ] );			// Post status name.
				$mod[ 'post_author' ]          = (int) get_post_field( 'post_author', $mod[ 'id' ] );	// Post author id.
				$mod[ 'post_coauthors' ]       = array();
				$mod[ 'post_time' ]            = get_post_time( 'c', $gmt = true, $mod[ 'id' ] );		// Returns false on failure.
				$mod[ 'post_modified_time' ]   = get_post_modified_time( 'c', $gmt = true, $mod[ 'id' ] );	// Returns false on failure.
				$mod[ 'is_post_type_archive' ] = SucomUtil::is_post_type_archive( $mod[ 'post_type' ], $mod[ 'post_slug' ] );

				if ( $post_type_object = get_post_type_object( $mod[ 'post_type' ] ) ) {

					if ( isset( $post_type_object->labels->singular_name ) ) {

						$mod[ 'post_type_label' ] = $post_type_object->labels->singular_name;
					}

					if ( isset( $post_type_object->public ) ) {

						$mod[ 'is_public' ] = $post_type_object->public ? true : false;
					}
				}
			}

			/**
			 * Hooked by the 'coauthors' pro module.
			 */
			return $local_cache[ $post_id ] = apply_filters( 'wpsso_get_post_mod', $mod, $post_id );
		}

		/**
		 * Check if the post type requires a specific hard-coded Open Graph type.
		 *
		 * For example, a post type 'organization' would return 'website' for the Open Graph type.
		 *
		 * Returns false or an Open Graph type string.
		 */
		public function get_post_type_og_type( $mod ) {

			static $local_cache = array();	// Cache for single page load.

			$mod_salt = SucomUtil::get_mod_salt( $mod );

			if ( isset( $local_cache[ $mod_salt ] ) ) {

				return $local_cache[ $mod_salt ];
			}

			/**
			 * Hard-code the Open Graph type based on the WordPress post type.
			 */
			if ( ! empty( $mod[ 'post_type' ] ) ) {

				if ( ! empty( $this->p->cf[ 'head' ][ 'og_type_by_post_type' ][ $mod[ 'post_type' ] ] ) ) {

					return $local_cache[ $mod_salt ] = $this->p->cf[ 'head' ][ 'og_type_by_post_type' ][ $mod[ 'post_type' ] ];
				}
			}

			return $local_cache[ $mod_salt ] = false;
		}

		/**
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_options( $post_id, $md_key = false, $filter_opts = true, $pad_opts = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'post_id'     => $post_id,
					'md_key'      => $md_key,
					'filter_opts' => $filter_opts,
					'pad_opts'    => $pad_opts,
				) );
			}

			static $local_cache = array();

			/**
			 * Use $post_id and $filter_opts to create the cache ID string, but do not add $pad_opts.
			 */
			$cache_id = SucomUtil::get_assoc_salt( array( 'id' => $post_id, 'filter' => $filter_opts ) );

			/**
			 * Maybe initialize the cache.
			 */
			if ( ! isset( $local_cache[ $cache_id ] ) ) {

				$local_cache[ $cache_id ] = false;

			} elseif ( $this->md_cache_disabled ) {

				$local_cache[ $cache_id ] = false;
			}

			$md_opts =& $local_cache[ $cache_id ];	// Shortcut variable name.

			if ( false === $md_opts ) {

				$md_opts = get_post_meta( $post_id, WPSSO_META_NAME, $single = true );

				if ( ! is_array( $md_opts ) ) {

					$md_opts = array();
				}

				/**
				 * Check if options need to be upgraded and saved.
				 *
				 * Returns true or false.
				 *
				 * $md_opts is passed by reference so the array can be modified.
				 */
				if ( $this->upgrade_options( $md_opts, $post_id ) ) {

					/**
					 * Save the upgraded options.
					 */
					update_post_meta( $post_id, WPSSO_META_NAME, $md_opts );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'post_id ' . $post_id . ' settings upgraded' );
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'post_id ' . $post_id . ' meta options read', $md_opts );
				}
			}

			if ( $filter_opts ) {

				if ( empty( $md_opts[ 'options_filtered' ] ) ) {

					$md_opts[ 'options_filtered' ] = 1;	// Set before calling filters to prevent recursion.

					$mod = $this->get_mod( $post_id );

					/**
					 * The 'import_custom_fields' filter is executed before the 'wpsso_get_md_options' and
					 * 'wpsso_get_post_options' filters, so values retrieved from custom fields may get
					 * overwritten by later filters.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying import_custom_fields filters for post ID ' . $post_id . ' metadata' );
					}

					$md_opts = apply_filters( 'wpsso_import_custom_fields', $md_opts, get_post_meta( $post_id ) );

					/**
					 * Since WPSSO Core v7.1.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying get_md_options filters' );
					}

					$md_opts = (array) apply_filters( 'wpsso_get_md_options', $md_opts, $mod );

					/**
					 * Since WPSSO Core v4.31.0.
					 *
					 * Hooked by several integration modules to provide information about the current content.
					 * e-Commerce integration modules will provide information on their product (price,
					 * condition, etc.) and disable these options in the Document SSO metabox.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying get_post_options filters for post ID ' . $post_id . ' metadata' );
					}

					$md_opts = (array) apply_filters( 'wpsso_get_post_options', $md_opts, $post_id, $mod );

					/**
					 * Since WPSSO Core v8.2.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying sanitize_md_options filters' );
					}

					$md_opts = apply_filters( 'wpsso_sanitize_md_options', $md_opts, $mod );
				}
			}

			return $this->return_options( $post_id, $md_opts, $md_key, $pad_opts );
		}

		public function save_options( $post_id, $rel_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! $this->user_can_save( $post_id, $rel_id ) ) {

				return;
			}

			$this->md_cache_disabled = true;	// Disable local cache for get_defaults() and get_options().

			$mod = $this->get_mod( $post_id );

			$opts = $this->get_submit_opts( $post_id );

			/**
			 * Just in case - do not save the SEO description if an SEO plugin is active.
			 */
			if ( ! empty( $this->p->avail[ 'seo' ][ 'any' ] ) ) {

				unset( $opts[ 'seo_desc' ] );
			}

			$opts = apply_filters( 'wpsso_save_md_options', $opts, $mod );

			$opts = apply_filters( 'wpsso_save_post_options', $opts, $post_id, $rel_id, $mod );

			if ( empty( $opts ) ) {

				return delete_post_meta( $post_id, WPSSO_META_NAME );

			}

			return update_post_meta( $post_id, WPSSO_META_NAME, $opts );
		}

		public function delete_options( $post_id, $rel_id = false ) {

			return delete_post_meta( $post_id, WPSSO_META_NAME );
		}

		/**
		 * Get all publicly accessible post IDs.
		 *
		 * These may include post IDs from non-public post types.
		 */
		public static function get_public_ids() {

			$posts_args = array(
				'has_password'   => false,
				'order'          => 'DESC',	// Newest first.
				'orderby'        => 'date',
				'paged'          => false,
				'post_status'    => 'publish',	// Only 'publish' (not 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or 'trash').
				'post_type'      => 'any',	// Return any post, page, or custom post type.
				'posts_per_page' => -1,		// The number of posts to query for. -1 to request all posts.
				'fields'         => 'ids',	// Return an array of post IDs.
				'no_found_rows'  => true,	// Skip counting total rows found - should be enabled when pagination is not needed.
			);

			return get_posts( $posts_args );
		}

		/**
		 * Return an array of post IDs for a given $mod object.
		 */
		public function get_posts_ids( array $mod, $ppp = null, $paged = null, array $posts_args = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( null === $ppp ) {

				$ppp = apply_filters( 'wpsso_posts_per_page', get_option( 'posts_per_page' ), $mod );
			}

			if ( null === $paged ) {

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
				'order'          => 'DESC',		// Newest first.
				'orderby'        => 'date',
				'paged'          => $paged,
				'post_status'    => 'publish',		// Only 'publish', not 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or 'trash'.
				'post_type'      => 'any',		// Return post, page, or any custom post type.
				'posts_per_page' => $ppp,
				'post_parent'    => $mod[ 'id' ],
				'child_of'       => $mod[ 'id' ],	// Only include direct children.
			), $posts_args, array( 'fields' => 'ids' ) );	// Return an array of post IDs.

			$mtime_max   = SucomUtil::get_const( 'WPSSO_GET_POSTS_MAX_TIME', 0.10 );
			$mtime_start = microtime( true );
			$post_ids    = get_posts( $posts_args );
			$mtime_total = microtime( true ) - $mtime_start;

			if ( $mtime_max > 0 && $mtime_total > $mtime_max ) {

				$info = $this->p->cf[ 'plugin' ][ $this->p->id ];

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( sprintf( 'slow query detected - WordPress get_posts() took %1$0.3f secs' . 
						' to get the children of post ID %2$d', $mtime_total, $mod[ 'id' ] ) );
				}

				$error_pre   = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$0.3f secs', 'wpsso' ), $mtime_max );
				$error_msg   = sprintf( __( 'Slow query detected - get_posts() took %1$0.3f secs to get the children of post ID %2$d (%3$s).',
					'wpsso' ), $mtime_total, $mod[ 'id' ], $rec_max_msg );

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->warn( $error_msg );
				}

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( count( $post_ids ) . ' post IDs returned in ' . sprintf( '%0.3f secs', $mtime_total ) );
			}

			return $post_ids;
		}

		public function add_post_column_headings( $columns ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->add_column_headings( $columns, $list_type = 'post' );
		}

		public function add_media_column_headings( $columns ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->add_column_headings( $columns, $list_type = 'media' );
		}

		public function show_column_content( $column_name, $post_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $column_name . ' for post ID ' . $post_id );
			}

			echo $this->get_column_content( '', $column_name, $post_id );
		}

		public function get_column_content( $value, $column_name, $post_id ) {

			if ( ! empty( $post_id ) && 0 === strpos( $column_name, 'wpsso_' ) ) {	// Just in case.

				$col_key = str_replace( 'wpsso_', '', $column_name );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting column value for ' . $col_key );
				}

				if ( null !== ( $col_info = self::get_sortable_columns( $col_key ) ) ) {

					if ( isset( $col_info[ 'meta_key' ] ) ) {	// Just in case.

						$value = $this->get_meta_cache_value( $post_id, $col_info[ 'meta_key' ] );
					}

					if ( isset( $col_info[ 'post_callbacks' ] ) && is_array( $col_info[ 'post_callbacks' ] ) ) {

						foreach( $col_info[ 'post_callbacks' ] as $input_name => $input_callback ) {

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

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * WordPress stores data using a post, term, or user ID, along with a group string.
			 *
			 * Example: wp_cache_get( 1, 'user_meta' );
			 *
			 * Returns (bool|mixed) false on failure to retrieve contents or the cache contents on success.
			 *
			 * $found (bool) (Optional) whether the key was found in the cache (passed by reference). Disambiguates a
			 * return of false, a storable value. Default null.
			 */
			$meta_cache = wp_cache_get( $post_id, 'post_meta', $force = false, $found );	// Optimize and check wp_cache first.

			if ( isset( $meta_cache[ $meta_key ][ 0 ] ) ) {

				$value = (string) maybe_unserialize( $meta_cache[ $meta_key ][ 0 ] );

			} else {

				$value = (string) get_post_meta( $post_id, $meta_key, $single = true );
			}

			if ( 'none' === $value ) {

				$value = $none;
			}

			return $value;
		}

		public function check_sortable_post_metadata( $value, $post_id, $meta_key, $single ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->check_sortable_metadata( $value, $post_id, $meta_key, $single );
		}

		/**
		 * Hooked into the current_screen action.
		 *
		 * Sets the parent::$head_tags and parent::$head_info class properties.
		 */
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * All meta modules set this property, so use it to optimize code execution.
			 */
			if ( false !== parent::$head_tags || ! isset( $screen->id ) ) {

				return;
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'screen id = ' . $screen->id );
			}

			switch ( $screen->id ) {

				case 'upload':
				case ( 0 === strpos( $screen->id, 'edit-' ) ? true : false ):	// Posts list table.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: not a recognized post page' );
					}

					return;
			}

			/**
			 * Get the post object for sanity checks.
			 */
			$post_obj = SucomUtil::get_post_object( true );

			$post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'post ID = ' . $post_id );
			}

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
				$this->p->debug->log( SucomUtil::pretty_array( $mod ) );
			}

			parent::$head_tags = array();

			if ( $post_obj->post_status === 'auto-draft' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'head meta skipped: post_status is auto-draft' );
				}

			} elseif ( $post_obj->post_status === 'trash' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'head meta skipped: post_status is trash' );
				}

			} elseif ( isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] === 'trash' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'head meta skipped: post is being trashed' );
				}

			} elseif ( SucomUtilWP::doing_block_editor() && ( ! empty( $_REQUEST[ 'meta-box-loader' ] ) || ! empty( $_REQUEST[ 'meta_box' ] ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'head meta skipped: doing block editor for meta box' );
				}

			} elseif ( ! empty( $this->p->options[ 'plugin_add_to_' . $post_obj->post_type ] ) ) {

				/**
				 * Hooked by woocommerce module to load front-end libraries and start a session.
				 */
				do_action( 'wpsso_admin_post_head', $mod );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'setting head_meta_info static property' );
				}

				/**
				 * $read_cache is false to generate notices etc.
				 */
				parent::$head_tags = $this->p->head->get_head_array( $post_id, $mod, $read_cache = false );

				parent::$head_info = $this->p->head->extract_head_info( $mod, parent::$head_tags );

				/**
				 * Check for missing open graph image and description values.
				 */
				if ( $mod[ 'is_public' ] && 'publish' === $mod[ 'post_status' ] ) {

					$ref_url = empty( parent::$head_info[ 'og:url' ] ) ? null : parent::$head_info[ 'og:url' ];

					$ref_url = $this->p->util->maybe_set_ref( $ref_url, $mod, __( 'checking meta tags', 'wpsso' ) );

					foreach ( array( 'image', 'description' ) as $mt_suffix ) {

						if ( empty( parent::$head_info[ 'og:' . $mt_suffix ] ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'og:' . $mt_suffix . ' meta tag is value empty and required' );
							}

							if ( $this->p->notice->is_admin_pre_notices() ) {

								$notice_msg = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

								$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;

								$this->p->notice->err( $notice_msg, null, $notice_key );
							}
						}
					}

					$this->p->util->maybe_unset_ref( $ref_url );

					/**
					 * Check duplicates only when the post is available publicly and we have a valid permalink.
					 */
					if ( current_user_can( 'manage_options' ) ) {

						$check_head = empty( $this->p->options[ 'plugin_check_head' ] ) ? false : true;

						if ( apply_filters( 'wpsso_check_post_head', $check_head, $post_id, $post_obj ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'checking post head' );
							}

							$this->check_post_head( $post_id, $post_obj );
						}
					}
				}
			}

			$action_query = 'wpsso-action';

			if ( ! empty( $_GET[ $action_query ] ) ) {

				$action_name = SucomUtil::sanitize_hookname( $_GET[ $action_query ] );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'found action query: ' . $action_name );
				}

				if ( empty( $_GET[ WPSSO_NONCE_NAME ] ) ) {	// WPSSO_NONCE_NAME is an md5() string

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'nonce token query field missing' );
					}

				} elseif ( ! wp_verify_nonce( $_GET[ WPSSO_NONCE_NAME ], WpssoAdmin::get_nonce_action() ) ) {

					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".', 'wpsso' ), 'post', $action_name ) );

				} else {

					$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

					switch ( $action_name ) {

						default:

							do_action( 'wpsso_load_meta_page_post_' . $action_name, $post_id, $post_obj );

							break;
					}
				}
			}
		}

		public function check_post_head( $post_id = true, $post_obj = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
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

						$this->p->debug->log( 'exiting early: post ID in post object is empty');
					}

					return;	// Stop here.
				}

				$post_id = $post_obj->ID;
			}

			static $do_once = array();

			if ( isset( $do_once[ $post_id ] ) ) {

				return;	// Stop here.
			}

			$do_once[ $post_id ] = true;

			/**
			 * Only check publicly available posts.
			 */
			if ( ! isset( $post_obj->post_status ) || 'publish' !== $post_obj->post_status ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post_status "' . $post_obj->post_status . '" is not publish' );
				}

				return;	// Stop here.
			}

			if ( empty( $post_obj->post_type ) || SucomUtilWP::is_post_type_public( $post_obj->post_type ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post_type "' . $post_obj->post_type . '" not public' );
				}

				return;	// Stop here.
			}

			$exec_count = $this->p->debug->enabled ? 0 : (int) get_option( WPSSO_POST_CHECK_COUNT_NAME, $default = 0 );
			$max_count  = SucomUtil::get_const( 'WPSSO_DUPE_CHECK_HEADER_COUNT', 10 );

			if ( $exec_count >= $max_count ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: exec_count of ' . $exec_count . ' exceeds max_count of ' . $max_count );
				}

				return;	// Stop here.
			}

			if ( ini_get( 'open_basedir' ) ) {	// Cannot follow redirects.

				$check_url = $this->p->util->get_sharing_url( $post_id, $add_page = false );

			} else {

				$check_url = SucomUtilWP::wp_get_shortlink( $post_id, $context = 'post' );
			}

			$check_url_htmlenc = SucomUtil::encode_html_emoji( urldecode( $check_url ) );

			if ( empty( $check_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: invalid shortlink' );
				}

				return;	// Stop here.
			}

			/**
			 * Fetch the post HTML.
			 */
			$is_admin = is_admin();	// Call the function only once.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting html for ' . $check_url );
			}

			if ( $is_admin ) {

				$this->p->notice->inf( sprintf( __( 'Checking %1$s for duplicate meta tags...', 'wpsso' ),
					'<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>' ) );
			}

			/**
			 * Use the Facebook user agent to get Open Graph meta tags.
			 */
			$curl_opts = array(
				'CURLOPT_USERAGENT' => WPSSO_PHP_CURL_USERAGENT_FACEBOOK,
			);

			$this->p->cache->clear( $check_url );	// Clear the cached webpage, just in case.

			$exp_secs     = $this->p->debug->enabled ? false : null;
			$webpage_html = $this->p->cache->get( $check_url, $format = 'raw', $cache_type = 'transient', $exp_secs, $cache_ext = '', $curl_opts );
			$url_mtime    = $this->p->cache->get_url_mtime( $check_url );
			$html_size    = strlen( $webpage_html );
			$error_size   = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_ERROR_SIZE', 2500000 );
			$warning_time = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_WARNING_TIME', 2.5 );
			$timeout_time = (int) SucomUtil::get_const( 'WPSSO_DUPE_CHECK_TIMEOUT_TIME', 3.0 );

			if ( $html_size > $error_size ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'size of ' . $check_url . ' is ' . $html_size . ' bytes' );
				}

				if ( $is_admin && ! $this->p->debug->enabled ) {

					$this->p->notice->err(
						sprintf( __( 'The webpage HTML retrieved from %1$s is %2$s bytes.', 'wpsso' ),
							'<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>', $html_size ) . ' ' . 
						sprintf( __( 'This exceeds the maximum limit of %1$s bytes imposed by the Google crawler.', 'wpsso' ), $error_size ) . ' ' . 
						__( 'If you do not reduce the webpage HTML size, Google will refuse to crawl this webpage.', 'wpsso' )
					);
				}
			}

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

				if ( $is_admin && $url_mtime > $warning_time ) {

					$this->p->notice->warn(
						sprintf( __( 'Retrieving the webpage HTML for %1$s took %2$s seconds.', 'wpsso' ),
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

					$this->p->debug->log( 'exiting early: error retrieving content from ' . $check_url );
				}

				if ( $is_admin ) {

					$this->p->notice->err( sprintf( __( 'Error retrieving content from <a href="%1$s">%1$s</a>.', 'wpsso' ), $check_url ) );
				}

				return;	// Stop here.

			} elseif ( stripos( $webpage_html, '<html' ) === false ) {	// Webpage must have an <html> tag.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: <html> tag not found in ' . $check_url );
				}

				if ( $is_admin ) {

					$this->p->notice->err( sprintf( __( 'An %1$s tag was not found in <a href="%2$s">%2$s</a>.', 'wpsso' ),
						'&lt;html&gt;', $check_url ) );
				}

				return;	// Stop here

			} elseif ( ! preg_match( '/<meta[ \n]/i', $webpage_html ) ) {	// Webpage must have one or more <meta/> tags.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: No <meta/> HTML tags were found in ' . $check_url );
				}

				if ( $is_admin ) {

					$this->p->notice->err( sprintf( __( 'No %1$s HTML tags were found in <a href="%2$s">%2$s</a>.', 'wpsso' ),
						'&lt;meta/&gt;', $check_url ) );
				}

				return;	// Stop here.

			} elseif ( false === strpos( $webpage_html, 'wpsso meta tags begin' ) ) {	// Webpage should include our own meta tags.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: wpsso meta tag section not found in ' . $check_url );
				}

				if ( $is_admin ) {

					$short_name = $this->p->cf[ 'plugin' ][ $this->p->id ][ 'short' ];

					$this->p->notice->err( sprintf( __( 'A %2$s meta tag section was not found in <a href="%1$s">%1$s</a> &mdash; perhaps a webpage caching plugin or service needs to be refreshed?', 'wpsso' ), $check_url, $short_name ) );
				}

				return;	// Stop here.
			}

			/**
			 * Remove the WPSSO meta tag and Schema markup section from the webpage to check for duplicate meta tags and markup.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'removing the wpsso meta tag section from the webpage html' );
			}

			$html_stripped = preg_replace( $this->p->head->get_mt_mark( 'preg' ), '', $webpage_html, -1, $mark_count );

			if ( ! $mark_count ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: preg_replace() function failed to remove the meta tag section' );
				}

				if ( $is_admin ) {

					$short_name = $this->p->cf[ 'plugin' ][ $this->p->id ][ 'short' ];

					$this->p->notice->err( sprintf( __( 'The PHP preg_replace() function failed to remove the %1$s meta tag section &mdash; this could be an indication of a problem with PHP\'s PCRE library or a webpage filter corrupting the %1$s meta tags.', 'wpsso' ), $short_name ) );
				}

				return;	// Stop here.
			}

			/**
			 * Check the stripped webpage HTML for ld+json script(s) and if not found, then suggest enabling the WPSSO JSON add-on.
			 */
			if ( empty( $this->p->avail[ 'p' ][ 'schema' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema markup is disabled' );
				}

			} elseif ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking the stripped webpage html for ld+json script(s)' );
				}

				$scripts_json = SucomUtil::get_json_scripts( $html_stripped, $do_decode = false );	// Return the json encoded containers.

				if ( ! empty( $scripts_json ) && is_array( $scripts_json ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( count( $scripts_json ) . ' application/ld+json script(s) found in the webpage' );
					}

					// Nothing to do.

				} elseif ( empty( $this->p->avail[ 'p_ext' ][ 'json' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'no application/ld+json script(s) found in the webpage' );
					}

					if ( $is_admin ) {

						$json_addon_link = $this->p->util->get_admin_url( 'addons#wpssojson', $this->p->cf[ 'plugin' ][ 'wpssojson' ][ 'name' ] );

						$notice_msg = sprintf( __( 'The webpage at %1$s does not include any Schema JSON-LD script(s).', 'wpsso' ), '<a href="' . $check_url . '">' . $check_url_htmlenc . '</a>' ) . ' ';

						$notice_msg .= __( 'Complete and accurate Schema JSON-LD markup is highly recommended for better ranking and click-through rates in search results.', 'wpsso' ) . ' ';

						$notice_msg .= sprintf( __( 'Consider activating the %1$s add-on to include Schema JSON-LD markup for Google Rich Results.', 'wpsso' ), $json_addon_link );

						$notice_key = 'application-ld-json-script-not-found';

						$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
					}
				}
			}

			/**
			 * Check the stripped webpage HTML for duplicate html tags.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'checking the stripped webpage html for duplicates' );
			}

			$metas = $this->p->util->get_html_head_meta( $html_stripped, $query = '/html/head/link|/html/head/meta', $libxml_errors = true );

			$check_opts = SucomUtil::preg_grep_keys( '/^add_/', $this->p->options, false, '' );

			$conflicts_msg = __( 'Conflict detected &mdash; your theme or another plugin is adding %1$s to the head section of this webpage.', 'wpsso' );

			$conflicts_found = 0;

			if ( is_array( $metas ) ) {

				if ( empty( $metas ) ) {	// No link or meta tags found.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error parsing head meta for ' . $check_url );
					}

					if ( $is_admin ) {

						$validator_url     = 'https://validator.w3.org/nu/?doc=' . urlencode( $check_url );
						$settings_page_url = $this->p->util->get_admin_url( 'general#sucom-tabset_pub-tab_pinterest' );

						$this->p->notice->err( sprintf( __( 'An error occured parsing the head meta tags from <a href="%1$s">%1$s</a> (no "link" or "meta" HTML tags were found).', 'wpsso' ), $check_url ) . ' ' . sprintf( __( 'The webpage may contain HTML syntax errors preventing PHP from successfully parsing the HTML document &mdash; please review the <a href="%1$s">W3C Markup Validator</a> results and correct any syntax errors.', 'wpsso' ), $validator_url ) );
					}

				} else {

					foreach( array(
						'link' => array( 'rel' ),
						'meta' => array( 'name', 'property', 'itemprop' ),
					) as $tag => $types ) {

						if ( isset( $metas[ $tag ] ) ) {

							foreach( $metas[ $tag ] as $meta ) {

								foreach( $types as $type ) {

									if ( isset( $meta[ $type ] ) && $meta[ $type ] !== 'generator' &&
										! empty( $check_opts[ $tag . '_' . $type . '_' . $meta[ $type ] ] ) ) {

										$conflicts_found++;

										$conflicts_tag = '<code>' . $tag . ' ' . $type . '="' . $meta[ $type ] . '"</code>';

										$this->p->notice->err( sprintf( $conflicts_msg, $conflicts_tag ) );
									}
								}
							}
						}
					}

					if ( $is_admin ) {

						$exec_count++;

						if ( $conflicts_found ) {

							$notice_key = 'duplicate-meta-tags-found';

							$notice_msg = sprintf( __( '%1$d duplicate meta tags found.', 'wpsso' ), $conflicts_found ) . ' ';

							$notice_msg .= sprintf( __( 'Check %1$d of %2$d failed (will retry)...', 'wpsso' ), $exec_count, $max_count );

							$this->p->notice->warn( $notice_msg, null, $notice_key );

						} else {

							$notice_key = 'no-duplicate-meta-tags-found';

							$notice_msg = __( 'Awesome! No duplicate meta tags found.', 'wpsso' ) . ' :-) ';

							if ( $this->p->debug->enabled ) {

								$notice_msg .= __( 'Debug option is enabled - will keep repeating duplicate check...', 'wpsso' );

							} else {

								$notice_msg .= sprintf( __( 'Check %1$d of %2$d successful...', 'wpsso' ), $exec_count, $max_count );
							}

							update_option( WPSSO_POST_CHECK_COUNT_NAME, $exec_count, $autoload = false );

							$this->p->notice->inf( $notice_msg, null, $notice_key );
						}
					}
				}
			}
		}

		public function add_meta_boxes() {

			if ( false === ( $post_obj = SucomUtil::get_post_object( true ) ) || empty( $post_obj->post_type ) ) {

				return;
			}

			$post_id = empty( $post_obj->ID ) ? 0 : $post_obj->ID;

			if ( ( $post_obj->post_type === 'page' && ! current_user_can( 'edit_page', $post_id ) ) || ! current_user_can( 'edit_post', $post_id ) ) {

				return;
			}

			if ( empty( $this->p->options[ 'plugin_add_to_' . $post_obj->post_type ] ) ) {

				return;
			}

			$metabox_id      = $this->p->cf[ 'meta' ][ 'id' ];
			$metabox_title   = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
			$metabox_screen  = $post_obj->post_type;
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'__block_editor_compatible_meta_box' => true,
			);

			add_meta_box( 'wpsso_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_document_meta' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		public function ajax_get_metabox_document_meta() {

			$doing_ajax = SucomUtil::get_const( 'DOING_AJAX' );

			if ( ! $doing_ajax ) {	// Just in case.

				return;

			} elseif ( SucomUtil::get_const( 'DOING_AUTOSAVE' ) ) {

				die( -1 );
			}

			check_ajax_referer( WPSSO_NONCE_NAME, '_ajax_nonce', true );

			if ( empty( $_POST[ 'post_id' ] ) ) {

				die( -1 );
			}

			$post_id = $_POST[ 'post_id' ];

			$post_obj = SucomUtil::get_post_object( $post_id );

			if ( ! is_object( $post_obj ) ) {

				die( -1 );

			} elseif ( empty( $post_obj->post_type ) ) {

				die( -1 );

			} elseif ( empty( $post_obj->post_status ) ) {

				die( -1 );

			} elseif ( $post_obj->post_status === 'auto-draft' ) {

				die( -1 );

			} elseif ( $post_obj->post_status === 'trash' ) {

				die( -1 );
			}

			$mod = $this->get_mod( $post_id );

			/**
			 * $read_cache is false to generate notices etc.
			 */
			parent::$head_tags = $this->p->head->get_head_array( $post_id, $mod, $read_cache = false );

			parent::$head_info = $this->p->head->extract_head_info( $mod, parent::$head_tags );

			/**
			 * Check for missing open graph image and description values.
			 */
			if ( $mod[ 'is_public' ] && 'publish' === $mod[ 'post_status' ] ) {

				$ref_url = empty( parent::$head_info[ 'og:url' ] ) ? null : parent::$head_info[ 'og:url' ];

				$ref_url = $this->p->util->maybe_set_ref( $ref_url, $mod, __( 'checking meta tags', 'wpsso' ) );

				foreach ( array( 'image', 'description' ) as $mt_suffix ) {

					if ( empty( parent::$head_info[ 'og:' . $mt_suffix ] ) ) {

						if ( $this->p->notice->is_admin_pre_notices() ) {

							$notice_msg = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

							$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;

							$this->p->notice->err( $notice_msg, null, $notice_key );
						}
					}
				}

				$this->p->util->maybe_unset_ref( $ref_url );
			}

			$metabox_html = $this->get_metabox_document_meta( $post_obj );

			die( $metabox_html );
		}

		public function get_metabox_document_meta( $post_obj ) {

			$metabox_id = $this->p->cf[ 'meta' ][ 'id' ];
			$mod        = $this->get_mod( $post_obj->ID );
			$tabs       = $this->get_document_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $post_obj->ID );
			$def_opts   = $this->get_defaults( $post_obj->ID );

			$is_auto_draft = SucomUtil::is_auto_draft( $mod );

			$this->p->admin->get_pkg_info();

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->id );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( $metabox_id . ' table rows' );	// Start timer.
			}

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				if ( $is_auto_draft ) {

					$table_rows[ $tab_key ][] = '<td><blockquote class="status-info save-a-draft"><p>' .
						__( 'Save a draft or publish to display these options.', 'wpsso' ) . '</p></blockquote></td>';

				} else {

					$mb_filter_name = 'wpsso_metabox_' . $metabox_id . '_' . $tab_key . '_rows';

					$mod_filter_name = 'wpsso_' . $mod[ 'name' ] . '_' . $tab_key . '_rows';

					$table_rows[ $tab_key ] = (array) apply_filters( $mb_filter_name,
						array(), $this->form, parent::$head_info, $mod );

					$table_rows[ $tab_key ] = (array) apply_filters( $mod_filter_name,
						$table_rows[ $tab_key ], $this->form, parent::$head_info, $mod );
				}
			}

			$tabbed_args = array( 'layout' => 'vertical', 'is_auto_draft' => $is_auto_draft );

			$mb_container_id = 'wpsso_metabox_' . $metabox_id . '_inside';

			$metabox_html = "\n" . '<div id="' . $mb_container_id . '">';

			$metabox_html .= $this->p->util->metabox->get_tabbed( $metabox_id, $tabs, $table_rows, $tabbed_args );

			$metabox_html .= apply_filters( $mb_container_id . '_footer', '', $mod );

			$metabox_html .= '</div><!-- #'. $mb_container_id . ' -->' . "\n";

			$metabox_html .= $this->get_metabox_javascript( $mb_container_id );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( $metabox_id . ' table rows' );	// End timer.
			}

			return $metabox_html;
		}

		public function clear_cache( $post_id, $rel_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $do_once = array();

			if ( isset( $do_once[ $post_id ][ $rel_id ] ) ) {

				return;
			}

			$do_once[ $post_id ][ $rel_id ] = true;

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

			$mod = $this->get_mod( $post_id );

			$col_meta_keys = parent::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_key => $meta_key ) {

				delete_post_meta( $post_id, $meta_key );
			}

			$permalink = get_permalink( $post_id );

			$this->p->cache->clear( $permalink );

			if ( ini_get( 'open_basedir' ) ) {

				$check_url = $this->p->util->get_sharing_url( $post_id, $add_page = false );

			} else {

				$check_url = SucomUtilWP::wp_get_shortlink( $post_id, $context = 'post' );
			}

			if ( $permalink !== $check_url ) {

				$this->p->cache->clear( $check_url );
			}

			$this->clear_mod_cache( $mod );

			/**
			 * Clear the post terms (categories, tags, etc.) for published (aka public) posts.
			 */
			if ( 'publish' === $post_status ) {

				if ( ! empty( $this->p->options[ 'plugin_clear_post_terms' ] ) ) {

					$post_taxonomies = get_post_taxonomies( $post_id );

					foreach ( $post_taxonomies as $tax_slug ) {

						$post_terms = wp_get_post_terms( $post_id, $tax_slug );	// Returns WP_Error if taxonomy does not exist.

						if ( is_array( $post_terms ) ) {

							foreach ( $post_terms as $term_obj ) {

								$this->p->term->clear_cache( $term_obj->term_id, $term_obj->term_taxonomy_id );
							}
						}
					}
				}
			}

			if ( function_exists( 'w3tc_pgcache_flush_post' ) ) {	// Clear W3 Total Cache.

				w3tc_pgcache_flush_post( $post_id );
			}

			/**
			 * The question shortcode (in the WPSSO FAQ add-on) attaches the post ID to the question so the post cache
			 * can be cleared when the question is updated.
			 */
			foreach ( array( 'post' ) as $attach_type ) {

				$attached_ids = self::get_attached( $post_id, $attach_type );

				foreach ( $attached_ids as $post_id => $bool ) {

					if ( $bool ) {

						$this->p->$attach_type->clear_cache( $post_id );
					}
				}
			}
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

		public function user_can_save( $post_id, $rel_id = false ) {

			$user_can_save = false;

			if ( ! $this->verify_submit_nonce() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}

				return $user_can_save;
			}

			if ( ! $post_type = SucomUtil::get_request_value( 'post_type', 'POST' ) ) {	// Uses sanitize_text_field.

				$post_type = 'post';
			}

			switch ( $post_type ) {

				case 'page':

					$user_can_save = current_user_can( 'edit_' . $post_type, $post_id );

					break;

				default:

					$user_can_save = current_user_can( 'edit_post', $post_id );

					break;

			}

			if ( ! $user_can_save ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'insufficient privileges to save settings for ' . $post_type . ' ID ' . $post_id );
				}

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( sprintf( __( 'Insufficient privileges to save settings for %1$s ID %2$s.',
						'wpsso' ), $post_type, $post_id ) );
				}
			}

			return $user_can_save;
		}

		/**
		 * Methods that return an associative array of Open Graph meta tags.
		 */
		public function get_og_type_reviews( $post_id, $og_type = 'product', $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			static $reviews_max = null;

			if ( null === $reviews_max ) {	// Only set the value once.

				$reviews_max = SucomUtil::get_const( 'WPSSO_SCHEMA_REVIEWS_PER_PAGE_MAX', 30 );
			}

			$reviews = array();

			if ( empty( $post_id ) ) {

				return $reviews;
			}

			$comments = get_comments( array(
				'post_id' => $post_id,
				'status'  => 'approve',
				'parent'  => 0,					// Parent ID of comment to retrieve children of (0 = don't get replies).
				'order'   => 'DESC',				// Newest first.
				'orderby' => 'date',
				'number'  => get_option( 'comments_per_page' ),	// Maximum number of comments to retrieve.
			) );

			if ( is_array( $comments ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $comments ) . ' comment objects' );
				}

				foreach( $comments as $num => $comment_obj ) {

					$og_review = $this->get_og_comment_review( $comment_obj, $og_type, $rating_meta, $worst_rating, $best_rating );

					if ( ! empty( $og_review ) ) {	// Just in case.

						$reviews[] = $og_review;
					}
				}

				if ( count( $reviews ) > $reviews_max ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( count( $reviews ) . ' reviews found (adjusted to ' . $reviews_max . ')' );
					}

					$reviews = array_slice( $reviews, 0, $reviews_max );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( '$reviews', $reviews );
			}

			return $reviews;
		}

		public function get_og_comment_review( $comment_obj, $og_type = 'product', $rating_meta = 'rating', $worst_rating = 1, $best_rating = 5 ) {

			$mt_ret = array();

			$mt_ret[ $og_type . ':review:id' ]           = $comment_obj->comment_ID;
			$mt_ret[ $og_type . ':review:url' ]          = get_comment_link( $comment_obj->comment_ID );
			$mt_ret[ $og_type . ':review:title' ]        = '';
			$mt_ret[ $og_type . ':review:content' ]      = get_comment_excerpt( $comment_obj->comment_ID );
			$mt_ret[ $og_type . ':review:created_time' ] = mysql2date( 'c', $comment_obj->comment_date_gmt );

			/**
			 * Review author.
			 */
			$mt_ret[ $og_type . ':review:author:id' ]    = $comment_obj->user_id;		// Author ID if registered (0 otherwise).
			$mt_ret[ $og_type . ':review:author:name' ]  = $comment_obj->comment_author;	// Author display name.

			/**
			 * Review rating.
			 *
			 * Rating values must be larger than 0 to include rating info.
			 */
			$rating_value = (float) get_comment_meta( $comment_obj->comment_ID, $rating_meta, true );

			if ( $rating_value > 0 ) {

				$mt_ret[ $og_type . ':review:rating:value' ] = $rating_value;
				$mt_ret[ $og_type . ':review:rating:worst' ] = $worst_rating;
				$mt_ret[ $og_type . ':review:rating:best' ]  = $best_rating;
			}

			return $mt_ret;
		}

		/**
		 * WpssoPost class specific methods.
		 *
		 * Filters the wp shortlink for a post - returns the shortened sharing URL.
		 *
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

			self::$saved_shortlink_url = null;	// Just in case.

			if ( isset( self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'returning shortlink (from static cache) = ' . 
						self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ] );
				}

				return self::$saved_shortlink_url = self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ];
			}

			/**
			 * Check to make sure we have a plugin shortener selected.
			 */
			if ( empty( $this->p->options[ 'plugin_shortener' ] ) || $this->p->options[ 'plugin_shortener' ] === 'none' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no shortening service defined' );
				}

				return $shortlink;	// Return original shortlink.
			}

			/**
			 * The WordPress link-template.php functions call wp_get_shortlink() with a post ID of 0. Recreate the same
			 * code here to get a real post ID and create a default shortlink (if required).
			 */
			if ( 0 === $post_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'provided post ID is 0 (current post)' );
				}

				if ( 'query' === $context && is_singular() ) {	// wp_get_shortlink() uses the same logic.

					$post_id = get_queried_object_id();

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting post ID ' . $post_id . ' from queried object' );
					}

				} elseif ( 'post' === $context ) {

					$post_obj = get_post();

					if ( empty( $post_obj->ID ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'exiting early: post object ID is empty' );
						}

						return $shortlink;	// Return original shortlink.

					} else {

						$post_id = $post_obj->ID;

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'setting post ID ' . $post_id . ' from post object' );
						}
					}
				}

				if ( empty( $post_id ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: unable to determine the post ID' );
					}

					return $shortlink;	// Return original shortlink.
				}

				if ( empty( $shortlink ) ) {

					if ( 'page' === get_post_type( $post_id ) &&
						(int) $post_id === (int) get_option( 'page_on_front' ) &&
							'page' === get_option( 'show_on_front' ) ) {

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

			$short_url = apply_filters( 'wpsso_get_short_url', $sharing_url, $this->p->options[ 'plugin_shortener' ], $mod );

			if ( filter_var( $short_url, FILTER_VALIDATE_URL ) === false ) {	// Invalid url.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: invalid short URL (' . $short_url . ') returned by filters' );
				}

				return $shortlink;	// Return original shortlink.
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'returning shortlink = ' . $short_url );
			}

			return self::$saved_shortlink_url = self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ] = $short_url;	// Success - return short url.
		}

		public function maybe_restore_shortlink( $shortlink = false, $post_id = 0, $context = 'post', $allow_slugs = true ) {

			if ( self::$saved_shortlink_url === $shortlink ) {	// Shortlink value has not changed.

				self::$saved_shortlink_url = null;	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: shortlink value has not changed' );
				}

				return $shortlink;
			}

			self::$saved_shortlink_url = null;	// Just in case.

			if ( isset( self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'restoring shortlink ' . $shortlink . ' to ' . 
						self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ] );
				}

				return self::$cache_shortlinks[ $post_id ][ $context ][ $allow_slugs ];
			}

			return $shortlink;
		}

		/**
		 * Since WPSSO Core v8.15.0.
		 *
		 * Returns a term ID, or false if a term for the $tax_slug is not found.
		 */
		public function get_primary_term_id( array $mod, $tax_slug = 'category' ) {

			$primary_term_id = false;

			if ( $mod[ 'is_post' ] ) {	// Just in case.

				static $local_cache = array();

				$post_id = $mod[ 'id' ];
	
				if ( isset( $local_cache[ $post_id ][ $tax_slug ] ) ) {
	
					return $local_cache[ $post_id ][ $tax_slug ];	// Return value from local cache.
				}

				/**
				 * Returns null if a custom primary term ID has not been selected.
				 */
				$primary_term_id = $this->get_options( $post_id, $md_key = 'primary_term_id' );

				/**
				 * Make sure the term is not null or false, and still exists.
				 */
				if ( ! empty( $primary_term_id ) && term_exists( $primary_term_id ) ) {	// Since WP v3.0.

					$is_custom = true;

				} else {

					$is_custom        = false;
					$primary_term_id  = false;
					$primary_tax_slug = apply_filters( 'wpsso_primary_tax_slug', $tax_slug, $mod );
					$primary_terms    = $this->get_primary_terms( $mod, $primary_tax_slug );

					foreach ( $primary_terms as $term_id => $term_name ) {

						$primary_term_id = $term_id;	// Use the first term ID found.
	
						break;	// Stop here.
					}
				}

				$primary_term_id = apply_filters( 'wpsso_primary_term_id', $primary_term_id, $mod, $tax_slug, $is_custom );
			
				$local_cache[ $post_id ][ $tax_slug ] = $primary_term_id;
			}

			return $primary_term_id;
		}

		/**
		 * Since WPSSO Core v8.16.0.
		 *
		 * Returns an associative array of term IDs and names.
		 */
		public function get_primary_terms( array $mod, $tax_slug = null ) {

			$primary_terms = array();

			if ( $mod[ 'is_post' ] ) {	// Just in case.

				/**
				 * Don't apply the 'wpsso_primary_tax_slug' filter when called by $this->get_primary_term_id().
				 */
				$primary_tax_slug = null === $tax_slug ? apply_filters( 'wpsso_primary_tax_slug', 'category', $mod ) : $tax_slug;
				$post_terms       = wp_get_post_terms( $mod[ 'id' ], $primary_tax_slug );	// Returns WP_Error if taxonomy does not exist.

				if ( is_array( $post_terms ) ) {

					foreach ( $post_terms as $term_obj ) {
			
						$primary_terms[ $term_obj->term_id ] = $term_obj->name;
					}
				}
			}

			return apply_filters( 'wpsso_primary_terms', $primary_terms, $mod, $tax_slug );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function get_meta( $post_id, $meta_key, $single = false ) {

			return get_post_meta( $post_id, $meta_key, $single );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function update_meta( $post_id, $meta_key, $value ) {

			return update_post_meta( $post_id, $meta_key, $value );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function delete_meta( $post_id, $meta_key ) {

			return delete_post_meta( $post_id, $meta_key );
		}
	}
}

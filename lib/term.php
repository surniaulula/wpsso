<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoWpMeta' ) ) {

	require_once dirname( __FILE__ ) . '/abstracts/wp-meta.php';	// SucomAddOn class.
}

if ( ! class_exists( 'WpssoTerm' ) ) {

	class WpssoTerm extends WpssoWpMeta {

		private $query_term_id  = 0;
		private $query_tax_slug = '';
		private $query_tax_obj  = false;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
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

			if ( $is_admin ) {

				/**
				 * Hook a minimum number of admin actions to maximize performance. The taxonomy and tag_ID
				 * arguments are always present when we're editing a category and/or tag page, so return
				 * immediately if they're not present.
				 */
				if ( ( $this->query_tax_slug = SucomUtil::get_request_value( 'taxonomy' ) ) === '' ) {	// Uses sanitize_text_field.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: no taxonomy query argument' );
					}

					return;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'query tax slug = ' . $this->query_tax_slug );
				}

				$this->query_tax_obj = get_taxonomy( $this->query_tax_slug );

				/**
				 * Add edit table columns.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding column filters for taxonomy ' . $this->query_tax_slug );
				}

				add_filter( 'manage_edit-' . $this->query_tax_slug . '_columns', array( $this, 'add_term_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );

				/**
				 * Enable orderby meta_key only if we have a meta table.
				 */
				if ( self::use_term_meta_table() ) {

					add_filter( 'manage_edit-' . $this->query_tax_slug . '_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );
				}

				add_filter( 'manage_' . $this->query_tax_slug . '_custom_column', array( $this, 'get_column_content' ), 10, 3 );

				/**
				 * The 'parse_query' action is hooked once in the WpssoPost class to set the column orderby for
				 * post, term, and user edit tables. This comment is only here as a reminder - do not uncomment the
				 * following 'parse_query' action hook.
				 *
				 * add_action( 'parse_query', array( $this, 'set_column_orderby' ), 10, 1 );
				 */

				/**
				 * Maybe create or update the term column content.
				 */
				add_filter( 'get_term_metadata', array( $this, 'check_sortable_meta' ), 10, 4 );

				if ( ( $this->query_term_id = SucomUtil::get_request_value( 'tag_ID' ) ) === '' ) {	// Uses sanitize_text_field.

					return;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'query term_id = ' . $this->query_term_id );
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
					 * load_meta_page() priorities: 100 post, 200 user, 300 term
					 *
					 * Sets the parent::$head_tags and parent::$head_info class properties.
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 300, 1 );

					add_action( $this->query_tax_slug . '_edit_form', array( $this, 'show_metaboxes' ), -100, 1 );
				}

				add_action( 'created_' . $this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );	// Default is -100.
				add_action( 'created_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );	// Default is -10.

				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );	// Default is -100.
				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );	// Default is -10.

				add_action( 'delete_' . $this->query_tax_slug, array( $this, 'delete_options' ), WPSSO_META_SAVE_PRIORITY, 2 );	// Default is -100.
				add_action( 'delete_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CACHE_PRIORITY, 2 );	// Default is -10.
			}
		}

		/**
		 * Get the $mod object for a term ID.
		 */
		public function get_mod( $term_id, $tax_slug = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $term_id ] ) ) {

				return $local_cache[ $term_id ];
			}

			$mod = self::get_mod_defaults();

			/**
			 * Common elements.
			 */
			$mod[ 'id' ]          = is_numeric( $term_id ) ? (int) $term_id : 0;	// Cast as integer.
			$mod[ 'name' ]        = 'term';
			$mod[ 'name_transl' ] = _x( 'term', 'module name', 'wpsso' );
			$mod[ 'obj' ]         =& $this;

			/**
			 * WpssoTerm elements.
			 */
			$mod[ 'is_term' ]  = true;
			$mod[ 'tax_slug' ] = SucomUtil::get_term_object( $mod[ 'id' ], (string) $tax_slug, 'taxonomy' );

			if ( $tax_obj = get_taxonomy( $mod[ 'tax_slug' ] ) ) {

				if ( isset( $tax_obj->labels->singular_name ) ) {

					$mod[ 'tax_label' ] = $tax_obj->labels->singular_name;
				}

				if ( isset( $tax_obj->public ) ) {

					$mod[ 'is_public' ] = $tax_obj->public ? true : false;
				}
			}

			return $local_cache[ $term_id ] = apply_filters( 'wpsso_get_term_mod', $mod, $term_id, $tax_slug );
		}

		/**
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_options( $term_id, $md_key = false, $filter_opts = true, $pad_opts = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array( 
					'term_id'     => $term_id,
					'md_key'      => $md_key,
					'filter_opts' => $filter_opts,
					'pad_opts'    => $pad_opts,	// Fallback to value in meta defaults.
				) );
			}

			static $local_cache = array();

			/**
			 * Use $term_id and $filter_opts to create the cache ID string, but do not add $pad_opts.
			 */
			$cache_id = SucomUtil::get_assoc_salt( array( 'id' => $term_id, 'filter' => $filter_opts ) );

			/**
			 * Maybe initialize the cache.
			 */
			if ( ! isset( $local_cache[ $cache_id ] ) ) {

				$local_cache[ $cache_id ] = null;

			} elseif ( $this->md_cache_disabled ) {

				$local_cache[ $cache_id ] = null;
			}

			$md_opts =& $local_cache[ $cache_id ];	// Shortcut variable name.

			if ( null === $md_opts ) {

				$md_opts = self::get_term_meta( $term_id, WPSSO_META_NAME, true );

				if ( ! is_array( $md_opts ) ) {

					$md_opts = array();
				}

				/**
				 * Check if options need to be upgraded and saved.
				 */
				if ( $this->is_upgrade_options_required( $md_opts ) ) {

					$md_opts = $this->upgrade_options( $md_opts, $term_id );

					self::update_term_meta( $term_id, WPSSO_META_NAME, $md_opts );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'term_id ' . $term_id . ' settings upgraded' );
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'term_id ' . $term_id . ' meta options read', $md_opts );
				}
			}

			if ( $filter_opts ) {

				if ( empty( $md_opts[ 'options_filtered' ] ) ) {

					$md_opts[ 'options_filtered' ] = 1;	// Set before calling filters to prevent recursion.

					$mod = $this->get_mod( $term_id );

					/**
					 * Since WPSSO Core v7.1.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying get_md_options filters' );
					}

					$md_opts = (array) apply_filters( 'wpsso_get_md_options', $md_opts, $mod );

					/**
					 * Since WPSSO Core v4.31.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying get_term_options filters for term_id ' . $term_id . ' meta' );
					}

					$md_opts = (array) apply_filters( 'wpsso_get_term_options', $md_opts, $term_id, $mod );

					/**
					 * Since WPSSO Core v8.2.0.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying sanitize_md_options filters' );
					}

					$md_opts = apply_filters( 'wpsso_sanitize_md_options', $md_opts, $mod );
				}
			}

			return $this->return_options( $term_id, $md_opts, $md_key, $pad_opts );
		}

		public function save_options( $term_id, $term_tax_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Make sure the current user can submit and same metabox options.
			 */
			if ( ! $this->user_can_save( $term_id, $term_tax_id ) ) {

				return;
			}

			$this->md_cache_disabled = true;	// Disable local cache for get_defaults() and get_options().

			/**
			 * Get first term with matching 'term_taxonomy_id'.
			 */
			$term = get_term_by( 'term_taxonomy_id', $term_tax_id, $tax_slug = '' );

			$mod = is_object( $term ) ? $this->get_mod( $term_id, $term->term_taxonomy_id ) : $mod = $this->get_mod( $term_id );

			/**
			 * Merge and check submitted post, term, and user metabox options.
			 */
			$opts = $this->get_submit_opts( $mod );

			$opts = apply_filters( 'wpsso_save_md_options', $opts, $mod );

			$opts = apply_filters( 'wpsso_save_term_options', $opts, $term_id, $term_tax_id, $mod );

			if ( empty( $opts ) ) {

				return self::delete_term_meta( $term_id, WPSSO_META_NAME );
			}

			return self::update_term_meta( $term_id, WPSSO_META_NAME, $opts );
		}

		public function delete_options( $term_id, $term_tax_id = false ) {

			return self::delete_term_meta( $term_id, WPSSO_META_NAME );
		}

		/**
		 * Get all publicly accessible term IDs for a taxonomy slug (optional).
		 *
		 * These may include term IDs from non-public taxonomies.
		 */
		public static function get_public_ids( $tax_name = null ) {

			global $wp_version;

			$terms_args = array(
				'fields' => 'ids',	// 'ids' (returns an array of ids).
			);

			$add_tax_in_args = version_compare( $wp_version, '4.5.0', '>=' ) ? true : false;

			$public_term_ids = array();

			$tax_names = SucomUtilWP::get_taxonomies( $output = 'names' );

			foreach ( $tax_names as $name ) {

				if ( $add_tax_in_args ) {	// Since WP v4.5.

					$terms_args[ 'taxonomy' ] = $name;

					$term_ids = get_terms( $terms_args );

				} else {

					$term_ids = get_terms( $name, $terms_args );
				}

				foreach ( $term_ids as $term_id ) {

					$public_term_ids[ $term_id ] = $term_id;
				}
			}

			rsort( $public_term_ids );	// Newest id first.

			return $public_term_ids;
		}

		/**
		 * Get post IDs for a term ID in a taxonomy slug.
		 *
		 * Return an array of post IDs for a given $mod object, including posts in child terms as well.
		 *
		 * Called by WpssoWpMeta->get_posts_mods().
		 */
		public function get_posts_ids( array $mod, array $extra_args = array() ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$posts_args = array_merge( array(
				'has_password' => false,
				'order'        => 'DESC',	// Newest first.
				'orderby'      => 'date',
				'post_status'  => 'publish',	// Only 'publish', not 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', or 'trash'.
				'post_type'    => 'any',	// Return posts, pages, or any custom post type.
				'tax_query'    => array(
				        array(
						'taxonomy'         => $mod[ 'tax_slug' ],
						'field'            => 'term_id',
						'terms'            => $mod[ 'id' ],
						'include_children' => true
					)
				),
			), $extra_args, array( 'fields' => 'ids' ) );	// Return an array of post IDs.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'calling get_posts() for ' . $mod[ 'name' ] . ' ID ' . $mod[ 'id' ] .  ' in taxonomy ' . $mod[ 'tax_slug' ] );
			}

			$mtime_start = microtime( $get_float = true );
			$post_ids    = get_posts( $posts_args );
			$mtime_total = microtime( $get_float = true ) - $mtime_start;
			$mtime_max   = WPSSO_GET_POSTS_MAX_TIME;

			if ( $mtime_total > $mtime_max ) {

				$func_name   = 'get_posts()';
				$error_pre   = sprintf( __( '%s warning:', 'wpsso' ), __METHOD__ );
				$rec_max_msg = sprintf( __( 'longer than recommended max of %1$.3f secs', 'wpsso' ), $mtime_max );
				$error_msg   = sprintf( __( 'Slow WordPress function detected - %1$s took %2$.3f secs to get posts for term ID %3$d in taxonomy %4$s (%5$s).',
					'wpsso' ), '<code>' . $func_name . '</code>', $mtime_total, $mod[ 'id' ], $mod[ 'tax_slug' ], $rec_max_msg );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( sprintf( 'slow WordPress function detected - %1$s took %2$.3f secs to get posts for term ID %3$d in taxonomy %4$s',
						$func_name, $mtime_total, $mod[ 'id' ], $mod[ 'tax_slug' ] ) );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->warn( $error_msg );
				}

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg, $strip_html = true );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( count( $post_ids ) . ' post IDs returned in ' . sprintf( '%0.3f secs', $mtime_total ) );
			}

			return $post_ids;
		}

		public function add_term_column_headings( $columns ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->add_column_headings( $columns, $list_type = 'term' );
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
				$this->p->debug->log( 'query tax slug = ' . $this->query_tax_slug );
			}

			switch ( $screen->id ) {

				case 'edit-' . $this->query_tax_slug:

					$mod = $this->get_mod( $this->query_term_id, $this->query_tax_slug );

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: not a recognized term page' );
					}

					return;
			}

			/**
			 * Define parent::$head_tags and signal to other 'current_screen' actions that this is a valid term page.
			 */
			parent::$head_tags = array();

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'term ID = ' . $this->query_term_id );
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale default = ' . SucomUtil::get_locale( 'default' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtil::get_locale( 'current' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtil::get_locale( $mod ) );
				$this->p->debug->log( SucomUtil::pretty_array( $mod ) );
			}

			if ( $this->query_term_id && ! empty( $this->p->options[ 'plugin_add_to_tax_' . $this->query_tax_slug ] ) ) {

				do_action( 'wpsso_admin_term_head', $mod, $screen->id );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'setting head_meta_info static property' );
				}

				/**
				 * $read_cache is false to generate notices etc.
				 */
				parent::$head_tags = $this->p->head->get_head_array( $use_post = false, $mod, $read_cache = false );

				parent::$head_info = $this->p->head->extract_head_info( $mod, parent::$head_tags );

				/**
				 * Check for missing open graph image and description values.
				 */
				if ( $mod[ 'is_public' ] ) {	// Since WPSSO Core v7.0.0.

					$ref_url = empty( parent::$head_info[ 'og:url' ] ) ? null : parent::$head_info[ 'og:url' ];

					$ref_url = $this->p->util->maybe_set_ref( $ref_url, $mod, __( 'checking meta tags', 'wpsso' ) );

					foreach ( array( 'image', 'description' ) as $mt_suffix ) {

						if ( empty( parent::$head_info[ 'og:' . $mt_suffix] ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'og:' . $mt_suffix . ' meta tag is value empty and required' );
							}

							/**
							 * An is_admin() test is required to use the WpssoMessages class.
							 */
							if ( $this->p->notice->is_admin_pre_notices() ) {

								$notice_msg = $this->p->msgs->get( 'notice-missing-og-' . $mt_suffix );

								$notice_key = $mod[ 'name' ] . '-' . $mod[ 'id' ] . '-notice-missing-og-' . $mt_suffix;

								$this->p->notice->err( $notice_msg, null, $notice_key );
							}
						}
					}

					$this->p->util->maybe_unset_ref( $ref_url );
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

					$this->p->notice->err( sprintf( __( 'Nonce token validation failed for %1$s action "%2$s".', 'wpsso' ), 'term', $action_name ) );

				} else {

					$_SERVER[ 'REQUEST_URI' ] = remove_query_arg( array( $action_query, WPSSO_NONCE_NAME ) );

					switch ( $action_name ) {

						default:

							do_action( 'wpsso_load_meta_page_term_' . $action_name, $this->query_term_id );

							break;
					}
				}
			}
		}

		public function add_meta_boxes() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: user cannot edit terms' );
				}

				return;
			}

			if ( empty( $this->p->options[ 'plugin_add_to_tax_' . $this->query_tax_slug ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot add metabox to taxonomy "' . $this->query_tax_slug . '"' );
				}

				return;
			}

			$metabox_id      = $this->p->cf[ 'meta' ][ 'id' ];
			$metabox_title   = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
			$metabox_screen  = 'wpsso-term';
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback.
				'__block_editor_compatible_meta_box' => true,
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding metabox id wpsso_' . $metabox_id );
			}

			add_meta_box( 'wpsso_' . $metabox_id, $metabox_title,
				array( $this, 'show_metabox_document_meta' ), $metabox_screen,
					$metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metaboxes( $term_obj ) {

			if ( ! current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {

				return;
			}

			if ( empty( $this->p->options[ 'plugin_add_to_tax_' . $this->query_tax_slug ] ) ) {

				return;
			}

			$pkg_info        = $this->p->admin->get_pkg_info();	// Returns an array from cache.
			$metabox_screen  = 'wpsso-term';
			$metabox_context = 'normal';

			echo "\n" . '<!-- wpsso term metabox section begin -->' . "\n";
			echo '<h3>' . $pkg_info[ 'wpsso' ][ 'short_dist' ] . '</h3>' . "\n";
			echo '<div id="poststuff" class="wpsso-metaboxes metabox-holder">' . "\n";

			do_meta_boxes( $metabox_screen, $metabox_context, $term_obj );

			echo "\n" . '</div><!-- #poststuff -->' . "\n";
			echo '<!-- wpsso term metabox section end -->' . "\n";
		}

		public function ajax_get_metabox_document_meta() {

			die( -1 );	// Nothing to do.
		}

		public function get_metabox_document_meta( $term_obj ) {

			$metabox_id = $this->p->cf[ 'meta' ][ 'id' ];
			$mod        = $this->get_mod( $term_obj->term_id, $this->query_tax_slug );
			$tabs       = $this->get_document_meta_tabs( $metabox_id, $mod );
			$opts       = $this->get_options( $term_obj->term_id );
			$def_opts   = $this->get_defaults( $term_obj->term_id );

			$this->p->admin->get_pkg_info();	// Returns an array from cache.

			$this->form = new SucomForm( $this->p, WPSSO_META_NAME, $opts, $def_opts, $this->p->id );

			wp_nonce_field( WpssoAdmin::get_nonce_action(), WPSSO_NONCE_NAME );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark( $metabox_id . ' table rows' );	// start timer
			}

			$table_rows = array();

			foreach ( $tabs as $tab_key => $title ) {

				$mb_filter_name = 'wpsso_metabox_' . $metabox_id . '_' . $tab_key . '_rows';

				$mod_filter_name = 'wpsso_' . $mod[ 'name' ] . '_' . $tab_key . '_rows';

				$table_rows[ $tab_key ] = (array) apply_filters( $mb_filter_name,
					array(), $this->form, parent::$head_info, $mod );

				$table_rows[ $tab_key ] = (array) apply_filters( $mod_filter_name,
					$table_rows[ $tab_key ], $this->form, parent::$head_info, $mod );
			}

			$tabbed_args = array( 'layout' => 'vertical' );

			$mb_container_id = 'wpsso_metabox_' . $metabox_id . '_inside';

			$metabox_html = "\n" . '<div id="' . $mb_container_id . '">';

			$metabox_html .= $this->p->util->metabox->get_tabbed( $metabox_id, $tabs, $table_rows, $tabbed_args );

			$metabox_html .= '<!-- ' . $mb_container_id . '_footer begin -->' . "\n";

			$metabox_html .= apply_filters( $mb_container_id . '_footer', '', $mod );

			$metabox_html .= '<!-- ' . $mb_container_id . '_footer end -->' . "\n";

			$metabox_html .= $this->get_metabox_javascript( $mb_container_id );

			$metabox_html .= '</div><!-- #'. $mb_container_id . ' -->' . "\n";

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

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $do_once = array();

			if ( isset( $do_once[ $term_id ][ $term_tax_id ] ) ) {

				return;
			}

			$do_once[ $term_id ][ $term_tax_id ] = true;

			$taxonomy = get_term_by( 'term_taxonomy_id', $term_tax_id, $tax_slug = '' );

			if ( isset( $taxonomy->slug ) ) {	// Just in case.

				$mod = $this->get_mod( $term_id, $taxonomy->slug );

			} else {

				$mod = $this->get_mod( $term_id );
			}

			$col_meta_keys = parent::get_column_meta_keys();

			foreach ( $col_meta_keys as $col_key => $meta_key ) {

				self::delete_term_meta( $term_id, $meta_key );
			}

			$this->clear_mod_cache( $mod );
		}

		public function user_can_save( $term_id, $term_tax_id = false ) {

			$user_can_save = false;

			if ( ! $this->verify_submit_nonce() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}

				return $user_can_save;
			}

			if ( ! $user_can_save = current_user_can( $this->query_tax_obj->cap->edit_terms ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'insufficient privileges to save settings for term ID ' . $term_id );
				}

				/**
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( sprintf( __( 'Insufficient privileges to save settings for term ID %1$s.',
						'wpsso' ), $term_id ) );
				}
			}

			return $user_can_save;
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function get_meta( $term_id, $meta_key, $single = false ) {

			return self::get_term_meta( $term_id, $meta_key, $single );
		}

		/**
		 * Backwards compatible methods for handling term meta, which did not exist before WordPress v4.4.
		 */
		public static function get_term_meta( $term_id, $meta_key, $single = false ) {

			$term_meta = false === $single ? array() : '';

			if ( self::use_term_meta_table( $term_id ) ) {

				$term_meta = get_term_meta( $term_id, $meta_key, $single );	// Since WP v4.4.

				/**
				 * Fallback to checking for deprecated term meta in the options table.
				 */
				if ( ( $single && $term_meta === '' ) || ( ! $single && $term_meta === array() ) ) {

					/**
					 * If deprecated meta is found, update the meta table and delete the deprecated meta.
					 */
					if ( ( $opt_term_meta = get_option( $meta_key . '_term_' . $term_id, null ) ) !== null ) {

						$updated = update_term_meta( $term_id, $meta_key, $opt_term_meta );	// Since WP v4.4.

						if ( ! is_wp_error( $updated ) ) {

							delete_option( $meta_key . '_term_' . $term_id );

							$term_meta = get_term_meta( $term_id, $meta_key, $single );

						} else {
							$term_meta = false === $single ? array( $opt_term_meta ) : $opt_term_meta;
						}
					}
				}

			} elseif ( ( $opt_term_meta = get_option( $meta_key . '_term_' . $term_id, null ) ) !== null ) {

				$term_meta = false === $single ? array( $opt_term_meta ) : $opt_term_meta;
			}

			return $term_meta;
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function update_meta( $term_id, $meta_key, $value ) {

			return self::update_term_meta( $term_id, $meta_key, $value );
		}

		public static function update_term_meta( $term_id, $meta_key, $value ) {

			if ( self::use_term_meta_table( $term_id ) ) {

				return update_term_meta( $term_id, $meta_key, $value );	// Since WP v4.4.
			}

			return update_option( $meta_key . '_term_' . $term_id, $value );
		}

		/**
		 * Since WPSSO Core v8.4.0.
		 */
		public static function delete_meta( $term_id, $meta_key ) {

			return self::delete_term_meta( $term_id, $meta_key );
		}

		public static function delete_term_meta( $term_id, $meta_key ) {

			if ( self::use_term_meta_table( $term_id ) ) {

				return delete_term_meta( $term_id, $meta_key );	// Since WP v4.4.
			}

			return delete_option( $meta_key . '_term_' . $term_id );
		}

		public static function use_term_meta_table( $term_id = false ) {

			static $local_cache = null;

			if ( null === $local_cache )	{	// Optimize and check only once.

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

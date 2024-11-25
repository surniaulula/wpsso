<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoAbstractWpMeta' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/abstract/wp-meta.php';
}

if ( ! class_exists( 'WpssoTerm' ) ) {

	class WpssoTerm extends WpssoAbstractWpMeta {

		private $query_term_id  = 0;
		private $query_tax_slug = '';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * This hook is fired once WordPress, plugins, and the theme are fully loaded and instantiated.
			 */
			add_action( 'wp_loaded', array( $this, 'add_wp_callbacks' ) );
		}

		/*
		 * Add WordPress action and filter callbacks.
		 */
		public function add_wp_callbacks() {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Since WPSSO Core v17.0.0.
			 *
			 * Register our term meta.
			 */
			$this->register_meta( $object_type = 'term', WPSSO_META_NAME );

			$is_admin = is_admin();	// Only check once.

			if ( $is_admin ) {

				/*
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

				/*
				 * Add edit table columns.
				 */
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding column filters for taxonomy ' . $this->query_tax_slug );
				}

				add_filter( 'manage_edit-' . $this->query_tax_slug . '_columns', array( $this, 'add_term_column_headings' ), WPSSO_ADD_COLUMN_PRIORITY, 1 );
				add_filter( 'manage_edit-' . $this->query_tax_slug . '_sortable_columns', array( $this, 'add_sortable_columns' ), 10, 1 );
				add_filter( 'manage_' . $this->query_tax_slug . '_custom_column', array( $this, 'get_column_content' ), 10, 3 );

				/*
				 * The 'parse_query' action is hooked once in the WpssoPost class to set the column orderby for
				 * post, term, and user edit tables.
				 *
				 * This comment is here as a reminder - do not uncomment the following 'parse_query' action hook.
				 *
				 * add_action( 'parse_query', array( $this, 'set_column_orderby' ), 10, 1 );
				 */

				/*
				 * Maybe create or update the term column content.
				 */
				add_filter( 'get_term_metadata', array( $this, 'check_sortable_meta' ), 10, 4 );

				if ( ( $this->query_term_id = SucomUtil::get_request_value( 'tag_ID' ) ) === '' ) {	// Uses sanitize_text_field.

					return;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'query term_id = ' . $this->query_term_id );
				}

				/*
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
				if ( ! empty( $_GET ) ) {	// Skip some action hooks if no query argument(s).

					/*
					 * load_meta_page() priorities: 100 post, 200 user, 300 term
					 *
					 * Sets the parent::$head_tags and parent::$head_info class properties.
					 */
					add_action( 'current_screen', array( $this, 'load_meta_page' ), 300, 1 );

					add_action( $this->query_tax_slug . '_pre_edit_form', array( $this, 'add_meta_boxes' ), 10, 2 );
					add_action( $this->query_tax_slug . '_edit_form', array( $this, 'show_metaboxes' ), -100, 2 );
				}

				add_action( 'created_' . $this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'created_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CLEAR_PRIORITY, 2 );
				add_action( 'created_' . $this->query_tax_slug, array( $this, 'refresh_cache' ), WPSSO_META_REFRESH_PRIORITY, 2 );

				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'save_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CLEAR_PRIORITY, 2 );
				add_action( 'edited_' . $this->query_tax_slug, array( $this, 'refresh_cache' ), WPSSO_META_REFRESH_PRIORITY, 2 );

				add_action( 'delete_' . $this->query_tax_slug, array( $this, 'delete_options' ), WPSSO_META_SAVE_PRIORITY, 2 );
				add_action( 'delete_' . $this->query_tax_slug, array( $this, 'clear_cache' ), WPSSO_META_CLEAR_PRIORITY, 2 );
			}
		}

		/*
		 * Get the $mod object for a term id.
		 */
		public function get_mod( $term_id, $tax_slug = '' ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark_caller();

				$this->p->debug->log_args( array(
					'term_id'  => $term_id,
					'tax_slug' => $tax_slug,
				) );
			}

			static $local_fifo = array();

			/*
			 * Maybe return the array from the local cache.
			 *
			 * Term IDs in older WordPress versions are not unique, so use the term ID and the taxonomy slug as a cache index.
			 */
			if ( isset( $local_fifo[ $term_id ][ $tax_slug ] ) ) {

				if ( ! $this->md_cache_disabled ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: term id ' . $term_id . ' mod array from local cache' );
					}

					return $local_fifo[ $term_id ][ $tax_slug ];

				} else unset( $local_fifo[ $term_id ][ $tax_slug ] );
			}

			/*
			 * Maybe limit the number of array elements.
			 */
			$local_fifo = SucomUtil::array_slice_fifo( $local_fifo, WPSSO_CACHE_ARRAY_FIFO_MAX );

			$mod = self::get_mod_defaults();

			/*
			 * Common elements.
			 */
			$mod[ 'id' ]            = is_numeric( $term_id ) ? (int) $term_id : 0;	// Cast as integer.
			$mod[ 'name' ]          = 'term';
			$mod[ 'name_transl' ]   = _x( 'term', 'module name', 'wpsso' );
			$mod[ 'obj' ]           =& $this;

			/*
			 * WpssoTerm elements.
			 */
			$mod[ 'is_term' ]    = true;
			$mod[ 'is_archive' ] = true;	// Required for WpssoUtil->get_url_paged().

			if ( $mod[ 'id' ] ) {	// Just in case.

				$mod[ 'wp_obj' ] = SucomUtilWP::get_term_object( $mod[ 'id' ], (string) $tax_slug );

				if ( $mod[ 'wp_obj' ] instanceof WP_Term ) {	// Just in case.

					$mod[ 'term_tax_id' ] = (int) $mod[ 'wp_obj' ]->term_taxonomy_id;
					$mod[ 'tax_slug' ]    = (string) $mod[ 'wp_obj' ]->taxonomy;

					if ( $mod[ 'tax_slug' ] ) {	// Just in case.

						$tax_obj = get_taxonomy( $mod[ 'tax_slug' ] );

						if ( $tax_obj instanceof WP_Taxonomy ) {	// Just in case.

							if ( isset( $tax_obj->labels->name ) ) {

								$mod[ 'tax_label_plural' ] = $tax_obj->labels->name;
							}

							if ( isset( $tax_obj->labels->singular_name ) ) {

								$mod[ 'tax_label_single' ] = $tax_obj->labels->singular_name;
							}

							if ( isset( $tax_obj->public ) ) {

								$mod[ 'is_public' ] = $tax_obj->public ? true : false;
							}
						}
					}

				} else $mod[ 'wp_obj' ] = false;
			}

			/*
			 * Filter the term mod array.
			 */
			$mod = apply_filters( 'wpsso_get_term_mod', $mod, $term_id, $tax_slug );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'mod', $mod );
			}

			/*
			 * Maybe save the array to the local cache.
			 */
			if ( ! $this->md_cache_disabled ) {

				if ( ! isset( $local_fifo[ $term_id ] ) ) {

					$local_fifo[ $term_id ] = array();
				}

				$local_fifo[ $term_id ][ $tax_slug ] = $mod;
			
				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_size( 'local_fifo', $local_fifo );
				}
			}

			return $mod;
		}

		public function get_mod_wp_object( array $mod ) {

			if ( $mod[ 'wp_obj' ] instanceof WP_Term ) {

				return $mod[ 'wp_obj' ];
			}

			return SucomUtilWP::get_term_object( $mod[ 'id' ], $mod[ 'tax_slug' ] );
		}

		/*
		 * Option handling methods:
		 *
		 *	get_defaults()
		 *	get_options()
		 *	save_options()
		 *	delete_options()
		 */
		public function get_options( $term_id, $md_key = false, $filter_opts = true, $merge_defs = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark_caller();

				$this->p->debug->log_args( array(
					'term_id'     => $term_id,
					'md_key'      => $md_key,
					'filter_opts' => $filter_opts,
					'merge_defs'  => $merge_defs,	// Fallback to value in meta defaults.
				) );
			}

			static $local_fifo = array();

			/*
			 * Use $term_id and $filter_opts to create the cache ID string, but do not add $merge_defs.
			 */
			$cache_id = SucomUtil::get_assoc_salt( array( 'id' => $term_id, 'filter' => $filter_opts ) );

			/*
			 * Maybe initialize a new local cache element. Use isset() instead of empty() to allow for an empty array.
			 */
			if ( ! isset( $local_fifo[ $cache_id ] ) ) {

				/*
				 * Maybe limit the number of array elements.
				 */
				$local_fifo = SucomUtil::array_slice_fifo( $local_fifo, WPSSO_CACHE_ARRAY_FIFO_MAX );

				$local_fifo[ $cache_id ] = null;	// Create an element to reference.
			}

			$md_opts =& $local_fifo[ $cache_id ];	// Reference the local cache element.

			if ( null === $md_opts ) {	// Maybe read metadata into a new local cache element.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting metadata for term id ' . $term_id );
				}

				$md_opts = self::get_meta( $term_id, WPSSO_META_NAME, true );

				if ( ! is_array( $md_opts ) ) {

					$md_opts = array();	// WPSSO_META_NAME not found.
				}

				unset( $md_opts[ 'opt_filtered' ] );	// Just in case.

				/*
				 * Check if options need to be upgraded and saved.
				 */
				if ( $this->p->opt->is_upgrade_required( $md_opts ) ) {

					$md_opts = $this->upgrade_options( $md_opts, $term_id );

					self::update_meta( $term_id, WPSSO_META_NAME, $md_opts );
				}
			}

			if ( $filter_opts ) {

				if ( ! empty( $md_opts[ 'opt_filtered' ] ) ) {	// Set before calling filters to prevent recursion.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'skipping filters: options already filtered' );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting opt_filtered to 1' );
					}

					$md_opts[ 'opt_filtered' ] = 1;	// Set before calling filters to prevent recursion.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'required call to WpssoTerm->get_mod() for term ID ' . $term_id );
					}

					$mod = $this->get_mod( $term_id );

					/*
					 * Overwrite parent options with those of the child, allowing only undefined child options
					 * to be inherited from the parent.
					 */
					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'inheriting parent metadata options for term id ' . $term_id );
					}

					$parent_opts = $this->get_inherited_md_opts( $mod );

					if ( ! empty( $parent_opts ) ) {

						$md_opts = array_merge( $parent_opts, $md_opts );
					}

					$filter_name = 'wpsso_get_md_options';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
					}

					$md_opts = apply_filters( 'wpsso_get_md_options', $md_opts, $mod );

					$filter_name = 'wpsso_get_' . $mod[ 'name' ] . '_options';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
					}

					$md_opts = apply_filters( $filter_name, $md_opts, $term_id, $mod );

					$filter_name = 'wpsso_sanitize_md_options';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'applying filters "' . $filter_name . '"' );
					}

					$md_opts = apply_filters( $filter_name, $md_opts, $mod );
				}
			}

			/*
			 * Maybe save the array to the local cache.
			 */
			if ( $this->md_cache_disabled ) {

				$deref_md_opts = $local_fifo[ $cache_id ];	// Dereference.

				unset( $local_fifo, $md_opts );

				return $this->return_options( $term_id, $deref_md_opts, $md_key, $merge_defs );
			}

			return $this->return_options( $term_id, $md_opts, $md_key, $merge_defs );
		}

		/*
		 * Use $term_tax_id = false to extend WpssoAbstractWpMeta->save_options().
		 */
		public function save_options( $term_id, $term_tax_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'term_id'     => $term_id,
					'term_tax_id' => $term_tax_id,
				) );
			}

			if ( empty( $term_id ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: term id is empty' );
				}

				return;

			} elseif ( ! $this->verify_submit_nonce() ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: verify_submit_nonce failed' );
				}

				return;

			/*
			 * Check user capability for the term id.
			 */
			} elseif ( ! $this->user_can_edit( $term_id, $term_tax_id ) ) {

				if ( $this->p->debug->enabled ) {

					$user_id = get_current_user_id();

					$this->p->debug->log( 'exiting early: user id ' . $user_id . ' cannot edit term id ' . $term_id );
				}

				return;
			}

			$this->md_cache_disable();	// Disable the local cache.

			$term_obj = get_term_by( 'term_taxonomy_id', $term_tax_id, $tax_slug = '' );

			$mod = isset( $term_obj->taxonomy ) ? $this->get_mod( $term_id, $term_obj->taxonomy ) : $mod = $this->get_mod( $term_id );

			$md_opts  = $this->get_submit_opts( $mod );	// Merge previous + submitted options and then sanitize.

			$this->md_cache_enable();	// Re-enable the local cache.

			if ( false === $md_opts ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: returned submit options is false' );
				}

				return;
			}

			$md_opts = apply_filters( 'wpsso_save_md_options', $md_opts, $mod );

			$md_opts = apply_filters( 'wpsso_save_' . $mod[ 'name' ] . '_options', $md_opts, $term_id, $mod );

			return self::update_meta( $term_id, WPSSO_META_NAME, $md_opts );
		}

		/*
		 * Use $term_tax_id = false to extend WpssoAbstractWpMeta->delete_options().
		 */
		public function delete_options( $term_id, $term_tax_id = false ) {

			return self::delete_meta( $term_id, WPSSO_META_NAME );
		}

		/*
		 * Get all publicly accessible term ids.
		 */
		public static function get_public_ids( array $terms_args = array() ) {

			$wpsso =& Wpsso::get_instance();

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->mark();
			}

			$mtime_start = microtime( $get_float = true );

			$tax_names = empty( $terms_args[ 'taxonomy' ] ) ? SucomUtilWP::get_taxonomies( $output = 'names' ) : array( $terms_args[ 'taxonomy' ] );

			$terms_args = array_merge( $terms_args, array( 'fields' => 'ids' ) );

			$public_ids = array();

			foreach ( $tax_names as $name ) {

				/*
				 * See https://developer.wordpress.org/reference/classes/wp_term_query/__construct/.
				 */
				$query_args = array_merge( $terms_args, array( 'taxonomy' => $name ) );

				$terms_ids = get_terms( $query_args );

				if ( is_array( $terms_ids ) ) {

					foreach ( $terms_ids as $term_id ) {

						if ( is_numeric( $term_id ) ) {

							$public_ids[ $term_id ] = $term_id;	// Prevents duplicates.
						}
					}
				}
			}

			unset( $query_args, $terms_ids );

			/*
			 * Sort public term IDs with the newest term ID first.
			 *
			 * Note that rsort() assigns new keys to elements in the array.
			 *
			 * See https://www.php.net/manual/en/function.rsort.php.
			 */
			rsort( $public_ids );

			$mtime_total = microtime( $get_float = true ) - $mtime_start;

			if ( $wpsso->debug->enabled ) {

				$wpsso->debug->log( count( $public_ids ) . ' term IDs returned in ' . sprintf( '%0.3f secs', $mtime_total ) );
			}

			return apply_filters( 'wpsso_term_public_ids', $public_ids, $terms_args );
		}

		/*
		 * Get the posts for a term.
		 *
		 * Returns an array of post ids for a given $mod object, including posts in child terms as well.
		 *
		 * The 'posts_per_page' value should be set for an archive page before calling this method.
		 *
		 * See WpssoAbstractWpMeta->get_posts_mods().
		 */
		public function get_posts_ids( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $mod[ 'is_term' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: ' . $mod[ 'name' ] . ' ID ' . $mod[ 'id' ] .  ' is not a term' );
				}

				return array();
			}

			$posts_args = array_merge( array(
				'has_password' => false,
				'order'        => 'DESC',	// Newest first.
				'orderby'      => 'date',
				'post_status'  => 'publish',	// Only 'publish' (not 'auto-draft', 'draft', 'future', 'inherit', 'pending', 'private', or 'trash').
				'post_type'    => 'any',	// Return posts, pages, or any custom post type.
				'tax_query'    => array(
				        array(
						'taxonomy'         => $mod[ 'tax_slug' ],
						'field'            => 'term_id',
						'terms'            => $mod[ 'id' ],
						'include_children' => true
					)
				),
			), $mod[ 'posts_args' ], array( 'fields' => 'ids' ) );	// Return an array of post ids.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'getting posts for ' . $mod[ 'name' ] . ' ID ' . $mod[ 'id' ] .  ' in taxonomy ' . $mod[ 'tax_slug' ] );

				$this->p->debug->log_arr( 'posts_args', $posts_args );
			}

			$mtime_start = microtime( $get_float = true );

			$posts_ids = SucomUtilWP::get_posts( $posts_args );	// Alternative to get_posts() that does not exclude sticky posts.

			$mtime_total = microtime( $get_float = true ) - $mtime_start;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( count( $posts_ids ) . ' post IDs returned in ' . sprintf( '%0.3f secs', $mtime_total ) );
			}

			return $posts_ids;
		}

		public function add_term_column_headings( $columns ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->add_column_headings( $columns, $opt_suffix = 'tax_' . $this->query_tax_slug );
		}

		public function get_update_meta_cache( $term_id ) {

			return SucomUtilWP::get_update_meta_cache( $term_id, $meta_type = 'term' );
		}

		/*
		 * Hooked into the current_screen action.
		 *
		 * Sets the parent::$head_tags and parent::$head_info class properties.
		 */
		public function load_meta_page( $screen = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
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

			/*
			 * Define parent::$head_tags and signal to other 'current_screen' actions that this is a term page.
			 */
			parent::$head_tags = array();	// Used by WpssoAbstractWpMeta->is_meta_page().

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'term id = ' . $this->query_term_id );
				$this->p->debug->log( 'home url = ' . get_option( 'home' ) );
				$this->p->debug->log( 'locale current = ' . SucomUtilWP::get_locale() );
				$this->p->debug->log( 'locale default = ' . SucomUtilWP::get_locale( 'default' ) );
				$this->p->debug->log( 'locale mod = ' . SucomUtilWP::get_locale( $mod ) );
				$this->p->debug->log( SucomUtil::get_array_pretty( $mod ) );
			}

			if ( $this->query_term_id && ! empty( $this->p->options[ 'plugin_add_to_tax_' . $this->query_tax_slug ] ) ) {

				do_action( 'wpsso_admin_term_head', $mod, $screen->id );

				list(
					parent::$head_tags,	// Used by WpssoAbstractWpMeta->is_meta_page().
					parent::$head_info	// Used by WpssoAbstractWpMeta->check_head_info().
				) = $this->p->util->cache->refresh_mod_head_meta( $mod );

				/*
				 * Check for missing open graph image and description values.
				 */
				if ( $mod[ 'id' ] && $mod[ 'is_public' ] ) {	// Since WPSSO Core v7.0.0.

					$this->check_head_info( $mod );
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

		/*
		 * Use $tax_slug = false to extend WpssoAbstractWpMeta->add_meta_boxes().
		 */
		public function add_meta_boxes( $term_obj, $tax_slug = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$term_id = empty( $term_obj->term_id ) ? 0 : $term_obj->term_id;
			$tax_obj = get_taxonomy( $tax_slug );

			if ( empty( $this->p->options[ 'plugin_add_to_tax_' . $tax_slug ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot add metabox to taxonomy "' . $tax_slug . '"' );
				}

				return;
			}

			$capability = $tax_obj->cap->edit_terms;

			if ( ! current_user_can( $capability, $term_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot ' . $capability . ' for term id ' . $term_id );
				}

				return;
			}

			$metabox_id      = $this->p->cf[ 'meta' ][ 'id' ];
			$metabox_title   = _x( $this->p->cf[ 'meta' ][ 'title' ], 'metabox title', 'wpsso' );
			$metabox_screen  = 'wpsso-term';
			$metabox_context = 'normal';
			$metabox_prio    = 'default';
			$callback_args   = array(	// Second argument passed to the callback.
				'metabox_id'                         => $metabox_id,
				'metabox_title'                      => $metabox_title,
				'__block_editor_compatible_meta_box' => true,
			);

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding metabox id wpsso_' . $metabox_id . ' for screen ' . $metabox_screen );
			}

			add_meta_box( 'wpsso_' . $metabox_id, $metabox_title, array( $this, 'show_metabox_' . $metabox_id ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metaboxes( $term_obj, $tax_slug ) {

			$term_id    = empty( $term_obj->term_id ) ? 0 : $term_obj->term_id;
			$tax_obj    = get_taxonomy( $tax_slug );
			$capability = $tax_obj->cap->edit_terms;

			if ( empty( $this->p->options[ 'plugin_add_to_tax_' . $tax_slug ] ) ) {

				return;

			} elseif ( ! current_user_can( $capability, $term_id ) ) {

				return;
			}

			$metabox_screen  = 'wpsso-term';
			$metabox_context = 'normal';

			/*
			 * Keep the original 800px width for the form table and allow metaboxes to take the screen width.
			 */
			echo '<style type="text/css">';
			echo 'div.wrap form#edittag { max-width:none; }';
			echo 'div.wrap form#edittag table.form-table { max-width:800px; }';
			echo '</style>' . "\n";
			echo '<div class="metabox-holder">' . "\n";

			do_meta_boxes( $metabox_screen, $metabox_context, $term_obj );

			echo "\n" . '</div><!-- .metabox-holder -->' . "\n";
		}

		public function ajax_get_metabox_sso() {

			die( -1 );	// Nothing to do.
		}

		/*
		 * Hooked to these actions:
		 *
		 * do_action( "created_$taxonomy", $term_id, $tt_id );
		 * do_action( "edited_$taxonomy",  $term_id, $tt_id );
		 * do_action( "delete_$taxonomy",  $term_id, $tt_id, $deleted_term );
		 *
		 * Also called by WpssoPost::clear_cache() to clear the post term cache.
		 *
		 * Use $term_tax_id = false to extend WpssoAbstractWpMeta->clear_cache().
		 */
		public function clear_cache( $term_id, $term_tax_id = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'term_id'     => $term_id,
					'term_tax_id' => $term_tax_id,
				) );
			}

			static $do_once = array();	// Just in case - prevent recursion.

			if ( isset( $do_once[ $term_id ][ $term_tax_id ] ) ) return;	// Stop here.

			$do_once[ $term_id ][ $term_tax_id ] = true;

			if ( empty( $term_id ) ) return;	// Just in case.

			$term_obj = get_term_by( 'term_taxonomy_id', $term_tax_id, $tax_slug = '' );

			$mod = isset( $term_obj->taxonomy ) ? $this->get_mod( $term_id, $term_obj->taxonomy ) : $this->get_mod( $term_id );

			unset( $term_obj );

			$this->clear_mod_cache( $mod );

			do_action( 'wpsso_clear_term_cache', $term_id, $mod );
		}

		/*
		 * Refresh the cache for a single term ID.
		 *
		 * Use $term_tax_id = false to extend WpssoAbstractWpMeta->refresh_cache().
		 */
		public function refresh_cache( $term_id, $term_tax_id = false ) {

			static $do_once = array();	// Just in case - prevent recursion.

			if ( isset( $do_once[ $term_id ][ $term_tax_id ] ) ) return;	// Stop here.

			$do_once[ $term_id ][ $term_tax_id ] = true;

			if ( empty( $term_id ) ) return;	// Just in case.

			$term_obj = get_term_by( 'term_taxonomy_id', $term_tax_id, $tax_slug = '' );

			$mod = isset( $term_obj->taxonomy ) ? $this->get_mod( $term_id, $term_obj->taxonomy ) : $this->get_mod( $term_id );

			unset( $term_obj );

			$this->p->util->cache->refresh_mod_head_meta( $mod );

			do_action( 'wpsso_refresh_term_cache', $term_id, $mod );
		}

		/*
		 * Check user capability for the term id.
		 *
		 * Use $term_tax_id = false to extend WpssoAbstractWpMeta->user_can_edit().
		 */
		public function user_can_edit( $term_id, $term_tax_id = false ) {

			$term_obj   = get_term_by( 'term_taxonomy_id', $term_tax_id, $tax_slug = '' );
			$tax_obj    = get_taxonomy( $term_obj->taxonomy );
			$capability = $tax_obj->cap->edit_terms;

			if ( ! current_user_can( $capability, $term_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: cannot ' . $capability . ' for term id ' . $term_id );
				}

				/*
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( sprintf( __( 'Insufficient privileges to edit term ID %1$s.', 'wpsso' ), $term_id ) );
				}

				return false;
			}

			return true;
		}

		/*
		 * If $meta_key is en empty string, retrieves all metadata for the specified object ID.
		 *
		 * See https://developer.wordpress.org/reference/functions/get_metadata/.
		 */
		public static function get_meta( $term_id, $meta_key = '', $single = false ) {

			return get_metadata( 'term', $term_id, $meta_key, $single );
		}

		public static function update_meta( $term_id, $meta_key, $value ) {

			return update_metadata( 'term', $term_id, $meta_key, $value );
		}

		public static function delete_meta( $term_id, $meta_key ) {

			return delete_metadata( 'term', $term_id, $meta_key );
		}
	}
}

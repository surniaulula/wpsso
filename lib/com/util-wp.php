<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtil' ) ) {

	require_once dirname( __FILE__ ) . '/util.php';
}

If ( ! class_exists( 'SucomUtilWP' ) ) {

	class SucomUtilWP extends SucomUtil {

		protected static $locale_cache = array();	// Used by clear_locale_cache() and get_locale().

		public function __construct() {}

		/*
		 * DOING CHECK METHODS:
		 *
		 * 	doing_ajax()
		 * 	doing_autosave()
		 * 	doing_block_editor()
		 * 	doing_cron()
		 * 	doing_frontend()
		 * 	doing_iframe()
		 * 	doing_rest()
		 * 	doing_xmlrpc()
		 */
		public static function doing_ajax() {

			if ( function_exists( 'wp_doing_ajax' ) ) {

				return wp_doing_ajax();
			}

			return defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		}

		public static function doing_autosave() {

			return defined( 'DOING_AUTOSAVE' ) ? DOING_AUTOSAVE : false;
		}

		public static function doing_block_editor() {

			static $local_cache = null;

			if ( $local_cache ) {	// Optimize - once true, stay true.

				return true;
			}

			$local_cache   = false;
			$post_id       = false;
			$can_edit_id   = false;
			$can_edit_type = false;
			$req_action    = empty( $_REQUEST[ 'action' ] ) ? false : sanitize_text_field( $_REQUEST[ 'action' ] );
			$is_meta_box   = empty( $_REQUEST[ 'meta-box-loader' ] ) && empty( $_REQUEST[ 'meta_box' ] ) ? false : true;
			$is_gutenbox   = empty( $_REQUEST[ 'gutenberg_meta_boxes' ] ) ? false : true;
			$is_classic    = isset( $_REQUEST[ 'classic-editor' ] ) && empty( $_REQUEST[ 'classic-editor' ] ) ? false : true;

			if ( ! empty( $_REQUEST[ 'post_ID' ] ) && is_numeric( $_REQUEST[ 'post_ID' ] ) ) {

				$post_id = SucomUtil::sanitize_int( $_REQUEST[ 'post_ID' ] );	// Returns integer or null.

			} elseif ( ! empty( $_REQUEST[ 'post' ] ) && is_numeric( $_REQUEST[ 'post' ] ) ) {

				$post_id = SucomUtil::sanitize_int( $_REQUEST[ 'post' ] );	// Returns integer or null.
			}

			if ( $post_id ) {

				if ( function_exists( 'use_block_editor_for_post' ) ) {

					/*
					 * Calling use_block_editor_for_post() in WordPress v5.0 during post save crashes the web
					 * browser. See https://core.trac.wordpress.org/ticket/45253 for details. Only call
					 * use_block_editor_for_post() if using WordPress v5.2 or newer.
					 */
					global $wp_version;

					if ( version_compare( $wp_version, '5.2', '<' ) ) {

						$can_edit_id = true;

					} elseif ( use_block_editor_for_post( $post_id ) ) {

						$can_edit_id = true;
					}

				} elseif ( function_exists( 'gutenberg_can_edit_post' ) ) {

					if ( gutenberg_can_edit_post( $post_id ) ) {

						$can_edit_id = true;
					}
				}

				/*
				 * If we can edit the post ID, then check if we can edit the post type.
				 */
				if ( $can_edit_id ) {

					$post_type_name = get_post_type( $post_id );

					if ( $post_type_name ) {

						if ( function_exists( 'use_block_editor_for_post_type' ) ) {

							if ( use_block_editor_for_post_type( $post_type_name ) ) {

								$can_edit_type = true;
							}

						} elseif ( function_exists( 'gutenberg_can_edit_post_type' ) ) {

							if ( gutenberg_can_edit_post_type( $post_type_name ) ) {

								$can_edit_type = true;
							}
						}
					}
				}
			}

			if ( $can_edit_id && $can_edit_type ) {

				if ( $is_gutenbox ) {

					$local_cache = true;

				} elseif ( $is_meta_box ) {

					$local_cache = true;

				} elseif ( ! $is_classic ) {

					$local_cache = true;

				} elseif ( $post_id && 'edit' === $req_action ) {

					$local_cache = true;
				}
			}

			return $local_cache;
		}

		public static function doing_cron() {

			if ( function_exists( 'wp_doing_cron' ) ) {

				return wp_doing_cron();
			}

			return defined( 'DOING_CRON' ) ? DOING_CRON : false;
		}

		public static function doing_dev() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			if ( function_exists( 'wp_get_environment_type' ) ) {	// Since WP v5.5.

				/*
				 * Returns 'local', 'development', 'staging', or 'production'.
				 */
				if ( 'development' === wp_get_environment_type() ) {

					return $local_cache = true;
				}
			}

			return $local_cache = false;
		}

		public static function doing_frontend() {

			if ( is_admin() ) {

				return false;

			} elseif ( defined( 'DOING_CRON' ) && DOING_CRON ) {

				return false;

			} elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

				return true;	// An ajax call is considered a frontend task.

			} elseif ( self::doing_rest() ) {

				return false;

			} else {

				return true;
			}
		}

		public static function doing_iframe() {

			return defined( 'IFRAME_REQUEST' ) ? true : false;
		}

		public static function doing_rest() {

			if ( empty( $_SERVER[ 'REQUEST_URI' ] ) ) {

				return false;
			}

			$rest_prefix = trailingslashit( rest_get_url_prefix() );

			return strpos( $_SERVER[ 'REQUEST_URI' ], $rest_prefix ) !== false ? true : false;
		}

		public static function doing_xmlrpc() {

			return defined( 'XMLRPC_REQUEST' ) ? true : false;
		}

		/*
		 * IS CHECK METHODS:
		 *
		 * 	is_amp()
		 * 	is_home_page()
		 *	is_home_posts()
		 *	is_mod_screen_obj()
		 *	is_mod_post_type()
		 *	is_post_page()
		 *	is_post_type()
		 *	is_post_type_archive()
		 *	is_post_type_public()
		 *	is_term_page()
		 *	is_term_tax_slug()
		 *	is_toplevel_edit()
		 *	is_user_page()
		 */
		public static function is_amp() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				if ( is_admin() ) {

					$local_cache = false;

				/*
				 * The amp_is_request() function cannot be called before the 'wp' action has run, so if the 'wp'
				 * action has not run, leave the $local_cache as null to allow for future checks.
				 */
				} elseif ( function_exists( 'amp_is_request' ) ) {

					if ( did_action( 'wp' ) ) {

						$local_cache = amp_is_request();
					}

				} elseif ( function_exists( 'is_amp_endpoint' ) ) {

					$local_cache = is_amp_endpoint();

				} elseif ( function_exists( 'ampforwp_is_amp_endpoint' ) ) {

					$local_cache = ampforwp_is_amp_endpoint();

				} elseif ( defined( 'AMP_QUERY_VAR' ) ) {

					$local_cache = get_query_var( AMP_QUERY_VAR, false ) ? true : false;

				} else $local_cache = false;
			}

			return $local_cache;
		}

		public static function is_home_page( $use_post = false ) {

			$is_home_page = false;

			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;

			if ( $post_id > 0 ) {	// The 'page_on_front' option post ID.

				if ( is_numeric( $use_post ) && (int) $use_post === $post_id ) {

					$is_home_page = true;

				} elseif ( $post_id === self::get_post_object( $use_post, $output = 'id' ) ) {

					$is_home_page = true;
				}
			}

			return apply_filters( 'sucom_is_home_page', $is_home_page, $use_post );
		}

		public static function is_home_posts( $use_post = false ) {

			$is_home_posts = false;

			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_for_posts' ) : 0;

			if ( $post_id > 0 ) {	// The 'page_for_posts' option post ID.

				if ( is_numeric( $use_post ) && (int) $post_id === $use_post ) {

					$is_home_posts = true;

				} elseif ( $post_id === self::get_post_object( $use_post, $output = 'id' ) ) {

					$is_home_posts = true;
				}

			} elseif ( false === $use_post && is_home() && is_front_page() ) {

				$is_home_posts = true;
			}

			return apply_filters( 'sucom_is_home_posts', $is_home_posts, $use_post );
		}

		public static function is_mod_screen_obj( array $mod ) {

			if ( ! is_admin() ) {	// Front-end does not have a screen.

				return false;
			}

			if ( empty( $mod[ 'id' ] ) || ! is_numeric( $mod[ 'id' ] ) ) {

				return false;
			}

			$screen_base = self::get_screen_base();

			if ( empty( $mod[ 'name' ] ) || $mod[ 'name' ] !== $screen_base ) {

				return false;
			}

			$obj_id = null;

			switch ( $screen_base ) {

				case 'post':

					$obj_id = self::get_request_value( 'post_ID', 'POST' );

					if ( '' === $obj_id ) {

						$obj_id = self::get_request_value( 'post', 'GET' );
					}

					break;

				case 'term':

					$obj_id = self::get_request_value( 'tag_ID' );

					break;

				case 'user':

					$obj_id = self::get_request_value( 'user_id' );

					break;

				default:

					return false;

					break;
			}

			if ( ! $obj_id || ! is_numeric( $obj_id ) ) {

				return false;
			}

			if ( (int) $obj_id === $mod[ 'id' ] ) {

				return true;
			}

			return false;
		}

		/*
		 * See WpssoIntegEcomWooCommerce->filter_attached_image_ids().
		 * See WpssoIntegEcomWooCommerce->filter_get_md_defaults().
		 * See WpssoIntegEcomWooCommerce->filter_og_seed().
		 * See WpssoIntegEcomWooCommerce->filter_tag_names_seed().
		 * See WpssoIntegEcomWooCommerce->filter_import_product_attributes().
		 * See WpssoIntegEcomWooCommerce->add_mt_product().
		 */
		public static function is_mod_post_type( array $mod, $post_type ) {

			if ( $mod[ 'is_post' ] && $mod[ 'id' ] && $mod[ 'post_type' ] === $post_type ) {

				return true;
			}

			return false;
		}

		public static function is_post_page( $use_post = false ) {

			$is_post_page = false;

			if ( is_numeric( $use_post ) && $use_post > 0 ) {

				$is_post_page = self::post_exists( $use_post );

			} elseif ( true === $use_post && ! empty( $GLOBALS[ 'post' ]->ID ) ) {

				$is_post_page = true;

			} elseif ( false === $use_post && is_singular() ) {

				$is_post_page = true;

			} elseif ( false === $use_post && is_post_type_archive() ) {

				$is_post_page = true;

			} elseif ( ! is_home() && is_front_page() && 'page' === get_option( 'show_on_front' ) ) {	// Static front page.

				$is_post_page = true;

			} elseif ( is_home() && ! is_front_page() && 'page' === get_option( 'show_on_front' ) ) {	// Static posts page.

				$is_post_page = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( $screen_base === 'post' ) {

					$is_post_page = true;

				} elseif ( false === $screen_base &&	// Called too early for screen.
					( '' !== self::get_request_value( 'post_ID', 'POST' ) ||	// Uses sanitize_text_field().
						'' !== self::get_request_value( 'post', 'GET' ) ) ) {

					$is_post_page = true;

				} elseif ( 'post-new' === basename( $_SERVER[ 'PHP_SELF' ], '.php' ) ) {

					$is_post_page = true;
				}
			}

			return apply_filters( 'sucom_is_post_page', $is_post_page, $use_post );
		}

		public static function is_post_type( $post_obj, $post_type ) {

			if ( ! empty( $post_obj->post_type ) && $post_obj->post_type === $post_type ) {

				return true;
			}

			return false;
		}

		public static function is_post_type_archive( $post_type_obj, $post_slug ) {

			$is_post_type_archive = false;

			if ( $post_type_obj && $post_slug && is_string( $post_slug ) ) {	// Just in case.

				if ( is_string( $post_type_obj ) ) {

					$post_type_obj = get_post_type_object( $post_type_obj );
				}

				if ( ! is_object( $post_type_obj ) ) {	// Just in case.

					return $is_post_type_archive;
				}

				if ( ! empty( $post_type_obj->has_archive ) ) {

					$archive_slug = $post_type_obj->has_archive;

					if ( true === $archive_slug ) {

						$archive_slug = $post_type_obj->rewrite[ 'slug' ];
					}

					if ( $post_slug === $archive_slug ) {

						$is_post_type_archive = true;
					}
				}
			}

			return $is_post_type_archive;
		}

		/*
		 * $mixed = WP_Post | post ID | post type name.
		 */
		public static function is_post_type_public( $mixed ) {

			$post_type_name = null;

			if ( is_object( $mixed ) || is_numeric( $mixed ) ) {

				$post_type_name = get_post_type( $mixed );

			} else $post_type_name = $mixed;	// Post type name.

			if ( $post_type_name ) {

				$args = array( 'name' => $post_type_name, 'public'  => 1 );

				$post_types = get_post_types( $args, $output = 'names', $operator = 'and' );

				if ( isset( $post_types[ 0 ] ) && $post_types[ 0 ] === $post_type_name ) {

					return true;
				}
			}

			return false;
		}

		public static function is_term_page( $term_id = 0, $tax_slug = '' ) {

			$is_term_page = false;

			if ( is_numeric( $term_id ) && $term_id > 0 ) {

				/*
				 * Note that term_exists() requires an integer ID, not a string ID.
				 */
				$is_term_page = term_exists( (int) $term_id, $tax_slug );

			} elseif ( is_tax() || is_category() || is_tag() ) {

				$is_term_page = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( 'term' === $screen_base ) {

					$is_term_page = true;

				} elseif ( ( false === $screen_base || $screen_base === 'edit-tags' ) &&
					( '' !== self::get_request_value( 'taxonomy' ) &&
						'' !== self::get_request_value( 'tag_ID' ) ) ) {

					$is_term_page = true;
				}
			}

			return apply_filters( 'sucom_is_term_page', $is_term_page );
		}

		/*
		 * See WpssoIntegEcomWooCommerce->filter_term_image_ids().
		 */
		public static function is_term_tax_slug( $term_id, $tax_slug ) {

			/*
			 * Optimize and get the term only once so this method can be called several times for different $tax_slugs.
			 */
			static $local_cache = array();

			if ( ! isset( $local_cache[ $term_id ] ) ) {

				$local_cache[ $term_id ] = get_term_by( 'id', $term_id, $tax_slug, OBJECT, 'raw' );
			}

			if ( ! empty( $local_cache[ $term_id ]->term_id ) &&
				! empty( $local_cache[ $term_id ]->taxonomy ) &&
					$local_cache[ $term_id ]->taxonomy === $tax_slug ) {

				return true;
			}

			return false;
		}

		/*
		 * See WpssoScript->admin_enqueue_scripts().
		 * See WpssoStyle->admin_enqueue_styles().
		 */
		public static function is_toplevel_edit( $hook_name ) {

			if ( false !== strpos( $hook_name, 'toplevel_page_' ) ) {

				if ( 'edit' === self::get_request_value( 'action', 'GET' ) && (int) self::get_request_value( 'post', 'GET' ) > 0 )  {

					return true;
				}

				if ( 'create_new' === self::get_request_value( 'action', 'GET' ) && 'edit' === self::get_request_value( 'return', 'GET' ) ) {

					return true;
				}
			}

			return false;
		}

		public static function is_user_page( $user_id = 0 ) {

			$is_user_page = false;

			if ( is_numeric( $user_id ) && $user_id > 0 ) {

				$is_user_page = self::user_exists( $user_id );

			} elseif ( is_author() ) {

				$is_user_page = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( false !== $screen_base ) {

					switch ( $screen_base ) {

						case 'profile':								// User profile page.
						case 'user-edit':							// User editing page.
						case ( 0 === strpos( $screen_base, 'profile_page_' ) ? true : false ):	// Your profile page.
						case ( 0 === strpos( $screen_base, 'users_page_' ) ? true : false ):	// Users settings page.

							$is_user_page = true;

							break;
					}

				} elseif ( '' !== self::get_request_value( 'user_id' ) ||	// Called too early for screen.
					'profile' === basename( $_SERVER[ 'PHP_SELF' ], '.php' ) ) {

					$is_user_page = true;
				}
			}

			return apply_filters( 'sucom_is_user_page', $is_user_page );
		}

		/*
		 * DISABLED CHECK METHODS:
		 *
		 * 	sitemaps_disabled()
		 */
		public static function sitemaps_disabled() {

			return self::sitemaps_enabled() ? false : true;
		}

		/*
		 * ENABLED CHECK METHODS:
		 *
		 *	oembed_enabled()
		 *	sitemaps_enabled()
		 */
		public static function oembed_enabled() {

			if ( function_exists( 'get_oembed_response_data' ) ) {

				return true;
			}

			return false;
		}

		public static function sitemaps_enabled() {

			static $locale_cache = null;

			if ( null !== $locale_cache ) {

				return $locale_cache;
			}

			global $wp_sitemaps;

			if ( is_callable( array( $wp_sitemaps, 'sitemaps_enabled' ) ) ) {	// Since WP v5.5.

				return $locale_cache = (bool) $wp_sitemaps->sitemaps_enabled();
			}

			return $locale_cache = false;
		}

		/*
		 * EXISTS CHECK METHODS:
		 *
		 * 	comment_exists()
		 * 	post_exists()
		 * 	role_exists()
		 * 	term_exists()
		 * 	user_exists()
		 */
		public static function comment_exists( $comment_id ) {

			return self::table_object_id_exists( 'comments', $comment_id );	// Uses a local cache.
		}

		/*
		 * See WpssoIntegEcomWooCommerce->check_woocommerce_pages().
		 */
		public static function post_exists( $post_id ) {

			return self::table_object_id_exists( 'posts', $post_id );	// Uses a local cache.
		}

		public static function role_exists( $role ) {

			$exists = false;

			if ( ! empty( $role ) ) {	// Just in case.

				if ( function_exists( 'wp_roles' ) ) {

					$exists = wp_roles()->is_role( $role );

				} else $exists = $GLOBALS[ 'wp_roles' ]->is_role( $role );
			}

			return $exists;
		}

		public static function term_exists( $term_id ) {

			return self::table_object_id_exists( 'terms', $term_id );	// Uses a local cache.
		}

		public static function user_exists( $user_id ) {

			return self::table_object_id_exists( 'users', $user_id );	// Uses a local cache.
		}

		private static function table_object_id_exists( $table, $obj_id ) {

			if ( $table && is_numeric( $obj_id ) && $obj_id > 0 ) {

				static $local_cache = array();

				$obj_id = (int) $obj_id;	// Cast as integer for the cache index.

				if ( isset( $local_cache[ $table ][ $obj_id ] ) ) {

					return $local_cache[ $table ][ $obj_id ];
				}

				global $wpdb;

				$select_sql = 'SELECT COUNT(ID) FROM ' . $wpdb->$table . ' WHERE ID = %d';

				return $local_cache[ $table ][ $obj_id ] = $wpdb->get_var( $wpdb->prepare( $select_sql, $obj_id ) ) ? true : false;
			}

			return false;
		}

		/*
		 * GET COMMENT METHODS:
		 *
		 *	get_comment_object()
		 */
		public static function get_comment_object( $comment_id = 0, $output = 'object' ) {

			$comment_obj = null;

			if ( $comment_id instanceof WP_Comment ) {

				$comment_obj = $comment_id;

			} elseif ( is_numeric( $comment_id ) && $comment_id > 0 ) {

				$comment_obj = get_comment( (int) $comment_id, OBJECT );

			} elseif ( is_admin() ) {

				if ( 'editcomment' === self::get_request_value( 'action' ) &&
					'' !== ( $comment_id = self::get_request_value( 'c' ) ) ) {

					$comment_obj = get_comment( (int) $comment_id, OBJECT );
				}
			}

			$comment_obj = apply_filters( 'sucom_get_comment_object', $comment_obj, $comment_id );

			if ( $comment_obj instanceof WP_Comment ) {	// Just in case.

				switch ( $output ) {

					case 'id':
					case 'ID':
					case 'comment_id':

						return isset( $comment_obj->comment_ID ) ? (int) $comment_obj->comment_ID : 0;	// Cast as integer.

					default:

						return $comment_obj;
				}
			}

			return false;
		}

		/*
		 * GET POST METHODS:
		 *
		 *	get_post_object()
		 *	get_post_type_archive_labels()
		 *	get_post_type_archives()
		 *	get_post_type_labels()
		 *	get_post_types()
		 *	get_posts()
		 *	maybe_load_post()
		 */
		public static function get_post_object( $use_post = false, $output = 'object' ) {

			$post_obj = null;

			if ( $use_post instanceof WP_Post ) {

				$post_obj = $use_post;

			} elseif ( is_numeric( $use_post ) && $use_post > 0 ) {

				$post_obj = get_post( $use_post, OBJECT, $filter = 'raw' );

			} elseif ( true === $use_post && ! empty( $GLOBALS[ 'post' ]->ID ) ) {

				$post_obj = $GLOBALS[ 'post' ];

			} elseif ( false === $use_post && apply_filters( 'sucom_is_post_page', ( is_singular() ? true : false ), $use_post ) ) {

				$post_obj = get_queried_object();

			} elseif ( ! is_home() && is_front_page() && 'page' === get_option( 'show_on_front' ) ) {	// Static front page.

				$post_obj = get_post( get_option( 'page_on_front' ), OBJECT, $filter = 'raw' );

			} elseif ( is_home() && ! is_front_page() && 'page' === get_option( 'show_on_front' ) ) {	// Static posts page.

				$post_obj = get_post( get_option( 'page_for_posts' ), OBJECT, $filter = 'raw' );

			} elseif ( is_admin() ) {

				if ( '' !== ( $post_id = self::get_request_value( 'post_ID', 'POST' ) ) ||
					'' !== ( $post_id = self::get_request_value( 'post', 'GET' ) ) ) {

					$post_obj = get_post( $post_id, OBJECT, $filter = 'raw' );
				}
			}

			$post_obj = apply_filters( 'sucom_get_post_object', $post_obj, $use_post );

			if ( $post_obj instanceof WP_Post ) {	// Just in case.

				switch ( $output ) {

					case 'id':
					case 'ID':
					case 'post_id':

						return isset( $post_obj->ID ) ? (int) $post_obj->ID : 0;	// Cast as integer.

					default:

						return $post_obj;
				}
			}

			return false;
		}

		public static function get_post_type_archive_labels( $val_prefix = '', $label_prefix = '' ) {

			$objects = self::get_post_type_archives( $output = 'objects' );

			return self::get_post_type_labels( $val_prefix, $label_prefix, $objects );
		}

		/*
		 * Note that 'has_archive' = 1 will not match post types that are registered with a string in 'has_archive'. Use
		 * 'has_archive' = true to include the WooCommerce product archive page (ie. 'has_archive' = 'shop').
		 */
		public static function get_post_type_archives( $output = 'objects', $sort = false, $args = null ) {

			if ( ! is_array( $args ) ) {

				$args = array();
			}

			if ( empty( $args[ 'has_archive' ] ) || 1 === $args[ 'has_archive' ] ) {

				$args[ 'has_archive' ] = true;
			}

			return self::get_post_types( $output, $sort, $args );
		}

		public static function get_post_type_labels( $val_prefix = '', $label_prefix = '', $objects = null ) {

			$values = array();

			if ( ! is_string( $val_prefix ) ) {	// Just in case.

				return $values;
			}

			if ( null === $objects ) {

				$objects = self::get_post_types( $output = 'objects', $sort = true );
			}

			if ( is_array( $objects ) ) {	// Just in case.

				foreach ( $objects as $obj ) {

					$obj_label = self::get_object_label( $obj );

					$values[ $val_prefix . $obj->name ] = trim( $label_prefix . ' ' . $obj_label );
				}
			}

			return $values;
		}

		/*
		 * Returns post types registered as 'public' = true and 'show_ui' = true by default.
		 *
		 * Note that the 'wp_block' custom post type for reusable blocks is registered as 'public' = false and 'show_ui' = true.
		 *
		 * $output = objects | names
		 */
		public static function get_post_types( $output = 'objects', $sort = false, $args = null ) {

			$def_args = array( 'public' => true, 'show_ui' => true );

			if ( null === $args ) {

				$args = $def_args;

			} elseif ( is_array( $args ) ) {

				$args = array_merge( $def_args, $args );

			} else return array();

			$operator = 'and';

			$post_types = get_post_types( $args, $output, $operator );

			if ( $sort ) {

				if ( 'objects' === $output ) {

					self::sort_objects_by_label( $post_types );

				} else asort( $post_types );
			}

			return apply_filters( 'sucom_get_post_types', $post_types, $output, $args );
		}

		/*
		 * Alternative to the WordPress get_posts() function which sets 'ignore_sticky_posts' and 'no_found_rows' to true.
		 *
		 * Calls WP_Query->query() with the supplied arguments.
		 *
		 * See wordpress/wp-includes/post.php.
		 * See WpssoPost->get_posts_ids().
		 * See WpssoPost->get_public_ids().
		 * See WpssoTerm->get_posts_ids().
		 * See WpssoUser->get_posts_ids().
		 */
		public static function get_posts( $args ) {

			/*
			 * Query argument sanitation.
			 *
			 * See wordpress/wp-includes/post.php.
			 */
			if ( ! empty( $args[ 'post_type' ] ) ) {

				if ( empty( $args[ 'post_status' ] ) ) {

					$args[ 'post_status' ] = 'attachment' === $args[ 'post_type' ] ? 'inherit' : 'publish';
				}
			}

			if ( ! empty( $args[ 'numberposts' ] ) && empty( $args[ 'posts_per_page' ] ) ) {

				$args[ 'posts_per_page' ] = $args[ 'numberposts' ];

				unset( $args[ 'numberposts' ] );
			}

			if ( ! empty( $args[ 'category' ] ) ) {

				$args[ 'cat' ] = $args[ 'category' ];

				unset( $args[ 'category' ] );
			}

			if ( ! empty( $args[ 'include' ] ) ) {

				$args[ 'post__in' ] = wp_parse_id_list( $args[ 'include' ] );

				$args[ 'posts_per_page' ] = count( $args[ 'post__in' ] );	// Only the number of posts included.

				unset( $args[ 'include' ] );
			}

			if ( ! empty( $args[ 'exclude' ] ) ) {

				$args[ 'post__not_in' ] = wp_parse_id_list( $args[ 'exclude' ] );

				unset( $args[ 'exclude' ] );
			}

			/*
			 * If the query arguments do not limit the number of posts returned with 'paged' and 'posts_per_page', then
			 * use a while loop to save memory and fetch a default of 1000 posts at a time.
			 */
			$wp_query     = new WP_Query;
			$has_nopaging = isset( $args[ 'nopaging' ] ) && $args[ 'nopaging' ] ? true : false;
			$has_paged    = isset( $args[ 'paged' ] ) && false !== $args[ 'paged' ] ? true : false;
			$has_ppp      = isset( $args[ 'posts_per_page' ] ) && -1 !== $args[ 'posts_per_page' ] ? true : false;

			if ( defined( 'SUCOM_GET_POSTS_DEBUG_LOG' ) && SUCOM_GET_POSTS_DEBUG_LOG ) {

				error_log( print_r( $args, true ) );
			}

			if ( ! $has_nopaging && ! $has_paged && ! $has_ppp ) {

				$args[ 'paged' ] = 1;

				$args[ 'posts_per_page' ] = defined( 'SUCOM_GET_POSTS_WHILE_PPP' ) ? SUCOM_GET_POSTS_WHILE_PPP : 1000;

				/*
				 * Setting the offset parameter overrides/ignores the paged parameter and breaks pagination.
				 *
				 * See https://developer.wordpress.org/reference/classes/wp_query/.
				 */
				unset( $args[ 'offset' ] );

				$posts = array();

				while ( $result = $wp_query->query( $args ) ) {	// Return an array of post objects or post IDs.

					$posts = array_merge( $posts, $result );

					$args[ 'paged' ]++;	// Get the next page.
				}

			} else $posts = $wp_query->query( $args );

			return $posts;
		}

		public static function maybe_load_post( $mixed, $force = false ) {

			if ( empty( $mixed ) ) {	// Just in case.

				return false;
			}

			global $post;

			if ( $mixed instanceof WP_Post ) {

				if ( $force || ! isset( $post->ID ) || (int) $post->ID !== (int) $mixed->ID ) {

					$post = $mixed;
				}

			} elseif ( is_numeric( $mixed ) ) {

				if ( $force || ! isset( $post->ID ) || (int) $post->ID !== (int) $mixed ) {

					$post = self::get_post_object( $mixed, $output = 'object' );

					return true;
				}
			}

			return false;
		}

		/*
		 * GET TAXONOMY METHODS:
		 *
		 *	get_taxonomies()
		 *	get_taxonomy_labels()
		 */
		public static function get_taxonomies( $output = 'objects', $sort = false, $args = null ) {

			$def_args = array( 'public' => true, 'show_ui' => true );

			if ( null === $args ) {

				$args = $def_args;

			} elseif ( is_array( $args ) ) {

				$args = array_merge( $def_args, $args );

			} else return array();

			$operator   = 'and';
			$taxonomies = get_taxonomies( $args, $output, $operator );

			if ( $sort ) {

				if ( 'objects' === $output ) {

					self::sort_objects_by_label( $taxonomies );

				} else asort( $post_types );
			}

			return apply_filters( 'sucom_get_taxonomies', $taxonomies, $output, $args );
		}

		public static function get_taxonomy_labels( $val_prefix = '', $label_prefix = '', $objects = null ) {

			$values = array();

			if ( ! is_string( $val_prefix ) ) {	// Just in case.

				return $values;
			}

			if ( null === $objects ) {

				$objects = self::get_taxonomies( $output = 'objects', $sort = true );
			}

			if ( is_array( $objects ) ) {	// Just in case.

				foreach ( $objects as $obj ) {

					$obj_label = self::get_object_label( $obj );

					$values[ $val_prefix . $obj->name ] = trim( $label_prefix . ' ' . $obj_label );
				}
			}

			asort( $values );	// Sort by label.

			return $values;
		}

		/*
		 * GET TERM METHODS:
		 *
		 *	get_term_object()
		 */
		public static function get_term_object( $term_id = 0, $tax_slug = '', $output = 'object' ) {

			$term_obj = null;

			if ( $term_id instanceof WP_Term ) {	// Just in case.

				$term_obj = $term_id;

			} elseif ( is_numeric( $term_id ) && $term_id > 0 ) {

				$term_obj = get_term( (int) $term_id, (string) $tax_slug, OBJECT, 'raw' );

			} elseif ( apply_filters( 'sucom_is_term_page', is_tax() ) || is_tag() || is_category() ) {

				$term_obj = get_queried_object();

			} elseif ( is_admin() ) {

				if ( '' !== ( $tax_slug = self::get_request_value( 'taxonomy' ) ) &&
					'' !== ( $term_id = self::get_request_value( 'tag_ID' ) ) ) {

					$term_obj = get_term( (int) $term_id, (string) $tax_slug, OBJECT, 'raw' );
				}
			}

			$term_obj = apply_filters( 'sucom_get_term_object', $term_obj, $term_id, $tax_slug );

			if ( $term_obj instanceof WP_Term ) {	// Just in case.

				switch ( $output ) {

					case 'id':
					case 'ID':
					case 'term_id':

						return isset( $term_obj->term_id ) ? (int) $term_obj->term_id : 0;	// Cast as integer.

					case 'taxonomy':

						return isset( $term_obj->taxonomy ) ? (string) $term_obj->taxonomy : '';	// Cast as string.

					default:

						return $term_obj;
				}
			}

			return false;
		}

		/*
		 * GET USER METHODS:
		 *
		 *	get_user_object()
		 *	get_users_names()
		 *	get_users_ids()
		 */
		public static function get_user_object( $user_id = 0, $output = 'object' ) {

			$user_obj = null;

			if ( $user_id instanceof WP_User ) {

				$user_obj = $user_id;

			} elseif ( is_numeric( $user_id ) && $user_id > 0 ) {

				$user_obj = get_userdata( $user_id );

			} elseif ( apply_filters( 'sucom_is_user_page', is_author() ) ) {

				$user_obj = get_query_var( 'author_name' ) ?
					get_user_by( 'slug', get_query_var( 'author_name' ) ) :
						get_userdata( get_query_var( 'author' ) );

			} elseif ( is_admin() ) {

				if ( '' === ( $user_id = self::get_request_value( 'user_id' ) ) ) {	// Uses sanitize_text_field().

					$user_id = get_current_user_id();
				}

				$user_obj = get_userdata( $user_id );
			}

			$user_obj = apply_filters( 'sucom_get_user_object', $user_obj, $user_id );

			if ( $user_obj instanceof WP_User ) {	// Just in case.

				switch ( $output ) {

					case 'id':
					case 'ID':
					case 'user_id':

						return isset( $user_obj->ID ) ? (int) $user_obj->ID : 0;	// Cast as integer.

					default:

						return $user_obj;
				}
			}

			return false;
		}

		/*
		 * Note that the 'wp_capabilities' meta value is a serialized array, so WordPress uses a LIKE query to match any
		 * string within the serialized array.
		 *
		 * Example query:
		 *
		 * 	SELECT wp_users.ID,wp_users.display_name
		 * 	FROM wp_users
		 * 	INNER JOIN wp_usermeta
		 * 	ON ( wp_users.ID = wp_usermeta.user_id )
		 * 	WHERE 1=1
		 * 	AND ( ( ( wp_usermeta.meta_key = 'wp_capabilities'
		 * 	AND wp_usermeta.meta_value LIKE '%\"person\"%' ) ) )
		 * 	ORDER BY display_name ASC
		 *
		 * If using the $limit argument, you must keep calling get_users_names() until it returns false.
		 */
		public static function get_users_names( $role = '', $blog_id = null, $limit = null ) {

			static $offset = null;

			if ( empty( $blog_id ) ) {

				$blog_id = get_current_blog_id();
			}

			if ( is_numeric( $limit ) ) {

				$offset = null === $offset ? 0 : $offset + $limit;
			}

			/*
			 * See https://developer.wordpress.org/reference/classes/wp_user_query/.
			 */
			$user_args = array(
				'blog_id' => $blog_id,
				'offset'  => null === $offset ? '' : $offset,	// Default value should be an empty string.
				'number'  => null === $limit ? '' : $limit,	// Default value should be an empty string.
				'order'   => 'ASC',	// Sort alphabetically by display name.
				'orderby' => 'display_name',
				'role'    => $role,
				'fields'  => array(	// Save memory and return only specific fields.
					'ID',
					'display_name',
				)
			);

			$users_names = array();

			/*
			 * See https://developer.wordpress.org/reference/classes/WP_User_Query/prepare_query/.
			 */
			foreach ( get_users( $user_args ) as $user_obj ) {

				$users_names[ $user_obj->ID ] = $user_obj->display_name;
			}

			if ( null !== $offset ) {	// Null, 0, or multiple of $limit integer.

				if ( empty( $users_names ) ) {

					$offset = null;	// Allow the next call to start fresh.

					return false;	// Break the calling while loop.
				}
			}

			return $users_names;
		}

		/*
		 * If using the $limit argument, you must keep calling get_users_ids() until it returns false.
		 *
		 * See WpssoRegister->uninstall_plugin().
		 * See WpssoUser->remove_person_role().
		 */
		public static function get_users_ids( $blog_id = null, $role = '', $limit = null ) {

			static $offset = null;

			if ( empty( $blog_id ) ) {

				$blog_id = get_current_blog_id();
			}

			if ( is_numeric( $limit ) ) {

				$offset = null === $offset ? 0 : $offset + $limit;
			}

			$user_args  = array(
				'blog_id' => $blog_id,
				'offset'  => null === $offset ? '' : $offset,	// Default value should be an empty string.
				'number'  => null === $limit ? '' : $limit,	// Default value should be an empty string.
				'order'   => 'DESC',	// Newest users first.
				'orderby' => 'ID',
				'role'    => $role,
				'fields'  => array(	// Save memory and return only specific fields.
					'ID',
				)
			);

			$users_ids = array();

			/*
			 * See https://developer.wordpress.org/reference/classes/WP_User_Query/prepare_query/.
			 */
			foreach ( get_users( $user_args ) as $user_obj ) {

				$users_ids[] = $user_obj->ID;
			}

			if ( null !== $offset ) {	// Null, 0, or multiple of $limit integer.

				if ( empty( $users_ids ) ) {

					$offset = null;	// Allow the next call to start fresh.

					return false;	// Break the calling while loop.
				}
			}

			return $users_ids;
		}

		/*
		 * OBJECT HANDLING METHODS:
		 *
		 *	get_object_label()
		 *	sort_objects_by_label()
		 *
		 * Add the slug (ie. name) to custom post type and taxonomy labels.
		 */
		public static function get_object_label( $obj ) {

			if ( empty( $obj->_builtin ) ) {	// Custom post type or taxonomy.

				return $obj->label . ' [' . $obj->name . ']';
			}

			return $obj->label;
		}

		public static function sort_objects_by_label( array &$objects ) {

			$assoc  = array();
			$sorted = array();

			foreach ( $objects as $num => $obj ) {

				if ( ! empty( $obj->labels->name ) ) {

					$sort_key = $obj->labels->name . '-' . $num;

				} elseif ( ! empty( $obj->label ) ) {

					$sort_key = $obj->label . '-' . $num;

				} else $sort_key = $obj->name . '-' . $num;

				$assoc[ $sort_key ] = $num;	// Make sure key is sortable and unique.
			}

			ksort( $assoc );

			foreach ( $assoc as $sort_key => $num ) {

				$sorted[] = $objects[ $num ];
			}

			unset( $assoc );

			return $objects = $sorted;
		}

		/*
		 * GET ROLES METHODS:
		 *
		 *	get_roles_users_names()
		 *	get_roles_users_ids()
		 *	get_roles_users_select()
		 */
		public static function get_roles_users_names( array $roles, $blog_id = null ) {

			if ( empty( $roles ) ) {

				return array();
			};

			if ( empty( $blog_id ) ) {

				$blog_id = get_current_blog_id();
			}

			$users_names = array();

			foreach ( $roles as $role ) {

				while ( $role_users = self::get_users_names( $role, $blog_id, $limit = 1000 ) ) {

					/*
					 * The union operator (+) gives priority to values in the first array, while
					 * array_replace() gives priority to values in the the second array.
					 */
					$users_names = $users_names + $role_users;	// Maintains numeric index.
				}
			}

			self::natasort( $users_names );	// Maintain ID => display_name association.

			return $users_names;
		}

		/*
		 * See WpssoUpgrade->options().
		 */
		public static function get_roles_users_ids( array $roles, $blog_id = null ) {

			/*
			 * Get the user ID => name associative array, and keep only the array keys.
			 */
			$users_ids = array_keys( self::get_roles_users_names( $roles, $blog_id ) );

			rsort( $users_ids );	// Newest user first.

			return $users_ids;
		}

		/*
		 * See WpssoUser->get_persons_names().
		 */
		public static function get_roles_users_select( array $roles, $blog_id = null, $add_none = true ) {

			$user_select = self::get_roles_users_names( $roles, $blog_id );

			if ( $add_none ) {

				/*
				 * The union operator (+) gives priority to values in the first array, while array_replace() gives
				 * priority to values in the the second array.
				 */
				$user_select = array( 'none' => 'none' ) + $user_select;	// Maintains numeric index.
			}

			return $user_select;
		}

		/*
		 * GET SITE INFORMATION METHODS:
		 *
		 * 	get_home_url()
		 * 	get_minimum_image_wh()
		 *	get_screen_base()
		 *	get_screen_id()
		 * 	get_site_name()
		 * 	get_site_name_alt()
		 * 	get_site_description()
		 * 	get_wp_config_file_path()
		 * 	get_wp_url()
		 *
		 * Returns the website URL from the options array, or the WordPress get_home_url() value.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_home_url( array $opts = array(), $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			$home_url = empty( $opts ) ? '' : SucomUtilOptions::get_key_value( 'site_home_url', $opts, $mixed );

			if ( empty( $home_url ) ) {	// Fallback to default WordPress value.

				$home_url = get_home_url( $blog_id = null, $path = '/', $scheme = null );
			}

			return apply_filters( 'sucom_get_home_url', $home_url );
		}

		/*
		 * Returns array( $min_width, $min_height, $size_count ).
		 *
		 * See WpssoMedia->show_post_upload_ui_message().
		 */
		public static function get_minimum_image_wh() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			global $_wp_additional_image_sizes;

			$min_width  = 0;
			$min_height = 0;
			$size_count = 0;

			foreach ( $_wp_additional_image_sizes as $size_name => $size_info ) {

				$size_count++;

				if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'width' ] ) ) {

					$width = intval( $_wp_additional_image_sizes[ $size_name ][ 'width' ] );

				} else $width = get_option( $size_name . '_size_w' );	// Returns false by default.

				if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'height' ] ) ) {

					$height = intval( $_wp_additional_image_sizes[ $size_name ][ 'height' ] );

				} else $height = get_option( $size_name . '_size_h' );	// Returns false by default.

				if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'crop' ] ) ) {

					$crop = $_wp_additional_image_sizes[ $size_name ][ 'crop' ];

				} else $crop = get_option( $size_name . '_crop' );	// Returns false by default.

				if ( ! is_array( $crop ) ) {

					$crop = empty( $crop ) ? false : true;
				}

				if ( $crop ) {

					if ( $width > $min_width ) {

						$min_width = $width;
					}

					if ( $height > $min_height ) {

						$min_height = $height;
					}

				} elseif ( $width < $height ) {

					if ( $width > $min_width ) {

						$min_width = $width;
					}

				} else {

					if ( $height > $min_height ) {

						$min_height = $height;
					}
				}
			}

			return $local_cache = array( $min_width, $min_height, $size_count );
		}

		/*
		 * Returns false or the admin screen base string.
		 */
		public static function get_screen_base( $screen = false ) {

			if ( false === $screen ) {

				if ( function_exists( 'get_current_screen' ) ) {

					$screen = get_current_screen();
				}
			}

			if ( isset( $screen->base ) ) {

				return $screen->base;
			}

			return false;
		}

		/*
		 * Returns false or the admin screen id string.
		 */
		public static function get_screen_id( $screen = false ) {

			if ( false === $screen ) {

				if ( function_exists( 'get_current_screen' ) ) {

					$screen = get_current_screen();
				}
			}

			if ( isset( $screen->id ) ) {

				return $screen->id;
			}

			return false;
		}

		/*
		 * Site Title.
		 *
		 * Returns a custom site name or the default WordPress site name.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_name( array $opts = array(), $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			$site_name = empty( $opts ) ? '' : SucomUtilOptions::get_key_value( 'site_name', $opts, $mixed );

			if ( empty( $site_name ) ) {	// Fallback to default WordPress value.

				$site_name = get_bloginfo( $show = 'name', $filter = 'raw' );
			}

			return $site_name;
		}

		/*
		 * Site Alternate Title.
		 *
		 * Returns a custom site alternate name or an empty string.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_name_alt( array $opts, $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return empty( $opts ) ? '' : SucomUtilOptions::get_key_value( 'site_name_alt', $opts, $mixed );
		}

		/*
		 * Site Tagline.
		 *
		 * Returns a custom site description or the default WordPress site description / tagline.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_description( array $opts = array(), $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			$site_desc = empty( $opts ) ? '' : SucomUtilOptions::get_key_value( 'site_desc', $opts, $mixed );

			if ( empty( $site_desc ) ) {	// Fallback to default WordPress value.

				$site_desc = get_bloginfo( $show = 'description', $filter = 'raw' );
			}

			return $site_desc;
		}

		public static function get_wp_config_file_path() {

			$parent_abspath = trailingslashit( dirname( ABSPATH ) );

			if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

				return ABSPATH . 'wp-config.php';

			} elseif ( file_exists( $parent_abspath . 'wp-config.php' ) && ! file_exists( $parent_abspath . 'wp-settings.php' ) ) {

				return $parent_abspath . 'wp-config.php';
			}

			return false;
		}

		/*
		 * Returns the WordPress installation URL from the options array, or the WordPress get_site_url() value.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_wp_url( array $opts = array(), $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			$wp_url = empty( $opts ) ? '' : SucomUtilOptions::get_key_value( 'site_wp_url', $opts, $mixed );

			if ( empty( $wp_url ) ) {	// Fallback to default WordPress value.

				$wp_url = get_site_url( $blog_id = null, $path = '/', $scheme = null );
			}

			return apply_filters( 'sucom_get_wp_url', $wp_url );
		}

		/*
		 * GET LOCALE METHODS:
		 *
		 * 	clear_locale_cache()
		 * 	get_available_feed_locale_names()
		 * 	get_available_locale_names()
		 * 	get_available_locales()
		 * 	get_locale()
		 */
		public static function clear_locale_cache() {

			self::$locale_cache = array();
		}

		/*
		 * Returns an associative array with locale keys and native names (example: 'en_US' => 'English (United States)').
		 */
		public static function get_available_feed_locale_names() {

			$locale_names = self::get_available_locale_names();	// Uses a local cache.

			$locale_names = apply_filters( 'sucom_available_feed_locale_names', $locale_names );

			return $locale_names;
		}

		/*
		 * Returns an associative array with locale keys and native names (example: 'en_US' => 'English (United States)').
		 */
		public static function get_available_locale_names() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/translation-install.php';

			$translations  = wp_get_available_translations();	// Array of translations, each an array of data, keyed by the language.
			$avail_locales = self::get_available_locales();		// Uses a local cache.
			$local_cache   = array();

			foreach ( $avail_locales as $locale ) {

				if ( isset( $translations[ $locale ][ 'native_name' ] ) ) {

					$native_name = $translations[ $locale ][ 'native_name' ];

				} elseif ( 'en' === $locale || 'en_US' === $locale ) {

					$native_name = 'English (United States)';

				} else $native_name = $locale;

				$local_cache[ $locale ] = $native_name;
			}

			$local_cache = apply_filters( 'sucom_available_locale_names', $local_cache );

			return $local_cache;
		}

		/*
		 * Returns an array of locales.
		 *
		 * See https://developer.wordpress.org/reference/functions/get_available_languages/.
		 */
		public static function get_available_locales() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			$def_locale  = self::get_locale( 'default' );	// Uses a local cache.
			$local_cache = get_available_languages();

			if ( ! is_array( $local_cache ) ) {	// Just in case.

				$local_cache = array( $def_locale );

			} elseif ( ! in_array( $def_locale, $local_cache ) ) {	// Just in case.

				$local_cache[] = $def_locale;
			}

			sort( $local_cache );

			$local_cache = apply_filters( 'sucom_available_locales', $local_cache );

			return $local_cache;
		}

		/*
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_locale( $mixed = 'current', $read_cache = true ) {

			if ( empty( $mixed ) ) {	// Just in case.

				$mixed = 'current';
			}

			/*
			 * If $mixed is an array, get its salt, otherwise use the string or post ID.
			 *
			 * Note that SucomUtil::get_mod_salt() does not include the page number or locale.
			 */
			$cache_index = is_array( $mixed ) ? self::get_mod_salt( $mixed ) : $mixed;

			if ( $read_cache ) {

				if ( isset( self::$locale_cache[ $cache_index ] ) ) {

					return self::$locale_cache[ $cache_index ];
				}
			}

			if ( 'default' === $mixed ) {

				global $wp_local_package;

				if ( isset( $wp_local_package ) ) {

					$locale = $wp_local_package;
				}

				if ( defined( 'WPLANG' ) ) {

					$locale = WPLANG;
				}

				/*
				 * The database 'WPLANG' values override the 'WPLANG' constant.
				 */
				if ( is_multisite() ) {

					if ( ( $multisite_locale = get_option( 'WPLANG' ) ) === false ) {

						$multisite_locale = get_site_option( 'WPLANG' );
					}

					if ( false !== $multisite_locale ) {

						$locale = $multisite_locale;
					}

				} else {

					$db_locale = get_option( 'WPLANG' );

					if ( false !== $db_locale ) {

						$locale = $db_locale;
					}
				}

			} elseif ( 'current' === $mixed || is_array( $mixed ) ) {

				$locale = get_locale();

			} elseif ( 'user' === $mixed ) {

				$locale = get_user_locale();

			} elseif ( is_string( $mixed ) ) {

				$locale_names = self::get_available_locale_names();	// Uses a local cache.

				if ( isset( $locale_names[ $mixed ] ) ) {

					$locale = $mixed;
				}
			}

			if ( empty( $locale ) ) {	// Just in case.

				$locale = 'en_US';
			}

			/*
			 * Filtered by WpssoIntegLangPolylang->filter_get_locale() and WpssoIntegLangWpml->filter_get_locale().
			 */
			$locale = apply_filters( 'sucom_get_locale', $locale, $mixed );

			return self::$locale_cache[ $cache_index ] = $locale;
		}

		/*
		 * ACTION AND FILTER METHODS:
		 *
		 *	do_shortcode_names()
		 *	get_filter_hook_ids()
		 *	get_filter_hook_names()
		 *	get_filter_hook_function()
		 *	remove_action_hook_name()
		 *	remove_filter_hook_name()
		 */
		public static function do_shortcode_names( array $do_names, $content, $ignore_html = false ) {

			if ( ! empty( $do_names ) ) {	// Just in case.

				global $shortcode_tags;

				$saved_tags = $shortcode_tags;	// Save the original registered shortcodes.

				$shortcode_tags = array();	// Init a new empty global shortcode tags array.

				foreach ( $do_names as $key ) {

					if ( isset( $saved_tags[ $key ] ) ) {

						$shortcode_tags[ $key ] = $saved_tags[ $key ];
					}
				}

				if ( ! empty( $shortcode_tags ) ) {	// Just in case.

					$content = do_shortcode( $content, $ignore_html );
				}

				$shortcode_tags = $saved_tags;	// Restore the original registered shortcodes.
			}

			return $content;
		}

		public static function get_filter_hook_ids( $filter_name ) {

			global $wp_filter;

			$hook_ids = array();

			if ( isset( $wp_filter[ $filter_name ]->callbacks ) ) {

				foreach ( $wp_filter[ $filter_name ]->callbacks as $hook_prio => $hook_group ) {

					foreach ( $hook_group as $hook_id => $hook_info ) {

						$hook_ids[] = $hook_id;
					}
				}
			}

			return $hook_ids;
		}

		public static function get_filter_hook_names( $filter_name ) {

			global $wp_filter;

			$hook_names = array();

			if ( isset( $wp_filter[ $filter_name ]->callbacks ) ) {

				foreach ( $wp_filter[ $filter_name ]->callbacks as $hook_prio => $hook_group ) {

					foreach ( $hook_group as $hook_id => $hook_info ) {

						/*
						 * Returns a function name or a class static method name.
						 *
						 * Class object methods are returned as class static method names.
						 */
						if ( $hook_name = self::get_filter_hook_function( $hook_info ) ) {

							$hook_names[] = $hook_name;
						}
					}
				}
			}

			return $hook_names;
		}

		/*
		 * Returns a function name or a class static method name.
		 *
		 * Class object methods are returned as class static method names.
		 */
		public static function get_filter_hook_function( array $hook_info ) {

			$hook_name = '';

			if ( isset( $hook_info[ 'function' ] ) ) {

				/*
				 * The callback hook is a dynamic or static method.
				 */
				if ( is_array( $hook_info[ 'function' ] ) ) {

					$class_name    = '';
					$function_name = '';

					/*
					 * The callback hook is a dynamic method.
					 */
					if ( is_object( $hook_info[ 'function' ][ 0 ] ) ) {

						$class_name = get_class( $hook_info[ 'function' ][ 0 ] );

					/*
					 * The callback hook is a static method.
					 */
					} elseif ( is_string( $hook_info[ 'function' ][ 0 ] ) ) {

						$class_name = $hook_info[ 'function' ][ 0 ];
					}

					if ( is_string( $hook_info[ 'function' ][ 1 ] ) ) {

						$function_name = $hook_info[ 'function' ][ 1 ];
					}

					/*
					 * Return a static method name.
					 */
					$hook_name = $class_name . '::' . $function_name;

				/*
				 * The callback hook is a function.
				 */
				} elseif ( is_string( $hook_info[ 'function' ] ) ) {

					$hook_name = $hook_info[ 'function' ];
				}
			}

			return $hook_name;
		}

		public static function remove_action_hook_name( $filter_name, $hook_name ) {

			self::remove_filter_hook_name( $filter_name, $hook_name );
		}

		/*
		 * Filters and actions are both saved in the $wp_filter global variable.
		 *
		 * Loop through all action/filter hooks and remove any that match the given function or static method name.
		 *
		 * Note that class object methods are matched using a class static method name.
		 */
		public static function remove_filter_hook_name( $filter_name, $hook_name ) {

			global $wp_filter;

			if ( isset( $wp_filter[ $filter_name ]->callbacks ) ) {

				foreach ( $wp_filter[ $filter_name ]->callbacks as $hook_prio => $hook_group ) {

					foreach ( $hook_group as $hook_id => $hook_info ) {

						/*
						 * Returns a function name or a class static method name.
						 *
						 * Class object methods are returned as class static method names.
						 */
						if ( self::get_filter_hook_function( $hook_info ) === $hook_name ) {

							unset( $wp_filter[ $filter_name ]->callbacks[ $hook_prio ][ $hook_id ] );
						}
					}
				}
			}
		}

		/*
		 * CACHE HANDLING METHODS:
		 *
		 *	get_update_meta_cache().
		 *	update_transient_array().
		 */
		public static function get_update_meta_cache( $obj_id, $meta_type ) {

			if ( ! is_numeric( $obj_id ) || empty( $meta_type ) ) {

				return array();
			}

			$obj_id = absint( $obj_id );

			if ( ! $obj_id ) {

				return array();
			}

			/*
			 * WordPress stores data using a post, term, or user ID, along with a group string.
			 *
			 * Example: wp_cache_get( 1, 'user_meta' );
			 *
			 * Returns (bool|mixed) false on failure to retrieve contents or the cache contents on success.
			 *
			 * $found (bool) Whether the key was found in the cache (passed by reference) - disambiguates a return of false.
			 */
			$found = null;

			$metadata = wp_cache_get( $obj_id, $meta_type . '_meta', $force = false, $found );

			if ( $found ) return $metadata;

			/*
			 * $meta_type (string) Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
			 * or any other object type with an associated meta table.
			 *
			 * Returns (array|false) metadata cache for the specified objects, or false on failure.
			 */
			$metadata = update_meta_cache( $meta_type, array( $obj_id ) );

			return $metadata[ $obj_id ];
		}

		/*
		 * Update the cached array and maintain the existing transient expiration time.
		 */
		public static function update_transient_array( $cache_id, $cache_array, $cache_exp_secs ) {

			$current_time  = time();
			$reset_at_secs = 300;

			/*
			 * If the $cache_array already has a '__created_at' value, then calculate how long until the transient
			 * object expires, and set the transient with that new expiration seconds.
			 */
			if ( isset( $cache_array[ '__created_at' ] ) ) {

				/*
				 * Adjust the expiration time by removing the difference (current time less creation time) from the
				 * desired transient expiration seconds.
				 */
				$transient_exp_secs = $cache_exp_secs - ( $current_time - $cache_array[ '__created_at' ] );

				/*
				 * If we're 300 seconds (5 minutes) or less from the transient expiring, then renew the transient
				 * creation and expiration times.
				 */
				if ( $transient_exp_secs < $reset_at_secs ) {

					$transient_exp_secs = $cache_exp_secs;

					$cache_array[ '__created_at' ] = $current_time;
				}

			} else {

				$transient_exp_secs = $cache_exp_secs;

				$cache_array[ '__created_at' ] = $current_time;
			}

			set_transient( $cache_id, $cache_array, $transient_exp_secs );

			return $transient_exp_secs;
		}

		/*
		 * OPTIONS KEY METHODS:
		 *
		 *	add_options_key()
		 *	delete_options_key()
		 *	get_options_key()
		 *	update_options_key()
		 *	add_site_options_key()
		 *	delete_site_options_key()
		 *	get_site_options_key()
		 *	update_site_options_key()
		 *
		 * Add an element to an options array, if the array key does not already exist.
		 */
		public static function add_options_key( $options_name, $key, $value ) {

			return self::update_options_key( $options_name, $key, $value, $protect = true, $site = false );
		}

		/*
		 * Delete an element from an options array, if the array key exists.
		 */
		public static function delete_options_key( $options_name, $key, $site = false ) {

			$opts = $site ? get_site_option( $options_name, $default = array() ) : get_option( $options_name, $default = array() );

			if ( ! array_key_exists( $key, $opts ) ) {	// Nothing to do.

				return false;	// No update.
			}

			unset( $opts[ $key ] );

			if ( empty( $opts ) ) {	// Just in case.

				return $site ? delete_site_option( $options_name ) : delete_option( $options_name );
			}

			return $site ? update_site_option( $options_name, $opts ) : update_option( $options_name, $opts );
		}

		/*
		 * Get an element from an options array. Returns null if the array key does not exist.
		 */
		public static function get_options_key( $options_name, $key, $site = false ) {

			$opts = $site ? get_site_option( $options_name, $default = array() ) : get_option( $options_name, $default = array() );

			if ( array_key_exists( $key, $opts ) ) {

				return $opts[ $key ];
			}

			return null;	// No value.
		}

		/*
		 * Update an options array element, if the array key does not exit, or its value is different.
		 *
		 * Use $protect = true to prevent overwriting an existing value.
		 */
		public static function update_options_key( $options_name, $key, $value, $protect = false, $site = false ) {

			$opts = $site ? get_site_option( $options_name, $default = array() ) :	// Returns an array by default.
				get_option( $options_name, $default = array() );		// Returns an array by default.

			if ( array_key_exists( $key, $opts ) ) {

				if ( $protect ) {	// Prevent overwriting an existing value.

					return false;	// No update.

				} elseif ( $value === $opts[ $key ] ) {	// Nothing to do.

					return false;	// No update.
				}
			}

			$opts[ $key ] = $value;

			return $site ? update_site_option( $options_name, $opts ) : update_option( $options_name, $opts );
		}

		/*
		 * Add an element to a site options array, if the array key does not already exist.
		 */
		public static function add_site_options_key( $options_name, $key, $value ) {

			return self::update_options_key( $options_name, $key, $value, $protect = true, $site = true );
		}

		/*
		 * Delete an element from a site options array, if the array key exists.
		 */
		public static function delete_site_options_key( $options_name, $key ) {

			return self::delete_options_key( $options_name, $key, $site = true );
		}

		/*
		 * Get an element from a site options array. Returns null if the array key does not exist.
		 */
		public static function get_site_options_key( $options_name, $key ) {

			return self::get_options_key( $options_name, $key, $site = true );
		}

		/*
		 * Update a site options array element, if the array key does not exit, or its value is different.
		 *
		 * Use $protect = true to prevent overwriting an existing value.
		 */
		public static function update_site_options_key( $options_name, $key, $value, $protect = false ) {

			return self::update_options_key( $options_name, $key, $value, $protect, $site = true );
		}

		/*
		 * RAW UNFILTERED METHODS:
		 *
		 *	raw_update_post()
		 *	raw_update_post_title()
		 *	raw_update_post_title_content()
		 *	raw_metadata_exists()
		 *	raw_wp_get_shortlink()
		 *	raw_home_url()
		 *	raw_get_home_url()
		 *	raw_site_url()
		 *	raw_get_site_url()
		 *	raw_set_url_scheme()
		 *	raw_do_option()
		 *	raw_delete_transient()
		 *	raw_get_transient()
		 *	raw_set_transient()
		 */
		public static function raw_update_post( $post_id, array $args ) {

			if ( wp_is_post_revision( $post_id ) ) {

			        $post_id = wp_is_post_revision( $post_id );
			}

			if ( ! is_numeric( $post_id ) ) {	// Just in case.

				return false;
			}

			global $wpdb;

			$post_id = absint( $post_id );
			$where   = array( 'ID' => $post_id );

			foreach ( $args as $field => $value ) {

				$args[ $field ] = sanitize_post_field( $field, $value, $post_id, $context = 'db' );
			}

			return $wpdb->update( $wpdb->posts, $args, $where );
		}

		public static function raw_update_post_title( $post_id, $post_title ) {

			$post_title = sanitize_text_field( $post_title );
			$post_name  = sanitize_title( $post_title );

			$args = array(
				'post_title' => $post_title,
				'post_name'  => $post_name,
			);

			return self::raw_update_post( $post_id, $args );
		}

		public static function raw_update_post_title_content( $post_id, $post_title, $post_content ) {

			$post_title   = sanitize_text_field( $post_title );
			$post_name    = sanitize_title( $post_title );
			$post_content = wp_kses_post( $post_content );	// KSES (Kses Strips Evil Scripts).

			$args = array(
				'post_title'   => $post_title,
				'post_name'    => $post_name,
				'post_content' => $post_content,
			);

			return self::raw_update_post( $post_id, $args );
		}

		public static function raw_metadata_exists( $meta_type, $obj_id, $meta_key ) {

			$metadata = self::get_update_meta_cache( $obj_id, $meta_type );

			return isset( $metadata[ $obj_id ][ $meta_key ] ) ? true : false;
		}

		/*
		 * Unfiltered version of wp_get_shortlink() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.0.3 on 2019/01/29.
		 */
		public static function raw_wp_get_shortlink( $id = 0, $context = 'post', $allow_slugs = true ) {

			$post_id = 0;

			if ( 'query' === $context && is_singular() ) {

				$post_id = get_queried_object_id();

				$post = get_post( $post_id );

			} elseif ( 'post' === $context ) {

				$post = get_post( $id );

				if ( ! empty( $post->ID ) ) {

					$post_id = $post->ID;
				}
			}

			$shortlink = '';

			if ( ! empty( $post_id ) ) {

				$post_type_obj = get_post_type_object( $post->post_type );

				if ( 'page' === $post->post_type &&
					(int) $post->ID === (int) get_option( 'page_on_front' ) &&
						'page' === get_option( 'show_on_front' ) ) {

					$shortlink = self::raw_home_url( '/' );

				} elseif ( ! empty( $post_type_obj->public ) ) {

					$shortlink = self::raw_home_url( '?p=' . $post_id );
				}
			}

			return $shortlink;
		}

		/*
		 * Unfiltered version of home_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_home_url( $path = '', $scheme = null ) {

			return self::raw_get_home_url( null, $path, $scheme );
		}

		/*
		 * Unfiltered version of get_home_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_get_home_url( $blog_id = null, $path = '', $scheme = null ) {

			$is_multisite = is_multisite();

			if ( empty( $blog_id ) || ! $is_multisite ) {

				/*
				 * The WordPress _config_wp_home() function is hooked to the 'option_home' filter in order to
				 * override the database value. Since we're not using the default filters, check for WP_HOME or
				 * WP_SITEURL and update the stored database value if necessary.
				 *
				 * The homepage of the website:
				 *
				 *	WP_HOME
				 *	home_url()
				 *	get_home_url()
				 *	Site Address (URL)
				 *	http://example.com
				 *
				 * The WordPress installation (ie. where you can reach the site by adding /wp-admin):
				 *
				 *	WP_SITEURL
				 *	site_url()
				 *	get_site_url()
				 *	WordPress Address (URL)
				 *	http://example.com/wp/
				 */
				if ( ! $is_multisite && defined( 'WP_HOME' ) && WP_HOME ) {

					$url = untrailingslashit( WP_HOME );

					$db_url = self::raw_do_option( $action = 'get', $opt_name = 'home' );

					if ( $db_url !== $url ) {

						self::raw_do_option( $action = 'update', $opt_name = 'home', $url );
					}

				} else $url = self::raw_do_option( $action = 'get', $opt_name = 'home' );

			} else {

				switch_to_blog( $blog_id );

				$url = self::raw_do_option( $action = 'get', $opt_name = 'home' );

				restore_current_blog();
			}

			if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), $strict = true ) ) {

				if ( is_ssl() ) {

					$scheme = 'https';

				} else $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
			}

			$url = self::raw_set_url_scheme( $url, $scheme );

			if ( $path && is_string( $path ) ) {

				$url .= '/' . ltrim( $path, '/' );
			}

			return $url;
		}

		/*
		 * Unfiltered version of site_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_site_url( $path = '', $scheme = null ) {

			return self::raw_get_site_url( null, $path, $scheme );
		}

		/*
		 * Unfiltered version of get_site_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_get_site_url( $blog_id = null, $path = '', $scheme = null ) {

			$is_multisite = is_multisite();

			if ( empty( $blog_id ) || ! $is_multisite ) {

				/*
				 * The WordPress _config_wp_home() function is hooked to the 'option_home' filter in order to
				 * override the database value. Since we're not using the default filters, check for WP_HOME or
				 * WP_SITEURL and update the stored database value if necessary.
				 *
				 * The homepage of the website:
				 *
				 *	WP_HOME
				 *	home_url()
				 *	get_home_url()
				 *	Site Address (URL)
				 *	http://example.com
				 *
				 * The WordPress installation (ie. where you can reach the site by adding /wp-admin):
				 *
				 *	WP_SITEURL
				 *	site_url()
				 *	get_site_url()
				 *	WordPress Address (URL)
				 *	http://example.com/wp/
				 */
				if ( ! $is_multisite && defined( 'WP_SITEURL' ) && WP_SITEURL ) {

					$url = untrailingslashit( WP_SITEURL );

					$db_url = self::raw_do_option( $action = 'get', $opt_name = 'siteurl' );

					if ( $db_url !== $url ) {

						self::raw_do_option( $action = 'update', $opt_name = 'siteurl', $url );
					}

				} else $url = self::raw_do_option( $action = 'get', $opt_name = 'siteurl' );

			} else {

				switch_to_blog( $blog_id );

				$url = self::raw_do_option( $action = 'get', $opt_name = 'siteurl' );

				restore_current_blog();
			}

			$url = self::raw_set_url_scheme( $url, $scheme );

			if ( $path && is_string( $path ) ) {

				$url .= '/' . ltrim( $path, '/' );
			}

			return $url;
		}

		/*
		 * Unfiltered version of set_url_scheme() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		private static function raw_set_url_scheme( $url, $scheme = null ) {

			if ( ! $scheme ) {

				$scheme = is_ssl() ? 'https' : 'http';

			} elseif ( 'admin' === $scheme || 'login' === $scheme || 'login_post' === $scheme || 'rpc' === $scheme ) {

				$scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';

			} elseif ( 'http' !== $scheme && 'https' !== $scheme && 'relative' !== $scheme ) {

				$scheme = is_ssl() ? 'https' : 'http';
			}

			$url = trim( $url );

			if ( substr( $url, 0, 2 ) === '//' ) {

				$url = 'http:' . $url;
			}

			if ( 'relative' === $scheme ) {

				$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );

				if ( '' !== $url && '/' === $url[ 0 ] ) {

					$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
				}

			} else $url = preg_replace( '#^\w+://#', $scheme . '://', $url );

			return $url;
		}

		/*
		 * Temporarily disable filters and actions hooks before calling get_option(), update_option(), and delete_option().
		 */
		public static function raw_do_option( $action, $opt_name, $value = null, $default = false ) {

			global $wp_filter, $wp_actions;

			$saved_filter  = $wp_filter;
			$saved_actions = $wp_actions;

			$wp_filter  = array();
			$wp_actions = array();

			$success   = null;
			$old_value = false;

			switch( $action ) {

				case 'get':
				case 'get_option':

					$success = get_option( $opt_name, $default );

					break;

				case 'update':
				case 'update_option':

					$old_value = get_option( $opt_name, $default );

					$success = update_option( $opt_name, $value );

					break;

				case 'delete':
				case 'delete_option':

					$success = delete_option( $opt_name );

					break;
			}

			$wp_filter  = $saved_filter;
			$wp_actions = $saved_actions;

			unset( $saved_filter, $saved_actions );

			switch( $action ) {

				case 'update':
				case 'update_option':

					do_action( 'sucom_update_option_' . $opt_name, $old_value, $value, $opt_name );

					break;
			}

			return $success;
		}

		public static function raw_delete_transient( $transient ) {

			if ( wp_using_ext_object_cache() ) {

				$result = wp_cache_delete( $transient, 'transient' );

			} else {

				$option_timeout = '_transient_timeout_' . $transient;
				$option         = '_transient_' . $transient;
				$result         = delete_option( $option );

				if ( $result ) {

					delete_option( $option_timeout );
				}
			}

			return $result;
		}

		public static function raw_get_transient( $transient ) {

			if ( wp_using_ext_object_cache() ) {

				$value = wp_cache_get( $transient, 'transient' );

			} else {

				$transient_option = '_transient_' . $transient;

				if ( ! wp_installing() ) {

					/*
					 * If option is not in alloptions, it is not autoloaded and thus has a timeout.
					 */
					$alloptions = wp_load_alloptions();

					if ( ! isset( $alloptions[ $transient_option ] ) ) {

						$transient_timeout = '_transient_timeout_' . $transient;

						$timeout = get_option( $transient_timeout );	// Returns false by default.

						if ( false !== $timeout && $timeout < time() ) {

							delete_option( $transient_option );
							delete_option( $transient_timeout );

							$value = false;
						}
					}
				}

				if ( ! isset( $value ) ) {

					$value = get_option( $transient_option );	// Returns false by default.
				}
			}

			return $value;
		}

		public static function raw_set_transient( $transient, $value, $expiration = 0 ) {

			$expiration = (int) $expiration;

			if ( wp_using_ext_object_cache() ) {

				$result = wp_cache_set( $transient, $value, 'transient', $expiration );

			} else {

				$transient_timeout = '_transient_timeout_' . $transient;
				$transient_option  = '_transient_' . $transient;

				if ( false === get_option( $transient_option ) ) {	// Returns false by default.

					$autoload = 'yes';

					/*
					 * If we have an expiration time, do not autoload the transient.
					 */
					if ( $expiration ) {

						$autoload = 'no';

						add_option( $transient_timeout, time() + $expiration, '', 'no' );
					}

					$result = add_option( $transient_option, $value, '', $autoload );

				} else {

					/*
					 * If an expiration time is provided, but the existing transient does not have a timeout
					 * value, delete, then re-create the transient with an expiration time.
					 */
					$update = true;

					if ( $expiration ) {

						if ( false === get_option( $transient_timeout ) ) {	// Returns false by default.

							delete_option( $transient_option );

							add_option( $transient_timeout, time() + $expiration, '', 'no' );

							$result = add_option( $transient_option, $value, '', 'no' );

							$update = false;

						} else {

							update_option( $transient_timeout, time() + $expiration );
						}
					}

					if ( $update ) {

						$result = update_option( $transient_option, $value );
					}
				}
			}

			return $result;
		}
	}
}

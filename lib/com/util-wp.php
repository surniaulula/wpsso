<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

If ( ! class_exists( 'SucomUtilWP' ) ) {

	class SucomUtilWP {

		public function __construct() {}

		public static function doing_ajax() {

			if ( function_exists( 'wp_doing_ajax' ) ) {	// Since WP v4.7.0.

				return wp_doing_ajax();
			}

			return defined( 'DOING_AJAX' ) ? DOING_AJAX : false;
		}

		public static function doing_autosave() {

			return defined( 'DOING_AUTOSAVE' ) ? DOING_AUTOSAVE : false;
		}

		public static function doing_block_editor() {

			static $is_doing = null;

			/*
			 * Optimize - once true, stay true.
			 */
			if ( $is_doing ) {

				return true;
			}

			$is_doing      = false;
			$post_id       = false;
			$can_edit_id   = false;
			$can_edit_type = false;
			$req_action    = empty( $_REQUEST[ 'action' ] ) ? false : $_REQUEST[ 'action' ];
			$is_meta_box   = empty( $_REQUEST[ 'meta-box-loader' ] ) && empty( $_REQUEST[ 'meta_box' ] ) ? false : true;
			$is_gutenbox   = empty( $_REQUEST[ 'gutenberg_meta_boxes' ] ) ? false : true;
			$is_classic    = isset( $_REQUEST[ 'classic-editor' ] ) && empty( $_REQUEST[ 'classic-editor' ] ) ? false : true;

			if ( ! empty( $_REQUEST[ 'post_ID' ] ) ) {

				$post_id = $_REQUEST[ 'post_ID' ];

			} elseif ( ! empty( $_REQUEST[ 'post' ] ) && is_numeric( $_REQUEST[ 'post' ] ) ) {

				$post_id = $_REQUEST[ 'post' ];
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

					} else {

						if ( use_block_editor_for_post( $post_id ) ) {

							$can_edit_id = true;
						}
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

					$is_doing = true;

				} elseif ( $is_meta_box ) {

					$is_doing = true;

				} elseif ( ! $is_classic ) {

					$is_doing = true;

				} elseif ( $post_id && $req_action === 'edit' ) {

					$is_doing = true;
				}
			}

			return $is_doing;
		}

		public static function doing_cron() {

			if ( function_exists( 'wp_doing_cron' ) ) {	// Since WP v4.8.0.

				return wp_doing_cron();
			}

			return defined( 'DOING_CRON' ) ? DOING_CRON : false;
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

		public static function oembed_enabled() {

			if ( function_exists( 'get_oembed_response_data' ) ) {	// Since WP v4.4.

				return true;
			}

			return false;
		}

		public static function sitemaps_disabled() {

			return self::sitemaps_enabled() ? false : true;
		}

		public static function sitemaps_enabled() {

			static $locale_cache = null;

			if ( null === $locale_cache ) {

				global $wp_sitemaps;

				if ( is_callable( array( $wp_sitemaps, 'sitemaps_enabled' ) ) ) {	// Since WP v5.5.

					$locale_cache = (bool) $wp_sitemaps->sitemaps_enabled();

				} else {

					$locale_cache = false;
				}
			}

			return $locale_cache;
		}

		/*
		 * Calls WP_Query->query() with the supplied arguments.
		 *
		 * Alternative to the WordPress get_posts() function, which sets 'ignore_sticky_posts' and 'no_found_rows' to true.
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

				$args[ 'posts_per_page' ] = count( $args[ 'post__in' ] );  // Only the number of posts included.

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
			$wp_query = new WP_Query;

			if ( ( ! isset( $args[ 'paged' ] ) || false === $args[ 'paged' ] ) &&
				( ! isset( $args[ 'posts_per_page' ] ) || -1 === $args[ 'posts_per_page' ] ) ) {

				$args[ 'paged' ] = 1;

				$args[ 'posts_per_page' ] = defined( 'SUCOM_GET_POSTS_WHILE_PPP' ) ? SUCOM_GET_POSTS_WHILE_PPP : 1000;

				$posts = array();

				while ( $result = $wp_query->query( $args ) ) {

					$posts = array_merge( $posts, $result );

					$args[ 'paged' ]++;	// Get the next page.
				}

				return $posts;
			}

			return $wp_query->query( $args );
		}

		/*
		 * Retrieves or updates the metadata cache by key and group.
		 *
		 * Usually called by an extended class (WpssoComment, WpssoPost, WpssoTerm, or WpssoUser), which hardcodes the
		 * $meta_type value to 'comment', 'post', 'term', or 'user'.
		 */
		public static function get_update_meta_cache( $obj_id, $meta_type ) {

			if ( ! $meta_type || ! is_numeric( $obj_id ) ) {

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

			if ( $found ) {

				return $metadata;
			}

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
		 * Filters and actions are both saved in the $wp_filter global variable.
		 */
		public static function remove_action_hook_name( $filter_name, $hook_name ) {

			self::remove_filter_hook_name( $filter_name, $hook_name );
		}

		/*
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
						if ( self::get_hook_function_name( $hook_info ) === $hook_name ) {

							unset( $wp_filter[ $filter_name ]->callbacks[ $hook_prio ][ $hook_id ] );
						}
					}
				}
			}
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
						if ( $hook_name = self::get_hook_function_name( $hook_info ) ) {

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
		public static function get_hook_function_name( array $hook_info ) {

			$hook_name = '';

			if ( isset( $hook_info[ 'function' ] ) ) {

				/*
				 * The callback hook is a dynamic or static method.
				 */
				if ( is_array( $hook_info[ 'function' ] ) ) {

					$class_name = '';

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

		public static function do_shortcode_names( array $shortcode_names, $content, $ignore_html = false ) {

			if ( ! empty( $shortcode_names ) ) {	// Just in case.

				global $shortcode_tags;

				$registered_tags = $shortcode_tags;	// Save the original registered shortcodes.

				$shortcode_tags = array();	// Init a new empty shortcode tags array.

				foreach ( $shortcode_names as $key ) {

					if ( isset( $registered_tags[ $key ] ) ) {

						$shortcode_tags[ $key ] = $registered_tags[ $key ];
					}
				}

				if ( ! empty( $shortcode_tags ) ) {	// Just in case.

					$content = do_shortcode( $content, $ignore_html );
				}

				$shortcode_tags = $registered_tags;	// Restore the original registered shortcodes.
			}

			return $content;
		}

		public static function get_theme_slug_version( $stylesheet = null, $theme_root = null ) {

			$theme = wp_get_theme( $stylesheet, $theme_root );

			return $theme->get_template() . '-' . $theme->Version;
		}

		public static function get_theme_header_file_paths( $skip_backups = true ) {

			$parent_tmpl_dir   = get_template_directory();
			$child_tmpl_dir    = get_stylesheet_directory();
			$header_file_paths = array();
			$tmpl_file_paths   = (array) glob( $parent_tmpl_dir . '/header*.php' );	// Returns false on error.

			if ( $parent_tmpl_dir !== $child_tmpl_dir ) {

				$tmpl_file_paths = array_merge( $tmpl_file_paths, (array) glob( $child_tmpl_dir . '/header*.php' ) );
			}

			foreach ( $tmpl_file_paths as $tmpl_file ) {

				if ( $skip_backups && preg_match( '/^.*\.php~.*$/', $tmpl_file ) ) {	// Skip backup files.

					continue;
				}

				$header_file_paths[ basename( $tmpl_file ) ] = $tmpl_file;	// Child template overwrites parent.
			}

			return $header_file_paths;
		}

		public static function get_wp_config_file_path() {

			$parent_abspath = trailingslashit( dirname( ABSPATH ) );

			$wp_config_file_path = false;

			/*
			 * The config file resides in ABSPATH.
			 */
			if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

				$wp_config_file_path = ABSPATH . 'wp-config.php';

			/*
			 * The config file resides one level above ABSPATH and is not part of another installation.
			 */
			} elseif ( file_exists( $parent_abspath . 'wp-config.php' ) && ! file_exists( $parent_abspath . 'wp-settings.php' ) ) {

				$wp_config_file_path = $parent_abspath . 'wp-config.php';

			}

			return $wp_config_file_path;
		}

		/*
		 * Some themes and plugins have been known to hook the WordPress 'get_shortlink' filter and return an empty URL to
		 * disable the WordPress shortlink meta tag. This breaks the WordPress wp_get_shortlink() function and is a
		 * violation of the WordPress theme guidelines.
		 *
		 * This method calls the WordPress wp_get_shortlink() function, and if an empty string is returned, calls an
		 * unfiltered version of the same function.
		 *
		 * $context = 'blog', 'post' (default), 'media', or 'query'
		 */
		public static function wp_get_shortlink( $id = 0, $context = 'post', $allow_slugs = true ) {

			$shortlink = wp_get_shortlink( $id, $context, $allow_slugs );	// Since WP v3.0.

			if ( empty( $shortlink ) || ! is_string( $shortlink) || false === filter_var( $shortlink, FILTER_VALIDATE_URL ) ) {

				$shortlink = self::raw_wp_get_shortlink( $id, $context, $allow_slugs );
			}

			return $shortlink;
		}

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

				} else {

					$url = self::raw_do_option( $action = 'get', $opt_name = 'home' );
				}

			} else {

				switch_to_blog( $blog_id );

				$url = self::raw_do_option( $action = 'get', $opt_name = 'home' );

				restore_current_blog();
			}

			if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), $strict = true ) ) {

				if ( is_ssl() ) {

					$scheme = 'https';

				} else {

					$scheme = parse_url( $url, PHP_URL_SCHEME );
				}
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

				} else {

					$url = self::raw_do_option( $action = 'get', $opt_name = 'siteurl' );
				}

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

			} else {

				$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
			}

			return $url;
		}

		/*
		 * Temporarily disable filter and action hooks before calling get_option(), update_option(), and delete_option().
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

		/*
		 * Deprecated on 2022/03/03.
		 */
		public static function get_post_types( $output = 'objects', $sort = false, $args = null ) {

			return SucomUtil::get_post_types( $output, $sort, $args );
		}

		/*
		 * Deprecated on 2022/03/03.
		 */
		public static function get_post_type_labels( $val_prefix = '', $label_prefix = '', $objects = null ) {

			return SucomUtil::get_post_type_labels( $val_prefix, $label_prefix, $objects );
		}

		/*
		 * Deprecated on 2022/03/03.
		 */
		public static function get_taxonomies( $output = 'objects', $sort = false, $args = null ) {

			return SucomUtil::get_taxonomies( $output, $sort, $args );
		}

		/*
		 * Deprecated on 2022/03/03.
		 */
		public static function get_taxonomy_labels( $val_prefix = '', $label_prefix = '', $objects = null ) {

			return SucomUtil::get_taxonomy_labels( $val_prefix, $label_prefix, $objects );
		}
	}
}

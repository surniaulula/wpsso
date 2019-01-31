<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomUtilWP' ) ) {

	class SucomUtilWP {

		protected static $cache_user_exists = array();	// Saved user_exists() values.

		/**
		 * wp_encode_emoji() is only available since WordPress v4.2.
		 * Use the WordPress function if available, otherwise provide the same functionality.
		 */
		public static function wp_encode_emoji( $content ) {

			if ( function_exists( 'wp_encode_emoji' ) ) {

				return wp_encode_emoji( $content ); // Since wp 4.2.

			} elseif ( function_exists( 'mb_convert_encoding' ) ) {

				$regex = '/(
				     \x23\xE2\x83\xA3               # Digits
				     [\x30-\x39]\xE2\x83\xA3
				   | \xF0\x9F[\x85-\x88][\xA6-\xBF] # Enclosed characters
				   | \xF0\x9F[\x8C-\x97][\x80-\xBF] # Misc
				   | \xF0\x9F\x98[\x80-\xBF]        # Smilies
				   | \xF0\x9F\x99[\x80-\x8F]
				   | \xF0\x9F\x9A[\x80-\xBF]        # Transport and map symbols
				)/x';

				if ( preg_match_all( $regex, $content, $all_matches ) ) {

					if ( ! empty( $all_matches[1] ) ) {

						foreach ( $all_matches[1] as $emoji ) {

							$unpacked = unpack( 'H*', mb_convert_encoding( $emoji, 'UTF-32', 'UTF-8' ) );

							if ( isset( $unpacked[1] ) ) {
								$entity = '&#x' . ltrim( $unpacked[1], '0' ) . ';';
								$content = str_replace( $emoji, $entity, $content );
							}
						}
					}
				}
			}
			return $content;
		}

		/**
		 * Some themes and plugins have been known to hook the WordPress 'get_shortlink' filter 
		 * and return an empty URL to disable the WordPress shortlink meta tag. This breaks the 
		 * WordPress wp_get_shortlink() function and is a violation of the WordPress theme 
		 * guidelines.
		 *
		 * This method calls the WordPress wp_get_shortlink() function, and if an empty string 
		 * is returned, calls an unfiltered version of the same function.
		 *
		 * $context = 'blog', 'post' (default), 'media', or 'query'
		 */
		public static function wp_get_shortlink( $id = 0, $context = 'post', $allow_slugs = true ) {

			$shortlink = '';

			if ( function_exists( 'wp_get_shortlink' ) ) {
				$shortlink = wp_get_shortlink( $id, $context, $allow_slugs ); // Since wp 3.0.
			}

			if ( empty( $shortlink ) || ! is_string( $shortlink) || filter_var( $shortlink, FILTER_VALIDATE_URL ) === false ) {
				$shortlink = self::raw_wp_get_shortlink( $id, $context, $allow_slugs );
			}

			return $shortlink;
		}

		/**
		 * Unfiltered version of wp_get_shortlink() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v5.0.3 on 2019/01/29.
		 */
		public static function raw_wp_get_shortlink( $id = 0, $context = 'post', $allow_slugs = true ) {
		
			$post_id = 0;
			
			if ( 'query' === $context && is_singular() ) {

				$post_id = get_queried_object_id();
				$post    = get_post( $post_id );

			} elseif ( 'post' === $context ) {

				$post = get_post( $id );

				if ( ! empty( $post->ID ) ) {
					$post_id = $post->ID;
				}
			}

			$shortlink = '';

			if ( ! empty( $post_id ) ) {

				$post_type = get_post_type_object( $post->post_type ); 

				if ( 'page' === $post->post_type && $post->ID == get_option( 'page_on_front' ) && 'page' == get_option( 'show_on_front' ) ) {

					$shortlink = self::raw_home_url( '/' );

				} elseif ( ! empty( $post_type->public ) ) {

					$shortlink = self::raw_home_url( '?p=' . $post_id );
				}
			} 
			
			return $shortlink;
		}

		/**
		 * Unfiltered version of home_url() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v5.0.3 on 2019/01/28.
		 */
		public static function raw_home_url( $path = '', $scheme = null ) {

			return self::raw_get_home_url( null, $path, $scheme );
		}

		/**
		 * Unfiltered version of get_home_url() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v5.0.3 on 2019/01/28.
		 */
		public static function raw_get_home_url( $blog_id = null, $path = '', $scheme = null ) {

			global $pagenow;

			if ( empty( $blog_id ) || ! is_multisite() ) {

				if ( defined( 'WP_HOME' ) && WP_HOME ) {

					$url = untrailingslashit( WP_HOME );

					/**
					 * Compare value stored in database and maybe fix inconsistencies.
					 */
					if ( self::raw_do_option( 'get', 'home' ) !== $url ) {
						self::raw_do_option( 'update', 'home', $url );
					}

				} else {
					$url = self::raw_do_option( 'get', 'home' );
				}

			} else {
				switch_to_blog( $blog_id );

				$url = self::raw_do_option( 'get', 'home' );

				restore_current_blog();
			}

			if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ) ) ) {

				if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow ) {

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

		/**
		 * Unfiltered version of site_url() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v5.0.3 on 2019/01/28.
		 */
		public static function raw_site_url( $path = '', $scheme = null ) {

			return self::raw_get_site_url( null, $path, $scheme );
		}

		/**
		 * Unfiltered version of get_site_url() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v5.0.3 on 2019/01/28.
		 */
		public static function raw_get_site_url( $blog_id = null, $path = '', $scheme = null ) {

			if ( empty( $blog_id ) || ! is_multisite() ) {

				if ( defined( 'WP_SITEURL' ) && WP_SITEURL ) {

					$url = untrailingslashit( WP_SITEURL );

					/**
					 * Compare value stored in database and maybe fix inconsistencies.
					 */
					if ( self::raw_do_option( 'get', 'siteurl' ) !== $url ) {
						self::raw_do_option( 'update', 'siteurl', $url );
					}

				} else {
					$url = self::raw_do_option( 'get', 'siteurl' );
				}

			} else {
				switch_to_blog( $blog_id );

				$url = self::raw_do_option( 'get', 'siteurl' );

				restore_current_blog();
			}

			$url = self::raw_set_url_scheme( $url, $scheme );

			if ( $path && is_string( $path ) ) {
				$url .= '/' . ltrim( $path, '/' );
			}

			return $url;
		}

		/**
		 * Unfiltered version of set_url_scheme() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v5.0 on 2018/12/12.
		 */
		private static function raw_set_url_scheme( $url, $scheme = null ) {

			if ( ! $scheme ) {
				$scheme = is_ssl() ? 'https' : 'http';
			} elseif ( $scheme === 'admin' || $scheme === 'login' || $scheme === 'login_post' || $scheme === 'rpc' ) {
				$scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
			} elseif ( $scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative' ) {
				$scheme = is_ssl() ? 'https' : 'http';
			}

			$url = trim( $url );

			if ( substr( $url, 0, 2 ) === '//' ) {
				$url = 'http:' . $url;
			}

			if ( 'relative' === $scheme ) {

				$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );

				if ( $url !== '' && $url[0] === '/' ) {
					$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
				}

			} else {
				$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
			}

			return $url;
		}

		/**
		 * Temporarily disable filter and action hooks before calling
		 * get_option(), update_option, and delete_option().
		 */
		public static function raw_do_option( $action, $opt_name, $val = null ) {

			global $wp_filter, $wp_actions;

			$saved_wp_filter  = $wp_filter;
			$saved_wp_actions = $wp_actions;

			foreach ( array(
				'sanitize_option_' . $opt_name,
				'default_option_' . $opt_name,
				'pre_option_' . $opt_name,
				'option_' . $opt_name,	
				'pre_update_option_' . $opt_name,
				'pre_update_option',
			) as $tag ) {
				unset( $wp_filter[ $tag ] );
			}

			$ret = null;

			switch( $action ) {

				case 'get':
				case 'get_option':

					$ret = get_option( $opt_name, $default = $val );

					break;

				case 'update':
				case 'update_option':

					foreach ( array(
						'update_option',
						'update_option_' . $opt_name,
						'updated_option',
					) as $tag ) {
						unset( $wp_actions[ $tag ] );
					}

					$ret = update_option( $opt_name, $val );

					break;

				case 'delete':
				case 'delete_option':

					foreach ( array(
						'delete_option',
						'delete_option_' . $opt_name,
						'deleted_option',
					) as $tag ) {
						unset( $wp_actions[ $tag ] );
					}

					$ret = delete_option( $opt_name );

					break;
			}

			$wp_filter  = $saved_wp_filter;
			$wp_actions = $saved_wp_actions;

			unset( $saved_wp_filter, $saved_wp_actions );

			return $ret;
		}

		/**
		 * Get the width, height, and crop value for a all image sizes.
		 * Returns an associative array with the image size name as the array key value.
		 */
		public static function get_image_sizes() {

			$sizes = array();

			foreach ( get_intermediate_image_sizes() as $size_name ) {
				$sizes[ $size_name ] = self::get_size_info( $size_name );
			}

			return $sizes;
		}

		/**
		 * Get the width, height, and crop value for a specific image size.
		 */
		public static function get_size_info( $size_name = 'thumbnail' ) {

			if ( is_integer( $size_name ) ) {
				return;
			} elseif ( is_array( $size_name ) ) {
				return;
			}

			global $_wp_additional_image_sizes;

			if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'width' ] ) ) {
				$width = intval( $_wp_additional_image_sizes[ $size_name ][ 'width' ] );
			} else {
				$width = get_option( $size_name . '_size_w' );
			}

			if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'height' ] ) ) {
				$height = intval( $_wp_additional_image_sizes[ $size_name ][ 'height' ] );
			} else {
				$height = get_option( $size_name . '_size_h' );
			}

			if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'crop' ] ) ) {
				$crop = $_wp_additional_image_sizes[ $size_name ][ 'crop' ];
			} else {
				$crop = get_option( $size_name . '_crop' );
			}

			if ( ! is_array( $crop ) ) {
				$crop = empty( $crop ) ? false : true;
			}

			return array( 'width' => $width, 'height' => $height, 'crop' => $crop );
		}

		public static function get_filter_hook_names( $filter_name ) {

			global $wp_filter;

			$hook_names = array();

			if ( isset( $wp_filter[ $filter_name ]->callbacks ) ) {
				foreach ( $wp_filter[ $filter_name ]->callbacks as $hook_prio => $hook_group ) {
					foreach ( $hook_group as $hook_ref => $hook_info ) {
						if ( ( $hook_name = self::get_hook_function_name( $hook_info ) ) !== '' ) {
							$hook_names[] = $hook_name;
						}
					}
				}
			}

			return $hook_names;
		}

		/**
		 * Used by the get_wp_hook_names() method.
		 */
		public static function get_hook_function_name( array $hook_info ) {

			$hook_name = '';

			if ( ! isset( $hook_info[ 'function' ] ) ) {              // Just in case.

				return $hook_name;                              // Stop here - return an empty string.

			} elseif ( is_array( $hook_info[ 'function' ] ) ) {       // Hook is a class / method.

				$class_name    = '';
				$function_name = '';

				if ( is_object( $hook_info[ 'function' ][0] ) ) {
					$class_name = get_class( $hook_info[ 'function' ][0] );
				} elseif ( is_string( $hook_info[ 'function' ][0] ) ) {
					$class_name = $hook_info[ 'function' ][0];
				}

				if ( is_string( $hook_info[ 'function' ][1] ) ) {
					$function_name = $hook_info[ 'function' ][1];

				}

				return $class_name . '::' . $function_name;

			} elseif ( is_string ( $hook_info[ 'function' ] ) ) { // Hook is a function.

				return $hook_info[ 'function' ];
			}

			return $hook_name;
		}

		public static function get_theme_slug_version( $stylesheet = null, $theme_root = null ) {

			$theme = wp_get_theme( $stylesheet, $theme_root );

			return $theme->get_template() . '-' . $theme->Version;
		}

		public static function get_theme_header_files( $skip_backups = true ) {

			$header_files = array();
			$parent_dir   = get_template_directory();
			$child_dir    = get_stylesheet_directory();
			$tmpl_files   = (array) glob( $parent_dir . '/header*.php' );	// Returns false on error.

			if ( $parent_dir !== $child_dir ) {
				$tmpl_files = array_merge( $tmpl_files, (array) glob( $child_dir . '/header*.php' ) );
			}

			foreach ( $tmpl_files as $tmpl_file ) {

				if ( $skip_backups && preg_match( '/^.*\.php~.*$/', $tmpl_file ) ) { // Skip backup files.
					continue;
				}

				$header_files[ basename( $tmpl_file ) ] = $tmpl_file; // Child template overwrites parent.
			}

			return $header_files;
		}

		public static function doing_block_editor() {

			static $is_doing = null;

			/**
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

				$post_type = get_post_type( $post_id );

				if ( $post_type ) {

					if ( function_exists( 'use_block_editor_for_post' ) ) {

						/**
						 * Calling use_block_editor_for_post() in WordPress v5.0 during post save crashes
						 * the web browser. See https://core.trac.wordpress.org/ticket/45253 for details.
						 * Only call use_block_editor_for_post() if using an earlier version of WordPress.
						 */
						global $wp_version;

						if ( version_compare( $wp_version, '5.0', '>=' ) ) {	// Assume can edit.
							$can_edit_id = true;
						} elseif ( use_block_editor_for_post( $post_id ) ) {
							$can_edit_id = true;
						}

					} elseif ( function_exists( 'gutenberg_can_edit_post' ) ) {
						if ( gutenberg_can_edit_post( $post_id ) ) {
							$can_edit_id = true;
						}
					}
		
					if ( function_exists( 'use_block_editor_for_post_type' ) ) {
						if ( use_block_editor_for_post_type( $post_type ) ) {
							$can_edit_type = true;
						}
					} elseif ( function_exists( 'gutenberg_can_edit_post_type' ) ) {
						if ( gutenberg_can_edit_post_type( $post_type ) ) {
							$can_edit_type = true;
						}
					}
				}
			}
	
			if ( $can_edit_id ) {
				if ( $can_edit_type ) {
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
			}

			return $is_doing;
		}

		public static function role_exists( $role ) {

			$ret = false;

			if ( ! empty( $role ) ) {	// Just in case.
				if ( function_exists( 'wp_roles' ) ) {
					$ret = wp_roles()->is_role( $role );
				} else {
					$ret = $GLOBALS[ 'wp_roles' ]->is_role( $role );
				}
			}

			return $ret;
		}

		public static function user_exists( $user_id ) {

			if ( is_numeric( $user_id ) && $user_id > 0 ) { // true is not valid.

				$user_id = (int) $user_id; // Cast as integer for array.

				if ( isset( self::$cache_user_exists[ $user_id ] ) ) {

					return self::$cache_user_exists[ $user_id ];

				} else {

					global $wpdb;

					$select_sql = 'SELECT COUNT(ID) FROM ' . $wpdb->users . ' WHERE ID = %d';

					return self::$cache_user_exists[ $user_id ] = $wpdb->get_var( $wpdb->prepare( $select_sql, $user_id ) ) ? true : false;
				}
			}

			return false;
		}

		public static function get_user_ids_for_roles( array $roles, $blog_id = null ) {

			/**
			 * Get the user ID => name associative array, and keep only the array keys.
			 */
			$user_ids = array_keys( self::get_user_names_for_roles( $roles, $blog_id ) );

			rsort( $user_ids );	// Newest user first.

			return $user_ids;
		}

		public static function get_user_select_for_roles( array $roles, $blog_id = null, $add_none = true ) {

			$user_select = self::get_user_names_for_roles( $roles, $blog_id );

			if ( $add_none ) {
				$user_select = array( 'none' => 'none' ) + $user_select;
			}

			return $user_select;
		}

		public static function get_user_names_for_roles( array $roles, $blog_id = null ) {

			if ( empty( $roles ) ) {
				return array();
			};

			if ( empty( $blog_id ) ) {
				$blog_id = get_current_blog_id();	// Since WP v3.1.
			}

			$user_names = array();

			foreach ( $roles as $role ) {
				$user_names += self::get_user_names( $blog_id, $role );
			}

			/**
			 * Use asort() or uasort() to maintain the ID => display_name association.
			 */
			if ( defined( 'SORT_STRING' ) ) {
				asort( $user_names, SORT_STRING );
			} else {
				uasort( $user_names, 'strcasecmp' ); // Case-insensitive string comparison.
			}

			return $user_names;
		}

		public static function get_user_names( $blog_id = null, $role = '', $limit = '' ) {

			static $offset = '';

			if ( empty( $blog_id ) ) {
				$blog_id = get_current_blog_id();	// Since WP v3.1.
			}

			if ( is_numeric( $limit ) ) {
				$offset = '' === $offset ? 0 : $offset + $limit;
			}

			$user_args  = array(
				'blog_id' => $blog_id,
				'offset'  => $offset,
				'number'  => $limit,
				'orderby' => 'display_name',
				'order'   => 'ASC',
				'role'    => $role,
				'fields'  => array(	// Save memory and only return only specific fields.
					'ID',
					'display_name',
				)
			);

			$user_names = array();

			foreach ( get_users( $user_args ) as $user_obj ) {
				$user_names[ $user_obj->ID ] = $user_obj->display_name;
			}

			if ( '' !== $offset ) {
				if ( empty( $user_names ) ) {
					$offset = '';	// Allow the next call to start fresh.
					return false;	// To break the while loop.
				}
			}

			return $user_names;
		}
	}
}

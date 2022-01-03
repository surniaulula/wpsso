<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtilWP' ) ) {

	class SucomUtilWP {

		protected static $cache_user_exists = array();	// Saved user_exists() values.

		public function __construct() {}

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

		public static function get_available_languages() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = get_available_languages();
			}

			return $local_cache;
		}

		public static function get_db_transient_keys( $only_expired = false, $key_prefix = '' ) {

			global $wpdb;

			$transient_keys = array();
			$opt_row_prefix = $only_expired ? '_transient_timeout_' : '_transient_';
			$current_time   = isset( $_SERVER[ 'REQUEST_TIME' ] ) ? (int) $_SERVER[ 'REQUEST_TIME' ] : time() ;

			$db_query = 'SELECT option_name';
			$db_query .= ' FROM ' . $wpdb->options;
			$db_query .= ' WHERE option_name LIKE \'' . $opt_row_prefix . $key_prefix . '%\'';

			if ( $only_expired ) {

				$db_query .= ' AND option_value < ' . $current_time;	// Expiration time older than current time.
			}

			$db_query .= ';';	// End of query.

			$result = $wpdb->get_col( $db_query );

			/**
			 * Remove '_transient_' or '_transient_timeout_' prefix from option name.
			 */
			foreach( $result as $option_name ) {

				$transient_keys[] = str_replace( $opt_row_prefix, '', $option_name );
			}

			return $transient_keys;
		}

		public static function get_db_transient_size_mb( $decimals = 2, $dec_point = '.', $thousands_sep = ',', $key_prefix = '' ) {

			global $wpdb;

			$db_query = 'SELECT CHAR_LENGTH( option_value ) / 1024 / 1024';
			$db_query .= ', CHAR_LENGTH( option_value )';
			$db_query .= ' FROM ' . $wpdb->options;
			$db_query .= ' WHERE option_name LIKE \'_transient_' . $key_prefix . '%\'';
			$db_query .= ';';	// End of query.

			$result = $wpdb->get_col( $db_query );

			return number_format( array_sum( $result ), $decimals, $dec_point, $thousands_sep );
		}

		public static function do_shortcode_names( array $shortcode_names, $content, $ignore_html = false ) {

			if ( ! empty( $shortcode_names ) ) {		// Just in case.

				global $shortcode_tags;

				$registered_tags = $shortcode_tags;	// Save the original registered shortcodes.

				$shortcode_tags = array();		// Init a new empty shortcode tags array.

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

		public static function get_wp_config_file_path() {

			$parent_abspath = trailingslashit( dirname( ABSPATH ) );

			$wp_config_file_path = false;

			/**
			 * The config file resides in ABSPATH.
			 */
			if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

				$wp_config_file_path = ABSPATH . 'wp-config.php';

			/**
			 * The config file resides one level above ABSPATH and is not part of another installation.
			 */
			} elseif ( file_exists( $parent_abspath . 'wp-config.php' ) && ! file_exists( $parent_abspath . 'wp-settings.php' ) ) {

				$wp_config_file_path = $parent_abspath . 'wp-config.php';

			}

			return $wp_config_file_path;
		}

		/**
		 * Returns an associative array of timezone strings (ie. 'Africa/Abidjan'), 'UTC', and offsets (ie. '-07:00').
		 */
		public static function get_timezones() {

			$timezones = timezone_identifiers_list();

			$timezones = array_combine( $timezones, $timezones );	// Create an associative array.

			$offset_range = array(
				-12,
				-11.5,
				-11,
				-10.5,
				-10,
				-9.5,
				-9,
				-8.5,
				-8,
				-7.5,
				-7,
				-6.5,
				-6,
				-5.5,
				-5,
				-4.5,
				-4,
				-3.5,
				-3,
				-2.5,
				-2,
				-1.5,
				-1,
				-0.5,
				0,
				0.5,
				1,
				1.5,
				2,
				2.5,
				3,
				3.5,
				4,
				4.5,
				5,
				5.5,
				5.75,
				6,
				6.5,
				7,
				7.5,
				8,
				8.5,
				8.75,
				9,
				9.5,
				10,
				10.5,
				11,
				11.5,
				12,
				12.75,
				13,
				13.75,
				14,
			);

			/**
			 * Create date( 'P' ) formatted timezone values (ie. -07:00).
			 */
			foreach ( $offset_range as $offset ) {

				$offset_value = self::format_tz_offset( $offset );

				$offset_name = 'UTC' . $offset_value;

				$timezones[ $offset_value ] = $offset_name;
			}

			return $timezones;
		}

		/**
		 * May return a timezone string (ie. 'Africa/Abidjan'), 'UTC', or an offset (ie. '-07:00').
		 */
		public static function get_default_timezone() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			if ( function_exists( wp_timezone_string() ) ) {	// Since WP v5.3.

				return $local_cache = wp_timezone_string();
			}

			$timezone_string = get_option( 'timezone_string' );

			if ( $timezone_string ) {

				return $local_cache = $timezone_string;
			}

			$offset = (float) get_option( 'gmt_offset' );

			$tz_offset = self::format_tz_offset( $offset );

			return $local_cache = $tz_offset;
		}

		/**
		 * Returns a date( 'P' ) formatted timezone value (ie. -07:00).
		 */
		public static function format_tz_offset( $offset ) {

			$hours   = (int) $offset;
			$minutes = ( $offset - $hours );

			$sign      = ( $offset < 0 ) ? '-' : '+';
			$abs_hour  = abs( $hours );
			$abs_mins  = abs( $minutes * 60 );
			$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

			return $tz_offset;
		}

		/**
		 * wp_encode_emoji() is only available since WordPress v4.2.
		 *
		 * Use the WordPress function if available, otherwise provide the same functionality.
		 */
		public static function wp_encode_emoji( $content ) {

			if ( function_exists( 'wp_encode_emoji' ) ) {

				return wp_encode_emoji( $content );	// Since WP v4.2.
			}

			/**
			 * If mb_convert_encoding() is not available, then return the string un-converted.
			 */
			if ( ! function_exists( 'mb_convert_encoding' ) ) {	// Just in case.

				return $content;
			}

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

				if ( ! empty( $all_matches[ 1 ] ) ) {

					foreach ( $all_matches[ 1 ] as $emoji ) {

						$unpacked = unpack( 'H*', mb_convert_encoding( $emoji, $to_encoding = 'UTF-32', $from_encoding = 'UTF-8' ) );

						if ( isset( $unpacked[ 1 ] ) ) {

							$entity = '&#x' . ltrim( $unpacked[ 1 ], '0' ) . ';';

							$content = str_replace( $emoji, $entity, $content );
						}
					}
				}
			}

			return $content;
		}

		/**
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

		/**
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

			/**
			 * WordPress stores data using a post, term, or user ID, along with a group string.
			 *
			 * Example: wp_cache_get( 1, 'user_meta' );
			 *
			 * Returns (bool|mixed) false on failure to retrieve contents or the cache contents on success.
			 *
			 * $found (bool) Whether the key was found in the cache (passed by reference) - disambiguates a return of false.
			 */
			$metadata = wp_cache_get( $obj_id, $meta_type . '_meta', $force = false, $found );

			if ( $found ) {

				return $metadata;
			}

			/**
			 * $meta_type (string) Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
			 * or any other object type with an associated meta table.  
			 *
			 * Returns (array|false) metadata cache for the specified objects, or false on failure.
			 */
			$metadata = update_meta_cache( $meta_type, array( $obj_id ) );

			return $metadata[ $obj_id ];
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

		/**
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

		/**
		 * Unfiltered version of home_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_home_url( $path = '', $scheme = null ) {

			return self::raw_get_home_url( null, $path, $scheme );
		}

		/**
		 * Unfiltered version of get_home_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_get_home_url( $blog_id = null, $path = '', $scheme = null ) {

			$is_multisite = is_multisite();

			if ( empty( $blog_id ) || ! $is_multisite ) {

				/**
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

		/**
		 * Unfiltered version of site_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_site_url( $path = '', $scheme = null ) {

			return self::raw_get_site_url( null, $path, $scheme );
		}

		/**
		 * Unfiltered version of get_site_url() from wordpress/wp-includes/link-template.php
		 *
		 * Last synchronized with WordPress v5.8.1 on 2021/10/15.
		 */
		public static function raw_get_site_url( $blog_id = null, $path = '', $scheme = null ) {

			$is_multisite = is_multisite();

			if ( empty( $blog_id ) || ! $is_multisite ) {

				/**
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

		/**
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

		/**
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

					/**
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

					/**
					 * If we have an expiration time, do not autoload the transient.
					 */
					if ( $expiration ) {

						$autoload = 'no';

						add_option( $transient_timeout, time() + $expiration, '', 'no' );
					}

					$result = add_option( $transient_option, $value, '', $autoload );

				} else {

					/**
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

		public static function get_theme_header_file_paths( $skip_backups = true ) {

			$parent_tmpl_dir   = get_template_directory();
			$child_tmpl_dir    = get_stylesheet_directory();
			$header_file_paths = array();
			$tmpl_file_paths   = (array) glob( $parent_tmpl_dir . '/header*.php' );	// Returns false on error.

			if ( $parent_tmpl_dir !== $child_tmpl_dir ) {

				$tmpl_file_paths = array_merge( $tmpl_file_paths, (array) glob( $child_tmpl_dir . '/header*.php' ) );
			}

			foreach ( $tmpl_file_paths as $tmpl_file ) {

				if ( $skip_backups && preg_match( '/^.*\.php~.*$/', $tmpl_file ) ) { // Skip backup files.

					continue;
				}

				$header_file_paths[ basename( $tmpl_file ) ] = $tmpl_file; // Child template overwrites parent.
			}

			return $header_file_paths;
		}

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

				if ( function_exists( 'use_block_editor_for_post' ) ) {

					/**
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

				/**
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

		public static function role_exists( $role ) {

			$exists = false;

			if ( ! empty( $role ) ) {	// Just in case.

				if ( function_exists( 'wp_roles' ) ) {

					$exists = wp_roles()->is_role( $role );

				} else {

					$exists = $GLOBALS[ 'wp_roles' ]->is_role( $role );
				}
			}

			return $exists;
		}

		public static function get_roles_user_ids( array $roles, $blog_id = null, $limit = '' ) {

			/**
			 * Get the user ID => name associative array, and keep only the array keys.
			 */
			$user_ids = array_keys( self::get_roles_user_names( $roles, $blog_id, $limit ) );

			rsort( $user_ids );	// Newest user first.

			return $user_ids;
		}

		public static function get_roles_user_select( array $roles, $blog_id = null, $add_none = true, $limit = '' ) {

			$user_select = self::get_roles_user_names( $roles, $blog_id, $limit );

			if ( $add_none ) {

				$user_select = array( 'none' => 'none' ) + $user_select;
			}

			return $user_select;
		}

		public static function get_roles_user_names( array $roles, $blog_id = null, $limit = '' ) {

			if ( empty( $roles ) ) {

				return array();
			};

			if ( empty( $blog_id ) ) {

				$blog_id = get_current_blog_id();
			}

			$user_names = array();

			foreach ( $roles as $role ) {

				$role_users = self::get_user_names( $role, $blog_id, $limit );	// Can return false with a numeric $limit argument.

				if ( ! empty( $role_users ) && is_array( $role_users ) ) {	// Check return value, just in case.

					$user_names += $role_users;
				}
			}

			/**
			 * Use asort() or uasort() to maintain the ID => display_name association.
			 */
			if ( ! empty( $user_names ) ) {	// Skip if nothing to sort.

				if ( defined( 'SORT_STRING' ) ) {

					asort( $user_names, SORT_STRING );

				} else {

					uasort( $user_names, 'strnatcmp' );
				}
			}

			return $user_names;
		}

		public static function user_exists( $user_id ) {

			if ( is_numeric( $user_id ) && $user_id > 0 ) { // True is not valid.

				$user_id = (int) $user_id; // Cast as integer for cache array.

				if ( isset( self::$cache_user_exists[ $user_id ] ) ) {

					return self::$cache_user_exists[ $user_id ];

				}

				global $wpdb;

				$select_sql = 'SELECT COUNT(ID) FROM ' . $wpdb->users . ' WHERE ID = %d';

				return self::$cache_user_exists[ $user_id ] = $wpdb->get_var( $wpdb->prepare( $select_sql, $user_id ) ) ? true : false;
			}

			return false;
		}

		/**
		 * Keep in mind that the 'wp_capabilities' meta value is a serialized array, so WordPress uses a LIKE query to
		 * match any string within the serialized array.
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
		 * If using the $limit argument, you must keep calling get_user_names() until it returns false - it may also return
		 * false on the first query if there are no users in the specified role.
		 */
		public static function get_user_names( $role = '', $blog_id = null, $limit = '' ) {

			static $offset = '';

			if ( empty( $blog_id ) ) {

				$blog_id = get_current_blog_id();
			}

			if ( is_numeric( $limit ) ) {

				$offset = '' === $offset ? 0 : $offset + $limit;
			}

			$user_args  = array(
				'blog_id' => $blog_id,
				'offset'  => $offset,
				'number'  => $limit,
				'order'   => 'ASC',
				'orderby' => 'display_name',
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

			if ( '' !== $offset ) {		// 0 or multiple of $limit.

				if ( empty( $user_names ) ) {

					$offset = '';	// Allow the next call to start fresh.

					return false;	// To break the while loop.
				}
			}

			return $user_names;
		}

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

				} else {

					$width = get_option( $size_name . '_size_w' );	// Returns false by default.
				}

				if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'height' ] ) ) {

					$height = intval( $_wp_additional_image_sizes[ $size_name ][ 'height' ] );

				} else {

					$height = get_option( $size_name . '_size_h' );	// Returns false by default.
				}

				if ( isset( $_wp_additional_image_sizes[ $size_name ][ 'crop' ] ) ) {

					$crop = $_wp_additional_image_sizes[ $size_name ][ 'crop' ];

				} else {

					$crop = get_option( $size_name . '_crop' );	// Returns false by default.
				}

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

		public static function count_metadata( $meta_type, $meta_key ) {

			global $wpdb;

 			if ( ! $meta_type || ! $meta_key ) {

				return false;
			}

			$table = _get_meta_table( $meta_type );

			if ( ! $table ) {

				return false;
			}

			$type_column = sanitize_key( $meta_type . '_id' );
			$id_column   = 'user' == $meta_type ? 'umeta_id' : 'meta_id';
			$meta_key    = wp_unslash( $meta_key );
 			$query       = $wpdb->prepare( "SELECT COUNT( $id_column ) FROM $table WHERE meta_key = %s", $meta_key );
 			$result      = $wpdb->get_col( $query );

			if ( isset( $result[ 0 ] ) && is_numeric( $result[ 0 ] ) ) {	// Just in case;

				return $result[ 0 ];
			}

			return 0;
		}

		public static function is_post_type_public( $mixed ) {

			$post_type_name = null;

			if ( is_object( $mixed ) || is_numeric( $mixed ) ) {

				/**
				 * Returns the post type name.
				 */
				$post_type_name = get_post_type( $mixed );	// Post object or ID.

			} else {

				$post_type_name = $mixed;			// Post type name.
			}

			if ( $post_type_name ) {

				$args = array( 'name' => $post_type_name, 'public'  => 1 );

				$post_types = get_post_types( $args, $output = 'names', $operator = 'and' );

				if ( isset( $post_types[ 0 ] ) && $post_types[ 0 ] === $post_type_name ) {

					return true;
				}
			}

			return false;
		}

		/**
		 * Returns post types registered as 'public' = 1 and 'show_ui' = 1.
		 *
		 * $output = objects | names
		 */
		public static function get_post_types( $output = 'objects', $sort_by_label = true ) {

			/**
			 * The 'wp_block' custom post type for reusable blocks is registered as 'public' = 0 and 'show_ui' = 1.
			 */
			$args = apply_filters( 'sucom_get_post_types_args', array( 'public' => 1, 'show_ui' => 1 ) );

			$operator = 'and';

			$post_types = get_post_types( $args, $output, $operator );

			if ( 'objects' === $output ) {

				if ( $sort_by_label ) {

					self::sort_objects_by_label( $post_types );
				}
			}

			return apply_filters( 'sucom_get_post_types', $post_types, $output );
		}

		public static function get_post_type_labels( array $values = array(), $val_prefix = '', $label_prefix = '', $objects = null ) {

			/**
			 * Returns post types registered as 'public' = 1 and 'show_ui' = 1.
			 */
			if ( null === $objects ) {

				$objects = self::get_post_types( $output = 'objects' );
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

		/**
		 * $output = objects | names
		 */
		public static function get_taxonomies( $output = 'objects', $sort_by_label = true ) {

			$args = apply_filters( 'sucom_get_taxonomies_args', array( 'public' => 1, 'show_ui' => 1 ) );

			$operator = 'and';

			$taxonomies = get_taxonomies( $args, $output, $operator );

			if ( 'objects' === $output ) {

				if ( $sort_by_label ) {

					self::sort_objects_by_label( $taxonomies );
				}
			}

			return apply_filters( 'sucom_get_taxonomies', $taxonomies, $output );
		}

		public static function get_taxonomy_labels( array $values = array(), $val_prefix = '', $label_prefix = '', $objects = null ) {

			if ( null === $objects ) {

				$objects = self::get_taxonomies( $output = 'objects' );
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

		/**
		 * Add the slug (ie. name) to custom post type and taxonomy labels.
		 */
		public static function get_object_label( $obj ) {

			if ( empty( $obj->_builtin ) ) {

				return $obj->label . ' [' . $obj->name . ']';
			}

			return $obj->label;
		}

		public static function sort_objects_by_label( array &$objects ) {

			$sorted = array();

			$by_name = array();

			foreach ( $objects as $num => $obj ) {

				if ( ! empty( $obj->labels->name ) ) {

					$sort_key = $obj->labels->name . '-' . $num;

				} elseif ( ! empty( $obj->label ) ) {

					$sort_key = $obj->label . '-' . $num;

				} else {
					$sort_key = $obj->name . '-' . $num;
				}

				$by_name[ $sort_key ] = $num;	// Make sure key is sortable and unique.
			}

			ksort( $by_name );

			foreach ( $by_name as $sort_key => $num ) {

				$sorted[] = $objects[ $num ];
			}

			return $objects = $sorted;
		}

		/**
		 * Only creates new keys - does not update existing keys.
		 */
		public static function add_site_option_key( $opt_name, $key, $value ) {

			return self::update_option_key( $opt_name, $key, $value, $protect = true, $site = true );
		}

		/**
		 * Only creates new keys - does not update existing keys.
		 */
		public static function add_option_key( $opt_name, $key, $value ) {

			return self::update_option_key( $opt_name, $key, $value, $protect = true, $site = false );
		}

		public static function update_site_option_key( $opt_name, $key, $value, $protect = false ) {

			return self::update_option_key( $opt_name, $key, $value, $protect, $site = true );
		}

		public static function update_option_key( $opt_name, $key, $value, $protect = false, $site = false ) {

			if ( $site ) {

				$opts = get_site_option( $opt_name, $default = array() );	// Returns an array by default.

			} else {

				$opts = get_option( $opt_name, $default = array() );	// Returns an array by default.
			}

			if ( $protect && isset( $opts[ $key ] ) ) {

				return false;	// No update.
			}

			$opts[ $key ] = $value;

			if ( $site ) {

				return update_site_option( $opt_name, $opts );

			} else {

				return update_option( $opt_name, $opts );
			}
		}

		public static function get_site_option_key( $opt_name, $key ) {

			return self::get_option_key( $opt_name, $key, $site = true );
		}

		public static function get_option_key( $opt_name, $key, $site = false ) {

			if ( $site ) {

				$opts = get_site_option( $opt_name, $default = array() );	// Returns an array by default.

			} else {

				$opts = get_option( $opt_name, $default = array() );	// Returns an array by default.
			}

			if ( isset( $opts[ $key ] ) ) {

				return $opts[ $key ];
			}

			return null;	// No value.
		}

		public static function delete_site_option_key( $opt_name, $key ) {

			return self::delete_option_key( $opt_name, $key, $site = true );
		}

		public static function delete_option_key( $opt_name, $key, $site = false ) {

			if ( $site ) {

				$opts = get_site_option( $opt_name, $default = array() );	// Returns an array by default.

			} else {

				$opts = get_option( $opt_name, $default = array() );	// Returns an array by default.
			}

			if ( isset( $opts[ $key ] ) ) {

				unset( $opts[ $key ] );

				if ( empty( $opts ) ) {	// Cleanup.

					if ( $site ) {

						return delete_site_option( $opt_name );

					} else {

						return delete_option( $opt_name );
					}
				}

				if ( $site ) {

					return update_site_option( $opt_name, $opts );

				} else {

					return update_option( $opt_name, $opts );
				}
			}

			return false;	// No delete.
		}
	}
}

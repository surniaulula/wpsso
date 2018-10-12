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
		 * Last synchronized with WordPress v4.9 on 2017/11/27.
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

				$post_type = get_post_type_object( $post->post_type ); 

				if ( 'page' === $post->post_type && $post->ID == get_option( 'page_on_front' ) && 'page' == get_option( 'show_on_front' ) ) {
					$shortlink = home_url( '/' );
				} elseif ( ! empty( $post_type->public ) ) {
					$shortlink = home_url( '?p=' . $post_id );
				}
			} 
			
			return $shortlink;
		}

		/**
		 * Unfiltered version of home_url() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v4.8.2 on 2017/10/22.
		 */
		public static function raw_home_url( $path = '', $scheme = null ) {

			return self::raw_get_home_url( null, $path, $scheme );
		}

		/**
		 * Unfiltered version of get_home_url() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v4.8.2 on 2017/10/22.
		 */
		public static function raw_get_home_url( $blog_id = null, $path = '', $scheme = null ) {

			global $pagenow;

			if ( method_exists( 'SucomUtil', 'protect_filter_value' ) ) {
				SucomUtil::protect_filter_value( 'pre_option_home' );
			}

			if ( empty( $blog_id ) || ! is_multisite() ) {

				$url = get_option( 'home' );

			} else {

				switch_to_blog( $blog_id );

				$url = get_option( 'home' );

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
				$url .= '/'.ltrim( $path, '/' );
			}

			return $url;
		}

		/**
		 * Unfiltered version of set_url_scheme() from wordpress/wp-includes/link-template.php
		 * Last synchronized with WordPress v4.8.2 on 2017/10/22.
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
					$url = '/'.ltrim( $url, "/ \t\n\r\0\x0B" );
				}

			} else {
				$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
			}

			return $url;
		}
	}
}

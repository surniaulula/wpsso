<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2018 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomPlugin' ) ) {

	class SucomPlugin {

		private static $cache_plugins = null;	// Common cache for get_plugins() and clear_plugins().

		public function __construct() {
		}

		public static function get_wp_plugin_dir() {

			if ( defined( 'WP_PLUGIN_DIR' ) && is_dir( WP_PLUGIN_DIR ) && is_writable( WP_PLUGIN_DIR ) ) {
				return WP_PLUGIN_DIR;
			}

			return false;
		}

		/**
		 * The WordPress get_plugins() function is very slow, so call it only once and cache its result.
		 */
		public static function get_plugins() {

			if ( self::$cache_plugins !== null ) {		// Common cache for get_plugins() and clear_plugins().
				return self::$cache_plugins;
			}

			self::$cache_plugins = array();

			if ( ! function_exists( 'get_plugins' ) ) {	// Load the WordPress library if necessary.

				$plugin_lib = trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php';

				if ( file_exists( $plugin_lib ) ) {	// Just in case.

					require_once $plugin_lib;

				} elseif ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {	// Just in case.

					$error_pre = sprintf( '%s error:', __METHOD__ );
					$error_msg = sprintf( 'The WordPress %s library file is missing and required.', $plugin_lib );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}
			}

			if ( function_exists( 'get_plugins' ) ) {

				self::$cache_plugins = get_plugins();

			} elseif ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {	// Just in case.

				$error_pre = sprintf( '%s error:', __METHOD__ );
				$error_msg = sprintf( 'The WordPress %s function is missing and required.', 'get_plugins()' );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			return self::$cache_plugins;
		}

		public static function clear_plugins_cache() {

			self::$cache_plugins = null;	// Common cache for get_plugins() and clear_plugins().
		}

		/**
		 * Returns an associative array of true/false values.
		 */
		public static function get_active_plugins( $use_cache = true ) {

			static $local_cache = null;

			if ( ! $use_cache || ! isset( $local_cache ) ) {

				$local_cache    = array();
				$active_plugins = get_option( 'active_plugins', array() );

				if ( is_multisite() ) {

					$active_network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );

					if ( ! empty( $active_network_plugins ) ) {
						$active_plugins = array_merge( $active_plugins, $active_network_plugins );
					}
				}

				foreach ( $active_plugins as $base ) {
					$local_cache[ $base ] = true;
				}
			}

			return $local_cache;
		}

		/**
		 * Returns true/false.
		 *
		 * Example: $plugin_base = wpsso/wpsso.php.
		 */
		public static function is_plugin_installed( $plugin_base, $use_cache = true ) {

			static $local_cache = array();					// Associative array of true/false values.

			if ( $use_cache && isset( $local_cache[ $plugin_base ] ) ) {
				return $local_cache[ $plugin_base ];
			} elseif ( empty( $plugin_base ) ) { 				// Just in case.
				return $local_cache[ $plugin_base ] = false;
			} elseif ( validate_file( $plugin_base ) > 0 ) {		// Contains invalid characters.
				return $local_cache[ $plugin_base ] = false;
			} elseif ( ! is_file( WP_PLUGIN_DIR . '/' . $plugin_base ) ) {	// Check existence of plugin folder.
				return $local_cache[ $plugin_base ] = false;
			}

			$plugins = self::get_plugins();

			if ( ! empty( $plugins[ $plugin_base ] ) ) {			// Check for a valid plugin header.
				return $local_cache[ $plugin_base ] = true;
			}

			return $local_cache[ $plugin_base ] = false;
		}

		/**
		 * Returns true/false.
		 *
		 * Example: $plugin_base = wpsso/wpsso.php.
		 */
		public static function is_plugin_active( $plugin_base, $use_cache = true ) {

			$active_plugins = self::get_active_plugins( $use_cache );

			if ( isset( $active_plugins[ $plugin_base ] ) ) {	// Associative array of true/false values.
				return $active_plugins[ $plugin_base ];		// Return true/false.
			}

			return false;
		}

		public static function activate_plugin( $plugin_base, $network_wide = false, $silent = true ) {

			$active_plugins = get_option( 'active_plugins', array() );

			if ( empty( $active_plugins[ $plugin_base ] ) ) {

				if ( ! $silent ) {
					do_action( 'activate_plugin', $plugin_base );
					do_action( 'activate_' . $plugin_base );
				}

				$active_plugins[] = $plugin_base;

				sort( $active_plugins ); // Emulate the WordPress function.

				$updated = update_option( 'active_plugins', $active_plugins );

				if ( ! $silent ) {
					do_action( 'activated_plugin', $plugin_base );
				}

				return $updated;
			}

			return false; // Plugin already active.
		}

		/**
		 * Returns true/false.
		 *
		 * Example: $plugin_slug = wpsso.
		 */
		public static function is_slug_active( $plugin_slug ) {

			static $local_cache = array();						// Associative array of true/false values.

			if ( isset( $local_cache[ $plugin_slug ] ) ) {
				return $local_cache[ $plugin_slug ];
			} elseif ( empty( $plugin_slug ) ) {					// Just in case.
				return $local_cache[ $plugin_slug ] = false;
			}

			foreach ( self::get_active_plugins( $use_cache = true ) as $plugin_base => $active ) {
				if ( strpos( $plugin_base, $plugin_slug . '/' ) === 0 ) {	// Plugin slug found.
					return $local_cache[ $plugin_slug ] = true;		// Stop here.
				}
			}

			return $local_cache[ $plugin_slug ] = false;
		}

		public static function get_slug_info( $plugin_slug, $plugin_fields = array(), $unfiltered = true ) {

			static $local_cache = array();

			$plugin_fields = array_merge( array(
				'active_installs'   => true,	// Get by default.
				'added'             => false,
				'banners'           => false,
				'compatibility'     => false,
				'contributors'      => false,
				'description'       => false,
				'donate_link'       => false,
				'downloadlink'      => true,	// Get by default.
				'group'             => false,
				'homepage'          => false,
				'icons'             => false,
				'last_updated'      => false,
				'sections'          => false,
				'short_description' => false,
				'rating'            => true,	// Get by default.
				'ratings'           => true,	// Get by default.
				'requires'          => false,
				'reviews'           => false,
				'tags'              => false,
				'tested'            => false,
				'versions'          => false
			), $plugin_fields );

			$fields_key = json_encode( $plugin_fields ); // Unique index based on selected fields.

			if ( isset( $local_cache[ $plugin_slug ][ $fields_key ] ) ) {
				return $local_cache[ $plugin_slug ][ $fields_key ];
			} elseif ( empty( $plugin_slug ) ) {	// Just in case.
				return $local_cache[ $plugin_slug ][ $fields_key ] = false;
			}

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin-install.php';
			}

			return $local_cache[ $plugin_slug ][ $fields_key ] = plugins_api( 'plugin_information', array(
				'slug'       => $plugin_slug,
				'fields'     => $plugin_fields,
				'unfiltered' => $unfiltered,	// True skips the update manager filter.
			) );
		}

		public static function get_slug_name( $plugin_slug, $unfiltered = true ) {

			$plugin_info = self::get_slug_info( $plugin_slug, array(), $unfiltered );

			return empty( $plugin_info->name ) ? $plugin_slug : $plugin_info->name;
		}

		public static function get_slug_download_url( $plugin_slug, $unfiltered = true ) {

			$plugin_info = self::get_slug_info( $plugin_slug, array( 'downloadlink' => true ), $unfiltered );

			if ( is_wp_error( $plugin_info ) ) {

				return $plugin_info;

			} elseif ( isset( $plugin_info->download_link ) ) {

				if ( filter_var( $plugin_info->download_link, FILTER_VALIDATE_URL ) === false ) { // Just in case.

					$plugin_name = empty( $plugin_info->name ) ? $plugin_slug : $plugin_info->name;

					return new WP_Error( 'invalid_download_link', 
						sprintf( __( 'The plugin information for "%s" contains an invalid download link.' ),
							$plugin_name ) );
				}

				return $plugin_info->download_link;

			} else {

				$plugin_name = empty( $plugin_info->name ) ? $plugin_slug : $plugin_info->name;

				return new WP_Error( 'missing_download_link', 
					sprintf( __( 'The plugin information for "%s" does not contain a download link.' ),
						$plugin_name ) );
			}
		}

		/**
		 * Does not remove an existing plugin folder before extracting the zip file.
		 */
		public static function download_and_install_slug( $plugin_slug, $unfiltered = true ) {

			$plugin_url = self::get_slug_download_url( $plugin_slug, $unfiltered );

			if ( is_wp_error( $plugin_url ) ) {
				return $plugin_url;
			}

			if ( ! function_exists( 'download_url' ) ) {
				require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/file.php';
			}

			$plugin_zip = download_url( $plugin_url );

			if ( is_wp_error( $plugin_zip ) ) {
				return $plugin_zip;
			}

			WP_Filesystem();

			$unzip_file = unzip_file( $plugin_zip, WP_PLUGIN_DIR );

			@unlink( $plugin_zip );

			if ( is_wp_error( $unzip_file ) ) {
				return $unzip_file;
			}

			return true; // Just in case - signal success.
		}

		/**
		 * Return the number of updates pending for a slug prefix.
		 *
		 * Example: $plugin_prefix = 'wpsso'
		 */
		public static function get_updates_count( $plugin_prefix = '' ) {

			$count = 0;

			$update_plugins = get_site_transient( 'update_plugins' );

			if ( ! empty( $update_plugins->response ) ) {
				if ( ! empty( $plugin_prefix ) ) {
					foreach ( $update_plugins->response as $base => $data ) {
						if ( isset( $data->slug ) && strpos( $data->slug, $plugin_prefix ) === 0 ) {
							$count++;
						}
					}
				} else {
					$count = count( $update_plugins->response );
				}
			}

			return $count;
		}

		/**
		 * Returns true/false.
		 */
		public static function have_plugin_update( $plugin_base ) {

			static $local_cache = array();					// Associative array of true/false values.

			if ( isset( $local_cache[ $plugin_base ] ) ) {
				return $local_cache[ $plugin_base ];
			} elseif ( empty( $plugin_base ) ) { 				// Just in case.
				return $local_cache[ $plugin_base ] = false;
			} elseif ( ! self::is_plugin_installed( $plugin_base ) ) { // Call with class to use common cache.
				return $local_cache[ $plugin_base ] = false;
			}

			$update_plugins = get_site_transient( 'update_plugins' );

			if ( isset( $update_plugins->response ) && is_array( $update_plugins->response ) ) {
				if ( isset( $update_plugins->response[ $plugin_base ] ) ) {
					return $local_cache[ $plugin_base ] = true;
				}
			}

			return $local_cache[ $plugin_base ] = false;
		}
	}
}

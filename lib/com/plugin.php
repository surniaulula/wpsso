<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomPlugin' ) ) {

	class SucomPlugin {

		public function __construct() {}

		/*
		 * The WordPress get_plugins() function is very slow, so call it only once and cache its result.
		 *
		 * Used by self::is_plugin_installed().
		 */
		public static function get_plugins( $read_cache = true ) {

			static $local_cache = null;

			if ( $read_cache ) {

				if ( null !== $local_cache ) {

					return $local_cache;
				}
			}

			$local_cache = array();

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

				$local_cache = get_plugins();

			} elseif ( method_exists( 'SucomUtil', 'safe_error_log' ) ) {	// Just in case.

				$error_pre = sprintf( '%s error:', __METHOD__ );
				$error_msg = sprintf( 'The WordPress %1$s function is missing and required.', '<code>get_plugins()</code>' );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			return $local_cache;
		}

		/*
		 * Returns an associative array of true/false values.
		 *
		 * Used by self::is_plugin_active() and Wpsso->show_config().
		 */
		public static function get_active_plugins( $read_cache = true ) {

			static $local_cache = null;

			if ( $read_cache ) {

				if ( null !== $local_cache ) {

					return $local_cache;
				}
			}

			$local_cache = array();

			$active_plugins = get_option( 'active_plugins', array() );

			if ( is_multisite() ) {

				$active_network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );

				if ( ! empty( $active_network_plugins ) ) {

					$active_plugins = array_merge( $active_plugins, $active_network_plugins );
				}
			}

			foreach ( $active_plugins as $plugin_base ) {

				$local_cache[ $plugin_base ] = true;
			}

			return $local_cache;
		}

		/*
		 * Returns true/false.
		 *
		 * Example: $plugin_base = wpsso/wpsso.php.
		 *
		 * Used by WpssoAdmin->get_ext_action_links().
		 */
		public static function is_plugin_installed( $plugin_base, $read_cache = true ) {

			static $local_cache = array();	// Associative array of true/false values.

			if ( $read_cache ) {

				if ( isset( $local_cache[ $plugin_base ] ) ) {

					return $local_cache[ $plugin_base ];
				}
			}

			if ( empty( $plugin_base ) ) {	// Just in case.

				return $local_cache[ $plugin_base ] = false;

			} elseif ( validate_file( $plugin_base ) > 0 ) {	// Contains invalid characters.

				return $local_cache[ $plugin_base ] = false;
			}

			$wp_plugins = self::get_plugins( $read_cache );	// Front-end safe and uses cache.

			if ( ! empty( $wp_plugins[ $plugin_base ] ) ) {	// Check for a valid plugin header.

				return $local_cache[ $plugin_base ] = true;
			}

			return $local_cache[ $plugin_base ] = false;
		}

		/*
		 * Returns true/false.
		 *
		 * Example: $plugin_base = wpsso/wpsso.php.
		 *
		 * Used by WpssoCheck->get_avail().
		 */
		public static function is_plugin_active( $plugin_base, $read_cache = true ) {

			$active_plugins = self::get_active_plugins( $read_cache );

			if ( isset( $active_plugins[ $plugin_base ] ) ) {	// Associative array of true/false values.

				return $active_plugins[ $plugin_base ];	// Return true/false.
			}

			return false;
		}

		/*
		 * Check the 'update_plugins' site transient and return the number of updates pending for a given slug prefix.
		 *
		 * Example: $plugin_prefix = 'wpsso'
		 *
		 * Used by WpssoHead->pending_updates_notice().
		 */
		public static function get_updates_count( $plugin_prefix = '' ) {

			$updates_count  = 0;
			$update_plugins = get_site_transient( 'update_plugins' );

			if ( ! empty( $update_plugins->response ) ) {

				foreach ( $update_plugins->response as $plugin_base => $data ) {

					if ( ! empty( $plugin_prefix ) ) {

						/*
						 * Example:
						 *
						 * 	$plugin_base = wpsso/wpsso.php
						 *
						 * 	$data->slug = wpsso
						 */
						if ( isset( $data->slug ) && strpos( $data->slug, $plugin_prefix ) === 0 ) {

							$updates_count++;
						}

					} else $updates_count++;
				}
			}

			return $updates_count;
		}

		/*
		 * Returns true/false.
		 *
		 * Used by WpssoAdmin->get_ext_action_links().
		 */
		public static function have_plugin_update( $plugin_base ) {

			static $local_cache = array();	// Associative array of true/false values.

			if ( isset( $local_cache[ $plugin_base ] ) ) {

				return $local_cache[ $plugin_base ];

			} elseif ( empty( $plugin_base ) ) {	// Just in case.

				return $local_cache[ $plugin_base ] = false;

			} elseif ( ! self::is_plugin_installed( $plugin_base ) ) {

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

<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoSchemaGraph' ) ) {

	class WpssoSchemaGraph {

		private static $graph_context = 'https://schema.org';
		private static $graph_type    = 'graph';
		private static $graph_data    = array();

		public function __construct() {}

		public static function get_type_url() {

			return self::$graph_context . '/' . self::$graph_type;
		}

		public static function add_data( $data ) {

			static $local_home_url  = null;

			if ( null === $local_home_url ) {

				$local_home_url = untrailingslashit( get_home_url() );
			}

			if ( empty( $data[ '@id' ] ) ) {

				self::$graph_data[] = $data;

				return true;

			} else {

				$val =& $data[ '@id' ];

				if ( 0 === strpos( $val, $local_home_url ) ) {

					$val = str_replace( $local_home_url, '', $val );	// Shorten the @id value, if possible.
				}

				if ( ! isset( self::$graph_data[ $val ] ) ) {	// If not already added.

					if ( count( $data ) > 1 ) {	// Ignore arrays with only an @id.

						self::$graph_data[ $val ] = $data;

						return true;
					}
				}
			}

			return false;
		}

		public static function get_json_reset_data() {

			$json_data = self::get_json();

			self::reset_data();

			return $json_data;
		}

		public static function get_json() {

			$json_data = array(
				'@context' => self::$graph_context,
				'@graph'   => array_values( self::$graph_data ),	// Exclude the associative array key values.
			);

			self::clean_json( $json_data );

			return $json_data;
		}

		public static function reset_data() {

			self::$graph_data = array();
		}

		/*
		 * Recursively remove null values, empty strings, and empty arrays.
		 */
		public static function clean_json( array &$arr ) {

			foreach ( $arr as $key => $value ) {

				if ( null === $value ) {		// Null value.

					unset( $arr[ $key ] );

				} elseif ( '' === $value ) {		// Empty string.

					unset( $arr[ $key ] );

				} elseif ( array() === $value ) {	// Empty array.

					unset( $arr[ $key ] );

				} elseif ( is_array( $value ) ) {

					self::clean_json( $arr[ $key ] );
				}
			}
		}

		public static function optimize_json( array &$json_data ) {	// Pass by reference is OK.

			static $local_new_data  = array();
			static $local_recursion = null;
			static $local_id_anchor = null;
			static $local_home_url  = null;

			if ( isset( $json_data[ '@graph' ] ) ) {	// Top level of json.

				$local_recursion = 0;

			} elseif ( null !== $local_recursion ) {

				$local_recursion++;
			}

			if ( $local_recursion > 32 ) {	// Just in case.

				return;
			}

			if ( null === $local_id_anchor ) {	// Optimize and call just once.

				$local_id_anchor = WpssoSchema::get_id_anchor();
			}

			if ( null === $local_home_url ) {

				$local_home_url = untrailingslashit( get_home_url() );
			}

			foreach ( $json_data as $key => &$val ) {

				if ( is_array( $val ) ) {

					self::optimize_json( $val );

				} elseif ( '@id' === $key ) {

					if ( 0 === strpos( $val, $local_home_url ) ) {

						$val = str_replace( $local_home_url, '', $val );	// Shorten the @id value, if possible.
					}

					if ( count( $json_data ) > 1 ) {	// Ignore arrays with a single element (ie. the @id property).

						if ( false !== strpos( $val, $local_id_anchor ) ) {	// Only optimize our own @ids.

							if ( empty( $local_new_data[ $val ] ) ) {

								$local_new_data[ $val ] = $json_data;

								foreach ( $local_new_data[ $val ] as $new_key => &$new_val ) {

									if ( is_array( $new_val ) ) {

										self::optimize_json( $new_val );
									}
								}
							}

							$json_data = array( $key => $val );
						}
					}

					break;
				}
			}

			if ( isset( $json_data[ '@graph' ] ) ) {	// Top level of json.

				$combined_graph = array_merge( array_values( $local_new_data ), $json_data[ '@graph' ] );

				/*
				 * Cleanup any empty @id arrays.
				 */
				foreach ( $combined_graph as $num => $val ) {

					if ( is_array( $val ) ) {	// Just in case.

						if ( ! empty( $val[ '@id' ] ) && 1 === count( $val ) ) {

							unset( $combined_graph[ $num ] );
						}
					}
				}

				$json_data[ '@graph' ] = $combined_graph;

				/*
				 * Reset the static variables after merging the new data.
				 */
				$local_new_data = array();

				$local_recursion = null;

				return $json_data;

			} elseif ( null !== $local_recursion ) {

				$local_recursion--;
			}
		}
	}
}

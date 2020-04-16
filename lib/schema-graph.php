<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoSchemaGraph' ) ) {

	class WpssoSchemaGraph {

		private $p;
		private static $graph_context = 'https://schema.org';
		private static $graph_type    = 'graph';
		private static $graph_data    = array();

		public function __construct() {
		}

		public static function get_type_url() {

			return self::$graph_context . '/' . self::$graph_type;
		}

		public static function add_data( $data ) {

			if ( empty( $data[ '@id' ] ) ) {

				self::$graph_data[] = $data;

				return true;

			} elseif ( ! isset( self::$graph_data[ $data[ '@id' ] ] ) ) {

				if ( count( $data ) > 1 ) {	// Ignore arrays with only an @id.

					self::$graph_data[ $data[ '@id' ] ] = $data;

					return true;
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

			static $new_data  = array();
			static $recursion = null;
			static $id_anchor = null;

			if ( null === $id_anchor ) {	// Optimize and call just once.
				$id_anchor = WpssoSchema::get_id_anchor();
			}

			if ( isset( $json_data[ '@graph' ] ) ) {	// Top level of json.
				$recursion = 0;
			} elseif ( null !== $recursion ) {
				$recursion++;
			}

			if ( $recursion > 32 ) {	// Just in case.
				return;
			}

			foreach ( $json_data as $key => &$val ) {

				if ( is_array( $val ) ) {

					self::optimize_json( $val );

				} elseif ( $recursion > 2 && '@id' === $key && strpos( $val, $id_anchor ) ) {

					if ( count( $json_data ) > 1 ) {	// Ignore arrays with only an @id property.

						if ( ! isset( $new_data[ $val ] ) ) {

							$new_data[ $val ] = $json_data;

							foreach ( $new_data[ $val ] as $new_key => &$new_val ) {

								if ( is_array( $new_val ) ) {
									self::optimize_json( $new_val );
								}
							}
						}

						$json_data = array( $key => $val );
					}

					break;
				}
			}

			if ( isset( $json_data[ '@graph' ] ) ) {	// Top level of json.

				$json_data[ '@graph' ] = array_merge( array_values( $new_data ), $json_data[ '@graph' ] );

				/**
				 * Reset the static variables after saving/merging the new data.
				 */
				$new_data  = array();
				$recursion = null;

				return $json_data;

			} elseif ( null !== $recursion ) {
				$recursion--;
			}
		}
	}
}

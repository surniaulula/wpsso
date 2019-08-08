<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2019 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'WpssoSchemaGraph' ) ) {

	class WpssoSchemaGraph {

		private static $graph_context = 'https://schema.org';
		private static $graph_type    = 'graph';
		private static $graph_data    = array();

		public function __construct( &$plugin ) {
		}

		public static function get_type_url() {

			return self::$graph_context . '/' . self::$graph_type;
		}

		public static function add_data( $data ) {

			if ( empty( $data[ '@id' ] ) ) {

				self::$graph_data[] = $data;

				return true;

			} elseif ( ! isset( self::$graph_data[ $data[ '@id' ] ] ) ) {

				self::$graph_data[ $data[ '@id' ] ] = $data;

				return true;
			}

			return false;
		}

		public static function get_json() {

			$json_data = array(
				'@context' => self::$graph_context,
				'@graph'   => array_values( self::$graph_data ),	// Exclude the associative array key values.
			);

			return $json_data;
		}

		public static function get_json_clean() {

			$json_data = self::get_json();
			
			self::clean();

			return $json_data;
		}

		public static function clean() {
			
			self::$graph_data = array();
		}

		public static function optimize( array &$json_data ) {

			static $new_data  = array();
			static $recursion = null;

			if ( isset( $json_data[ '@graph' ] ) ) {
				$recursion = 0;
			} elseif ( null !== $recursion ) {
				$recursion++;
			}

			if ( $recursion > 32 ) {	// Just in case.
				return;
			}

			foreach ( $json_data as $key => &$val ) {

				if ( is_array( $val ) ) {

					self::optimize( $val );

				/**
				 * The first dimension is the @graph array, so only optimize the second dimension and up.
				 */
				} elseif ( $recursion > 2 && '@id' === $key && strpos( $val, '#id/' ) ) {

					if ( ! isset( $new_data[ $val ] ) ) {

						$new_data[ $val ] = $json_data;

						foreach ( $new_data[ $val ] as $new_key => &$new_val ) {
							if ( is_array( $new_val ) ) {
								self::optimize( $new_val );
							}
						}
					}

					$json_data = array( $key => $val );

					break;
				}
			}

			if ( isset( $json_data[ '@graph' ] ) ) {

				$json_data[ '@graph' ] = array_merge( array_values( $new_data ), $json_data[ '@graph' ] );

				return $json_data;

			} elseif ( null !== $recursion ) {
				$recursion--;
			}
		}
	}
}

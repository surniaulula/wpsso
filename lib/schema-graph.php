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

		protected static $graph_data = array();

		public function __construct( &$plugin ) {
		}

		public static function add( $json_data ) {

			$id = empty( $json_data[ '@id' ] ) ?
				'' : $json_data[ '@id' ];

			if ( empty( $id ) ) {

				self::$graph_data[] = $json_data;

				return true;
			}

			if ( ! isset( self::$graph_data[ $id ] ) ) {

				self::$graph_data[ $id ] = $json_data;

				return true;
			}

			return false;
		}

		public static function get( $graph_context = 'https://schema.org' ) {

			$graph_data = array(
				'@context' => $graph_context,
				'@graph'   => array_values( self::$graph_data ),
			);

			return $graph_data;
		}

		public static function optimize( array &$data ) {

			static $new_data  = array();
			static $recursion = null;

			if ( isset( $data[ '@graph' ] ) ) {
				$recursion = 0;
			} elseif ( null !== $recursion ) {
				$recursion++;
			}

			if ( $recursion > 32 ) {	// Just in case.
				return;
			}

			foreach ( $data as $key => &$val ) {

				if ( is_array( $val ) ) {

					self::optimize( $val );

				/**
				 * The first dimension is the @graph array, so only optimize the second dimension and up.
				 */
				} elseif ( $recursion > 2 && '@id' === $key && strpos( $val, '#id/' ) ) {

					if ( ! isset( $new_data[ $val ] ) ) {

						$new_data[ $val ] = $data;

						foreach ( $new_data[ $val ] as $new_key => &$new_val ) {
							if ( is_array( $new_val ) ) {
								self::optimize( $new_val );
							}
						}
					}

					$data = array( $key => $val );

					break;
				}
			}

			if ( isset( $data[ '@graph' ] ) ) {

				$data[ '@graph' ] = array_merge( array_values( $new_data ), $data[ '@graph' ] );

				return $data;

			} elseif ( null !== $recursion ) {
				$recursion--;
			}
		}
	}
}

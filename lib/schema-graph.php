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

		public static function optimize( array $data ) {
			return $data;
		}
	}
}

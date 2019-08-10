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

		private $p;
		private $graph_context = 'https://schema.org';
		private $graph_type    = 'graph';
		private $graph_data    = array();

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get_type_url() {

			return $this->graph_context . '/' . $this->graph_type;
		}

		public function add_data( $data ) {

			if ( empty( $data[ '@id' ] ) ) {

				$this->graph_data[] = $data;

				return true;

			} elseif ( ! isset( $this->graph_data[ $data[ '@id' ] ] ) ) {

				if ( count( $data ) > 1 ) {	// Ignore arrays with only an @id.

					$this->graph_data[ $data[ '@id' ] ] = $data;

					return true;
				}
			}

			return false;
		}

		public function get_json() {

			$json_data = array(
				'@context' => $this->graph_context,
				'@graph'   => array_values( $this->graph_data ),	// Exclude the associative array key values.
			);

			return $json_data;
		}

		public function get_json_clean() {

			$json_data = $this->get_json();
			
			$this->clean_data();

			return $json_data;
		}

		public function clean_data() {
			
			$this->graph_data = array();
		}

		public function optimize( array &$json_data ) {	// Pass by reference is OK.

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

					$this->optimize( $val );

				/**
				 * The first dimension is the @graph array, so only optimize the second dimension and up.
				 */
				} elseif ( $recursion > 2 && '@id' === $key && strpos( $val, '#id/' ) ) {

					if ( count( $json_data ) > 1 ) {	// Ignore arrays with only an @id.

						if ( ! isset( $new_data[ $val ] ) ) {

							$new_data[ $val ] = $json_data;

							foreach ( $new_data[ $val ] as $new_key => &$new_val ) {
								if ( is_array( $new_val ) ) {
									$this->optimize( $new_val );
								}
							}
						}

						$json_data = array( $key => $val );
					}

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

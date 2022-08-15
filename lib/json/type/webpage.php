<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeWebpage' ) ) {

	class WpssoJsonTypeWebpage {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_webpage' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_webpage( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			$crumb_data = (array) apply_filters( 'wpsso_json_prop_https_schema_org_breadcrumb', array(), $mod, $mt_og, $page_type_id, $is_main );

			if ( ! empty( $crumb_data ) ) {

				$json_ret[ 'breadcrumb' ] = $crumb_data;
			}

			if ( ! empty( $json_data[ 'image' ][ 0 ] ) ) {

				if ( ! empty( $json_data[ 'image' ][ 0 ][ '@id' ] ) ) {

					$json_ret[ 'primaryImageOfPage' ] = array( '@id' => $json_data[ 'image' ][ 0 ][ '@id' ] );

				} else {

					$json_ret[ 'primaryImageOfPage' ] = $json_data[ 'image' ][ 0 ];
				}
			}

			$json_ret[ 'potentialAction' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/ReadAction', array(
				'target' => $json_data[ 'url' ],
			) );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

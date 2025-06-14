<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeService' ) ) {

	class WpssoJsonTypeService {

		private $p;	// Wpsso class object.

		/*
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'json_data_https_schema_org_service' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_service( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$service_id = 'none';

			/*
			 * Maybe get a service ID from the "Select a Service" option.
			 */
			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking for schema_service_id metadata option value' );
				}

				$service_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_service_id', $filter_opts = true, $merge_defs = true );
			}

			if ( null === $service_id || 'none' === $service_id ) {	// Allow for $service_id = 0.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: service id is null or "none"' );
				}

				return $json_data;
			}

			/*
			 * Possibly inherit the schema type.
			 */
			if ( $this->p->debug->enabled ) {

				if ( ! empty( $json_data ) ) {

					$this->p->debug->log( 'possibly inherit the schema type' );

					$this->p->debug->log_arr( 'json_data', $json_data );
				}
			}

			$json_ret = WpssoSchema::get_data_context( $json_data );	// Returns array() if no schema type found.

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding data for service id = ' . $service_id );
			}

			WpssoSchemaSingle::add_service_data( $json_ret, $mod, $service_id, $list_el = false );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

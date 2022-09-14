<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeOrganization' ) ) {

	class WpssoJsonTypeOrganization {

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
				'json_data_https_schema_org_organization' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_organization( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$org_id = 'none';

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'checking for schema_organization_id metadata option value' );
				}

				/**
				 * Maybe get a different organization ID from the "Select an Organization" option.
				 */
				$org_id = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_organization_id', $filter_opts = true, $merge_defs = true );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'schema_organization_id = ' . $org_id );
				}
			}

			if ( null === $org_id || 'none' === $org_id ) {	// Allow for $org_id = 0.

				if ( ! empty( $mod[ 'is_home' ] ) ) {	// Home page (static or blog archive).

					$org_id = 'site';

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: organization id is null or "none"' );
					}

					return $json_data;
				}
			}

			/**
			 * Possibly inherit the schema type.
			 */
			if ( $this->p->debug->enabled ) {

				if ( ! empty( $json_data ) ) {

					$this->p->debug->log( 'possibly inherit the schema type' );

					$this->p->debug->log_arr( 'json_data', $json_data );
				}
			}

			$json_ret = WpssoSchema::get_data_context( $json_data );	// Returns array() if no schema type found.

		 	/**
			 * $org_id can be 'none', 'site', or a number (including 0).
			 *
		 	 * $org_logo_key can be empty, 'org_logo_url', or 'org_banner_url' for Articles.
			 *
			 * Do not provide localized option names - the method will fetch the localized values.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding data for organization id = ' . $org_id );
			}

			WpssoSchemaSingle::add_organization_data( $json_ret, $mod, $org_id, $org_logo_key = 'org_logo_url', $list_element = false );

			/**
			 * Update the @id string to avoid connecting the webpage organization markup to properties like
			 * 'publisher', 'organizer', 'performer', 'hiringOrganization', etc.
			 */
			WpssoSchema::update_data_id( $json_ret, array( 'knowledge-graph' ) );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

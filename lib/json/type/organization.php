<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeOrganization' ) ) {

	class WpssoJsonTypeOrganization {

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
				'json_data_https_schema_org_organization' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_organization( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
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

		 	/*
			 * Add the Organization.
			 */
			WpssoSchemaSingle::add_organization_data( $json_ret, $mod, $org_id = null, $org_logo_key = 'org_logo_url', $list_el = 'merge' );

			/*
			 * Add media if the Organization type is not also a sub-type of Place.
			 *
			 * See https://schema.org/image as https://schema.org/ImageObject.
			 * See https://schema.org/subjectOf as https://schema.org/VideoObject.
			 */
			$type_id = WpssoSchema::get_data_type_id( $json_ret );

			if ( ! $this->p->schema->is_schema_type_child( $type_id, 'place' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'adding image and subjectOf properties for organization' );
				}

				WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = 'subjectOf' );
			}

			/*
			 * Update the @id string to avoid connecting the webpage organization markup to properties like
			 * 'publisher', 'organizer', 'performer', 'hiringOrganization', etc.
			 */
			if ( $is_main ) {	// Just in case.

				WpssoSchema::update_data_id( $json_ret, 'knowledge-graph' );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

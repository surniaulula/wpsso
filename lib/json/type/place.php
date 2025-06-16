<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypePlace' ) ) {

	class WpssoJsonTypePlace {

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
				'json_data_https_schema_org_place' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_place( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

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
			 * Add the Place.
			 */
			WpssoSchemaSingle::add_place_data( $json_ret, $mod, $place_id = null, $list_el = false );

			/*
			 * See https://schema.org/image as https://schema.org/ImageObject.
			 * See https://schema.org/subjectOf as https://schema.org/VideoObject.
			 */
			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = 'subjectOf' );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

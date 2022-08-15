<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeEvent' ) ) {

	class WpssoJsonTypeEvent {

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
				'json_data_https_schema_org_event' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_event( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			WpssoSchemaSingle::add_event_data( $json_ret, $mod, $event_id = false, $list_element = false );

			/**
			 * Property:
			 *	image as https://schema.org/ImageObject
			 *	subjectOf as https://schema.org/VideoObject
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding image and subjectOf video properties for event' );
			}

			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = 'subjectOf' );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeJobPosting' ) ) {

	class WpssoJsonTypeJobPosting {

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
				'json_data_https_schema_org_jobposting' => 5,
			) );
		}

		/*
		 * See https://developers.google.com/search/docs/appearance/structured-data/job-posting.
		 */
		public function filter_json_data_https_schema_org_jobposting( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			/*
			 * See https://schema.org/datePosted.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
				'datePosted' => 'article:published_time',
			) );

		 	/*
			 * Add the Job.
			 */
			WpssoSchemaSingle::add_job_data( $json_ret, $mod, $job_id = null, $list_el = false );

			/*
			 * Add media.
			 *
			 * See https://schema.org/image as https://schema.org/ImageObject.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding image property for jobposting (videos disabled)' );
			}

			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = false );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

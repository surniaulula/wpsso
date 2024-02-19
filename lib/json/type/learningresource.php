<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeLearningResource' ) ) {

	class WpssoJsonTypeLearningResource {

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
				'json_data_https_schema_org_learningresource' => 5,
			) );
		}

		/*
		 * Schema CreativeWork > LearningResource.
		 *
		 * See https://developers.google.com/search/docs/appearance/structured-data/learning-video#learning-video-[videoobject,-learningresource].
		 */
		public function filter_json_data_https_schema_org_learningresource( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();
			$md_opts  = array();

			WpssoSchema::add_type_opts_md_pad( $md_opts, $mod );

			/*
			 * See https://schema.org/learningResourceType.
			 */
			if ( ! empty( $md_opts[ 'schema_schema_learnres_type' ] ) ) {

				$json_ret[ 'learningResourceType' ] = (string) $md_opts[ 'schema_schema_learnres_type' ];
			}

			/*
			 * See https://schema.org/educationalLevel.
			 */
			if ( WpssoSchema::is_valid_key( $md_opts, 'schema_schema_learnres_level' ) ) {	// Not null, an empty string, or 'none'.

				$json_ret[ 'educationalLevel' ] = (string) $md_opts[ 'schema_schema_learnres_level' ];
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeQuestion' ) ) {

	class WpssoJsonFiltersTypeQuestion {

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
				'json_data_https_schema_org_question' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_question( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$question = array();

			/**
			 * Answer:
			 *
			 * Schema Question is a sub-type of CreativeWork. We already have the question in 'name' (the post/page
			 * title), the answer excerpt in 'description', and the full answer text in 'text'. Create the answer
			 * first, before changing / removing some question properties.
			 */
			$accepted_answer = WpssoSchema::get_schema_type_context( 'https://schema.org/Answer' );

			WpssoSchema::add_data_itemprop_from_assoc( $accepted_answer, $json_data, array( 
				'url'           => 'url',
				'name'          => 'description',	// Answer name is CreativeWork custom description or excerpt.
				'text'          => 'text',		// May not exist if the 'schema_add_text_prop' option is disabled.
				'inLanguage'    => 'inLanguage',
				'dateCreated'   => 'dateCreated',
				'datePublished' => 'datePublished',
				'dateModified'  => 'dateModified',
				'author'        => 'author',
			) );

			$accepted_answer[ 'upvoteCount' ] = 0;

			$question[ 'acceptedAnswer' ] = $accepted_answer;

			$question[ 'answerCount' ] = 1;

			/**
			 * Question:
			 *
			 * Adjust the Question properties after having created the 'acceptedAnswer' property.
			 */
			if ( isset( $json_data[ 'name' ] ) ) {	// Just in case.

				$question[ 'text' ] = $json_data[ 'name' ];
			}

			/**
			 * 'description' = This property describes the question. If the question has a group heading then this may
			 * 	be an appropriate place to call out what that heading is.
			 */
			unset( $question[ 'description' ], $json_data[ 'description' ] );

			unset( $question[ 'acceptedAnswer' ][ 'description' ] );

			return WpssoSchema::return_data_from_filter( $json_data, $question, $is_main );
		}
	}
}

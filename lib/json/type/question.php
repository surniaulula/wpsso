<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeQuestion' ) ) {

	class WpssoJsonTypeQuestion {

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

			$json_ret = array();

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $json_data, array( 
				'text' => 'name',
			) );

			/**
			 * Answer:
			 *
			 * Schema Question is a sub-type of CreativeWork. We already have the question in 'name' (the post/page
			 * title), the answer excerpt in 'description', and the full answer text in 'text'. Create the answer
			 * first, before changing / removing some question properties.
			 */
			$accepted_answer = WpssoSchema::get_schema_type_context( 'https://schema.org/Answer' );

			WpssoSchema::add_data_itemprop_from_assoc( $accepted_answer, $json_data, array( 
				'url'        => 'url',
				'name'       => 'description',	// The Answer name is CreativeWork custom description or excerpt.
				'text'       => 'text',		// May not exist if the 'schema_add_text_prop' option is disabled.
				'inLanguage' => 'inLanguage',
			) );

			unset( $json_data[ 'description' ] );

			if ( empty( $accepted_answer[ 'text' ] ) ) {

				$accepted_answer[ 'text' ] = $this->p->page->get_text( $mod, $md_key = 'schema_text', $max_len = 'schema_text' );
			}

			WpssoSchema::add_data_itemprop_from_assoc( $accepted_answer, $json_ret, array( 
				'dateCreated'   => 'dateCreated',
				'datePublished' => 'datePublished',
				'dateModified'  => 'dateModified',
				'author'        => 'author',
			) );

			$accepted_answer[ 'upvoteCount' ] = 0;

			$json_ret[ 'acceptedAnswer' ] = $accepted_answer;

			$json_ret[ 'answerCount' ] = 1;

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

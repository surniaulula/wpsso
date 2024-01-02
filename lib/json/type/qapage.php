<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeQAPage' ) ) {

	class WpssoJsonTypeQAPage {

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
				'json_data_https_schema_org_qapage' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_qapage( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			unset( $json_data[ 'mainEntityOfPage' ] );

			$json_ret = array();

			$question = WpssoSchema::get_schema_type_context( 'https://schema.org/Question' );

			/*
			 * $question and $json_data are passed by reference.
			 */
			WpssoSchema::move_data_itemprop_from_assoc( $question, $json_data, array(
				'url'           => 'url',
				'name'          => 'name',
				'description'   => 'description',
				'text'          => 'text',
				'inLanguage'    => 'inLanguage',
				'dateCreated'   => 'dateCreated',
				'datePublished' => 'datePublished',
				'dateModified'  => 'dateModified',
				'author'        => 'author',
			) );

			/*
			 * The 'description' property describes the question.
			 *
			 * If the question has a group heading then this may be an appropriate place to call out what that heading is.
			 */
			if ( ! empty( $mod[ 'obj' ] ) ) {

				/*
				 * The CreativeWork 'text' property may be empty if 'schema_def_add_text_prop' is unchecked.
				 */
				if ( empty( $question[ 'text' ] ) ) {

					$question[ 'text' ] = $this->p->page->get_text( $mod, $md_key = 'schema_text', $max_len = 'schema_text' );
				}

				$question[ 'description' ] = $mod[ 'obj' ]->get_options( $mod[ 'id' ], 'schema_qa_desc' );

				/*
				 * If we have an accepted answer, then add the 'description' group heading to the accepted answer.
				 */
				if ( ! empty( $question[ 'acceptedAnswer' ] ) ) {

					$question[ 'acceptedAnswer' ][ 'description' ] = $question[ 'description' ];
				}

			} else {

				unset( $question[ 'description' ] );

				unset( $question[ 'acceptedAnswer' ][ 'description' ] );
			}

			/*
			 * Calculate the number of accepted and suggested answers.
			 */
			$answer_count = empty( $question[ 'acceptedAnswer' ] ) ? 0 : 1;

			if ( isset( $question[ 'suggestedAnswer' ] ) ) {

				$answer_count += SucomUtil::is_non_assoc( $question[ 'suggestedAnswer' ] ) ? count( $question[ 'suggestedAnswer' ] ) : 1;
			}

			$question[ 'answerCount' ] = $answer_count;

			$json_ret[ 'mainEntity' ] = $question;

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

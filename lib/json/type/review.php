<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeReview' ) ) {

	class WpssoJsonTypeReview {

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
				'json_data_https_schema_org_review' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_review( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();
			$md_opts  = array();

			SucomUtil::add_type_opts_md_pad( $md_opts, $mod );

			/**
			 * See https://schema.org/itemReviewed.
			 */
			WpssoSchema::add_item_reviewed_data( $json_ret[ 'itemReviewed' ], $mod, $md_opts );

			/**
			 * See https://schema.org/reviewRating.
			 */
			$json_ret[ 'reviewRating' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/Rating' );

			WpssoSchema::add_data_itemprop_from_assoc( $json_ret[ 'reviewRating' ], $md_opts, array(
				'alternateName' => 'schema_review_rating_alt_name',
				'ratingValue'   => 'schema_review_rating',
				'worstRating'   => 'schema_review_rating_from',
				'bestRating'    => 'schema_review_rating_to',
			) );

			$json_ret[ 'reviewRating' ] = (array) apply_filters( 'wpsso_json_prop_https_schema_org_reviewrating',
				$json_ret[ 'reviewRating' ], $mod, $mt_og, $page_type_id, $is_main );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

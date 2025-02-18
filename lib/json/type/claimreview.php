<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2025 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeClaimReview' ) ) {

	class WpssoJsonTypeClaimReview {

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
				'json_data_https_schema_org_claimreview' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_claimreview( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();
			$md_opts  = array();

			WpssoSchema::add_type_opts_md_pad( $md_opts, $mod );

			/*
			 * Create the 'appearance' property value.
			 *
			 * Inherit the 'itemReviewed' property value from https://schema.org/Review.
			 */
			if ( ! empty( $json_data[ 'itemReviewed' ] ) ) {

				$appear_type_obj  = $json_data[ 'itemReviewed' ];
				$appear_type_url  = $this->p->schema->get_data_type_url( $appear_type_obj );
				$claim_review_url = $this->p->schema->get_schema_type_url( 'review.claim' );

				/*
				 * The subject of a claim review cannot be another claim review.
				 */
				if ( $claim_review_url === $appear_type_url ) {

					/*
					 * Add notice only if the admin notices have not already been shown.
					 */
					if ( $this->p->notice->is_admin_pre_notices() ) {

						$notice_msg = __( 'A claim review cannot be the subject of another claim review.', 'wpsso' ) . ' ';

						$notice_msg .= __( 'CreativeWork will be used instead as the Schema type for the subject of the webpage (ie. the content) being reviewed.', 'wpsso' );

						$this->p->notice->err( $notice_msg );
					}

					$appear_type_url = $this->p->schema->get_schema_type_url( 'creative.work' );
					$appear_type_obj = $this->p->schema->get_schema_type_context( $appear_type_url, $appear_type_obj );
				}

			} else {

				$appear_type_url = $this->p->schema->get_schema_type_url( 'creative.work' );
				$appear_type_obj = $this->p->schema->get_schema_type_context( $appear_type_url );
			}

			/*
			 * Re-define the 'itemReviewed' property as a https://schema.org/Claim and set the 'appearance' property.
			 */
			$claim_type_url = $this->p->schema->get_schema_type_url( 'claim' );

			$json_ret[ 'itemReviewed' ] = WpssoSchema::get_schema_type_context( $claim_type_url );

			/*
			 * Google suggests adding the 'author' and 'datePublished' properties to the Schema Claim type, if
			 * available.
			 */
			foreach ( array( 'author', 'datePublished' ) as $prop_name ) {

				if ( ! empty( $appear_type_obj[ $prop_name ] ) ) {

					$json_ret[ 'itemReviewed' ][ $prop_name ] = $appear_type_obj[ $prop_name ];
				}
			}

			$json_ret[ 'itemReviewed' ][ 'appearance' ] = $appear_type_obj;

			/*
			 * https://schema.org/claimReviewed
			 *
			 * A short summary of the specific claims reviewed in a ClaimReview.
			 *
			 * Used on these types:
			 *
			 *	ClaimReview
			 */
			if ( ! empty( $md_opts[ 'schema_review_claim_reviewed' ] ) ) {

				$json_ret[ 'claimReviewed' ] = $md_opts[ 'schema_review_claim_reviewed' ];
			}

			/*
			 * If there's a first appearance URL, add the URL using a CreativeWork object as well.
			 */
			if ( ! empty( $md_opts[ 'schema_review_claim_first_url' ] ) ) {

				$json_ret[ 'itemReviewed' ][ 'firstAppearance' ] = WpssoSchema::get_schema_type_context( $appear_type_url );

				WpssoSchema::add_data_itemprop_from_assoc( $json_ret[ 'itemReviewed' ][ 'firstAppearance' ], $md_opts, array(
					'url' => 'schema_review_claim_first_url',
				) );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

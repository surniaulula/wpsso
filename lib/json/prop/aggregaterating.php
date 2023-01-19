<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonPropAggregateRating' ) ) {

	class WpssoJsonPropAggregateRating {

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
				'json_data_https_schema_org_thing' => 5,
			), $prio = 10000 );
		}

		/**
		 * Automatically include an aggregateRating property based on the Open Graph rating meta tags.
		 *
		 * $page_type_id is false and $is_main is true when called as part of a collection page part.
		 *
		 * The Schema standard provides 'aggregateRating' and 'review' properties for these types:
		 *
		 * 	Brand
		 * 	CreativeWork
		 * 	Event
		 * 	Offer
		 * 	Organization
		 * 	Place
		 * 	Product
		 * 	Service
		 *
		 * Unfortunately Google allows the 'aggregateRating' property only for these types:
		 *
		 *	Book
		 *	Course
		 *	Event
		 *	HowTo (includes Recipe)
		 *	LocalBusiness
		 *	Movie
		 *	Product
		 *	SoftwareApplication
		 *
		 * And the 'review' property only for these types:
		 *
		 *	Book
		 *	Course
		 *	CreativeWorkSeason
		 *	CreativeWorkSeries
		 *	Episode
		 *	Event
		 *	Game
		 *	HowTo (includes Recipe)
		 *	LocalBusiness
		 *	MediaObject
		 *	Movie
		 *	MusicPlaylist
		 * 	MusicRecording
		 *	Organization
		 *	Product
		 *	SoftwareApplication
		 */
		public function filter_json_data_https_schema_org_thing( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			$aggr_rating = array(
				'ratingValue' => null,
				'ratingCount' => null,
				'worstRating' => 1,
				'bestRating'  => 5,
				'reviewCount' => null,
			);

			$og_type = isset( $mt_og[ 'og:type' ] ) ? $mt_og[ 'og:type' ] : false;

			/**
			 * Only pull values from meta tags if this is the main entity markup.
			 */
			if ( $is_main && $og_type ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log_arr( 'open graph rating', SucomUtil::preg_grep_keys( '/:rating:/', $mt_og ) );
				}

				WpssoSchema::add_data_itemprop_from_assoc( $aggr_rating, $mt_og, array(
					'ratingValue' => $og_type . ':rating:average',
					'ratingCount' => $og_type . ':rating:count',
					'worstRating' => $og_type . ':rating:worst',
					'bestRating'  => $og_type . ':rating:best',
					'reviewCount' => $og_type . ':review:count',
				) );
			}

			$aggr_rating = WpssoSchema::get_schema_type_context( 'https://schema.org/AggregateRating', $aggr_rating );

			$aggr_rating = (array) apply_filters( 'wpsso_json_prop_https_schema_org_aggregaterating', $aggr_rating, $mod, $mt_og, $page_type_id, $is_main );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_arr( 'aggregate rating', $aggr_rating );
			}

			/**
			 * Check for at least two essential meta tags (a rating value, and a rating count or review count).
			 *
			 * The rating value is expected to be a float and the rating counts are expected to be integers.
			 */
			if ( ! empty( $aggr_rating[ 'ratingValue' ] ) ) {

				if ( ! empty( $aggr_rating[ 'ratingCount' ] ) ) {

					if ( empty( $aggr_rating[ 'reviewCount' ] ) ) {	// Must be positive if included.

						unset( $aggr_rating[ 'reviewCount' ] );
					}

					$json_ret[ 'aggregateRating' ] = $aggr_rating;

				} elseif ( ! empty( $aggr_rating[ 'reviewCount' ] ) ) {

					if ( empty( $aggr_rating[ 'ratingCount' ] ) ) {	// Must be positive if included.

						unset( $aggr_rating[ 'ratingCount' ] );
					}

					$json_ret[ 'aggregateRating' ] = $aggr_rating;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'aggregate rating ignored: ratingCount and reviewCount are empty' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'aggregate rating ignored: ratingValue is empty' );
			}

			/**
			 * Return if nothing to do.
			 */
			if ( empty( $json_ret[ 'aggregateRating' ] ) && empty( $json_data[ 'aggregateRating' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no aggregate rating' );
				}

				unset( $json_ret[ 'aggregateRating' ], $json_data[ 'aggregateRating' ] );

				return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
			}

			/**
			 * Make sure aggregate ratings are allowed by Google for this Schema type.
			 */
			if ( ! $this->p->schema->allow_aggregate_rating( $page_type_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cannot add aggregate rating to page type id ' . $page_type_id );
				}

				unset( $json_ret[ 'aggregateRating' ], $json_data[ 'aggregateRating' ] );

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$page_type_url = $this->p->schema->get_schema_type_url( $page_type_id );

					$notice_msg = sprintf( __( 'An aggregate rating value for this markup has been ignored - <a href="%1$s">Google does not allow an aggregate rating value for the Schema %2$s type</a>.', 'wpsso' ), 'https://developers.google.com/search/docs/data-types/review-snippet', $page_type_url );

					$this->p->notice->warn( $notice_msg );

				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

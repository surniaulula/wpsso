<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonPropReview' ) ) {

	class WpssoJsonPropReview {

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
				'json_data_https_schema_org_thing' => 5,
			), $prio = 20000 );
		}

		/*
		 * Automatically include a review property based on the Open Graph review meta tags.
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

			$json_ret    = array();
			$all_reviews = array();
			$og_type     = isset( $mt_og[ 'og:type' ] ) ? $mt_og[ 'og:type' ] : false;

			/*
			 * Move any existing properties (from shortcodes, for example) so we can filter them and add new ones.
			 */
			if ( isset( $json_data[ 'review' ] ) ) {

				if ( isset( $json_data[ 'review' ][ 0 ] ) ) {	// Has an array of types.

					$all_reviews = $json_data[ 'review' ];

				} elseif ( ! empty( $json_data[ 'review' ] ) ) {

					$all_reviews[] = $json_data[ 'review' ];	// Markup for a single type.
				}

				unset( $json_data[ 'review' ] );
			}

			/*
			 * Only pull values from meta tags if this is the main entity markup.
			 */
			if ( $is_main && $og_type ) {

				if ( ! empty( $mt_og[ $og_type . ':reviews' ] ) && is_array( $mt_og[ $og_type . ':reviews' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding ' . count( $mt_og[ $og_type . ':reviews' ] ) . ' product reviews from mt_og' );
					}

					foreach ( $mt_og[ $og_type . ':reviews' ] as $mt_review ) {

						$single_review = WpssoSchema::get_data_itemprop_from_assoc( $mt_review, array(
							'url'          => 'review:url',
							'dateCreated'  => 'review:created_time',
							'dateModified' => 'review:updated_time',
							'name'         => 'review:title',
							'description'  => 'review:description',
							'text'         => 'review:text',
						) );

						if ( ! empty( $mt_review[ 'review:rating:value' ] ) ) {

							$single_review[ 'reviewRating' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/Rating',
								WpssoSchema::get_data_itemprop_from_assoc( $mt_review, array(
									'ratingValue' => 'review:rating:value',
									'worstRating' => 'review:rating:worst',
									'bestRating'  => 'review:rating:best',
								) )
							);
						}

						if ( ! empty( $mt_review[ 'review:author:name' ] ) ) {

							$single_review[ 'author' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/Person',
								WpssoSchema::get_data_itemprop_from_assoc( $mt_review, array(
									'url'  => 'review:author:url',
									'name' => 'review:author:name',
								) )
							);
						}

						if ( ! empty( $mt_review[ 'review:image' ] ) ) {

							WpssoSchema::add_images_data_mt( $single_review[ 'image' ], $mt_review[ 'review:image' ] );
						}

						if ( ! empty( $mt_review[ 'review:video' ] ) ) {

							WpssoSchema::add_videos_data_mt( $single_review[ 'video' ], $mt_review[ 'review:video' ] );
						}

						/*
						 * If we have a comment ID then add any replies to the comment.
						 */
						if ( ! empty( $mt_review[ 'review:id' ] ) ) {

							$replies_added = WpssoSchemaSingle::add_comment_reply_data( $single_review[ 'comment' ],
								$mod, $mt_review[ 'review:id' ] );

							if ( ! $replies_added ) {

								unset( $single_review[ 'comment' ] );
							}

							/*
							 * Update the @id string based on $json_ret[ 'url' ], $type_id, and $comment_id values.
							 */
							WpssoSchema::update_data_id( $single_review, array( $type_id = 'review', $mt_review[ 'review:id' ] ) );
						}

						/*
						 * Add the complete review.
						 */
						$all_reviews[] = WpssoSchema::get_schema_type_context( 'https://schema.org/Review', $single_review );
					}
				}
			}

			$json_ret[ 'review' ] = apply_filters( 'wpsso_json_prop_https_schema_org_review', $all_reviews, $mod, $mt_og, $page_type_id, $is_main );

			/*
			 * Return if nothing to do.
			 */
			if ( empty( $json_ret[ 'review' ] ) && empty( $json_data[ 'review' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no reviews' );
				}

				unset( $json_ret[ 'review' ], $json_data[ 'review' ] );

				return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
			}

			/*
			 * Make sure reviews are allowed by Google for this Schema type.
			 */
			if ( ! $this->p->schema->allow_review( $page_type_id ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cannot add review to page type id ' . $page_type_id );
				}

				unset( $json_ret[ 'review' ], $json_data[ 'review' ] );

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$page_type_url = $this->p->schema->get_schema_type_url( $page_type_id );

					$notice_msg = sprintf( __( 'Reviews for this markup have been ignored - <a href="%1$s">Google does not allow reviews for the Schema %2$s type</a>.', 'wpsso' ), 'https://developers.google.com/search/docs/data-types/review-snippet', $page_type_url );

					$this->p->notice->warn( $notice_msg );
				}
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

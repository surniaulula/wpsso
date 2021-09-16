<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeReview' ) ) {

	class WpssoJsonFiltersTypeReview {

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

			if ( ! empty( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_opts = SucomUtil::get_opts_begin( 'schema_review_', array_merge( 
					(array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] ),
					(array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] )	// Returns empty string if no meta found.
				) );

			} else {

				$md_opts = array();
			}

			/**
			 * Property:
			 * 	itemReviewed
			 */
			if ( WpssoSchema::is_valid_key( $md_opts, 'schema_review_item_type' ) ) {	// Not null, an empty string, or 'none'.

				$item_type_id = $md_opts[ 'schema_review_item_type' ];

			} else {

				$item_type_id = 'thing';
			}

			$item_type_url = $this->p->schema->get_schema_type_url( $item_type_id );

			$json_ret[ 'itemReviewed' ] = WpssoSchema::get_schema_type_context( $item_type_url );

			$item_reviewed =& $json_ret[ 'itemReviewed' ];	// Shortcut variable name.

			WpssoSchema::add_data_itemprop_from_assoc( $item_reviewed, $md_opts, array(
				'url'         => 'schema_review_item_url',
				'name'        => 'schema_review_item_name',
				'description' => 'schema_review_item_desc',
			) );

			foreach ( SucomUtil::preg_grep_keys( '/^schema_review_item_sameas_url_[0-9]+$/', $md_opts ) as $url ) {

				$item_reviewed[ 'sameAs' ][] = SucomUtil::esc_url_encode( $url );
			}

			WpssoSchema::check_prop_value_sameas( $item_reviewed );

			/**
			 * Set reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $this->p->util->get_canonical_url( $mod );

				$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'adding reviewed subject image', 'wpsso' ) );
			}

			/**
			 * Add the item images.
			 *
			 * $size_names can be a keyword (ie. 'opengraph' or 'schema'), a registered size name, or an array of size names.
			 */
			$mt_images = $this->p->media->get_mt_opts_images( $md_opts, $size_names = 'schema', $img_pre = 'schema_review_item_img' );

			WpssoSchema::add_images_data_mt( $item_reviewed[ 'image' ], $mt_images );

			if ( empty( $item_reviewed[ 'image' ] ) ) {

				unset( $item_reviewed[ 'image' ] );	// Prevent null assignment.

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( $item_reviewed[ 'image' ] );
			}

			/**
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$this->p->util->maybe_unset_ref( $canonical_url );
			}

			/**
			 * Schema Reviewed Item: Creative Work
			 */
			if ( $this->p->schema->is_schema_type_child( $item_type_id, 'creative.work' ) ) {

				/**
				 * The author type value should be either 'organization' or 'person'.
				 */
				if ( WpssoSchema::is_valid_key( $md_opts, 'schema_review_item_cw_author_type' ) ) {	// Not null, an empty string, or 'none'.

					$author_type_url = $this->p->schema->get_schema_type_url( $md_opts[ 'schema_review_item_cw_author_type' ] );

					$item_reviewed[ 'author' ] = WpssoSchema::get_schema_type_context( $author_type_url );

					WpssoSchema::add_data_itemprop_from_assoc( $item_reviewed[ 'author' ], $md_opts, array(
						'name' => 'schema_review_item_cw_author_name',
					) );

					if ( ! empty( $md_opts[ 'schema_review_item_cw_author_url' ] ) ) {

						$item_reviewed[ 'author' ][ 'sameAs' ][] = SucomUtil::esc_url_encode( $md_opts[ 'schema_review_item_cw_author_url' ] );
					}
				}

				/**
				 * Add the creative work published date, if one is available.
				 */
				if ( $date = WpssoSchema::get_opts_date_iso( $md_opts, 'schema_review_item_cw_pub' ) ) {

					$item_reviewed[ 'datePublished' ] = $date;
				}

				/**
				 * Add the creative work created date, if one is available.
				 */
				if ( $date = WpssoSchema::get_opts_date_iso( $md_opts, 'schema_review_item_cw_created' ) ) {

					$item_reviewed[ 'dateCreated' ] = $date;
				}

				/**
				 * Schema Reviewed Item: Creative Work -> Book
				 */
				if ( $this->p->schema->is_schema_type_child( $item_type_id, 'book' ) ) {

					WpssoSchema::add_data_itemprop_from_assoc( $item_reviewed, $md_opts, array(
						'isbn' => 'schema_review_item_cw_book_isbn',
					) );

				/**
				 * Schema Reviewed Item: Creative Work -> Movie
				 */
				} elseif ( $this->p->schema->is_schema_type_child( $item_type_id, 'movie' ) ) {

					/**
					 * Property:
					 * 	actor (supersedes actors)
					 */
					WpssoSchema::add_person_names_data( $item_reviewed, 'actor', $md_opts, 'schema_review_item_cw_movie_actor_person_name' );

					/**
					 * Property:
					 * 	director
					 */
					WpssoSchema::add_person_names_data( $item_reviewed, 'director', $md_opts, 'schema_review_item_cw_movie_director_person_name' );

				/**
				 * Schema Reviewed Item: Creative Work -> Software Application
				 */
				} elseif ( $this->p->schema->is_schema_type_child( $item_type_id, 'software.application' ) ) {

					WpssoSchema::add_data_itemprop_from_assoc( $item_reviewed, $md_opts, array(
						'applicationCategory'  => 'schema_review_item_software_app_cat',
						'operatingSystem'      => 'schema_review_item_software_app_os',
					) );

					$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

					foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

						$offer_opts = SucomUtil::preg_grep_keys( '/^schema_review_item_software_app_(offer_.*)_' . $key_num. '$/',
							$md_opts, $invert = false, $replace = '$1' );

						/**
						 * Must have at least an offer name and price.
						 */
						if ( isset( $offer_opts[ 'offer_name' ] ) && isset( $offer_opts[ 'offer_price' ] ) ) {

							if ( false !== ( $offer = WpssoSchema::get_data_itemprop_from_assoc( $offer_opts, array( 
								'name'          => 'offer_name',
								'price'         => 'offer_price',
								'priceCurrency' => 'offer_currency',
								'availability'  => 'offer_avail',	// In stock, Out of stock, Pre-order, etc.
							) ) ) ) {

								/**
								 * Avoid Google validator warnings.
								 */
								$offer[ 'url' ]             = $item_reviewed[ 'url' ];
								$offer[ 'priceValidUntil' ] = gmdate( 'c', time() + MONTH_IN_SECONDS );

								/**
								 * Add the offer.
								 */
								$item_reviewed[ 'offers' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/Offer', $offer );
							}
						}
					}
				}

			/**
			 * Schema Reviewed Item: Product
			 */
			} elseif ( $this->p->schema->is_schema_type_child( $item_type_id, 'product' ) ) {

				WpssoSchema::add_data_itemprop_from_assoc( $item_reviewed, $md_opts, array(
					'sku'  => 'schema_review_item_product_retailer_part_no',
					'mpn'  => 'schema_review_item_product_mfr_part_no',
				) );

				/**
				 * Add the product brand.
				 */
				$single_brand = WpssoSchema::get_data_itemprop_from_assoc( $md_opts, array( 
					'name' => 'schema_review_item_product_brand',
				) );

				if ( false !== $single_brand ) {	// Just in case.

					$item_reviewed[ 'brand' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/Brand', $single_brand );
				}

				$metadata_offers_max = SucomUtil::get_const( 'WPSSO_SCHEMA_METADATA_OFFERS_MAX', 5 );

				foreach ( range( 0, $metadata_offers_max - 1, 1 ) as $key_num ) {

					$offer_opts = SucomUtil::preg_grep_keys( '/^schema_review_item_product_(offer_.*)_' . $key_num. '$/',
						$md_opts, $invert = false, $replace = '$1' );

					/**
					 * Must have at least an offer name and price.
					 */
					if ( isset( $offer_opts[ 'offer_name' ] ) && isset( $offer_opts[ 'offer_price' ] ) ) {

						if ( false !== ( $offer = WpssoSchema::get_data_itemprop_from_assoc( $offer_opts, array( 
							'name'          => 'offer_name',
							'price'         => 'offer_price',
							'priceCurrency' => 'offer_currency',
							'availability'  => 'offer_avail',	// In stock, Out of stock, Pre-order, etc.
						) ) ) ) {

							/**
							 * Add the offer.
							 */
							$item_reviewed[ 'offers' ][] = WpssoSchema::get_schema_type_context( 'https://schema.org/Offer', $offer );
						}
					}
				}
			}

			$json_ret[ 'itemReviewed' ] = (array) apply_filters( 'wpsso_json_prop_https_schema_org_itemreviewed',
				$item_reviewed, $mod, $mt_og, $page_type_id, $is_main );

			/**
			 * Property:
			 * 	reviewRating
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

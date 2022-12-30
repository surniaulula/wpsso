<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeProduct' ) ) {

	class WpssoJsonTypeProduct {

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
				'json_data_https_schema_org_product' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_product( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$json_ret = array();

			/**
			 * Note that there is no Schema 'availability' property for the 'product:availability' value.
			 *
			 * Note that there is no Schema 'ean' property for the 'product:ean' value.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
				'url'                   => 'product:url',
				'name'                  => 'product:title',
				'description'           => 'product:description',
				'category'              => 'product:category',		// Product category ID from Google product taxonomy.
				'sku'                   => 'product:retailer_part_no',	// Product SKU.
				'mpn'                   => 'product:mfr_part_no',	// Product MPN.
				'gtin14'                => 'product:gtin14',		// Valid for both products and offers.
				'gtin13'                => 'product:gtin13',		// Valid for both products and offers.
				'gtin12'                => 'product:gtin12',		// Valid for both products and offers.
				'gtin8'                 => 'product:gtin8',		// Valid for both products and offers.
				'gtin'                  => 'product:gtin',		// Valid for both products and offers.
				'itemCondition'         => 'product:condition',		// Valid for both products and offers.
				'hasAdultConsideration' => 'product:adult_type',	// Valid for both products and offers.
				'color'                 => 'product:color',
				'material'              => 'product:material',
				'pattern'               => 'product:pattern',
			) );

			/**
			 * Convert a numeric category ID to its Google category string.
			 */
			WpssoSchema::check_prop_value_category( $json_ret );

			WpssoSchema::check_prop_value_gtin( $json_ret );

			/**
			 * Schema 'productID' property.
			 */
			foreach ( array( 'isbn', 'retailer_item_id' ) as $pref_id ) {

				if ( WpssoSchema::is_valid_key( $mt_og, 'product:' . $pref_id ) ) {	// Not null, an empty string, or 'none'.

					$json_ret[ 'productID' ] = $pref_id . ':' . $mt_og[ 'product:' . $pref_id ];

					break;	// Stop here.
				}
			}

			/**
			 * Schema 'brand' property.
			 */
			if ( WpssoSchema::is_valid_key( $mt_og, 'product:brand' ) ) {	// Not null, an empty string, or 'none'.

				$brand = WpssoSchema::get_data_itemprop_from_assoc( $mt_og, array(
					'name' => 'product:brand',
				) );

				if ( false !== $brand ) {	// Just in case.

					$json_ret[ 'brand' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/Brand', $brand );
				}
			}

			/**
			 * Schema 'audience' property.
			 *
			 * See https://support.google.com/merchants/answer/6324479 for 'suggestedGender'.
			 * See https://support.google.com/merchants/answer/6324463 for 'suggestedMinAge' and 'suggestedMaxAge'.
			 */
			$audience = array();

			if ( WpssoSchema::is_valid_key( $mt_og, 'product:target_gender' ) ) {	// Not null, an empty string, or 'none'.

				$audience[ 'suggestedGender' ] = $mt_og[ 'product:target_gender' ];
			}

			if ( WpssoSchema::is_valid_key( $mt_og, 'product:age_group' ) ) {	// Not null, an empty string, or 'none'.

				/**
				 * Age is expressed in years so, for example, use 0.25 for 3 months.
				 *
				 * See https://support.google.com/merchants/answer/6324463.
				 */
				switch ( $mt_og[ 'product:age_group' ] ) {
					case 'adult':     $audience[ 'suggestedMinAge' ] = 13;   break;
					case 'all ages':  $audience[ 'suggestedMinAge' ] = 13;   break;
					case 'infant':    $audience[ 'suggestedMinAge' ] = 0.25; $audience[ 'suggestedMaxAge' ] = 1;    break;
					case 'kids':      $audience[ 'suggestedMinAge' ] = 5;    $audience[ 'suggestedMaxAge' ] = 13;   break;
					case 'newborn':   $audience[ 'suggestedMinAge' ] = 0;    $audience[ 'suggestedMaxAge' ] = 0.25; break;
					case 'teen':      $audience[ 'suggestedMinAge' ] = 13;   break;
					case 'toddler':   $audience[ 'suggestedMinAge' ] = 1;    $audience[ 'suggestedMaxAge' ] = 5;    break;
				}
			}

			if ( ! empty( $audience ) ) {

				$json_ret[ 'audience' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/PeopleAudience', $audience );
			}

			/**
			 * Schema 'size' property.
			 *
			 * See https://support.google.com/merchants/answer/6324492 for 'name'.
			 * See https://support.google.com/merchants/answer/6324497 for 'sizeGroup'.
			 * See https://support.google.com/merchants/answer/6324502 for 'sizeSystem'.
			 */
			$size_spec = WpssoSchema::get_data_itemprop_from_assoc( $mt_og, array(
				'name'       => 'product:size',
				'sizeGroup'  => 'product:size_group',
				'sizeSystem' => 'product:size_system',
			) );

			if ( false !== $size_spec ) {

				$json_ret[ 'size' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/SizeSpecification', $size_spec );
			}

			/**
			 * Schema 'length', 'width', 'height', 'weight' properties.
			 */
			WpssoSchema::add_data_unit_from_assoc( $json_ret, $mt_og, $names = array(
				'length'       => 'product:length:value',
				'width'        => 'product:width:value',
				'height'       => 'product:height:value',
				'weight'       => 'product:weight:value',
				'fluid_volume' => 'product:fluid_volume:value',
			) );

			/**
			 * Schema 'hasEnergyConsumptionDetails' property.
			 */
			if ( WpssoSchema::is_valid_key( $mt_og, 'product:energy_efficiency:value' ) ) {	// Not null, an empty string, or 'none'.

				$energy_efficiency = WpssoSchema::get_data_itemprop_from_assoc( $mt_og, array(
					'hasEnergyEfficiencyCategory' => 'product:energy_efficiency:value',
					'energyEfficiencyScaleMin'    => 'product:energy_efficiency:min_value',
					'energyEfficiencyScaleMax'    => 'product:energy_efficiency:max_value',
				) );

				if ( false !== $energy_efficiency ) {	// Just in case.

					$json_ret[ 'hasEnergyConsumptionDetails' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/EnergyConsumptionDetails',
						$energy_efficiency );
				}
			}

			/**
			 * See https://schema.org/image as https://schema.org/ImageObject.
			 * See https://schema.org/subjectOf as https://schema.org/VideoObject.
			 */
			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'adding image and subjectOf video properties for product' );
			}

			WpssoSchema::add_media_data( $json_ret, $mod, $mt_og, $size_names = 'schema', $add_video = 'subjectOf' );

			/**
			 * Prevent recursion for an itemOffered within a Schema Offer.
			 */
			static $local_is_recursion = false;

			if ( $local_is_recursion ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'product offer recursion detected and avoided' );
				}

			} else {

				$local_is_recursion = true;

				/**
				 * See https://schema.org/offers as https://schema.org/Offer
				 */
				if ( empty( $mt_og[ 'product:offers' ] ) ) {	// No product variations.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting single offer data' );
					}

					if ( $offer = WpssoSchemaSingle::get_offer_data( $mod, $mt_og ) ) {

						$json_ret[ 'offers' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/Offer', $offer );

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'returned single offer is empty' );
					}

				/**
				 * See https://schema.org/offers as https://schema.org/AggregateOffer
				 */
				} elseif ( is_array( $mt_og[ 'product:offers' ] ) ) {	// Just in case - must be an array.

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting aggregate offer data' );
					}

					if ( empty( $this->p->options[ 'schema_aggr_offers' ] ) ) {

						WpssoSchema::add_offers_data( $json_ret, $mod, $mt_og[ 'product:offers' ] );

					} else {

						WpssoSchema::add_offers_aggregate_data( $json_ret, $mod, $mt_og[ 'product:offers' ] );
					}

				} else {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'product offers is not an array' );
					}
				}

				$local_is_recursion = false;
			}

			/**
			 * Check for required Product properties.
			 *
			 * The "image" property is required for Google's Merchant listings validator.
			 */
			WpssoSchema::check_required_props( $json_ret, $mod, array( 'image' ) );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

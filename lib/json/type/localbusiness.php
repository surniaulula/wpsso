<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeLocalBusiness' ) ) {

	class WpssoJsonTypeLocalBusiness {

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
				'json_data_https_schema_org_localbusiness' => 5,
			) );
		}

		public function filter_json_data_https_schema_org_localbusiness( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Skip if not the main schema types or there are no place meta tags.
			 */
			if ( ! $is_main || ! preg_grep( '/^place:/', array_keys( $mt_og ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: not main or no place meta tags');
				}

				return $json_data;
			}

			$json_ret = array();

			/*
			 * See https://schema.org/currenciesAccepted.
			 * See https://schema.org/paymentAccepted.
			 * See https://schema.org/priceRange.
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array(
				'currenciesAccepted' => 'place:business:currencies_accepted',	// Example: USD, CAD.
				'paymentAccepted'    => 'place:business:payment_accepted',	// Example: Cash, Credit Card.
				'priceRange'         => 'place:business:price_range',		// Example: $$.
			) );

			/*
			 * See https://schema.org/areaServed as https://schema.org/GeoShape.
			 */
			if ( ! empty( $mt_og[ 'place:location:latitude' ] ) &&
				! empty( $mt_og[ 'place:location:longitude' ] ) &&
					! empty( $mt_og[ 'place:business:service_radius' ] ) ) {

				$json_ret[ 'areaServed' ] = WpssoSchema::get_schema_type_context( 'https://schema.org/GeoShape', array(
					'circle' => $mt_og[ 'place:location:latitude' ] . ' ' . $mt_og[ 'place:location:longitude' ] . ' ' .
						$mt_og[ 'place:business:service_radius' ]
				) );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no place:location meta tags found for area served' );
			}

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

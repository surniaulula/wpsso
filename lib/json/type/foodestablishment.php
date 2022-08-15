<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonTypeFoodEstablishment' ) ) {

	class WpssoJsonTypeFoodEstablishment {

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
				'json_data_https_schema_org_foodestablishment' => 5,
			) );
		}

		/**
		 * https://schema.org/Bakery
		 * https://schema.org/BarOrPub
		 * https://schema.org/Brewery
		 * https://schema.org/CafeOrCoffeeShop
		 * https://schema.org/FastFoodRestaurant
		 * https://schema.org/FoodEstablishment
		 * https://schema.org/IceCreamShop
		 * https://schema.org/Restaurant
		 * https://schema.org/Winery
		 */
		public function filter_json_data_https_schema_org_foodestablishment( $json_data, $mod, $mt_og, $page_type_id, $is_main ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Skip if not the main schema types or there are no place meta tags.
			 */
			if ( ! $is_main || ! preg_grep( '/^place:/', array_keys( $mt_og ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: not main or no place meta tags');
				}

				return $json_data;
			}

			$json_ret = array();

			/**
			 * Property:
			 * 	acceptsReservations
			 * 	hasMenu
			 * 	servesCuisine
			 */
			WpssoSchema::add_data_itemprop_from_assoc( $json_ret, $mt_og, array( 
				'acceptsReservations' => 'place:business:accepts_reservations',	// True or false.
				'hasMenu'             => 'place:business:menu_url',
				'servesCuisine'       => 'place:business:cuisine',
			) );

			return WpssoSchema::return_data_from_filter( $json_data, $json_ret, $is_main );
		}
	}
}

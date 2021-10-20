<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2016-2021 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoJsonFiltersTypeWebsite' ) ) {

	class WpssoJsonFiltersTypeWebsite {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by Wpsso->init_json_filters().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/**
			 * Use the WpssoSchema method / filter.
			 */
			$this->p->util->add_plugin_filters( $this->p->schema, array(
				'json_data_https_schema_org_website' => 5,
			) );

			/**
			 * Disable JSON-LD markup from the WooCommerce WC_Structured_Data class (since v3.0.0).
			 */
			if ( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) {

				add_filter( 'woocommerce_structured_data_website', '__return_empty_array', PHP_INT_MAX );
			}
		}
	}
}

<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

/*
 * Integration module for the WooCommerce Currency Switcher plugin.
 */
if ( ! class_exists( 'WpssoIntegEcomWooCommerceCurrencySwitcher' ) ) {

	class WpssoIntegEcomWooCommerceCurrencySwitcher {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$this->p->util->add_plugin_filters( $this, array(
				'currency' => 1,
			), PHP_INT_MAX );
		}

		/*
		 * If the WooCommerce Currency Switcher plugin 'woocs_is_multiple_allowed' option is false, it will adjusts ONLY
		 * the currency value, not product prices, so product prices will not match the currency returned by WooCommerce.
		 *
		 * When 'woocs_is_multiple_allowed' is false, return the original WooCommerce currency to match the WooCommerce
		 * product prices.
		 */
		public function filter_currency( $shop_currency ) {

			if ( ! get_option( 'woocs_is_multiple_allowed', 0 ) ) {

				return get_option( 'woocommerce_currency' );
			}

			return $shop_currency;
		}
	}
}

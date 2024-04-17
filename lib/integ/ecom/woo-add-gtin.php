<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoIntegEcomWooAddGtin' ) ) {

	class WpssoIntegEcomWooAddGtin {

		private $p;	// Wpsso class object.

		private static $meta_name     = 'hwp_product_gtin';
		private static $var_meta_name = 'hwp_var_gtin';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Custom fields are read using the 'wpsso_import_custom_fields' filter.
			 */
			SucomUtilOptions::set_key_value_disabled( 'plugin_cf_product_gtin', self::$meta_name, $this->p->options );

			/*
			 * Product attributes are read using the 'wpsso_import_product_attributes' filter.
			 *
			 * Make sure the GTIN product attribute is not read, which would overwrite our custom field value.
			 */
			SucomUtilOptions::set_key_value_locale_disabled( 'plugin_attr_product_gtin', '', $this->p->options );

			$this->p->util->add_plugin_filters( $this, array(
				'wc_variation_alt_options' => 1,
			) );
		}

		/*
		 * Variations use a different custom field name.
		 */
		public function filter_wc_variation_alt_options( array $opts ) {

			$opts[ 'plugin_cf_product_gtin' ] = self::$var_meta_name;

			return $opts;
		}
	}
}

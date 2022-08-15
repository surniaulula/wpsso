<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
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

			/**
			 * Product metadata (aka custom fields) are read using the 'wpsso_import_custom_fields' filter in
			 * WpssoPost->get_options().
			 */
			$this->p->options[ 'plugin_cf_product_gtin' ]          = self::$meta_name;
			$this->p->options[ 'plugin_cf_product_gtin:disabled' ] = true;

			/**
			 * Make sure the GTIN product attribute is not read, which would overwrite our custom field value.
			 */
			$this->p->options[ 'plugin_attr_product_gtin' ]          = '';
			$this->p->options[ 'plugin_attr_product_gtin:disabled' ] = true;

			$this->p->util->add_plugin_filters( $this, array(
				'wc_variation_cf_meta_keys' => 1,
			) );
		}

		/**
		 * Variations use a different custom field name.
		 */
		public function filter_wc_variation_cf_meta_keys( array $var_cf_meta_keys ) {

			$var_cf_meta_keys[ 'plugin_cf_product_gtin' ] = self::$var_meta_name;

			return $var_cf_meta_keys;
		}
	}
}

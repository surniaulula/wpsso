<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2023 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoIntegEcomAbstractWooCommerceBrands' ) ) {

	require_once WPSSO_PLUGINDIR . 'lib/integ/ecom/abstract/woocommerce-brands.php';
}

if ( ! class_exists( 'WpssoIntegEcomPerfectWooCommerceBrands' ) ) {

	class WpssoIntegEcomPerfectWooCommerceBrands extends WpssoIntegEcomAbstractWooCommerceBrands {

		protected $brand_tax_slug  = 'pwb-brand';
		protected $brand_image_key = 'pwb_brand_image';
	}
}

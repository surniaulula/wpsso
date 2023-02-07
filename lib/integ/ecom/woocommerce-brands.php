<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
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

if ( ! class_exists( 'WpssoIntegEcomWooCommerceBrands' ) ) {

	class WpssoIntegEcomWooCommerceBrands extends WpssoIntegEcomAbstractWooCommerceBrands {

		protected $brand_tax_slug  = 'product_brand';
		protected $brand_image_key = 'thumbnail_id';
	}
}

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

if ( ! class_exists( 'WpssoIntegEcomYithWooCommerceBrands' ) ) {

	class WpssoIntegEcomYithWooCommerceBrands extends WpssoIntegEcomAbstractWooCommerceBrands {

		protected $brand_tax_slug  = 'yith_product_brand';	// Default brand taxonomy.
		protected $brand_image_key = 'thumbnail_id';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * The user may select any taxonomy (like a WooCommerce product attribute) for the brand.
			 */
			if ( $tax_slug = get_option( 'yith_wcbr_brands_taxonomy' ) ) {

				$this->brand_tax_slug = $tax_slug;
			}

			/*
			 * Filter the brand taxonomy slug.
			 *
			 * See yith-woocommerce-brands-add-on/includes/class.yith-wcbr.php.
			 */
			$this->brand_tax_slug = apply_filters( 'yith_wcbr_taxonomy_slug', $this->brand_tax_slug );

			parent::__construct( $plugin );
		}
	}
}

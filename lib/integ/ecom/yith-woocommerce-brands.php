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

if ( ! class_exists( 'WpssoIntegEcomYithWooCommerceBrands' ) ) {

	class WpssoIntegEcomYithWooCommerceBrands extends WpssoIntegEcomAbstractWooCommerceBrands {

		protected $brand_tax_slug  = 'yith_product_brand';
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

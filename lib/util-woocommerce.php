<?php
/**
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 * 
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 * 
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoUtilWooCommerce' ) ) {

	class WpssoUtilWooCommerce {

		private $p;
		private $util;

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin, &$util ) {

			$this->p    =& $plugin;
			$this->util =& $util;

			if ( $this->p->debug->enabled ) {
				$this->p->debug->mark();
			}
		}

		public function get_product( $product_id ) {

			global $woocommerce;

			$product = false;

			/**
			 * WooCommerce v2 and v3.
			 */
			if ( isset( $woocommerce->product_factory ) &&
				is_callable( array( $woocommerce->product_factory, 'get_product' ) ) ) {

				$product = $woocommerce->product_factory->get_product( $product_id );

			/**
			 * WooCommerce v1.
			 */
			} elseif ( class_exists( 'WC_Product' ) ) {

				$product = new WC_Product( $product_id );

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'no method or class to get product' );
			}

			return $product;
		}

		public function get_product_id( $product ) {

			$product_id = 0;

			/**
			 * WooCommerce v3.
			 */
			if ( is_callable( array( $product, 'get_id' ) ) ) {

				$product_id = $product->get_id();

			/**
			 * WooCommerce v2.
			 */
			} elseif ( isset( $product->id ) ) {

				$product_id = $product->id;

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'no method or property to get product id' );
			}

			return $product_id;
		}

		public function get_product_type( $product ) {

			$product_type = '';

			/**
			 * WooCommerce v3.
			 */
			if ( is_callable( array( $product, 'get_type' ) ) ) {

				$product_type = $product->get_type();

			/**
			 * WooCommerce v2.
			 */
			} elseif ( isset( $product->product_type ) ) {

				$product_type = $product->product_type;

			} elseif ( $this->p->debug->enabled ) {
				$this->p->debug->log( 'no method or property to get product type' );
			}

			return $product_type;
		}

		/**
		 * Similar to the WooCommerce method, except it does not exclude out of stock variations.
		 */
		public function get_available_variations( $product ) {

			$available_variations = array();

			foreach ( $product->get_children() as $child_id ) {

				$variation = wc_get_product( $child_id );

				if ( ! $variation ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'product child id ' . $child_id . ' is empty' );
					}

					continue;

				} elseif ( ! $variation->exists() ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'product child id ' . $child_id . ' does not exist' );
					}

					continue;

				} elseif ( 'variation' !== $this->get_product_type( $variation ) ) {	// Just in case.

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'product child id ' . $child_id . ' type is not a variation' );
					}

					continue;

				} elseif ( ! $variation->variation_is_visible() ) {

					if ( $this->p->debug->enabled ) {
						$this->p->debug->log( 'product child id ' . $child_id . ' variation is not visible' );
					}

					continue;
				}

				$available_variations[] = $product->get_available_variation( $variation );
			}

			return $available_variations;
		}
	}
}

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

if ( ! class_exists( 'WpssoUtilUnits' ) ) {	// Just in case.

	require_once WPSSO_PLUGINDIR . 'lib/util-units.php';
}

if ( ! class_exists( 'WpssoUtilWooCommerce' ) ) {

	class WpssoUtilWooCommerce {

		private $p;	// Wpsso class object.

		private $fee_cost = '';	// Package cost for the [fee] shortcode.

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/*
		 * Returns true for a variable product, false for a simple product.
		 */
		public function is_mod_variable( $mod ) {

			if ( $product = $this->get_product( $mod[ 'id' ] ) ) {

				return $this->is_product_variable( $product );
			}
		}

		public function is_product_variable( $product ) {

			if ( $product instanceof WC_Product ) {	// Just in case.

				if ( 'variable' === $this->get_product_type( $product ) ) {

					return true;
				}
			}

			return false;
		}

		public function is_product( $product ) {

			if ( $product instanceof WC_Product ) {

				return true;
			}

			return false;
		}

		public function get_product( $product_id ) {

			global $woocommerce;

			$product = false;

			if ( isset( $woocommerce->product_factory ) && is_callable( array( $woocommerce->product_factory, 'get_product' ) ) ) {

				$product = $woocommerce->product_factory->get_product( $product_id );

			} elseif ( class_exists( 'WC_Product' ) ) {

				$product = new WC_Product( $product_id );

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no method or class to get product' );
			}

			return $product;
		}

		/*
		 * Returns product id from product object.
		 */
		public function get_product_id( $product ) {

			$product_id = 0;

			if ( is_callable( array( $product, 'get_id' ) ) ) {

				$product_id = $product->get_id();

			} elseif ( isset( $product->id ) ) {

				$product_id = $product->id;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no method or property to get product id' );
			}

			return $product_id;
		}

		public function get_product_type( $product ) {

			$product_type = '';

			if ( is_callable( array( $product, 'get_type' ) ) ) {

				$product_type = $product->get_type();	// Returns 'simple', 'variable', etc.

			} elseif ( isset( $product->product_type ) ) {

				$product_type = $product->product_type;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no method or property to get product type' );
			}

			return $product_type;
		}

	 	/*
		 * Format the WooCommerce meta data as WordPress meta data.
		 */
		public function get_product_wp_meta( $product ) {

			$var_wp_meta = array();

			if ( is_callable( array( $product, 'get_meta_data' ) ) ) {	// Just in case.

				$wc_var_meta = $product->get_meta_data();

				if ( is_array( $wc_var_meta ) ) {	// Just in case.

					foreach ( $wc_var_meta as $md_obj ) {	// WC_Meta_Data object.

						if ( ! empty( $md_obj->key ) ) {

							$var_wp_meta[ $md_obj->key ] = array( $md_obj->value );
						}
					}
				}
			}

			return $var_wp_meta;
		}

		/*
		 * Similar to the WooCommerce method, except it does not exclude out of stock variations.
		 *
		 * See WpssoIntegEcomWooCommerce->filter_og_seed().
		 */
		public function get_available_variations( $product ) {

			$available_variations = array();

			if ( 'variable' !== $this->get_product_type( $product ) ) {	// Nothing to do.

				return $available_variations;
			}

			$product_children = $product->get_children();

			foreach ( $product_children as $child_id ) {

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

				/*
				 * Returns an array of data for a variation.
				 *
				 * Applies the 'woocommerce_available_variation' filter.
				 */
				$available_variations[] = $product->get_available_variation( $variation );
			}

			return $available_variations;
		}

		/*
		 * Returns the variation product object or false if not a visible, active, and purchasable variation.
		 */
		public function get_variation_product( $variation ) {

			$product = false;

			if ( ! is_array( $variation ) ) {

				return false;	// Stop here.

			} elseif ( empty( $variation[ 'variation_id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation id is empty' );
				}

				return false;	// Stop here.

			} elseif ( empty( $variation[ 'variation_is_visible' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation is not visible' );
				}

				return false;	// Stop here.

			} elseif ( empty( $variation[ 'variation_is_active' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation is not active' );
				}

				return false;	// Stop here.

			} elseif ( empty( $variation[ 'is_purchasable' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation is not purchaseable' );
				}

				return false;	// Stop here.
			}

			$product = $this->get_product( $variation[ 'variation_id' ] );

			if ( false === $product ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no product for variation id ' . $variation[ 'variation_id' ] );
				}

				return false;	// Stop here.
			}

			return $product;
		}

		/*
		 * Check if a simple product, variable product or any of its variations, has a meta data value.
		 *
		 * Called by WpssoWcmdWooCommerce->filter_product_enable_dimensions_display().
		 * Called by WpssoWcmdWooCommerce->filter_display_product_attributes().
		 */
		public function has_meta( $product, $meta_key ) {

			static $local_cache = array();

			$product_id = $this->get_product_id( $product );

			if ( isset( $local_cache[ $product_id ][ $meta_key ] ) ) {	// Already checked.

				return $local_cache[ $product_id ][ $meta_key ];
			}

			$meta_val = $product->get_meta( $meta_key, $single = true );

			if ( '' !== $meta_val ) {

				return $local_cache[ $product_id ][ $meta_key ] = true;
			}

			$available_vars = $this->get_available_variations( $product );	// Always returns an array.

			foreach ( $available_vars as $num => $variation ) {

				$var_id = $variation[ 'variation_id' ];

				if ( $var_obj = $this->get_product( $var_id ) ) {

					$meta_val = $var_obj->get_meta( $meta_key, $single = true );

					if ( '' !== $meta_val ) {

						return $local_cache[ $product_id ][ $meta_key ] = true;
					}
				}
			}

			return $local_cache[ $product_id ][ $meta_key ] = false;
		}

		/*
		 * Deprecatd on 2022/12/28.
		 */
		public static function get_dimension_label( $key  = '' ) {

			_deprecated_function( __METHOD__ . '()', '2022/12/28', $replacement = 'WpssoUtilUnits::get_dimension_label()' );	// Deprecation message.

			return WpssoUtilUnits::get_dimension_label( $key );
		}

		/*
		 * Deprecatd on 2022/12/28.
		 */
		public static function get_dimension( $value, $to, $from = '' ) {

			_deprecated_function( __METHOD__ . '()', '2022/12/28', $replacement = 'WpssoUtilUnits::get_dimension_convert()' );	// Deprecation message.

			return WpssoUtilUnits::get_dimension_convert( $value, $to, $from );
		}

		/*
		 * Deprecatd on 2022/12/28.
		 */
		public static function get_fluid_volume_label( $key = '' ) {

			_deprecated_function( __METHOD__ . '()', '2022/12/28', $replacement = 'WpssoUtilUnits::get_fluid_volume_label()' );	// Deprecation message.

			return WpssoUtilUnits::get_fluid_volume_label( $key );
		}

		/*
		 * Deprecatd on 2022/12/28.
		 */
		public static function get_fluid_volume_units() {

			_deprecated_function( __METHOD__ . '()', '2022/12/28', $replacement = 'WpssoUtilUnits::get_fluid_volume_units()' );	// Deprecation message.

			return WpssoUtilUnits::get_fluid_volume_units();
		}

		/*
		 * Deprecatd on 2022/12/28.
		 */
		public static function get_fluid_volume( $value, $to, $from = '' ) {

			_deprecated_function( __METHOD__ . '()', '2022/12/28', $replacement = 'WpssoUtilUnits::get_fluid_volume_convert()' );	// Deprecation message.

			return WpssoUtilUnits::get_fluid_volume_convert( $value, $to, $from );
		}
	}
}

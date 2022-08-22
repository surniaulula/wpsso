<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoUtilWoocommerce' ) ) {

	class WpssoUtilWoocommerce {

		private $p;	// Wpsso class object.

		private $fee_cost = '';	// Package cost for the [fee] shortcode.

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public function is_mod_variable( $mod ) {

			if ( $product = $this->get_product( $mod[ 'id' ] ) ) {

				return $this->is_product_variable( $product );
			}
		}

		public function is_product_variable( $product ) {

			if ( 'variable' === $this->get_product_type( $product ) ) {

				return true;
			}
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

		/**
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

		/**
		 * Similar to the WooCommerce method, except it does not exclude out of stock variations.
		 */
		public function get_available_variations( $product ) {

			$available_variations = array();

			if ( 'variable' !== $this->get_product_type( $product ) ) {	// Nothing to do.

				return $available_variations;
			}

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

				/**
				 * Returns an array of data for a variation.
				 *
				 * Applies the 'woocommerce_available_variation' filter.
				 */
				$available_variations[] = $product->get_available_variation( $variation );
			}

			return $available_variations;
		}

		/**
		 * Returns false, or the variation product object.
		 */
		public function get_variation_product( $mixed ) {

			$product = false;

			if ( ! is_array( $mixed ) ) {

				return false;	// Stop here.

			} elseif ( empty( $mixed[ 'variation_id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation id is empty' );
				}

				return false;	// Stop here.

			} elseif ( empty( $mixed[ 'variation_is_visible' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation is not visible' );
				}

				return false;	// Stop here.

			} elseif ( empty( $mixed[ 'variation_is_active' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation is not active' );
				}

				return false;	// Stop here.

			} elseif ( empty( $mixed[ 'is_purchasable' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: variation is not purchaseable' );
				}

				return false;	// Stop here.

			}

			$product = $this->get_product( $mixed[ 'variation_id' ] );

			if ( false === $product ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: no product for variation id ' . $mixed[ 'variation_id' ] );
				}

				return false;	// Stop here.
			}

			return $product;
		}

		/**
		 * Dimensions.
		 */
		public static function get_dimension_label( $unit_text ) {

			// translators: Please ignore - translation uses a different text domain.
			return __( $unit_text, 'woocommerce' );
		}

		public static function get_dimension_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				/**
				 * From woocommerce/includes/admin/settings/class-wc-settings-products.php.
				 */
				$local_cache = array(

					// translators: Please ignore - translation uses a different text domain.
					'mm' => __( 'mm', 'woocommerce' ),	// Milimeter.

					// translators: Please ignore - translation uses a different text domain.
					'cm' => __( 'cm', 'woocommerce' ),	// Centimeter.

					// translators: Please ignore - translation uses a different text domain.
					'm'  => __( 'm', 'woocommerce' ),	// Meter.

					// translators: Please ignore - translation uses a different text domain.
					'in' => __( 'in', 'woocommerce' ),	// Inch.

					// translators: Please ignore - translation uses a different text domain.
					'yd' => __( 'yd', 'woocommerce' ),	// Yard.
				);
			}

			return $local_cache;
		}

		public static function get_dimension( $dimension, $to_unit, $from_unit = '' ) {

			return wc_get_dimension( $dimension, $to_unit, $from_unit );
		}

		/**
		 * Fluid volumes.
		 */
		public static function get_fluid_volume_label( $unit_text ) {

			$fl_vol_units = self::get_fluid_volume_units();	// Returns translated values.

			if ( isset( $fl_vol_units[ $unit_text ] ) ) {

				return $fl_vol_units[ $unit_text ];
			}

			return '';
		}

		public static function get_fluid_volume_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'ml'       => __( 'ml', 'wpsso' ),		// Millilitre.
					'cl'       => __( 'cl', 'wpsso' ),		// Centilitre.
					'l'        => __( 'l', 'wpsso' ),		// Liter.
					'kl'       => __( 'kl', 'wpsso' ),		// Kiloliter.
					'US tsp'   => __( 'US tsp', 'wpsso' ),		// US teaspoon.
					'US tbsp'  => __( 'US tbsp', 'wpsso' ),		// US tablespoon.
					'US fl oz' => __( 'US fl oz', 'wpsso' ),	// US fluid ounce.
					'US cup'   => __( 'US cup', 'wpsso' ),		// US cup.
					'US pt'    => __( 'US pt', 'wpsso' ),		// US pint.
					'US qt'    => __( 'US qt', 'wpsso' ),		// US quart.
					'US gal'   => __( 'US gal', 'wpsso' ),		// US gallon.
				);
			}

			return $local_cache;
		}

		public static function get_fluid_volume( $volume, $to_unit, $from_unit = '' ) {

			$to_unit = strtolower( $to_unit );

			if ( empty( $from_unit ) ) {

				$from_unit = strtolower( get_option( 'woocommerce_fluid_volume_unit', $default = 'ml' ) );
			}

			if ( $from_unit !== $to_unit ) {

				/**
				 * Convert any volume to 'ml' first.
				 */
				switch ( $from_unit ) {

					/**
					 * Metric units.
					 */
					case 'ml':		// Millilitre.

						$volume *= 1;

						break;

					case 'cl':		// Centilitre.

						$volume *= 10;

						break;

					case 'l':		// Liter.

						$volume *= 1000;

						break;

					case 'kl':		// Kiloliter.

						$volume *= 1000000;

						break;

					/**
					 * Imperial units.
					 */
					case 'US tsp':		// US teaspoon.

						$volume *= 4.92892;

						break;

					case 'US tbsp':		// US tablespoon.

						$volume *= 14.7868;

						break;

					case 'US fl oz':	// US fluid oz.

						$volume *= 29.5735;

						break;

					case 'US cup':		// US cup.

						$volume *= 236.588;

						break;

					case 'US pt':		// US pint.

						$volume *= 473.176;

						break;

					case 'US qt':		// US quart.

						$volume *= 946.353;

						break;

					case 'US gal':		// US gallon.

						$volume *= 3785.41;

						break;
				}

				/**
				 * Convert volume from ml to desired output.
				 */
				switch ( $to_unit ) {

					/**
					 * Metric units.
					 */
					case 'ml':		// Millilitre.

						$volume *= 1;

						break;

					case 'cl':		// Centilitre.

						$volume *= 0.1;

						break;

					case 'l':		// Liter.

						$volume *= 0.001;

						break;

					case 'kl':		// Kiloliter.

						$volume *= 0.000001;

						break;

					/**
					 * Imperial units.
					 */
					case 'US tsp':		// US teaspoon.

						$volume *= 0.202884;

						break;

					case 'US tbsp':		// US tablespoon.

						$volume *= 0.067628;

						break;

					case 'US fl oz':	// US fluid oz.

						$volume *= 0.033814;

						break;

					case 'US cup':		// US cup.

						$volume *= 0.00422675;

						break;

					case 'US pt':		// US pint.

						$volume *= 0.00211338;

						break;

					case 'US qt':		// US quart.

						$volume *= 0.00105669;

						break;

					case 'US gal':		// US gallon.

						$volume *= 0.000264172;

						break;
				}
			}

			return ( $volume < 0 ) ? 0 : $volume;
		}

		/**
		 * Weight.
		 */
		public static function get_weight_label( $unit_text ) {

			// translators: Please ignore - translation uses a different text domain.
			return __( $unit_text, 'woocommerce' );
		}

		public static function get_weight_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				/**
				 * From woocommerce/includes/admin/settings/class-wc-settings-products.php.
				 */
				$local_cache = array(

					// translators: Please ignore - translation uses a different text domain.
					'g'   => __( 'g', 'woocommerce' ),	// Gram.

					// translators: Please ignore - translation uses a different text domain.
					'kg'  => __( 'kg', 'woocommerce' ),	// Kilogram.

					// translators: Please ignore - translation uses a different text domain.
					'oz'  => __( 'oz', 'woocommerce' ),	// Ounce.

					// translators: Please ignore - translation uses a different text domain.
					'lbs' => __( 'lbs', 'woocommerce' ),	// Pound.
				);
			}

			return $local_cache;
		}

		public static function get_weight( $weight, $to_unit, $from_unit = '' ) {

			return wc_get_weight( $weight, $to_unit, $from_unit );
		}

		/**
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
	}
}

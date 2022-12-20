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

if ( ! class_exists( 'WpssoUtilUnits' ) ) {

	class WpssoUtilUnits {

		private $p;	// Wpsso class object.

		/**
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		/**
		 * Dimensions.
		 */
		public static function get_dimension_label( $key ) {

			$units = self::get_dimension_units();	// Returns translated labels.

			return isset( $units[ $key ] ) ? $units[ $key ] : '';
		}

		public static function get_dimension_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'mm' => __( 'mm', 'wpsso' ),	// Millimeter.
					'cm' => __( 'cm', 'wpsso' ),	// Centimeter.
					'm'  => __( 'm', 'wpsso' ),	// Meter.
					'km' => __( 'km', 'wpsso' ),	// Kilometer.
					'in' => __( 'in', 'wpsso' ),	// Inch.
					'ft' => __( 'ft', 'wpsso' ),	// Foot.
					'yd' => __( 'yd', 'wpsso' ),	// Yard.
					'mi' => __( 'mi', 'wpsso' ),	// Mile.
				);
			}

			return $local_cache;
		}

		public static function get_dimension( $dimension, $to, $from ) {

			$dimension = (float) $dimension;

			if ( $from !== $to ) {	// Just in case.

				/**
				 * Convert dimension to cm first.
				 */
				switch ( $from ) {

					/**
					 * Metric units.
					 */
					case 'mm':	$dimension *= 0.1; break;	// Millimeter to Centimeter.
					case 'cm':	$dimension *= 1; break;		// Centimeter to Centimeter.
					case 'm':	$dimension *= 100; break;	// Meter to Centimeter.
					case 'km':	$dimension *= 100000; break;	// Kilometer to Centimeter.

					/**
					 * Imperial units.
					 */
					case 'in':	$dimension *= 2.54; break;	// Inch to Centimeter.
					case 'ft':	$dimension *= 30.48; break;	// Foot to Centimeter.
					case 'yd':	$dimension *= 91.44; break;	// Yard to Centimeter.
					case 'mi':	$dimension *= 160934.4; break;	// Mile to Centimeter.
				}
		
				/**
				 * Convert dimension from cm to desired output.
				 */
				switch ( $to ) {

					/**
					 * Metric units.
					 */
					case 'mm':	$dimension *= 10; break;	// Centimeter to Millimeter.
					case 'cm':	$dimension *= 1; break; 	// Centimeter to Centimeter.
					case 'm':	$dimension *= 0.01; break; 	// Centimeter to Meter.
					case 'km':	$dimension *= 0.00001; break;	// Centimeter to Kilometer.

					/**
					 * Imperial units.
					 */
					case 'in':	$dimension *= 0.3937007874; break;		// Centimeter to Inch.
					case 'ft':	$dimension *= 0.03280839895; break; 		// Centimeter to Foot.
					case 'yd':	$dimension *= 0.010936132983; break; 		// Centimeter to Yard.
					case 'mi':	$dimension *= 0.0000062137119224; break;	// Centimeter to Mile.
				}
			}
		
			return ( $dimension < 0 ) ? 0 : $dimension;
		}

		/**
		 * Fluid volumes.
		 */
		public static function get_fluid_volume_label( $key ) {

			$units = self::get_fluid_volume_units();	// Returns translated labels.

			return isset( $units[ $key ] ) ? $units[ $key ] : '';
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

		public static function get_fluid_volume( $volume, $to, $from ) {

			$volume = (float) $volume;

			if ( $from !== $to ) {	// Just in case.

				/**
				 * Convert volume to ml first.
				 */
				switch ( $from ) {

					/**
					 * Metric units.
					 */
					case 'ml':	$volume *= 1; break; 		// Millilitre to Millilitre.
					case 'cl':	$volume *= 10; break; 		// Centilitre to Millilitre.
					case 'l':	$volume *= 1000; break; 	// Liter to Millilitre.
					case 'kl':	$volume *= 1000000; break;	// Kiloliter to Millilitre.

					/**
					 * Imperial units.
					 */
					case 'US tsp':		$volume *= 4.92892; break;	// US teaspoon to Millilitre.
					case 'US tbsp':		$volume *= 14.7868; break; 	// US tablespoon to Millilitre.
					case 'US fl oz':	$volume *= 29.5735; break; 	// US fluid ounce to Millilitre.
					case 'US cup':		$volume *= 236.588; break; 	// US cup to Millilitre.
					case 'US pt':		$volume *= 473.176; break; 	// US pint to Millilitre.
					case 'US qt':		$volume *= 946.353; break; 	// US quart to Millilitre.
					case 'US gal':		$volume *= 3785.41; break;	// US gallon to Millilitre.
				}

				/**
				 * Convert volume from ml to desired output.
				 */
				switch ( $to ) {

					/**
					 * Metric units.
					 */
					case 'ml':	$volume *= 1; break; 		// Millilitre to Millilitre.
					case 'cl':	$volume *= 0.1; break; 		// Millilitre to Centilitre.
					case 'l':	$volume *= 0.001; break; 	// Millilitre to Liter.
					case 'kl':	$volume *= 0.000001; break;	// Millilitre to Kiloliter.

					/**
					 * Imperial units.
					 */
					case 'US tsp':		$volume *= 0.202884; break; 	// Millilitre to US teaspoon.
					case 'US tbsp':		$volume *= 0.067628; break; 	// Millilitre to US tablespoon.
					case 'US fl oz':	$volume *= 0.033814; break; 	// Millilitre to US fluid ounce.
					case 'US cup':		$volume *= 0.00422675; break; 	// Millilitre to US cup.
					case 'US pt':		$volume *= 0.00211338; break; 	// Millilitre to US pint.
					case 'US qt':		$volume *= 0.00105669; break; 	// Millilitre to US quart.
					case 'US gal':		$volume *= 0.000264172; break;	// Millilitre to US gallon.
				}
			}

			return ( $volume < 0 ) ? 0 : $volume;
		}

		/**
		 * Weight.
		 */
		public static function get_weight_label( $key ) {

			$units = self::get_weight_units();	// Returns translated labels.

			return isset( $units[ $key ] ) ? $units[ $key ] : '';
		}

		public static function get_weight_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'mg'  => __( 'mg', 'wpsso' ),	// Milligram.
					'g'   => __( 'g', 'wpsso' ),	// Gram.
					'kg'  => __( 'kg', 'wpsso' ),	// Kilogram.
					't'   => __( 't', 'wpsso' ),	// Metric Ton.
					'oz'  => __( 'oz', 'wpsso' ),	// Ounce.
					'lb'  => __( 'lb', 'wpsso' ),	// Pound.
					'lbs' => __( 'lbs', 'wpsso' ),	// Pound.
					'st'  => __( 'st', 'wpsso' ),	// Stone.
				);
			}

			return $local_cache;
		}

		public static function get_weight( $weight, $to, $from ) {

			$weight = (float) $weight;
		
			if ( $from !== $to ) {

				/**
				 * Convert weight to kg first.
				 */
				switch ( $from ) {

					/**
					 * Metric units.
					 */
					case 'mg':	$weight *= 0.000001; break;	// Milligram to Kilogram.
					case 'g':	$weight *= 0.001; break;	// Gram to Kilogram.
					case 'kg':	$weight *= 1; break;		// Kilogram to Kilogram.
					case 't':	$weight *= 1000; break;		// Metric Ton to Kilogram.

					/**
					 * Imperial units.
					 */
					case 'oz':	$weight *= 0.02834952; break;	// Ounce to Kilogram.
					case 'lb':	$weight *= 0.4535924; break;	// Pound to Kilogram.
					case 'lbs':	$weight *= 0.4535924; break;	// Pound to Kilogram.
					case 'st':	$weight *= 6.350293; break;	// Stone to Kilogram.
				}
		
				/**
				 * Convert weight from kg to desired output.
				 */
				switch ( $to ) {

					/**
					 * Metric units.
					 */
					case 'mg':	$weight *= 1000000; break;	// Kilogram to Milligram.
					case 'g':	$weight *= 1000; break;		// Kilogram to Gram.
					case 'kg':	$weight *= 1; break;		// Kilogram to Kilogram.
					case 't':	$weight *= 0.001; break;	// Kilogram to Metric Ton.

					/**
					 * Imperial units.
					 */
					case 'oz':	$weight *= 35.27396; break;	// Kilogram to Ounce.
					case 'lb':	$weight *= 2.204623; break;	// Kilogram to Pound.
					case 'lbs':	$weight *= 2.204623; break;	// Kilogram to Pound.
					case 'st':	$weight *= 0.157473; break;	// Kilogram to Stone.
				}
			}
		
			return ( $weight < 0 ) ? 0 : $weight;
		}
	}
}

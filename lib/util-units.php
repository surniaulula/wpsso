<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
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

		/*
		 * Instantiated by WpssoUtil->__construct().
		 */
		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}
		}

		public static function get_convert( $value, $to, $from = '' ) {

			$to   = 'lbs' === $to   ? 'lb' : $to;	// WooCommerce uses 'lbs' and WPSSO uses 'lb'.
			$from = 'lbs' === $from ? 'lb' : $from;	// WooCommerce uses 'lbs' and WPSSO uses 'lb'.

			$dimension_units = self::get_dimension_units();		// Uses a local cache.
			$fl_volume_units = self::get_fluid_volume_units();	// Uses a local cache.
			$weight_units    = self::get_weight_units();		// Uses a local cache.

			if ( isset( $dimension_units[ $to ] ) ) {

				if ( '' === $from || isset( $dimension_units[ $from ] ) ) {

					return self::get_dimension_convert( $value, $to, $from );
				}

			} elseif ( isset( $fl_volume_units[ $to ] ) ) {

				if ( '' === $from || isset( $fluid_value_units[ $from ] ) ) {

					return self::get_fluid_value_convert( $value, $to, $from );
				}

			} elseif ( isset( $weight_units[ $to ] ) ) {

				if ( '' === $from || isset( $weight_units[ $from ] ) ) {

					return self::get_weight_convert( $value, $to, $from );
				}
			}

			return false;
		}

		public static function get_mixed_text( $mixed_key ) {

			$type = self::get_mixed_type( $mixed_key );

			switch( $type ) {

				case 'dimension':
				case 'length':
				case 'width':
				case 'height':

					return self::get_dimension_text();

				case 'fluid_volume':

					return self::get_fluid_volume_text();

				case 'weight':

					return self::get_weight_text();
			}

			return '';
		}

		public static function get_mixed_label( $mixed_key ) {

			$type = self::get_mixed_type( $mixed_key );

			switch( $type ) {

				case 'dimension':
				case 'length':
				case 'width':
				case 'height':

					return self::get_dimension_label();

				case 'fluid_volume':

					return self::get_fluid_volume_label();

				case 'weight':

					return self::get_weight_label();
			}

			return '';
		}

		public static function get_mixed_type( $mixed_key ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $mixed_key ] ) ) {

				return $local_cache[ $mixed_key ];
			}

			$count     = null;
			$match_key = str_replace( ':', '_', $mixed_key );	// Fix for meta tag names.
			$match_key = preg_replace( '/_(value|units)$/', '_', $match_key, $limit = -1, $count );

			switch( $match_key ) {

				case 'dimension':
				case 'length':
				case 'width':
				case 'height':
				case ( false !== strpos( $match_key, '_length_' ) ? true : false );
				case ( false !== strpos( $match_key, '_width_' ) ? true : false );
				case ( false !== strpos( $match_key, '_height_' ) ? true : false );

					return $local_cache[ $mixed_key ] = 'dimension';

				case 'fluid_volume':
				case ( false !== strpos( $match_key, '_fluid_volume_' ) ? true : false );

					return $local_cache[ $mixed_key ] = 'fluid_volume';

				case 'weight':
				case ( false !== strpos( $match_key, '_weight_' ) ? true : false );

					return $local_cache[ $mixed_key ] = 'weight';
			}

			return $local_cache[ $mixed_key ] = '';
		}

		/*
		 * Dimensions.
		 *
		 * See https://support.google.com/merchants/answer/11018531.
		 */
		public static function get_dimension_text() {

			$wpsso =& Wpsso::get_instance();

			return isset( $wpsso->options[ 'og_def_dimension_units' ] ) ? $wpsso->options[ 'og_def_dimension_units' ] : 'cm';
		}

		public static function get_dimension_label( $key  = '' ) {

			$key   = empty( $key ) ? self::get_dimension_text() : $key;
			$units = self::get_dimension_units();	// Returns translated labels.

			return isset( $units[ $key ] ) ? $units[ $key ] : '';
		}

		/*
		 * See wc_get_dimension() from woocommerce/includes/wc-formatting-functions.php.
		 */
		public static function get_dimension_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'mm' => _x( 'mm', 'option value', 'wpsso' ),		// Millimeter.
					'cm' => _x( 'cm', 'option value', 'wpsso' ),		// Centimeter.
					'm'  => _x( 'm', 'option value', 'wpsso' ),		// Meter.
					'in' => _x( 'in', 'option value', 'wpsso' ),	// Inch.
					'ft' => _x( 'ft', 'option value', 'wpsso' ),		// Foot.
					'yd' => _x( 'yd', 'option value', 'wpsso' ),		// Yard.
				);

				$local_cache = apply_filters( 'wpsso_dimension_units', $local_cache );
			}

			return $local_cache;
		}

		public static function get_dimension_convert( $value, $to, $from = '' ) {

			$value = (float) $value;
			$from  = empty( $from ) ? self::get_dimension_text() : $from;

			if ( $from !== $to ) {	// Just in case.

				/*
				 * Convert dimension to cm first.
				 */
				switch ( $from ) {

					/*
					 * Metric units.
					 */
					case 'mm':	$value *= 0.1; break;		// Millimeter to Centimeter.
					case 'cm':	$value *= 1; break;		// Centimeter to Centimeter.
					case 'm':	$value *= 100; break;		// Meter to Centimeter.
					case 'km':	$value *= 100000; break;	// Kilometer to Centimeter.

					/*
					 * Imperial units.
					 */
					case 'in':	$value *= 2.54; break;		// Inch to Centimeter.
					case 'ft':	$value *= 30.48; break;		// Foot to Centimeter.
					case 'yd':	$value *= 91.44; break;		// Yard to Centimeter.
					case 'mi':	$value *= 160934.4; break;	// Mile to Centimeter.
				}

				/*
				 * Convert dimension from cm to desired output.
				 */
				switch ( $to ) {

					/*
					 * Metric units.
					 */
					case 'mm':	$value *= 10; break;		// Centimeter to Millimeter.
					case 'cm':	$value *= 1; break; 		// Centimeter to Centimeter.
					case 'm':	$value *= 0.01; break; 		// Centimeter to Meter.
					case 'km':	$value *= 0.00001; break;	// Centimeter to Kilometer.

					/*
					 * Imperial units.
					 */
					case 'in':	$value *= 0.3937007874; break;		// Centimeter to Inch.
					case 'ft':	$value *= 0.03280839895; break; 	// Centimeter to Foot.
					case 'yd':	$value *= 0.010936132983; break; 	// Centimeter to Yard.
					case 'mi':	$value *= 0.0000062137119224; break;	// Centimeter to Mile.
				}
			}

			return ( $value < 0 ) ? 0 : $value;
		}

		/*
		 * Fluid volumes.
		 */
		public static function get_fluid_volume_text() {

			$wpsso =& Wpsso::get_instance();

			return isset( $wpsso->options[ 'og_def_fluid_volume_units' ] ) ? $wpsso->options[ 'og_def_fluid_volume_units' ] : 'cm';
		}

		public static function get_fluid_volume_label( $key = '' ) {

			$key   = empty( $key ) ? self::get_fluid_volume_text() : $key;
			$units = self::get_fluid_volume_units();	// Returns translated labels.

			return isset( $units[ $key ] ) ? $units[ $key ] : '';
		}

		public static function get_fluid_volume_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'ml'       => _x( 'ml', 'option value', 'wpsso' ),		// Millilitre.
					'cl'       => _x( 'cl', 'option value', 'wpsso' ),		// Centilitre.
					'l'        => _x( 'l', 'option value', 'wpsso' ),		// Liter.
					'US tsp'   => _x( 'US tsp', 'option value', 'wpsso' ),		// US teaspoon.
					'US tbsp'  => _x( 'US tbsp', 'option value', 'wpsso' ),		// US tablespoon.
					'US fl oz' => _x( 'US fl oz', 'option value', 'wpsso' ),	// US fluid ounce.
					'US cup'   => _x( 'US cup', 'option value', 'wpsso' ),		// US cup.
					'US pt'    => _x( 'US pt', 'option value', 'wpsso' ),		// US pint.
					'US qt'    => _x( 'US qt', 'option value', 'wpsso' ),		// US quart.
					'US gal'   => _x( 'US gal', 'option value', 'wpsso' ),		// US gallon.
				);

				$local_cache = apply_filters( 'wpsso_fluid_volume_units', $local_cache );
			}

			return $local_cache;
		}

		public static function get_fluid_volume_convert( $value, $to, $from = '' ) {

			$value = (float) $value;
			$from  = empty( $from ) ? self::get_fluid_volume_text() : $from;

			if ( $from !== $to ) {	// Just in case.

				/*
				 * Convert volume to ml first.
				 */
				switch ( $from ) {

					/*
					 * Metric units.
					 */
					case 'ml':	$value *= 1; break; 		// Millilitre to Millilitre.
					case 'cl':	$value *= 10; break; 		// Centilitre to Millilitre.
					case 'l':	$value *= 1000; break; 		// Liter to Millilitre.
					case 'kl':	$value *= 1000000; break;	// Kiloliter to Millilitre.

					/*
					 * Imperial units.
					 */
					case 'US tsp':		$value *= 4.92892; break;	// US teaspoon to Millilitre.
					case 'US tbsp':		$value *= 14.7868; break; 	// US tablespoon to Millilitre.
					case 'US fl oz':	$value *= 29.5735; break; 	// US fluid ounce to Millilitre.
					case 'US cup':		$value *= 236.588; break; 	// US cup to Millilitre.
					case 'US pt':		$value *= 473.176; break; 	// US pint to Millilitre.
					case 'US qt':		$value *= 946.353; break; 	// US quart to Millilitre.
					case 'US gal':		$value *= 3785.41; break;	// US gallon to Millilitre.
				}

				/*
				 * Convert volume from ml to desired output.
				 */
				switch ( $to ) {

					/*
					 * Metric units.
					 */
					case 'ml':	$value *= 1; break; 		// Millilitre to Millilitre.
					case 'cl':	$value *= 0.1; break; 		// Millilitre to Centilitre.
					case 'l':	$value *= 0.001; break; 	// Millilitre to Liter.
					case 'kl':	$value *= 0.000001; break;	// Millilitre to Kiloliter.

					/*
					 * Imperial units.
					 */
					case 'US tsp':		$value *= 0.202884; break; 	// Millilitre to US teaspoon.
					case 'US tbsp':		$value *= 0.067628; break; 	// Millilitre to US tablespoon.
					case 'US fl oz':	$value *= 0.033814; break; 	// Millilitre to US fluid ounce.
					case 'US cup':		$value *= 0.00422675; break; 	// Millilitre to US cup.
					case 'US pt':		$value *= 0.00211338; break; 	// Millilitre to US pint.
					case 'US qt':		$value *= 0.00105669; break; 	// Millilitre to US quart.
					case 'US gal':		$value *= 0.000264172; break;	// Millilitre to US gallon.
				}
			}

			return ( $value < 0 ) ? 0 : $value;
		}

		/*
		 * Weight.
		 *
		 * See https://support.google.com/merchants/answer/11018531.
		 */
		public static function get_weight_text() {

			$wpsso =& Wpsso::get_instance();

			$key = isset( $wpsso->options[ 'og_def_weight_units' ] ) ? $wpsso->options[ 'og_def_weight_units' ] : 'cm';

			$key = 'lbs' === $key ? 'lb' : $key;	// WooCommerce uses 'lbs' and WPSSO uses 'lb'.

			return $key;
		}

		public static function get_weight_label( $key = '' ) {

			$key   = 'lbs' === $key ? 'lb' : $key;	// WooCommerce uses 'lbs' and WPSSO uses 'lb'.
			$key   = empty( $key ) ? self::get_weight_text() : $key;
			$units = self::get_weight_units();	// Returns translated labels.

			return isset( $units[ $key ] ) ? $units[ $key ] : '';
		}

		/*
		 * See wc_get_weight() from woocommerce/includes/wc-formatting-functions.php.
		 */
		public static function get_weight_units() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				$local_cache = array(
					'mg'  => _x( 'mg', 'option value', 'wpsso' ),	// Milligram.
					'g'   => _x( 'g', 'option value', 'wpsso' ),	// Gram.
					'kg'  => _x( 'kg', 'option value', 'wpsso' ),	// Kilogram.
					'oz'  => _x( 'oz', 'option value', 'wpsso' ),	// Ounce.
					'lb'  => _x( 'lbs', 'option value', 'wpsso' ),	// Pound.
					'st'  => _x( 'st', 'option value', 'wpsso' ),	// Stone.
				);

				$local_cache = apply_filters( 'wpsso_weight_units', $local_cache );
			}

			return $local_cache;
		}

		public static function get_weight_convert( $value, $to, $from = '' ) {

			$value = (float) $value;
			$to    = 'lbs' === $to   ? 'lb' : $to;		// WooCommerce uses 'lbs' and WPSSO uses 'lb'.
			$from  = 'lbs' === $from ? 'lb' : $from;	// WooCommerce uses 'lbs' and WPSSO uses 'lb'.
			$from  = empty( $from ) ? self::get_weight_text() : $from;

			if ( $from !== $to ) {

				/*
				 * Convert weight to kg first.
				 */
				switch ( $from ) {

					/*
					 * Metric units.
					 */
					case 'mg':	$value *= 0.000001; break;	// Milligram to Kilogram.
					case 'g':	$value *= 0.001; break;		// Gram to Kilogram.
					case 'kg':	$value *= 1; break;		// Kilogram to Kilogram.
					case 't':	$value *= 1000; break;		// Metric Ton to Kilogram.

					/*
					 * Imperial units.
					 */
					case 'oz':	$value *= 0.02834952; break;	// Ounce to Kilogram.
					case 'lb':	$value *= 0.4535924; break;	// Pound to Kilogram.
					case 'st':	$value *= 6.350293; break;	// Stone to Kilogram.
				}

				/*
				 * Convert weight from kg to desired output.
				 */
				switch ( $to ) {

					/*
					 * Metric units.
					 */
					case 'mg':	$value *= 1000000; break;	// Kilogram to Milligram.
					case 'g':	$value *= 1000; break;		// Kilogram to Gram.
					case 'kg':	$value *= 1; break;		// Kilogram to Kilogram.
					case 't':	$value *= 0.001; break;		// Kilogram to Metric Ton.

					/*
					 * Imperial units.
					 */
					case 'oz':	$value *= 35.27396; break;	// Kilogram to Ounce.
					case 'lb':	$value *= 2.204623; break;	// Kilogram to Pound.
					case 'st':	$value *= 0.157473; break;	// Kilogram to Stone.
				}
			}

			return ( $value < 0 ) ? 0 : $value;
		}
	}
}

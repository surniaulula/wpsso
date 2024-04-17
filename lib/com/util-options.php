<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtil' ) ) {

	require_once dirname( __FILE__ ) . '/util.php';
}

If ( ! class_exists( 'SucomUtilOptions' ) ) {

	class SucomUtilOptions extends SucomUtil {

		public function __construct() {}

		/*
		 * OPTIONS ARRAY METHODS:
		 *
		 *	get_opts_begin()
		 *	get_opts_hm_tz()
		 *	get_opts_labels_transl()
		 *	get_opts_values_transl()
		 *	get_key_locale()
		 *	get_key_value()
		 *	get_key_values_multi()
		 *	set_key_value()
		 *	set_key_value_disabled()
		 *	set_key_value_locale()
		 *	set_key_value_locale_disabled()
		 *	transl_key_values()
		 */
		public static function get_opts_begin( $opts, $str ) {	// Do not cast $opts as an array.

			if ( ! is_array( $opts ) && is_array( $str ) ) {	// Backwards compatibility.

				$arr = $str; $str = $opts; $opts = $arr ; unset( $arr );
			}

			$found = array();

			foreach ( $opts as $key => $value ) {

				if ( 0 === strpos( $key, $str ) ) {

					$found[ $key ] = $value;
				}
			}

			return $found;
		}

		public static function get_opts_hm_tz( array $opts, $key_hm, $key_tz = '' ) {

			if ( ! empty( $opts[ $key_hm ] ) ) {

				$timezone  = empty( $key_tz ) || empty( $opts[ $key_tz ] ) ? self::get_default_timezone() : $opts[ $key_tz ];

				$tz_offset = self::get_timezone_offset_hours( $timezone );

				return $opts[ $key_hm ] . ':00' . $tz_offset;
			}

			return false;
		}

		public static function get_opts_labels_transl( array $opts, $text_domain ) {

			foreach ( $opts as $opt_key => &$opt_label ) {

				/*
				 * The static value of array option labels is pre-defined in a different PHP file, like
				 * gettext/gettext-lib-config.php for example.
				 */
				$opt_label = _x( $opt_label, 'option label', $text_domain );
			}

			self::natasort( $opts );

			return $opts;
		}

		public static function get_opts_values_transl( array $opts, $text_domain ) {

			foreach ( $opts as $opt_key => &$opt_label ) {

				/*
				 * The static value of array option labels is pre-defined in a different PHP file, like
				 * gettext/gettext-lib-config.php for example.
				 */
				$opt_label = _x( $opt_label, 'option value', $text_domain );
			}

			return $opts;
		}

		/*
		 * Localize an options array key.
		 *
		 * $opts = false | array
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_key_locale( $key, $opts = false, $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilWP' ) ) {

				require_once dirname( __FILE__ ) . '/util-wp.php';
			}

			if ( false !== ( $pos = strpos( $key, '#' ) ) ) {	// Maybe remove pre-existing locale.

				$key = substr( $key, 0, $pos );
			}

			$locale     = SucomUtilWP::get_locale( $mixed );	// Uses a local cache.
			$def_locale = SucomUtilWP::get_locale( 'default' );	// Uses a local cache.
			$key_locale = $key . '#' . $locale;			// Maybe add the current or default locale.

			if ( $locale === $def_locale ) {

				/*
				 * The default language for the WordPress site may have changed in the past, so if we're using the
				 * default, check for a locale version of the default language.
				 */
				if ( isset( $opts[ $key_locale ] ) ) {

					return $key_locale;
				}

				return $key;
			}

			return $key_locale;
		}

		/*
		 * Returns a localized option value or null.
		 *
		 * Note that for non-existing keys or empty value strings, this methods returns the default non-localized value.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_key_value( $key, array $opts, $mixed = 'current' ) {

			$key_locale = self::get_key_locale( $key, $opts, $mixed );

			$value = isset( $opts[ $key_locale ] ) ? $opts[ $key_locale ] : null;	// Null if key does not exist.

			if ( null === $value || '' === $value ) {	// Maybe fallback to the default non-localized value.

				if ( false === strpos( $key_locale, '#' ) ) {	// The option key not localized, return null or empty string.

					return $value;
				}

				$key_default = self::get_key_locale( $key_locale, $opts, 'default' );

				if ( $key_locale !== $key_default ) {	// The option key is localized and it's not the default locale.

					/*
					 * If the $key_locale value is an empty string, and $key_default does not exist, then
					 * return the emty string.
					 */
					return isset( $opts[ $key_default ] ) ? $opts[ $key_default ] : $value;
				}
			}

			return $value;
		}

		public static function get_key_values_multi( $prefix, array &$opts, $add_none = false ) {

			if ( ! class_exists( 'SucomUtilWP' ) ) {

				require_once dirname( __FILE__ ) . '/util-wp.php';
			}

			$current    = SucomUtilWP::get_locale();		// Uses a local cache.
			$def_locale = SucomUtilWP::get_locale( 'default' );	// Uses a local cache.
			$matches    = self::preg_grep_keys( '/^' . $prefix . '_([0-9]+)(#.*)?$/', $opts );
			$results    = array();

			foreach ( $matches as $key => $value ) {

				$num = preg_replace( '/^' . $prefix . '_([0-9]+)(#.*)?$/', '$1', $key );

				if ( ! empty( $results[ $num ] ) ) {	// Preserve the first non-blank value.

					continue;

				} elseif ( ! empty( $opts[ $prefix . '_' . $num . '#' . $current ] ) ) {	// Current locale.

					$results[ $num ] = $opts[ $prefix . '_' . $num . '#' . $current ];

				} elseif ( ! empty( $opts[ $prefix . '_' . $num . '#' . $def_locale ] ) ) {	// Default locale.

					$results[ $num ] = $opts[ $prefix . '_' . $num . '#' . $def_locale ];

				} elseif ( ! empty( $opts[ $prefix . '_' . $num ] ) ) {	// No locale.

					$results[ $num ] = $opts[ $prefix . '_' . $num ];

				} else {	// Use value (could be empty).

					$results[ $num ] = $value;
				}
			}

			asort( $results );	// Sort values for display.

			if ( $add_none ) {

				/*
				 * The union operator (+) gives priority to values in the first array, while array_replace() gives
				 * priority to values in the the second array.
				 */
				$results = array( 'none' => 'none' ) + $results;	// Maintains numeric index.
			}

			return $results;
		}

		public static function set_key_value( $key, $value, array &$opts ) {

			$opts[ $key ] = $value;
		}

		public static function set_key_value_disabled( $key, $value, array &$opts ) {

			$opts[ $key ] = $value;

			$opts[ $key . ':disabled' ] = true;
		}

		public static function set_key_value_locale( $key, $value, array &$opts, $mixed = 'current' ) {

			$key_locale = self::get_key_locale( $key, $opts, $mixed );

			$opts[ $key_locale ] = $value;
		}

		public static function set_key_value_locale_disabled( $key, $value, array &$opts, $mixed = 'current' ) {

			$key_locale = self::get_key_locale( $key, $opts, $mixed );

			$opts[ $key_locale ] = $value;

			$opts[ $key_locale . ':disabled' ] = true;
		}

		public static function transl_key_values( $pattern, array &$opts, $text_domain ) {

			foreach ( self::preg_grep_keys( $pattern, $opts ) as $key => $val ) {

				$locale_key = self::get_key_locale( $key );

				if ( $locale_key !== $key && empty( $opts[ $locale_key ] ) ) {

					/*
					 * The static value of array option labels is pre-defined in a different PHP file, like
					 * gettext/gettext-lib-config.php for example.
					 */
					$val_transl = _x( $val, 'option value', $text_domain );

					if ( $val_transl !== $val ) {

						$opts[ $locale_key ] = $val_transl;
					}
				}
			}
		}
	}
}

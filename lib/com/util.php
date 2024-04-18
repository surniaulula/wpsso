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

	class SucomUtil {

		protected static $url_clean_query = array(

			// Age Verify plugin:
			'age-verified',

			// Autoptimize:
			'ao_noptimize',

			// AMP:
			'usqp',

			// Cookie Notice:
			'cn-reloaded',

			// ShareASale:
			'sscid',

			// Adobe Advertising Cloud:
			'ef_id',

			// Adobe Analytics:
			's_kwcid',

			// Bronto:
			'_bta_tid',
			'_bta_c',

			// Dotdigital:
			'dm_i',

			// Facebook:
			'fb_action_ids',
			'fb_action_types',
			'fb_source',
			'fbclid',

			// Google Analytics and Ads:
			'utm_source',
			'utm_campaign',
			'utm_medium',
			'utm_expid',
			'utm_term',
			'utm_content',
			'_ga',
			'gclid',
			'campaignid',
			'adgroupid',
			'adid',
			'gbraid',
			'wbraid',

			// Google Web Stories:
			'_gl',

			// Google DoubleClick:
			'gclsrc',

			// GoDataFeed:
			'gdfms',
			'gdftrk',
			'gdffi',

			// Klaviyo
			'_ke',

			// Listrak:
			'trk_contact',
			'trk_msg',
			'trk_module',
			'trk_sid',

			// Mailchimp:
			'mc_cid',
			'mc_eid',

			// Marin:
			'mkwid',
			'pcrid',

			// Matomo:
			'mtm_source',
			'mtm_medium',
			'mtm_campaign',
			'mtm_keyword',
			'mtm_cid',
			'mtm_content',

			// Microsoft Advertising:
			'msclkid',

			// Pinterest:
			'epik',
			'pp',

			// Piwik Pro:
			'pk_source',
			'pk_medium',
			'pk_campaign',
			'pk_keyword',
			'pk_cid',
			'pk_content',

			// Springbot:
			'redirect_log_mongo_id',
			'redirect_mongo_id',
			'sb_referer_host',
		);

		protected static $publisher_languages = array(

			/*
			 * https://developers.facebook.com/docs/messenger-platform/messenger-profile/supported-locales
			 */
			'facebook' => array(
				'af_ZA' => 'Afrikaans',
				'ar_AR' => 'Arabic',
				'as_IN' => 'Assamese',
				'az_AZ' => 'Azerbaijani',
				'be_BY' => 'Belarusian',
				'bg_BG' => 'Bulgarian',
				'bn_IN' => 'Bengali',
				'br_FR' => 'Breton',
				'bs_BA' => 'Bosnian',
				'ca_ES' => 'Catalan',
				'cb_IQ' => 'Sorani Kurdish',
				'co_FR' => 'Corsican',
				'cs_CZ' => 'Czech',
				'cx_PH' => 'Cebuano',
				'cy_GB' => 'Welsh',
				'da_DK' => 'Danish',
				'de_DE' => 'German',
				'el_GR' => 'Greek',
				'en_GB' => 'English (UK)',
				'en_UD' => 'English (Upside Down)',
				'en_US' => 'English (US)',
				'es_ES' => 'Spanish (Spain)',
				'es_LA' => 'Spanish',
				'et_EE' => 'Estonian',
				'eu_ES' => 'Basque',
				'fa_IR' => 'Persian',
				'ff_NG' => 'Fulah',
				'fi_FI' => 'Finnish',
				'fo_FO' => 'Faroese',
				'fr_CA' => 'French (Canada)',
				'fr_FR' => 'French (France)',
				'fy_NL' => 'Frisian',
				'ga_IE' => 'Irish',
				'gl_ES' => 'Galician',
				'gn_PY' => 'Guarani',
				'gu_IN' => 'Gujarati',
				'ha_NG' => 'Hausa',
				'he_IL' => 'Hebrew',
				'hi_IN' => 'Hindi',
				'hr_HR' => 'Croatian',
				'hu_HU' => 'Hungarian',
				'hy_AM' => 'Armenian',
				'id_ID' => 'Indonesian',
				'is_IS' => 'Icelandic',
				'it_IT' => 'Italian',
				'ja_JP' => 'Japanese',
				'ja_KS' => 'Japanese (Kansai)',
				'jv_ID' => 'Javanese',
				'ka_GE' => 'Georgian',
				'kk_KZ' => 'Kazakh',
				'km_KH' => 'Khmer',
				'kn_IN' => 'Kannada',
				'ko_KR' => 'Korean',
				'ku_TR' => 'Kurdish (Kurmanji)',
				'lt_LT' => 'Lithuanian',
				'lv_LV' => 'Latvian',
				'mg_MG' => 'Malagasy',
				'mk_MK' => 'Macedonian',
				'ml_IN' => 'Malayalam',
				'mn_MN' => 'Mongolian',
				'mr_IN' => 'Marathi',
				'ms_MY' => 'Malay',
				'mt_MT' => 'Maltese',
				'my_MM' => 'Burmese',
				'nb_NO' => 'Norwegian (bokmal)',
				'ne_NP' => 'Nepali',
				'nl_BE' => 'Dutch (België)',
				'nl_NL' => 'Dutch',
				'nn_NO' => 'Norwegian (nynorsk)',
				'or_IN' => 'Oriya',
				'pa_IN' => 'Punjabi',
				'pl_PL' => 'Polish',
				'ps_AF' => 'Pashto',
				'pt_BR' => 'Portuguese (Brazil)',
				'pt_PT' => 'Portuguese (Portugal)',
				'ro_RO' => 'Romanian',
				'ru_RU' => 'Russian',
				'rw_RW' => 'Kinyarwanda',
				'sc_IT' => 'Sardinian',
				'si_LK' => 'Sinhala',
				'sk_SK' => 'Slovak',
				'sl_SI' => 'Slovenian',
				'so_SO' => 'Somali',
				'sq_AL' => 'Albanian',
				'sr_RS' => 'Serbian',
				'sv_SE' => 'Swedish',
				'sw_KE' => 'Swahili',
				'sz_PL' => 'Silesian',
				'ta_IN' => 'Tamil',
				'te_IN' => 'Telugu',
				'tg_TJ' => 'Tajik',
				'th_TH' => 'Thai',
				'tl_PH' => 'Filipino',
				'tr_TR' => 'Turkish',
				'tz_MA' => 'Tamazight',
				'uk_UA' => 'Ukrainian',
				'ur_PK' => 'Urdu',
				'uz_UZ' => 'Uzbek',
				'vi_VN' => 'Vietnamese',
				'zh_CN' => 'Simplified Chinese (China)',
				'zh_HK' => 'Traditional Chinese (Hong Kong)',
				'zh_TW' => 'Traditional Chinese (Taiwan)',
			),

			/*
			 * https://developers.google.com/+/web/api/supported-languages
			 */
			'google' => array(
				'af'	 => 'Afrikaans',
				'am'	 => 'Amharic',
				'ar'	 => 'Arabic',
				'eu'	 => 'Basque',
				'bn'	 => 'Bengali',
				'bg'	 => 'Bulgarian',
				'ca'	 => 'Catalan',
				'zh-CN'	 => 'Chinese (Simplified)',
				'zh-HK'	 => 'Chinese (Hong Kong)',
				'zh-TW'	 => 'Chinese (Traditional)',
				'hr'	 => 'Croatian',
				'cs'	 => 'Czech',
				'da'	 => 'Danish',
				'nl'	 => 'Dutch',
				'en-GB'	 => 'English (UK)',
				'en-US'	 => 'English (US)',
				'et'	 => 'Estonian',
				'fil'	 => 'Filipino',
				'fi'	 => 'Finnish',
				'fr'	 => 'French',
				'fr-CA'	 => 'French (Canadian)',
				'gl'	 => 'Galician',
				'de'	 => 'German',
				'el'	 => 'Greek',
				'gu'	 => 'Gujarati',
				'iw'	 => 'Hebrew',
				'hi'	 => 'Hindi',
				'hu'	 => 'Hungarian',
				'is'	 => 'Icelandic',
				'id'	 => 'Indonesian',
				'it'	 => 'Italian',
				'ja'	 => 'Japanese',
				'kn'	 => 'Kannada',
				'ko'	 => 'Korean',
				'lv'	 => 'Latvian',
				'lt'	 => 'Lithuanian',
				'ms'	 => 'Malay',
				'ml'	 => 'Malayalam',
				'mr'	 => 'Marathi',
				'no'	 => 'Norwegian',
				'fa'	 => 'Persian',
				'pl'	 => 'Polish',
				'pt-BR'	 => 'Portuguese (Brazil)',
				'pt-PT'	 => 'Portuguese (Portugal)',
				'ro'	 => 'Romanian',
				'ru'	 => 'Russian',
				'sr'	 => 'Serbian',
				'sk'	 => 'Slovak',
				'sl'	 => 'Slovenian',
				'es'	 => 'Spanish',
				'es-419' => 'Spanish (Latin America)',
				'sw'	 => 'Swahili',
				'sv'	 => 'Swedish',
				'ta'	 => 'Tamil',
				'te'	 => 'Telugu',
				'th'	 => 'Thai',
				'tr'	 => 'Turkish',
				'uk'	 => 'Ukrainian',
				'ur'	 => 'Urdu',
				'vi'	 => 'Vietnamese',
				'zu'	 => 'Zulu',
			),
			'pinterest' => array(
				'en'	=> 'English',
				'ja'	=> 'Japanese',
			),

			/*
			 * https://dev.twitter.com/web/overview/languages
			 */
			'twitter' => array(
				'ar'	=> 'Arabic',
				'bn'	=> 'Bengali',
				'zh-tw'	=> 'Chinese (Traditional)',
				'zh-cn'	=> 'Chinese (Simplified)',
				'cs'	=> 'Czech',
				'da'	=> 'Danish',
				'en'	=> 'English',
				'de'	=> 'German',
				'el'	=> 'Greek',
				'fi'	=> 'Finnish',
				'fil'	=> 'Filipino',
				'fr'	=> 'French',
				'he'	=> 'Hebrew',
				'hi'	=> 'Hindi',
				'hu'	=> 'Hungarian',
				'id'	=> 'Indonesian',
				'it'	=> 'Italian',
				'ja'	=> 'Japanese',
				'ko'	=> 'Korean',
				'msa'	=> 'Malay',
				'nl'	=> 'Dutch',
				'no'	=> 'Norwegian',
				'fa'	=> 'Persian',
				'pl'	=> 'Polish',
				'pt'	=> 'Portuguese',
				'ro'	=> 'Romanian',
				'ru'	=> 'Russian',
				'es'	=> 'Spanish',
				'sv'	=> 'Swedish',
				'th'	=> 'Thai',
				'tr'	=> 'Turkish',
				'uk'	=> 'Ukrainian',
				'ur'	=> 'Urdu',
				'vi'	=> 'Vietnamese',
			)
		);

		public function __construct() {}

		/*
		 * ARRAY HANDLING METHODS:
		 *
		 *	add_after_key()
		 *	add_before_key()
		 *	array_count_diff()
		 *	array_flatten()
		 *	array_implode()
		 *	array_insert_element()
		 *	array_key_last()
		 *	array_map_recursive()
		 *	array_maybe_unserialize()
		 *	array_merge_recursive_distinct()
		 *	array_slice_fifo()
		 *	array_to_hashtags()
		 *	array_to_keywords()
		 *	get_array_or_element()
		 *	get_array_pretty()
		 *	get_assoc_salt()
		 *	move_to_front()
		 *	move_to_end()
		 *	natasort()
		 *	natksort()
		 *	next_key()
		 *	preg_grep_keys()
		 *	unset_keys()
		 *	unset_numeric_keys()
		 *
		 * Modify the referenced array and return true or false.
		 */
		public static function add_after_key( array &$arr, $match_key, $arr_or_key, $value = null ) {

			return self::array_insert_element( $arr, $insert = 'after', $match_key, $arr_or_key, $value );
		}

		/*
		 * Modify the referenced array and return true or false.
		 */
		public static function add_before_key( array &$arr, $match_key, $arr_or_key, $value = null ) {

			return self::array_insert_element( $arr, $insert = 'before', $match_key, $arr_or_key, $value );
		}

		public static function array_count_diff( array $arr, $max = 0 ) {

			$diff = 0;

			if ( $max > 0 && $max >= count( $arr ) ) {

				$diff = $max - count( $arr );
			}

			return $diff;
		}

		/*
		 * Convert a multi-dimentional array to a single dimension array.
		 */
		public static function array_flatten( array $arr ) {

			$flattened = array();

		        foreach ( $arr as $key => $value ) {

				if ( is_array( $value ) ) {

					$flattened = array_merge( $flattened, self::array_flatten( $value ) );

				} else $flattened[ $key ] = $value;
			}

			return $flattened;
		}

		/*
		 * Implode multi-dimentional array.
		 */
		public static function array_implode( array $arr, $glue = ' ' ) {

			$imploded = '';

		        foreach ( $arr as $value ) {

			        if ( is_array( $value ) ) {

					$imploded .= self::array_implode( $value, $glue ) . $glue;

				} else $imploded .= $value . $glue;
			}

			return strlen( $glue ) ? rtrim( $imploded, $glue ) : $glue;
		}

		/*
		 * Modify the referenced array and return true or false.
		 */
		public static function array_insert_element( array &$arr, $insert, $match_key, $arr_or_key, $value = null ) {

			$found_match = false;

			if ( array_key_exists( $match_key, $arr ) ) {

				$new_arr = array();

				foreach ( $arr as $key => $val ) {

					if ( 'after' === $insert ) {

						$new_arr[ $key ] = $val;
					}

					/*
					 * Add new value before/after the matched key.
					 *
					 * Replace the matched key by default (no test required).
					 */
					if ( $key === $match_key ) {

						if ( is_array( $arr_or_key ) ) {

							$new_arr = array_merge( $new_arr, $arr_or_key );

						} elseif ( is_string( $arr_or_key ) ) {

							$new_arr[ $arr_or_key ] = $value;

						} else $new_arr[] = $value;

						$found_match = true;
					}

					if ( 'before' === $insert ) {

						$new_arr[ $key ] = $val;
					}
				}

				$arr = $new_arr;

				unset( $new_arr );
			}

			return $found_match;
		}

		public static function array_key_last( array $array ) {

			if ( function_exists( 'array_key_last' ) ) {

				return array_key_last( $array );	// Since PHP v7.3.
			}

			return key( array_slice( $array, -1, 1, true ) );
		}

		public static function array_map_recursive( $func, array $arr ) {

			foreach ( $arr as $key => $el ) {

				$arr[ $key ] = is_array( $el ) ? self::array_map_recursive( $func, $el ) : $func( $el );
			}

			return $arr;
		}

		public static function array_maybe_unserialize( array $arr ) {

			return self::array_map_recursive( 'maybe_unserialize', $arr );
		}

		/*
		 * PHP's array_merge_recursive() merges arrays, but it converts values with duplicate keys to arrays rather than
		 * overwriting the value in the first array with the duplicate value in the second array, as array_merge does. The
		 * following method does not change the datatypes of the values in the arrays. Matching key values in the second
		 * array overwrite those in the first array, as is the case with array_merge().
		 */
		public static function array_merge_recursive_distinct( array &$arr1, array &$arr2 ) {

			$merged = $arr1;

			foreach ( $arr2 as $key => &$value ) {

				if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {

					$merged[ $key ] = self::array_merge_recursive_distinct( $merged[ $key ], $value );

				} else $merged[ $key ] = $value;
			}

			return $merged;
		}

		/*
		 * Maybe limit the number of array elements.
		 */
		public static function array_slice_fifo( array $array, $max = 1 ) {

			if ( is_numeric( $max ) && $max > 0 && count( $array ) > $max ) {

				return array_slice( $array, -$max, $length = null, $preserve_keys = true );
			}

			return $array;
		}

		public static function array_to_hashtags( array $arr = array() ) {

			$hashtags = self::sanitize_hashtags( $arr );
			$hashtags = array_filter( $hashtags );	// Removes empty array elements.
			$hashtags = trim( implode( $glue = ' ', $hashtags ) );

			return $hashtags;
		}

		/*
		 * Convert an array to a comma delimited text string.
		 */
		public static function array_to_keywords( array $arr = array() ) {

			$keywords = array_map( 'sanitize_text_field', $arr );
			$keywords = trim( implode( $glue = ', ', $keywords ) );

			return $keywords;
		}

		/*
		 * Convert an array to an HTML list.
		 */
		public static function array_to_list_html( array $arr, $type = 'ul' ) {

			return '<' . $type . '><li>' . implode( $glue = '</li> <li>', $arr ) . '</li></' . $type . '> ';
		}

		public static function get_array_or_element( $arr, $key = false, $add_none = false ) {

			if ( null === $key ) {

				// Nothing to do.

			} elseif ( false === $key ) {

				// Nothing to do.

			} elseif ( true === $key ) {	// Sort elements.

				asort( $arr );	// Sort an array in ascending order and maintain index association.

			} elseif ( isset( $arr[ $key ] ) ) {	// Return a specific array element.

				return $arr[ $key ];

			} else return null;	// Array key not found - return null.

			if ( true === $add_none ) {	// Prefix array with 'none'.

				/*
				 * The union operator (+) gives priority to values in the first array, while
				 * array_replace() gives priority to values in the the second array.
				 */
				$arr = array( 'none' => 'none' ) + $arr;	// Maintains numeric index.
			}

			return $arr;
		}

		public static function get_array_parents( array $arr, $parent_key = '', $gparent_key = '', &$parents = array() ) {

		        foreach ( $arr as $child_key => $value ) {

				if ( is_array( $value ) ) {

					self::get_array_parents( $value, $child_key, $parent_key, $parents );

				} elseif ( $parent_key && $child_key !== $parent_key ) {

					$parents[ $child_key ][] = $parent_key;

				} elseif ( $gparent_key && $child_key === $parent_key ) {

					$parents[ $child_key ][] = $gparent_key;
				}
			}

			return $parents;
		}

		public static function get_array_pretty( $mixed, $flatten = false ) {

			$pretty = '';

			if ( is_array( $mixed ) ) {

				foreach ( $mixed as $key => $val ) {

					$val = self::get_array_pretty( $val, $flatten );

					if ( $flatten ) {

						$pretty .= $key . '=' . $val.', ';

					} else {

						if ( is_object( $mixed[ $key ] ) ) {

							unset( $mixed[ $key ] );	// Dereference the object first.
						}

						$mixed[ $key ] = $val;
					}
				}

				$pretty = $flatten ? '(' . trim( $pretty, ', ' ) . ')' : $mixed;

			} elseif ( false === $mixed ) {

				$pretty = 'false';

			} elseif ( true === $mixed ) {

				$pretty = 'true';

			} elseif ( null === $mixed ) {

				$pretty = 'null';

			} elseif ( '' === $mixed ) {

				$pretty = '\'\'';

			} elseif ( is_object( $mixed ) ) {

				$pretty = 'object ' . get_class( $mixed );

			} else $pretty = $mixed;

			return $pretty;
		}

		public static function get_assoc_salt( array $assoc ) {

			$assoc_salt = '';

			foreach ( $assoc as $key => $val ) {

				$assoc_salt .= '_' . $key . ':' . (string) $val;
			}

			$assoc_salt = ltrim( $assoc_salt, '_' );	// Remove leading underscore.

			return $assoc_salt;
		}

		/*
		 * Move an array element to the front.
		 */
		public static function move_to_front( array &$arr, $key ) {

			if ( array_key_exists( $key, $arr ) ) {

				$val = $arr[ $key ];

				unset( $arr[ $key ] );

				$arr = array_merge( array( $key => $val ), $arr );
			}

			return $arr;
		}

		/*
		 * Move an array element to the end.
		 */
		public static function move_to_end( array &$arr, $key ) {

			if ( array_key_exists( $key, $arr ) ) {

				$val = $arr[ $key ];

				unset( $arr[ $key ] );

				$arr[ $key ] = $val;
			}

			return $arr;
		}

		public static function natasort( array &$arr ) {

			if ( function_exists( 'remove_accents' ) ) {	// WordPress function.

				return uasort( $arr, array( __CLASS__, 'strnatcasecmp_remove_accents' ) );
			}

			return uasort( $arr, 'strnatcasecmp' );
		}

		public static function natksort( array &$arr ) {

			if ( function_exists( 'remove_accents' ) ) {	// WordPress function.

				return uksort( $arr, array( __CLASS__, 'strnatcasecmp_remove_accents' ) );
			}

			return uksort( $arr, 'strnatcasecmp' );
		}

		public static function next_key( $needle, array &$arr, $loop_around = true ) {

			$keys = array_keys( $arr );
			$pos  = array_search( $needle, $keys );

			if ( false !== $pos ) {

				if ( isset( $keys[ $pos + 1 ] ) ) {

					return $keys[ $pos + 1 ];

				} elseif ( true === $loop_around ) {

					return $keys[ 0 ];
				}
			}

			return false;
		}

		/*
		 * $keys_preg must be a string.
		 *
		 * $replace can be a string or an associative array of 'pattern' => 'replacement'.
		 */
		public static function preg_grep_keys( $keys_preg, array $in_array, $invert = false, $replace = false ) {

			if ( empty( $in_array ) ) {	// Nothing to do.

				return $in_array;
			}

			$in_array_keys = array_keys( $in_array );
			$matched_keys  = preg_grep( $keys_preg, $in_array_keys, $invert ? PREG_GREP_INVERT : 0 );

			if ( empty( $matched_keys ) && $invert ) {	// Nothing to do.

				return $in_array;
			}

			/*
		 	 * The $replace value can be a string or an associative array of 'pattern' => 'replacement'.
			 */
			if ( is_array( $replace ) ) {

				$patterns     = array_keys( $replace );
				$replacements = array_values( $replace );

			} else {

				$patterns     = $keys_preg;
				$replacements = $replace;
			}

			$out_array = array();

			foreach ( $matched_keys as $key ) {

				if ( false === $replace ) {	// Element key remains unchanged.

					$out_array[ $key ] = $in_array[ $key ];

				} else {

					$fixed = preg_replace( $patterns, $replacements, $key );

					$out_array[ $fixed ] = $in_array[ $key ];
				}
			}

			return $out_array;
		}

		public static function unset_keys( &$arr1, $arr2 ) {

			foreach ( array_keys( $arr2 ) as $key ) {

				unset( $arr1[ $key ] );
			}
		}

		public static function unset_numeric_keys( &$arr ) {

			foreach ( array_keys( $arr ) as $key ) {

				if ( is_numeric( $key ) ) {

					unset( $arr[ $key ] );
				}
			}
		}

		/*
		 * COUNTRY METHODS:
		 *
		 *	get_alpha2_countries()
		 *	get_alpha2_country_name()
		 */
		public static function get_alpha2_countries() {

			if ( ! class_exists( 'SucomCountryCodes' ) ) {

				require_once dirname( __FILE__ ) . '/country-codes.php';
			}

			return SucomCountryCodes::get( 'alpha2' );
		}

		public static function get_alpha2_country_name( $country_code, $default_code = false ) {

			if ( empty( $country_code ) || 'none' === $country_code ) {

				return false;
			}

			if ( ! class_exists( 'SucomCountryCodes' ) ) {

				require_once dirname( __FILE__ ) . '/country-codes.php';
			}

			$countries = SucomCountryCodes::get( 'alpha2' );

			if ( ! isset( $countries[ $country_code ] ) ) {

				if ( false === $default_code || ! isset( $countries[ $default_code ] ) ) {

					return false;
				}

				return $countries[ $default_code ];
			}

			return $countries[ $country_code ];
		}

		/*
		 * CURRENCY METHODS:
		 *
		 *	get_currencies()
		 *	get_currencies_abbrev()
		 *	get_currency_symbol_abbrev()
		 *
		 * Used by WpssoSubmenuGeneral->get_table_rows() for 'Default Currency' option.
		 */
		public static function get_currencies( $key = false, $add_none = false, $format = '%2$s (%1$s)' ) {

			if ( ! class_exists( 'SucomCurrencies' ) ) {

				require_once dirname( __FILE__ ) . '/currencies.php';
			}

			if ( is_string( $key ) ) return SucomCurrencies::get( $key, $format );

			return self::get_array_or_element( SucomCurrencies::get( null, $format ), $key, $add_none );
		}

		public static function get_currencies_abbrev() {

			$currencies_by_abbrev = self::get_currencies( null, $add_none = false, $format = '%1$s' );

			asort( $currencies_by_abbrev );	// Sort by abbreviation.

			return $currencies_by_abbrev;
		}

		public static function get_currency_symbol_abbrev( $symbol = false, $default = 'USD', $decode = true ) {

			if ( $decode ) $symbol = self::decode_html( $symbol );

			if ( $symbol === '$' ) return 'USD';

			if ( ! class_exists( 'SucomCurrencies' ) ) {

				require_once dirname( __FILE__ ) . '/currencies.php';
			}

			$currencies_by_abbrev = SucomCurrencies::get_symbols( $key = null, $decode );

			$currencies_by_symbol = array_flip( $currencies_by_abbrev );	// Index by symbol.

			unset( $currencies_by_abbrev );

			return isset( $currencies_by_symbol[ $symbol ] ) ? $currencies_by_symbol[ $symbol ] : false;
		}

		/*
		 * DASHICON METHODS:
		 */
		public static function get_dashicons( $key = true, $add_none = false ) {

			if ( ! class_exists( 'SucomDashicons' ) ) {

				require_once dirname( __FILE__ ) . '/dashicons.php';
			}

			if ( is_numeric( $key ) || is_string( $key ) ) {

				return SucomDashicons::get( $key );
			}

			return self::get_array_or_element( SucomDashicons::get(), $key, $add_none );
		}

		/*
		 * DATE AND TIME METHODS:
		 *
		 *	format_tz_offset()
		 *	get_default_timezone()
		 *	get_formatted_timezone()
		 *	get_hours()
		 *	get_hours_range()
		 *	get_timezones()
		 *	get_timezone_abbr()
		 *	get_timezone_offset_secs()
		 *	get_timezone_offset_hours()
		 *	get_tz_name()
		 *	iso8601_to_seconds()
		 *	maybe_iso8601_to_seconds()
		 *	sprintf_date_time()
		 *
		 * Returns a date( 'P' ) formatted timezone value (ie. -07:00).
		 */
		public static function format_tz_offset( $offset ) {

			$hours     = (int) $offset;
			$minutes   = ( $offset - $hours );
			$sign      = ( $offset < 0 ) ? '-' : '+';
			$abs_hour  = abs( $hours );
			$abs_mins  = abs( $minutes * 60 );
			$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

			return $tz_offset;
		}

		/*
		 * May return a timezone string (ie. 'Africa/Abidjan'), 'UTC', or an offset (ie. '-07:00').
		 */
		public static function get_default_timezone() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			if ( function_exists( wp_timezone_string() ) ) {

				return $local_cache = wp_timezone_string();
			}

			$timezone_string = get_option( 'timezone_string' );

			if ( $timezone_string ) {

				return $local_cache = $timezone_string;
			}

			$offset = (float) get_option( 'gmt_offset' );

			$tz_offset = self::format_tz_offset( $offset );

			return $local_cache = $tz_offset;
		}

		public static function get_formatted_timezone( $tz_name, $format ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $tz_name ][ $format ] ) ) {

				return $local_cache[ $tz_name ][ $format ];
			}

			$dt_obj = new DateTime();

			$dt_obj->setTimeZone( new DateTimeZone( $tz_name ) );

			return $local_cache[ $tz_name ][ $format ] = $dt_obj->format( $format );
		}

		public static function get_hours( $step_secs = 3600, $label_format = 'H:i' ) {

			return self::get_hours_range( $start_secs = 0, $end_secs = 86400, $step_secs, $label_format );
		}

		/*
		 * Returns an associative array.
		 *
		 * Example time formats: 'H:i' (default), 'g:i a'.
		 */
		public static function get_hours_range( $start_secs = 0, $end_secs = 86400, $step_secs = 3600, $label_format = 'H:i' ) {

			$times = array();

		        foreach ( range( $start_secs, $end_secs, $step_secs ) as $ts ) {

				$value = gmdate( 'H:i', $ts );

				if ( 'H:i' !== $label_format ) {

					$times[ $value ] = gmdate( $label_format, $ts );

				} else $times[ $value ] = $value;
			}

			return $times;
		}

		/*
		 * Returns an associative array of timezone strings (ie. 'Africa/Abidjan'), 'UTC', and offsets (ie. '-07:00').
		 */
		public static function get_timezones() {

			/*
			 * See https://www.php.net/manual/en/function.timezone-identifiers-list.php.
			 */
			$timezones    = timezone_identifiers_list();
			$timezones    = array_combine( $timezones, $timezones );	// Create an associative array.
			$offset_range = array( -12, -11.5, -11, -10.5, -10, -9.5, -9, -8.5, -8, -7.5, -7, -6.5, -6, -5.5, -5, -4.5,
				-4, -3.5, -3, -2.5, -2, -1.5, -1, -0.5, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6,
				6.5, 7, 7.5, 8, 8.5, 8.75, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.75, 13, 13.75, 14 );

			/*
			 * Create date( 'P' ) formatted timezone values (ie. -07:00).
			 */
			foreach ( $offset_range as $offset ) {

				$offset_value = self::format_tz_offset( $offset );

				$offset_name = 'UTC' . $offset_value;

				$timezones[ $offset_value ] = $offset_name;
			}

			return $timezones;
		}

		/*
		 * Get timezone abbreviation (ie. 'EST', 'MDT', etc.).
		 */
		public static function get_timezone_abbr( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'T' );
		}

		/*
		 * Get timezone offset in seconds (west of UTC is negative, and east of UTC is positive).
		 */
		public static function get_timezone_offset_secs( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'Z' );
		}

		/*
		 * Timezone difference to UTC with colon between hours and minutes.
		 */
		public static function get_timezone_offset_hours( $tz_name ) {

			/*
			 * See https://www.php.net/manual/en/datetime.format.php.
			 *
			 * P 	Difference to Greenwich time (GMT) with colon between hours and minutes.
			 * p 	The same as P, but returns Z instead of +00:00.
			 *
			 * Unfortunately 'p' is only available since PHP v8, so we must use 'P' and check for '+00:00'.
			 */
			 $offset = self::get_formatted_timezone( $tz_name, 'P' );

			 if ( '+00:00' == $offset ) {

			 	$offset = 'Z';
			 }

			 return $offset;
		}

		/*
		 * "tz" is used in the method name to hint that the argument is an abbreviation.
		 */
		public static function get_tz_name( $tz_abbr ) {

			return timezone_name_from_abbr( $tz_abbr );
		}

		/*
		 * Convert ISO 8601 value (like P2DT15M33S) to seconds.
		 */
		public static function iso8601_to_seconds( $iso8601 ) {

			$interval = new \DateInterval( $iso8601 );

		        return ( $interval->d * 24 * 60 * 60 ) + ( $interval->h * 60 * 60 ) + ( $interval->i * 60 ) + $interval->s;
		}

		public static function maybe_iso8601_to_seconds( $mixed ) {

			if ( 0 === strpos( $mixed, 'P' ) ) {

				return self::iso8601_to_seconds( $mixed );
			}

			return $mixed;
		}

		/*
		 * See WpssoUser->add_person_role().
		 * See WpssoUser->remove_person_role().
		 * See WpssoUtilCache->refresh().
		 */
		public static function sprintf_date_time( $fmt = '%1$s %2$s' ) {

			$wp_timezone = wp_timezone();
			$wp_date_fmt = get_option( 'date_format' );
			$wp_time_fmt = get_option( 'time_format' );

			$now = date_create_immutable( $datetime = 'now', $wp_timezone );

			$date_val = $now->format( $wp_date_fmt );
			$time_val = $now->format( $wp_time_fmt );

			$date_time_val = sprintf( $fmt, $date_val, $time_val );

			return $date_time_val;
		}

		/*
		 * IS CHECK METHODS:
		 *
		 *	is_assoc()
		 *	is_https()
		 *	is_md5()
		 *	is_non_assoc()
		 *	is_true()
		 *	is_valid_option_value()
		 *
		 * Note that an empty array is not an associative array (ie. returns false for an empty array).
		 */
		public static function is_assoc( $mixed ) {

			$is_assoc = false;

			if ( ! empty( $mixed ) ) {	// Optimize.

				if ( is_array( $mixed ) ) {	// Just in case.

					if ( ! is_numeric( implode( array_keys( $mixed ) ) ) ) {

						$is_assoc = true;
					}
				}
			}

			return $is_assoc;
		}

		public static function is_https( $url = '' ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $url ] ) ) {

				return $local_cache[ $url ];
			}

			if ( strpos( $url, '://' ) ) {

				if ( 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) ) {

					return $local_cache[ $url ] = true;
				}

				return $local_cache[ $url ] = false;

			} elseif ( is_ssl() ) {

				return $local_cache[ $url ] = true;

			} elseif ( isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) && 'https' === strtolower( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) ) {

				return $local_cache[ $url ] = true;

			} elseif ( isset( $_SERVER[ 'HTTP_X_FORWARDED_SSL' ] ) && 'on' === strtolower( $_SERVER[ 'HTTP_X_FORWARDED_SSL' ] ) ) {

				return $local_cache[ $url ] = true;
			}

			return $local_cache[ $url ] = false;
		}

		public static function is_md5( $md5 ) {

			return strlen( $md5 ) === 32 && preg_match( '/^[a-f0-9]+$/', $md5 ) ? true : false;
		}

		/*
		 * Note that an empty array is not an associative array (ie. returns false for an empty array).
		 */
		public static function is_non_assoc( $mixed ) {

			$is_non_assoc = false;

			if ( is_array( $mixed ) ) {	// Just in case.

				if ( empty( $mixed ) ) {	// Optimize.

					$is_non_assoc = true;

				} elseif ( is_numeric( implode( array_keys( $mixed ) ) ) ) {

					$is_non_assoc = true;
				}
			}

			return $is_non_assoc;
		}

		public static function is_true( $mixed, $allow_null = false ) {

			$is_true = is_string( $mixed ) ? filter_var( $mixed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : (bool) $mixed;

		        return null === $is_true && ! $allow_null ? false : $is_true;
		}

		/*
		 * Check that the option value is not true, false, null, empty string, or 'none'.
		 */
		public static function is_valid_option_value( $value ) {

			if ( true === $value ) {

				return false;

			} elseif ( empty( $value ) && ! is_numeric( $value ) ) {	// False, null, or empty string.

				return false;

			} elseif ( 'none' === $value ) {	// Disabled option.

				return false;
			}

			return true;
		}

		/*
		 * OPEN GRAPH AND META TAG METHODS:
		 *
		 *	get_mt_og_seed()
		 *	get_mt_image_seed()
		 *	get_mt_product_seed()
		 *	get_mt_video_seed()
		 *	get_first_og_image_id()
		 *	get_first_og_image_url()
		 *	get_first_og_video_url()
		 *	get_first_mt_media_id()
		 *	get_first_mt_media_url()
		 *	get_publisher_languages()
		 *	merge_mt_og()
		 */
		public static function get_mt_og_seed() {

			return array(
				'fb:admins'           => null,
				'fb:app_id'           => null,
				'og:time'             => null,	// Internal meta tag.
				'og:type'             => null,
				'og:url'              => null,
				'og:redirect_url'     => null,	// Internal meta tag.
				'og:locale'           => null,
				'og:site_name'        => null,
				'og:title'            => null,
				'og:description'      => null,
				'og:updated_time'     => null,
				'og:video'            => null,
				'og:image'            => null,
				'schema:language'     => null,	// Internal meta tag.
				'schema:type:id'      => null,	// Internal meta tag.
				'schema:type:url'     => null,	// Internal meta tag.
				'schema:type:context' => null,	// Internal meta tag.
				'schema:type:name'    => null,	// Internal meta tag.
				'schema:type:path'    => null,	// Internal meta tag.
			);
		}

		/*
		 * Pre-define the array key order for the list() construct.
		 */
		public static function get_mt_image_seed( $mt_pre = 'og', array $mt_og = array() ) {

			$mt_ret = array(
				$mt_pre . ':image:secure_url' => null,
				$mt_pre . ':image:url'        => null,
				$mt_pre . ':image:width'      => null,
				$mt_pre . ':image:height'     => null,
				$mt_pre . ':image:cropped'    => null,	// Internal meta tag.
				$mt_pre . ':image:id'         => null,	// Internal meta tag.
				$mt_pre . ':image:alt'        => null,
				$mt_pre . ':image:size_name'  => null,	// Internal meta tag.
			);

			return self::merge_mt_og( $mt_ret, $mt_og );
		}

		/*
		 * This method is used by e-Commerce modules to pre-define and pre-sort the product meta tags.
		 *
		 * Use null values so WpssoOpengraph->add_data_og_type_md() can load metadata default values.
		 */
		public static function get_mt_product_seed( $mt_pre = 'product', array $mt_og = array() ) {

			$mt_ret = array(

				/*
				 * Product part numbers.
				 */
				$mt_pre . ':retailer_item_id' => null,	// Product ID.
				$mt_pre . ':retailer_part_no' => null,	// Product SKU.
				$mt_pre . ':mfr_part_no'      => null,	// Product MPN.
				$mt_pre . ':item_group_id'    => null,	// Product variant group ID.
				$mt_pre . ':ean'              => null,	// aka EAN, EAN-13, GTIN-13.
				$mt_pre . ':gtin14'           => null,	// Internal meta tag.
				$mt_pre . ':gtin13'           => null,	// Internal meta tag.
				$mt_pre . ':gtin12'           => null,	// Internal meta tag.
				$mt_pre . ':gtin8'            => null,	// Internal meta tag.
				$mt_pre . ':gtin'             => null,	// Internal meta tag.
				$mt_pre . ':isbn'             => null,
				$mt_pre . ':upc'              => null,	// Aka the UPC, UPC-A, UPC, GTIN-12.

				/*
				 * Product attributes and descriptions.
				 */
				$mt_pre . ':url'                         => null,	// Internal meta tag.
				$mt_pre . ':title'                       => null,	// Internal meta tag.
				$mt_pre . ':description'                 => null,	// Internal meta tag.
				$mt_pre . ':updated_time'                => null,	// Internal meta tag.
				$mt_pre . ':adult_type'                  => null,	// Internal meta tag.
				$mt_pre . ':age_group'                   => null,
				$mt_pre . ':quantity'                    => null,	// Internal meta tag.
				$mt_pre . ':availability'                => null,
				$mt_pre . ':brand'                       => null,
				$mt_pre . ':category'                    => null,	// The product category according to the Google product taxonomy.
				$mt_pre . ':retailer_category'           => null,	// Internal meta tag.
				$mt_pre . ':awards'                      => array(),	// Internal meta tag.
				$mt_pre . ':condition'                   => null,
				$mt_pre . ':energy_efficiency:value'     => null,	// Internal meta tag.
				$mt_pre . ':energy_efficiency:min_value' => null,	// Internal meta tag.
				$mt_pre . ':energy_efficiency:max_value' => null,	// Internal meta tag.
				$mt_pre . ':expiration_time'             => null,
				$mt_pre . ':color'                       => null,
				$mt_pre . ':material'                    => null,
				$mt_pre . ':mrp_id'                      => null,	// Internal meta tag.
				$mt_pre . ':pattern'                     => null,
				$mt_pre . ':purchase_limit'              => null,
				$mt_pre . ':eligible_quantity:value'     => null,	// Internal meta tag.
				$mt_pre . ':eligible_quantity:min_value' => null,	// Internal meta tag.
				$mt_pre . ':eligible_quantity:max_value' => null,	// Internal meta tag.
				$mt_pre . ':eligible_quantity:unit_code' => null,	// Internal meta tag.
				$mt_pre . ':eligible_quantity:unit_text' => null,	// Internal meta tag.
				$mt_pre . ':target_gender'               => null,
				$mt_pre . ':size'                        => null,
				$mt_pre . ':size_group'                  => null,	// Internal meta tag.
				$mt_pre . ':size_system'                 => null,	// Internal meta tag.
				$mt_pre . ':is_virtual'                  => null,	// Internal meta tag.

				/*
				 * Product net dimensions and weight.
				 */
				$mt_pre . ':length:value'       => null,	// Internal meta tag.
				$mt_pre . ':length:units'       => null,	// Internal meta tag (units after value).
				$mt_pre . ':width:value'        => null,	// Internal meta tag.
				$mt_pre . ':width:units'        => null,	// Internal meta tag (units after value).
				$mt_pre . ':height:value'       => null,	// Internal meta tag.
				$mt_pre . ':height:units'       => null,	// Internal meta tag (units after value).
				$mt_pre . ':weight:value'       => null,
				$mt_pre . ':weight:units'       => null,
				$mt_pre . ':fluid_volume:value' => null,	// Internal meta tag.
				$mt_pre . ':fluid_volume:units' => null,	// Internal meta tag (units after value).

				/*
				 * Product prices.
				 */
				$mt_pre . ':min_advert_price:amount'         => null,		// Internal meta tag.
				$mt_pre . ':min_advert_price:currency'       => null,		// Internal meta tag.
				$mt_pre . ':original_price:amount'           => null,
				$mt_pre . ':original_price:currency'         => null,
				$mt_pre . ':pretax_price:amount'             => null,
				$mt_pre . ':pretax_price:currency'           => null,
				$mt_pre . ':price_type'                      => null,		// Internal meta tag.
				$mt_pre . ':price:amount'                    => null,
				$mt_pre . ':price:currency'                  => null,
				$mt_pre . ':sale_price:amount'               => null,
				$mt_pre . ':sale_price:currency'             => null,
				$mt_pre . ':sale_price_dates:start'          => null,
				$mt_pre . ':sale_price_dates:start_date'     => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:start_time'     => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:start_timezone' => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:start_iso'      => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:end'            => null,
				$mt_pre . ':sale_price_dates:end_date'       => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:end_time'       => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:end_timezone'   => null,		// Internal meta tag.
				$mt_pre . ':sale_price_dates:end_iso'        => null,		// Internal meta tag.
				$mt_pre . ':offers'                          => array(),	// Internal meta tag.

				/*
				 * Product shipping.
				 */
				$mt_pre . ':shipping_cost:amount'   => null,
				$mt_pre . ':shipping_cost:currency' => null,
				$mt_pre . ':shipping_length:value'  => null,	// Internal meta tag.
				$mt_pre . ':shipping_length:units'  => null,	// Internal meta tag (units after value).
				$mt_pre . ':shipping_width:value'   => null,	// Internal meta tag.
				$mt_pre . ':shipping_width:units'   => null,	// Internal meta tag (units after value).
				$mt_pre . ':shipping_height:value'  => null,	// Internal meta tag.
				$mt_pre . ':shipping_height:units'  => null,	// Internal meta tag (units after value).
				$mt_pre . ':shipping_weight:value'  => null,
				$mt_pre . ':shipping_weight:units'  => null,
				$mt_pre . ':shipping_class_id'      => null,	// Internal meta tag.
				$mt_pre . ':shipping_offers'        => array(),	// Internal meta tag.

				/*
				 * Product ratings and reviews.
				 */
				$mt_pre . ':rating:average' => null,	// Internal meta tag.
				$mt_pre . ':rating:count'   => null,	// Internal meta tag.
				$mt_pre . ':rating:worst'   => null,	// Internal meta tag.
				$mt_pre . ':rating:best'    => null,	// Internal meta tag.
				$mt_pre . ':review:count'   => null,	// Internal meta tag.
				$mt_pre . ':reviews'        => array(),	// Internal meta tag.
			);

			if ( ! empty( $mt_og[ 'og:type' ] ) &&  'product' === $mt_og[ 'og:type' ] ) {

				$mt_ret = array_merge( $mt_ret, array(
					$mt_pre . ':variants' => array(),	// Internal meta tag.
				) );
			}

			return self::merge_mt_og( $mt_ret, $mt_og );
		}

		public static function get_mt_video_seed( $mt_pre = 'og', array $mt_og = array() ) {

			$mt_ret = array(
				$mt_pre . ':video:secure_url'      => null,
				$mt_pre . ':video:url'             => null,
				$mt_pre . ':video:title'           => null,	// Internal meta tag.
				$mt_pre . ':video:description'     => null,	// Internal meta tag.
				$mt_pre . ':video:family_friendly' => null,	// Internal meta tag.
				$mt_pre . ':video:regions_allowed' => array(),	// Internal meta tag.
				$mt_pre . ':video:type'            => null,	// Example: 'application/x-shockwave-flash', 'text/html', 'video/mp4', etc.
				$mt_pre . ':video:width'           => null,
				$mt_pre . ':video:height'          => null,
				$mt_pre . ':video:tag'             => array(),
				$mt_pre . ':video:duration'        => null,	// Internal meta tag.
				$mt_pre . ':video:published_date'  => null,	// Internal meta tag.
				$mt_pre . ':video:upload_date'     => null,	// Internal meta tag.
				$mt_pre . ':video:thumbnail_url'   => null,	// Internal meta tag.
				$mt_pre . ':video:embed_url'       => null,	// Internal meta tag.
				$mt_pre . ':video:stream_url'      => null,	// Internal meta tag. VideoObject contentUrl.
				$mt_pre . ':video:stream_size'     => null,	// Internal meta tag. VideoObject contentSize.
				$mt_pre . ':video:has_image'       => false,	// Internal meta tag.
				$mt_pre . ':video:iphone_name'     => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:iphone_id'       => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:iphone_url'      => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:ipad_name'       => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:ipad_id'         => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:ipad_url'        => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:googleplay_name' => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:googleplay_id'   => null,	// Internal meta tag for X (Twitter) player card.
				$mt_pre . ':video:googleplay_url'  => null,	// Internal meta tag for X (Twitter) player card.
			);

			$mt_ret += self::get_mt_image_seed( $mt_pre );

			/*
			 * Facebook applink meta tags.
			 */
			if ( $mt_pre === 'og' ) {

				$mt_ret += array(
					'al:ios:app_name'        => null,
					'al:ios:app_store_id'    => null,
					'al:ios:url'             => null,
					'al:android:app_name'    => null,
					'al:android:package'     => null,
					'al:android:url'         => null,
					'al:web:url'             => null,
					'al:web:should_fallback' => 'false',
				);
			}

			return self::merge_mt_og( $mt_ret, $mt_og );
		}

		public static function get_first_og_image_id( array $assoc ) {

			return self::get_first_mt_media_id( $assoc, $media_pre = 'og:image' );
		}

		public static function get_first_og_image_url( array $assoc ) {

			return self::get_first_mt_media_url( $assoc, $media_pre = 'og:image', $mt_suffixes = array( ':secure_url', ':url', '' ) );
		}

		public static function get_first_og_video_url( array $assoc ) {

			return self::get_first_mt_media_url( $assoc, $media_pre = 'og:video' );
		}

		public static function get_first_mt_media_id( array $assoc, $media_pre = 'og:image' ) {

			return self::get_first_mt_media_url( $assoc, $media_pre, $mt_suffixes = array( ':id' ) );
		}

		/*
		 * Return the first URL from the associative array (og:image:secure_url, og:image:url, og:image).
		 */
		public static function get_first_mt_media_url( array $assoc, $media_pre = 'og:image', $mt_suffixes = null ) {

			/*
			 * Check for two dimensional arrays and keep following the first array element.
			 *
			 * Prefer the $media_pre array key (if it's available).
			 */
			if ( isset( $assoc[ $media_pre ] ) && is_array( $assoc[ $media_pre ] ) ) {

				$first_el = reset( $assoc[ $media_pre ] );

			} else $first_el = reset( $assoc );	// Can be array or string.

			/*
			 * If the first element isn't a string (ie. non-array value), then recurse until we hit bottom.
			 */
			if ( is_array( $first_el ) ) {

				return self::get_first_mt_media_url( $first_el, $media_pre, $mt_suffixes );
			}

			if ( ! is_array( $mt_suffixes ) ) {	// Array of meta tag suffixes to use.

				$mt_suffixes = array( ':secure_url', ':url', '', ':embed_url', ':stream_url' );
			}

			/*
			 * First element is a text string, so check the array keys.
			 */
			foreach ( $mt_suffixes as $mt_suffix ) {

				if ( ! empty( $assoc[ $media_pre . $mt_suffix ] ) ) {

					return $assoc[ $media_pre . $mt_suffix ];	// Return first match.
				}
			}

			return '';	// Empty string.
		}

		public static function get_publisher_languages( $publisher = '' ) {

			switch ( $publisher ) {

				case 'facebook':
				case 'fb':

					return self::$publisher_languages[ 'facebook' ];

				case 'google':

					return self::$publisher_languages[ 'google' ];

				case 'pinterest':
				case 'pin':
				case 'rp':

					return self::$publisher_languages[ 'pinterest' ];

				default:

					if ( isset( self::$publisher_languages[ $publisher ] ) ) {

						return self::$publisher_languages[ $publisher ];
					}

					return array();
			}
		}

		/*
		 * Protected method used by get_mt_image_seed(), get_mt_product_seed(), and get_mt_video_seed().
		 */
		protected static function merge_mt_og( array $mt_ret, array $mt_og ) {

			if ( empty( $mt_og ) ) {	// Nothing to merge.

				return $mt_ret;
			}

			/*
			 * Always keep the 'og:type' meta tag top-most.
			 *
			 * Use array_key_exists() to allow for null value.
			 */
			if ( array_key_exists( 'og:type', $mt_og ) ) {

				return array_merge( array( 'og:type' => $mt_og[ 'og:type' ] ), $mt_ret, $mt_og );
			}

			return array_merge( $mt_ret, $mt_og );
		}

		/*
		 * SANITATION METHODS:
		 *
		 *	sanitize_classname()
		 *	sanitize_css_class()
		 *	sanitize_css_id()
		 *	sanitize_file_name()
		 *	sanitize_file_path()
		 *	sanitize_hashtags()
		 *	sanitize_hookname()
		 *	sanitize_input_name()
		 *	sanitize_int()
		 *	sanitize_key()
		 *	sanitize_locale()
		 *	sanitize_meta_key()
		 *	sanitize_tag()
		 *	sanitize_twitter_name()
		 *	sanitize_use_post()
		 *
		 * Used by WpssoConfig::load_lib() to sanitize file names to class names.
		 *
		 * See https://www.php.net/manual/en/language.variables.basics.php.
		 */
		public static function sanitize_classname( $classname, $allow_underscore = true ) {

			if ( ! $allow_underscore ) {

				$classname = preg_replace( '/_/', '', $classname );	# Remove underscores.
			}

			$classname = preg_replace( '/^[^a-zA-Z_\x80-\xff]+/', '', $classname );	# Cannot start with numeric characters.
			$classname = preg_replace( '/[^a-zA-Z0-9_\x80-\xff]/', '', $classname );

			return $classname;
		}

		public static function sanitize_css_class( $css_class ) {

			return trim( preg_replace( '/[^a-zA-Z0-9_\- ]+/', '-', $css_class ), $characters = '- ' );	// Spaces allowed between css class names.
		}

		/*
		 * See sucomChangeHideUnhideRows() in jquery-metabox.js.
		 */
		public static function sanitize_css_id( $css_id ) {

			return trim( preg_replace( '/[^a-zA-Z0-9_\-]+/', '-', $css_id ), $characters = '-' );	// Spaces not allowed.
		}

		public static function sanitize_file_name( $file_name ) {

			if ( '' === $file_name ) {

				return $file_name;
			}

			$special_chars = array( '?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', '\'', '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', chr( 0 ) );

			$file_name = preg_replace( '#\x{00a0}#siu', ' ', $file_name );
			$file_name = str_replace( $special_chars, '', $file_name );
			$file_name = str_replace( array( '%20', '+' ), '-', $file_name );
			$file_name = preg_replace( '/[\r\n\t -]+/', '-', $file_name );
			$file_name = trim( $file_name, '.-_' );

			return $file_name;
		}

		public static function sanitize_file_path( $file_path ) {

			if ( empty( $file_path ) ) {

				return false;
			}

			$file_path = implode( $glue = '/', array_map( array( __CLASS__, 'sanitize_file_name' ), explode( '/', $file_path ) ) );

			return $file_path;
		}

		/*
		 * Note that hashtags cannot begin with a number. This method truncates tags that begin with a number.
		 */
		public static function sanitize_hashtags( array $tags = array() ) {

			return preg_replace( array( '/^[0-9].*/', '/[ \[\]#!\$\?\\\\\/\*\+\.\-\^]/', '/^.+/' ), array( '', '', '#$0' ), $tags );
		}

		public static function sanitize_hookname( $hookname ) {

			$hookname = preg_replace( '/[\/\-\. \[\]:#]+/', '_', $hookname );
			$hookname = rtrim( $hookname, '_' );	// Only trim right side underscores to allow for '__return_false'.

			return self::sanitize_key( $hookname, $allow_upper = false );
		}

		/*
		 * See WpssoConfig::$cf[ 'opt' ][ 'defaults' ] for example input names.
		 *
		 * A colon can be used for qualifiers (example, ':disabled', ':width', ':height', etc.).
		 * A hashtag can be used for the locale (language).
		 *
		 * Example sanitation:
		 *
		 *	'mrp_method_https://schema.org/ReturnByMail' -> 'mrp_method_https_schema_org_ReturnByMail'
		 */
		public static function sanitize_input_name( $input_name ) {

			$input_name = preg_replace( '/:\/\//', '_', $input_name );
			$input_name = preg_replace( '/[^a-zA-Z0-9_\-:#]+/', '_', $input_name );

			return trim( $input_name, $characters = '-' );
		}

		/*
		 * There is no WordPress function to sanitize post IDs and integers.
		 *
		 * Returns an integer or null.
		 */
		public static function sanitize_int( $value ) {

			if ( is_numeric( $value ) ) {

				return (int) $value;
			}

			return null;
		}

		/*
		 * Sanitize an option array key.
		 *
		 * Unlike the WordPress sanitize_key() function, this method allows for colon and hash characters, and (optionally)
		 * upper case characters.
		 *
		 * See sucomSanitizeKey() in wpsso/js/com/jquery-admin-page.js
		 * See wordpress/wp-includes/formatting.php.
		 */
		public static function sanitize_key( $key, $allow_upper = false ) {

			/*
			 * Scalar variables are those containing an int, float, string or bool. Types array, object, resource and
			 * null are not scalar.
			 */
			if ( is_scalar( $key ) ) {

				if ( ! $allow_upper ) {

					$key = strtolower( $key );	// Convert upper case characters to lower case.
				}

				return preg_replace( '/[^a-zA-Z0-9_\-:#]/', '', $key );
			}

			return '';
		}

		public static function sanitize_locale( $locale ) {

			$locale = str_replace( '-', '_', $locale );	// Convert 'en-US' to 'en_US'.
			$locale = preg_replace( '/[^a-zA-Z_]/', '', $locale );

			return $locale;
		}

		public static function sanitize_meta_key( $meta_key ) {

			$meta_key = self::decode_html( $meta_key );
			$meta_key = self::strip_html( $meta_key );

			return $meta_key;
		}

		public static function sanitize_tag( $tag ) {

			$tag = sanitize_title_with_dashes( $tag, '', 'display' );
			$tag = urldecode( $tag );

			return $tag;
		}

		public static function sanitize_twitter_name( $twitter_name, $prefix_at = true ) {

			if ( '' !== $twitter_name ) {

				$twitter_name = substr( preg_replace( array( '/^.*\//', '/[^a-zA-Z0-9_]/' ), '', $twitter_name ), 0, 15 );

				if ( ! empty( $twitter_name ) && $prefix_at )  {

					$twitter_name = '@' . $twitter_name;
				}
			}

			return $twitter_name;
		}

		/*
		 * Note that an empty string or a null is sanitized as false.
		 *
		 * Used by the wpssorrssb_get_sharing_buttons() function and the WpssoRrssbShortcodeSharing->do_shortcode() method.
		 */
		public static function sanitize_use_post( $mixed, $default = true ) {

			if ( is_array( $mixed ) ) {

				$use_post = isset( $mixed[ 'use_post' ] ) ? $mixed[ 'use_post' ] : $default;

			} elseif ( is_object( $mixed ) ) {

				$use_post = isset( $mixed->use_post ) ? $mixed->use_post : $default;

			} else $use_post = $mixed;

			if ( empty( $use_post ) || 'false' === $use_post ) {	// 0, false, or 'false'

				return false;

			} elseif ( is_numeric( $use_post ) ) {

				return (int) $use_post;
			}

			return $default;
		}

		/*
		 * TEXT HANDLING METHODS:
		 *
		 *	append_url_fragment()
		 *	decode_html()
		 *	decode_utf8()
		 *	encode_html_emoji()
		 *	decode_url_add_query()
		 *	encode_utf8()
		 *	esc_url_encode()
		 *	explode_csv()
		 *	get_bool()
		 *	get_bool_string()
		 *	get_const()
		 *	get_file_path_locale()
		 *	get_json_scripts()
		 *	get_mod_css_id()
		 *	get_mod_salt()
		 *	get_prot()
		 *	get_stripped_php()
		 *	get_request_value()
		 *	get_url()
		 *	get_use_post_string()
		 *	insert_html_tag_attributes()
		 *	mb_convert_encoding_ucs2_utf8()
		 *	mb_decode_numericentity_utf8()
		 *	minify_css()
		 *	replace_unicode_escape()
		 *	restore_checkboxes()
		 *	safe_error_log()
		 *	strip_html()
		 *	strip_shortcodes()
		 *	strnatcasecmp_remove_accents()
		 *	unparse_url()
		 *	unquote_csv_value()
		 *	update_prot()
		 *
		 * Add a fragment to a URL.
		 */
		public static function append_url_fragment( $url, $fragment ) {

			return preg_replace( '/#.*/', '', $url ) . ( empty( $fragment ) ? '' : '#' . self::sanitize_css_id( $fragment ) );
		}

		/*
		 * Decode HTML entities and UTF8 encoding.
		 */
		public static function decode_html( $encoded ) {

			if ( false === strpos( $encoded, '&' ) ) {

				return $encoded;
			}

			static $charset = null;

			if ( null === $charset  ) {

				if ( function_exists( 'get_bloginfo' ) ) {

					$charset = get_bloginfo( $show = 'charset', $filter = 'raw' );
				}
			}

			return html_entity_decode( self::decode_utf8( $encoded ), ENT_QUOTES, $charset );
		}

		public static function decode_url_add_query( $url, array $args ) {

			if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {	// Invalid URL.

				return false;
			}

			$parsed_url = wp_parse_url( self::decode_html( urldecode( $url ) ) );

			if ( empty( $parsed_url ) ) {

				return false;
			}

			if ( empty( $parsed_url[ 'query' ] ) ) {

				$parsed_url[ 'query' ] = http_build_query( $args );

			} else $parsed_url[ 'query' ] .= '&' . http_build_query( $args );

			$url = self::unparse_url( $parsed_url );

			return $url;
		}

		public static function decode_utf8( $encoded ) {

			if ( false === strpos( $encoded, '&#' ) ) {

				return $encoded;
			}

			$encoded = preg_replace( '/&#8230;/', '...', $encoded );

			if ( function_exists( 'mb_decode_numericentity' ) ) {	// Just in case.

				return preg_replace_callback( '/&#\d{2,5};/u', array( __CLASS__, 'mb_decode_numericentity_utf8' ), $encoded );
			}

			return $encoded;
		}

		public static function encode_html_emoji( $content ) {

			static $charset = null;

			if ( null === $charset ) {

				if ( function_exists( 'get_bloginfo' ) ) {

					$charset = get_bloginfo( $show = 'charset', $filter = 'raw' );
				}
			}

			$content = htmlentities( $content, ENT_QUOTES, $charset, $double_encode = false );

			if ( function_exists( 'wp_encode_emoji' ) ) {

				$content = wp_encode_emoji( $content );
			}

			return $content;
		}

		public static function encode_utf8( $decoded ) {

			$encoded = $decoded;

			if ( function_exists( 'mb_detect_encoding' ) ) {	// Just in case.

				if ( mb_detect_encoding( $decoded, 'UTF-8') !== 'UTF-8' ) {

					if ( function_exists( 'utf8_encode' ) ) {

						$encoded = utf8_encode( $decoded );
					}
				}
			}

			return $encoded;
		}

		public static function esc_url_encode( $url, $esc_url = true ) {

			$decoded_url = self::decode_html( $url );	// Decode HTML entities.
			$encoded_url = urlencode( $esc_url ? esc_url_raw( $decoded_url ) : $decoded_url );
			$replace     = array( '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F',
				'%3F', '%25', '%23', '%5B', '%5D' );
			$allowed     = array( '!', '*', '\'', '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']' );

			return str_replace( $replace, $allowed, $encoded_url );
		}

		public static function explode_csv( $str ) {

			if ( empty( $str ) ) {

				return array();
			}

			return array_map( array( __CLASS__, 'unquote_csv_value' ), explode( ',', $str ) );
		}

		/*
		 * Maybe convert string to boolean.
		 *
		 * See WpssoFaqShortcodeQuestion->do_shortcode().
		 */
		public static function get_bool( $mixed ) {

			return is_string( $mixed ) ? filter_var( $mixed, FILTER_VALIDATE_BOOLEAN ) : (bool) $mixed;
		}

		public static function get_bool_string( $mixed ) {

			if ( false === $mixed ) {

				return 'false';

			} elseif ( true === $mixed ) {

				return 'true';

			} elseif ( null === $mixed ) {

				return 'null';
			}

			return (string) $mixed;
		}

		/*
		 * Return a constant value, or $undef if not defined.
		 */
		public static function get_const( $const, $undef = null ) {

			return defined( $const ) ? constant( $const ) : $undef;
		}

		/*
		 * Check for a localized file name.
		 */
		public static function get_file_path_locale( $file_path ) {

			if ( ! class_exists( 'SucomUtilWP' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-wp.php';
			}

			if ( preg_match( '/^(.*)(\.[a-z0-9]+)$/', $file_path, $matches ) ) {

				if ( ! empty( $matches[ 2 ] ) ) {	// Just in case.

					$file_path_locale = $matches[ 1 ] . '-' . SucomUtilWP::get_locale() . $matches[ 2 ];

					if ( file_exists( $file_path_locale ) ) {

						$file_path = $file_path_locale;
					}
				}
			}

			return $file_path;
		}

		public static function get_json_scripts( $html, $do_decode = true ) {

			/*
			 * Remove containers that should not include json scripts.
			 *
			 * i = Letters in the pattern match both upper and lower case letters.
			 * m = The "^" and "$" constructs match newlines and the complete subject string.
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 * U = Invert greediness of quantifiers, so they are NOT greedy by default, but become greedy if followed by ?.
			 *
			 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
			 */
			$html = preg_replace( '/<!--.*-->/Ums', '', $html );
			$html = preg_replace( '/<pre[ >].*<\/pre>/Uims', '', $html );
			$html = preg_replace( '/<textarea[ >].*<\/textarea>/Uims', '', $html );

			$json_scripts = array();

			/*
			 * i = Letters in the pattern match both upper and lower case letters.
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
			 *
			 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
			 */
			if ( preg_match_all( '/(<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>)(.+)(<\/script>)/Uis', $html, $all_matches, PREG_SET_ORDER ) ) {

				foreach ( $all_matches as $num => $matches ) {

					$json_data = json_decode( $matches[ 2 ], $assoc = true );

					if ( preg_match( '/ id=["\']([^"\']+)["\']/', $matches[ 1 ], $m ) ) {

						$single_id = 'id:' . $m[ 1 ];

					} else $single_id = 'md5:' . md5( serialize( $json_data ) );	// md5() input must be a string.

					if ( $do_decode ) {	// Return the decoded json data.

						if ( is_array( $json_data ) ) {

							$json_scripts[ $single_id ] = $json_data;

						} else {

							$error_pre = sprintf( '%s error:', __METHOD__ );

							$error_msg = sprintf( 'Error decoding json script: %s', print_r( $matches[ 2 ], true ) );

							self::safe_error_log( $error_pre . ' ' . $error_msg );
						}

					} else {	// Return the json script instead.

						$json_scripts[ $single_id ] = $matches[ 0 ];
					}
				}
			}

			return $json_scripts;
		}

		public static function get_mod_css_id( array $mod ) {

			$css_id = self::get_mod_salt( $mod );	// Does not include the page number or locale.
			$css_id = self::sanitize_css_id( $css_id );

			return $css_id;
		}

		/*
		 * A cache salt string based on the $mod array. If $mod is not an array, then use the canonical URL value.
		 *
		 * Note that the page number, sort order, locale, and amp check are added to the cache index not the salt string.
		 *
		 * Example mod salts:
		 *
		 * 	'post:123'
		 * 	'term:456_tax:post_tag'
		 * 	'post:0_url:https://example.com/a-subject/'
		 * 	'url:https://example.com/2022/01/'
		 */
		public static function get_mod_salt( $mod = false, $canonical_url = false ) {

			$mod_salt = '';

			if ( is_array( $mod ) ) {

				if ( ! empty( $mod[ 'name' ] ) ) {

					$mod_salt .= '_' . $mod[ 'name' ] . ':';

					if ( is_bool( $mod[ 'id' ] ) ) {

						$mod_salt .= $mod[ 'id' ] ? 'true' : 'false';

					} elseif ( is_numeric( $mod[ 'id' ] ) ) {	// Just in case.

						$mod_salt .= $mod[ 'id' ];
					}
				}

				if ( ! empty( $mod[ 'tax_slug' ] ) ) {

					$mod_salt .= '_tax:' . $mod[ 'tax_slug' ];
				}

				if ( ! is_numeric( $mod[ 'id' ] ) || ! $mod[ 'id' ] > 0 ) {

					if ( ! empty( $mod[ 'is_home' ] ) ) {	// Home page (static or blog archive).

						$mod_salt .= '_home:true';
					}

					if ( ! empty( $canonical_url ) ) {

						$mod_salt .= '_url:' . $canonical_url;
					}
				}

			} elseif ( ! empty( $canonical_url ) ) {

				$mod_salt .= '_url:' . $canonical_url;
			}

			$mod_salt = ltrim( $mod_salt, '_' );	// Remove leading underscore.

			return apply_filters( 'sucom_mod_salt', $mod_salt, $canonical_url );
		}

		public static function get_prot( $url = '' ) {

			if ( $url ) {

				return self::is_https( $url ) ? 'https' : 'http';

			} elseif ( self::is_https() ) {

				return 'https';

			} elseif ( is_admin() )  {

				if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {

					return 'https';
				}

			} elseif ( defined( 'FORCE_SSL' ) && FORCE_SSL ) {

				return 'https';
			}

			return 'http';
		}

		/*
		 * $method = 'ANY' uses the current request method.
		 */
		public static function get_request_value( $key, $method = 'ANY', $default = '' ) {

			switch ( 'ANY' === $method ? $_SERVER[ 'REQUEST_METHOD' ] : $method ) {

				case 'POST':

					if ( isset( $_POST[ $key ] ) ) {

						return sanitize_text_field( $_POST[ $key ] );
					}

					break;

				case 'GET':

					if ( isset( $_GET[ $key ] ) ) {

						return sanitize_text_field( $_GET[ $key ] );
					}

					break;
			}

			return $default;
		}

		public static function get_stripped_php( $file_path ) {

			$stripped_php = '';

			if ( file_exists( $file_path ) ) {

				$content = file_get_contents( $file_path );

				$comments = array( T_COMMENT );

				if ( defined( 'T_DOC_COMMENT' ) ) {

					$comments[] = T_DOC_COMMENT;	// PHP 5.
				}

				if ( defined( 'T_ML_COMMENT' ) ) {

					$comments[] = T_ML_COMMENT;	// PHP 4.
				}

				$tokens = token_get_all( $content );

				foreach ( $tokens as $token ) {

					if ( is_array( $token ) ) {

						if ( in_array( $token[ 0 ], $comments ) ) {

							continue;
						}

						$token = $token[ 1 ];
					}

					$stripped_php .= $token;
				}

			} else $stripped_php = false;

			return $stripped_php;
		}

		/*
		 * Get the current $_SERVER[ 'REQUEST_URI' ] URL, optionally with tracking and advertising query args removed.
		 */
		public static function get_url( $clean_args = true ) {

			static $local_cache = array();

			$cache_idx = $clean_args ? 0 : 1;	// Converts null to 0.

			if ( isset( $local_cache[ $cache_idx ] ) ) {

				return $local_cache[ $cache_idx ];
			}

			$base_url = preg_replace( '/^(.*\/\/[^\/]*).*$/', '$1', get_home_url() );

			$local_cache[ $cache_idx ] = esc_url_raw( $base_url . $_SERVER[ 'REQUEST_URI' ] );

			if ( $clean_args ) {	// Remove tracking and advertising query args.

				$query_args = apply_filters( 'sucom_url_clean_query_args', self::$url_clean_query );

				if ( ! empty( $query_args ) ) {	// Just in case.

					$query_args = array_flip( $query_args );	// Move values to keys.
					$query_args = array_fill_keys( array_keys( $query_args ), false );	// Set all values to false.

					$local_cache[ $cache_idx ] = add_query_arg( $query_args, $local_cache[ $cache_idx ] );	// Remove keys with false values.
				}
			}

			return $local_cache[ $cache_idx ];
		}

		public static function get_use_post_string( $use_post ) {

			$use_post = self::sanitize_use_post( $use_post );

			return self::get_bool_string( $use_post );
		}

		/*
		 * Add one or more attributes to the HTML tag.
		 *
		 * Example HTML tag:
		 *
		 *	$html argument = '<img src="/image.jpg">
		 *	$html returned = '<img src="/image.jpg" data-pin-nopin="nopin">'
		 */
		public static function insert_html_tag_attributes( $html, array $attr_names_values ) {

			foreach ( $attr_names_values as $attr_name => $attr_value ) {

				if ( false !== $attr_value && false === strpos( $html, ' ' . $attr_name . '=' ) ) {

					$html = preg_replace( '/ *\/?' . '>/', ' ' . $attr_name . '="' . $attr_value . '"$0', $html );
				}
			}

			return $html;
		}

		public static function mb_convert_encoding_ucs2_utf8( array $match ) {

			return mb_convert_encoding( pack( 'H*', $match[ 1 ] ), $to_encoding = 'UTF-8', $from_encoding = 'UCS-2' );
		}

		public static function mb_decode_numericentity_utf8( array $match ) {

			$convmap = array( 0x0, 0x10000, 0, 0xfffff );

			return mb_decode_numericentity( $match[ 0 ], $convmap, 'UTF-8' );
		}

		public static function minify_css( $css_data, $filter_prefix = 'sucom' ) {

			if ( ! empty( $css_data ) ) {	// Make sure we have something to minify.

				$classname = apply_filters( $filter_prefix . '_load_lib', false, 'ext/compressor', 'SuextMinifyCssCompressor' );

				if ( 'SuextMinifyCssCompressor' === $classname && class_exists( $classname ) ) {

					$css_data = $classname::process( $css_data );
				}
			}

			return $css_data;
		}

		/*
		 * Decode Facebook video URLs.
		 */
		public static function replace_unicode_escape( $str ) {

			if ( function_exists( 'mb_convert_encoding' ) ) {

				return preg_replace_callback( '/\\\\u([0-9a-f]{4})/i', array( __CLASS__, 'mb_convert_encoding_ucs2_utf8' ), $str );
			}

			return $str;
		}

		/*
		 * Unchecked checkboxes are not submitted, so re-create them based on hidden form values.
		 */
		public static function restore_checkboxes( $opts ) {

			if ( is_array( $opts ) ) {	// Just in case.

				$checkbox = self::preg_grep_keys( '/^is_checkbox_/', $opts, $invert = false, $replace = '' );

				foreach ( $checkbox as $key => $val ) {

					if ( ! array_key_exists( $key, $opts ) ) {

						$opts[ $key ] = 0;	// Add missing checkbox as empty.
					}

					unset( $opts[ 'is_checkbox_' . $key] );
				}
			}

			return $opts;
		}

		public static function safe_error_log( $error_msg, $strip_html = false ) {

			$ini_safe = array(
				'display_errors' => 0,
				'log_errors'     => 1,
				'error_log'      => defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && WP_DEBUG_LOG ?
					WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log',
			);

			$ini_orig = array();

			foreach ( $ini_safe as $name => $value ) {

				$value_orig = ini_get( $name );	// Returns false on failure.

				if ( $value !== $value_orig ) {

					if ( false !== ini_set( $name, $value ) ) {	// Returns false on failure.

						$ini_orig[ $name ] = $value_orig;
					}
				}
			}

			if ( $strip_html ) {

				$error_msg = self::strip_html( $error_msg );
			}

			/*
			 * Use error_log() instead of trigger_error() to avoid HTTP 500.
			 */
			error_log( $error_msg );

			foreach ( $ini_orig as $name => $value_orig ) {

				/*
				 * Failed getting the original value.
				 */
				if ( false === $value_orig ) {

					ini_restore( $name );

				/*
				 * PHP does not allow setting an empty 'error_log' when ini_get( 'open_basedir' ) is true.
				 */
				} elseif ( 'error_log' === $name && empty( $value_orig ) && ini_get( 'open_basedir' ) ) {

					ini_restore( $name );

				} else ini_set( $name, $value_orig );
			}
		}

		public static function strip_html( $text ) {

			/*
			 * i = Letters in the pattern match both upper and lower case letters.
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 * U = Invert greediness of quantifiers, so they are NOT greedy by default, but become greedy if followed by ?.
			 *
			 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
			 */
			$text = self::strip_shortcodes( $text );						// Remove any remaining shortcodes.
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );					// Put everything on one line.
			$text = preg_replace( '/<\?.*\?' . '>/U', ' ', $text );					// Remove php.
			$text = preg_replace( '/<script\b[^>]*>(.*)<\/script>/Ui', ' ', $text );		// Remove javascript.
			$text = preg_replace( '/<style\b[^>]*>(.*)<\/style>/Ui', ' ', $text );			// Remove inline stylesheets.
			$text = preg_replace( '/([\w])<\/(button|dt|h[0-9]+|li|th)>/i', '$1. ', $text );	// Add missing dot to buttons, headers, lists, etc.
			$text = preg_replace( '/(<p>|<p[^>]+>|<\/p>)/i', ' ', $text );				// Replace paragraph tags with a space.
			$text = trim( strip_tags( $text ) );							// Strip HTML and PHP tags from a string.
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );				// Replace 1+ spaces to a single space.

			return trim( $text );
		}

		/*
		 * Remove registered shortcodes from the content, along with some known unregistered shortcodes.
		 */
		public static function strip_shortcodes( $content ) {

			if ( false === strpos( $content, '[' ) ) {	// Optimize and check if there are shortcodes.

				return $content;
			}

			if ( function_exists( 'strip_shortcodes' ) ) {

				$content = strip_shortcodes( $content );	// Remove registered shortcodes.

				if ( false === strpos( $content, '[' ) ) {	// Stop here if there are no more shortcodes.

					return $content;
				}
			}

			$shortcodes_preg = array( '/\[\/?(cs_element_|et_|fusion_|mk_|rev_slider_|vc_)[^\]]+\]/' );

			$content = preg_replace( $shortcodes_preg, ' ', $content );

			return $content;
		}

		protected static function strnatcasecmp_remove_accents( $a, $b ) {

			return strnatcasecmp( remove_accents( $a ), remove_accents( $b ) );
		}

		public static function unparse_url( $parsed_url ) {

			$scheme   = isset( $parsed_url[ 'scheme' ] )   ? $parsed_url[ 'scheme' ] . '://' : '';
			$user     = isset( $parsed_url[ 'user' ] )     ? $parsed_url[ 'user' ] : '';
			$pass     = isset( $parsed_url[ 'pass' ] )     ? ':' . $parsed_url[ 'pass' ]  : '';
			$host     = isset( $parsed_url[ 'host' ] )     ? $parsed_url[ 'host' ] : '';
			$port     = isset( $parsed_url[ 'port' ] )     ? ':' . $parsed_url[ 'port' ] : '';
			$path     = isset( $parsed_url[ 'path' ] )     ? $parsed_url[ 'path' ] : '';
			$query    = isset( $parsed_url[ 'query' ] )    ? '?' . $parsed_url[ 'query' ] : '';
			$fragment = isset( $parsed_url[ 'fragment' ] ) ? '#' . $parsed_url[ 'fragment' ] : '';

			return $scheme . $user . $pass . ( $user || $pass ? '@' : '' ) . $host . $port . $path . $query . $fragment;
		}

		protected static function unquote_csv_value( $val ) {

			return trim( $val, '\'" ' );	// Remove quotes and spaces.
		}

		public static function update_prot( $url = '' ) {

			if ( 0 === strpos( $url, '/' ) ) {	// Skip relative URLs.

				return $url;
			}

			$prot_slash = self::get_prot() . '://';

			if ( 0 === strpos( $url, $prot_slash ) ) {	// Skip correct URLs.

				return $url;
			}

			return preg_replace( '/^([a-z]+:\/\/)/', $prot_slash, $url );
		}

		/*
		 * DEPRECATED METHODS:
		 *
		 * Deprecated on 2024/01/09.
		 */
		public static function add_title_part( &$title, $title_sep, $part, $hand = 'right' ) {

			if ( $part ) {	// Adding an empty $part would leave a hanging separator.

				if ( $title ) {

					if ( 'left' === $hand ) {

						$title = $part . ' ' . trim( $title_sep . ' ' . $title );

					} else $title = trim( $title . ' ' . $title_sep ) . ' ' . $part;

				} else $title = $part;	// We have a part, but no title.
			}

			return trim( $title );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function array_fifo( array $array, $max = 1 ) {

			return self::array_slice_fifo( $array, $max );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function array_or_key( $arr, $key = false, $add_none = false ) {

			return self::get_array_or_element( $arr, $key, $add_none );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function clear_locale_cache() {

			return SucomUtilWP::clear_locale_cache();
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function count_diff( array $arr, $max = 0 ) {

			return self::array_count_diff( $arr, $max );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_available_feed_locale_names() {

			return SucomUtilWP::get_available_feed_locale_names();
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_available_locale_names() {

			return SucomUtilWP::get_available_locale_names();
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_available_locales() {

			return SucomUtilWP::get_available_locales();
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_cache_index( $mixed = 'current' ) {

			$cache_index = '';

			if ( is_array( $mixed ) ) {

				if ( ! empty( $mixed[ 'query_vars' ][ 'order' ] ) ) {

					$cache_index .= '_order:' . $mixed[ 'query_vars' ][ 'order' ];
				}

				if ( ! empty( $mixed[ 'paged' ] ) && $mixed[ 'paged' ] > 1 ) {

					$cache_index .= '_paged:' . $mixed[ 'paged' ];
				}
			}

			$cache_index .= '_locale:' . SucomUtilWP::get_locale( $mixed );

			if ( SucomUtilWP::is_amp() ) {

				$cache_index .= '_amp:true';
			}

			foreach ( array( 'is_embed' => 'embed:true' ) as $function => $index_val ) {

				if ( function_exists( 'is_embed' ) && $function() ) {

					$cache_index .= '_' . $index_val;
				}
			}

			return apply_filters( 'sucom_cache_index', trim( $cache_index, '_' ), $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_column_rows( array $table_cells, $row_cols = 2 ) {

			sort( $table_cells );

			$per_col = ceil( count( $table_cells ) / $row_cols );

			$table_rows = array();

			foreach ( $table_cells as $num => $cell ) {

				if ( empty( $table_rows[ $num % $per_col ] ) ) {	// Initialize the array element.

					$table_rows[ $num % $per_col ] = '';
				}

				$table_rows[ $num % $per_col ] .= $cell;	// Create the html for each row.
			}

			return $table_rows;
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_dist_name( $name, $pkg ) {

			if ( false !== strpos( $name, $pkg ) ) {

				$name = preg_replace( '/^(.*) ' . $pkg . '( [\[\(].+[\)\]])?$/U', '$1$2', $name );
			}

			return preg_replace( '/^(.*)( [\[\(].+[\)\]])?$/U', '$1 ' . $pkg . '$2', $name );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_home_url( array $opts = array(), $mixed = 'current' ) {

			return SucomUtilWP::get_home_url( $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_locale( $mixed = 'current', $read_cache = true ) {

			return SucomUtilWP::get_locale( $mixed, $read_cache );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_minimum_image_wh() {

			return SucomUtilWP::get_minimum_image_wh();
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_options_label_transl( array $opts, $text_domain ) {

			return self::get_opts_labels_transl( $opts, $text_domain );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_options_value_transl( array $opts, $text_domain ) {

			return self::get_opts_values_transl( $opts, $text_domain );
		}

		/*
		 * Deprecated on 2024/01/13.
		 */
		public static function get_opts_label_transl( array $opts, $text_domain ) {

			return self::get_opts_labels_transl( $opts, $text_domain );
		}

		/*
		 * Deprecated on 2024/01/13.
		 */
		public static function get_opts_value_transl( array $opts, $text_domain ) {

			return self::get_opts_values_transl( $opts, $text_domain );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_post_object( $use_post = false, $output = 'object' ) {

			return SucomUtilWP::get_post_object( $use_post, $output );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_post_type_archives( $output = 'objects', $sort = false, $args = null ) {

			return SucomUtilWP::get_post_type_archives( $output, $sort, $args );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_post_type_archive_labels( $val_prefix = '', $label_prefix = '' ) {

			return SucomUtilWP::get_post_type_archive_labels( $val_prefix, $label_prefix );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_post_type_labels( $val_prefix = '', $label_prefix = '', $objects = null ) {

			return SucomUtilWP::get_post_type_labels( $val_prefix, $label_prefix, $objects );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_post_types( $output = 'objects', $sort = false, $args = null ) {

			return SucomUtilWP::get_post_types( $output, $sort, $args );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_roles_users_select( array $roles, $blog_id = null, $add_none = true ) {

			return SucomUtilWP::get_roles_users_select( $roles, $blog_id, $add_none );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_screen_id( $screen = false ) {

			return SucomUtilWP::get_screen_id( $screen );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_site_name( array $opts = array(), $mixed = 'current' ) {

			return SucomUtilWP::get_site_name( $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_site_name_alt( array $opts, $mixed = 'current' ) {

			return SucomUtilWP::get_site_name_alt( $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_site_description( array $opts = array(), $mixed = 'current' ) {

			return SucomUtilWP::get_site_description( $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_taxonomies( $output = 'objects', $sort = false, $args = null ) {

			return SucomUtilWP::get_taxonomies( $output, $sort, $args );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_taxonomy_labels( $val_prefix = '', $label_prefix = '', $objects = null ) {

			return SucomUtilWP::get_taxonomy_labels( $val_prefix, $label_prefix, $objects );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_text_reading_mins( $text, $words_per_min = 200 ) {

			return round( str_word_count( wp_strip_all_tags( $text ) ) / $words_per_min );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_transient_array( $cache_id ) {

			return get_transient( $cache_id );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_user_object( $user_id = 0, $output = 'object' ) {

			return SucomUtilWP::get_user_object( $user_id, $output );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function get_wp_url( array $opts = array(), $mixed = 'current' ) {

			return SucomUtilWP::get_wp_url( $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_post_page( $use_post = false ) {

			return SucomUtilWP::is_post_page( $use_post );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_amp() {

			return SucomUtilWP::is_amp();
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_home_page( $use_post = false ) {

			return SucomUtilWP::is_home_page( $use_post );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_home_posts( $use_post = false ) {

			return SucomUtilWP::is_home_posts( $use_post );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_mod_current_screen( array $mod ) {

			return SucomUtilWP::is_mod_screen_obj( $mod );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_mod_post_type( array $mod, $post_type ) {

			return SucomUtilWP::is_mod_post_type( $mod, $post_type );
		}

		/*
		 * Deprecated on 2024/01/13.
		 */
		public static function get_multi_key_locale( $prefix, array &$opts, $add_none = false ) {

			return self::get_key_values_multi( $prefix, $opts, $add_none );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_post_type( $post_obj, $post_type ) {

			return SucomUtilWP::is_post_type( $post_obj, $post_type ); 
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_post_type_archive( $post_type_obj, $post_slug ) {

			return SucomUtilWP::is_post_type_archive( $post_type_obj, $post_slug );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_post_type_public( $mixed ) {

			return SucomUtilWP::is_post_type_public( $mixed );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_term_page( $term_id = 0, $tax_slug = '' ) {

			return SucomUtilWP::is_term_page( $term_id, $tax_slug );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_toplevel_edit( $hook_name ) {

			return SucomUtilWP::is_toplevel_edit( $hook_name );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function is_user_page( $user_id = 0 ) {

			return SucomUtilWP::is_user_page( $user_id );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function pretty_array( $mixed, $flatten = false ) {

			self::get_array_pretty( $mixed, $flatten );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function role_exists( $role ) {

			return SucomUtilWP::role_exists( $role );
		}

		/*
		 * Deprecated on 2024/01/09.
		 */
		public static function update_transient_array( $cache_id, $cache_array, $cache_exp_secs ) {

			return SucomUtilWP::update_transient_array( $cache_id, $cache_array, $cache_exp_secs );
		}

		/*
		 * Deprecated on 2024/02/11.
		 */
		public static function get_html_transl( $html, $text_domain ) {

			if ( ! class_exists( 'SucomGetText' ) ) {

				require_once dirname( __FILE__ ) . '/gettext.php';
			}

			return SucomGetText::get_html_transl( $html, $text_domain );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_opts_begin( $opts, $str ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_opts_begin( $opts, $str );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_opts_hm_tz( array $opts, $key_hm, $key_tz = '' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_opts_hm_tz( $opts, $key_hm, $key_tz );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_opts_labels_transl( array $opts, $text_domain ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_opts_labels_transl( $opts, $text_domain );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_opts_values_transl( array $opts, $text_domain ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_opts_values_transl( $opts, $text_domain );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_key_locale( $key, $opts = false, $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_key_locale( $key, $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_key_value( $key, array $opts, $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_key_value( $key, $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function get_key_values_multi( $prefix, array &$opts, $add_none = false ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			return SucomUtilOptions::get_key_values_multi( $prefix, $opts, $add_none );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function set_key_value( $key, $value, array &$opts ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			SucomUtilOptions::set_key_value( $key, $value, $opts );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function set_key_value_disabled( $key, $value, array &$opts ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			SucomUtilOptions::set_key_value_disabled( $key, $value, $opts );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function set_key_value_locale( $key, $value, array &$opts, $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			SucomUtilOptions::set_key_value_locale( $key, $value, $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function set_key_value_locale_disabled( $key, $value, array &$opts, $mixed = 'current' ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			SucomUtilOptions::set_key_value_locale_disabled( $key, $value, $opts, $mixed );
		}

		/*
		 * Deprecated on 2024/04/16.
		 */
		public static function transl_key_values( $pattern, array &$opts, $text_domain ) {

			if ( ! class_exists( 'SucomUtilOptions' ) ) {	// Just in case.

				require_once dirname( __FILE__ ) . '/util-options.php';
			}

			SucomUtilOptions::transl_key_values( $pattern, $opts, $text_domain );
		}
	}
}

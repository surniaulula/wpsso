<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'SucomUtil' ) ) {

	class SucomUtil {

		protected $p;

		protected static $mobile_detect = null;		// SuextMobileDetect object
		protected static $plugins_index = null;		// hash of active site and network plugins
		protected static $site_plugins = null;
		protected static $network_plugins = null;
		protected static $crawler_name = null;		// saved crawler name from user-agent
		protected static $is = array();			// saved return values for is_post/term/user_page() checks

		private static $pub_lang = array(
			// https://www.facebook.com/translations/FacebookLocales.xml
			'facebook' => array(
				'af_ZA' => 'Afrikaans',
				'sq_AL' => 'Albanian',
				'ar_AR' => 'Arabic',
				'hy_AM' => 'Armenian',
				'az_AZ' => 'Azerbaijani',
				'eu_ES' => 'Basque',
				'be_BY' => 'Belarusian',
				'bn_IN' => 'Bengali',
				'bs_BA' => 'Bosnian',
				'bg_BG' => 'Bulgarian',
				'ca_ES' => 'Catalan',
				'zh_HK' => 'Chinese (Hong Kong)',
				'zh_CN' => 'Chinese (Simplified)',
				'zh_TW' => 'Chinese (Traditional)',
				'hr_HR' => 'Croatian',
				'cs_CZ' => 'Czech',
				'da_DK' => 'Danish',
				'nl_NL' => 'Dutch',
				'en_GB' => 'English (UK)',
				'en_PI' => 'English (Pirate)',
				'en_UD' => 'English (Upside Down)',
				'en_US' => 'English (US)',
				'eo_EO' => 'Esperanto',
				'et_EE' => 'Estonian',
				'fo_FO' => 'Faroese',
				'tl_PH' => 'Filipino',
				'fi_FI' => 'Finnish',
				'fr_CA' => 'French (Canada)',
				'fr_FR' => 'French (France)',
				'fy_NL' => 'Frisian',
				'gl_ES' => 'Galician',
				'ka_GE' => 'Georgian',
				'de_DE' => 'German',
				'el_GR' => 'Greek',
				'he_IL' => 'Hebrew',
				'hi_IN' => 'Hindi',
				'hu_HU' => 'Hungarian',
				'is_IS' => 'Icelandic',
				'id_ID' => 'Indonesian',
				'ga_IE' => 'Irish',
				'it_IT' => 'Italian',
				'ja_JP' => 'Japanese',
				'km_KH' => 'Khmer',
				'ko_KR' => 'Korean',
				'ku_TR' => 'Kurdish',
				'la_VA' => 'Latin',
				'lv_LV' => 'Latvian',
				'fb_LT' => 'Leet Speak',
				'lt_LT' => 'Lithuanian',
				'mk_MK' => 'Macedonian',
				'ms_MY' => 'Malay',
				'ml_IN' => 'Malayalam',
				'ne_NP' => 'Nepali',
				'nb_NO' => 'Norwegian (Bokmal)',
				'nn_NO' => 'Norwegian (Nynorsk)',
				'ps_AF' => 'Pashto',
				'fa_IR' => 'Persian',
				'pl_PL' => 'Polish',
				'pt_BR' => 'Portuguese (Brazil)',
				'pt_PT' => 'Portuguese (Portugal)',
				'pa_IN' => 'Punjabi',
				'ro_RO' => 'Romanian',
				'ru_RU' => 'Russian',
				'sk_SK' => 'Slovak',
				'sl_SI' => 'Slovenian',
				'es_LA' => 'Spanish',
				'es_ES' => 'Spanish (Spain)',
				'sr_RS' => 'Serbian',
				'sw_KE' => 'Swahili',
				'sv_SE' => 'Swedish',
				'ta_IN' => 'Tamil',
				'te_IN' => 'Telugu',
				'th_TH' => 'Thai',
				'tr_TR' => 'Turkish',
				'uk_UA' => 'Ukrainian',
				'vi_VN' => 'Vietnamese',
				'cy_GB' => 'Welsh',
			),
			// https://developers.google.com/+/web/api/supported-languages
			'google' => array(
				'af'	=> 'Afrikaans',
				'am'	=> 'Amharic',
				'ar'	=> 'Arabic',
				'eu'	=> 'Basque',
				'bn'	=> 'Bengali',
				'bg'	=> 'Bulgarian',
				'ca'	=> 'Catalan',
				'zh-HK'	=> 'Chinese (Hong Kong)',
				'zh-CN'	=> 'Chinese (Simplified)',
				'zh-TW'	=> 'Chinese (Traditional)',
				'hr'	=> 'Croatian',
				'cs'	=> 'Czech',
				'da'	=> 'Danish',
				'nl'	=> 'Dutch',
				'en-GB'	=> 'English (UK)',
				'en-US'	=> 'English (US)',
				'et'	=> 'Estonian',
				'fil'	=> 'Filipino',
				'fi'	=> 'Finnish',
				'fr'	=> 'French',
				'fr-CA'	=> 'French (Canadian)',
				'gl'	=> 'Galician',
				'de'	=> 'German',
				'el'	=> 'Greek',
				'gu'	=> 'Gujarati',
				'iw'	=> 'Hebrew',
				'hi'	=> 'Hindi',
				'hu'	=> 'Hungarian',
				'is'	=> 'Icelandic',
				'id'	=> 'Indonesian',
				'it'	=> 'Italian',
				'ja'	=> 'Japanese',
				'kn'	=> 'Kannada',
				'ko'	=> 'Korean',
				'lv'	=> 'Latvian',
				'lt'	=> 'Lithuanian',
				'ms'	=> 'Malay',
				'ml'	=> 'Malayalam',
				'mr'	=> 'Marathi',
				'no'	=> 'Norwegian',
				'fa'	=> 'Persian',
				'pl'	=> 'Polish',
				'pt-BR'	=> 'Portuguese (Brazil)',
				'pt-PT'	=> 'Portuguese (Portugal)',
				'ro'	=> 'Romanian',
				'ru'	=> 'Russian',
				'sr'	=> 'Serbian',
				'sk'	=> 'Slovak',
				'sl'	=> 'Slovenian',
				'es'	=> 'Spanish',
				'es-419'	=> 'Spanish (Latin America)',
				'sw'	=> 'Swahili',
				'sv'	=> 'Swedish',
				'ta'	=> 'Tamil',
				'te'	=> 'Telugu',
				'th'	=> 'Thai',
				'tr'	=> 'Turkish',
				'uk'	=> 'Ukrainian',
				'ur'	=> 'Urdu',
				'vi'	=> 'Vietnamese',
				'zu'	=> 'Zulu',
			),
			'pinterest' => array(
				'en'	=> 'English',
				'ja'	=> 'Japanese',
			),
			// https://www.tumblr.com/docs/en/share_button
			'tumblr' => array(
				'en_US' => 'English',
				'de_DE' => 'German',
				'fr_FR' => 'French',
				'it_IT' => 'Italian',
				'ja_JP' => 'Japanese',
				'tr_TR' => 'Turkish',
				'es_ES' => 'Spanish',
				'ru_RU' => 'Russian',
				'pl_PL' => 'Polish',
				'pt_PT' => 'Portuguese (PT)',
				'pt_BR' => 'Portuguese (BR)',
				'nl_NL' => 'Dutch',
				'ko_KR' => 'Korean',
				'zh_CN' => 'Chinese (Simplified)',
				'zh_TW' => 'Chinese (Traditional)',
			),
			// https://dev.twitter.com/web/overview/languages
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

		public function __construct() {
		}

		public static function is_https( $url = '' ) {
			if ( ! empty( $url ) ) {
				if ( parse_url( $url, PHP_URL_SCHEME ) === 'https' )
					return true;
				else return false;
			} elseif ( ! empty( $_SERVER['HTTPS'] ) ||
				( is_admin() && self::get_const( 'FORCE_SSL_ADMIN' ) ) )
					return true;
			else return false;
		}

		// returns 'http' or 'https'
		public static function get_prot( $url = '' ) {
			if ( self::is_https( $url ) )
				return 'https';
			else return 'http';
		}

		public static function get_const( $const, $not_found = null ) {
			if ( defined( $const ) )
				return constant( $const );
			else return $not_found;
		}

		// returns false or the admin screen id text string
		public static function get_screen_id( $screen = false ) {
			if ( $screen === false &&
				function_exists( 'get_current_screen' ) )
					$screen = get_current_screen();
			if ( isset( $screen->id ) )
				return $screen->id;
			return false;
		}

		public static function sanitize_hookname( $name ) {
			$name = preg_replace( '/[:\/\-\.]+/', '_', $name );
			return self::sanitize_key( $name );
		}

		public static function sanitize_classname( $name ) {
			$name = preg_replace( '/[:\/\-\.]+/', '', $name );
			return self::sanitize_key( $name );
		}

		public static function sanitize_tag( $tag ) {
			$tag = sanitize_title_with_dashes( $tag, '', 'display' );
			$tag = urldecode( $tag );
			return $tag;
		}

		public static function sanitize_hashtags( $tags = array() ) {
			// truncate tags that start with a number (not allowed)
			return preg_replace( array( '/^[0-9].*/', '/[ \[\]#!\$\?\\\\\/\*\+\.\-\^]/', '/^.+/' ), 
				array( '', '', '#$0' ), $tags );
		}

		public static function array_to_hashtags( $tags = array() ) {
			// array_filter() removes empty array values
			return trim( implode( ' ', array_filter( self::sanitize_hashtags( $tags ) ) ) );
		}

		public static function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
		}

		public static function active_plugins( $key = false ) {
			// create list only once
			if ( self::$plugins_index === null ) {
				$all_plugins = self::$site_plugins = get_option( 'active_plugins', array() );
				if ( is_multisite() ) {
					self::$network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					if ( ! empty( self::$network_plugins ) )
						$all_plugins = array_merge( self::$site_plugins, self::$network_plugins );
				}
				foreach ( $all_plugins as $base )
					self::$plugins_index[$base] = true;
			}
			if ( $key !== false ) {
				if ( isset( self::$plugins_index[$key] ) )
					return self::$plugins_index[$key];
				else return false;
			} else return self::$plugins_index;
		}

		public static function add_site_option_key( $name, $key, $value ) {
			return self::update_option_key( $name, $key, $value, true, true );
		}

		public static function update_site_option_key( $name, $key, $value, $protect = false ) {
			return self::update_option_key( $name, $key, $value, $protect, true );
		}

		// only creates new keys - does not update existing keys
		public static function add_option_key( $name, $key, $value ) {
			return self::update_option_key( $name, $key, $value, true, false );	// $protect = true
		}

		public static function update_option_key( $name, $key, $value, $protect = false, $site = false ) {
			if ( $site === true )
				$opts = get_site_option( $name, array() );
			else $opts = get_option( $name, array() );
			if ( $protect === true && 
				isset( $opts[$key] ) )
					return false;
			$opts[$key] = $value;
			if ( $site === true )
				return update_site_option( $name, $opts );
			else return update_option( $name, $opts );
		}

		public static function get_option_key( $name, $key, $site = false ) {
			if ( $site === true )
				$opts = get_site_option( $name, array() );
			else $opts = get_option( $name, array() );
			if ( isset( $opts[$key] ) )
				return $opts[$key];
			else return null;
		}

		public static function a2aa( $a ) {
			$aa = array();
			foreach ( $a as $i )
				$aa[][] = $i;
			return $aa;
		}

		public static function crawler_name( $is_crawler = '' ) {
			// optimize perf - only check once
			if ( self::$crawler_name === null ) {
				$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ?
					strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
				switch ( true ) {
					// "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
					case ( strpos( $ua, 'facebookexternalhit/' ) === 0 ):
						self::$crawler_name = 'facebook';
						break;

					// "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
					case ( strpos( $ua, 'compatible; googlebot/' ) !== false ):
						self::$crawler_name = 'google';
						break;

					// "Pinterest/0.1 +http://pinterest.com/"
					case ( strpos( $ua, 'pinterest/' ) === 0 ):
						self::$crawler_name = 'pinterest';
						break;

					// "Twitterbot/1.0"
					case ( strpos( $ua, 'twitterbot/' ) === 0 ):
						self::$crawler_name = 'twitter';
						break;

					// "W3C_Validator/1.3 http://validator.w3.org/services"
					case ( strpos( $ua, 'w3c_validator/' ) === 0 ):
						self::$crawler_name = 'w3c';
						break;
					default:
						self::$crawler_name = 'unknown';
						break;
				}
			}
			if ( ! empty( $is_crawler ) )
				return $is_crawler === self::$crawler_name ? true : false;
			else return self::$crawler_name;
		}

		public static function is_assoc( $arr ) {
			if ( ! is_array( $arr ) ) 
				return false;
			return is_numeric( implode( array_keys( $arr ) ) ) ? false : true;
		}

		public static function keys_start_with( $str, array $arr ) {
			$found = array();
			foreach ( $arr as $key => $value ) {
				if ( strpos( $key, $str ) === 0 )
					$found[$key] = $value;
			}
			return $found;
		}

		public static function preg_grep_keys( $preg, array &$arr, $invert = false, $replace = false ) {
			$invert = $invert == false ? 
				null : PREG_GREP_INVERT;
			$match = preg_grep( $preg, array_keys( $arr ), $invert );
			$found = array();
			foreach ( $match as $key ) {
				if ( $replace !== false ) {
					$fixed = preg_replace( $preg, $replace, $key );
					$found[$fixed] = $arr[$key]; 
				} else $found[$key] = $arr[$key]; 
			}
			return $found;
		}

		public static function rename_keys( &$opts = array(), $keys = array() ) {
			foreach ( $keys as $old => $new ) {
				if ( empty( $old ) )	// just in case
					continue;
				if ( isset( $opts[$old] ) ) {
					if ( ! empty( $new ) && 
						! isset( $opts[$new] ) )
							$opts[$new] = $opts[$old];
					unset ( $opts[$old] );
				}
			}
			return $opts;
		}

		public static function next_key( $needle, $arr, $cycle = true ) {
			$keys = array_keys( $arr );
			$pos = array_search( $needle, $keys );
			if ( $pos !== false ) {
				if ( isset( $keys[ $pos + 1 ] ) )
					return $keys[ $pos + 1 ];
				elseif ( $cycle === true )
					return $keys[0];
			}
			return false;
		}

		// deprecated 2016/04/01
		public static function insert_before_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return $array;
		}

		// deprecated 2016/04/01
		public static function insert_after_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return $array;
		}

		public static function before_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'before', $array, $match_key, $mixed, $add_value );
		}

		public static function after_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'after', $array, $match_key, $mixed, $add_value );
		}

		public static function replace_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'replace', $array, $match_key, $mixed, $add_value );
		}

		private static function insert_in_array( $rel_pos, &$array, &$match_key, &$mixed, &$add_value ) {
			if ( array_key_exists( $match_key, $array ) ) {
				$new_array = array();
				foreach ( $array as $key => $value ) {
					if ( $rel_pos === 'after' )
						$new_array[$key] = $value;
					if ( $key === $match_key ) {
						if ( is_array( $mixed ) )
							$new_array = array_merge( $new_array, $mixed );
						elseif ( is_string( $mixed ) )
							$new_array[$mixed] = $add_value;
						else $new_array[] = $add_value;
					}
					if ( $rel_pos === 'before' )
						$new_array[$key] = $value;
				}
				return $new_array;
			}
			return $array;
		}

		public static function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
			$merged = $array1; 
			foreach ( $array2 as $key => &$value ) {
				if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) )
					$merged[$key] = self::array_merge_recursive_distinct( $merged[$key], $value ); 
				else $merged[$key] = $value;
			} 
			return $merged;
		}

		// return the preferred URL (og:image:secure_url, og:image:url, og:image)
		public static function get_mt_media_url( $prefix, &$arr ) {
			foreach ( array( ':secure_url', ':url', '' ) as $suffix )
				if ( ! empty( $arr[$prefix.$suffix] ) )
					return $media_url = $arr[$prefix.$suffix];
			return '';
		}

		public static function get_mt_prop_video( $mt_pre = 'og' ) {
			return array(
				$mt_pre.':video:secure_url' => '',
				$mt_pre.':video:url' => '',
				$mt_pre.':video:type' => 'application/x-shockwave-flash',
				$mt_pre.':video:width' => '',
				$mt_pre.':video:height' => '',
				$mt_pre.':video:tag' => array(),
				$mt_pre.':video:duration' => '',	// non-standard / internal meta tag
				$mt_pre.':video:upload_date' => '',	// non-standard / internal meta tag
				$mt_pre.':video:thumbnail_url' => '',	// non-standard / internal meta tag
				$mt_pre.':video:embed_url' => '',	// non-standard / internal meta tag
				$mt_pre.':video:has_image' => false,	// non-standard / internal meta tag
				$mt_pre.':video:title' => '',		// non-standard / internal meta tag
				$mt_pre.':video:description' => '',	// non-standard / internal meta tag
			);
		}

		// pre-define the array key order for the list() construct (which assigns elements from right to left)
		public static function get_mt_prop_image( $mt_pre = 'og' ) {
			return array(
				$mt_pre.':image:secure_url' => '',
				$mt_pre.':image' => '',
				$mt_pre.':image:width' => '',
				$mt_pre.':image:height' => '',
				$mt_pre.':image:cropped' => '',		// non-standard / internal meta tag
				$mt_pre.':image:id' => '',		// non-standard / internal meta tag
			);
		}

		public static function get_pub_lang( $pub = '' ) {
			switch ( $pub ) {
				case 'fb' :
					return self::$pub_lang['facebook'];
				case 'gplus' :
				case 'googleplus' :
					return self::$pub_lang['google'];
				case 'pin' :
					return self::$pub_lang['pinterest'];
				default:
					if ( isset( self::$pub_lang[$pub] ) )
						return self::$pub_lang[$pub];
					else return array();
			}
		}

		// return the custom site name, and if empty, the default site name
		public static function get_site_name( array &$opts, array &$mod ) {
			self::get_locale_opt( 'og_site_name', $opts, $mod, 
				get_bloginfo( 'name', 'display' ) );
		}

		// return the custom site description, and if empty, the default site description
		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_site_description( array &$opts, array &$mod ) {
			self::get_locale_opt( 'og_site_description', $opts, $mod, 
				get_bloginfo( 'description', 'display' ) );
		}

		// return a localize options value
		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_locale_opt( $key, &$opts, $mixed, $if_empty = null ) {
			$key_locale = self::get_key_locale( $key, $opts, $mixed );
			if ( $if_empty !== null )
				return empty( $opts[$key_locale] ) ? $if_empty : $opts[$key_locale];
			// allow for empty values
			else return isset( $opts[$key_locale] ) ? $opts[$key_locale] : null;
		}

		// localize an options array key
		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_key_locale( $key, &$opts = false, $mixed = 'current' ) {
			$default = self::get_locale( 'default' );
			$locale = self::get_locale( $mixed );
			$key_locale = $key.'#'.$locale;

			// the default language may have changed, so if we're using the default,
			// check for a locale version for the default language
			if ( $locale === $default )
				return isset( $opts[$key_locale] ) ?
					$key_locale : $key;
			else return $key_locale;
		}

		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_locale( $mixed = 'current' ) {
			switch ( true ) {
				case ( $mixed === 'default' ):
					$wp_locale = defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US';
					break;
				default:
					$wp_locale = get_locale();
					break;
			}
			return apply_filters( 'sucom_locale', $wp_locale, $mixed );
		}

		public static function restore_checkboxes( &$opts ) {
			// unchecked checkboxes are not provided, so re-create them here based on hidden values
			$checkbox = self::preg_grep_keys( '/^is_checkbox_/', $opts, false, '' );

			foreach ( $checkbox as $key => $val ) {
				if ( ! array_key_exists( $key, $opts ) )
					$opts[$key] = 0;	// add missing checkbox as empty
				unset ( $opts['is_checkbox_'.$key] );
			}
			return $opts;
		}

		public static function get_is_page( $use_post = false, $use_cache = true ) {

			// optimize and only check what we need to
			$is_term_page = $is_user_page = false;
			if ( ! $is_post_page = self::is_post_page( $use_post, $use_cache ) )
				if ( ! $is_term_page = self::is_term_page( $use_cache ) )
					$is_user_page = self::is_user_page( $use_cache );

			return array(
				'post_page' => $is_post_page,
				'term_page' => $is_term_page,
				'user_page' => $is_user_page
			);
		}

		public static function is_post_page( $use_post = false, $use_cache = true ) {
			if ( $use_cache &&
				isset( self::$is['post_page'][$use_post] ) )
					return self::$is['post_page'][$use_post];

			$ret = false;

			if ( $use_post || is_singular() )
				$ret = true;
			elseif ( is_admin() ) {
				// $screen_id = upload | edit-post | post | edit-page | page
				$screen_id = self::get_screen_id();

				// exclude the post/page/media editing lists
				if ( $screen_id === 'upload' ||
					strpos( $screen_id, 'edit-' ) === 0 )
						$ret = false;
				elseif ( self::get_request_value( 'post_ID', 'POST' ) !== '' ||
					self::get_request_value( 'post', 'GET' ) !== '' )
						$ret = true;
				elseif ( basename( $_SERVER['PHP_SELF'] ) === 'post-new.php' )
					$ret = true;
			}

			$ret = apply_filters( 'sucom_is_post_page', $ret, $use_post );

			if ( $use_cache )
				return self::$is['post_page'][$use_post] = $ret;
			else return $ret;
		}

		// on archives and taxonomies, this will return the first post object
		public function get_post_object( $use_post = false, $output = 'object' ) {
			$post_obj = false;	// return false by default

			if ( is_numeric( $use_post ) ) {
				$post_obj = get_post( $use_post );

			} elseif ( $use_post === false || 
				apply_filters( 'sucom_is_post_page', is_singular(), $use_post ) ) {

				$post_obj = get_queried_object();

				if ( $post_obj === null && is_admin() ) {
					if ( ( $post_id = self::get_request_value( 'post_ID', 'POST' ) ) !== '' ||
						( $post_id = self::get_request_value( 'post', 'GET' ) ) !== '' )
							$post_obj = get_post( $post_id );
				}

				// fallback to $post if object is empty / invalid
				if ( empty( $post_obj->ID ) &&
					isset( $GLOBALS['post'] ) )
						$post_obj = $GLOBALS['post'];

			} elseif ( $use_post === true && 
				isset( $GLOBALS['post'] ) ) {
					$post_obj = $GLOBALS['post'];

			}

			$post_obj = apply_filters( 'sucom_get_post_object', $post_obj, $use_post );

			switch ( $output ) {
				case 'id':
				case 'ID':
					return isset( $post_obj->ID ) ? 
						$post_obj->ID : false;
					break;
				default:
					return is_object( $post_obj ) ?
						$post_obj : false;
					break;
			}
		}

		public static function is_term_page( $use_cache = true ) {
			if ( $use_cache &&
				isset( self::$is['term_page'] ) )
					return self::$is['term_page'];

			$ret = false;

			if ( is_tax() || is_category() || is_tag() )
				$ret = true;
			elseif ( is_admin() ) {
				// $screen_id = edit-category | edit-post_tag | term
				$screen_id = self::get_screen_id();

				if ( self::get_request_value( 'taxonomy' ) !== '' && 
					self::get_request_value( 'tag_ID' ) !== '' )
						$ret = true;
				elseif ( $screen_id === 'term' &&
					self::get_request_value( 'tag_ID' ) !== '' )
						$ret = true;
			}

			$ret = apply_filters( 'sucom_is_term_page', $ret );

			if ( $use_cache )
				return self::$is['term_page'] = $ret;
			else return $ret;
		}

		public static function is_category_page( $use_cache = true ) {
			if ( $use_cache &&
				isset( self::$is['category_page'] ) )
					return self::$is['category_page'];

			$ret = false;

			if ( is_category() )
				$ret = true;
			elseif ( is_admin() ) {
				if ( self::is_term_page() &&
					self::get_request_value( 'taxonomy' ) === 'category' )
						$ret = true;
			}

			$ret = apply_filters( 'sucom_is_category_page', $ret );

			if ( $use_cache )
				return self::$is['category_page'] = $ret;
			else return $ret;
		}

		public static function is_tag_page( $use_cache = true ) {
			if ( $use_cache &&
				isset( self::$is['tag_page'] ) )
					return self::$is['tag_page'];

			$ret = false;

			if ( is_tag() )
				$ret = true;
			elseif ( is_admin() ) {
				if ( self::is_term_page() &&
					self::get_request_value( 'taxonomy' ) === '_tag' )
						$ret = true;
			}

			$ret = apply_filters( 'sucom_is_tag_page', $ret );

			if ( $use_cache )
				return self::$is['tag_page'] = $ret;
			else return $ret;
		}

		public function get_term_object( $term_id = false, $tax_slug = false, $output = 'object' ) {
			$term_obj = false;	// return false by default

			if ( is_numeric( $term_id ) ) {
				$term_obj = get_term_by( 'id', $term_id, $tax_slug, OBJECT, 'raw' );

			} elseif ( apply_filters( 'sucom_is_term_page', is_tax() ) || is_tag() || is_category() ) {
				$term_obj = get_queried_object();

			} elseif ( is_admin() ) {
				if ( ( $tax_slug = self::get_request_value( 'taxonomy' ) ) !== '' &&
					( $term_id = self::get_request_value( 'tag_ID' ) ) !== '' )
						$term_obj = get_term_by( 'id', $term_id, $tax_slug, OBJECT, 'raw' );

			}

			$term_obj = apply_filters( 'sucom_get_term_object', $term_obj, $term_id, $tax_slug );

			switch ( $output ) {
				case 'id':
				case 'ID':
				case 'term_id':
					return isset( $term_obj->term_id ) ? 
						$term_obj->term_id : false;
					break;
				default:
					return is_object( $term_obj ) ? 
						$term_obj : false;
					break;
			}
		}

		public static function is_author_page( $use_cache = true ) {
			return self::is_user_page( $use_cache );
		}

		public static function is_user_page( $use_cache = true ) {
			if ( $use_cache &&
				isset( self::$is['user_page'] ) )
					return self::$is['user_page'];

			$ret = false;

			if ( is_author() ) {
				$ret = true;
			} elseif ( is_admin() ) {
				$screen_id = self::get_screen_id();
				if ( $screen_id !== false ) {
					switch ( $screen_id ) {
						case 'profile':
						case 'user-edit':
						case ( strpos( $screen_id, 'profile_page_' ) === 0 ? true : false ):
						case ( strpos( $screen_id, 'users_page_' ) === 0 ? true : false ):
							$ret = true;
							break;
					}
				}
				if ( $ret === false ) {
					if ( self::get_request_value( 'user_id' ) !== '' )
						$ret = true;
					elseif ( basename( $_SERVER['PHP_SELF'] ) === 'profile.php' )
						$ret = true;
				}
			}

			$ret = apply_filters( 'sucom_is_user_page', $ret );

			if ( $use_cache )
				return self::$is['user_page'] = $ret;
			else return $ret;
		}

		public function get_author_object( $user_id = false, $output = 'object' ) {
			return $this->get_user_object( $user_id, $ret );
		}

		public function get_user_object( $user_id = false, $output = 'object' ) {
			$user_obj = false;	// return false by default

			if ( is_numeric( $user_id ) ) {
				$user_obj = get_userdata( $user_id );

			} elseif ( apply_filters( 'sucom_is_user_page', is_author() ) ) {
				$user_obj = get_query_var( 'author_name' ) ? 
					get_user_by( 'slug', get_query_var( 'author_name' ) ) : 
					get_userdata( get_query_var( 'author' ) );

			} elseif ( is_admin() ) {
				if ( ( $user_id = self::get_request_value( 'user_id' ) ) === '' )
					$user_id = get_current_user_id();
				$user_obj = get_userdata( $user_id );
			}

			$user_obj = apply_filters( 'sucom_get_user_object', $user_obj, $user_id );

			switch ( $output ) {
				case 'id':
				case 'ID':
					return isset( $user_obj->ID ) ? 
						$user_obj->ID : false;
					break;
				default:
					return is_object( $user_obj ) ?
						$user_obj : false;
					break;
			}
		}

		public static function is_product_page( $use_post = false, $product_obj = false ) {
			if ( isset( self::$is['product_page'] ) )
				return self::$is['product_page'];

			$ret = false;

			if ( function_exists( 'is_product' ) && 
				is_product() )
					$ret = true;
			elseif ( is_admin() || is_object( $product_obj ) ) {
				if ( ! is_object( $product_obj ) && 
					! empty( $use_post ) )
						$product_obj = get_post( $use_post );
				if ( isset( $product_obj->post_type ) &&
					$product_obj->post_type === 'product' )
						$ret = true;
			}

			return self::$is['product_page'] = apply_filters( 'sucom_is_product_page', $ret, $use_post, $product_obj );
		}

		public static function is_product_category() {
			if ( isset( self::$is['product_category'] ) )
				return self::$is['product_category'];

			$ret = false;

			if ( function_exists( 'is_product_category' ) && 
				is_product_category() )
					$ret = true;
			elseif ( is_admin() ) {
				if ( self::get_request_value( 'taxonomy' ) === 'product_cat' &&
					self::get_request_value( 'post_type' ) === 'product' )
						$ret = true;
			}

			return self::$is['product_category'] = apply_filters( 'sucom_is_product_category', $ret );
		}

		public static function is_product_tag() {
			if ( isset( self::$is['product_tag'] ) )
				return self::$is['product_tag'];

			$ret = false;

			if ( function_exists( 'is_product_tag' ) && 
				is_product_tag() )
					$ret = true;
			elseif ( is_admin() ) {
				if ( self::get_request_value( 'taxonomy' ) === 'product_tag' &&
					self::get_request_value( 'post_type' ) === 'product' )
						$ret = true;
			}

			return self::$is['product_tag'] = apply_filters( 'sucom_is_product_tag', $ret );
		}

		public static function get_request_value( $key, $method = 'ANY' ) {
			if ( $method === 'ANY' )
				$method = $_SERVER['REQUEST_METHOD'];
			switch( $method ) {
				case 'POST':
					if ( isset( $_POST[$key] ) )
						return sanitize_text_field( $_POST[$key] );
					break;
				case 'GET':
					if ( isset( $_GET[$key] ) )
						return sanitize_text_field( $_GET[$key] );
					break;
			}
			return '';
		}

		public static function encode_utf8( $decoded ) {
			if ( ! mb_detect_encoding( $decoded, 'UTF-8') == 'UTF-8' )
				$encoded = utf8_encode( $decoded );
			else $encoded = $decoded;
			return $encoded;
		}

		public static function decode_utf8( $encoded ) {
			// if we don't have something to decode, return immediately
			if ( strpos( $encoded, '&#' ) === false )
				return $encoded;

			// convert certain entities manually to something non-standard
			$encoded = preg_replace( '/&#8230;/', '...', $encoded );

			// if mb_decode_numericentity is not available, return the string un-converted
			if ( ! function_exists( 'mb_decode_numericentity' ) )
				return $encoded;

			$decoded = preg_replace_callback( '/&#\d{2,5};/u',
				array( __CLASS__, 'decode_utf8_entity' ), $encoded );

			return $decoded;
		}

		public static function decode_utf8_entity( $matches ) {
			$convmap = array( 0x0, 0x10000, 0, 0xfffff );
			return mb_decode_numericentity( $matches[0], $convmap, 'UTF-8' );
		}

		// limit_text_length() uses PHP's multibyte functions (mb_strlen and mb_substr)
		public function limit_text_length( $text, $maxlen = 300, $trailing = '', $cleanup_html = true ) {
			$charset = get_bloginfo( 'charset' );

			if ( $cleanup_html === true )
				$text = $this->cleanup_html_tags( $text );				// remove any remaining html tags
			else $text = html_entity_decode( self::decode_utf8( $text ), ENT_QUOTES, $charset );

			if ( $maxlen > 0 ) {
				if ( mb_strlen( $trailing ) > $maxlen )
					$trailing = mb_substr( $trailing, 0, $maxlen );			// trim the trailing string, if too long
				if ( mb_strlen( $text ) > $maxlen ) {
					$text = mb_substr( $text, 0, $maxlen - mb_strlen( $trailing ) );
					$text = trim( preg_replace( '/[^ ]*$/', '', $text ) );		// remove trailing bits of words
					$text = preg_replace( '/[,\.]*$/', '', $text );			// remove trailing puntuation
				} else $trailing = '';							// truncate trailing string if text is less than maxlen
				$text = $text.$trailing;						// trim and add trailing string (if provided)
			}
			//$text = htmlentities( $text, ENT_QUOTES, $charset, false );
			$text = preg_replace( '/&nbsp;/', ' ', $text);					// just in case
			return $text;
		}

		public function cleanup_html_tags( $text, $strip_tags = true, $use_img_alt = false ) {
			$alt_text = '';
			$alt_prefix = isset( $this->p->options['plugin_img_alt_prefix'] ) ?
				$this->p->options['plugin_img_alt_prefix'] : 'Image:';

			$text = self::strip_shortcodes( $text );					// remove any remaining shortcodes
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );				// put everything on one line
			$text = preg_replace( '/<\?.*\?>/i', ' ', $text);				// remove php
			$text = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/i', ' ', $text);		// remove javascript
			$text = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/i', ' ', $text);		// remove inline stylesheets
			$text = preg_replace( '/<!--'.$this->p->cf['lca'].'-ignore-->(.*?)<!--\/'.
				$this->p->cf['lca'].'-ignore-->/i', ' ', $text);			// remove text between comment strings

			if ( $strip_tags ) {
				$text = preg_replace( '/<\/p>/i', ' ', $text);				// replace end of paragraph with a space
				$text_stripped = trim( strip_tags( $text ) );				// remove remaining html tags

				if ( $text_stripped === '' && $use_img_alt ) {				// possibly use img alt strings if no text
					if ( strpos( $text, '<img ' ) !== false &&
						preg_match_all( '/<img [^>]*alt=["\']([^"\'>]*)["\']/U', 
							$text, $matches, PREG_PATTERN_ORDER ) ) {

						foreach ( $matches[1] as $alt ) {
							$alt = trim( $alt );
							if ( ! empty( $alt ) ) {
								$alt = empty( $alt_prefix ) ? 
									$alt : $alt_prefix.' '.$alt;

								// add a period after the image alt text if missing
								$alt_text .= ( strpos( $alt, '.' ) + 1 ) === strlen( $alt ) ? 
									$alt.' ' : $alt.'. ';
							}
						}
						if ( $this->p->debug->enabled )
							$this->p->debug->log( 'img alt text: '.$alt_text );
					}
					$text = $alt_text;
				} else $text = $text_stripped;
			}
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );	// convert space-like chars to a single space

			return trim( $text );
		}

		public static function strip_shortcodes( $text ) {
			if ( strpos( $text, '[' ) === false )
				return $text;
			$shortcodes_preg = apply_filters( 
				'sucom_strip_shortcodes',
				array(
					'/\[\/?vc_[^\]]+\]/',
				)
			);
			$text = preg_replace( $shortcodes_preg, '', $text );
			$text = strip_shortcodes( $text );	// strip any remaining registered shortcodes
			return $text;
		}

		public function do_table_rows( $table_rows, $class_href_key = '', $class_tabset_mb = '', $class_tabset = '' ) {
			// just in case
			if ( empty( $table_rows ) || ! is_array( $table_rows ) )
				return;

			$lca = empty( $this->p->cf['lca'] ) ? 
				'sucom' : $this->p->cf['lca'];
			$total_rows = count( $table_rows );
			$count_rows = 0;
			$hidden_opts = 0;
			$hidden_rows = 0;

			// use call_user_func() instead of $classname::show_opts() for PHP 5.2
			$show_opts = class_exists( $lca.'user' ) ? call_user_func( array( $lca.'user', 'show_opts' ) ) : 'basic';

			foreach ( $table_rows as $key => $row ) {
				// default row class and id attribute values
				$tr = array(
					'class' => 'sucom_alt'.( $count_rows % 2 ).
						( $count_rows === 0 ? ' first_row' : '' ).
						( $count_rows === ( $total_rows - 1 ) ? ' last_row' : '' ),
					'id' => ( is_int( $key ) ? '' : 'tr_'.$key )
				);

				// if we don't already have a table row tag, then add one
				if ( strpos( $row, '<tr ' ) === false )
					$row = '<tr class="'.$tr['class'].'"'.
						( empty( $tr['id'] ) ? '' : ' id="'.$tr['id'].'"' ).'>'.$row;
				else {
					foreach ( $tr as $att => $val ) {
						if ( empty( $tr[$att] ) )
							continue;

						// if we're here, then we have a table row tag already
						// count the number of rows and options that are hidden
						if ( $att === 'class' && ! empty( $show_opts ) && 
							( $matched = preg_match( '/<tr [^>]*class="[^"]*hide_in_'.$show_opts.'[" ]/', $row ) > 0 ) ) {
							$hidden_opts += preg_match_all( '/(<th|<tr[^>]*><td)/', $row, $matches );
							$hidden_rows += $matched;
						}

						// add the attribute value
						$row = preg_replace( '/(<tr [^>]*'.$att.'=")([^"]*)(")/', '$1$2 '.$tr[$att].'$3', $row, -1, $cnt );

						// if one hasn't been added, then add both the attribute and its value
						if ( $cnt < 1 )
							$row = preg_replace( '/(<tr )/', '$1'.$att.'="'.$tr[$att].'" ', $row, -1, $cnt );
					}
				}

				// add a closing table row tag if we don't already have one
				if ( strpos( $row, '</tr>' ) === false )
					$row .= '</tr>'."\n";

				// update the table row array element with the new value
				$table_rows[$key] = $row;

				$count_rows++;
			}

			echo '<div class="'.
				( empty( $show_opts ) ? '' : 'sucom-show_'.$show_opts ).
				( empty( $class_tabset ) ? '' : ' '.$class_tabset ).
				( empty( $class_tabset_mb ) ? '' : ' '.$class_tabset_mb ).
				( empty( $class_href_key ) ? '' : ' '.$class_href_key ).
			'">'."\n";

			echo '<table class="sucom-setting '.$lca.
				( empty( $class_href_key ) ? '' : ' '.$class_href_key ).
				( $hidden_rows === $count_rows ? ' hide_in_'.$show_opts : '' ).
			'">'."\n";

			foreach ( $table_rows as $row )
				echo $row;

			echo '</table>'."\n";
			echo '</div>'."\n";

			if ( $hidden_opts > 0 ) {
				$show_opts_label = $this->p->cf['form']['show_options'][$show_opts];
				echo '<div class="hidden_opts_msg '.
					$class_tabset.'-msg '.
					$class_tabset_mb.'-msg '.
					$class_href_key.'-msg">'.
					$hidden_opts.' more options not shown in '.
					$show_opts_label.' view (<a href="javascript:void(0);" onClick="sucomViewUnhideRows( \''.
					$class_href_key.'\', \''.$show_opts.'\' );">unhide these options</a>)</div>'."\n";
			}
		}

		// tab titles in the array should already be translated:
		//
		// $tabs = array(
		//		'header' => _x( 'Edit Text', 'metabox tab', 'nextgen-facebook' ),
		//		'media' => _x( 'Select Media', 'metabox tab', 'nextgen-facebook' ),
		//		'preview' => _x( 'Preview', 'metabox tab', 'nextgen-facebook' ),
		//		'tags' => _x( 'Head Tags', 'metabox tab', 'nextgen-facebook' ),
		//		'validate' => _x( 'Validate', 'metabox tab', 'nextgen-facebook' ),
		// );
		//
		public function do_metabox_tabs( $metabox = '', $tabs = array(), $table_rows = array(), $args = array() ) {
			$tab_keys = array_keys( $tabs );
			$default_tab = '_'.reset( $tab_keys );		// must start with an underscore
			$class_metabox_tabs = 'sucom-metabox-tabs';
			$class_link = 'sucom-tablink';
			$class_tabset = 'sucom-tabset';

			if ( ! empty( $metabox ) ) {
				$metabox = '_'.$metabox;		// must start with an underscore
				$class_metabox_tabs .= ' '.$class_metabox_tabs.$metabox;
				$class_link .= ' '.$class_link.$metabox;
			}

			// allow a css ID to be passed as a query argument
			extract( array_merge( array(
				'scroll_to' => isset( $_GET['scroll_to'] ) ? 
					'#'.self::sanitize_key( $_GET['scroll_to'] ) : '',
			), $args ) );

			echo "\n".'<script type="text/javascript">jQuery(document).ready(function(){ '.
				'sucomTabs(\''.$metabox.'\', \''.$default_tab.'\', \''.$scroll_to.'\'); });</script>'."\n";
			echo '<div class="'.$class_metabox_tabs.'">'."\n";
			echo '<ul class="'.$class_metabox_tabs.'">'."\n";
			foreach ( $tabs as $tab => $title ) {
				$class_href_key = $class_tabset.$metabox.'-tab_'.$tab;
				echo '<div class="tab_left">&nbsp;</div><li class="'.
					$class_href_key.'"><a class="'.$class_link.'" href="#'.
					$class_href_key.'">'.$title.'</a></li>'."\n";
			}
			echo '</ul><!-- .'.$class_metabox_tabs.' -->'."\n";

			foreach ( $tabs as $tab => $title ) {
				$class_href_key = $class_tabset.$metabox.'-tab_'.$tab;
				$this->do_table_rows( 
					$table_rows[$tab], 
					$class_href_key,
					( empty( $metabox ) ? '' : $class_tabset.$metabox ),
					$class_tabset
				);
			}
			echo '</div><!-- .'.$class_metabox_tabs.' -->'."\n\n";
		}

		public static function get_stripped_php ( $file ) {
			$ret = '';
			if ( file_exists( $file ) ) {
				$php = file_get_contents( $file );
				$comments = array(T_COMMENT); 
				if ( defined( 'T_DOC_COMMENT' ) )
					$comments[] = T_DOC_COMMENT;	// php 5
				if ( defined( 'T_ML_COMMENT' ) )
					$comments[] = T_ML_COMMENT;	// php 4
				$tokens = token_get_all( $php );
				foreach ( $tokens as $token ) {
					if ( is_array( $token ) ) {
						if ( in_array( $token[0], $comments ) )
							continue; 
						$token = $token[1];
					}
					$ret .= $token;
				}
			} else $ret = false;
			return $ret;
		}

		public static function esc_url_encode( $url ) {
			$allowed = array( '!', '*', '\'', '(', ')', ';', ':', '@', '&', '=',
				'+', '$', ',', '/', '?', '%', '#', '[', ']' );
			$replace = array( '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D',
				'%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D' );
			return str_replace( $replace, $allowed, urlencode( esc_url( $url ) ) );
		}

		// wp_encode_emoji() is only available since v4.2
		// use the wp function if available, otherwise provide the same functionality
		public static function encode_emoji( $content ) {
			if ( function_exists( 'wp_encode_emoji' ) )
				return wp_encode_emoji( $content );		// since wp 4.2 
			elseif ( function_exists( 'mb_convert_encoding' ) ) {
				$regex = '/(
				     \x23\xE2\x83\xA3               # Digits
				     [\x30-\x39]\xE2\x83\xA3
				   | \xF0\x9F[\x85-\x88][\xA6-\xBF] # Enclosed characters
				   | \xF0\x9F[\x8C-\x97][\x80-\xBF] # Misc
				   | \xF0\x9F\x98[\x80-\xBF]        # Smilies
				   | \xF0\x9F\x99[\x80-\x8F]
				   | \xF0\x9F\x9A[\x80-\xBF]        # Transport and map symbols
				)/x';
				$matches = array();
				if ( preg_match_all( $regex, $content, $matches ) ) {
					if ( ! empty( $matches[1] ) ) {
						foreach ( $matches[1] as $emoji ) {
							$unpacked = unpack( 'H*', mb_convert_encoding( $emoji, 'UTF-32', 'UTF-8' ) );
							if ( isset( $unpacked[1] ) ) {
								$entity = '&#x' . ltrim( $unpacked[1], '0' ) . ';';
								$content = str_replace( $emoji, $entity, $content );
							}
						}
					}
				}
			}
			return $content;
		}

		public static function json_encode_array( array $data, $options = 0, $depth = 32 ) {
			if ( function_exists( 'wp_json_encode' ) )
				return wp_json_encode( $data, $options, $depth );
			elseif ( function_exists( 'json_encode' ) )
				return json_encode( $data, $options, $depth );
			else return '{}';	// empty string
		}

		public static function is_mobile() {
			// load class object on first use
			if ( self::$mobile_detect === null ) {
				if ( ! class_exists( 'SuextMobileDetect' ) )
					require_once( dirname( __FILE__ ).'/../ext/mobile-detect.php' );
				self::$mobile_detect = new SuextMobileDetect();
			}
			return self::$mobile_detect->isMobile();
		}

		public static function is_desktop() {
			return self::is_mobile() ? false : true;
		}

		/*
		 * Example:
		 *      'article' => 'Item Type Article',
		 *      'article#news:no_load' => 'Item Type NewsArticle',
		 *      'article#tech:no_load' => 'Item Type TechArticle',
		 */
		public static function get_lib_stub_action( $lib_id ) {
			if ( ( $pos = strpos( $lib_id, ':' ) ) !== false ) {
				$action = substr( $lib_id, $pos + 1 );
				$lib_id = substr( $lib_id, 0, $pos );
			} else $action = false;

			if ( ( $pos = strpos( $lib_id, '#' ) ) !== false ) {
				$stub = substr( $lib_id, $pos + 1 );
				$lib_id = substr( $lib_id, 0, $pos );
			} else $stub = false;

			return array( $lib_id, $stub, $action );
		}

		public static function get_user_select( $roles = array( 'administrator' ), $blog_id = false ) {

			if ( ! $blog_id )
				$blog_id = get_current_blog_id();

			if ( ! is_array( $roles ) )
				$roles = array( $roles );

			foreach ( $roles as $role ) {
				foreach ( get_users( array(
					'blog_id' => $blog_id,
					'role' => $role,
					'fields' => array(
						'id',
						'display_name'
					)
				) ) as $user ) 
					$ret[$user->display_name] = $user->id;
			}

			// sort by the display name key value
			ksort( $ret, SORT_NATURAL );

			// add 'none' to create an associative array *before* flipping the array
			// in order to preserve the user id => display name association
			return array_flip( array_merge( array( '[None]' => 'none' ), $ret ) );
		}

		public static function count_diff( &$arr, $max = 0 ) {
			$diff = 0;
			if ( ! is_array( $arr ) ) 
				return false;
			if ( $max > 0 && $max >= count( $arr ) )
				$diff = $max - count( $arr );
			return $diff;
		}

		public static function get_alpha2_countries() {
			if ( ! class_exists( 'SucomCountryCodes' ) )
				require_once( dirname( __FILE__ ).'/country-codes.php' );
			return SucomCountryCodes::get( 'alpha2' );
		}

		public static function get_alpha2_country_name( $country_code, $default_code = false ) {
			if ( empty( $country_code ) ||
				$country_code === 'none' )
					return false;

			if ( ! class_exists( 'SucomCountryCodes' ) )
				require_once( dirname( __FILE__ ).'/country-codes.php' );

			$countries = SucomCountryCodes::get( 'alpha2' );

			if ( ! isset( $countries[$country_code] ) ) {
				if ( $default_code === false || 
					! isset( $countries[$default_code] ) )
						return false;
				else return $countries[$default_code];
			} else return $countries[$country_code];
		}
	}
}

?>

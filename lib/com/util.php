<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2017 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for...' );
}

if ( ! class_exists( 'SucomUtil' ) ) {

	class SucomUtil {

		protected $p;

		protected static $is_mobile;			// is_mobile cached value
		protected static $mobile_obj;			// SuextMobileDetect class object
		protected static $active_plugins;		// active site and network plugins
		protected static $active_site_plugins;
		protected static $active_network_plugins;
		protected static $crawler_name;			// saved crawler name from user-agent
		protected static $filter_values = array();	// saved filter values
		protected static $user_exists = array();	// saved user_exists() values
		protected static $locales = array();		// saved get_locale() values

		private static $dashicons = array(
			100 => 'admin-appearance',
			101 => 'admin-comments',
			102 => 'admin-home',
			103 => 'admin-links',
			104 => 'admin-media',
			105 => 'admin-page',
			106 => 'admin-plugins',
			107 => 'admin-tools',
			108 => 'admin-settings',
			109 => 'admin-post',
			110 => 'admin-users',
			111 => 'admin-generic',
			112 => 'admin-network',
			115 => 'welcome-view-site',
			116 => 'welcome-widgets-menus',
			117 => 'welcome-comments',
			118 => 'welcome-learn-more',
			119 => 'welcome-write-blog',
			120 => 'wordpress',
			122 => 'format-quote',
			123 => 'format-aside',
			125 => 'format-chat',
			126 => 'format-video',
			127 => 'format-audio',
			128 => 'format-image',
			130 => 'format-status',
			132 => 'plus',
			133 => 'welcome-add-page',
			134 => 'align-center',
			135 => 'align-left',
			136 => 'align-right',
			138 => 'align-none',
			139 => 'arrow-right',
			140 => 'arrow-down',
			141 => 'arrow-left',
			142 => 'arrow-up',
			145 => 'calendar',
			147 => 'yes',
			148 => 'admin-collapse',
			153 => 'dismiss',
			154 => 'star-empty',
			155 => 'star-filled',
			156 => 'sort',
			157 => 'pressthis',
			158 => 'no',
			159 => 'marker',
			160 => 'lock',
			161 => 'format-gallery',
			163 => 'list-view',
			164 => 'exerpt-view',
			165 => 'image-crop',
			166 => 'image-rotate-left',
			167 => 'image-rotate-right',
			168 => 'image-flip-vertical',
			169 => 'image-flip-horizontal',
			171 => 'undo',
			172 => 'redo',
			173 => 'post-status',
			174 => 'cart',
			175 => 'feedback',
			176 => 'cloud',
			177 => 'visibility',
			178 => 'vault',
			179 => 'search',
			180 => 'screenoptions',
			181 => 'slides',
			182 => 'trash',
			183 => 'analytics',
			184 => 'chart-pie',
			185 => 'chart-bar',
			200 => 'editor-bold',
			201 => 'editor-italic',
			203 => 'editor-ul',
			204 => 'editor-ol',
			205 => 'editor-quote',
			206 => 'editor-alignleft',
			207 => 'editor-aligncenter',
			208 => 'editor-alignright',
			209 => 'editor-insertmore',
			210 => 'editor-spellcheck',
			211 => 'editor-distractionfree',
			212 => 'editor-kitchensink',
			213 => 'editor-underline',
			214 => 'editor-justify',
			215 => 'editor-textcolor',
			216 => 'editor-paste-word',
			217 => 'editor-paste-text',
			218 => 'editor-removeformatting',
			219 => 'editor-video',
			220 => 'editor-customchar',
			221 => 'editor-outdent',
			222 => 'editor-indent',
			223 => 'editor-help',
			224 => 'editor-strikethrough',
			225 => 'editor-unlink',
			226 => 'dashboard',
			227 => 'flag',
			229 => 'leftright',
			230 => 'location',
			231 => 'location-alt',
			232 => 'images-alt',
			233 => 'images-alt2',
			234 => 'video-alt',
			235 => 'video-alt2',
			236 => 'video-alt3',
			237 => 'share',
			238 => 'chart-line',
			239 => 'chart-area',
			240 => 'share-alt',
			242 => 'share-alt2',
			301 => 'twitter',
			303 => 'rss',
			304 => 'facebook',
			305 => 'facebook-alt',
			306 => 'camera',
			307 => 'groups',
			308 => 'hammer',
			309 => 'art',
			310 => 'migrate',
			311 => 'performance',
			312 => 'products',
			313 => 'awards',
			314 => 'forms',
			316 => 'download',
			317 => 'upload',
			318 => 'category',
			319 => 'admin-site',
			320 => 'editor-rtl',
			321 => 'backup',
			322 => 'portfolio',
			323 => 'tag',
			324 => 'wordpress-alt',
			325 => 'networking',
			326 => 'translation',
			328 => 'smiley',
			330 => 'book',
			331 => 'book-alt',
			332 => 'shield',
			333 => 'menu',
			334 => 'shield-alt',
			335 => 'no-alt',
			336 => 'id',
			337 => 'id-alt',
			338 => 'businessman',
			339 => 'lightbulb',
			340 => 'arrow-left-alt',
			341 => 'arrow-left-alt2',
			342 => 'arrow-up-alt',
			343 => 'arrow-up-alt2',
			344 => 'arrow-right-alt',
			345 => 'arrow-right-alt2',
			346 => 'arrow-down-alt',
			347 => 'arrow-down-alt2',
			348 => 'info',
			459 => 'star-half',
			460 => 'minus',
			462 => 'googleplus',
			463 => 'update',
			464 => 'edit',
			465 => 'email',
			466 => 'email-alt',
			468 => 'sos',
			469 => 'clock',
			470 => 'smartphone',
			471 => 'tablet',
			472 => 'desktop',
			473 => 'testimonial',
		);

		private static $pub_lang = array(
			// https://www.facebook.com/translations/FacebookLocales.xml
			'facebook' => array(
				'af_ZA' => 'Afrikaans',
				'ak_GH' => 'Akan',
				'am_ET' => 'Amharic',
				'ar_AR' => 'Arabic',
				'as_IN' => 'Assamese',
				'ay_BO' => 'Aymara',
				'az_AZ' => 'Azerbaijani',
				'be_BY' => 'Belarusian',
				'bg_BG' => 'Bulgarian',
				'bn_IN' => 'Bengali',
				'br_FR' => 'Breton',
				'bs_BA' => 'Bosnian',
				'ca_ES' => 'Catalan',
				'cb_IQ' => 'Sorani Kurdish',
				'ck_US' => 'Cherokee',
				'co_FR' => 'Corsican',
				'cs_CZ' => 'Czech',
				'cx_PH' => 'Cebuano',
				'cy_GB' => 'Welsh',
				'da_DK' => 'Danish',
				'de_DE' => 'German',
				'el_GR' => 'Greek',
				'en_GB' => 'English (UK)',
				'en_IN' => 'English (India)',
				'en_PI' => 'English (Pirate)',
				'en_UD' => 'English (Upside Down)',
				'en_US' => 'English (US)',
				'eo_EO' => 'Esperanto',
				'es_CL' => 'Spanish (Chile)',
				'es_CO' => 'Spanish (Colombia)',
				'es_ES' => 'Spanish (Spain)',
				'es_LA' => 'Spanish',
				'es_MX' => 'Spanish (Mexico)',
				'es_VE' => 'Spanish (Venezuela)',
				'et_EE' => 'Estonian',
				'eu_ES' => 'Basque',
				'fa_IR' => 'Persian',
				'fb_LT' => 'Leet Speak',
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
				'gx_GR' => 'Classical Greek',
				'ha_NG' => 'Hausa',
				'he_IL' => 'Hebrew',
				'hi_IN' => 'Hindi',
				'hr_HR' => 'Croatian',
				'ht_HT' => 'Haitian Creole',
				'hu_HU' => 'Hungarian',
				'hy_AM' => 'Armenian',
				'id_ID' => 'Indonesian',
				'ig_NG' => 'Igbo',
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
				'ky_KG' => 'Kyrgyz',
				'la_VA' => 'Latin',
				'lg_UG' => 'Ganda',
				'li_NL' => 'Limburgish',
				'ln_CD' => 'Lingala',
				'lo_LA' => 'Lao',
				'lt_LT' => 'Lithuanian',
				'lv_LV' => 'Latvian',
				'mg_MG' => 'Malagasy',
				'mi_NZ' => 'Māori',
				'mk_MK' => 'Macedonian',
				'ml_IN' => 'Malayalam',
				'mn_MN' => 'Mongolian',
				'mr_IN' => 'Marathi',
				'ms_MY' => 'Malay',
				'mt_MT' => 'Maltese',
				'my_MM' => 'Burmese',
				'nb_NO' => 'Norwegian (bokmal)',
				'nd_ZW' => 'Ndebele',
				'ne_NP' => 'Nepali',
				'nl_BE' => 'Dutch (België)',
				'nl_NL' => 'Dutch',
				'nn_NO' => 'Norwegian (nynorsk)',
				'ny_MW' => 'Chewa',
				'or_IN' => 'Oriya',
				'pa_IN' => 'Punjabi',
				'pl_PL' => 'Polish',
				'ps_AF' => 'Pashto',
				'pt_BR' => 'Portuguese (Brazil)',
				'pt_PT' => 'Portuguese (Portugal)',
				'qc_GT' => 'Quiché',
				'qu_PE' => 'Quechua',
				'rm_CH' => 'Romansh',
				'ro_RO' => 'Romanian',
				'ru_RU' => 'Russian',
				'rw_RW' => 'Kinyarwanda',
				'sa_IN' => 'Sanskrit',
				'sc_IT' => 'Sardinian',
				'se_NO' => 'Northern Sámi',
				'si_LK' => 'Sinhala',
				'sk_SK' => 'Slovak',
				'sl_SI' => 'Slovenian',
				'sn_ZW' => 'Shona',
				'so_SO' => 'Somali',
				'sq_AL' => 'Albanian',
				'sr_RS' => 'Serbian',
				'sv_SE' => 'Swedish',
				'sw_KE' => 'Swahili',
				'sy_SY' => 'Syriac',
				'sz_PL' => 'Silesian',
				'ta_IN' => 'Tamil',
				'te_IN' => 'Telugu',
				'tg_TJ' => 'Tajik',
				'th_TH' => 'Thai',
				'tk_TM' => 'Turkmen',
				'tl_PH' => 'Filipino',
				'tl_ST' => 'Klingon',
				'tr_TR' => 'Turkish',
				'tt_RU' => 'Tatar',
				'tz_MA' => 'Tamazight',
				'uk_UA' => 'Ukrainian',
				'ur_PK' => 'Urdu',
				'uz_UZ' => 'Uzbek',
				'vi_VN' => 'Vietnamese',
				'wo_SN' => 'Wolof',
				'xh_ZA' => 'Xhosa',
				'yi_DE' => 'Yiddish',
				'yo_NG' => 'Yoruba',
				'zh_CN' => 'Simplified Chinese (China)',
				'zh_HK' => 'Traditional Chinese (Hong Kong)',
				'zh_TW' => 'Traditional Chinese (Taiwan)',
				'zu_ZA' => 'Zulu',
				'zz_TR' => 'Zazaki',
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

		public static function get_dashicons( $by_name = false, $add_none = false ) {
			$dashicons = self::$dashicons;

			if ( $by_name )	// sort by value (dashicon name)
				asort( $dashicons );

			if ( $add_none )
				$dashicons = array( 'none' => 'none' ) + $dashicons;	// maintain numeric index

			return $dashicons;
		}

		public static function add_filter_protection( $filter_name ) {
			if ( has_filter( $filter_name, array( __CLASS__, 'filter_value_restore' ) ) ) {
				return false;
			}
			add_filter( $filter_name, array( __CLASS__, 'filter_value_save' ), PHP_INT_MIN, 1 );
			add_filter( $filter_name, array( __CLASS__, 'filter_value_restore' ), PHP_INT_MAX, 1 );

			return true;
		}

		public static function remove_filter_protection( $filter_name ) {
			if ( ! has_filter( $filter_name, array( __CLASS__, 'filter_value_restore' ) ) ) {
				return false;
			}
			remove_filter( $filter_name, array( __CLASS__, 'filter_value_save' ), PHP_INT_MIN );
			remove_filter( $filter_name, array( __CLASS__, 'filter_value_restore' ), PHP_INT_MAX );

			return true;
		}

		public static function filter_value_save( $value ) {
			$filter_name = current_filter();

			// don't save / restore empty strings (for home page wp_title)
			self::$filter_values[$filter_name] = trim( $value ) === '' ?
				null : $value;

			return $value;
		}

		public static function filter_value_restore( $value ) {
			$filter_name = current_filter();

			return self::$filter_values[$filter_name] === null ?
				$value : self::$filter_values[$filter_name];
		}

		public static function is_https( $url = '' ) {
			if ( ! empty( $url ) ) {
				if ( strpos( $url, '://' ) &&	// just in case
					parse_url( $url, PHP_URL_SCHEME ) === 'https' ) {
					return true;
				} else {
					return false;
				}
			} elseif ( is_ssl() ) {		// since wp 2.6.0
				return true;
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
				strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) === 'https' ) {
				return true;
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) &&
				strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] ) === 'on' ) {
				return true;
			} else {
				return false;
			}
		}

		public static function get_prot( $url = '' ) {
			if ( self::is_https( $url ) ) {
				return 'https';
			} elseif ( is_admin() )  {
				if ( self::get_const( 'FORCE_SSL_ADMIN' ) ) {
					return 'https';
				}
			} elseif ( self::get_const( 'FORCE_SSL' ) ) {
				return 'https';
			}
			return 'http';
		}

		public static function add_prot( $url = '' ) {
			return self::get_prot( $url ).'://'.preg_replace( '/^(.*://|//)/', '', $url );
		}

		public static function get_const( $const, $not_found = null ) {
			if ( defined( $const ) ) {
				return constant( $const );
			} else {
				return $not_found;
			}
		}

		// returns false or the admin screen id text string
		public static function get_screen_id( $screen = false ) {
			if ( $screen === false &&
				function_exists( 'get_current_screen' ) )
					$screen = get_current_screen();
			if ( isset( $screen->id ) )
				return $screen->id;
			else return false;
		}

		// returns false or the admin screen base text string
		public static function get_screen_base( $screen = false ) {
			if ( $screen === false &&
				function_exists( 'get_current_screen' ) )
					$screen = get_current_screen();
			if ( isset( $screen->base ) )
				return $screen->base;
			else return false;
		}

		// note that an empty string or a null is sanitized as false
		public static function sanitize_use_post( $mixed, $default = false ) {
			if ( is_array( $mixed ) )
				$use_post = isset( $mixed['use_post'] ) ?
					$mixed['use_post'] : $default;
			elseif ( is_object( $mixed ) )
				$use_post = isset( $mixed->use_post ) ?
					$mixed->use_post : $default;
			else $use_post = $mixed;
				
			if ( empty( $use_post ) || $use_post === 'false' )	// 0, false, or 'false'
				return false;
			elseif ( is_numeric( $use_post ) )
				return (int) $use_post;
			else return true;
		}

		public static function sanitize_hookname( $name ) {
			$name = preg_replace( '/[:\/\-\. ]+/', '_', $name );
			return self::sanitize_key( $name );
		}

		public static function sanitize_classname( $name, $underscore = true ) {
			$name = preg_replace( '/[:\/\-\. '.( $underscore ? '' : '_' ).']+/', '', $name );
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

		public static function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
		}

		public static function array_to_hashtags( $tags = array() ) {
			// array_filter() removes empty array values
			return trim( implode( ' ', array_filter( self::sanitize_hashtags( $tags ) ) ) );
		}

		public static function explode_csv( $str ) {
			if ( empty( $str ) ) {
				return array();
			} else {
				return array_map( array( __CLASS__, 'trim_csv_val' ), explode( ',', $str ) );
			}
		}

		private static function trim_csv_val( $val ) {
			return trim( $val, '\'" ' );	// remove quotes and spaces
		}

		public static function titleize( $str ) {
			return ucwords( preg_replace( '/[:\/\-\._]+/', ' ', self::decamelize( $str ) ) );
		}

		public static function decamelize( $str ) {
			return ltrim( strtolower( preg_replace('/[A-Z]/', '_$0', $str ) ), '_' );
		}

		// active plugins array is cached in a static class property
		public static function active_plugins( $plugin_base = false ) {
			if ( ! isset( self::$plugins_index ) ) {
				$combined_plugins = self::$active_site_plugins = get_option( 'active_plugins', array() );
				if ( is_multisite() ) {
					self::$active_network_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
					if ( ! empty( self::$active_network_plugins ) ) {
						$combined_plugins = array_merge( self::$active_site_plugins, self::$active_network_plugins );
					}
				}
				foreach ( $combined_plugins as $base ) {
					self::$active_plugins[$base] = true;
				}
			}
			if ( $plugin_base !== false ) {
				if ( isset( self::$active_plugins[$plugin_base] ) ) {
					return self::$active_plugins[$plugin_base];
				} else {
					return false;
				}
			} else {
				return self::$active_plugins;
			}
		}

		public static function installed_plugins( $plugin_base = false ) {
			if ( ! empty( $plugin_base ) && validate_file( $plugin_base ) > 0 ) {	// contains invalid characters
				return false;
			} elseif ( ! file_exists( WP_PLUGIN_DIR.'/'.$plugin_base) ) {	// check existence of plugin folder
				return false;
			}
			$installed_plugins = get_plugins();
			if ( ! isset( $installed_plugins[$plugin_base] ) ) {	// check for a valid plugin header
				return false;
			}
			return true;
		}

		public static function plugin_has_update( $plugin_base = false ) {
			if ( ! self::installed_plugins( $plugin_base ) ) {
				return false;
			}
			$update_plugins = get_site_transient( 'update_plugins' );
			if ( isset( $update_plugins->response ) && is_array( $update_plugins->response ) ) {
				if ( isset( $update_plugins->response[$plugin_base] ) ) {
					return true;
				}
			}
			return false;
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
			if ( $site === true ) {
				$opts = get_site_option( $name, array() );
			}
			else {
				$opts = get_option( $name, array() );
			}

			if ( $protect === true && isset( $opts[$key] ) ) {
				return false;
			}

			$opts[$key] = $value;

			if ( $site === true ) {
				return update_site_option( $name, $opts );
			} else {
				return update_option( $name, $opts );
			}
		}

		public static function get_option_key( $name, $key, $site = false ) {
			if ( $site === true )
				$opts = get_site_option( $name, array() );
			else $opts = get_option( $name, array() );
			if ( isset( $opts[$key] ) )
				return $opts[$key];
			else return null;
		}

		// deprecated on 2017/04/16
		public static function crawler_name() {
			return self::get_crawler_name();
		}

		public static function is_crawler_name( $crawler_name ) {
			return $crawler_name === self::get_crawler_name() ? true : false;
		}

		public static function get_crawler_name() {

			if ( ! isset( self::$crawler_name ) ) {

				$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ?
					strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';

				switch ( true ) {
					// "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)"
					case ( strpos( $ua, 'facebookexternalhit/' ) === 0 ):
						self::$crawler_name = 'facebook';
						break;

					// "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)"
					case ( strpos( $ua, 'compatible; bingbot/' ) !== false ):
						self::$crawler_name = 'bing';
						break;

					// "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
					case ( strpos( $ua, 'compatible; googlebot/' ) !== false ):
						self::$crawler_name = 'google';
						break;

					// Mozilla/5.0 (compatible; Google-Structured-Data-Testing-Tool +https://search.google.com/structured-data/testing-tool)"
					case ( strpos( $ua, 'compatible; google-structured-data-testing-tool' ) !== false ):
						self::$crawler_name = 'google';
						break;

					// "Pinterest/0.2 (+http://www.pinterest.com/bot.html)"
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

					// "Validator.nu/LV http://validator.w3.org/services"
					case ( strpos( $ua, 'validator.nu/' ) === 0 ):
						self::$crawler_name = 'w3c';
						break;

					// "Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MTC19V) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.81 Mobile Safari/537.36 (compatible; validator.ampproject.org) AppEngine-Google; (+http://code.google.com/appengine; appid: s~amp-validator)"
					case ( strpos( $ua, 'validator.ampproject.org' ) === 0 ):
						self::$crawler_name = 'amp';
						break;

					default:
						self::$crawler_name = 'none';
						break;
				}
				self::$crawler_name = apply_filters( 'sucom_crawler_name', self::$crawler_name, $ua );
			}

			return self::$crawler_name;
		}

		public static function a2aa( $a ) {
			$aa = array();
			foreach ( $a as $i )
				$aa[][] = $i;
			return $aa;
		}

		public static function is_assoc( $arr ) {
			if ( ! is_array( $arr ) ) {
				return false;
			} else {
				return is_numeric( implode( array_keys( $arr ) ) ) ? false : true;
			}
		}

		public static function keys_start_with( $str, array $arr ) {
			$found = array();
			foreach ( $arr as $key => $value ) {
				if ( strpos( $key, $str ) === 0 )
					$found[$key] = $value;
			}
			return $found;
		}

		// use reference for $input argument to allow unset of keys if $remove is true.
		public static function preg_grep_keys( $pattern, array &$input, $invert = false, $replace = false, $remove = false ) {
			$invert = $invert == false ? null : PREG_GREP_INVERT;
			$match = preg_grep( $pattern, array_keys( $input ), $invert );
			$found = array();
			foreach ( $match as $key ) {
				if ( $replace !== false ) {
					$fixed = preg_replace( $pattern, $replace, $key );
					$found[$fixed] = $input[$key];
				} else {
					$found[$key] = $input[$key];
				}
				if ( $remove !== false ) {
					unset( $input[$key] );
				}
			}
			return $found;
		}

		public static function rename_keys( &$opts = array(), $key_names = array(), $modifiers = true ) {
			foreach ( $key_names as $old_name => $new_name ) {
				if ( empty( $old_name ) ) {	// just in case
					continue;
				}
				$old_name_preg = $modifiers ? '/^'.$old_name.'(:is|:use|#.*|_[0-9]+)?$/' : '/^'.$old_name.'$/';

				foreach ( preg_grep( $old_name_preg, array_keys ( $opts ) ) as $old_name_local ) {
					if ( ! empty( $new_name ) ) {	// can be empty to remove option
						$new_name_local = preg_replace( $old_name_preg, $new_name.'$1', $old_name_local );
						$opts[$new_name_local] = $opts[$old_name_local];
					}
					unset( $opts[$old_name_local] );
				}
			}
		}

		public static function next_key( $needle, array &$input, $loop = true ) {
			$keys = array_keys( $input );
			$pos = array_search( $needle, $keys );
			if ( $pos !== false ) {
				if ( isset( $keys[ $pos + 1 ] ) )
					return $keys[ $pos + 1 ];
				elseif ( $loop === true )
					return $keys[0];
			}
			return false;
		}

		// move an associative array element to the end
		public static function move_to_end( array &$array, $key ) {
			$val = $array[$key];
			unset( $array[$key] );
			$array[$key] = $val;
			return $array;
		}

		// returns the modified array
		public static function get_before_key( array $array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'before', $array, $match_key, $mixed, $add_value );
		}

		// returns the modified array
		public static function get_after_key( array $array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'after', $array, $match_key, $mixed, $add_value );
		}

		// returns the modified array
		public static function get_replace_key( array $array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'replace', $array, $match_key, $mixed, $add_value );
		}

		// modifies the references array directly, and returns true or false
		public static function add_before_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'before', $array, $match_key, $mixed, $add_value, true );	// $ret_matched = true
		}

		// modifies the references array directly, and returns true or false
		public static function add_after_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'after', $array, $match_key, $mixed, $add_value, true );	// $ret_matched = true
		}

		// modifies the references array directly, and returns true or false
		public static function do_replace_key( array &$array, $match_key, $mixed, $add_value = '' ) {
			return self::insert_in_array( 'replace', $array, $match_key, $mixed, $add_value, true );	// $ret_matched = true
		}

		private static function insert_in_array( $rel_pos, array &$array, $match_key, $mixed, $add_value, $ret_matched = false ) {
			$matched = false;
			if ( array_key_exists( $match_key, $array ) ) {
				$new_array = array();
				foreach ( $array as $key => $value ) {
					if ( $rel_pos === 'after' )
						$new_array[$key] = $value;
					// add new value before / after matched key
					// replace matched key by default (no test required)
					if ( $key === $match_key ) {
						if ( is_array( $mixed ) )
							$new_array = array_merge( $new_array, $mixed );
						elseif ( is_string( $mixed ) )
							$new_array[$mixed] = $add_value;
						else $new_array[] = $add_value;
						$matched = true;
					}
					if ( $rel_pos === 'before' )
						$new_array[$key] = $value;
				}
				$array = $new_array;
				unset( $new_array );
			}
			return $ret_matched ? $matched : $array;	// return true/false or the array (default)
		}

		/*
		 * PHP's array_merge_recursive() merges arrays, but it converts
		 * values with duplicate keys to arrays rather than overwriting
		 * the value in the first array with the duplicate value in the
		 * second array, as array_merge does. The following method does
		 * not change the datatypes of the values in the arrays.
		 * Matching key values in the second array overwrite those in
		 * the first array, as is the case with array_merge().
		 */
		public static function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
			$merged = $array1;
			foreach ( $array2 as $key => &$value ) {
				if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
					$merged[$key] = self::array_merge_recursive_distinct( $merged[$key], $value );
				} else {
					$merged[$key] = $value;
				}
			}
			return $merged;
		}

		public static function array_flatten( array $array ) {
			$return = array();
		        foreach ( $array as $key => $value ) {
				if ( is_array( $value ) ) {
					$return = array_merge( $return, self::array_flatten( $value ) );
				} else {
					$return[$key] = $value;
				}
			}
			return $return;
		}

		public static function array_implode( array $array, $glue = ' ' ) {
			$return = '';
		        foreach ( $array as $value ) {
			        if ( is_array( $value ) ) {
					$return .= self::array_implode( $value, $glue ).$glue;
				} else {
					$return .= $value.$glue;
				}
			}
			return strlen( $glue ) ?
				rtrim( $return, $glue ) : $glue;
		}

		// array must use unique associative / string keys
		public static function array_parent_index( array $array, $parent_key = '', $gparent_key = '' ) {
			$return = array();
		        foreach ( $array as $child_key => $value ) {
				if ( is_array( $value ) ) {
					$return += self::array_parent_index( $value, $child_key, $parent_key );
				} elseif ( $parent_key && $child_key !== $parent_key ) {
					$return[$child_key] = $parent_key;
				} elseif ( $gparent_key && $child_key === $parent_key ) {
					$return[$child_key] = $gparent_key;
				}
			}
			return $return;
		}

		public static function has_array_element( $needle, array $array, $strict = false ) {
			foreach ( $array as $key => $element )
				if ( ( $strict ? $element === $needle : $element == $needle ) ||
					( is_array( $element ) && self::has_array_element( $needle, $element, $strict ) ) )
						return true;
			return false;
		}

		public static function get_first_last_next_nums( array $input ) {
			$count = count( $input );
			$keys = array_keys( $input );
			if ( $count && ! is_numeric( implode( $keys ) ) )	// array cannot be associative
				return array( 0, 0, 0 );
			sort( $keys );
			$first = (int) reset( $keys );
			$last = (int) end( $keys );
			$next = $count ? $last + 1 : $last;	// next is 0 for an empty array
			return array( $first, $last, $next );
		}

		// return the first url from the associative array (og:image:secure_url, og:image:url, og:image)
		public static function get_mt_media_url( array $assoc, $mt_pre = 'og:image' ) {

			// check for two dimensional arrays and keep following the first array element
			// prefer the $mt_pre array key (if it's available)
			if ( isset( $assoc[$mt_pre] ) && is_array( $assoc[$mt_pre] ) ) {
				$first_element = reset( $assoc[$mt_pre] );
			} else {
				$first_element = reset( $assoc );
			}

			if ( is_array( $first_element ) ) {
				return self::get_mt_media_url( $first_element, $mt_pre );
			}

			// first element is a text string, so check the array keys
			foreach ( array( ':secure_url', ':url', '' ) as $key ) {
				if ( ! empty( $assoc[$mt_pre.$key] ) ) {
					return $assoc[$mt_pre.$key];
				}
			}

			return '';
		}

		public static function get_mt_prop_video() {
			return array(
				'og:video:secure_url' => '',
				'og:video:url' => '',
				// 'og:video' => '',			// not used - do not include
				'og:video:type' => 'application/x-shockwave-flash',
				'og:video:width' => '',
				'og:video:height' => '',
				'og:video:tag' => array(),
				'og:video:duration' => '',		// non-standard / internal meta tag
				'og:video:upload_date' => '',		// non-standard / internal meta tag
				'og:video:thumbnail_url' => '',		// non-standard / internal meta tag
				'og:video:embed_url' => '',		// non-standard / internal meta tag
				'og:video:has_image' => false,		// non-standard / internal meta tag
				'og:video:title' => '',			// non-standard / internal meta tag
				'og:video:description' => '',		// non-standard / internal meta tag

				// used for Twitter player card meta tags
				'og:video:iphone_name' => '',		// non-standard / internal meta tag
				'og:video:iphone_id' => '',		// non-standard / internal meta tag
				'og:video:iphone_url' => '',		// non-standard / internal meta tag
				'og:video:ipad_name' => '',		// non-standard / internal meta tag
				'og:video:ipad_id' => '',		// non-standard / internal meta tag
				'og:video:ipad_url' => '',		// non-standard / internal meta tag
				'og:video:googleplay_name' => '',	// non-standard / internal meta tag
				'og:video:googleplay_id' => '',		// non-standard / internal meta tag
				'og:video:googleplay_url' => '',	// non-standard / internal meta tag

				// Facebook AppLink meta tags
				'al:ios:app_name' => '',
				'al:ios:app_store_id' => '',
				'al:ios:url' => '',
				'al:android:app_name' => '',
				'al:android:package' => '',
				'al:android:url' => '',
				'al:web:url' => '',
				'al:web:should_fallback' => 'true',
			);
		}

		// pre-define the array key order for the list() construct (which assigns elements from right to left)
		public static function get_mt_prop_image( $mt_pre = 'og' ) {
			return array(
				$mt_pre.':image:secure_url' => '',
				//$mt_pre.':image:url' => '',		// not used - do not include
				$mt_pre.':image' => '',
				$mt_pre.':image:width' => '',
				$mt_pre.':image:height' => '',
				$mt_pre.':image:cropped' => '',		// non-standard / internal meta tag
				$mt_pre.':image:id' => '',		// non-standard / internal meta tag
			);
		}

		public static function get_pub_lang( $pub = '' ) {
			switch ( $pub ) {
				case 'fb':
					return self::$pub_lang['facebook'];
				case 'gplus':
				case 'googleplus':
					return self::$pub_lang['google'];
				case 'pin':
					return self::$pub_lang['pinterest'];
				default:
					if ( isset( self::$pub_lang[$pub] ) ) {
						return self::$pub_lang[$pub];
					} else {
						return array();
					}
			}
		}

		public static function get_site_url( array $opts, $mixed = 'current' ) {
			$ret = self::get_locale_opt( 'site_url', $opts, $mixed );
			if ( empty( $ret ) ) {
				return get_bloginfo( 'url' );
			} else {
				return $ret;
			}
		}

		/*
		 * Returns a custom site name or the default WordPress site name.
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_name( array $opts, $mixed = 'current' ) {
			$ret = self::get_locale_opt( 'site_name', $opts, $mixed );
			if ( empty( $ret ) ) {
				return get_bloginfo( 'name', 'display' );
			} else {
				return $ret;
			}
		}

		public static function get_site_alt_name( array $opts, $mixed = 'current' ) {
			return self::get_locale_opt( 'site_alt_name', $opts, $mixed );
		}

		/*
		 * Returns a custom site description or the default WordPress site description / tagline.
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_description( array $opts, $mixed = 'current' ) {
			$ret = self::get_locale_opt( 'site_desc', $opts, $mixed );
			if ( empty( $ret ) ) {
				return get_bloginfo( 'description', 'display' );
			} else {
				return $ret;
			}
		}

		// returns an optional and customized locale value for the og:locale meta tag
		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_fb_locale( array $opts, $mixed = 'current' ) {

			// check for customized locale
			if ( ! empty( $opts ) ) {
				$key_locale = self::get_key_locale( 'fb_locale', $opts, $mixed );
				if ( ! empty( $opts[$key_locale] ) ) {
					return $opts[$key_locale];
				}
			}

			$locale = self::get_locale( $mixed );
			$def_locale = self::get_locale( 'default' );
			$fb_pub_lang = self::get_pub_lang( 'facebook' );

			if ( ! empty( $fb_pub_lang[$locale] ) ) {
				return $locale;
			} elseif ( ! empty( $fb_pub_lang[$def_locale] ) ) {
				return $def_locale;
			} else {
				return 'en_US';
			}
		}

		// return a localize options value
		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_locale_opt( $key, array $opts, $mixed = 'current' ) {
			$key_locale = self::get_key_locale( $key, $opts, $mixed );
			$val_locale = isset( $opts[$key_locale] ) ? $opts[$key_locale] : null;

			// fallback to default value for non-existing keys or empty strings
			if ( ! isset( $opts[$key_locale] ) || $opts[$key_locale] === '' ) {
				if ( ( $pos = strpos( $key_locale, '#' ) ) > 0 ) {
					$key_default = SucomUtil::get_key_locale( substr( $key_locale, 0, $pos ), $opts, 'default' );
					if ( $key_locale !== $key_default ) {
						return isset( $opts[$key_default] ) ? $opts[$key_default] : $val_locale;
					} else {
						return $val_locale;
					}
				} else {
					return $val_locale;
				}
			} else {
				return $val_locale;
			}
		}

		public static function set_key_locale( $key, $value, &$opts, $mixed = 'current' ) {
			$key_locale = self::get_key_locale( $key, $opts, $mixed );
			$opts[$key_locale] = $value;
		}

		// localize an options array key
		// $opts = false | array
		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_key_locale( $key, $opts = false, $mixed = 'current' ) {
			$default = self::get_locale( 'default' );
			$locale = self::get_locale( $mixed );
			$key_locale = $key.'#'.$locale;

			// the default language may have changed, so if we're using the default,
			// check for a locale version for the default language
			if ( $locale === $default ) {
				return isset( $opts[$key_locale] ) ? $key_locale : $key;
			} else {
				return $key_locale;
			}
		}

		public static function get_multi_key_locale( $prefix, array &$opts, $add_none = false ) {
			$default = self::get_locale( 'default' );
			$current = self::get_locale( 'current' );
			$matches = self::preg_grep_keys( '/^'.$prefix.'_([0-9]+)(#.*)?$/', $opts );
			$results = array();

			foreach ( $matches as $key => $value ) {
				$num = preg_replace( '/^'.$prefix.'_([0-9]+)(#.*)?$/', '$1', $key );

				if ( ! empty( $results[$num] ) )	// preserve the first non-blank value
					continue;
				elseif ( ! empty( $opts[$prefix.'_'.$num.'#'.$current] ) )	// current locale
					$results[$num] = $opts[$prefix.'_'.$num.'#'.$current];
				elseif ( ! empty( $opts[$prefix.'_'.$num.'#'.$default] ) )	// default locale
					$results[$num] = $opts[$prefix.'_'.$num.'#'.$default];
				elseif ( ! empty( $opts[$prefix.'_'.$num] ) )			// no locale
					$results[$num] = $opts[$prefix.'_'.$num];
				else $results[$num] = $value;					// use value (could be empty)
			}

			asort( $results );	// sort values for display

			if ( $add_none ) {
				$results = array( 'none' => 'none' ) + $results;	// maintain numeric index
			}

			return $results;
		}

		// $mixed = 'default' | 'current' | post ID | $mod array
		public static function get_locale( $mixed = 'current' ) {
			/*
			 * We use a class static variable (instead of a method static variable)
			 * to cache both self::get_locale() and SucomUtil::get_locale() in the
			 * same variable.
			 */
			$idx = is_array( $mixed ) ? $mixed['name'].'_'.$mixed['id'] : $mixed;

			if ( isset( self::$locales[$idx] ) ) {
				return self::$locales[$idx];
			}

			if ( $mixed === 'default' ) {
				global $wp_local_package;
				if ( isset( $wp_local_package ) ) {
					$locale = $wp_local_package;
				}
				if ( defined( 'WPLANG' ) ) {
					$locale = WPLANG;
				}
				if ( is_multisite() ) {
					if ( ( $ms_locale = get_option( 'WPLANG' ) ) === false ) {
						$ms_locale = get_site_option( 'WPLANG' );
					}
					if ( $ms_locale !== false ) {
						$locale = $ms_locale;
					}
				} else {
					$db_locale = get_option( 'WPLANG' );
					if ( $db_locale !== false ) {
						$locale = $db_locale;
					}
				}
				if ( empty( $locale ) ) {
					$locale = 'en_US';	// just in case
				}
			} else {
				if ( is_admin() && function_exists( 'get_user_locale' ) ) {	// since wp 4.7
					$locale = get_user_locale();
				} else {
					$locale = get_locale();
				}
			}

			return self::$locales[$idx] = apply_filters( 'sucom_locale', $locale, $mixed );
		}

		public static function get_available_locales() {
			$locales = get_available_languages();	// since wp 3.0
			return apply_filters( 'sucom_available_locales', $locales );
		}

		// examples:
		//	'term:123_tax:post_tag'
		//	'post:0_url:https://example.com/a-subject/'
		public static function get_mod_salt( array $mod, $sharing_url = false ) {
			$mod_salt = '';

			if ( ! empty( $mod['name'] ) ) {
				$mod_salt .= '_'.$mod['name'].':'.(int) $mod['id'];	// convert false to 0
			}

			if ( ! empty( $mod['tax_slug'] ) ) {
				$mod_salt .= '_tax:'.$mod['tax_slug'];
			}

			if ( empty( $mod['id'] ) && ! empty( $sharing_url ) ) {
				$mod_salt .= '_url:'.$sharing_url;
			}

			return ltrim( $mod_salt, '_' );
		}

		// update the transient array and keep the original expiration time
		public static function update_transient_array( $cache_id, $data_array, $cache_exp ) {
			$now_time = time();

			if ( isset( $data_array['__created_at'] ) ) {
				$cache_exp -= $now_time - $data_array['__created_at'];
			} else {
				$data_array['__created_at'] = $now_time;
			}

			set_transient( $cache_id, $data_array, $cache_exp );

			return $cache_exp;
		}

		public static function restore_checkboxes( &$opts ) {
			// unchecked checkboxes are not provided, so re-create them here based on hidden values
			$checkbox = self::preg_grep_keys( '/^is_checkbox_/', $opts, false, '' );

			foreach ( $checkbox as $key => $val ) {
				if ( ! array_key_exists( $key, $opts ) ) {
					$opts[$key] = 0;	// add missing checkbox as empty
				}
				unset ( $opts['is_checkbox_'.$key] );
			}
			return $opts;
		}

		public static function get_is_page( $use_post = false ) {

			// optimize and only check what we need to
			$is_term_page = $is_user_page = false;
			if ( ! $is_post_page = self::is_post_page( $use_post ) )
				if ( ! $is_term_page = self::is_term_page() )
					$is_user_page = self::is_user_page();

			return array(
				'post_page' => $is_post_page,
				'term_page' => $is_term_page,
				'user_page' => $is_user_page
			);
		}

		public static function is_archive_page() {
			$ret = false;
			if ( is_archive() )
				$ret = true;
			elseif ( is_admin() ) {
				$screen_base = self::get_screen_base();
				if ( $screen_base !== false ) {
					switch ( $screen_base ) {
						case 'edit':		// post/page list
						case 'edit-tags':	// categories/tags list
						case 'users':		// users list
							$ret = true;
							break;
					}
				}
			}
			return apply_filters( 'sucom_is_archive_page', $ret );
		}

		public static function is_home_page( $use_post = false ) {
			$ret = false;

			$post_id = get_option( 'show_on_front' ) === 'page' ?
				(int) get_option( 'page_on_front' ) : 0;

			if ( is_numeric( $use_post ) &&
				(int) $use_post === $post_id )	// optimize
					$ret = true;
			elseif ( $post_id > 0 &&
				self::get_post_object( $use_post, 'id' ) === $post_id )
					$ret = true;

			return apply_filters( 'sucom_is_home_page', $ret, $use_post );
		}

		public static function is_home_index( $use_post = false ) {
			$ret = false;

			$post_id = get_option( 'show_on_front' ) === 'page' ?
				(int) get_option( 'page_for_posts' ) : 0;

			if ( is_numeric( $use_post ) &&
				(int) $use_post === $post_id )	// optimize
					$ret = true;
			elseif ( $post_id > 0 &&
				self::get_post_object( $use_post, 'id' ) === $post_id )	// static posts page
					$ret = true;
			elseif ( $use_post === false && is_home() && is_front_page() )	// standard index page
				$ret = true;

			return apply_filters( 'sucom_is_home_index', $ret, $use_post );
		}

		public static function is_post_exists( $post_id ) {
			  return is_string( get_post_status( $post_id ) );
		}

		public static function is_post_page( $use_post = false ) {
			$ret = false;

			if ( is_numeric( $use_post ) && $use_post > 0 ) {
				$ret = self::is_post_exists( $use_post );

			} elseif ( $use_post === true && ! empty( $GLOBALS['post']->ID ) ) {
				$ret = true;
			
			} elseif ( $use_post === false && is_singular() ) {
				$ret = true;

			} elseif ( ! is_home() && is_front_page() && get_option( 'show_on_front' ) === 'page' ) {	// static front page
				$ret = true;

			} elseif ( is_home() && ! is_front_page() && get_option( 'show_on_front' ) === 'page' ) {	// static posts page
				$ret = true;

			} elseif ( is_admin() ) {
				$screen_base = self::get_screen_base();
				if ( $screen_base === 'post' )
					$ret = true;
				elseif ( $screen_base === false &&	// called too early for screen
					( self::get_request_value( 'post_ID', 'POST' ) !== '' ||	// uses sanitize_text_field
						self::get_request_value( 'post', 'GET' ) !== '' ) )
							$ret = true;
				elseif ( basename( $_SERVER['PHP_SELF'] ) === 'post-new.php' )
					$ret = true;
			}

			return apply_filters( 'sucom_is_post_page', $ret, $use_post );
		}

		public static function get_post_object( $use_post = false, $output = 'object' ) {
			$post_obj = false;	// return false by default

			if ( is_numeric( $use_post ) && $use_post > 0 ) {
				$post_obj = get_post( $use_post );

			} elseif ( $use_post === true && ! empty( $GLOBALS['post']->ID ) ) {
				$post_obj = $GLOBALS['post'];

			// used by the buddypress module
			} elseif ( $use_post === false && apply_filters( 'sucom_is_post_page', ( is_singular() ? true : false ), $use_post ) ) {
				$post_obj = get_queried_object();

			} elseif ( ! is_home() && is_front_page() && get_option( 'show_on_front' ) === 'page' ) {	// static front page
				$post_obj = get_post( get_option( 'page_on_front' ) );

			} elseif ( is_home() && ! is_front_page() && get_option( 'show_on_front' ) === 'page' ) {	// static posts page
				$post_obj = get_post( get_option( 'page_for_posts' ) );

			} elseif ( is_admin() ) {
				if ( ( $post_id = self::get_request_value( 'post_ID', 'POST' ) ) !== '' ||	// uses sanitize_text_field
					( $post_id = self::get_request_value( 'post', 'GET' ) ) !== '' )
						$post_obj = get_post( $post_id );
			}

			$post_obj = apply_filters( 'sucom_get_post_object', $post_obj, $use_post );

			switch ( $output ) {
				case 'id':
				case 'ID':
				case 'post_id':
					return isset( $post_obj->ID ) ?
						(int) $post_obj->ID : 0;	// cast as integer
					break;
				default:
					return is_object( $post_obj ) ?
						$post_obj : false;
					break;
			}
		}

		public static function maybe_load_post( $id, $force = false ) {
			global $post;
			if ( empty( $post ) || $force ) {
				$post = self::get_post_object( $id, 'object' );
				return true;
			} else return false;
		}

		public static function is_term_page( $term_id = 0, $tax_slug = '' ) {
			$ret = false;
			if ( is_numeric( $term_id ) && $term_id > 0 ) {
				$ret = term_exists( $term_id, $tax_slug );	// since wp 3.0
			} elseif ( is_tax() || is_category() || is_tag() ) {
				$ret = true;
			} elseif ( is_admin() ) {
				$screen_base = self::get_screen_base();
				if ( $screen_base === 'term' )	// since wp v4.5
					$ret = true;
				elseif ( ( $screen_base === false || $screen_base === 'edit-tags' ) &&	
					( self::get_request_value( 'taxonomy' ) !== '' &&	// uses sanitize_text_field
						self::get_request_value( 'tag_ID' ) !== '' ) )
							$ret = true;
			}
			return apply_filters( 'sucom_is_term_page', $ret );
		}

		public static function is_category_page( $term_id = 0 ) {
			$ret = false;
			if ( is_numeric( $term_id ) && $term_id > 0 ) {
				$ret = term_exists( $term_id, 'category' );	// since wp 3.0
			} elseif ( is_category() ) {
				$ret = true;
			} elseif ( is_admin() ) {
				if ( self::is_term_page() &&
					self::get_request_value( 'taxonomy' ) === 'category' )	// uses sanitize_text_field
						$ret = true;
			}
			return apply_filters( 'sucom_is_category_page', $ret );
		}

		public static function is_tag_page( $term_id = 0 ) {
			$ret = false;
			if ( is_numeric( $term_id ) && $term_id > 0 ) {
				$ret = term_exists( $term_id, 'post_tag' );	// since wp 3.0
			} elseif ( is_tag() ) {
				$ret = true;
			} elseif ( is_admin() ) {
				if ( self::is_term_page() &&
					self::get_request_value( 'taxonomy' ) === '_tag' )	// uses sanitize_text_field
						$ret = true;
			}
			return apply_filters( 'sucom_is_tag_page', $ret );
		}

		public static function get_term_object( $term_id = 0, $tax_slug = '', $output = 'object' ) {
			$term_obj = false;	// return false by default

			if ( is_numeric( $term_id ) && $term_id > 0 ) {
				$term_obj = get_term( (int) $term_id, (string) $tax_slug, OBJECT, 'raw' );

			} elseif ( apply_filters( 'sucom_is_term_page', is_tax() ) || is_tag() || is_category() ) {
				$term_obj = get_queried_object();

			} elseif ( is_admin() ) {
				if ( ( $tax_slug = self::get_request_value( 'taxonomy' ) ) !== '' &&	// uses sanitize_text_field
					( $term_id = self::get_request_value( 'tag_ID' ) ) !== '' )
						$term_obj = get_term( (int) $term_id, (string) $tax_slug, OBJECT, 'raw' );

			}

			$term_obj = apply_filters( 'sucom_get_term_object', $term_obj, $term_id, $tax_slug );

			switch ( $output ) {
				case 'id':
				case 'ID':
				case 'term_id':
					return isset( $term_obj->term_id ) ?
						(int) $term_obj->term_id : 0;		// cast as integer
					break;
				case 'taxonomy':
					return isset( $term_obj->taxonomy ) ?
						(string) $term_obj->taxonomy : '';	// cast as string
					break;
				default:
					return is_object( $term_obj ) ?
						$term_obj : false;
					break;
			}
		}

		public static function is_author_page( $user_id = 0 ) {
			return self::is_user_page( $user_id );
		}

		public static function is_user_page( $user_id = 0 ) {
			$ret = false;
			if ( is_numeric( $user_id ) && $user_id > 0 ) {
				$ret = self::user_exists( $user_id );
			} elseif ( is_author() ) {
				$ret = true;
			} elseif ( is_admin() ) {
				$screen_base = self::get_screen_base();
				if ( $screen_base !== false ) {
					switch ( $screen_base ) {
						case 'profile':
						case 'user-edit':
						case ( strpos( $screen_base, 'profile_page_' ) === 0 ? true : false ):
						case ( strpos( $screen_base, 'users_page_' ) === 0 ? true : false ):
							$ret = true;
							break;
					}
				} elseif ( self::get_request_value( 'user_id' ) !== '' || 	// called too early for screen
					basename( $_SERVER['PHP_SELF'] ) === 'profile.php' )
						$ret = true;
			}
			return apply_filters( 'sucom_is_user_page', $ret );
		}

		public static function user_exists( $user_id ) {
			if ( is_numeric( $user_id ) && $user_id > 0 ) {	// true is not valid
				$user_id = (int) $user_id;	// cast as integer for array
				if ( isset( self::$user_exists[$user_id] ) )
					return self::$user_exists[$user_id];
				else {
					global $wpdb;
					$select_sql = 'SELECT COUNT(ID) FROM '.$wpdb->users.' WHERE ID = %d';
					return self::$user_exists[$user_id] = $wpdb->get_var( $wpdb->prepare( $select_sql, $user_id ) ) ? true : false;
				}
			} else return false;
		}

		public static function get_author_object( $user_id = 0, $output = 'object' ) {
			return self::get_user_object( $user_id, $ret );
		}

		public static function get_user_object( $user_id = 0, $output = 'object' ) {
			$user_obj = false;	// return false by default

			if ( is_numeric( $user_id ) && $user_id > 0 ) {
				$user_obj = get_userdata( $user_id );

			} elseif ( apply_filters( 'sucom_is_user_page', is_author() ) ) {
				$user_obj = get_query_var( 'author_name' ) ?
					get_user_by( 'slug', get_query_var( 'author_name' ) ) :
					get_userdata( get_query_var( 'author' ) );

			} elseif ( is_admin() ) {
				if ( ( $user_id = self::get_request_value( 'user_id' ) ) === '' )	// uses sanitize_text_field
					$user_id = get_current_user_id();
				$user_obj = get_userdata( $user_id );
			}

			$user_obj = apply_filters( 'sucom_get_user_object', $user_obj, $user_id );

			switch ( $output ) {
				case 'id':
				case 'ID':
				case 'user_id':
					return isset( $user_obj->ID ) ?
						(int) $user_obj->ID : 0;	// cast as integer
					break;
				default:
					return is_object( $user_obj ) ?
						$user_obj : false;
					break;
			}
		}

		public static function is_product_page( $use_post = false, $product_obj = false ) {
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
			return apply_filters( 'sucom_is_product_page', $ret, $use_post, $product_obj );
		}

		public static function is_product_category() {
			$ret = false;
			if ( function_exists( 'is_product_category' ) &&
				is_product_category() )
					$ret = true;
			elseif ( is_admin() ) {
				if ( self::get_request_value( 'taxonomy' ) === 'product_cat' &&	// uses sanitize_text_field
					self::get_request_value( 'post_type' ) === 'product' )
						$ret = true;
			}
			return apply_filters( 'sucom_is_product_category', $ret );
		}

		public static function is_product_tag() {
			$ret = false;
			if ( function_exists( 'is_product_tag' ) &&
				is_product_tag() )
					$ret = true;
			elseif ( is_admin() ) {
				if ( self::get_request_value( 'taxonomy' ) === 'product_tag' &&	// uses sanitize_text_field
					self::get_request_value( 'post_type' ) === 'product' )
						$ret = true;
			}
			return apply_filters( 'sucom_is_product_tag', $ret );
		}

		public static function get_request_value( $key, $method = 'ANY', $default = '' ) {
			if ( $method === 'ANY' ) {
				$method = $_SERVER['REQUEST_METHOD'];
			}
			switch( $method ) {
				case 'POST':
					if ( isset( $_POST[$key] ) ) {
						return sanitize_text_field( $_POST[$key] );
					}
					break;
				case 'GET':
					if ( isset( $_GET[$key] ) ) {
						return sanitize_text_field( $_GET[$key] );
					}
					break;
			}
			return $default;
		}

		public static function encode_utf8( $decoded ) {
			if ( ! mb_detect_encoding( $decoded, 'UTF-8') == 'UTF-8' ) {
				$encoded = utf8_encode( $decoded );
			} else {
				$encoded = $decoded;
			}
			return $encoded;
		}

		public static function decode_utf8( $encoded ) {
			// if we don't have something to decode, return immediately
			if ( strpos( $encoded, '&#' ) === false ) {
				return $encoded;
			}
			// convert certain entities manually to something non-standard
			$encoded = preg_replace( '/&#8230;/', '...', $encoded );

			// if mb_decode_numericentity is not available, return the string un-converted
			if ( ! function_exists( 'mb_decode_numericentity' ) ) {
				return $encoded;
			}
			$decoded = preg_replace_callback( '/&#\d{2,5};/u',
				array( __CLASS__, 'decode_utf8_entity' ), $encoded );

			return $decoded;
		}

		public static function decode_utf8_entity( $matches ) {
			$convmap = array( 0x0, 0x10000, 0, 0xfffff );
			return mb_decode_numericentity( $matches[0], $convmap, 'UTF-8' );
		}

		public static function strip_html( $text ) {
			$text = self::strip_shortcodes( $text );					// remove any remaining shortcodes
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );				// put everything on one line
			$text = preg_replace( '/<\?.*\?'.'>/U', ' ', $text);				// remove php
			$text = preg_replace( '/<script\b[^>]*>(.*)<\/script>/Ui', ' ', $text);		// remove javascript
			$text = preg_replace( '/<style\b[^>]*>(.*)<\/style>/Ui', ' ', $text);		// remove inline stylesheets
			$text = preg_replace( '/<\/p>/i', ' ', $text);					// replace end of paragraph with a space
			$text = trim( strip_tags( $text ) );						// remove remaining html tags
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );			// replace 1+ spaces to a single space
			return trim( $text );
		}

		public static function strip_shortcodes( $text ) {
			if ( strpos( $text, '[' ) === false ) {		// optimize - return if no shortcodes
				return $text;
			}
			$shortcodes_preg = apply_filters( 'sucom_strip_shortcodes_preg', array(
				'/\[\/?(mk|rev_slider_|vc)_[^\]]+\]/',
			) );
			$text = preg_replace( $shortcodes_preg, ' ', $text );
			$text = strip_shortcodes( $text );		// strip any remaining registered shortcodes
			return $text;
		}

		public static function get_stripped_php( $file_path ) {
			$ret = '';
			if ( file_exists( $file_path ) ) {
				$content = file_get_contents( $file_path );
				$comments = array( T_COMMENT );
				if ( defined( 'T_DOC_COMMENT' ) ) {
					$comments[] = T_DOC_COMMENT;	// php 5
				}
				if ( defined( 'T_ML_COMMENT' ) ) {
					$comments[] = T_ML_COMMENT;	// php 4
				}
				$tokens = token_get_all( $content );
				foreach ( $tokens as $token ) {
					if ( is_array( $token ) ) {
						if ( in_array( $token[0], $comments ) ) {
							continue;
						}
						$token = $token[1];
					}
					$ret .= $token;
				}
			} else {
				$ret = false;
			}
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
			if ( function_exists( 'wp_encode_emoji' ) ) {
				return wp_encode_emoji( $content );		// since wp 4.2
			} elseif ( function_exists( 'mb_convert_encoding' ) ) {
				$regex = '/(
				     \x23\xE2\x83\xA3               # Digits
				     [\x30-\x39]\xE2\x83\xA3
				   | \xF0\x9F[\x85-\x88][\xA6-\xBF] # Enclosed characters
				   | \xF0\x9F[\x8C-\x97][\x80-\xBF] # Misc
				   | \xF0\x9F\x98[\x80-\xBF]        # Smilies
				   | \xF0\x9F\x99[\x80-\x8F]
				   | \xF0\x9F\x9A[\x80-\xBF]        # Transport and map symbols
				)/x';
				if ( preg_match_all( $regex, $content, $all_matches ) ) {
					if ( ! empty( $all_matches[1] ) ) {
						foreach ( $all_matches[1] as $emoji ) {
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

		// used to decode facebook video urls
		public static function replace_unicode_escape( $str ) {
			return preg_replace_callback( '/\\\\u([0-9a-f]{4})/i',
				array( __CLASS__, 'replace_unicode_escape_callback' ), $str );
		}

		private static function replace_unicode_escape_callback( $match ) {
			return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2' );
		}

		public static function json_encode_array( array $data, $options = 0, $depth = 32 ) {
			if ( function_exists( 'wp_json_encode' ) ) {
				return wp_json_encode( $data, $options, $depth );
			} elseif ( function_exists( 'json_encode' ) ) {
				return json_encode( $data, $options, $depth );
			} else {
				return '{}';	// empty string
			}
		}

		// returns self::$is_mobile cached value after first check
		public static function is_mobile() {
			if ( ! isset( self::$is_mobile ) ) {
				// load class object on first check
				if ( ! isset( self::$mobile_obj ) ) {
					if ( ! class_exists( 'SuextMobileDetect' ) ) {
						require_once dirname( __FILE__ ).'/../ext/mobile-detect.php';
					}
					self::$mobile_obj = new SuextMobileDetect();
				}
				self::$is_mobile = self::$mobile_obj->isMobile();
			}
			return self::$is_mobile;
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

			if ( ! is_array( $roles ) ) {
				$roles = array( $roles );
			}

			if ( ! $blog_id ) {
				$blog_id = get_current_blog_id();	// since wp 3.1
			}

			foreach ( $roles as $role ) {
				foreach ( get_users( array(
					'blog_id' => $blog_id,
					'role' => $role,
					'fields' => array(
						'id',
						'display_name'
					)
				) ) as $user ) {
					$ret[$user->display_name] = $user->id;
				}
			}

			// sort by the display name key value
			if ( defined( 'SORT_NATURAL' ) ) {	// available since PHP 5.4
				ksort( $ret, SORT_NATURAL );
			} else {
				uksort( $ret, 'strcasecmp' );	// case-insensitive string comparison
			}

			// add 'none' to create an associative array *before* flipping the array
			// in order to preserve the user id => display name association
			return array_flip( array_merge( array( 'none' => 'none' ), $ret ) );
		}

		public static function count_diff( &$arr, $max = 0 ) {
			$diff = 0;
			if ( ! is_array( $arr ) ) {
				return false;
			}
			if ( $max > 0 && $max >= count( $arr ) ) {
				$diff = $max - count( $arr );
			}
			return $diff;
		}

		public static function get_alpha2_countries() {
			if ( ! class_exists( 'SucomCountryCodes' ) ) {
				require_once dirname( __FILE__ ).'/country-codes.php';
			}
			return SucomCountryCodes::get( 'alpha2' );
		}

		public static function get_alpha2_country_name( $country_code, $default_code = false ) {

			if ( empty( $country_code ) || $country_code === 'none' ) {
				return false;
			}

			if ( ! class_exists( 'SucomCountryCodes' ) ) {
				require_once dirname( __FILE__ ).'/country-codes.php';
			}

			$countries = SucomCountryCodes::get( 'alpha2' );

			if ( ! isset( $countries[$country_code] ) ) {
				if ( $default_code === false || ! isset( $countries[$default_code] ) ) {
					return false;
				} else {
					return $countries[$default_code];
				}
			} else {
				return $countries[$country_code];
			}
		}

		public static function get_hours_range( $start = 0, $end = 86400, $step = 3600, $format = 'g:i a' ) {
			$times = array();
		        foreach ( range( $start, $end, $step ) as $timestamp ) {
				$hour_mins = gmdate( 'H:i', $timestamp );
				if ( ! empty( $format ) ) {
					$times[$hour_mins] = gmdate( $format, $timestamp );
				} else {
					$times[$hour_mins] = $hour_mins;
				}
			}
			return $times;
		}

		public static function get_column_rows( array $table_cells, $row_cols = 2, $hide_in_basic = false ) {
			sort( $table_cells );
			$table_rows = array();
			$per_col = ceil( count( $table_cells ) / $row_cols );
			foreach ( $table_cells as $num => $cell ) {
				if ( empty( $table_rows[ $num % $per_col ] ) )	// initialize the array element
					$table_rows[ $num % $per_col ] = $hide_in_basic ?
						'<tr class="hide_in_basic">' : '';
				$table_rows[ $num % $per_col ] .= $cell;	// create the html for each row
			}
			return $table_rows;
		}

		public static function get_theme_slug_version( $stylesheet = null, $theme_root = null ) {
			$theme = wp_get_theme( $stylesheet, $theme_root );
			return $theme->get_template().'-'.$theme->Version;
		}

		public static function get_image_sizes() {
			global $_wp_additional_image_sizes;
			$sizes = array();
			foreach ( get_intermediate_image_sizes() as $size_name )
				$sizes[$size_name] = self::get_size_info( $size_name );
			return $sizes;
		}

		public static function get_size_info( $size_name = 'thumbnail' ) {
			if ( is_integer( $size_name ) )
				return;
			if ( is_array( $size_name ) )
				return;

			global $_wp_additional_image_sizes;

			if ( isset( $_wp_additional_image_sizes[$size_name]['width'] ) )
				$width = intval( $_wp_additional_image_sizes[$size_name]['width'] );
			else $width = get_option( $size_name.'_size_w' );

			if ( isset( $_wp_additional_image_sizes[$size_name]['height'] ) )
				$height = intval( $_wp_additional_image_sizes[$size_name]['height'] );
			else $height = get_option( $size_name.'_size_h' );

			if ( isset( $_wp_additional_image_sizes[$size_name]['crop'] ) )
				$crop = $_wp_additional_image_sizes[$size_name]['crop'];
			else $crop = get_option( $size_name.'_crop' );

			if ( ! is_array( $crop ) )
				$crop = empty( $crop ) ? false : true;

			return array( 'width' => $width, 'height' => $height, 'crop' => $crop );
		}

		// returns the class and id attributes
		public static function get_atts_css_attr( array $atts, $css_name, $css_extra = '' ) {
			$css_class = $css_name.'-'.
				( empty( $atts['css_class'] ) ?
					'button' : $atts['css_class'] );

			if ( ! empty( $css_extra ) )
				$css_class = $css_extra.' '.$css_class;

			return 'class="'.$css_class.'" id="'.self::get_atts_src_id( $atts, $css_name ).'"';
		}

		public static function get_atts_src_id( array $atts, $src_name ) {
			$src_id = $src_name.'-'.
				( empty( $atts['css_id'] ) ?
					'button' : $atts['css_id'] );

			if ( ! empty( $atts['use_post'] ) || is_singular() || in_the_loop() ) {
				global $post;
				if ( ! empty( $post->ID ) ) {
					$src_id .= '-post-'.$post->ID;
				}
			}

			return $src_id;
		}

		public static function is_toplevel_edit( $hook_name ) {
			return strpos( $hook_name, 'toplevel_page_' ) !== false && (
				( self::get_request_value( 'action', 'GET' ) === 'edit' &&	// uses sanitize_text_field
					(int) self::get_request_value( 'post', 'GET' ) > 0 ) ||
				( self::get_request_value( 'action', 'GET' ) === 'create_new' &&
					self::get_request_value( 'return', 'GET' ) === 'edit' )
			) ? true : false;
		}

		public static function is_true( $mixed, $allow_null = false ) {
			$ret_bool = is_string( $mixed ) ?
				filter_var( $mixed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : (bool) $mixed;
		        return $ret_bool === null && ! $allow_null ?
				false : $ret_bool;
		}

		// converts string to boolean
		public static function get_bool( $mixed ) {
			return is_string( $mixed ) ?
				filter_var( $mixed, FILTER_VALIDATE_BOOLEAN ) : (bool) $mixed;
		}

		// glob() returns false on error
		public static function get_header_files() {
			$ret_array = array();
			$parent_dir = get_template_directory();
			$child_dir = get_stylesheet_directory();
			$header_files = (array) glob( $parent_dir.'/header*.php' );

			if ( $parent_dir !== $child_dir ) {
				$header_files = array_merge( $header_files,
					(array) glob( $child_dir.'/header*.php' ) );
			}

			foreach ( $header_files as $tmpl_file ) {
				$tmpl_base = basename( $tmpl_file );
				$ret_array[$tmpl_base] = $tmpl_file;	// child tmpl overwrites parent
			}

			return $ret_array;
		}

		public static function get_at_name( $val ) {
			if ( $val !== '' ) {
				$val = substr( preg_replace( array( '/^.*\//',
					'/[^a-zA-Z0-9_]/' ), '', $val ), 0, 15 );
				if ( ! empty( $val ) )  {
					$val = '@'.$val;
				}
			}
			return $val;
		}

		public static function is_amp() {
			if ( ! defined( 'AMP_QUERY_VAR' ) ) {
				$is_amp = false;
			} else {
				$is_amp = get_query_var( AMP_QUERY_VAR, false ) ? true : false;
			}
			return $is_amp;
		}

		public static function minify_css( $css_data, $lca ) {
			if ( ! empty( $css_data ) ) {
				$classname = apply_filters( $lca.'_load_lib', false, 'ext/compressor', 'SuextMinifyCssCompressor' );
				if ( $classname !== false && class_exists( $classname ) ) {
					$css_data = call_user_func( array( $classname, 'process' ), $css_data );
				}
			}
			return $css_data;
		}

		public static function add_pkg_name( &$name, $type ) {
			$name = self::get_pkg_name( $name, $type );
		}

		public static function get_pkg_name( $name, $type ) {
			if ( strpos( $name, $type ) !== false ) {
				$name = preg_replace( '/^(.*) '.$type.'( \(.+\))?$/U', '$1$2', $name );
			}
			return preg_replace( '/^(.*)( \(.+\))?$/U', '$1 '.$type.'$2', $name );
		}
	}
}

?>

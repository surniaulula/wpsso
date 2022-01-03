<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2022 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomUtilWP' ) ) {

	require_once dirname( __FILE__ ) . '/util-wp.php';
}

if ( ! class_exists( 'SucomUtil' ) ) {

	class SucomUtil {

		private static $cache_locale  = array();	// Saved get_locale() values.
		private static $cache_protect = array();	// Saved protect_filter_value() values.

		private static $currencies = array(
			'AED' => 'United Arab Emirates dirham',
			'AFN' => 'Afghan afghani',
			'ALL' => 'Albanian lek',
			'AMD' => 'Armenian dram',
			'ANG' => 'Netherlands Antillean guilder',
			'AOA' => 'Angolan kwanza',
			'ARS' => 'Argentine peso',
			'AUD' => 'Australian dollar',
			'AWG' => 'Aruban florin',
			'AZN' => 'Azerbaijani manat',
			'BAM' => 'Bosnia and Herzegovina convertible mark',
			'BBD' => 'Barbadian dollar',
			'BDT' => 'Bangladeshi taka',
			'BGN' => 'Bulgarian lev',
			'BHD' => 'Bahraini dinar',
			'BIF' => 'Burundian franc',
			'BMD' => 'Bermudian dollar',
			'BND' => 'Brunei dollar',
			'BOB' => 'Bolivian boliviano',
			'BRL' => 'Brazilian real',
			'BSD' => 'Bahamian dollar',
			'BTC' => 'Bitcoin',
			'BTN' => 'Bhutanese ngultrum',
			'BWP' => 'Botswana pula',
			'BYR' => 'Belarusian ruble',
			'BZD' => 'Belize dollar',
			'GBP' => 'British pound',
			'CAD' => 'Canadian dollar',
			'CDF' => 'Congolese franc',
			'CHF' => 'Swiss franc',
			'CLP' => 'Chilean peso',
			'CNY' => 'Chinese yuan',
			'COP' => 'Colombian peso',
			'CRC' => 'Costa Rican col&oacute;n',
			'CUC' => 'Cuban convertible peso',
			'CUP' => 'Cuban peso',
			'CVE' => 'Cape Verdean escudo',
			'CZK' => 'Czech koruna',
			'DJF' => 'Djiboutian franc',
			'DKK' => 'Danish krone',
			'DOP' => 'Dominican peso',
			'DZD' => 'Algerian dinar',
			'EGP' => 'Egyptian pound',
			'ERN' => 'Eritrean nakfa',
			'ETB' => 'Ethiopian birr',
			'EUR' => 'Euro',
			'FJD' => 'Fijian dollar',
			'FKP' => 'Falkland Islands pound',
			'GEL' => 'Georgian lari',
			'GGP' => 'Guernsey pound',
			'GHS' => 'Ghana cedi',
			'GIP' => 'Gibraltar pound',
			'GMD' => 'Gambian dalasi',
			'GNF' => 'Guinean franc',
			'GTQ' => 'Guatemalan quetzal',
			'GYD' => 'Guyanese dollar',
			'HKD' => 'Hong Kong dollar',
			'HNL' => 'Honduran lempira',
			'HRK' => 'Croatian kuna',
			'HTG' => 'Haitian gourde',
			'HUF' => 'Hungarian forint',
			'IDR' => 'Indonesian rupiah',
			'ILS' => 'Israeli new shekel',
			'IMP' => 'Manx pound',
			'INR' => 'Indian rupee',
			'IQD' => 'Iraqi dinar',
			'IRR' => 'Iranian rial',
			'IRT' => 'Iranian toman',
			'ISK' => 'Icelandic kr&oacute;na',
			'JEP' => 'Jersey pound',
			'JMD' => 'Jamaican dollar',
			'JOD' => 'Jordanian dinar',
			'JPY' => 'Japanese yen',
			'KES' => 'Kenyan shilling',
			'KGS' => 'Kyrgyzstani som',
			'KHR' => 'Cambodian riel',
			'KMF' => 'Comorian franc',
			'KPW' => 'North Korean won',
			'KRW' => 'South Korean won',
			'KWD' => 'Kuwaiti dinar',
			'KYD' => 'Cayman Islands dollar',
			'KZT' => 'Kazakhstani tenge',
			'LAK' => 'Lao kip',
			'LBP' => 'Lebanese pound',
			'LKR' => 'Sri Lankan rupee',
			'LRD' => 'Liberian dollar',
			'LSL' => 'Lesotho loti',
			'LYD' => 'Libyan dinar',
			'MAD' => 'Moroccan dirham',
			'MDL' => 'Moldovan leu',
			'MGA' => 'Malagasy ariary',
			'MKD' => 'Macedonian denar',
			'MMK' => 'Burmese kyat',
			'MNT' => 'Mongolian t&ouml;gr&ouml;g',
			'MOP' => 'Macanese pataca',
			'MRO' => 'Mauritanian ouguiya',
			'MUR' => 'Mauritian rupee',
			'MVR' => 'Maldivian rufiyaa',
			'MWK' => 'Malawian kwacha',
			'MXN' => 'Mexican peso',
			'MYR' => 'Malaysian ringgit',
			'MZN' => 'Mozambican metical',
			'NAD' => 'Namibian dollar',
			'NGN' => 'Nigerian naira',
			'NIO' => 'Nicaraguan c&oacute;rdoba',
			'NOK' => 'Norwegian krone',
			'NPR' => 'Nepalese rupee',
			'NZD' => 'New Zealand dollar',
			'OMR' => 'Omani rial',
			'PAB' => 'Panamanian balboa',
			'PEN' => 'Peruvian nuevo sol',
			'PGK' => 'Papua New Guinean kina',
			'PHP' => 'Philippine peso',
			'PKR' => 'Pakistani rupee',
			'PLN' => 'Polish z&#x142;oty',
			'PRB' => 'Transnistrian ruble',
			'PYG' => 'Paraguayan guaran&iacute;',
			'QAR' => 'Qatari riyal',
			'RON' => 'Romanian leu',
			'RSD' => 'Serbian dinar',
			'RUB' => 'Russian ruble',
			'RWF' => 'Rwandan franc',
			'SAR' => 'Saudi riyal',
			'SBD' => 'Solomon Islands dollar',
			'SCR' => 'Seychellois rupee',
			'SDG' => 'Sudanese pound',
			'SEK' => 'Swedish krona',
			'SGD' => 'Singapore dollar',
			'SHP' => 'Saint Helena pound',
			'SLL' => 'Sierra Leonean leone',
			'SOS' => 'Somali shilling',
			'SRD' => 'Surinamese dollar',
			'SSP' => 'South Sudanese pound',
			'STD' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra',
			'SYP' => 'Syrian pound',
			'SZL' => 'Swazi lilangeni',
			'THB' => 'Thai baht',
			'TJS' => 'Tajikistani somoni',
			'TMT' => 'Turkmenistan manat',
			'TND' => 'Tunisian dinar',
			'TOP' => 'Tongan pa&#x2bb;anga',
			'TRY' => 'Turkish lira',
			'TTD' => 'Trinidad and Tobago dollar',
			'TWD' => 'New Taiwan dollar',
			'TZS' => 'Tanzanian shilling',
			'UAH' => 'Ukrainian hryvnia',
			'UGX' => 'Ugandan shilling',
			'USD' => 'United States dollar',
			'UYU' => 'Uruguayan peso',
			'UZS' => 'Uzbekistani som',
			'VEF' => 'Venezuelan bol&iacute;var',
			'VND' => 'Vietnamese &#x111;&#x1ed3;ng',
			'VUV' => 'Vanuatu vatu',
			'WST' => 'Samoan t&#x101;l&#x101;',
			'XAF' => 'Central African CFA franc',
			'XCD' => 'East Caribbean dollar',
			'XOF' => 'West African CFA franc',
			'XPF' => 'CFP franc',
			'YER' => 'Yemeni rial',
			'ZAR' => 'South African rand',
			'ZMW' => 'Zambian kwacha',
		);

		private static $currency_symbols = array(
			'AED' => '&#x62f;.&#x625;',
			'AFN' => '&#x60b;',
			'ALL' => 'L',
			'AMD' => 'AMD',
			'ANG' => '&fnof;',
			'AOA' => 'Kz',
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => 'Afl.',
			'AZN' => 'AZN',
			'BAM' => 'KM',
			'BBD' => '&#36;',
			'BDT' => '&#2547;&nbsp;',
			'BGN' => '&#1083;&#1074;.',
			'BHD' => '.&#x62f;.&#x628;',
			'BIF' => 'Fr',
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => 'Bs.',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTC' => '&#3647;',
			'BTN' => 'Nu.',
			'BWP' => 'P',
			'BYR' => 'Br',
			'BZD' => '&#36;',
			'CAD' => '&#36;',
			'CDF' => 'Fr',
			'CHF' => '&#67;&#72;&#70;',
			'CLP' => '&#36;',
			'CNY' => '&yen;',
			'COP' => '&#36;',
			'CRC' => '&#x20a1;',
			'CUC' => '&#36;',
			'CUP' => '&#36;',
			'CVE' => '&#36;',
			'CZK' => '&#75;&#269;',
			'DJF' => 'Fr',
			'DKK' => 'DKK',
			'DOP' => 'RD&#36;',
			'DZD' => '&#x62f;.&#x62c;',
			'EGP' => 'EGP',
			'ERN' => 'Nfk',
			'ETB' => 'Br',
			'EUR' => '&euro;',
			'FJD' => '&#36;',
			'FKP' => '&pound;',
			'GBP' => '&pound;',
			'GEL' => '&#x10da;',
			'GGP' => '&pound;',
			'GHS' => '&#x20b5;',
			'GIP' => '&pound;',
			'GMD' => 'D',
			'GNF' => 'Fr',
			'GTQ' => 'Q',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => 'L',
			'HRK' => 'Kn',
			'HTG' => 'G',
			'HUF' => '&#70;&#116;',
			'IDR' => 'Rp',
			'ILS' => '&#8362;',
			'IMP' => '&pound;',
			'INR' => '&#8377;',
			'IQD' => '&#x639;.&#x62f;',
			'IRR' => '&#xfdfc;',
			'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
			'ISK' => 'kr.',
			'JEP' => '&pound;',
			'JMD' => '&#36;',
			'JOD' => '&#x62f;.&#x627;',
			'JPY' => '&yen;',
			'KES' => 'KSh',
			'KGS' => '&#x441;&#x43e;&#x43c;',
			'KHR' => '&#x17db;',
			'KMF' => 'Fr',
			'KPW' => '&#x20a9;',
			'KRW' => '&#8361;',
			'KWD' => '&#x62f;.&#x643;',
			'KYD' => '&#36;',
			'KZT' => 'KZT',
			'LAK' => '&#8365;',
			'LBP' => '&#x644;.&#x644;',
			'LKR' => '&#xdbb;&#xdd4;',
			'LRD' => '&#36;',
			'LSL' => 'L',
			'LYD' => '&#x644;.&#x62f;',
			'MAD' => '&#x62f;.&#x645;.',
			'MDL' => 'MDL',
			'MGA' => 'Ar',
			'MKD' => '&#x434;&#x435;&#x43d;',
			'MMK' => 'Ks',
			'MNT' => '&#x20ae;',
			'MOP' => 'P',
			'MRO' => 'UM',
			'MUR' => '&#x20a8;',
			'MVR' => '.&#x783;',
			'MWK' => 'MK',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => 'MT',
			'NAD' => '&#36;',
			'NGN' => '&#8358;',
			'NIO' => 'C&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#x631;.&#x639;.',
			'PAB' => 'B/.',
			'PEN' => 'S/.',
			'PGK' => 'K',
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PRB' => '&#x440;.',
			'PYG' => '&#8370;',
			'QAR' => '&#x631;.&#x642;',
			'RMB' => '&yen;',
			'RON' => 'lei',
			'RSD' => '&#x434;&#x438;&#x43d;.',
			'RUB' => '&#8381;',
			'RWF' => 'Fr',
			'SAR' => '&#x631;.&#x633;',
			'SBD' => '&#36;',
			'SCR' => '&#x20a8;',
			'SDG' => '&#x62c;.&#x633;.',
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&pound;',
			'SLL' => 'Le',
			'SOS' => 'Sh',
			'SRD' => '&#36;',
			'SSP' => '&pound;',
			'STD' => 'Db',
			'SYP' => '&#x644;.&#x633;',
			'SZL' => 'L',
			'THB' => '&#3647;',
			'TJS' => '&#x405;&#x41c;',
			'TMT' => 'm',
			'TND' => '&#x62f;.&#x62a;',
			'TOP' => 'T&#36;',
			'TRY' => '&#8378;',
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => 'Sh',
			'UAH' => '&#8372;',
			'UGX' => 'UGX',
			'USD' => '&#36;',
			'UYU' => '&#36;',
			'UZS' => 'UZS',
			'VEF' => 'Bs F',
			'VND' => '&#8363;',
			'VUV' => 'Vt',
			'WST' => 'T',
			'XAF' => 'Fr',
			'XCD' => '&#36;',
			'XOF' => 'Fr',
			'XPF' => 'Fr',
			'YER' => '&#xfdfc;',
			'ZAR' => '&#82;',
			'ZMW' => 'ZK',
		);

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

			/**
			 * https://developers.facebook.com/docs/messenger-platform/messenger-profile/supported-locales
			 */
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

			/**
			 * https://developers.google.com/+/web/api/supported-languages
			 */
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

			/**
			 * https://www.tumblr.com/docs/en/share_button
			 */
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

			/**
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

		public static function get_min_int() {

			return defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : -2147483648;	// Since PHP v7.0.0.
		}

		public static function get_max_int() {

			return defined( 'PHP_INT_MAX' ) ? PHP_INT_MAX : 2147483647;	// Since PHP 5.0.2.
		}

		/**
		 * Use "tz" in the method name to hint that method argument is an abbreviation.
		 */
		public static function get_tz_name( $tz_abbr ) {

			return timezone_name_from_abbr( $tz_abbr );
		}

		/**
		 * Get a timezone abbreviation (ie. 'EST', 'MDT', etc.).
		 */
		public static function get_timezone_abbr( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'T' );
		}

		/**
		 * Timezone offset in seconds (offset west of UTC is negative, and east of UTC is positive).
		 */
		public static function get_timezone_offset_secs( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'Z' );
		}

		/**
		 * Timezone difference to UTC with colon between hours and minutes.
		 */
		public static function get_timezone_offset_hours( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'P' );
		}

		public static function get_formatted_timezone( $tz_name, $format ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $tz_name ][ $format ] ) ) {

				return $local_cache[ $tz_name ][ $format ];
			}

			$dt = new DateTime();

			$dt->setTimeZone( new DateTimeZone( $tz_name ) );

			return $local_cache[ $tz_name ][ $format ] = $dt->format( $format );
		}

		private static function maybe_get_array( $arr, $key = false, $add_none = false ) {

			if ( null === $key ) {

				/**
				 * Nothing to do.
				 */

			} elseif ( false === $key ) {

				/**
				 * Nothing to do.
				 */

			} elseif ( true === $key ) {				// Sort by value.

				asort( $arr );

			} elseif ( isset( $arr[ $key ] ) ) {			// Return a specific array value.

				return $arr[ $key ];

			} else {
				return null;					// Array key not found - return null.
			}

			if ( true === $add_none ) { 				// Prefix array with 'none'.

				$arr = array( 'none' => 'none' ) + $arr; 	// Maintains numeric index.
			}

			return $arr;
		}

		public static function get_currencies( $currency_abbrev = false, $add_none = false, $format = '%2$s (%1$s)' ) {

			static $local_cache = array(); // Array of arrays, indexed by $format.

			if ( ! isset( $local_cache[ $format ] ) ) {

				if ( $format === '%2$s' ) { // Optimize and get existing format.

					$local_cache[ $format ] =& self::$currencies;

				} else {

					foreach ( self::$currencies as $key => $value ) {

						$local_cache[ $format ][ $key ] = sprintf( $format, $key, $value );
					}
				}

				asort( $local_cache[ $format ] ); // Sort by value.
			}

			return self::maybe_get_array( $local_cache[ $format ], $currency_abbrev, $add_none );
		}

		public static function get_currency_abbrev( $currency_abbrev = false, $add_none = false ) {

			static $local_cache = null;

			if ( ! isset( $local_cache ) ) {

				$local_cache = array();

				/**
				 * Create an array of currency abbrev => abbrev values.
				 */
				foreach ( self::$currencies as $key => $value ) {

					$local_cache[ $key ] = $key;	// Example: USD => USD
				}

				ksort( $local_cache ); // Sort by key (same as value).
			}

			return self::maybe_get_array( $local_cache, $currency_abbrev, $add_none );
		}

		public static function get_currency_symbol_abbrev( $currency_symbol = false, $default = 'USD', $decode = true ) {

			if ( $decode ) {

				$currency_symbol = self::decode_html( $currency_symbol );
			}

			static $local_cache = array();

			if ( isset( $local_cache[ $currency_symbol ] ) ) {

				return $local_cache[ $currency_symbol ];

			} elseif ( $currency_symbol === '$' ) {	// Optimize and match for USD first.

				return $local_cache[ $currency_symbol ] = 'USD';
			}

			/**
			 * Optionally decode the currency symbol values.
			 */
			$currency_symbols = self::get_currency_symbols( $currency_abbrev = false, $add_none = false, $decode );

			if ( is_array( $currency_symbols ) ) {	// Just in case.

				foreach ( $currency_symbols as $key => $value ) {	// Example: USD => $

					if ( $value === $currency_symbol ) {		// Example: $ === $

						/**
						 * Cache by currency symbol and return the currency abbreviation.
						 */
						return $local_cache[ $currency_symbol ] = $key;
					}
				}
			}

			return $local_cache[ $currency_symbol ] = $default;
		}

		public static function get_currency_symbols( $currency_abbrev = false, $add_none = false, $decode = false ) {

			if ( $decode ) {

				static $local_cache = null;

				if ( ! isset( $local_cache ) ) {

					$local_cache = array();

					foreach ( self::$currency_symbols as $key => $value ) {

						$local_cache[ $key ] = self::decode_html( $value );	// Example: USD => $
					}

					ksort( $local_cache ); // Sort by key.
				}

				return self::maybe_get_array( $local_cache, $currency_abbrev, $add_none );
			}

			return self::maybe_get_array( self::$currency_symbols, $currency_abbrev, $add_none );
		}

		public static function get_dashicons( $icon_number = false, $add_none = false ) {

			return self::maybe_get_array( self::$dashicons, $icon_number, $add_none );
		}

		public static function get_pub_lang( $pub = '' ) {

			switch ( $pub ) {

				case 'facebook':
				case 'fb':

					return self::$pub_lang[ 'facebook' ];

				case 'google':

					return self::$pub_lang[ 'google' ];

				case 'pinterest':
				case 'pin':
				case 'rp':

					return self::$pub_lang[ 'pinterest' ];

				default:

					if ( isset( self::$pub_lang[ $pub ] ) ) {

						return self::$pub_lang[ $pub ];

					}

					return array();
			}
		}

		public static function maybe_link_url( $mixed ) {

			if ( is_string( $mixed ) && 0 === strpos( $mixed, 'http' ) ) {

				$mixed = '<a href="' . $mixed . '">' . $mixed . '</a>';
			}

			return $mixed;
		}

		public static function is_valid_day( $hm_o, $hm_c ) {

			/**
			 * Performa a quick sanitation before using strtotime().
			 */
			if ( empty( $hm_o ) || empty( $hm_c ) || 'none' === $hm_o || 'none' === $hm_c ) {

				return false;
			}

			$hm_o_time = strtotime( $hm_o );
			$hm_c_time = strtotime( $hm_c );

			if ( $hm_o_time < $hm_c_time ) {

				return true;
			}

			return false;
		}

		/**
		 * Checks for 'none' and invalid times for midday close and open.
		 */
		public static function is_valid_midday( $hm_o, $hm_midday_c, $hm_midday_o, $hm_c ) {

			/**
			 * Performa a quick sanitation before using strtotime().
			 */
			if ( empty( $hm_o ) || empty( $hm_c ) || 'none' === $hm_o || 'none' === $hm_c ) {

				return false;
			}

			if ( empty( $hm_midday_c ) || empty( $hm_midday_o ) || 'none' === $hm_midday_c || 'none' === $hm_midday_o ) {

				return false;
			}

			$hm_o_time        = strtotime( $hm_o );
			$hm_midday_c_time = strtotime( $hm_midday_c );
			$hm_midday_o_time = strtotime( $hm_midday_o );
			$hm_c_time        = strtotime( $hm_c );

			if ( $hm_o_time < $hm_midday_c_time ) {

				if ( $hm_midday_c_time < $hm_midday_o_time ) {

					if ( $hm_midday_o_time < $hm_c_time ) {

						return true;
					}
				}
			}

			return false;
		}

		public static function is_amp() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				if ( is_admin() ) {

					$local_cache = false;

				/**
				 * The amp_is_request() function cannot be called before the 'wp' action has run, so if the 'wp'
				 * action has not run, leave the $local_cache as null to allow for future checks.
				 */
				} elseif ( function_exists( 'amp_is_request' ) ) {

					if ( did_action( 'wp' ) ) {

						$local_cache = amp_is_request();
					}

				} elseif ( function_exists( 'is_amp_endpoint' ) ) {

					$local_cache = is_amp_endpoint();

				} elseif ( function_exists( 'ampforwp_is_amp_endpoint' ) ) {

					$local_cache = ampforwp_is_amp_endpoint();

				} elseif ( defined( 'AMP_QUERY_VAR' ) ) {

					$local_cache = get_query_var( AMP_QUERY_VAR, false ) ? true : false;

				} else {

					$local_cache = false;
				}
			}

			return $local_cache;
		}

		/**
		 * Deprecated on 2020/10/02.
		 */
		public static function is_mobile() {

			_deprecated_function( __METHOD__ . '()', '2020/10/02', $replacement = '' );	// Deprecation message.

			return null;
		}

		/**
		 * Deprecated on 2020/10/02.
		 */
		public static function is_desktop() {

			_deprecated_function( __METHOD__ . '()', '2020/10/02', $replacement = '' );	// Deprecation message.

			return null;
		}

		public static function is_https( $url = '' ) {

			static $local_cache = array();

			if ( isset( $local_cache[ $url ] ) ) {

				return $local_cache[ $url ];
			}

			if ( strpos( $url, '://' ) ) {

				if ( 'https' === parse_url( $url, PHP_URL_SCHEME ) ) {

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

		/**
		 * Return the current request URL and remove tracking query arguments by default.
		 */
		public static function get_url( $remove_tracking = true ) {

			$url = esc_url_raw( self::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ] );

			/**
			 * Maybe remove tracking query arguments used by facebook, google, etc.
			 */
			if ( $remove_tracking ) {

				static $tracking_args = null;

				if ( null === $tracking_args ) {	// Do only once.

					$tracking_args = array(
						'fb_action_ids',
						'fb_action_types',
						'fb_source',
						'fb_aggregation_id',
						'utm_medium',
						'utm_source',
						'utm_campaign',
						'utm_content',
						'utm_term',
						'gclid',
						'pk_campaign',
						'pk_kwd',
					);

					$tracking_args = (array) apply_filters( 'sucom_remove_tracking_args', $tracking_args );

					$tracking_args = array_flip( $tracking_args );	// Move values to keys.

					$tracking_args = array_fill_keys( array_keys( $tracking_args ), false );	// Set all values to false.
				}

				if ( ! empty( $tracking_args ) ) {	// Just in case.

					$url = add_query_arg( $tracking_args, $url );	// Remove all keys with false values.
				}
			}

			return $url;
		}

		public static function get_prot( $url = '' ) {

			if ( ! empty( $url ) ) {

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

		public static function update_prot( $url = '' ) {

			if ( 0 === strpos( $url, '/' ) ) { // Skip relative URLs.

				return $url;
			}

			$prot_slash = self::get_prot() . '://';

			if ( 0 === strpos( $url, $prot_slash ) ) { // Skip correct URLs.

				return $url;
			}

			return preg_replace( '/^([a-z]+:\/\/)/', $prot_slash, $url );
		}

		public static function get_const( $const, $undef = null ) {

			if ( defined( $const ) ) {

				return constant( $const );

			}

			return $undef;
		}

		/**
		 * Returns false or the admin screen id string.
		 */
		public static function get_screen_id( $screen = false ) {

			if ( false === $screen && function_exists( 'get_current_screen' ) ) {

				$screen = get_current_screen();
			}

			if ( isset( $screen->id ) ) {

				return $screen->id;
			}

			return false;
		}

		/**
		 * Returns false or the admin screen base string.
		 */
		public static function get_screen_base( $screen = false ) {

			if ( false === $screen && function_exists( 'get_current_screen' ) ) {

				$screen = get_current_screen();
			}

			if ( isset( $screen->base ) ) {

				return $screen->base;
			}

			return false;
		}

		public static function get_use_post_string( $use_post ) {

			$use_post = self::sanitize_use_post( $use_post );

			if ( false === $use_post ) {

				return 'false';

			} elseif ( true === $use_post ) {

				return 'true';
			}

			return (string) $use_post;
		}

		public static function maybe_unserialize_array( array $arr ) {

			return self::array_map_recursive( 'maybe_unserialize', $arr );
		}

		public static function array_map_recursive( $func, array $arr ) {

			foreach ( $arr as $key => $el ) {

				$arr[ $key ] = is_array( $el ) ? self::array_map_recursive( $func, $el ) : $func( $el );
			}

			return $arr;
		}

		/**
		 * Note that an empty string or a null is sanitized as false.
		 *
		 * Used by the wpssorrssb_get_sharing_buttons() function and the WpssoRrssbShortcodeSharing->do_shortcode() method.
		 */
		public static function sanitize_use_post( $mixed, $default = true ) {

			if ( is_array( $mixed ) ) {

				$use_post = isset( $mixed[ 'use_post' ] ) ? $mixed[ 'use_post' ] : $default;

			} elseif ( is_object( $mixed ) ) {

				$use_post = isset( $mixed->use_post ) ? $mixed->use_post : $default;

			} else {

				$use_post = $mixed;
			}

			if ( empty( $use_post ) || 'false' === $use_post ) { // 0, false, or 'false'

				return false;

			} elseif ( is_numeric( $use_post ) ) {

				return (int) $use_post;
			}

			return $default;
		}

		public static function sanitize_file_path( $file_path ) {

			if ( empty( $file_path ) ) {

				return false;
			}

			$file_path = implode( $glue = '/', array_map( array( __CLASS__, 'sanitize_file_name' ), explode( '/', $file_path ) ) );

			return $file_path;
		}

		public static function sanitize_file_name( $file_name ) {

			$special_chars = array(
				'?',
				'[',
				']',
				'/',
				'\\',
				'=',
				'<',
				'>',
				':',
				';',
				',',
				'\'',
				'"',
				'&',
				'$',
				'#',
				'*',
				'(',
				')',
				'|',
				'~',
				'`',
				'!',
				'{',
				'}',
				'%',
				'+',
				chr( 0 )
			);

			$file_name = preg_replace( '#\x{00a0}#siu', ' ', $file_name );
			$file_name = str_replace( $special_chars, '', $file_name );
			$file_name = str_replace( array( '%20', '+' ), '-', $file_name );
			$file_name = preg_replace( '/[\r\n\t -]+/', '-', $file_name );
			$file_name = trim( $file_name, '.-_' );

			return $file_name;
		}

		public static function sanitize_tag( $tag ) {

			$tag = sanitize_title_with_dashes( $tag, '', 'display' );

			$tag = urldecode( $tag );

			return $tag;
		}

		/**
		 * Note that hashtags cannot begin with a number - this method truncates tags that begin with a number.
		 */
		public static function sanitize_hashtags( array $tags = array() ) {

			return preg_replace(
				array( '/^[0-9].*/', '/[ \[\]#!\$\?\\\\\/\*\+\.\-\^]/', '/^.+/' ),
				array( '', '', '#$0' ),
				$tags
			);
		}

		public static function sanitize_hookname( $name ) {

			$name = preg_replace( '/[#:\/\-\. ]+/', '_', $name );

			$name = rtrim( $name, '_' );

			return self::sanitize_key( $name );
		}

		public static function sanitize_classname( $name, $allow_underscore = true ) {

			$name = preg_replace( '/[#:\/\-\. ' . ( $allow_underscore ? '' : '_' ) . ']+/', '', $name );

			return self::sanitize_key( $name );
		}

		public static function sanitize_locale( $locale ) {

			$locale = str_replace( '-', '_', $locale );	// Convert 'en-US' to 'en_US'.

			$locale = preg_replace( '/[^a-zA-Z_]/', '', $locale );

			return $locale;
		}

		/**
		 * Unlike the WordPress sanitize_key() function, this method allows for a colon and upper case characters.
		 */
		public static function sanitize_key( $key, $allow_upper = false ) {

			$key = preg_replace( '/[^a-zA-Z0-9\-_:]/', '', $key );

			return trim( $allow_upper ? $key : strtolower( $key ) );
		}

		public static function sanitize_css_class( $class ) {

			return trim( preg_replace( '/[^a-zA-Z0-9\-_ ]/', '-', $class ) );	// Allow spaces between css class names.
		}

		/**
		 * Do not allow colons and periods as they may cause issues with some browsers, CSS editors and Javascript
		 * framworks. jQuery, for example, has issues with ids that contain periods and colons.
		 */
		public static function sanitize_css_id( $id ) {

			return trim( preg_replace( '/[^a-zA-Z0-9\-_]/', '-', $id ) );
		}

		public static function array_key_last( array $array ) {

			if ( function_exists( 'array_key_last' ) ) {

				return array_key_last( $array );	// Since PHP v7.3.
			}

			return key( array_slice( $array, -1, 1, true ) );
		}

		public static function array_to_keywords( array $tags = array() ) {

			$keywords = array_map( 'sanitize_text_field', $tags );

			$keywords = trim( implode( $glue = ', ', $keywords ) );

			return $keywords;
		}

		public static function array_to_hashtags( array $tags = array() ) {

			$hashtags = self::sanitize_hashtags( $tags );

			$hashtags = array_filter( $hashtags );	// Removes empty array elements.

			$hashtags = trim( implode( $glue = ' ', $hashtags ) );

			return $hashtags;
		}

		public static function explode_csv( $str ) {

			if ( empty( $str ) ) {

				return array();
			}

			return array_map( array( __CLASS__, 'unquote_csv_value' ), explode( ',', $str ) );
		}

		private static function unquote_csv_value( $val ) {

			return trim( $val, '\'" ' ); // Remove quotes and spaces.
		}

		public static function titleize( $str ) {

			return ucwords( preg_replace( '/[:\/\-\._]+/', ' ', self::decamelize( $str ) ) );
		}

		public static function decamelize( $str ) {

			return ltrim( strtolower( preg_replace('/[A-Z]/', '_$0', $str ) ), '_' );
		}

		/**
		 * Check that the id value is not true, false, null, or 'none'.
		 */
		public static function is_valid_option_id( $id ) {

			if ( true === $id ) {

				return false;

			} elseif ( empty( $id ) && ! is_numeric( $id ) ) { // Null or false.

				return false;

			} elseif ( 'none' === $id ) {	// Disabled option.

				return false;

			}

			return true;
		}

		/**
		 * Since WPSSO Core v1.21.0.
		 *
		 * Note that an empty array is not an associative array (ie. returns false for an empty array).
		 */
		public static function is_assoc( $mixed ) {

			$ret = false;

			if ( ! empty( $mixed ) ) {	// Optimize.

				if ( is_array( $mixed ) ) {	// Just in case.

					if ( ! is_numeric( implode( array_keys( $mixed ) ) ) ) {

						$ret = true;
					}
				}
			}

			return $ret;
		}

		/**
		 * Since WPSSO Core v7.7.0.
		 *
		 * Note that an empty array is not an associative array (ie. returns false for an empty array).
		 */
		public static function is_non_assoc( $mixed ) {

			$ret = false;

			if ( is_array( $mixed ) ) {	// Just in case.

				if ( empty( $mixed ) ) {	// Optimize.

					$ret = true;

				} elseif ( is_numeric( implode( array_keys( $mixed ) ) ) ) {

					$ret = true;
				}
			}

			return $ret;
		}

		/**
		 * Since WPSSO Core v4.17.0.
		 */
		public static function a_to_aa( array $arr ) {

			$arr_arr = array();

			foreach ( $arr as $el ) {

				$arr_arr[][] = $el;
			}

			return $arr_arr;
		}

		/**
		 * Returns the number of bytes in a serialized array.
		 */
		public static function serialized_len( array $arr ) {

			$serialized = serialize( $arr );

			if ( function_exists( 'mb_strlen' ) ) {	// Just in case.

				return mb_strlen( $serialized, '8bit' );
			}

			return strlen( $serialized );
		}

		/**
		 * Deprecated on 2020/10/20.
		 */
		public static function get_open_close( array $opts, $key_day_o, $key_midday_close, $key_midday_o, $key_day_c ) {

			_deprecated_function( __METHOD__ . '()', '2020/10/20', $replacement = __CLASS__ . '::get_opts_open_close_hm_tz()' );	// Deprecation message.

			return self::get_opts_open_close_hm_tz( $opts, $key_day_o, $key_midday_close, $key_midday_o, $key_day_c );
		}

		/**
		 * Returns an empty array or an associative array of open => close hours, including a timezone offset.
		 *
		 * $open_close = Array (
		 *	[08:00-07:00] => 17:00-07:00
		 * )
		 *
		 * -07:00 is a timezone offset.
		 */
		public static function get_opts_open_close_hm_tz( array $opts, $key_day_o, $key_midday_c, $key_midday_o, $key_day_c, $key_tz = '' ) {

			$oc_pairs        = array();
			$is_valid_day    = false;
			$is_valid_midday = false;

			if ( ! empty( $opts[ $key_day_o ] ) && ! empty( $opts[ $key_day_c ] ) ) {

				$is_valid_day = self::is_valid_day( $opts[ $key_day_o ], $opts[ $key_day_c ] );

				if ( ! empty( $opts[ $key_midday_c ] ) && ! empty( $opts[ $key_midday_o ] ) ) {

					$is_valid_midday = self::is_valid_midday( $opts[ $key_day_o ], $opts[ $key_midday_c ], $opts[ $key_midday_o ], $opts[ $key_day_c ] );
				}

				if ( $is_valid_day ) {

					$timezone  = empty( $key_tz ) || empty( $opts[ $key_tz ] ) ? SucomUtilWP::get_default_timezone() : $opts[ $key_tz ];
					$tz_offset = self::get_timezone_offset_hours( $timezone );
					$hm_tz_o   = $opts[ $key_day_o ] . $tz_offset;
					$hm_tz_c   = $opts[ $key_day_c ] . $tz_offset;

					if ( $is_valid_midday ) {

						$hm_tz_midday_c = $opts[ $key_midday_c ] . $tz_offset;
						$hm_tz_midday_o = $opts[ $key_midday_o ] . $tz_offset;

						$oc_pairs[ $hm_tz_o ]        = $hm_tz_midday_c;
						$oc_pairs[ $hm_tz_midday_o ] = $hm_tz_c;

					} else {

						$oc_pairs[ $hm_tz_o ] = $hm_tz_c;
					}
				}
			}

			return $oc_pairs;
		}

		public static function get_opts_hm_tz( array $opts, $key_hm, $key_tz = '' ) {

			if ( ! empty( $opts[ $key_hm ] ) ) {

				$timezone  = empty( $key_tz ) || empty( $opts[ $key_tz ] ) ? SucomUtilWP::get_default_timezone() : $opts[ $key_tz ];
				$tz_offset = self::get_timezone_offset_hours( $timezone );
				$hm_tz     = $opts[ $key_hm ] . $tz_offset;

				return $hm_tz;
			}

			return false;
		}

		public static function get_opts_begin( $str, array $opts ) {

			$found = array();

			foreach ( $opts as $key => $value ) {

				if ( 0 === strpos( $key, $str ) ) {

					$found[ $key ] = $value;
				}
			}

			return $found;
		}

		public static function natksort( &$assoc_arr ) {

			return uksort( $assoc_arr, 'strnatcmp' );
		}

		/**
		 * Since 2021/09/17.
		 */
		public static function unset_numeric_keys( &$assoc_arr ) {

			foreach ( array_keys( $assoc_arr ) as $key ) {

				if ( is_numeric( $key ) ) {

					unset( $assoc_arr[ $key ] );
				}
			}
		}

		public static function unset_from_assoc( &$assoc_arr1, $assoc_arr2 ) {

			foreach ( array_keys( $assoc_arr2 ) as $key ) {

				unset( $assoc_arr1[ $key ] );
			}
		}

		/**
		 * The $key_preg value must be a string.
		 *
		 * The $replace value can be a string or an associative array of 'pattern' => 'replacement'.
		 */
		public static function preg_grep_keys( $key_preg, array $in_arr, $invert = false, $replace = false ) {

			if ( empty( $in_arr ) ) {	// Nothing to do.

				return $in_arr;
			}

			$in_arr_keys = array_keys( $in_arr );

			$matched_keys = preg_grep( $key_preg, $in_arr_keys, $invert ? PREG_GREP_INVERT : null );

			if ( empty( $matched_keys ) && $invert ) {	// Nothing to do.

				return $in_arr;
			}

			/**
		 	 * The $replace value can be a string or an associative array of 'pattern' => 'replacement'.
			 */
			if ( is_array( $replace ) ) {

				$patterns     = array_keys( $replace );
				$replacements = array_values( $replace );

			} else {

				$patterns     = $key_preg;
				$replacements = $replace;
			}

			$out_arr  = array();

			foreach ( $matched_keys as $key ) {

				if ( false === $replace ) {	// Element key remains unchanged.

					$out_arr[ $key ] = $in_arr[ $key ];

				} else {

					$fixed = preg_replace( $patterns, $replacements, $key );

					$out_arr[ $fixed ] = $in_arr[ $key ];
				}
			}

			return $out_arr;
		}

		public static function next_key( $needle, array &$input, $loop = true ) {

			$keys = array_keys( $input );

			$pos = array_search( $needle, $keys );

			if ( false !== $pos ) {

				if ( isset( $keys[ $pos + 1 ] ) ) {

					return $keys[ $pos + 1 ];

				} elseif ( true === $loop ) {

					return $keys[ 0 ];
				}
			}

			return false;
		}

		/**
		 * Move an associative array element to the end.
		 */
		public static function move_to_end( array &$assoc_arr, $key ) {

			if ( array_key_exists( $key, $assoc_arr ) ) {

				$val = $assoc_arr[ $key ];

				unset( $assoc_arr[ $key ] );

				$assoc_arr[ $key ] = $val;
			}

			return $assoc_arr;
		}

		public static function move_to_front( array &$arr, $key ) {

			if ( array_key_exists( $key, $arr ) ) {

				$val = $arr[ $key ];

				$arr = array_merge( array( $key => $val ), $arr );
			}

			return $arr;
		}

		/**
		 * Modifies the referenced array directly, and returns true or false.
		 */
		public static function add_after_key( array &$arr, $match_key, $mixed, $add_value = null ) {

			return self::insert_in_array( $insert = 'after', $arr, $match_key, $mixed, $add_value, $ret_bool = true );
		}

		private static function insert_in_array( $insert, array &$arr, $match_key, $mixed, $add_value = null, $ret_bool = false ) {

			$matched = false;

			if ( array_key_exists( $match_key, $arr ) ) {

				$new_arr = array();

				foreach ( $arr as $key => $val ) {

					if ( 'after' === $insert ) {

						$new_arr[ $key ] = $val;
					}

					/**
					 * Add new value before/after the matched key.
					 *
					 * Replace the matched key by default (no test required).
					 */
					if ( $key === $match_key ) {

						if ( is_array( $mixed ) ) {

							$new_arr = array_merge( $new_arr, $mixed );

						} elseif ( is_string( $mixed ) ) {

							$new_arr[ $mixed ] = $add_value;

						} else {

							$new_arr[] = $add_value;
						}

						$matched = true;
					}

					if ( 'before' === $insert ) {

						$new_arr[ $key ] = $val;
					}
				}

				$arr = $new_arr;

				unset( $new_arr );
			}

			return $ret_bool ? $matched : $arr; // Return true/false or the array (default).
		}

		/**
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

				} else {

					$merged[ $key ] = $value;
				}
			}

			return $merged;
		}

		public static function array_flatten( array $arr ) {

			$return = array();

		        foreach ( $arr as $key => $value ) {

				if ( is_array( $value ) ) {

					$return = array_merge( $return, self::array_flatten( $value ) );

				} else {

					$return[ $key ] = $value;
				}
			}

			return $return;
		}

		public static function array_implode( array $arr, $glue = ' ' ) {

			$return = '';

		        foreach ( $arr as $value ) {

			        if ( is_array( $value ) ) {

					$return .= self::array_implode( $value, $glue ) . $glue;

				} else {

					$return .= $value . $glue;
				}
			}

			return strlen( $glue ) ? rtrim( $return, $glue ) : $glue;
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

		public static function has_array_element( $needle, array $arr, $strict = false ) {

			foreach ( $arr as $key => $element ) {

				if ( ( $strict ? $element === $needle : $element == $needle ) ||
					( is_array( $element ) && self::has_array_element( $needle, $element, $strict ) ) ) {

					return true;
				}
			}

			return false;
		}

		public static function get_first_value( array $arr ) {

			foreach ( $arr as $value ) {

				return $value;
			}

			return null;	// Return null if array is empty.
		}

		public static function get_first_num( array $input ) {

			list( $first, $last, $next ) = self::get_first_last_next_nums( $input );

			return $first;
		}

		public static function get_last_num( array $input ) {

			list( $first, $last, $next ) = self::get_first_last_next_nums( $input );

			return $last;
		}

		public static function get_next_num( array $input ) {

			list( $first, $last, $next ) = self::get_first_last_next_nums( $input );

			return $next;
		}

		public static function get_first_last_next_nums( array $input ) {

			$keys  = array_keys( $input );
			$count = count( $keys );

			if ( $count && ! is_numeric( implode( $keys ) ) ) { // Check for non-numeric keys.

				$keys = array();

				foreach ( $input as $key => $value ) { // Keep only the numeric keys.

					if ( is_numeric( $key ) ) {

						$keys[] = $key;
					}
				}

				$count = count( $keys );
			}

			sort( $keys ); // Sort numerically.

			$first = (int) reset( $keys );       // Get the first number.
			$last  = (int) end( $keys );         // Get the last number.
			$next  = $count ? $last + 1 : $last; // Next is 0 (not 1) for an empty array.

			return array( $first, $last, $next );
		}

		public static function get_mt_og_seed() {

			return array(
				'fb:admins'       => null,
				'fb:app_id'       => null,
				'og:type'         => null,
				'og:url'          => null,
				'og:locale'       => null,
				'og:site_name'    => null,
				'og:title'        => null,
				'og:description'  => null,
				'og:updated_time' => null,
				'og:video'        => null,
				'og:image'        => null,
			);
		}

		/**
		 * Pre-define the array key order for the list() construct.
		 */
		public static function get_mt_image_seed( $mt_pre = 'og', array $mt_og = array() ) {

			$mt_ret = array(
				$mt_pre . ':image:secure_url' => '',
				$mt_pre . ':image:url'        => '',
				$mt_pre . ':image:width'      => '',
				$mt_pre . ':image:height'     => '',
				$mt_pre . ':image:cropped'    => '',	// Non-standard / internal meta tag.
				$mt_pre . ':image:id'         => '',	// Non-standard / internal meta tag.
				$mt_pre . ':image:alt'        => '',
				$mt_pre . ':image:size_name'  => '',	// Non-standard / internal meta tag.
			);

			return self::maybe_merge_mt_og( $mt_ret, $mt_og );
		}

		/**
		 * This method is used by e-Commerce modules to pre-define and pre-sort the product meta tags.
		 */
		public static function get_mt_product_seed( $mt_pre = 'product', array $mt_og = array() ) {

			$mt_ret = array(

				/**
				 * Product part numbers.
				 */
				$mt_pre . ':item_group_id'    => '',	// Product variant group ID.
				$mt_pre . ':retailer_item_id' => '',	// Product ID.
				$mt_pre . ':retailer_part_no' => '',	// Product SKU.
				$mt_pre . ':mfr_part_no'      => '',	// Product MPN.
				$mt_pre . ':ean'              => '',	// aka EAN, EAN-13, GTIN-13.
				$mt_pre . ':gtin14'           => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin13'           => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin12'           => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin8'            => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin'             => '',	// Non-standard / internal meta tag.
				$mt_pre . ':isbn'             => '',
				$mt_pre . ':upc'              => '',	// Aka the UPC, UPC-A, UPC, GTIN-12.

				/**
				 * Product attributes and descriptions.
				 */
				$mt_pre . ':url'                => '',	// Non-standard / internal meta tag.
				$mt_pre . ':age_group'          => '',
				$mt_pre . ':availability'       => '',
				$mt_pre . ':brand'              => '',
				$mt_pre . ':category'           => '',	// The product category according to the Google product taxonomy.
				$mt_pre . ':retailer_category'  => '',	// Non-standard / internal meta tag.
				$mt_pre . ':condition'          => '',
				$mt_pre . ':expiration_time'    => '',
				$mt_pre . ':color'              => '',
				$mt_pre . ':material'           => '',
				$mt_pre . ':pattern'            => '',
				$mt_pre . ':purchase_limit'     => '',
				$mt_pre . ':quantity:value'     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:minimum'   => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:maximum'   => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:unit_code' => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:unit_text' => '',	// Non-standard / internal meta tag.
				$mt_pre . ':target_gender'      => '',
				$mt_pre . ':size'               => '',
				$mt_pre . ':size_type'          => '',	// Non-standard / internal meta tag.

				/**
				 * Product ratings and reviews.
				 */
				$mt_pre . ':rating:average' => '',	// Non-standard / internal meta tag.
				$mt_pre . ':rating:count'   => '',	// Non-standard / internal meta tag.
				$mt_pre . ':rating:worst'   => '',	// Non-standard / internal meta tag.
				$mt_pre . ':rating:best'    => '',	// Non-standard / internal meta tag.
				$mt_pre . ':review:count'   => '',	// Non-standard / internal meta tag.

				/**
				 * Product measurements and weight.
				 */
				$mt_pre . ':depth:value'        => '',	// Non-standard / internal meta tag.
				$mt_pre . ':depth:units'        => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':height:value'       => '',	// Non-standard / internal meta tag.
				$mt_pre . ':height:units'       => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':length:value'       => '',	// Non-standard / internal meta tag.
				$mt_pre . ':length:units'       => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':fluid_volume:value' => '',	// Non-standard / internal meta tag.
				$mt_pre . ':fluid_volume:units' => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':weight:value'       => '',
				$mt_pre . ':weight:units'       => '',
				$mt_pre . ':width:value'        => '',	// Non-standard / internal meta tag.
				$mt_pre . ':width:units'        => '',	// Non-standard / internal meta tag (units after value).

				/**
				 * Product prices and shipping.
				 */
				$mt_pre . ':original_price:amount'           => '',
				$mt_pre . ':original_price:currency'         => '',
				$mt_pre . ':pretax_price:amount'             => '',
				$mt_pre . ':pretax_price:currency'           => '',
				$mt_pre . ':price:amount'                    => '',
				$mt_pre . ':price:currency'                  => '',
				$mt_pre . ':sale_price:amount'               => '',
				$mt_pre . ':sale_price:currency'             => '',
				$mt_pre . ':sale_price_dates:start'          => '',
				$mt_pre . ':sale_price_dates:start_date'     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:start_time'     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:start_timezone' => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:start_iso'      => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:end'            => '',
				$mt_pre . ':sale_price_dates:end_date'       => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:end_time'       => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:end_timezone'   => '',	// Non-standard / internal meta tag.
				$mt_pre . ':sale_price_dates:end_iso'        => '',	// Non-standard / internal meta tag.
				$mt_pre . ':shipping_cost:amount'            => '',
				$mt_pre . ':shipping_cost:currency'          => '',
				$mt_pre . ':shipping_weight:value'           => '',
				$mt_pre . ':shipping_weight:units'           => '',
			);

			if ( isset( $mt_og[ 'og:type' ] ) ) {

				if ( $mt_og[ 'og:type' ] === 'product' ) {

					$mt_ret[ $mt_pre . ':offers' ]  = array();		// Non-standard / internal meta tag.
					$mt_ret[ $mt_pre . ':reviews' ] = array();		// Non-standard / internal meta tag.
				}
			}

			return self::maybe_merge_mt_og( $mt_ret, $mt_og );
		}

		public static function get_mt_video_seed( $mt_pre = 'og', array $mt_og = array() ) {

			$mt_ret = array(
				$mt_pre . ':video:secure_url'      => '',
				$mt_pre . ':video:url'             => '',
				$mt_pre . ':video:type'            => '',	// Example: 'application/x-shockwave-flash' or 'text/html'.
				$mt_pre . ':video:width'           => '',
				$mt_pre . ':video:height'          => '',
				$mt_pre . ':video:tag'             => array(),
				$mt_pre . ':video:duration'        => '',	// Non-standard / internal meta tag.
				$mt_pre . ':video:upload_date'     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':video:thumbnail_url'   => '',	// Non-standard / internal meta tag.
				$mt_pre . ':video:embed_url'       => '',	// Non-standard / internal meta tag.
				$mt_pre . ':video:stream_url'      => '',	// Non-standard / internal meta tag. VideoObject contentUrl.
				$mt_pre . ':video:has_image'       => false,	// Non-standard / internal meta tag.
				$mt_pre . ':video:title'           => '',	// Non-standard / internal meta tag.
				$mt_pre . ':video:description'     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':video:iphone_name'     => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:iphone_id'       => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:iphone_url'      => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:ipad_name'       => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:ipad_id'         => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:ipad_url'        => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:googleplay_name' => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:googleplay_id'   => '',	// Non-standard / internal meta tag for Twitter player card.
				$mt_pre . ':video:googleplay_url'  => '',	// Non-standard / internal meta tag for Twitter player card.
			);

			$mt_ret += self::get_mt_image_seed( $mt_pre );

			/**
			 * Facebook applink meta tags.
			 */
			if ( $mt_pre === 'og' ) {

				$mt_ret += array(
					'al:ios:app_name'        => '',
					'al:ios:app_store_id'    => '',
					'al:ios:url'             => '',
					'al:android:app_name'    => '',
					'al:android:package'     => '',
					'al:android:url'         => '',
					'al:web:url'             => '',
					'al:web:should_fallback' => 'false',
				);
			}

			return self::maybe_merge_mt_og( $mt_ret, $mt_og );
		}

		/**
		 * Private method used by get_mt_image_seed(), get_mt_product_seed(), and get_mt_video_seed().
		 */
		private static function maybe_merge_mt_og( array $mt_ret, array $mt_og ) {

			if ( empty( $mt_og ) ) {	// Nothing to merge.

				return $mt_ret;
			}

			/**
			 * Always keep the 'og:type' meta tag top-most.
			 *
			 * Note that isset() does not return true for array keys that correspond to a null value, while
			 * array_key_exists() does, so use array_key_exists() here.
			 */
			if ( array_key_exists( 'og:type', $mt_og ) ) {

				return array_merge( array( 'og:type' => $mt_og[ 'og:type' ] ), $mt_ret, $mt_og );
			}

			return array_merge( $mt_ret, $mt_og );
		}

		/**
		 * Deprecated on 2020/08/10.
		 */
		public static function get_mt_media_url( array $assoc, $media_pre = 'og:image', $mt_suffixes = null ) {

			_deprecated_function( __METHOD__ . '()', '2020/08/10', $replacement = __CLASS__ . '::get_first_mt_media_url()' );	// Deprecation message.

			return self::get_first_mt_media_url( $assoc, $media_pre, $mt_suffixes );
		}

		/**
		 * Return the first URL from the associative array (og:image:secure_url, og:image:url, og:image).
		 */
		public static function get_first_mt_media_url( array $assoc, $media_pre = 'og:image', $mt_suffixes = null ) {

			if ( ! is_array( $mt_suffixes ) ) {	// Array of meta tag suffixes to use.

				$mt_suffixes = array( ':secure_url', ':url', '', ':embed_url', ':stream_url' );
			}

			/**
			 * Check for two dimensional arrays and keep following the first array element.
			 *
			 * Prefer the $media_pre array key (if it's available).
			 */
			if ( isset( $assoc[ $media_pre ] ) && is_array( $assoc[ $media_pre ] ) ) {

				$first_media = reset( $assoc[ $media_pre ] );

			} else {

				$first_media = reset( $assoc );	// Can be array or string.
			}

			if ( is_array( $first_media ) ) {	// Recurse until we hit bottom (ie. we have a string).

				return self::get_first_mt_media_url( $first_media, $media_pre );
			}

			/**
			 * First element is a text string, so check the array keys.
			 */
			foreach ( $mt_suffixes as $mt_suffix ) {

				if ( ! empty( $assoc[ $media_pre . $mt_suffix ] ) ) {

					return $assoc[ $media_pre . $mt_suffix ];	// Return first match.
				}
			}

			return ''; // Empty string.
		}

		public static function get_first_og_image_url( array $assoc ) {

			return self::get_first_mt_media_url( $assoc, $media_pre = 'og:image', $mt_suffixes = array( ':secure_url', ':url', '' ) );
		}

		/**
		 * Check for a local translated file name, and if found, return a file path and file URL to the translated version.
		 */
		public static function get_file_path_locale( $file_path, $file_url = false ) {

			if ( preg_match( '/^(.*)(\.[a-z0-9]+)$/', $file_path, $matches ) ) {

				if ( ! empty( $matches[ 2 ] ) ) {	// Just in case.

					$file_path_locale = $matches[ 1 ] . '-' . self::get_locale() . $matches[ 2 ];

					if ( file_exists( $file_path_locale ) ) {

						if ( $file_url ) {

							$file_base = basename( $file_path );

							$file_base_locale = basename( $file_path_locale );

							$file_url = str_replace( '/' . $file_base, '/' . $file_base_locale, $file_url );
						}

						$file_path = $file_path_locale;
					}
				}
			}

			if ( $file_url ) {

				return array( $file_path, $file_url );
			}

			return $file_path;
		}

		/**
		 * Translate HTML headers, paragraphs, and list items.
		 */
		public static function get_html_transl( $html, $text_domain ) {

			$gettext = self::get_html_gettext( $html, $text_domain );

			foreach ( $gettext as $repl => $arr ) {

				$transl = _x( $arr[ 'text' ], $arr[ 'context' ], $arr[ 'text_domain' ] );

				$html = str_replace( $repl, $arr[ 'begin' ] . $transl . $arr[ 'end' ], $html );
			}

			return $html;
		}

		public static function get_html_gettext( $html, $text_domain ) {

			$gettext = array();

			foreach ( array(
				'/(<h[0-9][^>]*>)(.*)(<\/h[0-9]>)/Uis'         => 'html header',
				'/(<p>|<p [^>]*>)(.*)(<\/p>)/Uis'              => 'html paragraph',	// Get paragraphs before list items.
				'/(<li[^>]*>)(.*)(<\/li>)/Uis'                 => 'html list item',
				'/(<blockquote[^>]*>)(.*)(<\/blockquote>)/Uis' => 'html blockquote',
			) as $pattern => $context ) {

				if ( preg_match_all( $pattern, $html, $all_matches, PREG_SET_ORDER ) ) {

					foreach ( $all_matches as $num => $matches ) {

						list( $match, $begin, $text, $end ) = $matches;

						$html = str_replace( $match, '', $html );	// Do not match again.

						$text = trim( $text );	// Just in case.

						if ( '' === $text ) {	// Ignore HTML tags with no content.

							continue;
						}

						$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );	// Put everything on one line.

						$gettext[ $match ] = array(
							'begin'       => $begin,
							'text'        => $text,
							'end'         => $end,
							'context'     => $context,
							'text_domain' => $text_domain,
						);
					}
				}
			}

			return $gettext;
		}

		public static function show_html_gettext_php( $html, $text_domain ) {

			$gettext = self::get_html_gettext( $html, $text_domain );

			foreach ( $gettext as $repl => $arr ) {

				$arr[ 'text' ] = str_replace( '\'', '\\\'', $arr[ 'text' ] );

				echo '_x( \'' . $arr[ 'text' ] . '\', \'' . $arr[ 'context' ] . '\', \'' . $arr[ 'text_domain' ] . '\' );' . "\n";
			}
		}

		public static function show_lib_gettext_php( $mixed, $context, $text_domain ) {

			if ( is_array( $mixed ) ) {

				foreach ( $mixed as $key => $val ) {

					if ( 'admin' === $key ) {

						continue;
					}

					self::show_lib_gettext_php( $val, $context, $text_domain );
				}

				return;

			} elseif ( is_numeric( $mixed ) ) {	// Number.

				return;

			} elseif ( empty( $mixed ) ) {		// Empty.

				return;

			} elseif ( 0 === strpos( $mixed, '/' ) ) {	// Regular expression.

				return;

			} elseif ( false !== filter_var( $mixed, FILTER_VALIDATE_URL ) ) {	// URL.

				return;
			}

			$mixed = str_replace( '\'', '\\\'', $mixed );

			echo '_x( \'' . $mixed . '\', \'' . $context . '\', \'' . $text_domain . '\' );' . "\n";
		}

		public static function transl_key_values( $pattern, array &$opts, $text_domain ) {

			foreach ( self::preg_grep_keys( $pattern, $opts ) as $key => $val ) {

				$locale_key = self::get_key_locale( $key );

				if ( $locale_key !== $key && empty( $opts[ $locale_key ] ) ) {

					$val_transl = _x( $val, 'option value', $text_domain );

					if ( $val_transl !== $val ) {

						$opts[ $locale_key ] = $val_transl;
					}
				}
			}
		}

		/**
		 * Returns an option value or null.
		 *
		 * Note that for non-existing keys or empty strings, this methods will return the default non-localized value.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_key_value( $key, array $opts, $mixed = 'current' ) {

			$key_locale = self::get_key_locale( $key, $opts, $mixed );
			$val_locale = isset( $opts[ $key_locale ] ) ? $opts[ $key_locale ] : null;

			if ( ! isset( $opts[ $key_locale ] ) || '' === $opts[ $key_locale ] ) {

				if ( false !== strpos( $key_locale, '#' ) ) {

					$key_default = self::get_key_locale( $key_locale, $opts, 'default' );

					if ( $key_locale !== $key_default ) {

						return isset( $opts[ $key_default ] ) ? $opts[ $key_default ] : $val_locale;
					}

					return $val_locale;
				}

				return $val_locale;

			}

			return $val_locale;
		}

		public static function set_key_locale( $key, $value, &$opts, $mixed = 'current' ) {

			$key_locale = self::get_key_locale( $key, $opts, $mixed );

			$opts[ $key_locale ] = $value;
		}

		/**
		 * Localize an options array key.
		 *
		 * $opts = false | array
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_key_locale( $key, $opts = false, $mixed = 'current' ) {

			/**
			 * Remove any pre-existing locale value.
			 */
			if ( false !== ( $pos = strpos( $key, '#' ) ) ) {

				$key = substr( $key, 0, $pos );
			}

			$default    = self::get_locale( 'default' );	// Uses a static cache.
			$locale     = self::get_locale( $mixed );	// Uses a static cache.
			$key_locale = $key . '#' . $locale;

			/**
			 * The default language for the WordPress site may have changed in the past, so if we're using the default,
			 * check for a locale version of the default language.
			 */
			if ( $locale === $default ) {

				return isset( $opts[ $key_locale ] ) ? $key_locale : $key;
			}

			return $key_locale;
		}

		public static function get_multi_key_locale( $prefix, array &$opts, $add_none = false ) {

			$current = self::get_locale();			// Uses a static cache.
			$default = self::get_locale( 'default' );	// Uses a static cache.
			$matches = self::preg_grep_keys( '/^' . $prefix . '_([0-9]+)(#.*)?$/', $opts );
			$results = array();

			foreach ( $matches as $key => $value ) {

				$num = preg_replace( '/^' . $prefix . '_([0-9]+)(#.*)?$/', '$1', $key );

				if ( ! empty( $results[ $num ] ) ) { // Preserve the first non-blank value.

					continue;

				} elseif ( ! empty( $opts[ $prefix . '_' . $num . '#' . $current ] ) ) { // Current locale.

					$results[ $num ] = $opts[ $prefix . '_' . $num . '#' . $current ];

				} elseif ( ! empty( $opts[ $prefix . '_' . $num . '#' . $default ] ) ) { // Default locale.

					$results[ $num ] = $opts[ $prefix . '_' . $num . '#' . $default ];

				} elseif ( ! empty( $opts[ $prefix . '_' . $num ] ) ) { // No locale.

					$results[ $num ] = $opts[ $prefix . '_' . $num ];

				} else { // Use value (could be empty).

					$results[ $num ] = $value;
				}
			}

			asort( $results ); // Sort values for display.

			if ( $add_none ) {

				$results = array( 'none' => 'none' ) + $results; // Maintain numeric index.
			}

			return $results;
		}

		public static function refresh_current_locale_cache() {

			self::get_locale( $mixed = 'current', $read_cache = false );
		}

		/**
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_locale( $mixed = 'current', $read_cache = true ) {

			$cache_index = is_array( $mixed ) ? $mixed[ 'name' ] . '_' . $mixed[ 'id' ] : $mixed;

			if ( $read_cache ) {

				if ( isset( self::$cache_locale[ $cache_index ] ) ) {

					return self::$cache_locale[ $cache_index ];
				}
			}

			if ( 'default' === $mixed ) {

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

					if ( false !== $ms_locale ) {

						$locale = $ms_locale;
					}

				} else {

					$db_locale = get_option( 'WPLANG' );

					if ( false !== $db_locale ) {

						$locale = $db_locale;
					}
				}

				if ( empty( $locale ) ) {

					$locale = 'en_US';	// Just in case.
				}

			} else {

				/**
				 * get_user_locale() is available since WP v4.7.0, so make sure it exists before calling it. :)
				 */
				$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			}

			/**
			 * Filtered by WpssoProLangPolylang->filter_get_locale() and WpssoProLangWpml->filter_get_locale().
			 */
			$locale = apply_filters( 'sucom_get_locale', $locale, $mixed );

			return self::$cache_locale[ $cache_index ] = $locale;
		}

		public static function get_available_feed_locale_names() {

			$locale_names = self::get_available_locale_names();

			$locale_names = apply_filters( 'sucom_available_feed_locale_names', $locale_names );

			return $locale_names;
		}

		public static function get_available_locale_names() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/translation-install.php';

			$translations  = wp_get_available_translations();	// Since WP v4.0.
			$avail_locales = self::get_available_locales();
			$local_cache   = array();

			foreach ( $avail_locales as $locale ) {

				if ( isset( $translations[ $locale ][ 'native_name' ] ) ) {

					$native_name = $translations[ $locale ][ 'native_name' ];

				} elseif ( 'en_US' === $locale ) {

					$native_name = 'English (United States)';

				} else {

					$native_name = $locale;
				}

				$local_cache[ $locale ] = $native_name;
			}

			$local_cache = apply_filters( 'sucom_available_locale_names', $local_cache );

			return $local_cache;
		}

		public static function get_available_locales() {

			static $local_cache = null;

			if ( null !== $local_cache ) {

				return $local_cache;
			}

			$local_cache = get_available_languages();

			$default_locale = self::get_locale( 'default' );	// Uses a static cache.

			if ( ! is_array( $local_cache ) ) {	// Just in case.

				$local_cache = array( $default_locale );

			} elseif ( ! in_array( $default_locale, $local_cache ) ) {	// Just in case.

				$local_cache[] = $default_locale;
			}

			sort( $local_cache );

			$local_cache = apply_filters( 'sucom_available_locales', $local_cache );

			return $local_cache;
		}

		/**
		 * Add metadata defaults and custom values to the $type_opts array.
		 *
		 * $type_opts can be false, an empty array, or an array of one or more options.
		 */
		public static function add_type_opts_md_pad( &$type_opts, array $mod, array $opts_md_pre = array() ) {

			if ( is_object( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_defs = (array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] );
				$md_opts = (array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				if ( empty( $opts_md_pre ) ) {	// Nothing to rename.

					$type_opts = array_merge( $md_defs, $md_opts );

				} else {

					foreach ( $opts_md_pre as $opt_key => $md_pre ) {

						$md_defs = self::preg_grep_keys( '/^' . $md_pre . '_/', $md_defs, $invert = false, $opt_key . '_' );
						$md_opts = self::preg_grep_keys( '/^' . $md_pre . '_/', $md_opts, $invert = false, $opt_key . '_' );

						if ( is_array( $type_opts ) ) {

							$type_opts = array_merge( $md_defs, $type_opts, $md_opts );

						} else {

							$type_opts = array_merge( $md_defs, $md_opts );
						}
					}
				}
			}
		}

		public static function get_mod_anchor( array $mod ) {

			$mod_anchor = self::get_mod_salt( $mod );

			$mod_anchor = self::sanitize_css_id( $mod_anchor );

			return $mod_anchor;
		}

		/**
		 * Results a salt string based on $mod values.
		 *
		 * Example mod salts:
		 *
		 * 	'post:123'
		 * 	'term:456_tax:post_tag'
		 * 	'post:0_url:https://example.com/a-subject/'
		 */
		public static function get_mod_salt( array $mod, $canonical_url = false ) {

			$mod_salt = '';

			if ( ! empty( $mod[ 'name' ] ) ) {

				$mod_salt .= '_' . $mod[ 'name' ] . ':';

				if ( false === $mod[ 'id' ] ) {

					$mod_salt .= 'false';

				} elseif ( true === $mod[ 'id' ] ) {

					$mod_salt .= 'true';

				} elseif ( empty( $mod[ 'id' ] ) ) {

					$mod_salt .= '0';

				} else {

					$mod_salt .= $mod[ 'id' ];
				}
			}

			if ( ! empty( $mod[ 'tax_slug' ] ) ) {

				$mod_salt .= '_tax:' . $mod[ 'tax_slug' ];
			}

			if ( ! is_numeric( $mod[ 'id' ] ) || ! $mod[ 'id' ] > 0 ) {

				if ( ! empty( $mod[ 'is_home' ] ) ) {	// Home page (static or blog archive).

					$mod_salt .= '_home';
				}

				if ( ! empty( $canonical_url ) ) {

					$mod_salt .= '_url:' . $canonical_url;
				}
			}

			$mod_salt = ltrim( $mod_salt, '_' );

			return apply_filters( 'sucom_mod_salt', $mod_salt, $canonical_url );
		}

		public static function get_assoc_salt( array $assoc ) {

			$assoc_salt = '';

			foreach ( $assoc as $key => $val ) {

				$assoc_salt .= '_' . $key . ':' . (string) $val;
			}

			$assoc_salt = ltrim( $assoc_salt, '_' );	// Remove leading underscore.

			return $assoc_salt;
		}

		public static function get_implode_assoc( $val_glue, $key_glue, array $arr, $salt_str = '' ) {

			foreach ( $arr as $key => $val ) {

				$salt_str .= $val_glue;

				if ( is_array( $val ) ) {

					$salt_str .= self::get_implode_assoc( $val_glue, $key_glue, $val, $salt_str );

				} else {

					$salt_str .= (string) $key . $key_glue . $val;
				}
			}

			return ltrim( $salt_str, $val_glue );
		}

		/**
		 * Check that the transient has not expired, and does not exceed the max expiration time.
		 */
		public static function check_transient_timeout( $cache_id, $max_exp_secs ) {

			if ( $transient_timeout = get_option( '_transient_timeout_' . $cache_id ) ) {

				$current_time = time();	// Get the time only once.

				if ( $transient_timeout < $current_time || $transient_timeout > ( $current_time + $max_exp_secs ) ) {

					delete_transient( $cache_id );
				}
			}
		}

		public static function get_transient_array( $cache_id ) {

			$cache_array = get_transient( $cache_id );

			return $cache_array;
		}

		/**
		 * Update the cached array and maintain the existing transient expiration time.
		 */
		public static function update_transient_array( $cache_id, $cache_array, $cache_exp_secs ) {

			$current_time  = time();
			$reset_at_secs = 300;

			/**
			 * If the $cache_array already has a '__created_at' value, calculate how long until the transient object
			 * expires, and then set the transient with that new expiration seconds.
			 */
			if ( isset( $cache_array[ '__created_at' ] ) ) {

				/**
				 * Adjust the expiration time by removing the difference (current time less creation time) from the
				 * desired transient expiration seconds.
				 */
				$transient_exp_secs = $cache_exp_secs - ( $current_time - $cache_array[ '__created_at' ] );

				/**
				 * If we're 300 seconds (5 minutes) or less from the transient expiring, then renew the transient
				 * creation / expiration times.
				 */
				if ( $transient_exp_secs < $reset_at_secs ) {

					$transient_exp_secs = $cache_exp_secs;

					$cache_array[ '__created_at' ] = $current_time;
				}

			} else {

				$transient_exp_secs = $cache_exp_secs;

				$cache_array[ '__created_at' ] = $current_time;
			}

			set_transient( $cache_id, $cache_array, $transient_exp_secs );

			return $transient_exp_secs;
		}

		public static function delete_transient_array( $cache_id ) {

			$deleted = delete_transient( $cache_id );

			return $deleted;
		}

		public static function restore_checkboxes( &$opts ) {

			if ( is_array( $opts ) ) {	// Just in case.

				/**
				 * Unchecked checkboxes are not provided, so re-create them here based on hidden values.
				 */
				$checkbox = self::preg_grep_keys( '/^is_checkbox_/', $opts, $invert = false, $replace = '' );

				foreach ( $checkbox as $key => $val ) {

					if ( ! array_key_exists( $key, $opts ) ) {

						$opts[ $key ] = 0; // Add missing checkbox as empty.
					}

					unset ( $opts[ 'is_checkbox_' . $key] );
				}
			}

			return $opts;
		}

		public static function get_page_info( $use_post = false ) {

			$is_post_page = $is_term_page = $is_user_page = false;

			/**
			 * Optimize and stop on first match.
			 */
			if ( ! $is_post_page = self::is_post_page( $use_post ) ) {

				if ( ! $is_term_page = self::is_term_page() ) {

					$is_user_page = self::is_user_page();
				}
			}

			return array(
				'post_page' => $is_post_page,
				'term_page' => $is_term_page,
				'user_page' => $is_user_page
			);
		}

		/**
		 * Deprecated on 2020/12/09.
		 */
		public static function is_archive_page() {

			_deprecated_function( __METHOD__ . '()', '2020/12/09', $replacement = '' );	// Deprecation message.

			return apply_filters( 'sucom_is_archive_page', is_archive() );
		}

		/**
		 * $use_post can be true, false, a numeric post ID, or a post object.
		 */
		public static function is_home_page( $use_post = false ) {

			$is_home_page = false;

			/**
			 * Fallback to null so $use_post = 0 does not match.
			 */
			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : null;

			if ( $post_id > 0 ) {

				if ( is_numeric( $use_post ) && (int) $use_post === $post_id ) {

					$is_home_page = true;

				} elseif ( self::get_post_object( $use_post, 'id' ) === $post_id ) {

					$is_home_page = true;
				}
			}

			return apply_filters( 'sucom_is_home_page', $is_home_page, $use_post );
		}

		public static function is_home_posts( $use_post = false ) {

			$is_home_posts = false;

			/**
			 * Fallback to null so $use_post = 0 does not match.
			 */
			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_for_posts' ) : null;

			if ( is_numeric( $use_post ) && (int) $use_post === $post_id ) {

				$is_home_posts = true;

			} elseif ( $post_id > 0 && self::get_post_object( $use_post, 'id' ) === $post_id ) {

				$is_home_posts = true;

			} elseif ( false === $use_post && is_home() && is_front_page() ) {

				$is_home_posts = true;
			}

			return apply_filters( 'sucom_is_home_posts', $is_home_posts, $use_post );
		}

		public static function is_auto_draft( array $mod ) {

			if ( ! empty( $mod[ 'is_post' ] ) ) {

				if ( empty( $mod[ 'post_status' ] ) || 'auto-draft' === $mod[ 'post_status' ] ) {

					return true;
				}
			}

			return false;
		}

		public static function is_trashed( array $mod ) {

			if ( $mod[ 'is_post' ] && isset( $mod[ 'post_status' ] ) ) {

				if ( 'trash' === $mod[ 'post_status' ] ) {

					return true;
				}
			}

			return false;
		}

		public static function is_mod_current_screen( array $mod ) {

			if ( ! is_admin() ) {	// Front-end does not have a "current screen".

				return false;
			}

			if ( empty( $mod[ 'id' ] ) || ! is_numeric( $mod[ 'id' ] ) ) {

				return false;
			}

			$screen_base = self::get_screen_base();

			if ( empty( $mod[ 'name' ] ) || $mod[ 'name' ] !== $screen_base ) {

				return false;
			}

			switch ( $screen_base ) {

				case 'post':

					$current_id = self::get_request_value( 'post_ID', 'POST' );

					if ( '' === $current_id ) {

						$current_id = self::get_request_value( 'post', 'GET' );
					}

					break;

				case 'term':

					$current_id = self::get_request_value( 'tag_ID' );

					break;

				case 'user':

					$current_id = self::get_request_value( 'user_id' );

					break;

				default:

					return false;

					break;
			}

			if ( ! $current_id || ! is_numeric( $current_id ) ) {

				return false;
			}

			if ( (int) $current_id === $mod[ 'id' ] ) {

				return true;
			}

			return false;
		}

		public static function is_mod_post_type( array $mod, $post_type ) {

			if ( $mod[ 'is_post' ] && $mod[ 'id' ] && $mod[ 'post_type' ] === $post_type ) {

				return true;
			}

			return false;
		}

		public static function is_mod_tax_slug( array $mod, $tax_slug ) {

			if ( $mod[ 'is_term' ] && $mod[ 'id' ] && $mod[ 'tax_slug' ] === $tax_slug ) {

				return true;
			}

			return false;
		}

		public static function is_term_tax_slug( $term_id, $tax_slug ) {

			/**
			 * Optimize and get the term only once so this method can be called several times for different $tax_slugs.
			 */
			static $local_cache = array();

			if ( ! isset( $local_cache[ $term_id ] ) ) {

				$local_cache[ $term_id ] = get_term_by( 'id', $term_id, $tax_slug, OBJECT, 'raw' );
			}

			if ( ! empty( $local_cache[ $term_id ]->term_id ) &&
				! empty( $local_cache[ $term_id ]->taxonomy ) &&
					$local_cache[ $term_id ]->taxonomy === $tax_slug ) {

				return true;
			}

			return false;
		}

		public static function is_post_exists( $post_id ) {

			  return is_string( get_post_status( $post_id ) );
		}

		public static function is_post_page( $use_post = false ) {

			$ret = false;

			if ( is_numeric( $use_post ) && $use_post > 0 ) {

				$ret = self::is_post_exists( $use_post );

			} elseif ( true === $use_post && ! empty( $GLOBALS[ 'post' ]->ID ) ) {

				$ret = true;

			} elseif ( false === $use_post && is_singular() ) {

				$ret = true;

			} elseif ( false === $use_post && is_post_type_archive() ) {

				$ret = true;

			} elseif ( ! is_home() && is_front_page() && 'page' === get_option( 'show_on_front' ) ) { // Static front page.

				$ret = true;

			} elseif ( is_home() && ! is_front_page() && 'page' === get_option( 'show_on_front' ) ) { // Static posts page.

				$ret = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( $screen_base === 'post' ) {

					$ret = true;

				} elseif ( false === $screen_base && // Called too early for screen.
					( '' !== self::get_request_value( 'post_ID', 'POST' ) || // Uses sanitize_text_field().
						'' !== self::get_request_value( 'post', 'GET' ) ) ) {

					$ret = true;

				} elseif ( 'post-new.php' === basename( $_SERVER[ 'PHP_SELF' ] ) ) {

					$ret = true;
				}
			}

			return apply_filters( 'sucom_is_post_page', $ret, $use_post );
		}

		/**
		 * $post_type can be the post type string, or the post type object.
		 */
		public static function is_post_type_archive( $post_type, $post_slug ) {

			$is_post_type_archive = false;

			if ( ! empty( $post_type ) && ! empty( $post_slug ) ) {	// Just in case.

				if ( is_object( $post_type ) ) {

					$post_type_obj =& $post_type;

				} else {

					$post_type_obj = get_post_type_object( $post_type );
				}

				if ( ! empty( $post_type_obj->has_archive ) ) {	// just in case.

					$archive_slug = $post_type_obj->has_archive;

					if ( true === $archive_slug ) {

						$archive_slug = $post_type_obj->rewrite[ 'slug' ];
					}

					if ( $post_slug === $archive_slug ) {

						$is_post_type_archive = true;
					}
				}
			}

			return $is_post_type_archive;
		}

		public static function get_post_object( $use_post = false, $output = 'object' ) {

			$post_obj = false; // Return false by default.

			if ( is_numeric( $use_post ) && $use_post > 0 ) {

				$post_obj = get_post( $use_post );

			} elseif ( true === $use_post && ! empty( $GLOBALS[ 'post' ]->ID ) ) {

				$post_obj = $GLOBALS[ 'post' ];

			} elseif ( false === $use_post && apply_filters( 'sucom_is_post_page', ( is_singular() ? true : false ), $use_post ) ) {

				$post_obj = get_queried_object();

			} elseif ( ! is_home() && is_front_page() && 'page' === get_option( 'show_on_front' ) ) { // Static front page.

				$post_obj = get_post( get_option( 'page_on_front' ) );

			} elseif ( is_home() && ! is_front_page() && 'page' === get_option( 'show_on_front' ) ) { // Static posts page.

				$post_obj = get_post( get_option( 'page_for_posts' ) );

			} elseif ( is_admin() ) {

				if ( '' !== ( $post_id = self::get_request_value( 'post_ID', 'POST' ) ) ||
					'' !== ( $post_id = self::get_request_value( 'post', 'GET' ) ) ) {

					$post_obj = get_post( $post_id );
				}
			}

			$post_obj = apply_filters( 'sucom_get_post_object', $post_obj, $use_post );

			switch ( $output ) {

				case 'id':
				case 'ID':
				case 'post_id':

					return isset( $post_obj->ID ) ? (int) $post_obj->ID : 0; // Cast as integer.

					break;

				default:

					return is_object( $post_obj ) ? $post_obj : false;

					break;
			}
		}

		public static function maybe_load_post( $post_id, $force = false ) {

			if ( empty( $post_id ) ) {	// Just in case.

				return false;
			}

			global $post;

			if ( $force || ! isset( $post->ID ) || $post->ID !== $post_id ) {

				$post = self::get_post_object( $post_id, 'object' );

				return true;
			}

			return false;
		}

		public static function is_term_page( $term_id = 0, $tax_slug = '' ) {

			$ret = false;

			if ( is_numeric( $term_id ) && $term_id > 0 ) {

				/**
				 * Note that term_exists() requires an integer ID, not a string ID.
				 */
				$ret = term_exists( (int) $term_id, $tax_slug );	// Since WP v3.0.

			} elseif ( is_tax() || is_category() || is_tag() ) {

				$ret = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( 'term' === $screen_base ) {	 	// Since WP v4.5.

					$ret = true;

				} elseif ( ( false === $screen_base || $screen_base === 'edit-tags' ) &&	
					( '' !== self::get_request_value( 'taxonomy' ) &&
						'' !== self::get_request_value( 'tag_ID' ) ) ) {

					$ret = true;
				}
			}

			return apply_filters( 'sucom_is_term_page', $ret );
		}

		public static function is_category_page( $term_id = 0 ) {

			$ret = false;

			if ( is_numeric( $term_id ) && $term_id > 0 ) {

				/**
				 * Note that term_exists() requires an integer ID, not a string ID.
				 */
				$ret = term_exists( (int) $term_id, 'category' );	// Since WP v3.0.

			} elseif ( is_category() ) {

				$ret = true;

			} elseif ( is_admin() ) {

				if ( self::is_term_page() && 'category' === self::get_request_value( 'taxonomy' ) ) {

					$ret = true;
				}
			}

			return apply_filters( 'sucom_is_category_page', $ret );
		}

		public static function is_tag_page( $term_id = 0 ) {

			$ret = false;

			if ( is_numeric( $term_id ) && $term_id > 0 ) {

				/**
				 * Note that term_exists() requires an integer ID, not a string ID.
				 */
				$ret = term_exists( (int) $term_id, 'post_tag' );	// Since WP v3.0.

			} elseif ( is_tag() ) {

				$ret = true;

			} elseif ( is_admin() ) {

				if ( self::is_term_page() && 'post_tag' === self::get_request_value( 'taxonomy' ) ) {

					$ret = true;
				}
			}

			return apply_filters( 'sucom_is_tag_page', $ret );
		}

		public static function get_term_object( $term_id = 0, $tax_slug = '', $output = 'object' ) {

			$term_obj = false; // Return false by default.

			if ( is_numeric( $term_id ) && $term_id > 0 ) {

				$term_obj = get_term( (int) $term_id, (string) $tax_slug, OBJECT, 'raw' );

			} elseif ( apply_filters( 'sucom_is_term_page', is_tax() ) || is_tag() || is_category() ) {

				$term_obj = get_queried_object();

			} elseif ( is_admin() ) {

				if ( '' !== ( $tax_slug = self::get_request_value( 'taxonomy' ) ) &&
					'' !== ( $term_id = self::get_request_value( 'tag_ID' ) ) ) {

					$term_obj = get_term( (int) $term_id, (string) $tax_slug, OBJECT, 'raw' );
				}
			}

			$term_obj = apply_filters( 'sucom_get_term_object', $term_obj, $term_id, $tax_slug );

			switch ( $output ) {

				case 'id':
				case 'ID':
				case 'term_id':

					return isset( $term_obj->term_id ) ? (int) $term_obj->term_id : 0; // Cast as integer.

					break;

				case 'taxonomy':

					return isset( $term_obj->taxonomy ) ? (string) $term_obj->taxonomy : ''; // Cast as string.

					break;

				default:

					return is_object( $term_obj ) ? $term_obj : false;

					break;
			}
		}

		public static function is_author_page( $user_id = 0 ) {

			return self::is_user_page( $user_id );
		}

		public static function is_user_page( $user_id = 0 ) {

			$ret = false;

			if ( is_numeric( $user_id ) && $user_id > 0 ) {

				$ret = SucomUtilWP::user_exists( $user_id );

			} elseif ( is_author() ) {

				$ret = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( false !== $screen_base ) {

					switch ( $screen_base ) {

						case 'profile':		// User profile page.
						case 'user-edit':	// User editing page.
						case ( 0 === strpos( $screen_base, 'profile_page_' ) ? true : false ):	// Your profile page.
						case ( 0 === strpos( $screen_base, 'users_page_' ) ? true : false ):	// Users settings page.

							$ret = true;

							break;
					}

				} elseif ( '' !== self::get_request_value( 'user_id' ) ||  // Called too early for screen.
					'profile.php' === basename( $_SERVER[ 'PHP_SELF' ] ) ) {

					$ret = true;
				}
			}

			return apply_filters( 'sucom_is_user_page', $ret );
		}

		public static function get_author_object( $user_id = 0, $output = 'object' ) {

			return self::get_user_object( $user_id, $ret );
		}

		public static function get_user_object( $user_id = 0, $output = 'object' ) {

			$user_obj = false; // Return false by default.

			if ( is_numeric( $user_id ) && $user_id > 0 ) {

				$user_obj = get_userdata( $user_id );

			} elseif ( apply_filters( 'sucom_is_user_page', is_author() ) ) {

				$user_obj = get_query_var( 'author_name' ) ?
					get_user_by( 'slug', get_query_var( 'author_name' ) ) :
						get_userdata( get_query_var( 'author' ) );

			} elseif ( is_admin() ) {

				if ( '' === ( $user_id = self::get_request_value( 'user_id' ) ) ) { // Uses sanitize_text_field().

					$user_id = get_current_user_id();
				}

				$user_obj = get_userdata( $user_id );
			}

			$user_obj = apply_filters( 'sucom_get_user_object', $user_obj, $user_id );

			switch ( $output ) {

				case 'id':
				case 'ID':
				case 'user_id':

					return isset( $user_obj->ID ) ? (int) $user_obj->ID : 0; // Cast as integer.

					break;

				default:

					return is_object( $user_obj ) ? $user_obj : false;

					break;
			}
		}

		public static function get_request_value( $key, $method = 'ANY', $default = '' ) {

			if ( $method === 'ANY' ) {

				$method = $_SERVER[ 'REQUEST_METHOD' ];
			}

			switch( $method ) {

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

		public static function encode_utf8( $decoded ) {

			$encoded = $decoded;

			if ( function_exists( 'mb_detect_encoding' ) ) { 	// Just in case.

				if ( mb_detect_encoding( $decoded, 'UTF-8') !== 'UTF-8' ) {

					if ( function_exists( 'utf8_encode' ) ) {

						$encoded = utf8_encode( $decoded );
					}
				}
			}

			return $encoded;
		}

		/**
		 * Decode HTML entities and UTF8 encoding.
		 */
		public static function decode_html( $encoded ) {

			/**
			 * If we don't have something to decode, then return immediately.
			 */
			if ( false === strpos( $encoded, '&' ) ) {

				return $encoded;
			}

			static $charset = null;

			if ( ! isset( $charset  ) ) {

				$charset = get_bloginfo( $show = 'charset', $filter = 'raw' );	// Only get it once.
			}

			return html_entity_decode( self::decode_utf8( $encoded ), ENT_QUOTES, $charset );
		}

		public static function decode_utf8( $encoded ) {

			/**
			 * if we don't have something to decode, then return immediately.
			 */
			if ( false === strpos( $encoded, '&#' ) ) {

				return $encoded;
			}

			/**
			 * Convert certain entities manually to something non-standard.
			 */
			$encoded = preg_replace( '/&#8230;/', '...', $encoded );

			/**
			 * If mb_decode_numericentity() is not available, then return the string un-converted.
			 */
			if ( ! function_exists( 'mb_decode_numericentity' ) ) {	// Just in case.

				return $encoded;
			}

			return preg_replace_callback( '/&#\d{2,5};/u', array( __CLASS__, 'decode_utf8_entity' ), $encoded );
		}

		/**
		 * The existence of mb_decode_numericentity() is checked before doing the callback.
		 */
		public static function decode_utf8_entity( $matches ) {

			$convmap = array( 0x0, 0x10000, 0, 0xfffff );

			return mb_decode_numericentity( $matches[ 0 ], $convmap, 'UTF-8' );
		}

		/**
		 * Decode a URL and add query arguments. Returns false on error.
		 */
		public static function decode_url_add_query( $url, array $args ) {

			if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {	// Check for invalid URL.

				return false;
			}

			$parsed_url = parse_url( self::decode_html( urldecode( $url ) ) );

			if ( empty( $parsed_url ) ) {

				return false;
			}

			if ( empty( $parsed_url[ 'query' ] ) ) {

				$parsed_url[ 'query' ] = http_build_query( $args );

			} else {

				$parsed_url[ 'query' ] .= '&' . http_build_query( $args );
			}

			$url = self::unparse_url( $parsed_url );

			return $url;
		}

		public static function add_query_fragment( $url, $new_fragment ) {

			if ( $old_fragment = strstr( $url, '#' ) ) {

				$url = substr( $url, 0, -strlen( $old_fragment ) );
			}

			return $url . '#' . trim( $new_fragment, '#' );
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

		public static function strip_html( $text ) {

			$text = self::strip_shortcodes( $text );						// Remove any remaining shortcodes.
			$text = preg_replace( '/[\s\n\r]+/s', ' ', $text );					// Put everything on one line.
			$text = preg_replace( '/<\?.*\?' . '>/U', ' ', $text );					// Remove php.
			$text = preg_replace( '/<script\b[^>]*>(.*)<\/script>/Ui', ' ', $text );		// Remove javascript.
			$text = preg_replace( '/<style\b[^>]*>(.*)<\/style>/Ui', ' ', $text );			// Remove inline stylesheets.
			$text = preg_replace( '/([\w])<\/(button|dt|h[0-9]+|li|th)>/i', '$1. ', $text );	// Add missing dot to buttons, headers, lists, etc.
			$text = preg_replace( '/(<p>|<p[^>]+>|<\/p>)/i', ' ', $text );				// Replace paragraph tags with a space.
			$text = trim( strip_tags( $text ) );							// Remove remaining html tags.
			$text = preg_replace( '/(\xC2\xA0|\s)+/s', ' ', $text );				// Replace 1+ spaces to a single space.

			return trim( $text );
		}

		/**
		 * Strip / remove all registered shortcodes, along with some unregistered shortcodes. Hook the
		 * 'sucom_strip_shortcodes_preg' filter to modify the unregistered shortcode regular expression.
		 */
		public static function strip_shortcodes( $text ) {

			if ( false === strpos( $text, '[' ) ) { // Optimize and check if there are shortcodes.

				return $text;
			}

			$text = strip_shortcodes( $text );      // Remove registered shortcodes.

			if ( false === strpos( $text, '[' ) ) { // Stop here if no shortcodes.

				return $text;
			}

			$shortcodes_preg = apply_filters( 'sucom_strip_shortcodes_preg', array(
				'/\[\/?(cs_element_|et_|fusion_|mk_|rev_slider_|vc_)[^\]]+\]/',
			) );

			$text = preg_replace( $shortcodes_preg, ' ', $text );

			return $text;
		}

		public static function get_stripped_php( $file_path ) {

			$stripped_php = '';

			if ( file_exists( $file_path ) ) {

				$content = file_get_contents( $file_path );

				$comments = array( T_COMMENT );

				if ( defined( 'T_DOC_COMMENT' ) ) {

					$comments[] = T_DOC_COMMENT;    // PHP 5.
				}

				if ( defined( 'T_ML_COMMENT' ) ) {

					$comments[] = T_ML_COMMENT;     // PHP 4.
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

			} else {

				$stripped_php = false;
			}

			return $stripped_php;
		}

		public static function esc_url_encode( $url, $esc_url = true ) {

			$decoded_url = self::decode_html( $url ); // Just in case - decode HTML entities.

			$clean_url = $esc_url ? esc_url_raw( $decoded_url ) : $decoded_url;

			$encoded_url = urlencode( $clean_url );

			$replace = array( '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D' );

			$allowed = array( '!', '*', '\'', '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']' );

			return str_replace( $replace, $allowed, $encoded_url );
		}

		public static function encode_html_emoji( $content ) {

			static $charset = null;

			if ( ! isset( $charset ) ) {

				$charset = get_bloginfo( $show = 'charset', $filter = 'raw' );	// Only get it once.
			}

			$content = htmlentities( $content, ENT_QUOTES, $charset, $double_encode = false );

			$content = SucomUtilWP::wp_encode_emoji( $content );

			return $content;
		}

		/**
		 * Used to decode Facebook video URLs.
		 */
		public static function replace_unicode_escape( $str ) {

			/**
			 * If mb_convert_encoding() is not available, then return the string un-converted.
			 */
			if ( ! function_exists( 'mb_convert_encoding' ) ) {

				return $str;
			}

			return preg_replace_callback( '/\\\\u([0-9a-f]{4})/i', array( __CLASS__, 'replace_unicode_escape_callback' ), $str );
		}

		/**
		 * The existence of mb_convert_encoding() is checked before doing the callback.
		 */
		private static function replace_unicode_escape_callback( $match ) {

			return mb_convert_encoding( pack( 'H*', $match[ 1 ] ), $to_encoding = 'UTF-8', $from_encoding = 'UCS-2' );
		}

		public static function json_encode_array( array $data, $options = 0, $depth = 32 ) {

			if ( function_exists( 'wp_json_encode' ) ) {

				return wp_json_encode( $data, $options, $depth );

			} elseif ( function_exists( 'json_encode' ) ) {

				$php_version = phpversion();

				if ( version_compare( $php_version, '5.5.0', '>=' ) ) {

					return json_encode( $data, $options, $depth );  // $depth since PHP v5.5.0.

				} elseif ( version_compare( $php_version, '5.3.0', '>=' ) ) {

					return json_encode( $data, $options );          // $options since PHP v5.3.0.

				}

				return json_encode( $data );

			}

			return '{}'; // Empty string.
		}

		public static function get_json_scripts( $html, $do_decode = true ) {

			if ( function_exists( 'mb_convert_encoding' ) ) {	// Just in case.

				$html = mb_convert_encoding( $html, $to_encoding = 'HTML-ENTITIES', $from_encoding = 'UTF-8' );
			}

			/**
			 * Remove containers that should not include json scripts.
			 *
			 * U = Invert greediness of quantifiers, so they are NOT greedy by default, but become greedy if followed by ?.
			 * m = The "^" and "$" constructs match newlines and the complete subject string.
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 */
			$html = preg_replace( '/<!--.*-->/Ums', '', $html );
			$html = preg_replace( '/<pre[ >].*<\/pre>/Uims', '', $html );
			$html = preg_replace( '/<textarea[ >].*<\/textarea>/Uims', '', $html );

			$json_data = array();

			/**
			 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
			 * i = Letters in the pattern match both upper and lower case letters. 
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 *
			 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
			 */
			if ( preg_match_all( '/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*)<\/script>/Uis', $html, $all_matches, PREG_SET_ORDER ) ) {

				foreach ( $all_matches as $num => $matches ) {

					$json_decoded = json_decode( $matches[ 1 ], $assoc = true );

					$json_md5 = md5( serialize( $json_decoded ) );	// md5() input must be a string.

					if ( $do_decode ) {	// Return only the decoded json data.

						if ( is_array( $json_decoded ) ) {

							$json_data[ $json_md5 ] = $json_decoded;

						} else {

							$error_pre = sprintf( '%s error:', __METHOD__ );

							$error_msg = sprintf( 'Error decoding json script: %s', print_r( $matches[ 1 ], true ) );

							self::safe_error_log( $error_pre . ' ' . $error_msg );
						}

					} else {	// Return the complete script container.

						$json_data[ $json_md5 ] = $matches[ 0 ];
					}
				}
			}

			return $json_data;
		}

		public static function get_user_ids( $blog_id = null, $role = '', $limit = '' ) {

			static $offset = '';

			if ( empty( $blog_id ) ) {

				$blog_id = get_current_blog_id();
			}

			if ( is_numeric( $limit ) ) {

				$offset = '' === $offset ? 0 : $offset + $limit;
			}

			$user_args  = array(
				'blog_id' => $blog_id,
				'offset'  => $offset,
				'number'  => $limit,
				'order'   => 'DESC',	// Newest users first.
				'orderby' => 'ID',
				'role'    => $role,
				'fields'  => array(	// Save memory and only return only specific fields.
					'ID',
				)
			);

			$user_ids = array();

			foreach ( get_users( $user_args ) as $user_obj ) {

				$user_ids[] = $user_obj->ID;
			}

			if ( '' !== $offset ) {

				if ( empty( $user_ids ) ) {

					$offset = '';	// Allow the next call to start fresh.

					return false;	// To break the while loop.
				}
			}

			return $user_ids;
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

		/**
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

				} else {

					$times[ $value ] = $value;
				}
			}

			return $times;
		}

		public static function get_column_rows( array $table_cells, $row_cols = 2 ) {

			sort( $table_cells );

			$table_rows = array();

			$per_col = ceil( count( $table_cells ) / $row_cols );

			foreach ( $table_cells as $num => $cell ) {

				if ( empty( $table_rows[ $num % $per_col ] ) ) { // Initialize the array element.

					$table_rows[ $num % $per_col ] = '';
				}

				$table_rows[ $num % $per_col ] .= $cell; // Create the html for each row.
			}

			return $table_rows;
		}

		/**
		 * Deprecated on 2020/04/14.
		 */
		public static function get_atts_css_attr( array $atts, $css_name, $css_extra = '' ) {

			_deprecated_function( __METHOD__ . '()', '2020/04/14', $replacement = '' );	// Deprecation message.

			return '';
		}

		/**
		 * Deprecated on 2020/04/14.
		 */
		public static function get_atts_src_id( array $atts, $src_name ) {

			_deprecated_function( __METHOD__ . '()', '2020/04/14', $replacement = '' );	// Deprecation message.

			return '';
		}

		public static function is_toplevel_edit( $hook_name ) {

			if ( false !== strpos( $hook_name, 'toplevel_page_' ) ) {

				if ( 'edit' === self::get_request_value( 'action', 'GET' ) && (int) self::get_request_value( 'post', 'GET' ) > 0 )  {

					return true;
				}

				if ( 'create_new' === self::get_request_value( 'action', 'GET' ) && 'edit' === self::get_request_value( 'return', 'GET' ) ) {

					return true;
				}
			}

			return false;
		}

		public static function is_true( $mixed, $allow_null = false ) {

			$ret_bool = is_string( $mixed ) ? filter_var( $mixed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : (bool) $mixed;

		        return null === $ret_bool && ! $allow_null ? false : $ret_bool;
		}

		/**
		 * Converts string to boolean.
		 */
		public static function get_bool( $mixed ) {

			return is_string( $mixed ) ? filter_var( $mixed, FILTER_VALIDATE_BOOLEAN ) : (bool) $mixed;
		}

		/**
		 * Wrapper for SuextMinifyCssCompressor class to minify CSS.
		 */
		public static function minify_css( $css_data, $filter_prefix = 'sucom' ) {

			if ( ! empty( $css_data ) ) {	// Make sure we have something to minify.

				$classname = apply_filters( $filter_prefix . '_load_lib', false, 'ext/compressor', 'SuextMinifyCssCompressor' );

				if ( 'SuextMinifyCssCompressor' === $classname && class_exists( $classname ) ) {

					$css_data = $classname::process( $css_data );
				}
			}

			return $css_data;
		}

		public static function get_at_name( $val ) {

			if ( $val !== '' ) {

				$val = substr( preg_replace( array( '/^.*\//', '/[^a-zA-Z0-9_]/' ), '', $val ), 0, 15 );

				if ( ! empty( $val ) )  {

					$val = '@' . $val;
				}
			}

			return $val;
		}

		public static function get_dist_name( $name, $pkg ) {

			if ( false !== strpos( $name, $pkg ) ) {

				$name = preg_replace( '/^(.*) ' . $pkg . '( [\[\(].+[\)\]])?$/U', '$1$2', $name );
			}

			return preg_replace( '/^(.*)( [\[\(].+[\)\]])?$/U', '$1 ' . $pkg . '$2', $name );
		}

		/**
		 * Site Title.
		 *
		 * Returns a custom site name or the default WordPress site name.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_name( array $opts = array(), $mixed = 'current' ) {

			$site_name = empty( $opts ) ? '' : self::get_key_value( 'site_name', $opts, $mixed );

			if ( empty( $site_name ) ) {

				$site_name = get_bloginfo( $show = 'name', $filter = 'raw' );	// Fallback to default WordPress value.
			}

			return $site_name;
		}

		public static function get_site_name_alt( array $opts, $mixed = 'current' ) {

			return empty( $opts ) ? '' : self::get_key_value( 'site_name_alt', $opts, $mixed );
		}

		/**
		 * Tagline.
		 * 
		 * Returns a custom site description or the default WordPress site description / tagline.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_description( array $opts = array(), $mixed = 'current' ) {

			$site_desc = empty( $opts ) ? '' : self::get_key_value( 'site_desc', $opts, $mixed );

			if ( empty( $site_desc ) ) {

				$site_desc = get_bloginfo( $show = 'description', $filter = 'raw' );	// Fallback to default WordPress value.
			}

			return $site_desc;
		}

		/**
		 * Deprecated on 2020/12/16.
		 */
		public static function get_site_url( array $opts = array(), $mixed = 'current' ) {

			_deprecated_function( __METHOD__ . '()', '2021/12/16', $replacement = __CLASS__ . '::get_home_url()' );	// Deprecation message.

			return self::get_home_url( $opts, $mixed );
		}

		/**
		 * Returns the website URL (ie. the home page, WP_HOME value) from the options array, or the WordPress
		 * get_home_url() value.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_home_url( array $opts = array(), $mixed = 'current' ) {

			$home_url = empty( $opts ) ? '' : self::get_key_value( 'home_url', $opts, $mixed );

			if ( empty( $home_url ) ) {	// Fallback to default WordPress value.

				$home_url = get_home_url();	// Fallback to default WordPress value.
			}

			return $home_url;
		}

		/**
		 * Returns the WordPress installation URL (ie. the blog page, WP_SITEURL value) from the options array, or the
		 * WordPress get_site_url() value.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_wp_url( array $opts = array(), $mixed = 'current' ) {

			$wp_url = empty( $opts ) ? '' : self::get_key_value( 'wp_url', $opts, $mixed );

			if ( empty( $wp_url ) ) {

				$wp_url = get_site_url();	// Fallback to default WordPress value.
			}

			return $wp_url;
		}

		/**
		 * Wrap a filter to return its original / unchanged value.
		 *
		 * Returns true if protection filters were added, false if protection filters are not required.
		 */
		public static function protect_filter_value( $filter_name, $auto_unprotect = true ) {

			unset( self::$cache_protect[ $filter_name ] );	// Just in case.

			/**
			 * Don't bother if there's nothing to protect.
			 */
			if ( false === has_filter( $filter_name ) ) {

				return false;
			}

			self::$cache_protect[ $filter_name ][ 'auto_unprotect' ] = $auto_unprotect;

			/**
			 * Only hook the save/restore protection filters once.
			 */
			if ( false === has_filter( $filter_name, array( __CLASS__, '__save_current_filter_value' ) ) ) {	// Can return a priority of 0.

				$min_int = self::get_min_int();
				$max_int = self::get_max_int();

				add_filter( $filter_name, array( __CLASS__, '__save_current_filter_value' ), $min_int, 1 );
				add_filter( $filter_name, array( __CLASS__, '__restore_current_filter_value' ), $max_int, 1 );
			}

			return true;
		}

		public static function unprotect_filter_value( $filter_name ) {

			/**
			 * Don't bother if there are no protection filters.
			 */
			if ( false === has_filter( $filter_name, array( __CLASS__, '__save_current_filter_value' ) ) ) {	// Can return a priority of 0.

				return false;
			}

			$min_int = self::get_min_int();
			$max_int = self::get_max_int();

			remove_filter( $filter_name, array( __CLASS__, '__save_current_filter_value' ), $min_int );
			remove_filter( $filter_name, array( __CLASS__, '__restore_current_filter_value' ), $max_int );

			return true;
		}

		public static function get_original_filter_value( $filter_name ) {

			if ( isset( self::$cache_protect[ $filter_name ][ 'original_value' ] ) ) {

				return self::$cache_protect[ $filter_name ][ 'original_value' ];
			}

			return null;
		}

		public static function get_modified_filter_value( $filter_name ) {

			if ( isset( self::$cache_protect[ $filter_name ][ 'modified_value' ] ) ) {

				return self::$cache_protect[ $filter_name ][ 'modified_value' ];
			}

			return null;
		}

		public static function __save_current_filter_value( $value ) {

			$filter_name = current_filter();

			self::$cache_protect[ $filter_name ][ 'original_value' ] = $value;	// Save value to static cache.
			self::$cache_protect[ $filter_name ][ 'modified_value' ] = $value;	// Save value to static cache.

			return $value;
		}

		public static function __restore_current_filter_value( $value ) {

			$filter_name = current_filter();

			if ( isset( self::$cache_protect[ $filter_name ][ 'original_value' ] ) ) {		// Just in case.

				self::$cache_protect[ $filter_name ][ 'modified_value' ] = $value;		// Save for get_modified_filter_value().

				if ( $value !== self::$cache_protect[ $filter_name ][ 'original_value' ] ) {

					$value = self::$cache_protect[ $filter_name ][ 'original_value' ];	// Restore value from static cache.
				}
			}

			if ( ! empty( self::$cache_protect[ $filter_name ][ 'auto_unprotect' ] ) ) {

				$min_int = self::get_min_int();
				$max_int = self::get_max_int();

				remove_filter( $filter_name, array( __CLASS__, __FUNCTION__ ), $min_int );
				remove_filter( $filter_name, array( __CLASS__, __FUNCTION__ ), $max_int );
			}

			return $value;
		}

		/**
		 * Sets 'display_errors' to false to prevent PHP errors from being displayed and restores previous PHP settings
		 * after logging the error.
		 */
		public static function safe_error_log( $error_msg, $strip_html = false ) {

			$ini_set = array(
				'display_errors' => 0,
				'log_errors'     => 1,
				'error_log'      => defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && WP_DEBUG_LOG ?
					WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log',
			);

			$ini_saved = array();

			/**
			 * Save old option values and define new option values.
			 */
			foreach ( $ini_set as $name => $value ) {

				$ini_saved[ $name ] = ini_get( $name );	// Returns false if option does not exist.

				if ( false !== $ini_saved[ $name ] ) {

					if ( $ini_saved[ $name ] !== $value ) {

						ini_set( $name, $value );

					} else {

						unset( $ini_saved[ $name ] );
					}

				} else {

					unset( $ini_saved[ $name ] );
				}
			}

			if ( $strip_html ) {

				$error_msg = self::strip_html( $error_msg );
			}

			/**
			 * Use error_log() instead of trigger_error() to avoid HTTP 500.
			 */
			error_log( $error_msg );

			/**
			 * Only restore option values that were changed.
			 */
			foreach ( $ini_saved as $name => $value ) {

				ini_set( $name, $value );
			}
		}

		public static function flatten_mixed( $mixed ) {

			return self::pretty_array( $mixed, $flatten = true );
		}

		public static function pretty_array( $mixed, $flatten = false ) {

			$ret = '';

			if ( is_array( $mixed ) ) {

				foreach ( $mixed as $key => $val ) {

					$val = self::pretty_array( $val, $flatten );

					if ( $flatten ) {

						$ret .= $key.'=' . $val.', ';

					} else {

						if ( is_object( $mixed[ $key ] ) ) {

							unset ( $mixed[ $key ] );	// Dereference the object first.
						}

						$mixed[ $key ] = $val;
					}
				}

				if ( $flatten ) {

					$ret = '(' . trim( $ret, ', ' ) . ')';

				} else {

					$ret = $mixed;
				}

			} elseif ( false === $mixed ) {

				$ret = 'false';

			} elseif ( true === $mixed ) {

				$ret = 'true';

			} elseif ( null === $mixed ) {

				$ret = 'null';

			} elseif ( '' === $mixed ) {

				$ret = '\'\'';

			} elseif ( is_object( $mixed ) ) {

				$ret = 'object ' . get_class( $mixed );

			} else {

				$ret = $mixed;
			}

			return $ret;
		}

		/**
		 * Add one or more attributes to the HTML tag.
		 *
		 * Example HTML tag:
		 *
		 *	$html argument = '<img src="/image.jpg">
		 *
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

		/**
		 * Deprecated on 2020/03/23.
		 */
		public static function get_lib_stub_action( $lib_id ) {

			_deprecated_function( __METHOD__ . '()', '2020/03/23', $replacement = '' );	// Deprecation message.

			return array( $lib_id, false, false );
		}

		/**
		 * Calculate the estimated reading time in minutes.
		 *
		 * 250 is the default reading words per minute.
		 *
		 * See https://en.wikipedia.org/wiki/Speed_reading.
		 */
		public static function get_text_reading_mins( $text, $words_per_min = 200 ) {

			$word_count = str_word_count( wp_strip_all_tags( $text ) );

			return round( $word_count / $words_per_min );
		}
	}
}

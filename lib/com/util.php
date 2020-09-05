<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
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

		public function __construct() {
		}

		public static function get_min_int() {

			return defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : -2147483648;	// Since PHP v7.0.0.
		}

		public static function get_max_int() {

			return defined( 'PHP_INT_MAX' ) ? PHP_INT_MAX : 2147483647;	// Since PHP 5.0.2.
		}

		private static function get_formatted_timezone( $tz_name, $format ) {

			$dt = new DateTime();

			$dt->setTimeZone( new DateTimeZone( $tz_name ) );

			return $dt->format( $format );
		}

		/**
		 * Use "tz" in the method name to hint that input is an abbreviation.
		 */
		public static function get_tz_name( $tz_abbr ) {

			return timezone_name_from_abbr( $tz_abbr );
		}

		public static function get_timezone_abbr( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'T' );
		}

		/**
		 * Timezone offset in seconds - offset west of UTC is negative, and east of UTC is positive.
		 */
		public static function get_timezone_offset( $tz_name ) {

			return self::get_formatted_timezone( $tz_name, 'Z' );
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

		/**
		 * Deprecated on 2019/08/18.
		 */
		public static function is_force_regen() {

			return false;
		}

		/**
		 * Checks for 'none' value in midday_close and midday_open.
		 */
		public static function is_valid_midday( $open, $midday_close, $midday_open, $close ) {

			/**
			 * Performa a quick sanitation before using strtotime().
			 */
			if ( empty( $midday_close ) || empty( $midday_open ) || $midday_close === 'none' || $midday_open === 'none' ) {

				return false;
			}

			if ( strtotime( $midday_close ) < strtotime( $midday_open ) &&
				strtotime( $open ) < strtotime( $midday_close ) &&
					strtotime( $midday_open ) < strtotime( $close ) ) {

				return true;
			}

			return false;
		}

		public static function is_amp() {

			static $local_cache = null;

			if ( null === $local_cache ) {

				/**
				 * The amp_is_request() and is_amp_endpoint() functions cannot be called before the 'parse_query'
				 * action has run, so if the 'parse_query' action has not run, leave the $local_cache as null to
				 * allow for future checks.
				 */
				if ( function_exists( 'amp_is_request' ) ) {	// AMP.

					if ( did_action( 'parse_query' ) ) {

						$local_cache = amp_is_request();
					}

				} elseif ( function_exists( 'is_amp_endpoint' ) ) {	// AMP and Better AMP.

					if ( did_action( 'parse_query' ) ) {

						$local_cache = is_amp_endpoint();
					}

				} elseif ( function_exists( 'ampforwp_is_amp_endpoint' ) ) {	// AMP for WP.

					$local_cache = ampforwp_is_amp_endpoint();

				} elseif ( defined( 'AMP_QUERY_VAR' ) ) {

					$local_cache = get_query_var( AMP_QUERY_VAR, false ) ? true : false;

				} else {

					$local_cache = false;
				}
			}

			return $local_cache;
		}

		public static function is_mobile() {

			static $local_cache = null;
			static $mobile_obj  = null;

			if ( ! isset( $local_cache ) ) {

				if ( ! isset( $mobile_obj ) ) {	// Load class object on first check

					if ( ! class_exists( 'SuextMobileDetect' ) ) {

						require_once dirname( __FILE__ ) . '/../ext/mobile-detect.php';
					}

					$mobile_obj = new SuextMobileDetect();
				}

				$local_cache = $mobile_obj->isMobile();
			}

			return $local_cache;
		}

		public static function is_desktop() {

			return self::is_mobile() ? false : true;
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

			if ( strpos( $url, '/' ) === 0 ) { // Skip relative urls.

				return $url;
			}

			$prot_slash = self::get_prot() . '://';

			if ( strpos( $url, $prot_slash ) === 0 ) { // Skip correct urls.

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

			if ( $use_post === false ) {

				return 'false';

			} elseif ( $use_post === true ) {

				return 'true';
			}

			return (string) $use_post;
		}

		/**
		 * Note that an empty string or a null is sanitized as false.
		 */
		public static function sanitize_use_post( $mixed, $default = false ) {

			if ( is_array( $mixed ) ) {

				$use_post = isset( $mixed[ 'use_post' ] ) ? $mixed[ 'use_post' ] : $default;

			} elseif ( is_object( $mixed ) ) {

				$use_post = isset( $mixed->use_post ) ? $mixed->use_post : $default;

			} else {

				$use_post = $mixed;
			}

			if ( empty( $use_post ) || $use_post === 'false' ) { // 0, false, or 'false'

				return false;

			} elseif ( is_numeric( $use_post ) ) {

				return (int) $use_post;
			}

			return true;
		}

		public static function sanitize_file_path( $file_path ) {

			if ( empty( $file_path ) ) {

				return false;
			}

			$file_path = implode( '/', array_map( array( __CLASS__, 'sanitize_file_name' ), explode( '/', $file_path ) ) );

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

			$name = preg_replace( '/[:\/\-\. ]+/', '_', $name );

			return self::sanitize_key( $name );
		}

		public static function sanitize_classname( $name, $allow_underscore = true ) {

			$name = preg_replace( '/[:\/\-\. ' . ( $allow_underscore ? '' : '_' ) . ']+/', '', $name );

			return self::sanitize_key( $name );
		}

		public static function sanitize_key( $key ) {

			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
		}

		public static function sanitize_anchor( $anchor ) {

			return preg_replace( '/[^a-z0-9\-]/', '-', strtolower( $anchor ) );
		}

		public static function array_to_keywords( array $tags = array() ) {

			$keywords = array_map( 'sanitize_text_field', $tags );

			$keywords = trim( implode( ', ', $keywords ) );

			return $keywords;
		}

		public static function array_to_hashtags( array $tags = array() ) {

			$hashtags = self::sanitize_hashtags( $tags );

			$hashtags = array_filter( $hashtags );	// Removes empty array elements.

			$hashtags = trim( implode( ' ', $hashtags ) );

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

		public static function get_open_close( array $opts, $key_open, $key_midday_close, $key_midday_open, $key_close ) {

			$open_close = array();

			if ( ! empty( $opts[ $key_open ] ) && ! empty( $opts[ $key_close ] ) ) {	// Have opening and closing hours.

				$open_close[ $opts[ $key_open ] ] = $opts[ $key_close ];

				if ( ! empty( $opts[ $key_midday_close ] ) && ! empty( $opts[ $key_midday_open ] ) ) {

					/**
					 * Checks for 'none' value in midday_close and midday_open.
					 */
					$has_midday = self::is_valid_midday(
						$opts[ $key_open ],
						$opts[ $key_midday_close ],
						$opts[ $key_midday_open ],
						$opts[ $key_close ]
					);

					if ( $has_midday ) {

						$open_close[ $opts[ $key_open ] ]        = $opts[ $key_midday_close ];
						$open_close[ $opts[ $key_midday_open ] ] = $opts[ $key_close ];
					}	
				}
			}

			return $open_close;
		}

		public static function get_opts_begin( $str, array $opts ) {

			$found = array();

			foreach ( $opts as $key => $value ) {

				if ( strpos( $key, $str ) === 0 ) {

					$found[ $key ] = $value;
				}
			}

			return $found;
		}

		public static function natksort( &$arr ) {

			return uksort( $arr, 'strnatcmp' );
		}

		/**
		 * Use reference for $input argument to allow unset of keys if $remove is true.
		 */
		public static function preg_grep_keys( $pattern, array &$input, $invert = false, $replace = false, $remove = false ) {

			$invert = $invert ? PREG_GREP_INVERT : null;
			$match  = preg_grep( $pattern, array_keys( $input ), $invert );
			$found  = array();

			foreach ( $match as $key ) {

				if ( false !== $replace ) {	// Can be an empty string.

					$fixed = preg_replace( $pattern, $replace, $key );

					$found[ $fixed ] = $input[ $key ];

				} else {

					$found[ $key ] = $input[ $key ];
				}

				if ( $remove ) {

					unset( $input[ $key ] );
				}
			}

			return $found;
		}

		public static function rename_keys( &$opts = array(), $key_names = array(), $modifiers = true ) {

			foreach ( $key_names as $old_name => $new_name ) {

				if ( empty( $old_name ) ) { // Just in case.

					continue;
				}

				$old_name_preg = $modifiers ? '/^' . $old_name . '(:is|:use|#.*|_[0-9]+)?$/' : '/^' . $old_name . '$/';

				foreach ( preg_grep( $old_name_preg, array_keys ( $opts ) ) as $old_name_local ) {

					if ( ! empty( $new_name ) ) { // Can be empty to remove option.

						$new_name_local = preg_replace( $old_name_preg, $new_name . '$1', $old_name_local );

						$opts[ $new_name_local ] = $opts[ $old_name_local ];
					}

					unset( $opts[ $old_name_local ] );
				}
			}
		}

		public static function next_key( $needle, array &$input, $loop = true ) {

			$keys = array_keys( $input );
			$pos  = array_search( $needle, $keys );

			if ( false !== $pos ) {

				if ( isset( $keys[ $pos + 1 ] ) ) {

					return $keys[ $pos + 1 ];

				} elseif ( true === $loop ) {

					return $keys[0];
				}
			}

			return false;
		}

		/**
		 * Move an associative array element to the end.
		 */
		public static function move_to_end( array &$arr, $key ) {

			if ( array_key_exists( $key, $arr ) ) {

				$val = $arr[ $key ];

				unset( $arr[ $key ] );

				$arr[ $key ] = $val;
			}

			return $arr;
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

			return self::insert_in_array( 'after', $arr, $match_key, $mixed, $add_value, $ret_bool = true );
		}

		private static function insert_in_array( $pos, array &$arr, $match_key, $mixed, $add_value = null, $ret_bool = false ) {

			$matched = false;

			if ( array_key_exists( $match_key, $arr ) ) {

				$new_arr = array();

				foreach ( $arr as $key => $val ) {

					if ( 'after' === $pos ) {

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

					if ( 'before' === $pos ) {

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
				'fb:app_id'       => null,
				'fb:admins'       => null,
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
				$mt_pre . ':id'                              => '',	// Non-standard / internal meta tag.
				$mt_pre . ':retailer_item_id'                => '',	// Product ID.
				$mt_pre . ':retailer_part_no'                => '',	// Product SKU.
				$mt_pre . ':mfr_part_no'                     => '',	// Product MPN.
				$mt_pre . ':ean'                             => '',	// aka EAN, EAN-13, GTIN-13.
				$mt_pre . ':gtin14'                          => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin13'                          => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin12'                          => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin8'                           => '',	// Non-standard / internal meta tag.
				$mt_pre . ':gtin'                            => '',	// Non-standard / internal meta tag.
				$mt_pre . ':isbn'                            => '',
				$mt_pre . ':upc'                             => '',	// Aka the UPC, UPC-A, UPC, GTIN-12.

				/**
				 * Product attributes and descriptions.
				 */
				$mt_pre . ':url'                             => '',	// Non-standard / internal meta tag.
				$mt_pre . ':age_group'                       => '',
				$mt_pre . ':availability'                    => '',
				$mt_pre . ':brand'                           => '',
				$mt_pre . ':category'                        => '',	// Product category ID.
				$mt_pre . ':color'                           => '',
				$mt_pre . ':condition'                       => '',
				$mt_pre . ':expiration_time'                 => '',
				$mt_pre . ':is_product_shareable'            => '',
				$mt_pre . ':material'                        => '',
				$mt_pre . ':pattern'                         => '',
				$mt_pre . ':plural_title'                    => '',
				$mt_pre . ':product_link'                    => '',
				$mt_pre . ':purchase_limit'                  => '',
				$mt_pre . ':quantity:value'                  => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:minimum'                => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:maximum'                => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:unit_code'              => '',	// Non-standard / internal meta tag.
				$mt_pre . ':quantity:unit_text'              => '',	// Non-standard / internal meta tag.
				$mt_pre . ':retailer'                        => '',
				$mt_pre . ':retailer_category'               => '',
				$mt_pre . ':retailer_title'                  => '',
				$mt_pre . ':target_gender'                   => '',

				/**
				 * Product ratings and reviews.
				 */
				$mt_pre . ':rating:average'                  => '',	// Non-standard / internal meta tag.
				$mt_pre . ':rating:count'                    => '',	// Non-standard / internal meta tag.
				$mt_pre . ':rating:worst'                    => '',	// Non-standard / internal meta tag.
				$mt_pre . ':rating:best'                     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':review:count'                    => '',	// Non-standard / internal meta tag.

				/**
				 * Product size and weight.
				 */
				$mt_pre . ':size'                            => '',
				$mt_pre . ':depth:value'                     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':depth:units'                     => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':height:value'                    => '',	// Non-standard / internal meta tag.
				$mt_pre . ':height:units'                    => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':length:value'                    => '',	// Non-standard / internal meta tag.
				$mt_pre . ':length:units'                    => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':fluid_volume:value'              => '',	// Non-standard / internal meta tag.
				$mt_pre . ':fluid_volume:units'              => '',	// Non-standard / internal meta tag (units after value).
				$mt_pre . ':weight:value'                    => '',
				$mt_pre . ':weight:units'                    => '',
				$mt_pre . ':width:value'                     => '',	// Non-standard / internal meta tag.
				$mt_pre . ':width:units'                     => '',	// Non-standard / internal meta tag (units after value).

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
				$mt_pre . ':video:stream_url'      => '',	// Non-standard / internal meta tag.
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
			
			return self::get_first_mt_media_url( $assoc, $media_pre, $mt_suffixes );
		}

		/**
		 * Return the first URL from the associative array (og:image:secure_url, og:image:url, og:image).
		 */
		public static function get_first_mt_media_url( array $assoc, $media_pre = 'og:image', $mt_suffixes = null ) {

			if ( ! is_array( $mt_suffixes ) ) {

				$mt_suffixes = array( ':secure_url', ':url', '', ':embed_url' );
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

		public static function get_file_path_locale( $file_path, $file_url = false ) {

			if ( preg_match( '/^(.*)(\.[a-z0-9]+)$/', $file_path, $matches ) ) {

				if ( ! empty( $matches[ 2 ] ) ) {	// Just in case.

					$file_path_locale = $matches[ 1 ] . '-' . self::get_locale( 'current' ) . $matches[ 2 ];

					if ( file_exists( $file_path_locale ) ) {

						$file_path = $file_path_locale;

						if ( $file_url ) {

							$file_url = self::get_file_path_locale( $file_url );
						}
					}
				}
			}

			if ( $file_url ) {

				return array( $file_path, $file_url );
			}

			return $file_path;
		}

		public static function transl_key_values( $pattern, array &$opts, $text_domain = false ) {

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
		 * Returns a localized option value or null.
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_key_value( $key, array $opts, $mixed = 'current' ) {

			$key_locale = self::get_key_locale( $key, $opts, $mixed );

			$val_locale = isset( $opts[ $key_locale ] ) ? $opts[ $key_locale ] : null;

			/**
			 * Fallback to default value for non-existing keys or empty strings.
			 */
			if ( ! isset( $opts[ $key_locale ] ) || $opts[ $key_locale ] === '' ) {

				if ( false !== ( $pos = strpos( $key_locale, '#' ) ) ) {

					$key_default = substr( $key_locale, 0, $pos );

					$key_default = self::get_key_locale( $key_default, $opts, 'default' );

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

				$key = substr_replace( $key, '', $pos );
			}

			$default    = self::get_locale( 'default' );
			$locale     = self::get_locale( $mixed );
			$key_locale = $key . '#' . $locale;

			/**
			 * The default language may have changed, so if we're using the default, check for a locale version for the
			 * default language.
			 */
			if ( $locale === $default ) {

				return isset( $opts[ $key_locale ] ) ? $key_locale : $key;
			}

			return $key_locale;
		}

		public static function get_multi_key_locale( $prefix, array &$opts, $add_none = false ) {

			$default = self::get_locale( 'default' );
			$current = self::get_locale( 'current' );
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

		/**
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_locale( $mixed = 'current' ) {

			$cache_index = is_array( $mixed ) ? $mixed[ 'name' ] . '_' . $mixed[ 'id' ] : $mixed;

			if ( isset( self::$cache_locale[ $cache_index ] ) ) {

				return self::$cache_locale[ $cache_index ];
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

				if ( is_admin() && function_exists( 'get_user_locale' ) ) {	// Since WP v4.7.

					$locale = get_user_locale();

				} else {

					$locale = get_locale();
				}
			}

			return self::$cache_locale[ $cache_index ] = apply_filters( 'sucom_locale', $locale, $mixed );
		}

		public static function get_available_locales() {

			$available_locales = get_available_languages();		// Since WP v3.0.

			$default_locale = self::get_locale( 'default' );

			if ( ! is_array( $available_locales ) ) {		// Just in case.

				$available_locales = array( $default_locale );

			} elseif ( ! in_array( $default_locale, $available_locales ) ) {	// Just in case.

				$available_locales[] = $default_locale;
			}

			sort( $available_locales );

			return apply_filters( 'sucom_available_locales', $available_locales );
		}

		public static function complete_type_options( $type_opts, array $mod, array $opts_md_pre ) {

			if ( is_object( $mod[ 'obj' ] ) ) {	// Just in case.

				$md_defs = (array) $mod[ 'obj' ]->get_defaults( $mod[ 'id' ] );
				$md_opts = (array) $mod[ 'obj' ]->get_options( $mod[ 'id' ] );

				foreach ( $opts_md_pre as $opt_key => $md_pre ) {

					$md_defs = SucomUtil::preg_grep_keys( '/^' . $md_pre . '_/', $md_defs, false, $opt_key . '_' );
					$md_opts = SucomUtil::preg_grep_keys( '/^' . $md_pre . '_/', $md_opts, false, $opt_key . '_' );

					if ( is_array( $type_opts ) ) {

						$type_opts = array_merge( $md_defs, $type_opts, $md_opts );

					} else {

						$type_opts = array_merge( $md_defs, $md_opts );
					}
				}
			}

			return $type_opts;
		}

		public static function get_mod_anchor( array $mod ) {

			$mod_anchor = self::get_mod_salt( $mod );

			$mod_anchor = self::sanitize_anchor( $mod_anchor );

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
		public static function get_mod_salt( array $mod, $sharing_url = false ) {

			$sep = '_';

			$mod_salt = '';

			if ( ! empty( $mod[ 'name' ] ) ) {

				$mod_salt .= $sep . $mod[ 'name' ] . ':';

				if ( $mod[ 'id' ] === false ) {

					$mod_salt .= 'false';

				} elseif ( $mod[ 'id' ] === true ) {

					$mod_salt .= 'true';

				} elseif ( empty( $mod[ 'id' ] ) ) {

					$mod_salt .= '0';

				} else {

					$mod_salt .= $mod[ 'id' ];
				}
			}

			if ( ! empty( $mod[ 'tax_slug' ] ) ) {

				$mod_salt .= $sep . 'tax:' . $mod[ 'tax_slug' ];
			}

			if ( empty( $mod[ 'id' ] ) ) {

				if ( ! empty( $mod[ 'is_home' ] ) ) {

					$mod_salt .= $sep . 'home';
				}

				if ( ! empty( $sharing_url ) ) {

					$mod_salt .= $sep . 'url:' . $sharing_url;
				}
			}

			$mod_salt = ltrim( $mod_salt, $sep );

			return apply_filters( 'sucom_mod_salt', $mod_salt, $sharing_url );
		}

		public static function get_query_salt( $query_salt = '' ) {

			global $wp_query;

			if ( isset( $wp_query->query ) ) {

				$query_salt = self::get_implode_assoc( '_', ':', $wp_query->query, $query_salt );
			}

			return apply_filters( 'sucom_query_salt', $query_salt );
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

		public static function is_archive_page() {

			$ret = false;

			if ( is_archive() ) {	// False for search page.

				$ret = true;

			} elseif ( is_admin() ) {

				$screen_base = self::get_screen_base();

				if ( false !== $screen_base ) {

					switch ( $screen_base ) {

						case 'edit':		// Post/page list.

						case 'edit-tags':	// Categories/tags list.

						case 'users':		// Users list.

							$ret = true;

							break;
					}
				}
			}

			return apply_filters( 'sucom_is_archive_page', $ret );
		}

		public static function is_home_page( $use_post = false ) {

			$ret = false;

			/**
			 * Fallback to null so $use_post = 0 does not match.
			 */
			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : null;

			if ( $post_id > 0 ) {

				if ( is_numeric( $use_post ) && (int) $use_post === $post_id ) {

					$ret = true;

				} elseif ( self::get_post_object( $use_post, 'id' ) === $post_id ) {

					$ret = true;
				}
			}

			return apply_filters( 'sucom_is_home_page', $ret, $use_post );
		}

		public static function is_home_posts( $use_post = false ) {

			$ret = false;

			/**
			 * Fallback to null so $use_post = 0 does not match.
			 */
			$post_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_for_posts' ) : null;

			if ( is_numeric( $use_post ) && (int) $use_post === $post_id ) {

				$ret = true;

			} elseif ( $post_id > 0 && self::get_post_object( $use_post, 'id' ) === $post_id ) {

				$ret = true;

			} elseif ( false === $use_post && is_home() && is_front_page() ) {

				$ret = true;
			}

			return apply_filters( 'sucom_is_home_posts', $ret, $use_post );
		}

		public static function is_auto_draft( array $mod ) {

			if ( ! empty( $mod[ 'is_post' ] ) ) {

				if ( empty( $mod[ 'post_status' ] ) || $mod[ 'post_status' ] === 'auto-draft' ) {

					return true;
				}
			}

			return false;
		}

		public static function is_trashed( array $mod ) {

			if ( $mod[ 'is_post' ] && isset( $mod[ 'post_status' ] ) ) {

				if ( $mod[ 'post_status' ] === 'trash' ) {

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

		public static function is_post_type_archive( $post_type, $post_slug ) {

			$is_post_type_archive = false;

			if ( ! empty( $post_type ) && ! empty( $post_slug ) ) {	// Just in case.

				$post_type_obj = get_post_type_object( $post_type );

				if ( ! empty( $post_type_obj->has_archive ) ) {

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

			/**
			 * The 'sucom_is_post_page' filter is used by the buddypress module.
			 */
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

				$ret = term_exists( $term_id, $tax_slug );	// Since WP v3.0.

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

				$ret = term_exists( $term_id, 'category' );	// Since WP v3.0.

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

				$ret = term_exists( $term_id, 'post_tag' );	// Since WP v3.0.

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
			if ( strpos( $encoded, '&' ) === false ) {

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
			if ( strpos( $encoded, '&#' ) === false ) {

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

			return mb_decode_numericentity( $matches[0], $convmap, 'UTF-8' );
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

			if ( strpos( $text, '[' ) === false ) { // Optimize and check if there are shortcodes.

				return $text;
			}

			$text = strip_shortcodes( $text );      // Remove registered shortcodes.

			if ( strpos( $text, '[' ) === false ) { // Stop here if no shortcodes.

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
			$clean_url   = $esc_url ? esc_url_raw( $decoded_url ) : $decoded_url;
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
		 * Used to decode Facebook video urls.
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

			return mb_convert_encoding( pack( 'H*', $match[ 1 ] ), 'UTF-8', 'UCS-2' );
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

		public static function get_json_scripts( $content, $do_decode = true ) {

			if ( function_exists( 'mb_convert_encoding' ) ) {

				$content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );	// Convert to UTF8.
			}

			/**
			 * Remove containers that should not include json scripts.
			 */
			$content = preg_replace( '/<!--.*-->/Uums', '', $content );
			$content = preg_replace( '/<pre[ >].*<\/pre>/Uiums', '', $content );
			$content = preg_replace( '/<textarea[ >].*<\/textarea>/Uiums', '', $content );

			$json_data = array();

			/**
			 * U = Inverts the "greediness" of quantifiers so that they are not greedy by default.
			 * i = Letters in the pattern match both upper and lower case letters. 
			 * s = A dot metacharacter in the pattern matches all characters, including newlines.
			 *
			 * See http://php.net/manual/en/reference.pcre.pattern.modifiers.php.
			 */
			if ( preg_match_all( '/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*)<\/script>/Uis',
				$content, $all_matches, PREG_SET_ORDER ) ) {

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

			if ( empty( $country_code ) || $country_code === 'none' ) {

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

			return '';
		}

		/**
		 * Deprecated on 2020/04/14.
		 */
		public static function get_atts_src_id( array $atts, $src_name ) {

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

		public static function minify_css( $css_data, $lca ) {

			if ( ! empty( $css_data ) ) {

				$classname = apply_filters( $lca . '_load_lib', false, 'ext/compressor', 'SuextMinifyCssCompressor' );

				if ( false !== $classname && class_exists( $classname ) ) {

					$css_data = call_user_func( array( $classname, 'process' ), $css_data );
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

		public static function get_dist_name( $name, $type ) {

			if ( false !== strpos( $name, $type ) ) {

				$name = preg_replace( '/^(.*) ' . $type . '( [\[\(].+[\)\]])?$/U', '$1$2', $name );
			}

			return preg_replace( '/^(.*)( [\[\(].+[\)\]])?$/U', '$1 ' . $type . '$2', $name );
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
		 * Site Address (URL).
		 *
		 * Returns a custom site address URL or the default site address URL (aka home URL).
		 *
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function get_site_url( array $opts = array(), $mixed = 'current' ) {

			$site_url = empty( $opts ) ? '' : self::get_key_value( 'site_url', $opts, $mixed );

			if ( empty( $site_url ) ) {	// Fallback to default WordPress value.

				$site_url = get_bloginfo( $show = 'url', $filter = 'raw' );	// Fallback to default WordPress value.
			}

			return $site_url;
		}

		/**
		 * WordPress Address (URL).
		 */
		public static function get_wp_url( array $opts = array(), $mixed = 'current' ) {

			$wp_url = empty( $opts ) ? '' : self::get_key_value( 'wp_url', $opts, $mixed );

			if ( empty( $wp_url ) ) {

				$wp_url = get_bloginfo( $show = 'wpurl', $filter = 'raw' );	// Fallback to default WordPress value.
			}

			return $wp_url;
		}

		/**
		 * $mixed = 'default' | 'current' | post ID | $mod array
		 */
		public static function is_site_https( array $opts = array(), $mixed = 'current' ) {

			if ( self::get_const( 'FORCE_SSL' ) ) {	// Optimize - all front-end URLs are forced to https.

				return true;
			}

			return self::is_https( self::get_site_url( $opts, $mixed ) );
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

		public static function insert_html_tag_attributes( $html, array $attr_names_values ) {

			foreach ( $attr_names_values as $attr_name => $attr_value ) {

				if ( false !== $attr_value && strpos( $html, ' ' . $attr_name . '=' ) === false ) {

					$html = preg_replace( '/ *\/?' . '>/', ' ' . $attr_name . '="' . $attr_value . '"$0', $html );
				}
			}

			return $html;
		}

		/**
		 * See https://developers.google.com/search/reference/robots_meta_tag.
		 */
		public static function get_robots_default_directives() {

			if ( isset( $_GET[ 'replytocom' ] ) ) {

				$directives = array(
					'follow'    => true,	// Allow follow.
					'noarchive' => true,
					'noindex'   => true,
					'nosnippet' => true,
				);

			/**
			 * The site is not public, so discourage robots from indexing the site.
			 */
			} elseif ( ! get_option( 'blog_public' ) ) {

				$directives = array(
					'noarchive' => true,
					'nofollow'  => true,
					'noindex'   => true,
					'nosnippet' => true,
				);

			/**
			 * The current webpage should not be indexed, but allow robots to follow links.
			 */
			} elseif ( is_404() || is_search() ) {

				$directives = array(
					'follow'    => true,	// Allow follow.
					'noarchive' => true,
					'noindex'   => true,
					'nosnippet' => true,
				);

			/**
			 * Default robots.
			 */
			} else {

				$directives = array(
					'follow'            => true,	// Allow follow.
					'index'             => true,	// Allow index.
					'max-image-preview' => 'large',	// Max size for image preview.
					'max-snippet'       => -1,	// Max characters for textual snippet (-1 = no limit).
					'max-video-preview' => -1,	// Max seconds for video snippet (-1 = no limit).
				);
			}

			return apply_filters( 'sucom_robots_default_directives', $directives );
		}

		/**
		 * Deprecated on 2020/03/23.
		 */
		public static function get_lib_stub_action( $lib_id ) {

			return array( $lib_id, false, false );
		}
	}
}

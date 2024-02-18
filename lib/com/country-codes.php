<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'SucomCountryCodes' ) ) {

	class SucomCountryCodes {

		/*
		 * See https://en.wikipedia.org/wiki/ISO_3166-1.
		 */
		private static $countries = array(
			'Afghanistan' => array(
				'alpha2'  => 'AF',
				'alpha3'  => 'AFG',
				'numeric' => '004',
			),
			'Åland Islands' => array(
				'alpha2'  => 'AX',
				'alpha3'  => 'ALA',
				'numeric' => '248',
			),
			'Albania' => array(
				'alpha2'  => 'AL',
				'alpha3'  => 'ALB',
				'numeric' => '008',
			),
			'Algeria' => array(
				'alpha2'  => 'DZ',
				'alpha3'  => 'DZA',
				'numeric' => '012',
			),
			'American Samoa' => array(
				'alpha2'  => 'AS',
				'alpha3'  => 'ASM',
				'numeric' => '016',
			),
			'Andorra' => array(
				'alpha2'  => 'AD',
				'alpha3'  => 'AND',
				'numeric' => '020',
			),
			'Angola' => array(
				'alpha2'  => 'AO',
				'alpha3'  => 'AGO',
				'numeric' => '024',
			),
			'Anguilla' => array(
				'alpha2'  => 'AI',
				'alpha3'  => 'AIA',
				'numeric' => '660',
			),
			'Antarctica' => array(
				'alpha2'  => 'AQ',
				'alpha3'  => 'ATA',
				'numeric' => '010',
			),
			'Antigua and Barbuda' => array(
				'alpha2'  => 'AG',
				'alpha3'  => 'ATG',
				'numeric' => '028',
			),
			'Argentina' => array(
				'alpha2'  => 'AR',
				'alpha3'  => 'ARG',
				'numeric' => '032',
			),
			'Armenia' => array(
				'alpha2'  => 'AM',
				'alpha3'  => 'ARM',
				'numeric' => '051',
			),
			'Aruba' => array(
				'alpha2'  => 'AW',
				'alpha3'  => 'ABW',
				'numeric' => '533',
			),
			'Australia' => array(
				'alpha2'  => 'AU',
				'alpha3'  => 'AUS',
				'numeric' => '036',
			),
			'Austria' => array(
				'alpha2'  => 'AT',
				'alpha3'  => 'AUT',
				'numeric' => '040',
			),
			'Azerbaijan' => array(
				'alpha2'  => 'AZ',
				'alpha3'  => 'AZE',
				'numeric' => '031',
			),
			'Bahamas' => array(
				'alpha2'  => 'BS',
				'alpha3'  => 'BHS',
				'numeric' => '044',
			),
			'Bahrain' => array(
				'alpha2'  => 'BH',
				'alpha3'  => 'BHR',
				'numeric' => '048',
			),
			'Bangladesh' => array(
				'alpha2'  => 'BD',
				'alpha3'  => 'BGD',
				'numeric' => '050',
			),
			'Barbados' => array(
				'alpha2'  => 'BB',
				'alpha3'  => 'BRB',
				'numeric' => '052',
			),
			'Belarus' => array(
				'alpha2'  => 'BY',
				'alpha3'  => 'BLR',
				'numeric' => '112',
			),
			'Belgium' => array(
				'alpha2'  => 'BE',
				'alpha3'  => 'BEL',
				'numeric' => '056',
			),
			'Belize' => array(
				'alpha2'  => 'BZ',
				'alpha3'  => 'BLZ',
				'numeric' => '084',
			),
			'Benin' => array(
				'alpha2'  => 'BJ',
				'alpha3'  => 'BEN',
				'numeric' => '204',
			),
			'Bermuda' => array(
				'alpha2'  => 'BM',
				'alpha3'  => 'BMU',
				'numeric' => '060',
			),
			'Bhutan' => array(
				'alpha2'  => 'BT',
				'alpha3'  => 'BTN',
				'numeric' => '064',
			),
			'Bolivia (Plurinational State of)' => array(
				'alpha2'  => 'BO',
				'alpha3'  => 'BOL',
				'numeric' => '068',
			),
			'Bonaire, Sint Eustatius and Saba' => array(
				'alpha2'  => 'BQ',
				'alpha3'  => 'BES',
				'numeric' => '535',
			),
			'Bosnia and Herzegovina' => array(
				'alpha2'  => 'BA',
				'alpha3'  => 'BIH',
				'numeric' => '070',
			),
			'Botswana' => array(
				'alpha2'  => 'BW',
				'alpha3'  => 'BWA',
				'numeric' => '072',
			),
			'Bouvet Island' => array(
				'alpha2'  => 'BV',
				'alpha3'  => 'BVT',
				'numeric' => '074',
			),
			'Brazil' => array(
				'alpha2'  => 'BR',
				'alpha3'  => 'BRA',
				'numeric' => '076',
			),
			'British Indian Ocean Territory' => array(
				'alpha2'  => 'IO',
				'alpha3'  => 'IOT',
				'numeric' => '086',
			),
			'Brunei Darussalam' => array(
				'alpha2'  => 'BN',
				'alpha3'  => 'BRN',
				'numeric' => '096',
			),
			'Bulgaria' => array(
				'alpha2'  => 'BG',
				'alpha3'  => 'BGR',
				'numeric' => '100',
			),
			'Burkina Faso' => array(
				'alpha2'  => 'BF',
				'alpha3'  => 'BFA',
				'numeric' => '854',
			),
			'Burundi' => array(
				'alpha2'  => 'BI',
				'alpha3'  => 'BDI',
				'numeric' => '108',
			),
			'Cabo Verde' => array(
				'alpha2'  => 'CV',
				'alpha3'  => 'CPV',
				'numeric' => '132',
			),
			'Cambodia' => array(
				'alpha2'  => 'KH',
				'alpha3'  => 'KHM',
				'numeric' => '116',
			),
			'Cameroon' => array(
				'alpha2'  => 'CM',
				'alpha3'  => 'CMR',
				'numeric' => '120',
			),
			'Canada' => array(
				'alpha2'  => 'CA',
				'alpha3'  => 'CAN',
				'numeric' => '124',
			),
			'Cayman Islands' => array(
				'alpha2'  => 'KY',
				'alpha3'  => 'CYM',
				'numeric' => '136',
			),
			'Central African Republic' => array(
				'alpha2'  => 'CF',
				'alpha3'  => 'CAF',
				'numeric' => '140',
			),
			'Chad' => array(
				'alpha2'  => 'TD',
				'alpha3'  => 'TCD',
				'numeric' => '148',
			),
			'Chile' => array(
				'alpha2'  => 'CL',
				'alpha3'  => 'CHL',
				'numeric' => '152',
			),
			'China' => array(
				'alpha2'  => 'CN',
				'alpha3'  => 'CHN',
				'numeric' => '156',
			),
			'Christmas Island' => array(
				'alpha2'  => 'CX',
				'alpha3'  => 'CXR',
				'numeric' => '162',
			),
			'Cocos (Keeling) Islands' => array(
				'alpha2'  => 'CC',
				'alpha3'  => 'CCK',
				'numeric' => '166',
			),
			'Colombia' => array(
				'alpha2'  => 'CO',
				'alpha3'  => 'COL',
				'numeric' => '170',
			),
			'Comoros' => array(
				'alpha2'  => 'KM',
				'alpha3'  => 'COM',
				'numeric' => '174',
			),
			'Congo' => array(
				'alpha2'  => 'CG',
				'alpha3'  => 'COG',
				'numeric' => '178',
			),
			'Congo (Democratic Republic of the)' => array(
				'alpha2'  => 'CD',
				'alpha3'  => 'COD',
				'numeric' => '180',
			),
			'Cook Islands' => array(
				'alpha2'  => 'CK',
				'alpha3'  => 'COK',
				'numeric' => '184',
			),
			'Costa Rica' => array(
				'alpha2'  => 'CR',
				'alpha3'  => 'CRI',
				'numeric' => '188',
			),
			'Côte d\'Ivoire' => array(
				'alpha2'  => 'CI',
				'alpha3'  => 'CIV',
				'numeric' => '384',
			),
			'Croatia' => array(
				'alpha2'  => 'HR',
				'alpha3'  => 'HRV',
				'numeric' => '191',
			),
			'Cuba' => array(
				'alpha2'  => 'CU',
				'alpha3'  => 'CUB',
				'numeric' => '192',
			),
			'Curaçao' => array(
				'alpha2'  => 'CW',
				'alpha3'  => 'CUW',
				'numeric' => '531',
			),
			'Cyprus' => array(
				'alpha2'  => 'CY',
				'alpha3'  => 'CYP',
				'numeric' => '196',
			),
			'Czech Republic' => array(
				'alpha2'  => 'CZ',
				'alpha3'  => 'CZE',
				'numeric' => '203',
			),
			'Denmark' => array(
				'alpha2'  => 'DK',
				'alpha3'  => 'DNK',
				'numeric' => '208',
			),
			'Djibouti' => array(
				'alpha2'  => 'DJ',
				'alpha3'  => 'DJI',
				'numeric' => '262',
			),
			'Dominica' => array(
				'alpha2'  => 'DM',
				'alpha3'  => 'DMA',
				'numeric' => '212',
			),
			'Dominican Republic' => array(
				'alpha2'  => 'DO',
				'alpha3'  => 'DOM',
				'numeric' => '214',
			),
			'Ecuador' => array(
				'alpha2'  => 'EC',
				'alpha3'  => 'ECU',
				'numeric' => '218',
			),
			'Egypt' => array(
				'alpha2'  => 'EG',
				'alpha3'  => 'EGY',
				'numeric' => '818',
			),
			'El Salvador' => array(
				'alpha2'  => 'SV',
				'alpha3'  => 'SLV',
				'numeric' => '222',
			),
			'Equatorial Guinea' => array(
				'alpha2'  => 'GQ',
				'alpha3'  => 'GNQ',
				'numeric' => '226',
			),
			'Eritrea' => array(
				'alpha2'  => 'ER',
				'alpha3'  => 'ERI',
				'numeric' => '232',
			),
			'Estonia' => array(
				'alpha2'  => 'EE',
				'alpha3'  => 'EST',
				'numeric' => '233',
			),
			'Ethiopia' => array(
				'alpha2'  => 'ET',
				'alpha3'  => 'ETH',
				'numeric' => '231',
			),
			'Falkland Islands (Malvinas)' => array(
				'alpha2'  => 'FK',
				'alpha3'  => 'FLK',
				'numeric' => '238',
			),
			'Faroe Islands' => array(
				'alpha2'  => 'FO',
				'alpha3'  => 'FRO',
				'numeric' => '234',
			),
			'Fiji' => array(
				'alpha2'  => 'FJ',
				'alpha3'  => 'FJI',
				'numeric' => '242',
			),
			'Finland' => array(
				'alpha2'  => 'FI',
				'alpha3'  => 'FIN',
				'numeric' => '246',
			),
			'France' => array(
				'alpha2'  => 'FR',
				'alpha3'  => 'FRA',
				'numeric' => '250',
			),
			'French Guiana' => array(
				'alpha2'  => 'GF',
				'alpha3'  => 'GUF',
				'numeric' => '254',
			),
			'French Polynesia' => array(
				'alpha2'  => 'PF',
				'alpha3'  => 'PYF',
				'numeric' => '258',
			),
			'French Southern Territories' => array(
				'alpha2'  => 'TF',
				'alpha3'  => 'ATF',
				'numeric' => '260',
			),
			'Gabon' => array(
				'alpha2'  => 'GA',
				'alpha3'  => 'GAB',
				'numeric' => '266',
			),
			'Gambia' => array(
				'alpha2'  => 'GM',
				'alpha3'  => 'GMB',
				'numeric' => '270',
			),
			'Georgia' => array(
				'alpha2'  => 'GE',
				'alpha3'  => 'GEO',
				'numeric' => '268',
			),
			'Germany' => array(
				'alpha2'  => 'DE',
				'alpha3'  => 'DEU',
				'numeric' => '276',
			),
			'Ghana' => array(
				'alpha2'  => 'GH',
				'alpha3'  => 'GHA',
				'numeric' => '288',
			),
			'Gibraltar' => array(
				'alpha2'  => 'GI',
				'alpha3'  => 'GIB',
				'numeric' => '292',
			),
			'Greece' => array(
				'alpha2'  => 'GR',
				'alpha3'  => 'GRC',
				'numeric' => '300',
			),
			'Greenland' => array(
				'alpha2'  => 'GL',
				'alpha3'  => 'GRL',
				'numeric' => '304',
			),
			'Grenada' => array(
				'alpha2'  => 'GD',
				'alpha3'  => 'GRD',
				'numeric' => '308',
			),
			'Guadeloupe' => array(
				'alpha2'  => 'GP',
				'alpha3'  => 'GLP',
				'numeric' => '312',
			),
			'Guam' => array(
				'alpha2'  => 'GU',
				'alpha3'  => 'GUM',
				'numeric' => '316',
			),
			'Guatemala' => array(
				'alpha2'  => 'GT',
				'alpha3'  => 'GTM',
				'numeric' => '320',
			),
			'Guernsey' => array(
				'alpha2'  => 'GG',
				'alpha3'  => 'GGY',
				'numeric' => '831',
			),
			'Guinea' => array(
				'alpha2'  => 'GN',
				'alpha3'  => 'GIN',
				'numeric' => '324',
			),
			'Guinea-Bissau' => array(
				'alpha2'  => 'GW',
				'alpha3'  => 'GNB',
				'numeric' => '624',
			),
			'Guyana' => array(
				'alpha2'  => 'GY',
				'alpha3'  => 'GUY',
				'numeric' => '328',
			),
			'Haiti' => array(
				'alpha2'  => 'HT',
				'alpha3'  => 'HTI',
				'numeric' => '332',
			),
			'Heard Island and McDonald Islands' => array(
				'alpha2'  => 'HM',
				'alpha3'  => 'HMD',
				'numeric' => '334',
			),
			'Holy See' => array(
				'alpha2'  => 'VA',
				'alpha3'  => 'VAT',
				'numeric' => '336',
			),
			'Honduras' => array(
				'alpha2'  => 'HN',
				'alpha3'  => 'HND',
				'numeric' => '340',
			),
			'Hong Kong' => array(
				'alpha2'  => 'HK',
				'alpha3'  => 'HKG',
				'numeric' => '344',
			),
			'Hungary' => array(
				'alpha2'  => 'HU',
				'alpha3'  => 'HUN',
				'numeric' => '348',
			),
			'Iceland' => array(
				'alpha2'  => 'IS',
				'alpha3'  => 'ISL',
				'numeric' => '352',
			),
			'India' => array(
				'alpha2'  => 'IN',
				'alpha3'  => 'IND',
				'numeric' => '356',
			),
			'Indonesia' => array(
				'alpha2'  => 'ID',
				'alpha3'  => 'IDN',
				'numeric' => '360',
			),
			'Iran (Islamic Republic of)' => array(
				'alpha2'  => 'IR',
				'alpha3'  => 'IRN',
				'numeric' => '364',
			),
			'Iraq' => array(
				'alpha2'  => 'IQ',
				'alpha3'  => 'IRQ',
				'numeric' => '368',
			),
			'Ireland' => array(
				'alpha2'  => 'IE',
				'alpha3'  => 'IRL',
				'numeric' => '372',
			),
			'Isle of Man' => array(
				'alpha2'  => 'IM',
				'alpha3'  => 'IMN',
				'numeric' => '833',
			),
			'Israel' => array(
				'alpha2'  => 'IL',
				'alpha3'  => 'ISR',
				'numeric' => '376',
			),
			'Italy' => array(
				'alpha2'  => 'IT',
				'alpha3'  => 'ITA',
				'numeric' => '380',
			),
			'Jamaica' => array(
				'alpha2'  => 'JM',
				'alpha3'  => 'JAM',
				'numeric' => '388',
			),
			'Japan' => array(
				'alpha2'  => 'JP',
				'alpha3'  => 'JPN',
				'numeric' => '392',
			),
			'Jersey' => array(
				'alpha2'  => 'JE',
				'alpha3'  => 'JEY',
				'numeric' => '832',
			),
			'Jordan' => array(
				'alpha2'  => 'JO',
				'alpha3'  => 'JOR',
				'numeric' => '400',
			),
			'Kazakhstan' => array(
				'alpha2'  => 'KZ',
				'alpha3'  => 'KAZ',
				'numeric' => '398',
			),
			'Kenya' => array(
				'alpha2'  => 'KE',
				'alpha3'  => 'KEN',
				'numeric' => '404',
			),
			'Kiribati' => array(
				'alpha2'  => 'KI',
				'alpha3'  => 'KIR',
				'numeric' => '296',
			),
			'Korea (Democratic People\'s Republic of)' => array(
				'alpha2'  => 'KP',
				'alpha3'  => 'PRK',
				'numeric' => '408',
			),
			'Korea (Republic of)' => array(
				'alpha2'  => 'KR',
				'alpha3'  => 'KOR',
				'numeric' => '410',
			),
			'Kuwait' => array(
				'alpha2'  => 'KW',
				'alpha3'  => 'KWT',
				'numeric' => '414',
			),
			'Kyrgyzstan' => array(
				'alpha2'  => 'KG',
				'alpha3'  => 'KGZ',
				'numeric' => '417',
			),
			'Lao People\'s Democratic Republic' => array(
				'alpha2'  => 'LA',
				'alpha3'  => 'LAO',
				'numeric' => '418',
			),
			'Latvia' => array(
				'alpha2'  => 'LV',
				'alpha3'  => 'LVA',
				'numeric' => '428',
			),
			'Lebanon' => array(
				'alpha2'  => 'LB',
				'alpha3'  => 'LBN',
				'numeric' => '422',
			),
			'Lesotho' => array(
				'alpha2'  => 'LS',
				'alpha3'  => 'LSO',
				'numeric' => '426',
			),
			'Liberia' => array(
				'alpha2'  => 'LR',
				'alpha3'  => 'LBR',
				'numeric' => '430',
			),
			'Libya' => array(
				'alpha2'  => 'LY',
				'alpha3'  => 'LBY',
				'numeric' => '434',
			),
			'Liechtenstein' => array(
				'alpha2'  => 'LI',
				'alpha3'  => 'LIE',
				'numeric' => '438',
			),
			'Lithuania' => array(
				'alpha2'  => 'LT',
				'alpha3'  => 'LTU',
				'numeric' => '440',
			),
			'Luxembourg' => array(
				'alpha2'  => 'LU',
				'alpha3'  => 'LUX',
				'numeric' => '442',
			),
			'Macao' => array(
				'alpha2'  => 'MO',
				'alpha3'  => 'MAC',
				'numeric' => '446',
			),
			'Macedonia (the former Yugoslav Republic of)' => array(
				'alpha2'  => 'MK',
				'alpha3'  => 'MKD',
				'numeric' => '807',
			),
			'Madagascar' => array(
				'alpha2'  => 'MG',
				'alpha3'  => 'MDG',
				'numeric' => '450',
			),
			'Malawi' => array(
				'alpha2'  => 'MW',
				'alpha3'  => 'MWI',
				'numeric' => '454',
			),
			'Malaysia' => array(
				'alpha2'  => 'MY',
				'alpha3'  => 'MYS',
				'numeric' => '458',
			),
			'Maldives' => array(
				'alpha2'  => 'MV',
				'alpha3'  => 'MDV',
				'numeric' => '462',
			),
			'Mali' => array(
				'alpha2'  => 'ML',
				'alpha3'  => 'MLI',
				'numeric' => '466',
			),
			'Malta' => array(
				'alpha2'  => 'MT',
				'alpha3'  => 'MLT',
				'numeric' => '470',
			),
			'Marshall Islands' => array(
				'alpha2'  => 'MH',
				'alpha3'  => 'MHL',
				'numeric' => '584',
			),
			'Martinique' => array(
				'alpha2'  => 'MQ',
				'alpha3'  => 'MTQ',
				'numeric' => '474',
			),
			'Mauritania' => array(
				'alpha2'  => 'MR',
				'alpha3'  => 'MRT',
				'numeric' => '478',
			),
			'Mauritius' => array(
				'alpha2'  => 'MU',
				'alpha3'  => 'MUS',
				'numeric' => '480',
			),
			'Mayotte' => array(
				'alpha2'  => 'YT',
				'alpha3'  => 'MYT',
				'numeric' => '175',
			),
			'Mexico' => array(
				'alpha2'  => 'MX',
				'alpha3'  => 'MEX',
				'numeric' => '484',
			),
			'Micronesia (Federated States of)' => array(
				'alpha2'  => 'FM',
				'alpha3'  => 'FSM',
				'numeric' => '583',
			),
			'Moldova (Republic of)' => array(
				'alpha2'  => 'MD',
				'alpha3'  => 'MDA',
				'numeric' => '498',
			),
			'Monaco' => array(
				'alpha2'  => 'MC',
				'alpha3'  => 'MCO',
				'numeric' => '492',
			),
			'Mongolia' => array(
				'alpha2'  => 'MN',
				'alpha3'  => 'MNG',
				'numeric' => '496',
			),
			'Montenegro' => array(
				'alpha2'  => 'ME',
				'alpha3'  => 'MNE',
				'numeric' => '499',
			),
			'Montserrat' => array(
				'alpha2'  => 'MS',
				'alpha3'  => 'MSR',
				'numeric' => '500',
			),
			'Morocco' => array(
				'alpha2'  => 'MA',
				'alpha3'  => 'MAR',
				'numeric' => '504',
			),
			'Mozambique' => array(
				'alpha2'  => 'MZ',
				'alpha3'  => 'MOZ',
				'numeric' => '508',
			),
			'Myanmar' => array(
				'alpha2'  => 'MM',
				'alpha3'  => 'MMR',
				'numeric' => '104',
			),
			'Namibia' => array(
				'alpha2'  => 'NA',
				'alpha3'  => 'NAM',
				'numeric' => '516',
			),
			'Nauru' => array(
				'alpha2'  => 'NR',
				'alpha3'  => 'NRU',
				'numeric' => '520',
			),
			'Nepal' => array(
				'alpha2'  => 'NP',
				'alpha3'  => 'NPL',
				'numeric' => '524',
			),
			'Netherlands' => array(
				'alpha2'  => 'NL',
				'alpha3'  => 'NLD',
				'numeric' => '528',
			),
			'New Caledonia' => array(
				'alpha2'  => 'NC',
				'alpha3'  => 'NCL',
				'numeric' => '540',
			),
			'New Zealand' => array(
				'alpha2'  => 'NZ',
				'alpha3'  => 'NZL',
				'numeric' => '554',
			),
			'Nicaragua' => array(
				'alpha2'  => 'NI',
				'alpha3'  => 'NIC',
				'numeric' => '558',
			),
			'Niger' => array(
				'alpha2'  => 'NE',
				'alpha3'  => 'NER',
				'numeric' => '562',
			),
			'Nigeria' => array(
				'alpha2'  => 'NG',
				'alpha3'  => 'NGA',
				'numeric' => '566',
			),
			'Niue' => array(
				'alpha2'  => 'NU',
				'alpha3'  => 'NIU',
				'numeric' => '570',
			),
			'Norfolk Island' => array(
				'alpha2'  => 'NF',
				'alpha3'  => 'NFK',
				'numeric' => '574',
			),
			'Northern Mariana Islands' => array(
				'alpha2'  => 'MP',
				'alpha3'  => 'MNP',
				'numeric' => '580',
			),
			'Norway' => array(
				'alpha2'  => 'NO',
				'alpha3'  => 'NOR',
				'numeric' => '578',
			),
			'Oman' => array(
				'alpha2'  => 'OM',
				'alpha3'  => 'OMN',
				'numeric' => '512',
			),
			'Pakistan' => array(
				'alpha2'  => 'PK',
				'alpha3'  => 'PAK',
				'numeric' => '586',
			),
			'Palau' => array(
				'alpha2'  => 'PW',
				'alpha3'  => 'PLW',
				'numeric' => '585',
			),
			'Palestine, State of' => array(
				'alpha2'  => 'PS',
				'alpha3'  => 'PSE',
				'numeric' => '275',
			),
			'Panama' => array(
				'alpha2'  => 'PA',
				'alpha3'  => 'PAN',
				'numeric' => '591',
			),
			'Papua New Guinea' => array(
				'alpha2'  => 'PG',
				'alpha3'  => 'PNG',
				'numeric' => '598',
			),
			'Paraguay' => array(
				'alpha2'  => 'PY',
				'alpha3'  => 'PRY',
				'numeric' => '600',
			),
			'Peru' => array(
				'alpha2'  => 'PE',
				'alpha3'  => 'PER',
				'numeric' => '604',
			),
			'Philippines' => array(
				'alpha2'  => 'PH',
				'alpha3'  => 'PHL',
				'numeric' => '608',
			),
			'Pitcairn' => array(
				'alpha2'  => 'PN',
				'alpha3'  => 'PCN',
				'numeric' => '612',
			),
			'Poland' => array(
				'alpha2'  => 'PL',
				'alpha3'  => 'POL',
				'numeric' => '616',
			),
			'Portugal' => array(
				'alpha2'  => 'PT',
				'alpha3'  => 'PRT',
				'numeric' => '620',
			),
			'Puerto Rico' => array(
				'alpha2'  => 'PR',
				'alpha3'  => 'PRI',
				'numeric' => '630',
			),
			'Qatar' => array(
				'alpha2'  => 'QA',
				'alpha3'  => 'QAT',
				'numeric' => '634',
			),
			'Réunion' => array(
				'alpha2'  => 'RE',
				'alpha3'  => 'REU',
				'numeric' => '638',
			),
			'Romania' => array(
				'alpha2'  => 'RO',
				'alpha3'  => 'ROU',
				'numeric' => '642',
			),
			'Russian Federation' => array(
				'alpha2'  => 'RU',
				'alpha3'  => 'RUS',
				'numeric' => '643',
			),
			'Rwanda' => array(
				'alpha2'  => 'RW',
				'alpha3'  => 'RWA',
				'numeric' => '646',
			),
			'Saint Barthélemy' => array(
				'alpha2'  => 'BL',
				'alpha3'  => 'BLM',
				'numeric' => '652',
			),
			'Saint Helena, Ascension and Tristan da Cunha' => array(
				'alpha2'  => 'SH',
				'alpha3'  => 'SHN',
				'numeric' => '654',
			),
			'Saint Kitts and Nevis' => array(
				'alpha2'  => 'KN',
				'alpha3'  => 'KNA',
				'numeric' => '659',
			),
			'Saint Lucia' => array(
				'alpha2'  => 'LC',
				'alpha3'  => 'LCA',
				'numeric' => '662',
			),
			'Saint Martin (French part)' => array(
				'alpha2'  => 'MF',
				'alpha3'  => 'MAF',
				'numeric' => '663',
			),
			'Saint Pierre and Miquelon' => array(
				'alpha2'  => 'PM',
				'alpha3'  => 'SPM',
				'numeric' => '666',
			),
			'Saint Vincent and the Grenadines' => array(
				'alpha2'  => 'VC',
				'alpha3'  => 'VCT',
				'numeric' => '670',
			),
			'Samoa' => array(
				'alpha2'  => 'WS',
				'alpha3'  => 'WSM',
				'numeric' => '882',
			),
			'San Marino' => array(
				'alpha2'  => 'SM',
				'alpha3'  => 'SMR',
				'numeric' => '674',
			),
			'Sao Tome and Principe' => array(
				'alpha2'  => 'ST',
				'alpha3'  => 'STP',
				'numeric' => '678',
			),
			'Saudi Arabia' => array(
				'alpha2'  => 'SA',
				'alpha3'  => 'SAU',
				'numeric' => '682',
			),
			'Senegal' => array(
				'alpha2'  => 'SN',
				'alpha3'  => 'SEN',
				'numeric' => '686',
			),
			'Serbia' => array(
				'alpha2'  => 'RS',
				'alpha3'  => 'SRB',
				'numeric' => '688',
			),
			'Seychelles' => array(
				'alpha2'  => 'SC',
				'alpha3'  => 'SYC',
				'numeric' => '690',
			),
			'Sierra Leone' => array(
				'alpha2'  => 'SL',
				'alpha3'  => 'SLE',
				'numeric' => '694',
			),
			'Singapore' => array(
				'alpha2'  => 'SG',
				'alpha3'  => 'SGP',
				'numeric' => '702',
			),
			'Sint Maarten (Dutch part)' => array(
				'alpha2'  => 'SX',
				'alpha3'  => 'SXM',
				'numeric' => '534',
			),
			'Slovakia' => array(
				'alpha2'  => 'SK',
				'alpha3'  => 'SVK',
				'numeric' => '703',
			),
			'Slovenia' => array(
				'alpha2'  => 'SI',
				'alpha3'  => 'SVN',
				'numeric' => '705',
			),
			'Solomon Islands' => array(
				'alpha2'  => 'SB',
				'alpha3'  => 'SLB',
				'numeric' => '090',
			),
			'Somalia' => array(
				'alpha2'  => 'SO',
				'alpha3'  => 'SOM',
				'numeric' => '706',
			),
			'South Africa' => array(
				'alpha2'  => 'ZA',
				'alpha3'  => 'ZAF',
				'numeric' => '710',
			),
			'South Georgia and the South Sandwich Islands' => array(
				'alpha2'  => 'GS',
				'alpha3'  => 'SGS',
				'numeric' => '239',
			),
			'South Sudan' => array(
				'alpha2'  => 'SS',
				'alpha3'  => 'SSD',
				'numeric' => '728',
			),
			'Spain' => array(
				'alpha2'  => 'ES',
				'alpha3'  => 'ESP',
				'numeric' => '724',
			),
			'Sri Lanka' => array(
				'alpha2'  => 'LK',
				'alpha3'  => 'LKA',
				'numeric' => '144',
			),
			'Sudan' => array(
				'alpha2'  => 'SD',
				'alpha3'  => 'SDN',
				'numeric' => '729',
			),
			'Suriname' => array(
				'alpha2'  => 'SR',
				'alpha3'  => 'SUR',
				'numeric' => '740',
			),
			'Svalbard and Jan Mayen' => array(
				'alpha2'  => 'SJ',
				'alpha3'  => 'SJM',
				'numeric' => '744',
			),
			'Swaziland' => array(
				'alpha2'  => 'SZ',
				'alpha3'  => 'SWZ',
				'numeric' => '748',
			),
			'Sweden' => array(
				'alpha2'  => 'SE',
				'alpha3'  => 'SWE',
				'numeric' => '752',
			),
			'Switzerland' => array(
				'alpha2'  => 'CH',
				'alpha3'  => 'CHE',
				'numeric' => '756',
			),
			'Syrian Arab Republic' => array(
				'alpha2'  => 'SY',
				'alpha3'  => 'SYR',
				'numeric' => '760',
			),
			'Taiwan, Province of China[a]' => array(
				'alpha2'  => 'TW',
				'alpha3'  => 'TWN',
				'numeric' => '158',
			),
			'Tajikistan' => array(
				'alpha2'  => 'TJ',
				'alpha3'  => 'TJK',
				'numeric' => '762',
			),
			'Tanzania, United Republic of' => array(
				'alpha2'  => 'TZ',
				'alpha3'  => 'TZA',
				'numeric' => '834',
			),
			'Thailand' => array(
				'alpha2'  => 'TH',
				'alpha3'  => 'THA',
				'numeric' => '764',
			),
			'Timor-Leste' => array(
				'alpha2'  => 'TL',
				'alpha3'  => 'TLS',
				'numeric' => '626',
			),
			'Togo' => array(
				'alpha2'  => 'TG',
				'alpha3'  => 'TGO',
				'numeric' => '768',
			),
			'Tokelau' => array(
				'alpha2'  => 'TK',
				'alpha3'  => 'TKL',
				'numeric' => '772',
			),
			'Tonga' => array(
				'alpha2'  => 'TO',
				'alpha3'  => 'TON',
				'numeric' => '776',
			),
			'Trinidad and Tobago' => array(
				'alpha2'  => 'TT',
				'alpha3'  => 'TTO',
				'numeric' => '780',
			),
			'Tunisia' => array(
				'alpha2'  => 'TN',
				'alpha3'  => 'TUN',
				'numeric' => '788',
			),
			'Turkey' => array(
				'alpha2'  => 'TR',
				'alpha3'  => 'TUR',
				'numeric' => '792',
			),
			'Turkmenistan' => array(
				'alpha2'  => 'TM',
				'alpha3'  => 'TKM',
				'numeric' => '795',
			),
			'Turks and Caicos Islands' => array(
				'alpha2'  => 'TC',
				'alpha3'  => 'TCA',
				'numeric' => '796',
			),
			'Tuvalu' => array(
				'alpha2'  => 'TV',
				'alpha3'  => 'TUV',
				'numeric' => '798',
			),
			'Uganda' => array(
				'alpha2'  => 'UG',
				'alpha3'  => 'UGA',
				'numeric' => '800',
			),
			'Ukraine' => array(
				'alpha2'  => 'UA',
				'alpha3'  => 'UKR',
				'numeric' => '804',
			),
			'United Arab Emirates' => array(
				'alpha2'  => 'AE',
				'alpha3'  => 'ARE',
				'numeric' => '784',
			),
			'United Kingdom of Great Britain and Northern Ireland' => array(
				'alpha2'  => 'GB',
				'alpha3'  => 'GBR',
				'numeric' => '826',
			),
			'United States Minor Outlying Islands' => array(
				'alpha2'  => 'UM',
				'alpha3'  => 'UMI',
				'numeric' => '581',
			),
			'United States of America' => array(
				'alpha2'  => 'US',
				'alpha3'  => 'USA',
				'numeric' => '840',
			),
			'Uruguay' => array(
				'alpha2'  => 'UY',
				'alpha3'  => 'URY',
				'numeric' => '858',
			),
			'Uzbekistan' => array(
				'alpha2'  => 'UZ',
				'alpha3'  => 'UZB',
				'numeric' => '860',
			),
			'Vanuatu' => array(
				'alpha2'  => 'VU',
				'alpha3'  => 'VUT',
				'numeric' => '548',
			),
			'Venezuela (Bolivarian Republic of)' => array(
				'alpha2'  => 'VE',
				'alpha3'  => 'VEN',
				'numeric' => '862',
			),
			'Vietnam' => array(
				'alpha2'  => 'VN',
				'alpha3'  => 'VNM',
				'numeric' => '704',
			),
			'Virgin Islands (British)' => array(
				'alpha2'  => 'VG',
				'alpha3'  => 'VGB',
				'numeric' => '092',
			),
			'Virgin Islands (U.S.)' => array(
				'alpha2'  => 'VI',
				'alpha3'  => 'VIR',
				'numeric' => '850',
			),
			'Wallis and Futuna' => array(
				'alpha2'  => 'WF',
				'alpha3'  => 'WLF',
				'numeric' => '876',
			),
			'Western Sahara' => array(
				'alpha2'  => 'EH',
				'alpha3'  => 'ESH',
				'numeric' => '732',
			),
			'Yemen' => array(
				'alpha2'  => 'YE',
				'alpha3'  => 'YEM',
				'numeric' => '887',
			),
			'Zambia' => array(
				'alpha2'  => 'ZM',
				'alpha3'  => 'ZMB',
				'numeric' => '894',
			),
			'Zimbabwe' => array(
				'alpha2'  => 'ZW',
				'alpha3'  => 'ZWE',
				'numeric' => '716',
			),
		);

		private static $codes = array();

		/*
		 * $key = 'alpha2', 'alpha3', or 'numeric'.
		 */
		public static function get( $key ) {

			if ( ! isset( self::$codes[ $key ] ) ) {

				self::$codes[ $key ] = array();

				foreach ( self::$countries as $name => $arr ) {

					if ( isset( $arr[ $key ] ) ) {	// 'alpha2', 'alpha3', or 'numeric'.

						self::$codes[ $key ][ $arr[ $key ] ] = $name;
					}
				}
			}

			return self::$codes[ $key ];
		}
	}
}

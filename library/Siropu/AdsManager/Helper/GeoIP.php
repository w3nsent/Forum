<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Helper_GeoIP
{
	public static $countryCode = null;

	public static function getCountryCode()
	{
		if (function_exists('geoip_country_code_by_name'))
		{
			return geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
		}

		$dir = dirname(dirname(__FILE__)) . '/ThirdParty/MaxMind/';
		require_once($dir . 'geoip.inc');
		return geoip_country_code_by_addr(geoip_open($dir . 'GeoIP.dat', GEOIP_STANDARD), $_SERVER['REMOTE_ADDR']);
	}
	public static function countryMatchesCriteria($criteria)
	{
		if (self::$countryCode === null)
		{
			self::$countryCode = self::getCountryCode();
		}

		if (self::$countryCode)
		{
			$isCountry    = isset($criteria['is_country']) ? array_flip($criteria['is_country']) : false;
			$isNotCountry = isset($criteria['is_not_country']) ? array_flip($criteria['is_not_country']) : false;

			if ($isCountry && isset($isCountry[self::$countryCode])
				|| $isNotCountry && !isset($isNotCountry[self::$countryCode]))
			{
				return true;
			}
		}
	}
	public static function getCountryList()
	{
		return array(
			'Africa' => array(
				'continent' => new XenForo_Phrase('siropu_ads_manager_geo_targeting_africa'),
				'countries' => array(
					'DZ' => 'Algeria',
					'AO' => 'Angola',
					'BJ' => 'Benin',
					'BW' => 'Botswana',
					'BF' => 'Burkina Faso',
					'BI' => 'Burundi',
					'CM' => 'Cameroon',
					'CV' => 'Cape Verde',
					'CF' => 'Central African Republic',
					'TD' => 'Chad',
					'KM' => 'Comoros',
					'CG' => 'Congo - Brazzaville',
					'CD' => 'Congo - Kinshasa',
					'CI' => 'Côte d’Ivoire',
					'DJ' => 'Djibouti',
					'EG' => 'Egypt',
					'GQ' => 'Equatorial Guinea',
					'ER' => 'Eritrea',
					'ET' => 'Ethiopia',
					'GA' => 'Gabon',
					'GM' => 'Gambia',
					'GH' => 'Ghana',
					'GN' => 'Guinea',
					'GW' => 'Guinea-Bissau',
					'KE' => 'Kenya',
					'LS' => 'Lesotho',
					'LR' => 'Liberia',
					'LY' => 'Libya',
					'MG' => 'Madagascar',
					'MW' => 'Malawi',
					'ML' => 'Mali',
					'MR' => 'Mauritania',
					'MU' => 'Mauritius',
					'YT' => 'Mayotte',
					'MA' => 'Morocco',
					'MZ' => 'Mozambique',
					'NA' => 'Namibia',
					'NE' => 'Niger',
					'NG' => 'Nigeria',
					'RW' => 'Rwanda',
					'RE' => 'Réunion',
					'SH' => 'Saint Helena',
					'SN' => 'Senegal',
					'SC' => 'Seychelles',
					'SL' => 'Sierra Leone',
					'SO' => 'Somalia',
					'ZA' => 'South Africa',
					'SD' => 'Sudan',
					'SZ' => 'Swaziland',
					'ST' => 'São Tomé and Príncipe',
					'TZ' => 'Tanzania',
					'TG' => 'Togo',
					'TN' => 'Tunisia',
					'UG' => 'Uganda',
					'EH' => 'Western Sahara',
					'ZM' => 'Zambia',
					'ZW' => 'Zimbabwe',
				)
			),
			'Asia' => array(
				'continent' => new XenForo_Phrase('siropu_ads_manager_geo_targeting_asia'),
				'countries' => array(
					'AF' => 'Afghanistan',
					'AM' => 'Armenia',
					'AZ' => 'Azerbaijan',
					'BH' => 'Bahrain',
					'BD' => 'Bangladesh',
					'BT' => 'Bhutan',
					'BN' => 'Brunei',
					'KH' => 'Cambodia',
					'CN' => 'China',
					'CY' => 'Cyprus',
					'GE' => 'Georgia',
					'HK' => 'Hong Kong SAR China',
					'IN' => 'India',
					'ID' => 'Indonesia',
					'IR' => 'Iran',
					'IQ' => 'Iraq',
					'IL' => 'Israel',
					'JP' => 'Japan',
					'JO' => 'Jordan',
					'KZ' => 'Kazakhstan',
					'KW' => 'Kuwait',
					'KG' => 'Kyrgyzstan',
					'LA' => 'Laos',
					'LB' => 'Lebanon',
					'MO' => 'Macau SAR China',
					'MY' => 'Malaysia',
					'MV' => 'Maldives',
					'MN' => 'Mongolia',
					'MM' => 'Myanmar [Burma]',
					'NP' => 'Nepal',
					'NT' => 'Neutral Zone',
					'KP' => 'North Korea',
					'OM' => 'Oman',
					'PK' => 'Pakistan',
					'PS' => 'Palestinian Territories',
					'YD' => 'People\'s Democratic Republic of Yemen',
					'PH' => 'Philippines',
					'QA' => 'Qatar',
					'SA' => 'Saudi Arabia',
					'SG' => 'Singapore',
					'KR' => 'South Korea',
					'LK' => 'Sri Lanka',
					'SY' => 'Syria',
					'TW' => 'Taiwan',
					'TJ' => 'Tajikistan',
					'TH' => 'Thailand',
					'TL' => 'Timor-Leste',
					'TR' => 'Turkey',
					'TM' => 'Turkmenistan',
					'AE' => 'United Arab Emirates',
					'UZ' => 'Uzbekistan',
					'VN' => 'Vietnam',
					'YE' => 'Yemen',
				)
			),
			'Europe' => array(
				'continent' => new XenForo_Phrase('siropu_ads_manager_geo_targeting_europe'),
				'countries' => array(
					'AL' => 'Albania',
					'AD' => 'Andorra',
					'AT' => 'Austria',
					'BY' => 'Belarus',
					'BE' => 'Belgium',
					'BA' => 'Bosnia and Herzegovina',
					'BG' => 'Bulgaria',
					'HR' => 'Croatia',
					'CY' => 'Cyprus',
					'CZ' => 'Czech Republic',
					'DK' => 'Denmark',
					'DD' => 'East Germany',
					'EE' => 'Estonia',
					'FO' => 'Faroe Islands',
					'FI' => 'Finland',
					'FR' => 'France',
					'DE' => 'Germany',
					'GI' => 'Gibraltar',
					'GR' => 'Greece',
					'GG' => 'Guernsey',
					'HU' => 'Hungary',
					'IS' => 'Iceland',
					'IE' => 'Ireland',
					'IM' => 'Isle of Man',
					'IT' => 'Italy',
					'JE' => 'Jersey',
					'LV' => 'Latvia',
					'LI' => 'Liechtenstein',
					'LT' => 'Lithuania',
					'LU' => 'Luxembourg',
					'MK' => 'Macedonia',
					'MT' => 'Malta',
					'FX' => 'Metropolitan France',
					'MD' => 'Moldova',
					'MC' => 'Monaco',
					'ME' => 'Montenegro',
					'NL' => 'Netherlands',
					'NO' => 'Norway',
					'PL' => 'Poland',
					'PT' => 'Portugal',
					'RO' => 'Romania',
					'RU' => 'Russia',
					'SM' => 'San Marino',
					'RS' => 'Serbia',
					'CS' => 'Serbia and Montenegro',
					'SK' => 'Slovakia',
					'SI' => 'Slovenia',
					'ES' => 'Spain',
					'SJ' => 'Svalbard and Jan Mayen',
					'SE' => 'Sweden',
					'CH' => 'Switzerland',
					'UA' => 'Ukraine',
					'SU' => 'Union of Soviet Socialist Republics',
					'GB' => 'United Kingdom',
					'VA' => 'Vatican City',
					'AX' => 'Åland Islands'
				)
			),
			'North America' => array(
				'continent' => new XenForo_Phrase('siropu_ads_manager_geo_targeting_north_america'),
				'countries' => array(
					'AG' => 'Antigua and Barbuda',
					'BS' => 'Bahamas',
					'BB' => 'Barbados',
					'BZ' => 'Belize',
					'CA' => 'Canada',
					'CR' => 'Costa Rica',
					'CU' => 'Cuba',
					'DM' => 'Dominica',
					'DO' => 'Dominican Republic',
					'SV' => 'El Salvador',
					'GD' => 'Grenada',
					'GT' => 'Guatemala',
					'HT' => 'Haiti',
					'HN' => 'Honduras',
					'JM' => 'Jamaica',
					'MX' => 'Mexico',
					'NI' => 'Nicaragua',
					'PA' => 'Panama',
					'KN' => 'Saint Kitts and Nevis',
					'LC' => 'Saint Lucia',
					'VC' => 'Saint Vincent and the Grenadines',
					'TT' => 'Trinidad and Tobago',
					'US' => 'United States'
				)
			),
			'South America' => array(
				'continent' => new XenForo_Phrase('siropu_ads_manager_geo_targeting_south_america'),
				'countries' => array(
					'AR' => 'Argentina',
					'BO' => 'Bolivia',
					'BR' => 'Brazil',
					'CL' => 'Chile',
					'CO' => 'Colombia',
					'EC' => 'Ecuador',
					'GY' => 'Guyana',
					'PY' => 'Paraguay',
					'PE' => 'Peru',
					'SR' => 'Suriname',
					'UY' => 'Uruguay',
					'VE' => 'Venezuela'
				)
			),
			'Oceania' => array(
				'continent' => new XenForo_Phrase('siropu_ads_manager_geo_targeting_oceania'),
				'countries' => array(
					'AS' => 'American Samoa',
					'AQ' => 'Antarctica',
					'AU' => 'Australia',
					'BV' => 'Bouvet Island',
					'IO' => 'British Indian Ocean Territory',
					'CX' => 'Christmas Island',
					'CC' => 'Cocos [Keeling] Islands',
					'CK' => 'Cook Islands',
					'FJ' => 'Fiji',
					'PF' => 'French Polynesia',
					'TF' => 'French Southern Territories',
					'GU' => 'Guam',
					'HM' => 'Heard Island and McDonald Islands',
					'KI' => 'Kiribati',
					'MH' => 'Marshall Islands',
					'FM' => 'Micronesia',
					'NR' => 'Nauru',
					'NC' => 'New Caledonia',
					'NZ' => 'New Zealand',
					'NU' => 'Niue',
					'NF' => 'Norfolk Island',
					'MP' => 'Northern Mariana Islands',
					'PW' => 'Palau',
					'PG' => 'Papua New Guinea',
					'PN' => 'Pitcairn Islands',
					'WS' => 'Samoa',
					'SB' => 'Solomon Islands',
					'GS' => 'South Georgia and the South Sandwich Islands',
					'TK' => 'Tokelau',
					'TO' => 'Tonga',
					'TV' => 'Tuvalu',
					'UM' => 'U.S. Minor Outlying Islands',
					'VU' => 'Vanuatu',
					'WF' => 'Wallis and Futuna'
				)
			)
		);
	}
}

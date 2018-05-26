<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Helper_Device
{
	public static function detect()
	{
		if (!class_exists('Mobile_Detect'))
		{
			require_once(dirname(dirname(__FILE__)) . '/ThirdParty/MobileDetect/Mobile_Detect.php');
		}

		return new Mobile_Detect();
	}
	public static function deviceMatchesCriteria($criteria)
	{
		if ($criteria)
		{
			foreach ($criteria as $device => $brands)
			{
				if ($device == self::getDeviceType())
				{
					if (is_array($brands))
					{
						$brandMatch = false;

						foreach ($brands as $brand)
						{
							if ($device == 'tablet' && !in_array($brand, array('iPad', 'Kindle', 'Hudl', 'GenericTablet')))
							{
								$brand = $brand . 'Tablet';
							}

							if (self::detect()->is($brand))
							{
								$brandMatch = true;
							}
						}

						return $brandMatch;
					}

					return true;
				}
			}
		}
	}
	public static function getDeviceType()
	{
		return (self::detect()->isMobile() ? (self::detect()->isTablet() ? 'tablet' : 'mobile') : 'desktop');
	}
	public static function getDeviceList()
	{
		$devices = array(
			'tablet' => array(
				'iPad',
				'Samsung',
				'Sony',
				'Lenovo',
				'AMPE',
				'Acer',
				'Advan',
				'Ainol',
				'AllFine',
				'AllView',
				'Archos',
				'Arnova',
				'Asus',
				'AudioSonic',
				'BlackBerry',
				'Blaupunkt',
				'Broncho',
				'Captiva',
				'Celkon',
				'ChangJia',
				'Coby',
				'Concorde',
				'Cresta',
				'Cube',
				'DPS',
				'Danew',
				'DanyTech',
				'Dell',
				'Digma',
				'ECS',
				'Eboda',
				'EssentielB',
				'Evolio',
				'FX2',
				'Fly',
				'Fujitsu',
				'GU',
				'Galapad',
				'GoClever',
				'HCL',
				'HP',
				'HTC',
				'Huawei',
				'Hudl',
				'IRU',
				'Iconbit',
				'Intenso',
				'JXD',
				'Jaytech',
				'Karbonn',
				'Kindle',
				'Kobo',
				'Kocaso',
				'LG',
				'Lava',
				'Leader',
				'MID',
				'MSI',
				'Mediatek',
				'Medion',
				'Megafon',
				'Mi',
				'Micromax',
				'Modecom',
				'Motorola',
				'Mpman',
				'Nabi',
				'Nec',
				'Nexo',
				'Nexus',
				'Nibiru',
				'Nook',
				'Odys',
				'Onda',
				'Overmax',
				'PROSCAN',
				'Pantech',
				'Philips',
				'Playstation',
				'PocketBook',
				'PointOfView',
				'Positivo',
				'Prestigio',
				'PyleAudio',
				'RockChip',
				'RossMoor',
				'SMiT',
				'Skk',
				'Storex',
				'Surface',
				'Teclast',
				'Tecno',
				'Texet',
				'Tolino',
				'Toshiba',
				'Trekstor',
				'Ubislate',
				'Versus',
				'Viewsonic',
				'Visture',
				'Vodafone',
				'Vonino',
				'Wolder',
				'Xoro',
				'YONES',
				'Yarvik',
				'Zync',
				'bq',
				'iJoy',
				'iMobile',
				'GenericTablet'
			),
			'mobile' => array(
				'iPhone',
				'Samsung',
				'Alcatel',
				'Amoi',
				'Asus',
				'BlackBerry',
				'Dell',
				'Fly',
				'HTC',
				'INQ',
				'LG',
				'Micromax',
				'Motorola',
				'Nexus',
				'Nintendo',
				'Palm',
				'Pantech',
				'SimValley',
				'Sony',
				'Vertu',
				'Wiko',
				'Wolfgang',
				'iMobile',
				'GenericPhone'
			)
		);

		return $devices;
	}
	public static function getDeviceListPhrase()
	{
		return array(
			'desktop' => new XenForo_Phrase('siropu_ads_manager_device_desktop'),
			'tablet'  => new XenForo_Phrase('siropu_ads_manager_device_tablet'),
			'mobile'  => new XenForo_Phrase('siropu_ads_manager_device_mobile')
		);
	}
}

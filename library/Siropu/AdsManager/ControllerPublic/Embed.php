<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerPublic_Embed extends XenForo_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
	{
		
	}
	public function actionIndex()
	{
		$search = array();

		if ($aId = $this->_input->filterSingle('aid', XenForo_Input::UINT))
		{
			$search['ad_id'] = $aId;
		}
		else if ($pId = $this->_input->filterSingle('pid', XenForo_Input::UINT))
		{
			$search['package_id'] = $pId;
		}

		$ads          = $this->getModelFromCache('Siropu_AdsManager_Model_Ads')->getActiveAdsForEmbed($search);
		$countExclude = 0;
		$viewParams   = array();

		foreach ($ads as $key => $val)
		{
			$type = $val['type'];
			$deviceCriteria = XenForo_Helper_Criteria::unserializeCriteria($val['device_criteria']);
			$geoIPCriteria  = XenForo_Helper_Criteria::unserializeCriteria($val['geoip_criteria']);

			if ((!empty($deviceCriteria) && !Siropu_AdsManager_Helper_Device::deviceMatchesCriteria($deviceCriteria))
				|| (!empty($geoIPCriteria) && !Siropu_AdsManager_Helper_GeoIP::countryMatchesCriteria($geoIPCriteria))
				|| ($val['hide_from_robots'] && XenForo_Visitor::getInstance()->robotId))
			{
				unset($ads[$key]);
			}
			else
			{
				if ($type == 'banner')
				{
					$bannerType = array();

					if ($val['banner'])
					{
						$bannerType[] = 1;
					}
					if ($val['code'])
					{
						$bannerType[] = 2;
					}

					shuffle($bannerType);
					$ads[$key]['banner_type'] = $bannerType[0];

					if ($extraBanners = @unserialize($val['banner_extra']))
					{
						array_unshift($extraBanners, $val['banner']);
						shuffle($extraBanners);
						$ads[$key]['banner'] = $extraBanners[0];
					}

					if ($this->_getHelperGeneral()->isSwf($val['banner']))
					{
						$ads[$key]['flash'] = true;
					}
				}

				if ($val['daily_stats'] || $val['click_stats'] || $val['ga_stats'])
				{
					$viewParams['stats'] = true;
				}

				if ($val['count_exclude'])
				{
					$countExclude += 1;
				}

				$ads[$key]['style']      = @unserialize($val['style']);
				$ads[$key]['attributes'] = $this->_getHelperGeneral()->getAdAttributes($ads[$key]);
				$ads[$key]['size']       = $this->_getHelperGeneral()->getAdWidthHeight($ads[$key]);
			}
		}

		if ($adCount = count($ads))
		{
			$ads = array_values($ads);

			if ($adCount > 1 && ($order = $ads[0]['ads_order']))
			{
				if ($order == 'random')
				{
					shuffle($ads);
				}
				else
				{
					usort($ads, 'Siropu_AdsManager_Helper_General::sortAdsByPackageCriteria');
				}

				if (in_array($type, array('code', 'banner', 'text')) && $ads[0]['js_interval'])
				{
					for ($i = 0; $i < $adCount; $i++)
					{
						$ads[$i]['display'] = ($i == 0) ? '' : ' style="display: none;"';
					}

					$viewParams['jsRotator'] = $ads[0]['js_interval'];
				}
			}

			if (($maxDisplay = $ads[0]['max_items_display']) && $adCount > $maxDisplay)
			{
				$ads = array_slice($ads, 0, $maxDisplay);
			}

			$viewParams['ads']            = $ads;
			$viewParams['adCount']        = $adCount;
			$viewParams['countExclude']   = $countExclude;
			$viewParams['bannerPath']     = $this->_getHelperGeneral()->getBannerPath('url');
			$viewParams['unitAttributes'] = $this->_getHelperGeneral()->getUnitAttributes('embed', $type, $viewParams);
			$viewParams['costPerList']    = $this->_getHelperGeneral()->getCostPerList();
		}

		$containerParams = array(
			'containerTemplate' => 'SIROPU_ADS_MANAGER_CONTAINER',
			'bodyClasses'       => 'samEmbed'
		);

		return $this->responseView('Siropu_AdsManager_ViewPublic_Raw', 'siropu_ads_manager_embed', $viewParams, $containerParams);
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
}
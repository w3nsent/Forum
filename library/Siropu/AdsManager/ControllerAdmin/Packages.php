<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Packages extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$search   = $this->_input->filterSingle('search', XenForo_Input::ARRAY_SIMPLE);
		$packages = $this->_getHelperGeneral()->setAvailableAdSlotCount($this->_getPackagesModel()->getAllPackages($search));

		$viewParams= array(
			'packages'     => $packages,
			'typePackages' => $this->_getHelperGeneral()->groupPackagesByType($packages),
			'costPerList'  => $this->_getHelperGeneral()->getCostPerList(),
			'typeList'     => $this->_getHelperGeneral()->getAdTypes(),
			'search'       => $search
		);

		return $this->responseView('', 'siropu_ads_manager_package_list', $viewParams);
	}
	public function actionAdd()
	{
		return $this->_getPackageAddEditResponse();
	}
	public function actionEdit()
	{
		$package = $this->_getPackageOrError();
		$package['style'] = unserialize($package['style']);

		return $this->_getPackageAddEditResponse(array(
			'package'      => $package,
			'positions'    => explode("\n", $package['positions']),
			'costList'     => $this->_getHelperGeneral()->prepareGroupListForDisplay($package['cost_list']),
			'discountList' => $this->_getHelperGeneral()->prepareGroupListForDisplay($package['discount']),
			'costPerList'  => $this->_getHelperGeneral()->getCostPerList(),
			'step'         => $this->_getHelperGeneral()->getSpinboxStep($package['cost_per'])
		));
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'type'                => XenForo_Input::STRING,
			'style'               => XenForo_Input::ARRAY_SIMPLE,
			'name'                => XenForo_Input::STRING,
			'description'         => XenForo_Input::STRING,
			'positions'           => XenForo_Input::STRING,
			'item_id'             => XenForo_Input::STRING,
			'cost_amount'         => XenForo_Input::FLOAT,
			'cost_list'           => XenForo_Input::STRING,
			'cost_currency'       => XenForo_Input::STRING,
			'cost_per'            => XenForo_Input::STRING,
			'min_purchase'        => XenForo_Input::UINT,
			'max_purchase'        => XenForo_Input::UINT,
			'max_items_allowed'   => XenForo_Input::UINT,
			'max_items_display'   => XenForo_Input::UINT,
			'ads_order'           => XenForo_Input::STRING,
			'count_ad_views'      => XenForo_Input::UINT,
			'count_ad_clicks'     => XenForo_Input::UINT,
			'daily_stats'         => XenForo_Input::UINT,
			'click_stats'         => XenForo_Input::UINT,
			'nofollow'            => XenForo_Input::UINT,
			'target_blank'        => XenForo_Input::UINT,
			'hide_from_robots'    => XenForo_Input::UINT,
			'js_rotator'          => XenForo_Input::UINT,
			'js_interval'         => XenForo_Input::UINT,
			'keyword_limit'       => XenForo_Input::UINT,
			'position_criteria'   => XenForo_Input::ARRAY_SIMPLE,
			'page_criteria'       => XenForo_Input::ARRAY_SIMPLE,
			'user_criteria'       => XenForo_Input::ARRAY_SIMPLE,
			'device_criteria'     => XenForo_Input::ARRAY_SIMPLE,
			'geoip_criteria'      => XenForo_Input::ARRAY_SIMPLE,
			'guidelines'          => XenForo_Input::STRING,
			'advertiser_criteria' => XenForo_Input::ARRAY_SIMPLE,
			'advertise_here'      => XenForo_Input::UINT,
			'display_order'       => XenForo_Input::UINT,
			'enabled'             => XenForo_Input::UINT
		));

		if ($dwData['type'] == 'featured' && !XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('XenResource'))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_resource_manager_required'));
		}

		$packageId = $this->_getPackageID();
		$package   = $packageId ? $this->_getPackagesModel()->getPackageById($packageId) : null;

		$dwData['style'] = serialize($dwData['style']);

		if ($positions = $this->_input->filterSingle('positions', XenForo_Input::ARRAY_SIMPLE))
		{
			$dwData['positions'] = implode("\n", $positions);
		}

		if ($dwData['cost_per'] == 'CPM' || $dwData['cost_per'] == 'CPC'
			|| $dwData['daily_stats'] || $dwData['click_stats'])
		{
			$dwData['count_ad_views']  = 1;
			$dwData['count_ad_clicks'] = 1;
		}

		$minPurchase = $dwData['min_purchase'];
		$maxPurchase = $dwData['max_purchase'];

		if ($minPurchase > $maxPurchase)
		{
			$dwData['min_purchase'] = $maxPurchase;
			$dwData['max_purchase'] = $minPurchase;
		}

		$previewPath = $this->_getHelperGeneral()->getBannerPath('absolute') . '/preview/';

		if ($upload = XenForo_Upload::getUploadedFile('preview'))
		{
			$extension = XenForo_Helper_File::getFileExtension($upload->getFileName());
			$fileName  = uniqid() . '.' . $extension;
			$filePath  = $previewPath . $fileName;

			if (XenForo_Helper_File::safeRename($upload->getTempFile(), $filePath))
			{
				$dwData['preview'] = $fileName;
			}

			if (!empty($package['preview']))
			{
				@unlink($previewPath . $package['preview']);
			}
		}

		if ($this->_input->filterSingle('remove_preview', XenForo_Input::UINT))
		{
			@unlink($previewPath . $package['preview']);
			$dwData['preview'] = '';
		}

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Packages');
		if ($packageId)
		{
			$dw->setExistingData($packageId);
		}

		if ($discount = $this->_input->filterSingle('discount', XenForo_Input::ARRAY_SIMPLE))
		{
			$dwData['discount'] = $this->_getHelperGeneral()->prepareGroupListForStorage($discount);
		}
		else
		{
			$dwData['discount'] = '';
		}

		if ($costList = $this->_input->filterSingle('cost_list', XenForo_Input::ARRAY_SIMPLE))
		{
			$dwData['cost_list'] = $this->_getHelperGeneral()->prepareGroupListForStorage($costList);
		}
		else
		{
			$dwData['cost_list'] = '';
		}
		$dw->bulkSet($dwData);
		$dw->save();

		if ($package)
		{
			$update = array(
				'positions'         => $dwData['positions'],
				'item_id'           => $dwData['item_id'],
				'count_views'       => $dwData['count_ad_views'],
				'count_clicks'      => $dwData['count_ad_clicks'],
				'daily_stats'       => $dwData['daily_stats'],
				'click_stats'       => $dwData['click_stats'],
				'nofollow'          => $dwData['nofollow'],
				'target_blank'      => $dwData['target_blank'],
				'hide_from_robots'  => $dwData['hide_from_robots'],
				'keyword_limit'     => $dwData['keyword_limit'],
				'position_criteria' => serialize($dwData['position_criteria']),
				'page_criteria'     => serialize(XenForo_Helper_Criteria::prepareCriteriaForSave($dwData['page_criteria'])),
				'user_criteria'     => serialize(XenForo_Helper_Criteria::prepareCriteriaForSave($dwData['user_criteria'])),
				'date_last_change'  => time()
			);

			$bypass = $this->_input->filterSingle('bypass', XenForo_Input::UINT);

			if (!$dwData['enabled'] || $bypass)
			{
				$update['geoip_criteria']  = serialize($dwData['geoip_criteria']);
				$update['device_criteria'] = serialize($dwData['device_criteria']);
			}

			$this->_getAdsModel()->updateAdsByPackageId($update, $packageId, $bypass);
			$this->_getHelperGeneral()->refreshActiveAdsCache();
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('ad-packages') . $this->getLastHash($dw->get('package_id'))
		);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			if ($this->_input->filterSingle('delete_ads', XenForo_Input::UINT))
			{
				$this->_getAdsModel()->deleteAdsByPackageId($this->_getPackageID());
			}

			return $this->_deleteData(
				'Siropu_AdsManager_DataWriter_Packages', 'package_id',
				XenForo_Link::buildAdminLink('ad-packages')
			);
		}
		else
		{
			$viewParams = array(
				'package' => $this->_getPackageOrError(),
				'ads'     => $this->_getAdsModel()->getAllAds('', array('package_id' => $this->_getPackageID()))
			);

			return $this->responseView('', 'siropu_ads_manager_package_delete_confirm', $viewParams);
		}
	}
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getPackagesModel()->getAllPackages(),
			'Siropu_AdsManager_DataWriter_Packages',
			'ad-packages',
			'enabled');
	}
	public function actionStats()
	{
		$viewParams = array(
			'package'    => $this->_getPackageOrError(),
			'stats'      => $this->_getPackagesModel()->getPackageStats($this->_getPackageID()),
			'statusList' => $this->_getHelperGeneral()->getStatusList(),
			'ads'        => $this->_getAdsModel()->getAllAds('', array('package_id' => $this->_getPackageID()), array('field' => 'ctr', 'dir' => 'desc'))
		);

		return $this->responseView('', 'siropu_ads_manager_package_stats', $viewParams);
	}
	public function actionEmbed()
	{
		$viewParams = array(
			'embedUrl' => XenForo_Link::buildPublicLink('canonical:sam-embed', '', array('pid' => $this->_getPackageID())),
			'sizeList' => $this->_getHelperGeneral()->getAdSizes()
		);

		return $this->responseView('', 'siropu_ads_manager_embed', $viewParams);
	}
	protected function _getPackageAddEditResponse($viewParams = array())
	{
		$positionCriteria
			= $pageCriteria
			= $userCriteria
			= $deviceCriteria
			= $geoIPCriteria
			= $advertiserCriteria
			= array();

		if (isset($viewParams['package']))
		{
			$positionCriteria   = $viewParams['package']['position_criteria'];
			$pageCriteria       = $viewParams['package']['page_criteria'];
			$userCriteria       = $viewParams['package']['user_criteria'];
			$deviceCriteria     = $viewParams['package']['device_criteria'];
			$geoIPCriteria      = $viewParams['package']['geoip_criteria'];
			$advertiserCriteria = $viewParams['package']['advertiser_criteria'];
		}

		$positionList    = $this->_getPositionsModel()->getAllPositions();
		$positionCatList = $this->_getPositionsCateoriesModel()->getAllCategories();

		$viewParams = array_merge($viewParams, array(
			'type'               => $this->_input->filterSingle('type', XenForo_Input::STRING),
			'typeList'           => $this->_getHelperGeneral()->getAdTypes(),
			'sizeList'           => $this->_getHelperGeneral()->getAdSizes(),
			'positionList'       => $this->_getHelperGeneral()->groupPositionsByCategory($positionList, $positionCatList, true),
			'hiddenPosCount'     => $this->_getHelperGeneral()->getHiddenPositionsCount($positionList),
			'currencyList'       => $this->_getHelperGeneral()->getCurrencyList('', true),
			'prefCurrency'       => $this->_getOptions()->siropu_ads_manager_currency,
			'positionCriteria'   => XenForo_Helper_Criteria::unserializeCriteria($positionCriteria),
			'pageCriteria'       => XenForo_Helper_Criteria::prepareCriteriaForSelection($pageCriteria),
			'pageCriteriaData'   => XenForo_Helper_Criteria::getDataForPageCriteriaSelection(),
			'userCriteria'       => XenForo_Helper_Criteria::prepareCriteriaForSelection($userCriteria),
			'userCriteriaData'   => XenForo_Helper_Criteria::getDataForUserCriteriaSelection(),
			'deviceCriteria'     => XenForo_Helper_Criteria::unserializeCriteria($deviceCriteria),
			'deviceCriteria'     => XenForo_Helper_Criteria::unserializeCriteria($deviceCriteria),
			'geoIPCriteria'      => XenForo_Helper_Criteria::unserializeCriteria($geoIPCriteria),
			'advertiserCriteria' => XenForo_Helper_Criteria::unserializeCriteria($advertiserCriteria),
			'deviceList'         => $this->_getHelperDevice()->getDeviceList(),
			'countryList'        => $this->_getHelperGeoIP()->getCountryList(),
			'previewPath'        => $this->_getHelperGeneral()->getBannerPath() . '/preview/'
		));

		return $this->responseView('', 'siropu_ads_manager_package_edit', $viewParams);
	}
	protected function _getPackageOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getPackageID();
		}

		if ($info = $this->_getPackagesModel()->getPackageById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_ads_manager_package_not_found'), 404));
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getPositionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _getPositionsCateoriesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PositionsCategories');
	}
	protected function _getPackagesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Packages');
	}
	protected function _getForumsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Forums');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getHelperDevice()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_Device');
	}
	protected function _getHelperGeoIP()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_GeoIP');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getPackageID()
	{
		return $this->_input->filterSingle('package_id', XenForo_Input::UINT);
	}
}

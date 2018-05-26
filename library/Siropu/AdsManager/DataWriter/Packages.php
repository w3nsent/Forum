<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_Packages extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_packages' => array(
				'package_id'          => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'type'                => array('type' => self::TYPE_STRING),
				'name'                => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'required'  => true),
				'description'         => array('type' => self::TYPE_STRING, 'default' => ''),
				'positions'           => array('type' => self::TYPE_STRING, 'default' => ''),
				'item_id'             => array('type' => self::TYPE_STRING, 'default' => ''),
				'cost_amount'         => array('type' => self::TYPE_FLOAT, 'default' => 0.00),
				'cost_list'           => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'cost_currency'       => array('type' => self::TYPE_STRING, 'maxLength' => 3, 'default' => 'USD'),
				'cost_per'            => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'min_purchase'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'max_purchase'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'discount'            => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'advertise_here'      => array('type' => self::TYPE_UINT, 'default' => 0),
				'style'               => array('type' => self::TYPE_STRING, 'default' => ''),
				'max_items_allowed'   => array('type' => self::TYPE_UINT, 'default' => 0),
				'max_items_display'   => array('type' => self::TYPE_UINT, 'default' => 0),
				'ads_order'           => array('type' => self::TYPE_STRING, 'default' => 'random'),
				'count_ad_views'      => array('type' => self::TYPE_UINT, 'default' => 0),
				'count_ad_clicks'     => array('type' => self::TYPE_UINT, 'default' => 0),
				'daily_stats'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'click_stats'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'nofollow'            => array('type' => self::TYPE_UINT, 'default' => 1),
				'target_blank'        => array('type' => self::TYPE_UINT, 'default' => 1),
				'hide_from_robots'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'js_rotator'          => array('type' => self::TYPE_UINT, 'default' => 1),
				'js_interval'         => array('type' => self::TYPE_UINT, 'default' => 5),
				'keyword_limit'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'position_criteria'   => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_prepareCriteria')),
				'page_criteria'       => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_verifyCriteria')),
				'user_criteria'       => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_verifyCriteria')),
				'device_criteria'     => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_prepareCriteria')),
				'geoip_criteria'      => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_prepareCriteria')),
				'guidelines'          => array('type' => self::TYPE_STRING, 'default' => ''),
				'advertiser_criteria' => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_prepareCriteria')),
				'preview'             => array('type' => self::TYPE_STRING, 'default' => ''),
				'display_order'       => array('type' => self::TYPE_UINT, 'maxLength' => 255, 'default' => 1),
				'enabled'             => array('type' => self::TYPE_UINT, 'default' => 0),
			)
		);
	}
	protected function _verifyCriteria(&$criteria)
	{
		$criteriaFiltered = XenForo_Helper_Criteria::prepareCriteriaForSave($criteria);
		$criteria = serialize($criteriaFiltered);
		return true;
	}
	protected function _prepareCriteria(&$criteria)
	{
		if (is_array($criteria))
		{
			$criteria = serialize($criteria);
		}
		return true;
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'package_id'))
		{
			return array('xf_siropu_ads_manager_packages' => $this->_getPackagesModel()->getPackageById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'package_id = ' . $this->_db->quote($this->getExisting('package_id'));
	}
	protected function _postSave()
	{
		if ($this->get('type') == 'sticky')
		{
			if ($this->get('enabled') && $this->get('advertise_here'))
			{
				XenForo_Application::setSimpleCacheData('advertiseHereStickyPackage', $this->getMergedData());
			}
			else
			{
				XenForo_Application::setSimpleCacheData('advertiseHereStickyPackage', '');
			}
		}
	}
	protected function _postDelete()
	{
		if ($placeholder = $this->_getAdsModel()->packagePlaceholderExists($this->get('package_id')))
		{
			$this->_getAdsModel()->deleteAd($placeholder['ad_id']);
		}

		XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
	}
	protected function _getPackagesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Packages');
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
}

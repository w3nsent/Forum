<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_Ads extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_ads' => array(
				'ad_id'               => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'package_id'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_id'             => array('type' => self::TYPE_UINT, 'default' => 0),
				'username'            => array('type' => self::TYPE_STRING, 'default' => ''),
				'name'                => array('type' => self::TYPE_STRING, 'maxLength' => 255),
				'type'                => array('type' => self::TYPE_STRING, 'default' => 'code'),
				'positions'           => array('type' => self::TYPE_STRING, 'default' => ''),
				'item_id'             => array('type' => self::TYPE_STRING, 'default' => ''),
				'code'                => array('type' => self::TYPE_STRING, 'default' => ''),
				'backup'              => array('type' => self::TYPE_STRING, 'default' => ''),
				'url'                 => array('type' => self::TYPE_STRING, 'default' => ''),
				'banner'              => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'banner_url'          => array('type' => self::TYPE_STRING, 'default' => ''),
				'banner_extra'        => array('type' => self::TYPE_STRING, 'default' => ''),
				'title'               => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'description'         => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'items'               => array('type' => self::TYPE_STRING, 'default' => ''),
				'purchase'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'extend'              => array('type' => self::TYPE_UINT, 'default' => 0),
				'date_start'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'date_end'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'count_views'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'view_limit'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'count_clicks'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'click_limit'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'daily_stats'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'click_stats'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'ga_stats'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'nofollow'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'target_blank'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'hide_from_robots'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'ad_order'            => array('type' => self::TYPE_UINT, 'default' => 1),
				'priority'            => array('type' => self::TYPE_UINT, 'default' => 1),
				'display_after'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'hide_after'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'inherit_settings'    => array('type' => self::TYPE_UINT, 'default' => 1),
				'is_placeholder'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'count_exclude'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'keyword_limit'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'position_criteria'   => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_prepareCriteria')),
				'page_criteria'       => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_verifyCriteria')),
				'user_criteria'       => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_verifyCriteria')),
				'device_criteria'     => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_prepareCriteria')),
				'geoip_criteria'      => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_prepareCriteria')),
				'notes'               => array('type' => self::TYPE_STRING, 'default' => ''),
				'date_created'        => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'date_active'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'date_last_change'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'date_last_active'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'view_count'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'click_count'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'ctr'                 => array('type' => self::TYPE_FLOAT, 'default' => 0),
				'email_notifications' => array('type' => self::TYPE_UINT, 'default' => 1),
				'alert_notifications' => array('type' => self::TYPE_UINT, 'default' => 1),
				'subscription'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'reject_reason'       => array('type' => self::TYPE_STRING, 'default' => ''),
				'notice_sent'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'pending_transaction' => array('type' => self::TYPE_UINT, 'default' => 0),
				'status_old'          => array('type' => self::TYPE_STRING, 'default' => ''),
				'status'              => array('type' => self::TYPE_STRING, 'default' => 'Pending'),
			)
		);
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'ad_id'))
		{
			return array('xf_siropu_ads_manager_ads' => $this->_getAdsModel()->getAdById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'ad_id = ' . $this->_db->quote($this->getExisting('ad_id'));
	}
	protected function _prepareCriteria(&$criteria)
	{
		if (is_array($criteria))
		{
			$criteria = serialize($criteria);
		}
		return true;
	}
	protected function _verifyCriteria(&$criteria)
	{
		$criteriaFiltered = XenForo_Helper_Criteria::prepareCriteriaForSave($criteria);
		$criteria = serialize($criteriaFiltered);
		return true;
	}
	protected function _preSave()
	{
		$this->set('date_last_change', time());
	}
	protected function _postSave()
	{
		if ($this->get('package_id')
			&& !$this->get('is_placeholder')
			&& ($this->isInsert() || $this->isUpdate() && $this->isChanged('status'))
			&& ($placeholder = $this->_getAdsModel()->packagePlaceholderExists($this->get('package_id'))))
		{
			$status = 'Inactive';

			if (!$this->_getAdsModel()->getAllAds('Active', array('package_id' => $this->get('package_id'), 'is_placeholder' => 0)))
			{
				$status = 'Active';
			}

			$this->_getAdsModel()->changeAdStatus($placeholder['ad_id'], $status);
		}

		if ($this->isInsert() || !$this->isChanged('view_count') && !$this->isChanged('click_count'))
		{
			XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
		}
	}
	protected function _postDelete()
	{
		XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
}

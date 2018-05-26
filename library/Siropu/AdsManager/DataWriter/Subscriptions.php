<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_Subscriptions extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_subscriptions' => array(
				'subscription_id'   => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'subscr_id'         => array('type' => self::TYPE_STRING, 'default' => ''),
				'package_id'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'ad_id'             => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_id'           => array('type' => self::TYPE_UINT, 'default' => 0),
				'username'          => array('type' => self::TYPE_STRING, 'default' => ''),
				'amount'            => array('type' => self::TYPE_FLOAT, 'default' => 0.00),
				'currency'          => array('type' => self::TYPE_STRING, 'maxLength' => 3, 'default' => 'USD'),
				'subscr_date'       => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'subscr_method'     => array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => ''),
				'last_payment_date' => array('type' => self::TYPE_UINT, 'default' => 0),
				'status'            => array('type' => self::TYPE_STRING, 'default' => 'Active')
			)
		);
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'subscription_id'))
		{
			return array('xf_siropu_ads_manager_subscriptions' => $this->_getSubscriptionsModel()->getSubscriptionById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'subscription_id = ' . $this->_db->quote($this->getExisting('subscription_id'));
	}
	protected function _getSubscriptionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Subscriptions');
	}
}
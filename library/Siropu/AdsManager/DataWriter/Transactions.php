<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_Transactions extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_transactions' => array(
				'transaction_id'   => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'ad_id'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_id'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'username'         => array('type' => self::TYPE_STRING, 'default' => ''),
				'cost_amount'      => array('type' => self::TYPE_FLOAT, 'default' => 0.00),
				'cost_amount_btc'  => array('type' => self::TYPE_FLOAT, 'default' => 0.00),
				'cost_currency'    => array('type' => self::TYPE_STRING, 'maxLength' => 3, 'default' => 'USD'),
				'discount_amount'  => array('type' => self::TYPE_FLOAT, 'default' => 0.00),
				'discount_percent' => array('type' => self::TYPE_UINT, 'default' => 0),
				'promo_code'       => array('type' => self::TYPE_STRING, 'default' => ''),
				'payment_method'   => array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => ''),
				'payment_txn_id'   => array('type' => self::TYPE_STRING, 'default' => ''),
				'download'         => array('type' => self::TYPE_STRING, 'default' => ''),
				'date_generated'   => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'date_completed'   => array('type' => self::TYPE_UINT, 'default' => 0),
				'status'           => array('type' => self::TYPE_STRING, 'default' => 'Pending')
			)
		);
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'transaction_id'))
		{
			return array('xf_siropu_ads_manager_transactions' => $this->_getTransactionsModel()->getTransactionById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'transaction_id = ' . $this->_db->quote($this->getExisting('transaction_id'));
	}
	protected function _postSave()
	{
		if ($this->get('status') == 'Completed')
		{
			$this->_getUserModel()->changeUserGroups($this->get('user_id'));
		}
	}
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getUserModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_User');
	}
}

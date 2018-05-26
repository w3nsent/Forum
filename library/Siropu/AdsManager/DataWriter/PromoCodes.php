<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_PromoCodes extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_promo_codes' => array(
				'code_id'               => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'code'                  => array(
					'type'         => self::TYPE_STRING,
					'maxlength'    => 255,
					'required'     => true,
					'verification' => array('$this', '_verifyUniqueness')
				),
				'description'           => array('type' => self::TYPE_STRING, 'default' => ''),
				'value'                 => array('type' => self::TYPE_FLOAT, 'default' => 0),
				'type'                  => array('type' => self::TYPE_STRING, 'default' => ''),
				'packages'              => array('type' => self::TYPE_STRING, 'default' => ''),
				'min_transaction_value' => array('type' => self::TYPE_FLOAT, 'default' => 0),
				'date_expire'           => array('type' => self::TYPE_UINT, 'default' => 0),
				'usage_limit_total'     => array('type' => self::TYPE_UINT, 'default' => 0),
				'usage_limit_user'      => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_criteria'         => array(
					'type'         => self::TYPE_UNKNOWN,
					'required'     => true,
					'verification' => array('$this', '_verifyCriteria')
				),
				'usage_count'           => array('type' => self::TYPE_UINT, 'default' => 0),
				'date_created'          => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'enabled'               => array('type' => self::TYPE_UINT, 'default' => 0)
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'code_id'))
		{
			return array('xf_siropu_ads_manager_promo_codes' => $this->_getPromoCodesModel()->getPromoCodeById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'code_id = ' . $this->_db->quote($this->getExisting('code_id'));
	}
	protected function _verifyUniqueness($code)
	{
		if ($this->isInsert() && $this->_getPromoCodesModel()->getPromoCodeByCode($code))
		{
			$this->error(new XenForo_Phrase('siropu_ads_manager_promo_code_must_be_unique'), 'code');
			return false;
		}

		return true;
	}
	protected function _verifyCriteria(&$criteria)
	{
		$criteriaFiltered = XenForo_Helper_Criteria::prepareCriteriaForSave($criteria);
		$criteria = serialize($criteriaFiltered);
		return true;
	}
	protected function _getPromoCodesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PromoCodes');
	}
}
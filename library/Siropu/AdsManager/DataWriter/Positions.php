<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_Positions extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_positions' => array(
				'position_id'    => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'hook'           => array(
					'type'          => self::TYPE_STRING,
					'maxLength'     => 255,
					'required'      => true,
					'verification'  => array('$this', '_verifyPositionHookUniqueness'),
					'requiredError' => 'siropu_ads_manager_position_hook_required',
				),
				'name'           => array(
					'type'          => self::TYPE_STRING,
					'maxLength'     => 255,
					'required'      => true,
					'requiredError' => 'siropu_ads_manager_position_name_required'
				),
				'description'    => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'cat_id'         => array('type' => self::TYPE_UINT, 'default' => 0),
				'display_order'  => array('type' => self::TYPE_UINT, 'default' => 0),
				'visible'        => array('type' => self::TYPE_UINT, 'default' => 1),
			)
		);
	}
	protected function _verifyPositionHookUniqueness(&$hook)
	{
		if ($this->isInsert() && $this->_getModel()->getPositionByHook($hook))
		{
			$this->error(new XenForo_Phrase('siropu_ads_manager_hook_must_be_unique'), 'hook');
			return false;
		}

		return true;
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'position_id'))
		{
			return array('xf_siropu_ads_manager_positions' => $this->_getModel()->getPositionById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'position_id = ' . $this->_db->quote($this->getExisting('position_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _postSave()
	{
		Siropu_AdsManager_Helper_General::refreshAdPositionsCache();
	}
	protected function _postDelete()
	{
		Siropu_AdsManager_Helper_General::refreshAdPositionsCache();
	}
}
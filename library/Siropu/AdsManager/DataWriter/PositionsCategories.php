<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_DataWriter_PositionsCategories extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_ads_manager_positions_categories' => array(
				'cat_id'        => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title'         => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'required' => true),
				'display_order' => array('type' => self::TYPE_UINT, 'default' => 0),
			)
		);
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'cat_id'))
		{
			return array('xf_siropu_ads_manager_positions_categories' => $this->_getModel()->getCategoryById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'cat_id = ' . $this->_db->quote($this->getExisting('cat_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PositionsCategories');
	}
	protected function _postDelete()
	{
		Siropu_AdsManager_Helper_General::refreshAdPositionsCache();
	}
}
<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Images extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_images' => array(
				'image_id'      => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'image_user_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'image_name'    => array('type' => self::TYPE_STRING, 'required' => true),
				'image_date'    => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time)
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'image_id'))
		{
			return array('xf_siropu_chat_images' => $this->_getModel()->getImageById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'image_id = ' . $this->_db->quote($this->getExisting('image_id'));
	}
	protected function _postDelete()
	{
		Siropu_Chat_HelperUpload::deleteImage($this->get('image_name'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model_Images');
	}
}
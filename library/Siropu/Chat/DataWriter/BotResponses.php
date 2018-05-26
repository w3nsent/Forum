<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_BotResponses extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_bot_responses' => array(
				'response_id'          => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'response_bot_name'    => array('type' => self::TYPE_STRING, 'default' => ''),
				'response_keyword'     => array('type' => self::TYPE_STRING, 'required' => true),
				'response_message'     => array('type' => self::TYPE_STRING, 'required' => true),
				'response_description' => array('type' => self::TYPE_STRING, 'default' => ''),
				'response_rooms'       => array('type' => self::TYPE_STRING, 'default' => ''),
				'response_user_groups' => array('type' => self::TYPE_STRING, 'default' => ''),
				'response_type'        => array('type' => self::TYPE_UINT, 'default' => 1),
				'response_settings'    => array('type' => self::TYPE_STRING, 'default' => ''),
				'response_last'        => array('type' => self::TYPE_STRING, 'default' => ''),
				'response_enabled'     => array('type' => self::TYPE_UINT, 'default' => 1)
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'response_id'))
		{
			return array('xf_siropu_chat_bot_responses' => $this->_getModel()->getResponseById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'response_id = ' . $this->_db->quote($this->getExisting('response_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
}
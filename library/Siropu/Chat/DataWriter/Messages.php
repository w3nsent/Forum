<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Messages extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_messages' => array(
				'message_id'         => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'message_room_id'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'message_user_id'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'message_bot_name'   => array('type' => self::TYPE_STRING, 'default' => ''),
				'message_recipients' => array('type' => self::TYPE_STRING, 'default' => ''),
				'message_tagged'     => array('type' => self::TYPE_STRING, 'default' => ''),
				'message_text'       => array('type' => self::TYPE_STRING, 'required' => true),
				'message_type'       => array('type' => self::TYPE_STRING, 'default' => 'chat'),
				'message_date'       => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time)
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'message_id'))
		{
			return array('xf_siropu_chat_messages' => $this->_getModel()->getMessageById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'message_id = ' . $this->_db->quote($this->getExisting('message_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
}
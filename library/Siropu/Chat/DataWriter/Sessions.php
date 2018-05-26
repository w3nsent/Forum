<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Sessions extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_sessions' => array(
				'user_id'            => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_room_id'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_rooms'         => array('type' => self::TYPE_STRING, 'default' => ''),
				'user_settings'      => array('type' => self::TYPE_STRING, 'default' => ''),
				'user_status'        => array('type' => self::TYPE_STRING, 'default' => ''),
				'user_is_banned'     => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_is_muted'      => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_message_count' => array('type' => self::TYPE_UINT, 'default' => 0),
				'user_last_activity' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time)
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'user_id'))
		{
			return array('xf_siropu_chat_sessions' => $this->_getModel()->getSession($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'user_id = ' . $this->_db->quote($this->getExisting('user_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
}
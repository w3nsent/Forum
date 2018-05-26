<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Rooms extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_rooms' => array(
				'room_id'            => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'room_user_id'       => array('type' => self::TYPE_UINT, 'default' => 0),
				'room_name'          => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'required' => true),
				'room_description'   => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'room_password'      => array('type' => self::TYPE_STRING, 'maxLength' => 255, 'default' => ''),
				'room_permissions'   => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification' => array('$this', '_prepareData')),
				'room_locked'        => array('type' => self::TYPE_UINT, 'default' => 0),
				'room_auto_delete'   => array('type' => self::TYPE_UINT, 'default' => 1),
				'room_date'          => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'room_last_activity' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time)
		));
	}
	protected function _prepareData(&$data)
	{
		$data = serialize($data ? $data : array('in_group_ids' => array()));
		return true;
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'room_id'))
		{
			return array('xf_siropu_chat_rooms' => $this->_getModel()->getRoomById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'room_id = ' . $this->_db->quote($this->getExisting('room_id'));
	}
	protected function _postSave()
	{
		Siropu_Chat_Helper::refreshRoomsCache();
	}
	protected function _postDelete()
	{
		Siropu_Chat_Helper::refreshRoomsCache();
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
}
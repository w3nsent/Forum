<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Bans extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_bans' => array(
				'ban_id'      => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'ban_user_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'ban_room_id' => array('type' => self::TYPE_INT, 'default' => 0),
				'ban_start'   => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'ban_end'     => array('type' => self::TYPE_UINT, 'default' => 0),
				'ban_author'  => array('type' => self::TYPE_UINT, 'required' => true),
				'ban_reason'  => array('type' => self::TYPE_STRING, 'default' => ''),
				'ban_type'    => array('type' => self::TYPE_STRING, 'default' => 'ban')
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'ban_id'))
		{
			return array('xf_siropu_chat_bans' => $this->_getModel()->getBanById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'ban_id = ' . $this->_db->quote($this->getExisting('ban_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
}
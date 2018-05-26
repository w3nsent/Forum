<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Reports extends Xenforo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_siropu_chat_reports' => array(
				'report_id'              => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'report_message_id'      => array('type' => self::TYPE_UINT, 'required' => true),
				'report_message_user_id' => array('type' => self::TYPE_UINT, 'required' => true),
				'report_message_text'    => array('type' => self::TYPE_STRING, 'required' => true),
				'report_message_date'    => array('type' => self::TYPE_UINT, 'required' => true),
				'report_room_id'         => array('type' => self::TYPE_UINT, 'required' => true),
				'report_user_id'         => array('type' => self::TYPE_UINT, 'required' => true),
				'report_reason'          => array('type' => self::TYPE_STRING, 'default' => ''),
				'report_date'            => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'report_state'           => array('type' => self::TYPE_STRING, 'default' => 'open'),
				'report_update_date'     => array('type' => self::TYPE_UINT, 'default' => 0),
				'report_update_user_id'  => array('type' => self::TYPE_UINT, 'default' => 0),
				'report_update_username' => array('type' => self::TYPE_STRING, 'default' => '')
		));
	}
	protected function _getExistingData($data)
	{
		if ($id = $this->_getExistingPrimaryKey($data, 'report_id'))
		{
			return array('xf_siropu_chat_reports' => $this->_getModel()->getReportById($id));
		}
	}
	protected function _getUpdateCondition($tableName)
	{
		return 'report_id = ' . $this->_db->quote($this->getExisting('report_id'));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
}
<?php

class BS_ColorStatus_XenForo_DataWriter_User extends XFCP_BS_ColorStatus_XenForo_DataWriter_User
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_user']['status_color'] = array('type' => self::TYPE_STRING, 'required' => false, 'maxLength' => 10);
		return $fields;
	}

	protected function _preSave()
	{
		if (isset($GLOBALS['statusColor']))
		{
			$this->set('status_color', $GLOBALS['statusColor']);
			unset($GLOBALS['statusColor']);
		}
		return parent::_preSave();
	}
}
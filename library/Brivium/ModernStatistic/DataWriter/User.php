<?php

/**
* Data writer for users.
*
* @package XenForo_User
*/
class Brivium_ModernStatistic_DataWriter_User extends XFCP_Brivium_ModernStatistic_DataWriter_User
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_user']['brms_statistic_perferences'] = array('type' => self::TYPE_SERIALIZED, 'default' => '');
		return $fields;
	}
}

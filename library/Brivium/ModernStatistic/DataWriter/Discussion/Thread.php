<?php

/**
* Data writer for threads.
*
* @package XenForo_Discussion
*/
class Brivium_ModernStatistic_DataWriter_Discussion_Thread extends XFCP_Brivium_ModernStatistic_DataWriter_Discussion_Thread
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_thread']['brms_promote'] 	= array('type' => self::TYPE_BOOLEAN, 'default' => 0);
		$fields['xf_thread']['brms_promote_date'] 	= array('type' => self::TYPE_UINT,   'default' => 0);
		return $fields;
	}

	protected function _discussionPreSave()
	{
		$preSave = parent::_discussionPreSave();
		if (isset($GLOBALS['BRMS_ControllerPublic'])) {
			$controller = $GLOBALS['BRMS_ControllerPublic'];

			$input = $controller->getInput()->filter(array(
				'brms_promote' => XenForo_Input::UINT,
				'brms_promote_date' => XenForo_Input::UINT,
			));

			if(!$input['brms_promote'])
			{
				$input['brms_promote'] = 0;
				$input['brms_promote_date'] = 0;
			}

			$this->bulkSet($input);
			unset($GLOBALS['BRMS_ControllerPublic']);
		}
		return $preSave;
	}
}

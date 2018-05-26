<?php
class Brivium_AdvancedReputationSystem_DataWriter_PresetDelete extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_brivium_preset_delete' => array(
				'preset_delete_id' 		=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'reason' 			=> array('type' => self::TYPE_STRING, 'required' => true),
				'message' 			=> array('type' => self::TYPE_STRING, 'required' => true),
				'post_date' 		=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'active' 			=> array('type' => self::TYPE_BOOLEAN, 'default' => 1)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (! $presetDeleteId = $this->_getExistingPrimaryKey( $data, 'preset_delete_id'))
		{
			return false;
		}
		return array(
			'xf_brivium_preset_delete' => $this->_getPresetDeleteModel()->getPresetDeleteById( $presetDeleteId)
		);
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'preset_delete_id = ' . $this->_db->quote( $this->getExisting( 'preset_delete_id'));
	}

	protected function _preSave()
	{
	}

	protected function _postSave()
	{
	}

	protected function _preDelete()
	{
	}

	protected function _postDelete()
	{
	}

	protected function _getPresetDeleteModel()
	{
		return $this->getModelFromCache( 'Brivium_AdvancedReputationSystem_Model_PresetDelete');
	}
}
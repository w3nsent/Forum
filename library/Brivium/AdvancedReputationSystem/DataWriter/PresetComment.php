<?php
class Brivium_AdvancedReputationSystem_DataWriter_PresetComment extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_brivium_preset_reputation_message' => array(
				'preset_id' 		=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title' 			=> array('type' => self::TYPE_STRING, 'required' => true),
				'message' 			=> array('type' => self::TYPE_STRING, 'required' => true),
				'post_date' 		=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'active' 			=> array('type' => self::TYPE_BOOLEAN, 'default' => 1)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (! $presetCommentId = $this->_getExistingPrimaryKey( $data, 'preset_id'))
		{
			return false;
		}
		return array(
			'xf_brivium_preset_reputation_message' => $this->_getPresetCommentModel()->getPresetCommentById( $presetCommentId)
		);
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'preset_id = ' . $this->_db->quote( $this->getExisting( 'preset_id'));
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

	protected function _getPresetCommentModel()
	{
		return $this->getModelFromCache( 'Brivium_AdvancedReputationSystem_Model_PresetComment');
	}
}
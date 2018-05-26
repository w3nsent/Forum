<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_DataWriter_Forum extends XFCP_Brivium_AdvancedReputationSystem_DataWriter_Forum
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		
		$fields['xf_forum']['reputation_count_entrance'] = array('type' => self::TYPE_UINT, 'default' => 0);
		
		return $fields;
	}
	
	protected function _preSave()
	{
		if(isset($GLOBALS['BRARS_reputation_count_entrance']))
		{
			$this->set('reputation_count_entrance', $GLOBALS['BRARS_reputation_count_entrance']);
		}
		return parent::_preSave();
	}
}
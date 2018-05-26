<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_DataWriter_User extends XFCP_Brivium_AdvancedReputationSystem_DataWriter_User
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_user'] += array(
			'reputation_count' => array('type' => self::TYPE_INT, 'default' => 0),
			'brars_post_count' => array('type' => self::TYPE_UINT, 'default' => 0),
			'brars_rated_count' => array('type' => self::TYPE_UINT, 'default' => 0),
			'brars_rated_negative_count' => array('type' => self::TYPE_UINT, 'default' => 0)
		);
		
		return $fields;
	}
	
	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		parent::_postSave();
		
		$options = XenForo_Application::getOptions();
		// Give rep points on registrations
		if ($options->registrationpoints != 0)
		{
			if (($options->get( 'registrationSetup', 'emailConfirmation') && $this->isChanged( 'user_state') and $this->getExisting( 'user_state') == 'email_confirm') || ($this->isInsert() and ! ($options->get( 'registrationSetup', 'emailConfirmation'))))
			{
				$userId = $this->get( 'user_id');
				$this->_db->query( "
				    UPDATE `xf_user`
				    SET reputation_count = reputation_count + ?
				    WHERE user_id = ?
			        ", array(
					$options->registrationpoints,
					$userId
				));
				
				XenForo_Model_Alert::alert($userId, 0, '', 'user', $userId, 'reputation_welcome', array('points'=>$options->registrationpoints));
			}
		}
	}
}
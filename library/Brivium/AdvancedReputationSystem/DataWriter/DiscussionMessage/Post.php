<?php

//######################## Reputation System By Brivium ###########################

class Brivium_AdvancedReputationSystem_DataWriter_DiscussionMessage_Post extends XFCP_Brivium_AdvancedReputationSystem_DataWriter_DiscussionMessage_Post
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		
		$fields['xf_post'] += array(
			'reputations'            => array('type' => self::TYPE_INT,   'default' => 0),
			'br_reputations_count'   		=> array('type' => self::TYPE_UINT,   'default' => 0),
			'br_first_reputation_date'   	=> array('type' => self::TYPE_UINT,   'default' => 0),
			'br_last_reputations_date'   	=> array('type' => self::TYPE_UINT,   'default' => 0),
			'br_lastest_reputation_ids'   	=> array('type' => self::TYPE_STRING,   'default' => ''),
		);
		return $fields;
	}
	
	protected function _messagePostSave()
	{
		$reputationModel = $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
		$reputationPoint = $this->get('reputations');
		if($this->isChanged('message_state') && $this->get('message_state') == 'visible')
		{
			$reputationModel->recountPointsToUserReceived($reputationPoint, $this->get('user_id'));
		}elseif ($this->isChanged('message_state') && $this->getExisting('message_state') == 'visible')
		{
			$reputationModel->recountPointsToUserReceived(-$reputationPoint, $this->get('user_id'));
		}
		return parent::_messagePostSave();
	}
	
	protected function _messagePostDelete()
	{
		$reputationPoint = $this->get('reputations');
		$reputationModel = $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
		if ($this->get('message_state') == 'visible')
		{
			$this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('brivium_post_comment', $this->get('post_id'));
		}
	
		if(empty($GLOBALS['brMergePosts']))
		{
			$db = $this->_db;
			$giverUserIds = $reputationModel->getGiverUserIdsByPostId($this->get('post_id'));
			$db->delete('xf_reputation', 'post_id = '.$db->quote( $this->get('post_id')));
			$reputationModel->rebuildReputationsToUsers($giverUserIds);
			if ($this->get('message_state') == 'visible')
			{
				$reputationModel->recountPointsToUserReceived(-$reputationPoint, $this->get('user_id'));
			}
		}
		return parent::_messagePostDelete();
	}
}
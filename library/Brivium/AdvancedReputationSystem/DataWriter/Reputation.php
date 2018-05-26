<?php

//######################## Reputation System By Brivium ###########################

class Brivium_AdvancedReputationSystem_DataWriter_Reputation extends XenForo_DataWriter 
{
	const DATA_DELETE_REASON = 'deleteReason';
	const NO_SEND_ALERT = 'noSendAlertToReceiver';
	const IMPORT_TABLE = 'importTable';
	
    /**
    * Gets the fields that are defined for the table. See parent for explanation.
    *
    * @return array
    */
    protected function _getFields() 
	{
		return array(
			'xf_reputation' => array(
				'reputation_id' 	=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'post_id'       	=> array('type' => self::TYPE_UINT, 'required' => true),
				'receiver_user_id'  => array('type' => self::TYPE_UINT, 'required' => true),
				'receiver_username' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50),
				'giver_user_id' 	=> array('type' => self::TYPE_UINT, 'default' => 0),
				'giver_username' 	=> array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50),
				'reputation_date' 	=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'points' 			=> array('type' => self::TYPE_INT, 'required' => true),
				'is_anonymous' 		=> array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'comment' 			=> array('type' => self::TYPE_STRING, 'default' => ''),
				'reputation_state'  => array('type' => self::TYPE_STRING, 'default' => 'visible',
											'allowedValues' => array('visible', 'moderated', 'deleted')
											),
				'email_address'  	=> array('type' => self::TYPE_STRING, 'maxLength' => 120, 'default' => ''
													, 'verification' => array('$this', '_verifyEmail')
													, 'requiredError' => 'please_enter_valid_email'
											),
				'ip_address'  		=> array('type' => self::TYPE_UINT, 'default' => 0),
				'encode'  			=> array('type' => self::TYPE_STRING, 'maxLength' => 36, 'default' => '')
			)
		);
	}

	/**
    * Gets the actual existing data out of data that was passed in. See parent for explanation.
    *
    * @param mixed
    *
    * @see XenForo_DataWriter::_getExistingData()
    *
    * @return array|false
    */
	protected function _getExistingData($data) 
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'reputation_id')) {
			return false;
		}

		return array('xf_reputation' => $this->_getReputationModel()->getReputationById($id));
	}

	/**
    * Gets SQL condition to update the existing record.
    * 
    * @see XenForo_DataWriter::_getUpdateCondition() 
    *
    * @return string
    */
	protected function _getUpdateCondition($tableName)
    {
       return 'reputation_id = ' . $this->_db->quote($this->getExisting('reputation_id'));
    }

    protected function _verifyEmail(&$email)
    {
    	$giverUserId = $this->get('giver_user_id');
    	
    	if(!empty($giverUserId))
    	{
    		return true;
    	}elseif(empty($email))
    	{
    		return false;
    	}
    
    	if (!Zend_Validate::is($email, 'EmailAddress'))
    	{
    		$this->error(new XenForo_Phrase('please_enter_valid_email'), 'email');
    		return false;
    	}

    	if (XenForo_Helper_Email::isEmailBanned($email))
    	{
    		$this->error(new XenForo_Phrase('email_address_you_entered_has_been_banned_by_administrator'), 'email');
    		return false;
    	}
    
    	return true;
    }
    
	/**
	 * Pre-save handling.
	 */
    protected function _preSave()
    {
		if ($this->isInsert())
		{
			$this->insertIpAddress();
		}
		$this->_checkMessageValidity();
    }
    
    protected function _checkMessageValidity()
    {
    	$message = $this->get('comment');
    	$importTable = $this->getExtraData(self::IMPORT_TABLE);
    	if(empty($importTable) && utf8_strlen($message)>140)
    	{
    		$this->error(new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => 140)), 'comment');
    	
    	}
    	
    	if(empty($importTable))
    	{
    		if($this->_getPostModel()->mustReputationComment($this->get('points')))
    		{
    			$minchar = XenForo_Application::getOptions()->minchar;
    			if(utf8_strlen($message)<$minchar && $minchar > 0)
    			{
    				$this->error(new XenForo_Phrase('BRARS_x_y_min_chars_comment_required', array('minchar' => $minchar)));
    			}elseif(utf8_strlen($message)<=0)
    			{
    				$this->error(new XenForo_Phrase('BRARS_comment_required'),'comment');
    			}
    		}
    		 
    		if(utf8_strlen($message) > 0)
    		{
    			$this->spamWord($message);
    		}
    	}
    }
    
    protected function spamWord($message)
    {
    	$visitor = XenForo_Visitor::getInstance();
    	if (!$visitor['is_admin'] AND !$visitor['is_moderator'] AND !$visitor['is_staff'])
    	{
    		$options =  XenForo_Application::getOptions();
    		if(!empty( $options->spamwords))
    		{
    			$spamwords = explode(",", $options->spamwords);
    			$pattern = '#('.implode('|', $spamwords).')#i';
    			if(preg_match($pattern, $message, $content))
    			{
    				$this->error(new XenForo_Phrase('stop_bad_spam_words', array('badwordsfilter' => $options->spamwords)));
    			}
    		}
    	}
    }

    protected function insertIpAddress()
    {
    	$ipAddress = (isset ( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false);
    	
    	if (is_string ( $ipAddress ) && strpos ( $ipAddress, '.' ))
    	{
    		$ipAddress = ip2long ( $ipAddress );
    	} else
    	{
    		$ipAddress = 0;
    	}
    	
    	$this->set('ip_address', $ipAddress);
    }
	
	/**
	 * Post-save handling.
	 */
	protected function _postSave() 
	{
		$this->_updateDeletionLog();
	    //Updated users reputation points. Add positive points and substract the negative ones
		$isSendAlert = false;
		$reputationModel = $this->_getReputationModel();
		
		$points = $this->get('points');
		
		$postThread = $this->_getPostModel()->getPostById($this->get('post_id'),array('join'=> XenForo_Model_Post::FETCH_THREAD));
		
		$isVisible = ($postThread['message_state']=='visible' && $postThread['discussion_state'] == 'visible');
		$importTable = $this->getExtraData(self::IMPORT_TABLE);
		
		if(empty($importTable))
		{
			if ($this->isInsert() && $this->get('reputation_state') == 'visible')
			{
				$reputationModel->rebuildReputationsToPosts($this->get('post_id'));
				$isSendAlert = true;
				if($isVisible)
				{
					$reputationModel->recountPointsToUserReceived($points, $this->get('receiver_user_id'));
				}
					
				$reputationModel->rebuildReputationToUserGiver($this->get('giver_user_id'), 1, $points>=0?0:1);
			}elseif($this->isUpdate() && $this->isChanged('reputation_state') && $this->get('reputation_state') == 'visible')
			{
				$isSendAlert = true;
					
				$reputationModel->rebuildReputationsToPosts($this->get('post_id'));
				if($isVisible)
				{
					$reputationModel->recountPointsToUserReceived($points, $this->get('receiver_user_id'));
				}
				$reputationModel->rebuildReputationToUserGiver($this->get('giver_user_id'), 1, $points>=0?0:1);
			}elseif ($this->isUpdate() && $this->isChanged('reputation_state') && $this->getExisting('reputation_state') == 'visible')
			{
				$reputationModel->rebuildReputationsToPosts($this->get('post_id'));
				$this->getModelFromCache( 'XenForo_Model_Alert')->deleteAlerts( 'brivium_reputation_system', $this->get( 'reputation_id'));
					
				if($isVisible)
				{
					$reputationModel->recountPointsToUserReceived(-$points, $this->get('receiver_user_id'));
				}
					
				$reputationModel->rebuildReputationToUserGiver($this->get('giver_user_id'), -1, $points>=0?0:-1);
			}elseif($this->isUpdate() && $this->isChanged('points') && $this->get('reputation_state') == 'visible')
			{
				$existingPoints = $this->getExisting('points');
					
				if(($existingPoints>=0 && $points>=0) || ($existingPoints<0 && $points<0))
				{
					$ratedNegativeCount = 0;
				}elseif($existingPoints>$points)
				{
					$ratedNegativeCount = 1;
				}else {
					$ratedNegativeCount = -1;
				}
					
				$reputationModel->rebuildReputationsToPosts($this->get('post_id'));
				if($isVisible)
				{
					$reputationModel->recountPointsToUserReceived($points -  $existingPoints, $this->get('receiver_user_id'));
				}
					
				$reputationModel->rebuildReputationToUserGiver($this->get('giver_user_id'), 0, $ratedNegativeCount);
			}
		}
		
		$options = XenForo_Application::getOptions();
		$moderation = $options->max_neg_moderation;
		if(!empty($moderation) && $this->get('reputation_state') == 'visible')
		{
			$postId = $this->get('post_id');
			$postModel = $this->_getPostModel();
			$post = $postModel->getPostById($postId);
			if($post['reputations'] <=  $moderation)
			{
				if(!empty($post['position']))
				{
					$postDw = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
					$postDw->setExistingData($post['post_id']);
					$postDw->set('message_state', 'moderated');
					$postDw->save();
					$redirect = XenForo_Link::buildPublicLink('threads', $post);
					$message = new XenForo_Phrase('BRARS_the_post_sent_to_moderation_queue');
				}else 
				{
					$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
					$threadDw->setExistingData($post['thread_id']);
					$threadDw->set('discussion_state', 'moderated');
					$threadDw->save();
					$thread = $threadDw->getMergedData();
					$redirect = XenForo_Link::buildPublicLink('forums', $thread);
					$message = new XenForo_Phrase('BRARS_the_thread_sent_to_moderation_queue');
				}
				
				$GLOBALS['BRARS_reponses'] = array(
					'redirect' => $redirect,
					'message' => $message
				);
				
				$isSendAlert = false;
			}
		}
		if(empty($importTable) && $isSendAlert && XenForo_Model_Alert::userReceivesAlert(array('user_id' => $this->get('receiver_user_id')), 'reputation', 'new') && $isVisible)
		{
				if($this->get('is_anonymous'))
				{
					$userId = 0;
					$username = new XenForo_Phrase('BRARS_someone');
				}else {
					$userId = $this->get('giver_user_id');
					$username = $this->get('giver_username');
				}
					
				XenForo_Model_Alert::alert($this->get('receiver_user_id'),
					$userId,
					$username,
					'brivium_reputation_system',
					$this->get('reputation_id'),
					'new_reputation'
				);
		}
	}
	
	/**
	 * Post-delete handling.
	 */
	protected function _postDelete()
	{
		if($this->get('reputation_state') == 'visible')
		{
			$this->getModelFromCache( 'XenForo_Model_Alert')->deleteAlerts( 'brivium_reputation_system', $this->get( 'reputation_id'));
			
			$reputationModel = $this->_getReputationModel();
			$points = $this->get('points');
			$reputationModel->recountPointsToUserReceived(-$points, $this->get('receiver_user_id'));
			if($this->get('giver_user_id')>0)
			{
				$reputationModel->rebuildReputationToUserGiver($this->get('giver_user_id'), 1, $points>=0?0:-1);
			}
			
			$reputationModel->rebuildReputationsToPosts($this->get('post_id'));
		}
	}

	protected function _updateDeletionLog()
	{
		if (!$this->isChanged('reputation_state'))
		{
			return;
		}
	
		if ($this->get('reputation_state') == 'deleted')
		{
			$reason = $this->getExtraData(self::DATA_DELETE_REASON);
			$this->getModelFromCache('XenForo_Model_DeletionLog')->logDeletion(
					'brivium_reputation_system', $this->get('reputation_id'), $reason
			);
		}
		
		if ($this->getExisting('reputation_state') == 'deleted')
		{
			$this->getModelFromCache('XenForo_Model_DeletionLog')->removeDeletionLog(
					'brivium_reputation_system', $this->get('reputation_id')
			);
		}
	}
	
	/**
	 * @return Brivium_AdvancedReputationSystem_Model_Reputation
	 */
	protected function _getReputationModel() 
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
	}
	
	protected function _getPostModel()
	{
		return $this->getModelFromCache('XenForo_Model_Post');
	}
}
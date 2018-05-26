<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_Thread extends XFCP_Brivium_AdvancedReputationSystem_Model_Thread
{
    public function canViewThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{    
	    $parent = parent::canViewThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		
	    $visitor = XenForo_Visitor::getInstance();
		
	    //Set up the rep points requirement to enter forums and exlude groups from the limit as well
	   if(isset($forum['reputation_count_entrance']) AND $forum['reputation_count_entrance'] != 0)
	   {
	      //Guests must register
	      if (!$visitor['user_id'])
	   	  {
	   	  	$errorPhraseKey = 'must_be_registered';
	   	  	return false;
	   	  }
		  
	      if($visitor['reputation_count'] <= $forum['reputation_count_entrance'] AND !XenForo_Visitor::getInstance()->hasPermission('reputation', 'exlude_forum_rep'))
	      {
	         $errorPhraseKey = array('num_rep_forum', 'username' => $visitor['username'],
			 'repforum' => $forum['reputation_count_entrance'],
			 'repcount' => $visitor['reputation_count']);
	          return false;
	      }
	   }
		
		return $parent;
	}
	
	//Anonymous rep alert
	/* public function sendAnonymousAlert($alertType, $postId, array $poststarters, XenForo_Visitor $visitor = null)
	{
		$visitor = XenForo_Visitor::getInstance(); 
		
		if (!$visitor)
		{
			$visitor = XenForo_Visitor::getInstance();
		}

		if (!empty($poststarters))
		{
			foreach ($poststarters AS $poststarter)
			{
				$user = $this->_getUserModel()->getUserByName($poststarter);
				
				if (XenForo_Model_Alert::userReceivesAlert($user, 'post', $alertType))
				{
					XenForo_Model_Alert::alert($user['user_id'],
							$visitor['user_id'], $visitor['username'],
							'post', 
							$postId,
							$alertType
					);
				}
			}
		}
		return false;
	} */
	
	//Delete rep alert
	/* public function sendDeleteAlert($alertType, $postId, array $poststarters, XenForo_Visitor $visitor = null)
	{
		$visitor = XenForo_Visitor::getInstance(); 
		
		if (!$visitor)
		{
			$visitor = XenForo_Visitor::getInstance();
		}

		if (!empty($poststarters))
		{
			foreach ($poststarters AS $poststarter)
			{
				$user = $this->_getUserModel()->getUserByName($poststarter);
				
				if (XenForo_Model_Alert::userReceivesAlert($user, 'post', $alertType))
				{
					XenForo_Model_Alert::alert($user['user_id'],
							$visitor['user_id'], $visitor['username'],
							'post', 
							$postId,
							$alertType
					);
				}
			}
		}
		return false;
	} */
	
	public function modifyThreadUserPostCount($threadId, $userId, $modifyValue)
	{
		$this->brUpdatePostCountToUserById($userId);
		return parent::modifyThreadUserPostCount($threadId, $userId, $modifyValue);
	}
	
	public function brUpdatePostCountToUserById($userId)
	{
		$db = $this->_getDb();
		$postCounts = $db->fetchOne("
				SELECT count(post.post_id) as brars_post_count
				FROM xf_post AS post
				LEFT JOIN `xf_thread` AS thread
					ON (thread.thread_id = post.thread_id)
				WHERE (post.message_state = ?) AND (thread.discussion_state = ?) AND (post.user_id = ?)
				", array('visible', 'visible', $userId));
		$bind = array(
			'brars_post_count' => 	$postCounts
		);
		$db->update('xf_user', $bind, 'user_id = '.$userId);
	}
	
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}	
		    
 }
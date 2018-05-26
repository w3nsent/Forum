<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_Post extends XFCP_Brivium_AdvancedReputationSystem_Model_Post
{
	public function getPostInsertMessageState(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
	    $parent = parent::getPostInsertMessageState($thread, $forum, $nodePermissions, $viewingUser);
		
	    $options = XenForo_Application::get('options');
	    $visitor = XenForo_Visitor::getInstance();
		$fidmoderation = explode( ",", $options->fidmoderation);
		
		if ($options->max_neg_points_moderation != 0 AND $visitor['reputation_count'] <= $options->max_neg_points_moderation AND in_array($forum['node_id'], $fidmoderation))
		{
	       return 'moderated';
		}
		
		return $parent;
    }
    
    public function mergePosts(array $posts, array $threads, $targetPostId, $newMessage, $options = array())
    {
    	$GLOBALS['brMergePosts'] = true;
    
    	$isMergePost = parent::mergePosts($posts, $threads, $targetPostId, $newMessage, $options);
    
    	if(!empty($isMergePost))
    	{
    		$targetPost = !empty($posts[$targetPostId])?$posts[$targetPostId]:array();
    		if(empty($targetPost))
    		{
    			return $isMergePost;
    		}
    		$receiverUserId = $targetPost['user_id'];
    		
    		unset($posts[$targetPostId]);
    		
    		$receiverUsers = array();
    		foreach ($posts as $post)
    		{
    			if(!isset($receiverUsers[$post['user_id']]['brars_post_count']))
    			{
    				$receiverUsers[$post['user_id']]['brars_post_count'] = -1;
    			}else{
    				$receiverUsers[$post['user_id']]['brars_post_count'] += -1;
    			}
    		
    			if($post['user_id'] != $receiverUserId)
    			{
    				if(!isset($receiverUsers[$post['user_id']]['reputation_count']))
    				{
    					$receiverUsers[$post['user_id']]['reputation_count'] = -$post['reputations'];
    				}else{
    					$receiverUsers[$post['user_id']]['reputation_count'] -= $post['reputations'];
    				}
    				 
    				if(!isset($receiverUsers[$receiverUserId]['reputation_count']))
    				{
    					$receiverUsers[$receiverUserId]['reputation_count'] = +$post['reputations'];
    				}else{
    					$receiverUsers[$receiverUserId]['reputation_count'] += $post['reputations'];
    				}
    			}
    		}
    		
    		if(empty($receiverUsers))
    		{
    			return $isMergePost;
    		}
    		$db = $this->_getDb();
    		foreach ($receiverUsers as $userId => $bind)
    		{
    			if(empty($userId))
    			{
    				continue;
    			}
    			$writeUser = XenForo_DataWriter::create('XenForo_DataWriter_User');
    			$writeUser->setExistingData($userId);
    			
    			$reputationPoints = !empty($bind['reputation_count'])?$bind['reputation_count']:0;
    			$reputationPoints += $writeUser->getExisting('reputation_count');
    			$postCount = !empty($bind['brars_post_count'])?$bind['brars_post_count']:0;
    			$postCount += $writeUser->getExisting('brars_post_count');
    			
    			$writeUser->set('reputation_count', $reputationPoints);
    			$writeUser->set('brars_post_count', max(0, $postCount));
    			$writeUser->save();
    			unset($writeUser);
    		}
    		$bindReputation = array(
    			'post_id' => $targetPostId,
    			'receiver_user_id' => $targetPost['user_id'],
    			'receiver_username' => $targetPost['username']
    		);
    		$db->update('xf_reputation', $bindReputation, 'post_id IN (' . $db->quote(array_keys($posts)) . ')');
    		$this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation')->rebuildReputationsToPosts($targetPostId);
    	}
    
    	return $isMergePost;
    }
    
    protected function _moveOrCopyPosts($action, array $posts, array $sourceThreads, array $targetThread, array $options = array())
    {
    	if($action == 'copy' && !empty($posts) && is_array($posts))
    	{
    		$bind = array(
    			'reputations'            => 0,
		    	'br_reputations_count'   		=>0,
		    	'br_first_reputation_date'   	=> 0,
		    	'br_last_reputations_date'   	=> 0,
		    	'br_lastest_reputation_ids'   	=> '',
    		);
    		foreach ($posts as &$post)
    		{
    			foreach ($bind as $field => $val)
    			{
    				if(isset($post[$field]))
    				{
    					$post[$field] = $val;
    				}
    			}
    		}
    	}
    	return parent::_moveOrCopyPosts($action, $posts, $sourceThreads, $targetThread, $options);
    }
    
    public function preparePost(array $post, array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
    {
    	$post = parent::preparePost( $post, $thread, $forum, $nodePermissions, $viewingUser);
    
    	$post['canViewReptations'] = $this->canViewReputationOnPost($post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    	$post['canGiveReputation'] = $this->canGiveReputation( $post, $thread, $forum, $null, $nodePermissions, $viewingUser);
    	$post['canGiveReputationAnonymously'] = $this->canGiveReputationAnonymously($viewingUser);
    	$post['canGiveNegativePoints'] = $this->canGiveNegativePoints($viewingUser);
    	$post['maxReputationPoints'] = $this->giveMaxReputationPoints($viewingUser);
    	$post['minReputationPoints'] = $this->giveMinReputationPoints($viewingUser);
    	$post['mustReputationComment'] = $this->mustReputationComment(0, $viewingUser);
    	
    	$reputationModel = $this->_getReputationModel();
    	$post['canUseStarInterface'] = $reputationModel->canUseStarInterface();
    	
    	if($post['br_reputations_count'] > 0 && $post['canUseStarInterface'])
    	{
	    	$post['reputationStar']  = $reputationModel->convertPointsToStar($post['reputations'] / $post['br_reputations_count'], true);
    	}
    	
    	return $post;
    }
    
    public function canUseReputation(array $forum = array(), &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	$options = XenForo_Application::getOptions();
    
    	if(empty($viewingUser['user_id']) && empty($options->BRARS_guestRating))
    	{
    		return false;
    	}
    	
    	if(!empty($forum['node_id']) && !empty($options->excludedrepsforums) && in_array($forum['node_id'], $options->excludedrepsforums))
    	{
    		return false;
    	}
    
    	if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'can_use_rep'))
    	{
    		return false;
    	}
    
    	return true;
    
    }
    
    public function canGiveReputation(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	if(!$this->canUseReputation($forum, $errorPhraseKey, $nodePermissions, $viewingUser))
    	{
    		return false;
    	}
    	
    	if ($post['message_state'] != 'visible' || empty($post['user_id']))
    	{
    		return false;
    	}
    	
    	if ($post['user_id'] == $viewingUser['user_id'])
    	{
    		$errorPhraseKey = 'BRARS_no_reputation_self';
    		return false;
    	}
    	
    	if (!$this->getModelFromCache('XenForo_Model_Post')->canViewPost($post, $thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser))
    	{
    		return false;
    	}
    	
    	return true;
    }
    
    public function canGiveReputationAnonymously(array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	
    	return XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'rateAnonymous');
    }
    
    public function giveMaxReputationPoints(array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	$maxPoints = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'maxrep_points');
    	if($maxPoints == -1)
    	{
    		return 9999;
    	}
    	
    	return max(1, $maxPoints);
    }
    
    public function giveMinReputationPoints(array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	
    	if(!$this->canGiveNegativePoints($viewingUser))
    	{
    		return 0;
    	}
    	
    	return -1 * $this->giveMaxReputationPoints($viewingUser);
    }
    
    public function canGiveNegativePoints(array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    
    	if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'give_neg_rep'))
    	{
    		return true;
    	}
    
    	return false;
    }
    
    public function mustReputationComment($points = 0, array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    
    	if(empty($viewingUser['user_id']))
    	{
    		return true;
    	}
    	
    	if($viewingUser['is_admin'] || $viewingUser['is_moderator'] || $viewingUser['is_staff'])
    	{
    		return false;
    	}
    	
    	$options = XenForo_Application::getOptions();
    	if(!$options->commentrequired && $points >=0)
    	{
    		return false;
    	}
    	
    	if($points<0 && XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_freeTextNegative'))
    	{
    		return false;
    	}
    	 
    	return true;
    }
    
    public function canViewReputationOnPost(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	
    	if(!$this->canUseReputation($forum, $errorPhraseKey, $nodePermissions, $viewingUser))
    	{
    		return false;
    	}
    	
    	if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'viewAllReps'))
    	{
    		return true;
    	}
    	
    	if($viewingUser['user_id'] == $post['user_id'] && XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'viewOwnReps'))
    	{
    		return true;
    	}
    	
    	return false;
    }
    
    public function canManagerModeration(array $viewingUser = null)
    {
    	$this->standardizeViewingUserReference($viewingUser);
    	 
    	if((empty($viewingUser['is_admin']) && empty($viewingUser['is_moderator'])) || !$this->canUseReputation())
    	{
    		return false;
    	}
    	$canViewReputaionOnPost = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'viewAllReps');
    	$canEditAnyReputation = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_editAnyRep');
    	$canDeleteAnyReputation = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_deleteAnyRep');
    	$canApproveUnapprove = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_approveUnapprove');
    	if(!$canViewReputaionOnPost || !$canEditAnyReputation || !$canDeleteAnyReputation || !$canApproveUnapprove)
    	{
    		return false;
    	}
    	
    	return true;
    }
    
    protected function _getReputationModel()
    {
    	return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
    }
}
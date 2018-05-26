<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_Reputation extends XenForo_Model 
{
	const FETCH_REPUTATION_RECEIVER = 0x01;
	const FETCH_REPUTATION_GIVER = 0x02;
	const FETCH_POST = 0x04;
	const FETCH_DELETION_LOG = 0x08;
	
	private $_lastNumberReceviedNotNegative = null;
	
	public function getReputationById($reputationId, array $fetchOptions = array())
	{
		$sqlClauses = $this->prepareReputationFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow( '
				SELECT reputation.*
					' . $sqlClauses['selectFields'] . '
				FROM `xf_reputation` AS reputation
					' . $sqlClauses['joinTables'] . '
				WHERE  reputation.reputation_id = ?
			', array($reputationId));
	}
	
	public function getReputations(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareReputationConditions($conditions, $fetchOptions);
		
		$orderClause = $this->prepareReputationOrderOptions($fetchOptions, 'reputation.reputation_date DESC');

		$joinOptions = $this->prepareReputationFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults("
				SELECT reputation.*
				$joinOptions[selectFields]
				FROM `xf_reputation` AS reputation
				$joinOptions[joinTables]
				WHERE $whereConditions
				$orderClause
				", $limitOptions['limit'], $limitOptions['offset']
		), 'reputation_id');
	}
	public function countReputations(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->prepareReputationConditions($conditions, $fetchOptions);
		$sqlClauses = $this->prepareReputationFetchOptions($fetchOptions);
	
		return $this->_getDb()->fetchOne('
				SELECT COUNT(*)
				FROM `xf_reputation` AS reputation
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
			');
	}
	
	//Get all rep points and Reputations
	public function prepareReputationConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
	
		$keyArray = array(
				'reputation_id', 'post_id', 'receiver_user_id', 'receiver_username', 'giver_user_id', 'giver_username', 'email_address', 'ip_address'
		);
		foreach ($keyArray as $intField)
		{
			if (empty($conditions[$intField]))
				continue;
				
			if (is_array($conditions[$intField]))
			{
				$sqlConditions[] = "reputation.$intField IN (" . $db->quote($conditions[$intField]) . ")";
			} else {
				$sqlConditions[] = "reputation.$intField = " . $db->quote($conditions[$intField]);
			}
		}
		
		if (!empty($conditions['giver_username_like']))
		{
			if (is_array($conditions['giver_username_like']))
			{
				$sqlConditions[] = 'reputation.giver_username LIKE ' . XenForo_Db::quoteLike($conditions['giver_username_like'][0], $conditions['giver_username_like'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'reputation.giver_username LIKE ' . XenForo_Db::quoteLike($conditions['giver_username_like'], 'lr', $db);
			}
		}
		
		if (!empty($conditions['receiver_username_like']))
		{
			if (is_array($conditions['receiver_username_like']))
			{
				$sqlConditions[] = 'reputation.receiver_username LIKE ' . XenForo_Db::quoteLike($conditions['receiver_username_like'][0], $conditions['receiver_username_like'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'reputation.receiver_username LIKE ' . XenForo_Db::quoteLike($conditions['receiver_username_like'], 'lr', $db);
			}
		}
	
		if (!empty($conditions['reputation_date']) && is_array($conditions['reputation_date']))
		{
			list($operator, $cutOff) = $conditions['reputation_date'];
	
			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "reputation.reputation_date $operator " . $db->quote($cutOff);
		}
	
		if (!empty($conditions['reputation_state']))
		{
			if (is_array($conditions['reputation_state']))
			{
				$sqlConditions[] = 'reputation.reputation_state IN (' . $db->quote($conditions['reputation_state']) . ')';
			}
			else
			{
				$sqlConditions[] = 'reputation.reputation_state = ' . $db->quote($conditions['reputation_state']);
			}
		}
	
		if (! empty( $conditions['start']))
		{
			$sqlConditions[] = 'reputation.reputation_date >= ' . $db->quote( $conditions['start']);
		}
	
		if (! empty( $conditions['end']))
		{
			$sqlConditions[] = 'reputation.reputation_date <= ' . $db->quote( $conditions['end']);
		}
	
		return $this->getConditionsForClause($sqlConditions);
	}
	
	//@param array $fetchOptions Collection of options related to fetching
	public function prepareReputationFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
	
		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_REPUTATION_GIVER)
			{
				$selectFields .= '
					, giver.*, if(giver.username is not null, giver.username, reputation.giver_username) as giver_username';
				$joinTables .= '
					LEFT JOIN `xf_user` AS giver
						ON (giver.user_id = reputation.giver_user_id)';
			}
			
			if ($fetchOptions['join'] & self::FETCH_REPUTATION_RECEIVER)
			{
				$selectFields .= '
					, if(receiver.username is not null, receiver.username, reputation.receiver_username) as receiver_username' ;
				$joinTables .= '
					INNER JOIN `xf_user` AS receiver
						ON (receiver.user_id = reputation.receiver_user_id)';
			}
				
			if ($fetchOptions['join'] & self::FETCH_POST)
			{
				$selectFields .= '
					, post.message AS post_message, post.message_state 
					, thread.thread_id, thread.title, thread.node_id, thread.discussion_state, thread.discussion_open, thread.discussion_type';
				$joinTables .= '
					INNER JOIN `xf_post` AS post
						ON (post.post_id = reputation.post_id)
					LEFT JOIN `xf_thread` AS thread
						ON (thread.thread_id = post.thread_id)';
			}
			
			if ($fetchOptions['join'] & self::FETCH_DELETION_LOG)
			{
				$selectFields .= ',
					deletion_log.delete_date, deletion_log.delete_reason,
					deletion_log.delete_user_id, deletion_log.delete_username';
				$joinTables .= '
					LEFT JOIN xf_deletion_log AS deletion_log ON
						(deletion_log.content_type = \'brivium_reputation_system\' AND deletion_log.content_id = reputation.reputation_id)';
			}
		}
	
		return array(
				'selectFields' => $selectFields,
				'joinTables'   => $joinTables
		);
	}
	
	//Order reputations by date
	public function prepareReputationOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
				'reputation_date' => 'reputation.reputation_date',
		);
	
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}
	
	
	
	public function getReputationsByIds($reputationIds, array $fetchOptions = array())
	{
		if(empty($reputationIds))
		{
			return array();
		}
		$conditions = array('reputation_id' => $reputationIds);
		return $this->getReputations($conditions, $fetchOptions);
	}
	
	
	public function canViewAnonymousReputations(array $viewingUser = array())
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_admin'] || $viewingUser['is_staff'] || $viewingUser['is_moderator'])
		{
			return true;
		}
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'viewAnonymousReputations');		
	}
	
	//View members reputations in profiles for own profiles or all profiles
	public function canViewReputationProfile(array $userProfile, array $viewingUser = null) 
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'view_all_rep')) 
		{
			return true;
		}
		
		if ($userProfile['user_id'] == $viewingUser['user_id'] && XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'view_own_rep'))
		{
		    return true;
		}
        
        return false;	
	}
	
	//Groups that can see the reputation statistic area
	public function canViewReputationStatistics(array $viewingUser = null) 
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'view_rep_stats')) 
		{
			return true;
		}

		return false;
	}
	
	public function dailyLimit(array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null, array $guest = array()) 
	{
		$this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);
		
		if(empty($viewingUser['user_id']) && empty($guest['email_address']))
		{
			return true;
		}
		
		$conditions = array(
			'giver_user_id' => $viewingUser['user_id'],
			'email_address' => !empty($guest['email_address'])?$guest['email_address']:'',
			'post_id'		=> $post['post_id'],
		);
		
		if($this->countReputations($conditions))
		{
			$errorPhraseKey = array('you_already_gave_reputation');
			return false;
		}
		
		$dailyrepgids = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'daily_rep_limit');
		//Daily limit
		if ($dailyrepgids > 0) 
		{
			$conditions = array(
				'giver_user_id' => $viewingUser['user_id'],
				'reputation_date' => array('>', XenForo_Application::$time - 86400),
				'email_address' => !empty($guest['email_address'])?$guest['email_address']:''
			);
			$reputationCounts = $this->countReputations($conditions);
			if ($reputationCounts >= $dailyrepgids) 
			{
				if(!empty($guest['email_address']))
				{
					$errorPhraseKey = array('BRARS_daily_rep_reached', 'username' => $guest['username'], 'dailyrepgids' => $dailyrepgids);
				}else{
					$errorPhraseKey = array('BRARS_daily_rep_reached', 'username' => $viewingUser['username'], 'dailyrepgids' => $dailyrepgids);
				}
	            return false;
			}
		}
		
		
		$userSpread = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'user_spread');
		//User spread limit
		if ($userSpread > 0) 
		{
			$conditions = array(
					'giver_user_id' => $viewingUser['user_id'],
					'email_address' => !empty($guest['email_address'])?$guest['email_address']:''
			);
			
			$fetchOptions = array(
				'limit' => $userSpread
			);
			$reputations = $this->getReputations($conditions, $fetchOptions);
			
			if(!empty($reputations))
			{
				foreach ($reputations as $reputation)
				{
					if ($reputation['receiver_user_id'] == $post['user_id'])
					{
						$errorPhraseKey = array('BRARS_user_spread_limit', 'username' => $post['username'], 'userspread' => $userSpread);
						return false;
					}
				}
			}
		}elseif($userSpread < 0)
		{
			$conditions = array(
					'giver_user_id' => $viewingUser['user_id'],
					'email_address' => !empty($guest['email_address'])?$guest['email_address']:'',
					'post_id'	=> $post['post_id']
			);
			$reputationCount = $this->countReputations($conditions);
			
			if(!empty($reputationCount))
			{
				$errorPhraseKey = array('you_already_gave_reputation');
				return false;
			}
		}
		
		return true;
	}
	
	public function canViewReputation(array $reputation, array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		
		if(!$this->_getPostModel()->canUseReputation($forum, $errorPhraseKey, $nodePermissions, $viewingUser))
		{
			return false;
		}
		
		if ($this->isModerated($reputation) && !$this->canViewModeratedReputations($viewingUser))
		{
			return false;
		}
		else if ($this->isDeleted($reputation) && !$this->canViewDeletedReputations($viewingUser))
		{
			return false;
		}
		
		if(!$this->_getPostModel()->canViewPost($post, $thread, $forum))
		{
			return false;
		}
		
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'viewAllReps'))
		{
			return true;
		}
		
		if ($reputation['receiver_user_id'] == $viewingUser['user_id'] && XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'viewOwnReps'))
		{
			return true;
		}
		
		if ($reputation['giver_user_id'] == $viewingUser['user_id'])
		{
			return true;
		}
		
		return false;
	}
	
	public function canViewReputationAndContainer(array $reputation, array $post, array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReferenceForNode( $thread['node_id'], $viewingUser, $nodePermissions);
		return $this->canViewReputation( $reputation, $post, $thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
	}
	
	public function canViewModeratedReputations(array $viewingUser = array())
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_viewModerated');
	}
	
	public function canViewDeletedReputations(array $viewingUser = array())
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_viewDeleted');
	}
	
	public function canReportReputation(array $viewingUser = array())
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_canReportRep');
	}
	
	public function canApproveUnapproveReputation(array $viewingUser = array())
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_approveUnapprove');
	}
	
	public function isDeleted(array $reputation)
	{
		if (! isset( $reputation['reputation_state']))
		{
			throw new XenForo_Exception( 'BRARS_reputation_state_not_available');
		}
	
		return ($reputation['reputation_state'] == 'deleted');
	}
	
	public function isModerated(array $reputation)
	{
		if (! isset( $reputation['reputation_state']))
		{
			throw new XenForo_Exception( 'BRARS_reputation_state_not_available');
		}
	
		return ($reputation['reputation_state'] == 'moderated');
	}
	
	public function canEditReputation(array $reputation, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_editAnyRep'))
		{
			return true;
		}
	
		if ($reputation['giver_user_id'] == $viewingUser['user_id'] && XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_editOwnRep'))
		{
			$editDeleteLimit = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'deleteOwnRepLimit');
	
			if ($editDeleteLimit != -1 && (!$editDeleteLimit || $reputation['reputation_date'] < XenForo_Application::$time - 60 * $editDeleteLimit))
			{
				$errorPhraseKey = array('BRARS_reputation_edit_or_delete_time_limit_expired', 'minutes' => $editDeleteLimit);
				return false;
			}
			return true;
		}
		return false;
	}
	
	public function canDeleteReputation(array $reputation,  $deleteType = 'soft', &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
	
		if ($deleteType != 'soft' && !XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_hardDeleteAnyRep'))
		{
			return false;
		}
	
		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_deleteAnyRep'))
		{
			return true;
		}else if ($reputation['giver_user_id'] == $viewingUser['user_id'] && XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'deleteOwnRep'))
		{
			$editDeleteLimit = XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'deleteOwnRepLimit');
			if ($editDeleteLimit != -1 && (!$editDeleteLimit || $reputation['reputation_date'] < XenForo_Application::$time - 60 * $editDeleteLimit))
			{
				$errorPhraseKey = array('BRARS_reputation_edit_or_delete_time_limit_expired', 'minutes' => $editDeleteLimit);
				return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Fetch the most reputated users
	 *
	 * @param array $criteria
	 * @param array $fetchOptions
	 *
	 * @return array User records
	 */
	public function getMostReputatedUsers(array $criteria, array $fetchOptions = array())
	{
		$fetchOptions['order'] = 'reputation_count';
		$fetchOptions['direction'] = 'desc';

		return $this->getReputatedUsers($criteria, $fetchOptions);
	}
	
	public function getLastNumberReceviedNotNegative()
	{
		if(is_array($this->_lastNumberReceviedNotNegative))
		{
			return $this->_lastNumberReceviedNotNegative;
		}
		
		$reputations = $this->getReputations(array('reputation_state' => 'visible'));
		
		if(empty($reputations))
		{
			$this->_lastNumberReceviedNotNegative =  array();
			return array();
		}
		
		$users = array();
		$finishUser = array();
		foreach ($reputations as $reputation)
		{
			$number = isset($users[$reputation['receiver_user_id']]['brNumberReputationNotNegativer'])?$users[$reputation['receiver_user_id']]['brNumberReputationNotNegativer']:-1;
			if(empty($number))
			{
				continue;
			}
			
			if($reputation['points'] >= 0)
			{
				$number = max(1, $number+1);
				$finishUser[$reputation['receiver_user_id']]['brNumberReputationNotNegativer'] = $number;
			}else
			{
				$number = 0;
			}
			$users[$reputation['receiver_user_id']]['brNumberReputationNotNegativer'] = $number;
			
		}
		$this->_lastNumberReceviedNotNegative =  $finishUser; 
		return $this->_lastNumberReceviedNotNegative;
	}
	
	/**
	 * Gets most reputation users.
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array Format: [user id] => user info
	 */
	public function getReputatedUsers(array $conditions, array $fetchOptions = array())
	{
		$whereClause = $this->prepareRepUserConditions($conditions, $fetchOptions);
		$orderClause = $this->prepareRepUserOrderOptions($fetchOptions, 'user.username');
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT user.*
				FROM xf_user AS user
				WHERE ' . $whereClause . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'user_id');
	}
	
	public function prepareRepUserConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['reputation_count']) && is_array($conditions['reputation_count']))
		{
			$sqlConditions[] = $this->getCutOffCondition("user.reputation_count", $conditions['reputation_count']);
		}

		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function prepareRepUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'reputation_count' => 'user.reputation_count'
		);
		
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}
	
	//Recount. See below
	/* public function recountReputations()
	{		
		@set_time_limit(0);
		ignore_user_abort(true);
		XenForo_Application::getDb()->setProfiler(false); 
		$db = $this->_getDb();
			
		
		//Delete reputation(s) given in posts that do not exist anymore
		$db->query("DELETE reps.*
 		            FROM xf_reputation AS reps  
					LEFT JOIN xf_post AS post 
					ON (reps.post_id = post.post_id)
					WHERE post.post_id IS NULL
				   ");	
		
	    //Delete reputation(s) given by users that do not exist anymore
	    $db->query("DELETE reps.* 
		            FROM xf_reputation AS reps 
					LEFT JOIN xf_user AS user 
					ON (reps.giver_user_id = user.user_id)
					WHERE user.user_id IS NULL
				  ");			  
        
		XenForo_Db::commit($db);
	} */

	
	//Gets the most positive reputation posts in descending order
	public function getMostPositivePosts($limit)
	{
		$limitedSql = $this->limitQueryResults("
			 SELECT r.post_id, p.user_id, p.message, p.username, COUNT(*) AS positive_reputations
             FROM xf_reputation r
             LEFT JOIN xf_post p ON (r.post_id=p.post_id)
             WHERE r.points > 0
			 AND p.message_state = 'visible'
             GROUP BY r.post_id
             ORDER BY positive_reputations DESC
		", $limit);
		
		$postIds = $this->_getDb()->fetchCol($limitedSql);

		$postResults = $this->_getPostModel()->getPostsByIds($postIds, array
		(
			'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_USER
		));

		$postModel = $this->_getPostModel();
		
		$posts = array();
		
		foreach ($postResults AS $post)
		{
			if ($postModel->canViewPost($post, $post, $post))
			{
			   $posts["$post[post_id].$post[post_date]"] = $post;
			}
		}
		krsort($posts);

		return $posts;
	}
	
	//Gets the most negative reputation posts in descending order
	public function getMostNegativePosts($limit)
	{
		$limitedSql = $this->limitQueryResults("
			 SELECT r.post_id, p.user_id, p.message, p.username, COUNT(*) AS negative_reputations
             FROM xf_reputation r
             LEFT JOIN xf_post p ON (r.post_id=p.post_id)
             WHERE r.points < 0
			 AND p.message_state = 'visible'
             GROUP BY r.post_id
             ORDER BY negative_reputations DESC
		", $limit);
		
		$postIds = $this->_getDb()->fetchCol($limitedSql);

		$postResults = $this->_getPostModel()->getPostsByIds($postIds, array
		(
			'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_USER
		));
		
        $postModel = $this->getModelFromCache('XenForo_Model_Post');
		
		$posts = array();
		
		foreach ($postResults AS $post)
		{
			if ($postModel->canViewPost($post, $post, $post))
			{
			   $posts["$post[post_id].$post[post_date]"] = $post;
			}
		}
		krsort($posts);

		return $posts;
	}
	
	//Delete all user's reputations
	public function getReputationsByUser($userId)
	{
		return $this->fetchAllKeyed("
			SELECT *
			FROM xf_reputation
			WHERE giver_user_id = ?
			ORDER BY reputation_date DESC
		", 'reputation_id', $userId);
	}
	
	public function getReputationsInPostBefore($postId, $beforeDate, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions += array(
				'post_id' => $postId,
				'reputation_date' => array(
						'<',
						$beforeDate
				)
		);
		return $this->getReputations( $conditions, $fetchOptions);
	}
	
	public function getReputationsInPostAfter($postId, $afterDate, array $conditions = array(), array $fetchOptions = array())
	{
		$conditions += array(
				'post_id' => $postId,
				'reputation_date' => array(
						'>',
						$afterDate
				)
		);
		return $this->getReputations( $conditions, $fetchOptions);
	}
	

	/**
	 * @param integer $userId
	 *
	 * @return int Total reputations deleted
	 */
	public function deleteReputationsByUser($userId)
	{
		$reputations = $this->getReputationsByUser($userId);
		if(!empty($reputations))
		{
			$posts = array();
			$receiverUsers = array();
			
			foreach ($reputations as $reputation)
			{
				$postPoint = isset($posts[$reputation['post_id']])?$posts[$reputation['post_id']]:0;
				$receiverPoint = isset($receiverUsers[$reputation['receiver_user_id']])?$receiverUsers[$reputation['receiver_user_id']]:0;
				
				$posts[$reputation['post_id']] = $reputation['points'] + $postPoint;
				$receiverUsers[$reputation['receiver_user_id']] = $reputation['points'] + $receiverPoint;
			}

			$db = $this->_getDb();
			$db->delete('xf_reputation', 'giver_user_id = ' .$userId);
			$this->rebuildReputationsToPosts(array_keys($posts));
			$this->rebuildReputationToUserGiver($userId, -999999999, -999999999);
			
			foreach ($receiverUsers as $receiveUserId => $points)
			{
				$this->recountPointsToUserReceived(-$points, $receiveUserId);
			}
			
			if(XenForo_Model_Alert::userReceivesAlert( array('user_id' => $userId), 'reputation', 'delete'))
			{
				XenForo_Model_Alert::alert( $userId, 0, '', 'user', $userId, 'delete_all_reputation');
			}
		}
		return true;
	}
	
	public function prepareReputations(array $reputations)
	{
		if(!empty($reputations))
		{
			foreach ($reputations as $key=>&$reputation)
			{
				$reputation = $this->prepareReputation($reputation);
			}
		}
		
		return $reputations;
	}
	
	
	public function prepareReputation(array $reputation)
	{
		$reputation['canView'] =  $this->canViewReputation($reputation, $reputation, $reputation, $reputation);
		$reputation['canEdit'] = $this->canEditReputation($reputation);
		if($this->canDeleteReputation($reputation, 'soft'))
		{
			$reputation['canDelete'] = true;
			$reputation['canUndelete'] = true;
		}
		$reputation['canReport'] = $this->canReportReputation();
		$reputation['isDeleted'] = $this->isDeleted( $reputation);
		$reputation['isModerated'] = $this->isModerated( $reputation);
	
		if ($this->canApproveUnapproveReputation())
		{
			$reputation['canApprove'] = true;
			$reputation['canUnapprove'] = true;
		}
		
		if(!empty($reputation['canEdit']) || !empty($reputation['canDelete']) || !empty($reputation['canApprove']))
		{
			$reputation['hasControls'] = true;
		}
		
		if(empty($reputation['giver_user_id']) && !empty($reputation['email_address']))
		{
			$reputation['is_guest'] = true;
		}
		
		$reputation['ip_address'] = long2ip($reputation['ip_address']);
	
		$userModel = $this->getModelFromCache('XenForo_Model_User');
		$visitor = XenForo_Visitor::getInstance()->toArray();
		$reputation['canViewAnonymous'] = (
				$reputation['giver_user_id'] == $visitor['user_id']
				|| $this->canViewAnonymousReputations()
		);
		
		$reputation['receiver'] =  array(
				'user_id' => $reputation['receiver_user_id'],
				'username' => $reputation['receiver_username']
		);
		
		$reputation['giver'] =  array(
				'user_id' => $reputation['giver_user_id'],
				'username' => $reputation['giver_username']
		);
		
		if (array_key_exists('user_group_id', $reputation))
		{
			$userModel = $this->_getUserModel();
			$post = $userModel->prepareUser($reputation);
		}
		if ($reputation['is_anonymous'])
		{
			$reputation['user_id'] = 0;
			$reputation['username'] = new XenForo_Phrase('BRARS_anonymous');
		}else if(!empty($reputation['is_guest']))
		{
			$reputation['user_id'] = 0;
			$reputation['username'] = new XenForo_Phrase('BRARS_guest');
		}
		$reputation['star'] =  $this->convertPointsToStar($reputation['points']);
		
		if (!empty($reputation['delete_date']))
		{
			$reputation['deleteInfo'] = array(
					'user_id' => $reputation['delete_user_id'],
					'username' => $reputation['delete_username'],
					'date' => $reputation['delete_date'],
					'reason' => $reputation['delete_reason'],
			);
		}
		
		return $reputation;
	}
	
	public function canUseStarInterface()
	{
		$starOptions = XenForo_Application::getOptions()->BRARS_starInterface;
		if(empty($starOptions['enabled']) || empty($starOptions['star']) || !is_array($starOptions['star']) || count($starOptions['star'])!=5 )
		{
			return false;
		}
		 
		return true;
	}
	
	
	public function convertPointsToStar($points, $half = false)
	{
		if(!$this->canUseStarInterface())
		{
			return false;
		}
		$starOptions = XenForo_Application::getOptions()->BRARS_starInterface;

		$stars = $starOptions['star'];
		
		if($points <= reset($stars))
		{
			return 1;
		}elseif($points >= end($stars))
		{
			return 5;
		}

		foreach ( $stars AS $star => $point )
		{
			$halfPoints = $point+($stars[$star + 1] - $stars[$star])/2;
			if($points>=$point && $points<$halfPoints)
			{
				return $star;
			}
			
			if($points>= $halfPoints && $points<$stars[$star + 1])
			{
				if($half)
				{
					return $star+0.5;
				}
				return $star+1;
			}
		}
		return false;
	}
	
	public function _updateReputationState(array $reputation, $newState, $expectedOldState = false)
	{
		switch ($newState)
		{
			case 'visible' :
				switch (strval( $expectedOldState))
				{
					case 'visible' :
						return;
					case 'moderated' :
						$logAction = 'approve';
						break;
					case 'deleted' :
						$logAction = 'undelete';
						break;
					default :
						$logAction = 'undelete';
						break;
				}
				break;
					
			case 'moderated' :
				switch (strval( $expectedOldState))
				{
					case 'visible' :
						$logAction = 'unapprove';
						break;
					case 'moderated' :
						return;
					case 'deleted' :
						$logAction = 'unapprove';
						break;
					default :
						$logAction = 'unapprove';
						break;
				}
				break;
					
			case 'deleted' :
				switch (strval( $expectedOldState))
				{
					case 'visible' :
						$logAction = 'delete_soft';
						break;
					case 'moderated' :
						$logAction = 'delete_soft';
						break;
					case 'deleted' :
						return;
					default :
						$logAction = 'delete_soft';
						break;
				}
				break;
					
			default :
				return;
		}
		
		if ($expectedOldState && $reputation['reputation_state'] != $expectedOldState)
		{
			return;
		}
		$dw = XenForo_DataWriter::create( 'Brivium_AdvancedReputationSystem_DataWriter_Reputation', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($reputation['reputation_id']);
		$dw->set( 'reputation_state', $newState);
		$dw->save();
		
		XenForo_Model_Log::logModeratorAction( 'brivium_reputation_system', $reputation, $logAction);
	}
	
	public function deleteReputation($reputationId, $deleteType, array $options = array())
	{
		$options = array_merge( array(
			'reason' => '',
			'authorAlert' => false,
			'authorAlertReason' => ''
		), $options);
		
		$dw = XenForo_DataWriter::create( 'Brivium_AdvancedReputationSystem_DataWriter_Reputation');
		$dw->setExistingData( $reputationId);
		
		if ($deleteType == 'hard')
		{
			$dw->delete();
		} else
		{
			$dw->setExtraData( Brivium_AdvancedReputationSystem_DataWriter_Reputation::DATA_DELETE_REASON, $options['reason']);
			$dw->set( 'reputation_state', 'deleted');
			$dw->save();
		}
		
		$alertUserId = $dw->get('giver_user_id');
		if ($options['authorAlert'] && !empty($alertUserId))
		{
			$threadPost = $this->getReputationById($reputationId, array('join'=>self::FETCH_POST));
			if(!empty($threadPost))
			{
				$this->sendModeratorActionAlert('delete', $dw->getMergedData(), $threadPost, $options['authorAlertReason']);
			}
		}
		
		return $dw;
	}
	
	public function sendModeratorActionAlert($action, array $reputation, array $threadPost, $reason = '', array $extra = array(), $alertUserId = null)
	{
		$extra = array_merge( array(
				'title' => $threadPost['title'],
				'link' => XenForo_Link::buildPublicLink( 'brars-reputations', $reputation),
				'postLink' => XenForo_Link::buildPublicLink( 'posts', $threadPost),
				'threadLink' => XenForo_Link::buildPublicLink( 'threads', $threadPost),
				'reason' => $reason
		), $extra);
	
		if ($alertUserId === null)
		{
			$alertUserId = $reputation['giver_user_id'];
		}
	
		if (! $alertUserId)
		{
			return false;
		}
		
		if(XenForo_Model_Alert::userReceivesAlert( array('user_id' => $alertUserId), 'reputation', 'delete'))
		{
			XenForo_Model_Alert::alert( $alertUserId, 0, '', 'user', $alertUserId, 'reputation_'.$action, $extra);
		}
		return true;
	}
	
	public function reputationStateViews()
	{
		$viewReputationState = array('visible');
		if($this->canViewModeratedReputations())
		{
			$viewReputationState[] = 'moderated';
		}
	
		if($this->canViewDeletedReputations())
		{
			$viewReputationState[] = 'deleted';
		}
		return $viewReputationState;
	}
	
	public function getReputationInsertState()
	{
		$viewingUser = $this->standardizeViewingUserReference();
	
		if ($viewingUser['user_id'] && $this->canApproveUnapproveReputation())
		{
			return 'visible';
		}
		
		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'BRARS_reputationVisible'))
		{
			return 'visible';
		}
		return 'moderated';
	}
	
	public function mergeLastReputations(array $positionReputation, array $posts, array $thread, array $forum)
	{
		if (empty( $posts))
		{
			return $posts;
		}
		
		// get All Last Post reputation Ids.
		$lastPostReputationIds = array();
		foreach($posts as $post)
		{
			if(empty($post['br_lastest_reputation_ids']) || empty($post['canViewReptations']))
			{
				continue;
			}
			
			if(!empty($positionReputation['post_id']) && $positionReputation['post_id'] == $post['post_id'])
			{
				continue;
			}
			$lastPostReputationIds += array_flip( explode( ',', $post['br_lastest_reputation_ids']));
		}
		
		if (empty( $lastPostReputationIds) && empty($positionReputation))
		{
			return $posts;
		}
		
		$reputations = array();
		$conditions['reputation_state'] = $this->reputationStateViews();
		$fetchOptions['join'] = self::FETCH_REPUTATION_GIVER|self::FETCH_POST|self::FETCH_DELETION_LOG;
		if(!empty($lastPostReputationIds))
		{
			$conditions['reputation_id'] = array_keys( $lastPostReputationIds);
			$reputations = $this->getReputations($conditions, $fetchOptions);
			unset( $conditions['reputation_id']);
		}
		
		$optionMaxReputationDisplay = XenForo_Application::getOptions()->BRARS_maxReputationsDisplay;

		if (! empty($positionReputation))
		{
			$conditions += array(
					'end' => $positionReputation['reputation_date'],
					'post_id' => $positionReputation['post_id']
			);
			$fetchOptions += array(
					'limit' => $optionMaxReputationDisplay+1,
			);
			$positionReputations = $this->getReputations( $conditions, $fetchOptions);
		}
		
		if (! empty( $positionReputations))
		{
			$reputations += $positionReputations;
			$lastPositionComment = reset( $positionReputations);
			
			if (! empty( $posts[$lastPositionComment['post_id']]) && $posts[$lastPositionComment['post_id']]['br_last_reputations_date'] > $lastPositionComment['reputation_date'])
			{
				if(!empty($posts[$lastPositionComment['post_id']]['canViewReptations']))
				{
					$posts[$lastPositionComment['post_id']]['brivium_last_shown_reputation_date'] = $lastPositionComment['reputation_date'];
				}
			}
		}
		
		if (! empty( $reputations))
		{
			$reputations = $this->prepareReputations($reputations);
			
			foreach($reputations as $reputationId => $reputation)
			{
				if (! empty( $posts[$reputation['post_id']]) && !empty($posts[$reputation['post_id']]['canViewReptations']))
				{
					if(!empty($posts[$reputation['post_id']]['brReputations']) && count($posts[$reputation['post_id']]['brReputations'])>=$optionMaxReputationDisplay)
					{
						if(empty($posts[$reputation['post_id']]['brivium_first_shown_reputation_date']))
						{
							$firstreputation = end($posts[$reputation['post_id']]['brReputations']);
							$posts[$reputation['post_id']]['brivium_first_shown_reputation_date'] = $firstreputation['reputation_date'];
						}
						continue;
					}
					$posts[$reputation['post_id']]['brReputations'][$reputationId] = $reputation;
				}
			}
		}
		return $posts;
	}
	
	public function rebuildReputationsToPosts($postIds = array())
	{
		$db = $this->_getDb();
	
		$whereConditions = '';
		$andWhereConditions = '';
		if (!empty($postIds))
		{
			if (is_array($postIds))
			{
				$whereConditions 	= 'WHERE post_id IN (' . $db->quote($postIds) . ')';
				$andWhereConditions = ' AND post_id IN (' . $db->quote($postIds) . ')';
			}
			else
			{
				$whereConditions = 'WHERE post_id = ' . $db->quote($postIds);
				$andWhereConditions = ' AND post_id = ' . $db->quote($postIds);
			}
		}
		
		$reputationsPointAndCount = $this->fetchAllKeyed("
			SELECT post_id, sum(points) as reputations, count(post_id) as br_reputations_count
			FROM xf_reputation
			WHERE reputation_state = ? $andWhereConditions
			GROUP BY post_id
		", 'post_id', array('visible'));
		$reputationDate = $this->fetchAllKeyed("
			SELECT post_id,
				MIN(reputation_date) AS br_first_reputation_date,
				MAX(reputation_date) AS br_last_reputations_date
			FROM xf_reputation 
			$whereConditions
			GROUP BY post_id
		",'post_id');
		
		$reputationsIds = $this->fetchAllKeyed("
				SELECT reputation_id, reputation_date, post_id
				FROM xf_reputation 
				$whereConditions
				ORDER BY reputation_date DESC
				",'reputation_id');
		
		$reputations = array();
		if(!empty($reputationsIds))
		{
			$tmpArray = array();
			foreach ($reputationsIds as $reputationId => $reputation)
			{
				if(!empty($tmpArray[$reputation['post_id']]) && count($tmpArray[$reputation['post_id']])>10)
				{
					continue;
				}
				$tmpArray[$reputation['post_id']][$reputationId]=$reputationId;
			}
			
			foreach ($tmpArray as $postId => $value)
			{
				$reputations[$postId]['br_lastest_reputation_ids'] = implode(',', array_reverse($value));
				if(!empty($reputationsPointAndCount[$postId]))
				{
					$reputations[$postId] += $reputationsPointAndCount[$postId];
				}else 
				{
					$reputations[$postId] += array('reputations' => 0, 'br_reputations_count' => 0);
				}
				if(!empty($reputationDate[$postId]))
				{
					$reputations[$postId] += $reputationDate[$postId];
				}
				else
				{
					$reputations[$postId] += array('br_first_reputation_date' => 0, 'br_last_reputations_date' => 0);
				}
				
			}
		}
		if(!empty($reputations))
		{
			foreach ($reputations as $postId => $value)
			{
				unset($value['post_id']);
				$db->update('xf_post', $value, 'post_id = '.$postId);
			}
		}
		
		if(!empty($postIds))
		{
			$bind = array(
				'reputations'            		=> 0,
				'br_reputations_count'   		=> 0,
				'br_first_reputation_date' 		=> 0,
				'br_last_reputations_date'   	=> 0,
				'br_lastest_reputation_ids'   	=> '',
			);
			
			if(is_array($postIds))
			{
				$diffPostIds =  array_diff($postIds, array_keys($reputations));
				if(!empty($diffPostIds))
				{
					$db->update('xf_post', $bind, 'post_id IN (' . $db->quote($postIds) . ')');
				}
			}elseif(!array_key_exists($postIds, $reputations)){
				$db->update('xf_post', $bind, 'post_id = '.$postIds);
			}
		}
	}
	
	public function sendEmail(array $user, array $reputation, array $post, array $thread)
	{
		if(empty($user['email']) || empty($reputation['encode']) || !XenForo_Application::getOptions()->BRARS_emailConfirmation)
		{
			return false;
		}
		
		$confirmLink = XenForo_Link::buildPublicLink( 'full:brars-reputations/confirm', $reputation, array('c'=>$reputation['encode']));
		$params = array(
				'boardTitle' => XenForo_Application::getOptions()->boardTitle,
				'confirmLink' => $confirmLink,
				'user' => $user,
				'reputation' => $reputation,
				'post' => $post,
				'thread' => $thread
		);
		$mail = XenForo_Mail::create('BRARS_confirm_rating_reputation', $params);
		$mail->send($user['email'], $user['username']);
		
		return true;
	}
	
	public function recountPointsToUserReceived($points, $userId)
	{
		if(empty($userId) || empty($points))
		{
			return false;	
		}
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_User');
		$dw->setExistingData($userId);
		
		$reputationCount = $dw->getExisting('reputation_count') + $points;
		
		$dw->set('reputation_count', $reputationCount);
		$dw->save();
	}
	
	public function rebuildReputationToUserGiver($userId, $ratedCount, $ratedNegativeCount)
	{
		if(empty($userId))
		{
			return false;
		}
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_User');
		$dw->setExistingData($userId);
		
		$brRatedCount = max(0, $dw->getExisting('brars_rated_count') + $ratedCount);
		$brRatedNegativeCount = max(0, $dw->getExisting('brars_rated_negative_count') + $ratedNegativeCount);
		
		$dw->set('brars_rated_count', $brRatedCount);
		$dw->set('brars_rated_negative_count', $brRatedNegativeCount);
		$dw->save();
	}
	
	public function rebuildReputationsToUsers($giverUserIds = array())
	{
		$wherePostConditions = $this->perPostConditions(array('user_id' =>$giverUserIds));
		$fetchOptions = array();
		$whereConditions = $this->prepareReputationConditions(array('giver_user_id' => $giverUserIds), $fetchOptions);
		
		$db = $this->_getDb();
		$postCounts = $db->fetchPairs("
				SELECT post.user_id, count(post.post_id) as brars_post_count
				FROM xf_post AS post
				LEFT JOIN `xf_thread` AS thread
					ON (thread.thread_id = post.thread_id)
				WHERE $wherePostConditions 
				AND (post.message_state = ?) AND (thread.discussion_state = ?) 
				GROUP BY post.user_id
				", array('visible', 'visible'));
		
		$reputations = $this->fetchAllKeyed("
				SELECT reputation.giver_user_id, count(reputation.reputation_id) as brars_rated_count
					,count(if(reputation.points < 0, 1, NULL)) as brars_rated_negative_count
				FROM xf_reputation AS reputation
				WHERE $whereConditions AND (reputation_state = ?)
				GROUP BY giver_user_id
				", 'giver_user_id',array('visible'));
		
		if(!empty($postCounts))
		{
			XenForo_Db::beginTransaction($db);
			foreach ($postCounts as $userId => $postCount)
			{
				$bind = array(
						'brars_post_count' => $postCount,
						'brars_rated_count' => !empty($reputations[$userId]['brars_rated_count'])?$reputations[$userId]['brars_rated_count']:0,
						'brars_rated_negative_count' => !empty($reputations[$userId]['brars_rated_negative_count'])?$reputations[$userId]['brars_rated_negative_count']:0,
				);
				$db->update('xf_user', $bind, 'user_id = '.$userId);
				unset($bind);
			}
			XenForo_Db::commit($db);
		}
		return true;
	}
	
	public function clearReputations()
	{
		$db = $this->_getDb();
		$db->query('TRUNCATE TABLE xf_reputation');
		$bindUser = array(
			'reputation_count' => 0,
			'brars_post_count' => 0,
			'brars_rated_count' => 0,
			'brars_rated_negative_count' => 0
		);
		$db->update('xf_user', $bindUser);
		
		$bindPost = array(
			'reputations' => 0,
			'br_reputations_count' => 0,
			'br_first_reputation_date' => 0,
			'br_last_reputations_date' => 0,
			'br_lastest_reputation_ids' => ''
		);
		$db->update('xf_post', $bindPost);
	}
	
	public function deleteReputationEmptyPost()
	{
		$db = $this->_getDb();
		$db->query("DELETE reps.*
 		            FROM xf_reputation AS reps
					LEFT JOIN xf_post AS post
					ON (reps.post_id = post.post_id)
					WHERE post.post_id IS NULL
				   ");
	}
	
	public function getGiverUserIdsByPostId($postId)
	{
		$db = $this->_getDb();
		return $db->fetchAssoc("
				SELECT DISTINCT giver_user_id
				FROM xf_reputation
				WHERE (giver_user_id > 0) AND (reputation_state = ?) AND (post_id = ?)
				", array('visible', $postId));
	}
	
	protected function perPostConditions(array $conditions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		
		if (!empty($conditions['post_id']))
		{
			if (is_array($conditions['post_id']))
			{
				$sqlConditions[] = 'post.post_id IN (' . $db->quote($conditions['post_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'post.post_id = ' . $db->quote($conditions['post_id']);
			}
		}
		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'post.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'post.user_id = ' . $db->quote($conditions['user_id']);
			}
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function getPostIdsInRange($offSet = 0, $limit = 250)
	{
		$db = $this->_getDb();
		return $db->fetchCol("
			SELECT DISTINCT post_id
			FROM xf_reputation
			WHERE (reputation_state = ?)
			limit ?,? 
			", array('visible', $offSet, $limit));
	}
	
	public function importReputationFromBd()
	{
		$xenAddons = XenForo_Application::get('addOns');
		if(empty($xenAddons['bdReputation']))
		{
			return array();
		}

		$result = $this->_getDb()->query('
			INSERT IGNORE INTO `xf_reputation`
				(`post_id`, `receiver_user_id`, `receiver_username`, `giver_user_id`, `giver_username`, `reputation_date`, `points`, `comment`, `email_address`, `encode`, `bdreputation_given_id`)
			SELECT post_id, received_user_id, receiver.username, given_user_id, given.username, give_date, points, comment, given.email, NULL, given_id
			FROM xf_bdreputation_given AS bdreputation
			INNER JOIN xf_user AS receiver ON 
				(receiver.user_id = bdreputation.received_user_id)
			INNER JOIN xf_user AS given ON 
				(given.user_id = bdreputation.given_user_id)
		');
		return $result->rowCount();
	}
	
	
	public function getGiverUserIdsInRange($offSet = 0, $limit = 250)
	{
		$db = $this->_getDb();
		return $db->fetchCol("
			SELECT DISTINCT giver_user_id
			FROM xf_reputation
			WHERE (reputation_state = ?)
			limit ?,?
			", array('visible', $offSet, $limit));
	}
	
	public function updateReputationCounter($userIds)
	{
		if(empty($userIds))
		{
			return false;
		}
		$db = $this->_getDb();
		
		$where = '';
		if(is_array($userIds))
		{
			$where = 'WHERE xf_user.user_id IN ('.$db->quote($userIds).')';
		}
		
		$db->query('
			UPDATE xf_user
			LEFT JOIN (
				SELECT receiver_user_id, SUM(points) as reputation_count
				FROM xf_reputation
				WHERE (receiver_user_id > 0) AND (reputation_state = \'visible\')
				GROUP BY receiver_user_id
			) AS user_reputation_counter ON (user_reputation_counter.receiver_user_id = xf_user.user_id)
			SET
				xf_user.reputation_count = IF(user_reputation_counter.receiver_user_id IS NULL, 0, user_reputation_counter.reputation_count)
			'.$where.'
		');
	}
	
	/**
	 * @return XenForo_Model_Post
	 */
	protected function _getPostModel()
	{
		return $this->getModelFromCache('XenForo_Model_Post');
	}
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
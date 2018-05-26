<?php

class Brivium_ModernStatistic_Model_Thread extends XFCP_Brivium_ModernStatistic_Model_Thread
{
	public function modernStatisticPrepareThread(array $thread, $modernStatistic, array $nodePermissions = null, array $viewingUser = null)
	{
		$thread['lastPostInfo'] = array(
			'post_date' => $thread['last_post_date'],
			'post_id' => $thread['last_post_id'],
			'user_id' => $thread['last_post_user_id'],
			'username' => $thread['last_post_username']
		);
		$forum = array(
			'node_id' => $thread['node_id'],
		);
		if (isset($thread['node_title']))
		{
			$forum['title'] = $thread['node_title'];
			$thread['forum'] = $forum;
		}

		if ($thread['view_count'] <= $thread['reply_count'])
		{
			$thread['view_count'] = $thread['reply_count'] + 1;
		}

		$thread['title'] 			= XenForo_Helper_String::censorString($thread['title']);
		$thread['titleCensored'] 	= true;

		//$thread['lastPageNumbers'] = $this->getLastPageNumbers($thread['reply_count']);

		if (empty($thread['user_group_id']))
		{
			$thread['display_style_group_id'] = XenForo_Model_User::$defaultGuestGroupId;
		}
		if (!empty($thread['user_group_id']))
		{
			$thread['lastPostInfo']['user_group_id'] = $thread['user_group_id'];
		}
		if (!empty($thread['display_style_group_id']))
		{
			$thread['lastPostInfo']['display_style_group_id'] = $thread['display_style_group_id'];
		}
		if (!empty($thread['is_banned']))
		{
			$thread['lastPostInfo']['is_banned'] = $thread['is_banned'];
		}
		$options = XenForo_Application::get('options');
		if($modernStatistic['preview_tooltip']=='thread_preview'){
			$thread['hasPreview'] = $this->hasPreview($thread, $forum, $nodePermissions, $viewingUser);
		}

		$thread['isNew'] = $this->isNew($thread, $forum);
		if ($thread['isNew']) {
			$readDate = $this->getMaxThreadReadDate($thread, $forum);
			$thread['haveReadData'] = ($readDate >= XenForo_Application::$time - ($options->readMarkingDataLifetime * 86400));
		} else {
			$thread['haveReadData'] = false;
		}
		return $thread;
	}

	public function modernStatisticPrepareThreads($threads, $modernStatistic)
	{
		if(!$threads) {
			return array();
		}
		foreach($threads as &$thread){
			$thread = $this->modernStatisticPrepareThread($thread, $modernStatistic);
		}
		return $threads;
	}

	public function prepareThreadConditions(array $conditions, array &$fetchOptions)
	{
		$result = parent::prepareThreadConditions($conditions, $fetchOptions);

		$sqlConditions = array($result);
		$db = $this->_getDb();
		if (!empty($conditions['BRMS_post_date']) && is_array($conditions['BRMS_post_date'])) {
			list($operator, $cutOff) = $conditions['BRMS_post_date'];
			$this->assertValidCutOffOperator($operator);

			$sqlConditions[] = "thread.post_date $operator " . $db->quote($cutOff);
		}
		if (!empty($conditions['BRMS_not_node_id']))
		{
			if (is_array($conditions['node_id']))
			{
				$sqlConditions[] = 'thread.node_id NOT IN (' . $db->quote($conditions['BRMS_not_node_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'thread.node_id <> ' . $db->quote($conditions['BRMS_not_node_id']);
			}
		}

		if (isset($conditions['brms_promote']))
		{
			$sqlConditions[] = 'thread.brms_promote = ' . ($conditions['brms_promote'] ? 1 : 0);
		}

		if (count($sqlConditions) > 1) {
			return $this->getConditionsForClause($sqlConditions);
		} else {
			return $result;
		}
	}

	public function prepareThreadFetchOptions(array $fetchOptions)
	{
		$result = parent::prepareThreadFetchOptions($fetchOptions);
		extract($result);

		if (!empty($fetchOptions['order']) && $fetchOptions['order'] == 'brms_promote_date')
		{
			$orderBy = 'thread.brms_promote_date DESC, thread.last_post_date DESC';

			$orderClause  = "ORDER BY $orderBy";
		}

		if (!empty($fetchOptions['BRMS_fetch_user']))
		{
			$selectFields .= ',
					user.user_group_id , user.display_style_group_id ,  user.is_banned';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = thread.user_id)';
		}
		if (!empty($fetchOptions['BRMS_join_last_post']))
		{
			$selectFields .= ',
				last_post_user.user_group_id , last_post_user.display_style_group_id ,  last_post_user.is_banned';
			$joinTables .= '
				LEFT JOIN xf_user AS last_post_user ON
					(last_post_user.user_id = thread.last_post_user_id)';
		}
		return compact('selectFields', 'joinTables', 'orderClause');
	}

	public function canPromoteThreadBRMS(array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);

		return XenForo_Permission::hasContentPermission($nodePermissions, 'BRMS_promoteThread');
	}
}

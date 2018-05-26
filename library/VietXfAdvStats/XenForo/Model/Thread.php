<?php
class VietXfAdvStats_XenForo_Model_Thread extends XFCP_VietXfAdvStats_XenForo_Model_Thread {
	public function VietXfAdvStats_prepareThread(array $thread, array $nodePermissions = null, array $viewingUser = null) {
		if (isset($thread['forum_id'])) {
			$forum = array(
				'node_id' => $thread['forum_id'],
				'title' => $thread['forum_title'],
			);
		} else {
			$forum = array(
				'node_id' => $thread['node_id'],
			);
		}
		
		// return $this->prepareThread($thread, $forum, $nodePermissions, $viewingUser);
		// don't do this because that will add additional db query (node permission)
		$options =  new XenForo_Application;
		$options = $options->get('options');
		if(!$options->showPrefix){
			$thread['prefix_id']= 0;
		}
		if($options->showTooltip){
			$thread['hasPreview'] = $this->hasPreview($thread, $forum, $nodePermissions, $viewingUser);
		}
		
		$thread['isNew'] = $this->isNew($thread, $forum);
		if ($thread['isNew']) {
			$readDate = $this->getMaxThreadReadDate($thread, $forum);
			$thread['haveReadData'] = ($readDate > XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400));
		} else {
			$thread['haveReadData'] = false;
		}
	
		return $thread;
	}
	public function hasPreview(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

		return
		(
			$thread['first_post_id']
			&& XenForo_Application::get('options')->discussionPreviewLength
			&& $this->isRedirect($thread) == false
			&& XenForo_Permission::hasContentPermission($nodePermissions, 'viewContent')
		);
	}
	public function prepareThreadConditions(array $conditions, array &$fetchOptions) {
		$result = parent::prepareThreadConditions($conditions, $fetchOptions);
		
		$sqlConditions = array($result);
		
		if (!empty($conditions['VietXfAdvStats_forum_ids'])) {
			$sqlConditions[] = 'thread.node_id IN (' . $this->_getDb()->quote($conditions['VietXfAdvStats_forum_ids']) . ')';
		}
		
		if (!empty($conditions['VietXfAdvStats_post_date']) && is_array($conditions['VietXfAdvStats_post_date'])) {
			list($operator, $cutOff) = $conditions['VietXfAdvStats_post_date'];
			$this->assertValidCutOffOperator($operator);
			
			$sqlConditions[] = "thread.post_date $operator " . $this->_getDb()->quote($cutOff);
		}
		if (!empty($conditions['VietXfAdvStats_using'])) {
			$sqlConditions[] = "(thread.discussion_type != 'redirect' AND thread.discussion_state != 'deleted' AND thread.discussion_state != 'moderated')";
		}
		
		
		if (count($sqlConditions) > 1) {
			return $this->getConditionsForClause($sqlConditions);
		} else {
			return $result;
		}
	}
	
	public function prepareThreadFetchOptions(array $fetchOptions) {
		$result = parent::prepareThreadFetchOptions($fetchOptions);
		extract($result);
		
		if (!empty($fetchOptions['VietXfAdvStats_join_forum'])) {
			$selectFields .= ',
				node.node_id AS forum_id, node.title AS forum_title';
			$joinTables .= '
				INNER JOIN xf_node AS node ON
					(node.node_id = thread.node_id)
				INNER JOIN xf_forum AS forum ON
					(forum.node_id = thread.node_id)';
		}
		
		if (!empty($fetchOptions['VietXfAdvStats_join_last_post']) AND (empty($fetchOptions['join']) || !($fetchOptions['join'] & self::FETCH_USER))){
			if ($fetchOptions['VietXfAdvStats_join_last_post'] & self::FETCH_USER) {
				$selectFields .= ',
					user.*';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = thread.last_post_user_id)';
			} else if ($fetchOptions['VietXfAdvStats_join_last_post'] & self::FETCH_AVATAR) {
				$selectFields .= ',
					user.avatar_date, user.gravatar';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = thread.last_post_user_id)';
			}
		}
		if (!empty($fetchOptions['VietXfAdvStats_join']) AND (empty($fetchOptions['join']) || !($fetchOptions['join'] & self::FETCH_USER))){
			if ($fetchOptions['VietXfAdvStats_join'] & self::FETCH_USER) {
				$selectFields .= ',
					user.*';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = thread.user_id)';
			} else if ($fetchOptions['VietXfAdvStats_join_last_post'] & self::FETCH_AVATAR) {
				$selectFields .= ',
					user.avatar_date, user.gravatar';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = thread.user_id)';
			}
		}
		
		
		
		if (!empty($fetchOptions['VietXfAdvStats_random'])) {
			$orderClause = 'ORDER BY RAND()';
		}
		
		return compact('selectFields' , 'joinTables', 'orderClause');
	}
}
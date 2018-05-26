<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Forums extends Xenforo_Model
{
	public function getStickyForumList($forumList)
	{
		if ($forumList = $forumList['node_id'])
		{
			return $this->_getDb()->fetchAll('
				SELECT node_id, title
				FROM xf_node
				WHERE node_id IN (' . implode(',', $forumList) . ')
				ORDER BY title ASC
			');
		}
	}
	public function getForumsStickyList($forumList)
	{
		if ($forumList = $forumList['node_id'])
		{
			return $this->_getDb()->fetchAll('
				SELECT thread_id
				FROM xf_thread
				WHERE sticky = 1
				AND node_id IN (' . implode(',', $forumList) . ')');
		}
	}
	public function getForumStickyList($forumId)
	{
		$db = $this->_getDb();
		return $db->fetchAll('
			SELECT user_id
			FROM xf_thread
			WHERE sticky = 1
			AND node_id = ' . $db->quote($forumId));
	}
}
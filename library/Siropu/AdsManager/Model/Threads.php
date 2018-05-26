<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Threads extends Xenforo_Model
{
	public function getStickyForumList($forumList)
	{
		return $this->_getDb()->fetchAll('
			SELECT node_id, title
			FROM xf_node
			WHERE node_id IN (' . implode(',', $forumList['node_id']) . ')');
	}
	public function toggleStickyThreadById($status, $threadId, $expired = false)
	{
		$options = XenForo_Application::get('options');
		$actions = $options->siropu_ads_manager_unsticky_actions;
		$prefix  = $options->siropu_ads_manager_sticky_prefix;
		$data    = array('sticky' => in_array($status, array('Active', 'Completed')) ? 1 : 0);

		if ($prefix)
		{
			$data['prefix_id'] = $prefix;
		}

		if ($expired)
		{
			if ($actions['remove'])
			{
				$data['discussion_state'] = 'deleted';
			}
			if ($actions['close'])
			{
				$data['discussion_open'] = 0;
			}
			if ($actions['prefix'])
			{
				$data['prefix_id'] = 0;
			}
		}

		switch ($status)
		{
			case 'Active':
				break;
			case 'Inactive':
			case 'Paused':
				if ($actions['prefix'] || $prefix)
				{
					$data['prefix_id'] = 0;
				}
				break;
			default:
				break;
		}

		$this->_getDb()->update('xf_thread', $data, 'thread_id = ' . (int) $threadId);
	}
}
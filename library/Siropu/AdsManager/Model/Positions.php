<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Positions extends Xenforo_Model
{
	public function getAllPositions()
	{
		if ($resultArray = XenForo_Application::getSimpleCacheData('adPositionList'))
		{
			return $resultArray;
		}

		$resultArray = $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_ads_manager_positions
			ORDER BY display_order ASC
		', 'position_id');

		XenForo_Application::setSimpleCacheData('adPositionList', $resultArray);
		return $resultArray;
	}
	public function getPositionById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_positions
			WHERE position_id = ?
			', $id);
	}
	public function getPositionByHook($hook)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_positions
			WHERE hook = ?
			', $hook);
	}
	public function updatePositionsByCategoryId($catId, $data)
	{
		$db = $this->_getDb();
		$db->update('xf_siropu_ads_manager_positions', $data, 'cat_id = ' . $db->quote($catId));
	}
	public function getPositionsForHookMatch()
	{
		$list = array();

		foreach ($this->getAllPositions() as $position)
		{
			$list[$position['hook']] = $position['name'];
		}

		return $list;
	}
}
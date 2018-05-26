<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_PositionsCategories extends Xenforo_Model
{
	public function getAllCategories()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_ads_manager_positions_categories
			ORDER BY display_order ASC
		', 'cat_id');
	}
	public function getCategoryById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_positions_categories
			WHERE cat_id = ?
			', $id);
	}
}
<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_PromoCodes extends Xenforo_Model
{
	public function getAllPromoCodes()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_ads_manager_promo_codes
		', 'code_id');
	}
	public function getPromoCodeById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_promo_codes
			WHERE code_id = ?
			', $id);
	}
	public function getPromoCodeByCode($code)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_promo_codes
			WHERE code = ?
			', $code);
	}
	public function getPromoCodesForUser()
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_promo_codes
			WHERE enabled = 1
		');
	}
}
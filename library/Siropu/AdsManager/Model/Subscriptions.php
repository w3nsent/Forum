<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Subscriptions extends Xenforo_Model
{
	public function getAllSubscriptions($search = array())
	{
		$db     = $this->_getDb();
		$fields = array('subscr_id', 'package_id', 'username', 'subscr_method', 'status');
		$where  = '';

		if ($search)
		{
			$i = 0;

			foreach ($search as $field => $val)
			{
				if ($val && in_array($field, $fields))
				{
					$where .= ($i == 0) ? ' WHERE ' : ' AND ';
					$where .= "{$field} = {$db->quote($val)}";

					$i++;
				}
			}
		}

		return $db->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_subscriptions'
			. $where .
			' ORDER BY subscr_date DESC');
	}
	public function getAllSubscriptionsCount()
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_ads_manager_subscriptions
		');

		return $result['count'];
	}
	public function getSubscriptionById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_subscriptions
			WHERE subscription_id = ?
			', $id);
	}
	public function getSubscriptionBySubscrId($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_subscriptions
			WHERE subscr_id = ?
			', $id);
	}
	public function getSubscriptionByAdId($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_subscriptions
			WHERE ad_id = ?
			', $id);
	}
	public function getSubscriptionJoinAdsJoinPackagesById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT s.*, a.name AS ad_name, a.type, p.name AS package_name
			FROM xf_siropu_ads_manager_subscriptions s
			LEFT JOIN xf_siropu_ads_manager_ads a ON a.ad_id = s.ad_id
			LEFT JOIN xf_siropu_ads_manager_packages p ON p.package_id = s.package_id
			WHERE s.subscription_id = ?
			', $id);
	}
}
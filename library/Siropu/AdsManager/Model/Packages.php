<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Packages extends Xenforo_Model
{
	public function getAllPackages($search = array())
	{
		$db     = $this->_getDb();
		$fields = array('type');
		$where  = '';

		if ($search)
		{
			$i = 0;

			foreach ($search as $field => $val)
			{
				if ($val && in_array($field, $fields))
				{
					$where .= ($i == 0) ? ' WHERE ' : ' AND ';
					$where .= "p.{$field} = {$db->quote($val)}";

					$i++;
				}
			}
		}

		return $this->fetchAllKeyed('
			SELECT p.*, (
				SELECT COUNT(a.ad_id)
				FROM xf_siropu_ads_manager_ads a
				WHERE (a.status IN ("Active", "Paused") OR a.status_old IN ("Active", "Paused"))
				AND a.package_id = p.package_id
				AND a.count_exclude = 0
			) AS adCount
			FROM xf_siropu_ads_manager_packages p '
			. $where .
			' ORDER BY ' . $this->_getDisplayOrder(), 'package_id');
	}
	public function getPackageById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_packages
			WHERE package_id = ?
			', $id);
	}
	public function getPackagesByIds($ids)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_packages
			WHERE package_id IN (' . implode(',', $ids) . ')');
	}
	public function getPackagesByType($type)
	{
		$db = $this->_getDb();

		return $db->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_packages
			WHERE type = ' . $db->quote($type) . '
			ORDER BY enabled DESC, name ASC'
		);
	}
	public function getPackagesForUser()
	{
		return $this->_getDb()->fetchAll('
			SELECT p.*, (
				SELECT COUNT(*)
				FROM xf_siropu_ads_manager_ads a
				WHERE (a.status IN ("Active", "Paused") OR a.status_old IN ("Active", "Paused"))
				AND a.count_exclude = 0
				AND a.package_id = p.package_id
			) AS adCount
			FROM xf_siropu_ads_manager_packages p
			WHERE p.enabled = 1
			ORDER BY ' . $this->_getDisplayOrder());
	}
	public function getPackagesForPlaceholders()
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_packages
			WHERE enabled = 1
			AND cost_amount > 0
			AND type NOT IN ("sticky", "keyword")
			ORDER BY name ASC');
	}
	public function getPackageActiveAdCount($packageId)
	{
		if (!$packageId)
		{
			return false;
		}

		$db = $this->_getDb();
		$count = $db->fetchRow('
			SELECT COUNT(ad_id) AS adCount
			FROM xf_siropu_ads_manager_ads
			WHERE package_id = ' . $db->quote($packageId) . '
			AND count_exclude = 0
			AND (status IN ("Active", "Paused") OR status_old IN ("Active", "Paused"))
		');

		return $count['adCount'];
	}
	public function getPackageStats($packageId)
	{
		return $this->_getDb()->fetchRow('
			SELECT
				COALESCE(SUM(view_count), 0) AS totalViews,
				COALESCE(SUM(click_count), 0) AS totalClicks,
				COALESCE(ROUND(AVG(ctr), 2), 0) AS avgCtr
			FROM xf_siropu_ads_manager_ads
			WHERE package_id = ?
		', $packageId);
	}
	public function getTopPerformingPackages()
	{
		$resultArray = $this->_getDb()->fetchAll('
			SELECT p.*,
				(
					SELECT ROUND(AVG(a.ctr), 2)
					FROM xf_siropu_ads_manager_ads AS a
					WHERE a.package_id = p.package_id
					AND a.ctr > 0
				) AS avgCtr
			FROM xf_siropu_ads_manager_packages AS p
			WHERE p.daily_stats = 1
			ORDER BY avgCtr DESC
			LIMIT 5
		');

		foreach ($resultArray as $key => $val)
		{
			if ($val['avgCtr'] == 0.00)
			{
				unset($resultArray[$key]);
			}
		}

		return $resultArray;
	}
	public function getInheritData($data, $package = array())
	{
		$package = $package ? $package : $this->getPackageById($data['package_id']);

		if ($data['inherit_settings'] && $package)
		{
			$geoIpCriteria = XenForo_Helper_Criteria::unserializeCriteria($package['geoip_criteria']);
			return array_merge($data, array(
				'positions'         => $package['positions'],
				'item_id'           => $package['item_id'],
				'count_views'       => $package['count_ad_views'],
				'count_clicks'      => $package['count_ad_clicks'],
				'daily_stats'       => $package['daily_stats'],
				'click_stats'       => $package['click_stats'],
				'nofollow'          => $package['nofollow'],
				'target_blank'      => $package['target_blank'],
				'hide_from_robots'  => $package['hide_from_robots'],
				'keyword_limit'     => $package['keyword_limit'],
				'position_criteria' => $package['position_criteria'],
				'page_criteria'     => $package['page_criteria'],
				'user_criteria'     => $package['user_criteria'],
				'device_criteria'   => $package['device_criteria'],
				'geoip_criteria'    => $geoIpCriteria ? $geoIpCriteria : $data['geoip_criteria']
			));
		}

		return $data;
	}
	protected function _getDisplayOrder()
	{
		switch (XenForo_Application::get('options')->siropu_ads_manager_package_order)
		{
			case 'order':
				return 'display_order ASC';
				break;
			case 'slots':
			default:
				return 'adCount ASC';
				break;
		}
	}
}

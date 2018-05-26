<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Ads extends Xenforo_Model
{
	public function getAllAds($status = '', $search = array(), $orderBy = array())
	{
		$fields = array(
			'ad_id',
			'package_id',
			'user_id',
			'username',
			'type',
			'items',
			'is_placeholder',
			'subscription',
			'status_old',
			'status'
		);

		$db    = $this->_getDb();
		$where = $status ? 'WHERE a.status = ' . $db->quote($status) : '';

		if ($search)
		{
			$i = 0;

			foreach ($search as $field => $val)
			{
				if ($val && in_array($field, $fields))
				{
					$where .= ($i != 0 || $status) ? ' AND ' : ' WHERE ';
					$where .= "a.{$field} = {$db->quote($val)}";

					$i++;
				}
			}
		}

		$order = 'date_created asc';

		if ($orderBy)
		{
			$orderOptions = array('name', 'ad_order', 'date_created', 'date_end', 'view_count', 'click_count', 'ctr');

			if (in_array($orderBy['field'], $orderOptions))
			{
				$order = $orderBy['field'] . (($orderBy['dir'] == 'asc') ? ' asc' : ' desc');
			}
		}

		return $this->fetchAllKeyed('
			SELECT a.*, p.package_id, p.name AS package_name, p.cost_per, p.ads_order
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id '
			. $where .
			' ORDER BY a.' . $order
		, 'ad_id');
	}
	public function getAdById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_ads
			WHERE ad_id = ?
			', $id);
	}
	public function getAdsByIds($ids)
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_ads_manager_ads
			WHERE ad_id IN (' . implode(',', array_map('intval', $ids)) . ') ',
			'ad_id');
	}
	public function getAdJoinPackageById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT a.*, p.package_id, p.name AS package_name, p.cost_amount, p.cost_currency, p.cost_per, p.cost_list, p.min_purchase, p.discount, p.style, p.max_items_allowed
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE ad_id = ?
			', $id);
	}
	public function getQueuedAds()
	{
		return $this->_getDb()->fetchAll('
			SELECT a.*, p.package_id, p.name AS package_name, p.cost_amount, p.cost_currency, p.cost_per, p.cost_list, p.discount, p.max_items_allowed
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE a.status = "Queued"
			ORDER BY a.date_created ASC');
	}
	public function getAdCountByPackageId($packageId)
	{
		return $this->_getDb()->fetchRow('
			SELECT
				COUNT(CASE WHEN status = "Active" THEN 1 ELSE NULL END) AS Active,
				COUNT(CASE WHEN status = "Pending" THEN 1 ELSE NULL END) AS Pending,
				COUNT(CASE WHEN status = "Paused" THEN 1 ELSE NULL END) AS Paused,
				COUNT(CASE WHEN status_old = "Active" THEN 1 ELSE NULL END) AS oldActive,
				COUNT(CASE WHEN status_old = "Paused" THEN 1 ELSE NULL END) AS oldPaused
			FROM xf_siropu_ads_manager_ads
			WHERE package_id = ?
			AND count_exclude = 0
			', $packageId);
	}
	public function getPendingStickyAdsThreadIds()
	{
		$resultArray = $this->_getDb()->fetchAll('
			SELECT items
			FROM xf_siropu_ads_manager_ads
			WHERE status IN ("Pending", "Approved", "Queued")
			AND type = "sticky"');

		$ids = array();
		foreach ($resultArray as $row)
		{
			$ids[] = (int) $row['items'];
		}
		return $ids;
	}
	public function getPendingTransactionAdCount($packageId)
	{
		$db = $this->_getDb();
		$result = $db->fetchRow('
			SELECT COUNT(*) AS adCount
			FROM xf_siropu_ads_manager_ads
			WHERE pending_transaction = 1
			AND status != "Active"
			AND package_id = ' . $db->quote($packageId));

		return $result['adCount'];
	}
	public function getActiveAdsForDisplay($returnQuery = false, $item = array(), $itemCount = 0)
	{
		if ($resultArray = XenForo_Application::getSimpleCacheData('activeAdsForDisplay'))
		{
			return $returnQuery ? $resultArray : Siropu_AdsManager_Helper_General::groupAdsByHookAndType($resultArray, $item, $itemCount);
		}

		$resultArray = $this->_getDb()->fetchAll('
			SELECT
				a.*,
				p.package_id,
				p.name AS package_name,
				p.advertise_here,
				p.style,
				p.max_items_allowed,
				p.max_items_display,
				p.cost_amount,
				p.cost_currency,
				p.cost_per,
				p.ads_order,
				p.js_rotator,
				p.js_interval
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE a.type != "sticky"
			AND a.status = "Active"
			ORDER BY a.ad_order ASC');

		XenForo_Application::setSimpleCacheData('activeAdsForDisplay', $resultArray ? $resultArray : array(0));
		return $returnQuery ? $resultArray : Siropu_AdsManager_Helper_General::groupAdsByHookAndType($resultArray, $item, $itemCount);
	}
	public function getKeywordAds($adId = 0)
	{
		$db = $this->_getDb();
		return $db->fetchAll('
			SELECT ad_id, items, date_last_active
			FROM xf_siropu_ads_manager_ads
			WHERE type = "keyword"
			AND status != "Rejected" ' .
			($adId ? 'AND ad_id != ' . $db->quote($adId) : ''));
	}
	public function getAdsForCache()
	{
		if ($resultArray = XenForo_Application::getSimpleCacheData('adsForCache'))
		{
			return $resultArray;
		}

		$resultArray = $this->_getDb()->fetchAll('
			SELECT user_id
			FROM xf_siropu_ads_manager_ads
			WHERE status IN ("Active", "Inactive", "Approved")');

		XenForo_Application::setSimpleCacheData('adsForCache', $resultArray);
		return $resultArray;
	}
	public function updateAdsByPackageId($data, $packageId, $bypass)
	{
		$db = $this->_getDb();
		$condition = 'package_id = ' . $db->quote($packageId) . ($bypass ? '' : ' AND inherit_settings = 1');
		$db->update('xf_siropu_ads_manager_ads', $data, $condition);
	}
	public function changeAdStatus($adId, $status)
	{
		$db = $this->_getDb();
		$db->update('xf_siropu_ads_manager_ads', array('status' => $status), 'ad_id = ' . $db->quote($adId));
	}
	public function deleteAd($adId)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_ads_manager_ads', 'ad_id = ' . $db->quote($adId));
	}
	public function deleteAdsByPackageId($packageId)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_ads_manager_ads', 'package_id = ' . $db->quote($packageId));
	}
	public function deletePlaceholdersByPackageIds($packageIds)
	{
		$this->_getDb()->delete('xf_siropu_ads_manager_ads', 'is_placeholder = 1 AND package_id IN (' . implode(',', $packageIds) . ')');
	}
	public function getAdvertiserAds()
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_ads
			WHERE purchase <> 0
		');
	}
	public function getAllAdvertisers()
	{
		return $this->_getDb()->fetchAll('
			SELECT user_id, username
			FROM xf_siropu_ads_manager_ads
			GROUP BY user_id
			ORDER BY username ASC
		');
	}
	public function getUserDataForEmail($input)
	{
		$db = $this->_getDb();

		$where = '';
		if (!empty($input['package']))
		{
			$where .= 'WHERE a.package_id IN (' . implode(',', $input['package']) . ')';
		}
		if (!empty($input['status']))
		{
			$statusList = array();
			foreach ($input['status'] as $status)
			{
				$statusList[] = $db->quote($status);
			}

			$where .= ($where ? ' AND' : 'WHERE') . ' a.status IN (' . implode(',', $statusList) . ')';
		}
		if (!empty($input['username']))
		{
			$usernameList = array();
			foreach ($input['username'] as $username)
			{
				$usernameList[] = $db->quote($username);
			}

			$where .= ($where ? ' AND' : 'WHERE') . ' a.username IN (' . implode(',', $usernameList) . ')';
		}

		return $db->fetchAll('
			SELECT
				a.username,
				u.email,
				u.language_id
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_user u ON u.user_id = a.user_id
			' . $where . '
			GROUP BY a.user_id
		');
	}
	public function getPlaceholders()
	{
		return $this->_getDb()->fetchAll('
			SELECT a.*, p.name as package_name
			FROM xf_siropu_ads_manager_ads AS a
			LEFT JOIN xf_siropu_ads_manager_packages AS p
			ON p.package_id = a.package_id
			WHERE a.is_placeholder = 1
			ORDER BY p.name DESC
		');
	}
	public function packagePlaceholderExists($packageId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_ads
			WHERE package_id = ?
			AND is_placeholder = 1
		', $packageId);
	}
	public function getActiveAdsForEmbed($search = array())
	{
		$where = array();

		if (!empty($search['ad_id']))
		{
			$where[] = ' AND a.ad_id = ' . $this->_getDb()->quote($search['ad_id']);
		}
		if (!empty($search['package_id']))
		{
			$where[] = ' AND p.package_id = ' . $this->_getDb()->quote($search['package_id']);
		}

		return $this->_getDb()->fetchAll('
			SELECT
				a.*,
				p.package_id,
				p.name AS package_name,
				p.advertise_here,
				p.style,
				p.max_items_allowed,
				p.max_items_display,
				p.cost_amount,
				p.cost_currency,
				p.cost_per,
				p.ads_order,
				p.js_rotator,
				p.js_interval
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE a.status = "Active"
			' . ($where ? implode("\n", $where) : '') . '
			ORDER BY a.ad_order ASC');
	}
	public function getActiveAdvertisersAds()
	{
		$order = XenForo_Application::get('options')->siropu_ads_manager_advertisers_order;

		switch ($order['field'])
		{
			case 'ctr':
			default:
				$field = 'ctr';
				break;
			case 'views':
				$field = 'view_count';
				break;
			case 'clicks':
				$field = 'click_count';
				break;
			case 'order':
				$field = 'ad_order';
				break;
			case 'date':
				$field = 'date_created';
				break;
		}

		switch ($order['direction'])
		{
			case 'asc':
				$direction = 'ASC';
				break;
			case 'desc':
				$direction = 'DESC';
				break;
			case 'rand':
			default:
				$direction = 'RAND()';
				break;
		}

		if ($direction == 'RAND()')
		{
			$field = '';
		}

		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_ads
			WHERE purchase <> 0
			AND type IN ("code", "banner", "text", "link")
			AND status = "Active"
			ORDER BY ' . $field . ' ' . $direction);
	}
	public function approveAd($adId)
	{
		$adsModel = XenForo_Model::create('Siropu_AdsManager_Model_Ads');
		$transactionsModel = XenForo_Model::create('Siropu_AdsManager_Model_Transactions');

		if ($ad = $adsModel->getAdJoinPackageById($adId))
		{
		     $dwData = array();

		     if ($ad['status_old'])
		     {
		          $dwData = array(
		               'status_old' => '',
		               'status'     => $ad['status_old']
		          );

		          if ($ad['status_old'] == 'Active')
		          {
		               $dwData['date_active']      = time();
		               $dwData['date_last_active'] = 0;

		               if ($ad['date_end'])
		               {
		                    $dwData['date_end'] = $ad['date_end'] + (time() - $ad['date_last_active']);
		               }
		          }
		     }
		     else if ($ad['status'] == 'Pending' && $ad['cost_amount'] > 0)
		     {
		          if (Siropu_AdsManager_Helper_General::checkForAvailableAdSlots($ad))
		          {
		               $transactionsModel->generateTransaction($ad);
		               $adsModel->changeAdStatus($ad['ad_id'], 'Approved');

		               $dwData = array(
		                    'status'              => 'Approved',
		                    'pending_transaction' => 1
		               );

		               Siropu_AdsManager_Helper_General::sendEmailNotification('siropu_ads_manager_ad_approved', array(
		                    'name'  => $ad['name'],
		                    'limit' => XenForo_Application::get('options')->siropu_ads_manager_transaction_time_limit,
		                    'url'   => XenForo_Link::buildPublicLink('canonical:advertising/invoices')), $ad['user_id']);

		               XenForo_Model_Alert::alert(
		                    $ad['user_id'],
		                    $ad['user_id'],
		                    $ad['username'],
		                    'siropu_ads_manager',
		                    $ad['ad_id'],
		                    'pending_invoice'
		               );
		          }
		          else
		          {
		               $dwData['status'] = 'Queued';

		               Siropu_AdsManager_Helper_General::sendEmailNotification('siropu_ads_manager_ad_queued', array(
		                    'name' => $ad['name']), $ad['user_id']);
		          }
		     }
		     else
		     {
		          switch ($ad['cost_per'])
		          {
		               case 'CPM':
		                    $dwData['view_limit'] = $ad['purchase'];
		                    break;
		               case 'CPC':
		                    $dwData['click_limit'] = $ad['purchase'];
		                    break;
		               default:
		                    $dwData['date_end'] = strtotime("+{$ad['purchase']} {$ad['cost_per']}");
		                    break;
		          }

		          $dwData['date_active'] = time();
		          $dwData['status']      = 'Active';
		     }

		     $dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		     $dw->setExistingData($ad['ad_id']);
		     $dw->bulkSet($dwData);
		     $dw->save();
		}
	}
}

<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_User extends Xenforo_Model
{
	public function getAllUserAds($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT a.*, p.name AS package_name, p.cost_per
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE a.package_id != 0
			AND a.user_id = ?
			ORDER BY a.status ASC, a.date_created ASC'
			, $userID);
	}
	public function getUserAdById($adID, $userID)
	{
		$db = $this->_getDb();

		return $db->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_ads
			WHERE ad_id = ' . $db->quote($adID) . '
			AND user_id = ' . $db->quote($userID));
	}
	public function getUserAdJoinPackageById($adID, $userID)
	{
		$db = $this->_getDb();

		return $db->fetchRow('
			SELECT a.*, p.min_purchase, p.max_purchase, p.cost_amount, p.cost_currency, p.cost_per, p.cost_list, p.discount, p.max_items_allowed
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE a.ad_id = ' . $db->quote($adID) . '
			AND a.user_id = ' . $db->quote($userID));
	}
	public function getUserAdsById($adIDs, $userID)
	{
		$IDs = array();

		foreach ($adIDs as $id)
		{
			$IDs[] = (int) $id;
		}

		$db = $this->_getDb();

		return $db->fetchAll('
			SELECT a.ad_id, a.type, a.name, a.purchase, a.items, p.cost_amount, p.cost_currency, p.cost_per, p.cost_list, p.discount
			FROM xf_siropu_ads_manager_ads a
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE a.ad_id IN (' . implode(',', $IDs) . ')
			AND a.user_id = ' . $db->quote($userID));
	}
	public function getUserForumThreads($nodeID, $userID)
	{
		$db = $this->_getDb();

		return $db->fetchAll('
			SELECT thread_id, title
			FROM xf_thread
			WHERE node_id = ' . $db->quote($nodeID) . '
			AND user_id = ' . $db->quote($userID) . '
			AND discussion_open = 1
			ORDER BY post_date DESC, reply_count DESC');
	}
	public function getAllUserTransactions($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE user_id = ?
			ORDER BY status ASC, date_completed DESC
		', $userID);
	}
	public function getUserTransactionById($id, $userID)
	{
		$db = $this->_getDb();

		return $db->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE transaction_id = ' . $db->quote($id) . '
			AND user_id = ' . $db->quote($userID));
	}
	public function getUserTransactionJoinAdJoinPackageById($id, $userID)
	{
		$db = $this->_getDb();

		return $db->fetchRow('
			SELECT t.*, a.name, a.purchase, a.extend, p.name AS package_name, p.cost_per
			FROM xf_siropu_ads_manager_transactions t
			LEFT JOIN xf_siropu_ads_manager_ads a
			ON a.ad_id = t.ad_id
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE t.transaction_id = ' . $db->quote($id) . '
			AND t.user_id = ' . $db->quote($userID));
	}
	public function getUserTransactionsByIds($transactionIds, $userID)
	{
		$IDs = array();

		foreach ($transactionIds as $id)
		{
			$IDs[] = (int) $id;
		}

		$db = $this->_getDb();

		return $db->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE transaction_id IN (' . implode(',', $IDs) . ')
			AND user_id = ' . $db->quote($userID));
	}
	public function getUserPendingTransactions($userID)
	{
		$userAds = array();

		if ($userID)
		{
			foreach ($this->_getAdsModel()->getAdsForCache() as $row)
			{
				$userAds[$row['user_id']] = true;
			}
		}

		$pending = array();

		if (isset($userAds[$userID]))
		{
			$db = $this->_getDb();

			return $db->fetchAll('
				SELECT *
				FROM xf_siropu_ads_manager_transactions
				WHERE user_id = ' . $db->quote($userID) . '
				AND status = "Pending"
				ORDER BY date_generated DESC
			');
		}
	}
	public function getUserExpiringAds($userID)
	{
		$userAds = array();

		if ($userID)
		{
			foreach ($this->_getAdsModel()->getActiveAdsForDisplay(true) as $row)
			{
				$userAds[$row['user_id']] = true;
			}
		}

		$expiring = array();

		if (isset($userAds[$userID]))
		{
			foreach ($this->getAllUserAds($userID) as $row)
			{
				if ($row['status'] == 'Active' && Siropu_AdsManager_Helper_General::adIsExpiring($row))
				{
					$row['expiring'] = Siropu_AdsManager_Helper_General::calculateAdTimeActionLeft($row);
					$expiring[] = $row;
				}
			}
		}

		return $expiring;
	}
	public function getUserStickyAds($userID)
	{
		$resultArray = $this->_getDb()->fetchAll('
			SELECT items
			FROM xf_siropu_ads_manager_ads
			WHERE type = "sticky"
			AND user_id = ?'
			, $userID);
			
		$list = array();

		foreach ($resultArray as $row)
		{
			$list[] = (int) $row['items'];
		}

		return $list;
	}
	public function getUserFeaturedAds($userID)
	{
		$resultArray = $this->_getDb()->fetchAll('
			SELECT items
			FROM xf_siropu_ads_manager_ads
			WHERE type = "featured"
			AND user_id = ?'
			, $userID);
			
		$list = array();

		foreach ($resultArray as $row)
		{
			$list[] = (int) $row['items'];
		}

		return $list;
	}
	public function getUserPromoCodeUsageCount($userID, $promoCode)
	{
		$db = $this->_getDb();

		$result = $db->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_ads_manager_transactions
			WHERE user_id = ' . $db->quote($userID) . '
			AND promo_code = ' . $db->quote($promoCode)); 

		return $result['count'];
	}
	public function userHasValidAds($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT items
			FROM xf_siropu_ads_manager_ads
			WHERE (status IN ("Active", "Paused") OR status_old IN ("Active", "Paused"))
			AND user_id = ?'
			, $userID);
	}
	public function changeUserGroups($userID, $action = 'add')
	{
		$userGroups = $this->_getOptions()->siropu_ads_manager_advertiser_user_groups;

		if (!empty($userGroups['user_group_ids']) && ($user = $this->_getUsersModel()->getUserById($userID)))
		{
			foreach ($userGroups['user_group_ids'] as $id)
			{
				$isMemberOf = XenForo_Template_Helper_Core::helperIsMemberOf($user, $id);

				if ($action == 'add' && !$isMemberOf)
				{
					$this->_getUsersModel()->addUserGroupChange($userID, "sam_group_$id", $id);
				}
				if ($action == 'remove' && $isMemberOf && !$this->userHasValidAds($userID))
				{
					$this->_getUsersModel()->removeUserGroupChange($userID, "sam_group_$id");
				}
			}
		}
	}
	protected function _getAdsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Ads');
	}
	protected function _getUsersModel()
	{
		return XenForo_Model::create('XenForo_Model_User');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}
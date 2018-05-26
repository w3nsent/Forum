<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_CronEntry
{
	public static function run()
    {
		self::checkForExpiringAds();
		self::checkForUncompletedTransactions();
		self::checkForQueuedAds();
		self::deleteStatsOlderThan();
	}
	public static function checkForExpiringAds()
    {
		$lowCtrAction = self::_getOptions()->siropu_ads_manager_disable_low_ctr;
		$count        = 0;

		foreach (self::_getAdsModel()->getAllAds('', array('subscription' => 0)) as $row)
		{
			$adExpired = false;

			if ($row['date_start'] && $row['date_start'] <= time())
			{
				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($row['ad_id']);
				$dw->set('date_start', 0);
				$dw->set('date_active', time());
				if ($row['status'] == 'Paused' && ($end = $row['date_end']))
				{
					$dw->set('date_end', $end + (time() - $row['date_last_active']));
				}
				$dw->set('status', 'Active');
				$dw->save();

				$count++;
			}

			$url = XenForo_Link::buildPublicLink('canonical:advertising/ads/extend', '', array('id' => $row['ad_id']));

			if ($row['status'] == 'Inactive' && $row['type'] == 'sticky')
			{
				self::_getThreadsModel()->toggleStickyThreadById('Inactive', $row['items']);
			}

			if ($row['status'] == 'Active' && $row['date_end'] && $row['date_end'] <= time())
			{
				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($row['ad_id']);
				$dw->set('date_end', 0);
				$dw->set('date_active', 0);
				$dw->set('date_last_active', time());
				$dw->set('notice_sent', 0);
				$dw->set('status', 'Inactive');
				$dw->save();

				if ($row['type'] == 'sticky')
				{
					self::_getThreadsModel()->toggleStickyThreadById('Inactive', $row['items'], true);
				}

				if ($row['type'] == 'featured')
				{
					$resource = self::_getResourceModel()->getResourceById($row['items']);
					self::_getResourceModel()->unfeatureResource($resource);
				}

				if ($row['email_notifications'])
				{
					Siropu_AdsManager_Helper_General::sendEmailNotification('siropu_ads_manager_ad_expired', array(
						'name' => $row['name'],
						'url'  => $url), $row['user_id']);
				}

				if ($row['alert_notifications'])
				{
					XenForo_Model_Alert::alert(
						$row['user_id'],
						$row['user_id'],
						$row['username'],
						'siropu_ads_manager',
						$row['ad_id'],
						'ad_expired'
					);
				}

				self::_getUserModel()->changeUserGroups($row['user_id'], 'remove');

				$adExpired = true;
				$count++;
			}

			if ($row['status'] == 'Active'
				&& !$row['purchase']
				&& $row['count_views']
				&& $row['count_clicks']
				&& $lowCtrAction['enabled']
				&& $row['ctr'] < $lowCtrAction['ctr_lower_than']
				&& $row['date_active'] <= strtotime("-{$lowCtrAction['time_frame']} Days"))
			{
				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($row['ad_id']);
				$dw->set('date_active', 0);
				$dw->set('date_last_active', time());
				$dw->set('status', 'Inactive');
				$dw->save();

				$count++;
			}

			if (in_array($row['ads_order'], array('ctrAsc', 'ctrDesc')))
			{
				$count++;
			}

			if ($row['status'] == 'Active'
				&& ($row['email_notifications'] || $row['alert_notifications'])
				&& !$row['notice_sent']
				&& Siropu_AdsManager_Helper_General::adIsExpiring($row)
				&& !$adExpired)
			{
				if ($row['email_notifications'])
				{
					Siropu_AdsManager_Helper_General::sendEmailNotification('siropu_ads_manager_ad_expiring', array(
						'name' => $row['name'],
						'url'  => $url), $row['user_id']);
				}

				if ($row['alert_notifications'])
				{
					XenForo_Model_Alert::alert(
						$row['user_id'],
						$row['user_id'],
						$row['username'],
						'siropu_ads_manager',
						$row['ad_id'],
						'ad_expiring'
					);
				}

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($row['ad_id']);
				$dw->set('notice_sent', 1);
				$dw->save();
			}
		}

		if ($count)
		{
			Siropu_AdsManager_Helper_General::refreshActiveAdsCache();
		}
	}
	public static function checkForQueuedAds()
	{
		foreach (self::_getAdsModel()->getQueuedAds() as $row)
		{
			if (Siropu_AdsManager_Helper_General::checkForAvailableAdSlots($row))
			{
				self::_getTransactionsModel()->generateTransaction($row);

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($row['ad_id']);
				$dw->set('status', 'Approved');
				$dw->set('pending_transaction', 1);
				$dw->save();

				Siropu_AdsManager_Helper_General::sendEmailNotification('siropu_ads_manager_ad_slot_available', array(
					'name'  => $row['name'],
					'limit' => self::_getOptions()->siropu_ads_manager_transaction_time_limit,
					'url'   => XenForo_Link::buildPublicLink('canonical:advertising/invoices')), $row['user_id']);
			}
		}
	}
	public static function checkForUncompletedTransactions()
	{
		if (!$timeLimit = self::_getOptions()->siropu_ads_manager_transaction_time_limit)
		{
			return false;
		}

		foreach (self::_getTransactionsModel()->getTransactionsByStatus('Pending') as $row)
		{
			if ($row['date_generated'] <= strtotime("-{$timeLimit} Hours"))
			{
				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
				$dw->setExistingData($row['transaction_id']);
				$dw->set('cost_amount_btc', 0);
				$dw->set('status', 'Cancelled');
				$dw->save();

				if (($ad = self::_getAdsModel()->getAdById($row['ad_id'])) && $ad['status'] != 'Active')
				{
					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($row['ad_id']);
					$dw->set('status', 'Rejected');
					$dw->set('pending_transaction', 0);
					$dw->save();
				}
			}
		}
	}
	public static function deleteStatsOlderThan()
	{
		if ($days = self::_getOptions()->siropu_ads_manager_delete_stats_older_than_x)
		{
			self::_getStatsModel()->deleteStatsOlderThan(strtotime("-{$days} Days"));
		}
	}
	protected static function _getPackagesModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Packages');
	}
	protected static function _getAdsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Ads');
	}
	protected static function _getStatsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Stats');
	}
	protected static function _getUserModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_User');
	}
	protected static function _getTransactionsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Transactions');
	}
	protected static function _getThreadsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Threads');
	}
	protected static function _getResourceModel()
	{
		return XenForo_Model::create('XenResource_Model_Resource');
	}
	protected static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}

<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Transactions extends Xenforo_Model
{
	public function getAllTransactions($search = array())
	{
		$db     = $this->_getDb();
		$fields = array('username', 'payment_txn_id', 'status', 'ad_id');
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
			FROM xf_siropu_ads_manager_transactions '
			. $where .
			' ORDER BY date_generated ASC');
	}
	public function getTransactionsByStatus($status)
	{
		$db = $this->_getDb();
		return $db->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE status = ' . $db->quote($status));
	}
	public function getTransactionById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE transaction_id = ?
			', $id);
	}
	public function getTransactionsByIds($IDs)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE transaction_id IN (' . implode(',', $IDs) . ')');
	}
	public function getPendingTransactionByAdId($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_transactions
			WHERE ad_id = ?
			AND status = "Pending"
			', $id);
	}
	public function getTransactionJoinAdsById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_ads_manager_transactions t
			LEFT JOIN xf_siropu_ads_manager_ads a
			ON a.ad_id = t.ad_id
			WHERE t.transaction_id = ?
			', $id);
	}
	public function getTransactionJoinAdsJoinPackagesById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT t.*, a.ad_id, a.type, a.name, a.items, a.date_end, a.view_limit, a.view_count, a.click_limit, a.click_count, a.purchase, a.extend, a.notice_sent, a.pending_transaction, a.status AS ad_status, p.package_id, p.name AS package_name, p.cost_per
			FROM xf_siropu_ads_manager_transactions t
			LEFT JOIN xf_siropu_ads_manager_ads a
			ON a.ad_id = t.ad_id
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE t.transaction_id = ?
			', $id);
	}
	public function getTransactionsJoinAdsJoinPackagesByIds($IDs)
	{
		return $this->_getDb()->fetchAll('
			SELECT t.*, a.ad_id, a.type, a.name, a.items, a.date_end, a.view_limit, a.view_count, a.click_limit, a.click_count, a.purchase, a.extend, a.notice_sent, a.pending_transaction, a.status AS ad_status, p.package_id, p.name AS package_name, p.cost_per
			FROM xf_siropu_ads_manager_transactions t
			LEFT JOIN xf_siropu_ads_manager_ads a
			ON a.ad_id = t.ad_id
			LEFT JOIN xf_siropu_ads_manager_packages p
			ON p.package_id = a.package_id
			WHERE t.transaction_id IN (' . implode(',', $IDs) . ')');
	}
	public function getTransactionCountByAdId($adId)
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_ads_manager_transactions
			WHERE ad_id = ? '
			, $adId);

		return $result['count'];
	}
	public function generateTransaction($aData, $tData = array())
	{
		$cost = Siropu_AdsManager_Helper_General::calculateAdCost($aData);

		if ($cost['discounted'] > 0)
		{
			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
			$dw->bulkSet(array_merge(array(
				'ad_id'            => $aData['ad_id'],
				'user_id'          => $aData['user_id'],
				'username'         => $aData['username'],
				'cost_amount'      => $cost['discounted'],
				'cost_currency'    => $cost['currency'],
				'discount_percent' => $cost['percent'],
				'discount_amount'  => $cost['discount'],
			), $tData));
			$dw->save();
		}
	}
	public function processTransaction($data, $status, $paymentMethod, $txnId)
	{
		if ($data['status'] == 'Completed' && $status == 'Completed')
		{
			return;
		}

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
		$dw->setExistingData($data['transaction_id']);
		$dw->set('status', $status);
		if ($status == 'Completed')
		{
			$dw->set('date_completed', time());
			if ($paymentMethod)
			{
				$dw->set('payment_method', $paymentMethod);
			}
			if ($txnId)
			{
				$dw->set('payment_txn_id', $txnId);
			}

			Siropu_AdsManager_Helper_General::sendEmailNotification('siropu_ads_manager_invoice_completed', array(
				'id' => $data['transaction_id']), $data['user_id']);
		}
		$dw->save();

		if (!$data['ad_id'])
		{
			return false;
		}

		$dwData = array();
		$extend = $data['extend'];

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		$dw->setExistingData($data['ad_id']);
		if ($status == 'Completed' && $data['status'] == 'Pending')
		{
			$purchase = $extend ? $extend : $data['purchase'];
			$costPer  = $data['cost_per'];

			switch ($data['ad_status'])
			{
				case 'Approved':
				case 'Inactive':
					switch ($costPer)
					{
						case 'CPM':
							$dwData['view_limit'] = $purchase;
							break;
						case 'CPC':
							$dwData['click_limit'] = $purchase;
							break;
						default:
							$dwData['date_end'] = strtotime("+{$purchase} {$costPer}");
							break;
					}
					$dwData['date_active'] = time();
					$dwData['status'] = 'Active';
					break;
				case 'Active':
					switch ($costPer)
					{
						case 'CPM':
							$dwData['view_limit'] = $data['view_limit'] + $extend;
							break;
						case 'CPC':
							$dwData['click_limit'] = $data['click_limit'] + $extend;
							break;
						default:
							$date_end = strtotime("+{$extend} {$costPer}") + ($data['date_end'] - time());
							$dwData['date_end'] = $date_end;
							break;
					}
					break;
				
			}
		}
		else if ($status == 'Cancelled' || $status == 'Pending')
		{
			$dwData['date_active'] = 0;
			$dwData['status'] = 'Inactive';
		}

		if ($data['notice_sent'])
		{
			$dwData['notice_sent'] = 0;
		}

		if ($data['pending_transaction'])
		{
			$dwData['pending_transaction'] = 0;
		}

		if ($extend)
		{
			$dwData['purchase'] = $extend;
			$dwData['extend'] = 0;
		}

		$dw->BulkSet($dwData);
		$dw->save();

		if ($data['type'] == 'sticky')
		{
			XenForo_Model::create('Siropu_AdsManager_Model_Threads')->toggleStickyThreadById($status, $data['items']);
		}

		if ($data['type'] == 'featured')
		{
			$resourceModel = XenForo_Model::create('XenResource_Model_Resource');
			$resource = $resourceModel->getResourceById($data['items']);

			if ($status == 'Completed')
			{
				$resourceModel->featureResource($resource);
			}
			else
			{
				$resourceModel->unfeatureResource($resource);
			}
		}
	}
	public function deleteTransactionsByAdId($adId)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_ads_manager_transactions', 'ad_id = ' . $db->quote($adId));
	}
}
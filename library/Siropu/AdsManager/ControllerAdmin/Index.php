<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Index extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$ads = $this->_getAdsModel()->getAllAds();

		$pendingInvoices   = $this->_getTransactionsModel()->getAllTransactions(array('status' => 'Pending'));
		$completedInvoices = $this->_getTransactionsModel()->getAllTransactions(array('status' => 'Completed'));
		$cancelledInvoices = $this->_getTransactionsModel()->getAllTransactions(array('status' => 'Cancelled'));

		$pendingCount      = count($pendingInvoices);
		$completedCount    = count($completedInvoices);
		$cancelledCount    = count($cancelledInvoices);

		$viewParams = array(
			'ads'                    => $ads,
			'pendingAdList'          => $this->_getHelperGeneral()->getAdsByStatus($ads, 'Pending'),
			'expiringAdList'         => $this->_getHelperGeneral()->getAdsExpiring($ads),
			'pendingTransactionList' => $pendingInvoices,
			'pendingTransactionVal'  => $this->_getHelperGeneral()->getTransactionsRevenue($pendingInvoices, 'Pending'),
			'typeList'               => $this->_getHelperGeneral()->getAdTypes(),
			'adStatusCount'          => $this->_getHelperGeneral()->groupAdsByStatus($ads),
			'adTypeCount'            => $this->_getHelperGeneral()->groupAdsByType($ads),
			'topPerformingAds'       => $this->_getHelperGeneral()->getTopPerformingAds($ads),
			'topPerformingPackages'  => $this->_getPackagesModel()->getTopPerformingPackages(),
			'totalSubscriptionCount' => $this->_getSubscriptionsModel()->getAllSubscriptionsCount(),
			'invoiceTotalCount'      => $pendingCount + $completedCount + $cancelledCount,
			'invoiceTotalRevenue'    => $this->_getHelperGeneral()->getTransactionsRevenue($completedInvoices),
			'invoicePendingCount'    => $pendingCount,
			'invoiceCompletedCount'  => $completedCount,
			'invoiceCancelledCount'  => $cancelledCount,
		);

		return $this->responseView('', 'siropu_ads_manager', $viewParams);
	}
	public function actionHelp()
	{
		return $this->responseView('', 'siropu_ads_manager_help');
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getPositionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _getPackagesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Packages');
	}
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getSubscriptionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Subscriptions');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
}

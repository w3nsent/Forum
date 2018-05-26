<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Subscriptions extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$search   = $this->_input->filterSingle('search', XenForo_Input::ARRAY_SIMPLE);
		$packages = $this->_getPackagesModel()->getAllPackages();

		$viewParams = array(
			'subscriptions' => $this->_getSubscriptionsModel()->getAllSubscriptions($search),
			'packageList'   => $packages,
			'statusList'    => $this->_getHelperGeneral()->getStatusList(),
			'search'        => $search
		);

		return $this->responseView('', 'siropu_ads_manager_subscription_list', $viewParams);
	}
	public function actionDetails()
	{
		$viewParams = array(
			'subscription' => $this->_getSubscriptionsModel()->getSubscriptionJoinAdsJoinPackagesById($this->_getID())
		);

		return $this->responseView('', 'siropu_ads_manager_subscription_details', $viewParams);
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
	protected function _getSubscriptionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Subscriptions');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getID()
	{
		return $this->_input->filterSingle('subscription_id', XenForo_Input::UINT);
	}
}
<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Easy User Ban Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerPublic_Thread extends XFCP_Siropu_AdsManager_ControllerPublic_Thread
{
	public function actionSave()
    {
		if ($this->_isDissallowedAction() && ($error = $this->_validateAction()))
		{
			return $this->responseError($error);
		}

		return parent::actionSave();
	}
	public function actionQuickUpdate()
    {
		if ($this->_isDissallowedAction() && ($error = $this->_validateAction()))
		{
			return $this->responseError($error);
		}

		return parent::actionQuickUpdate();
	}
	public function actionMove()
	{
		if ($error = $this->_validateAction())
		{
			return $this->responseError($error);
		}

		return parent::actionMove();
	}
	public function actionDelete()
	{
		if ($error = $this->_validateAction())
		{
			return $this->responseError($error);
		}

		return parent::actionDelete();
	}
	protected function _isDissallowedAction()
	{
		$input = $this->_input->filter(array(
			'discussion_state' => XenForo_Input::STRING,
			'discussion_open'  => XenForo_Input::UINT,
			'sticky'           => XenForo_Input::UINT
		));

		if ($input['discussion_state'] != 'visible' || !$input['discussion_open'] || !$input['sticky'])
		{
			return true;
		}
	}
	protected function _validateAction()
	{
		if (!XenForo_Visitor::getInstance()->is_admin && $this->getModelFromCache('Siropu_AdsManager_Model_Ads')->getAllAds('Active', array('type' => 'sticky', 'items' => $this->_input->filterSingle('thread_id', XenForo_Input::UINT))))
		{
			return new XenForo_Phrase('siropu_ads_manager_active_sticky_ad_moderator_action_error');
		}
	}
}
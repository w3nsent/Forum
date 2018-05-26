<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Easy User Ban Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerPublic_InlineModThread extends XFCP_Siropu_AdsManager_ControllerPublic_InlineModThread
{
	public function actionUnstick()
	{
		if ($error = $this->_validateAction())
		{
			return $this->responseError($error);
		}

		return parent::actionUnstick();
	}
	public function actionLock()
	{
		if ($error = $this->_validateAction())
		{
			return $this->responseError($error);
		}

		return parent::actionLock();
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
	protected function _validateAction()
	{
		if (!XenForo_Visitor::getInstance()->is_admin && ($ads = $this->getModelFromCache('Siropu_AdsManager_Model_Ads')->getAllAds('Active', array('type' => 'sticky'))))
		{
			$threadIds = $this->getInlineModIds(false);
			$foundIds  = $threadTitles = array();

			foreach ($ads as $ad)
			{
				if (in_array($ad['items'], $threadIds))
				{
					$foundIds[] = $ad['items'];
				}
			}

			if ($foundIds)
			{
				foreach ($this->getModelFromCache('XenForo_Model_Thread')->getThreadsByIds($foundIds) as $thread)
				{
					$threadTitles[] = $thread['title'];
				}

				return new XenForo_Phrase('siropu_ads_manager_active_sticky_ad_inline_moderator_action_error',
					array('titles' => implode("\n", $threadTitles)));
			}
		}
	}
}
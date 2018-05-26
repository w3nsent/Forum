<?php

class BS_BRMSStick_ControllerPublic_Thread extends XFCP_BS_BRMSStick_ControllerPublic_Thread
{	
	public function actionIndex()
	{
		$parent = parent::actionIndex();

		if ($parent instanceof XenForo_ControllerResponse_View)
		{
			$threadId = $parent->params['thread']['thread_id'];	
			$parent->params['thread']['brmsStick'] = $this->_getBSModel()->getStickStatus($threadId);
		}	

		return $parent;	
	}

	public function actionUnstick()
	{
		$this->_assertPostOnly();
		$this->_assertPermissions();
	
		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		$thread = $this->_getThreadModel()->getThreadById($threadId);
		$stick = $this->_getBSModel()->getStickStatus($threadId);

		if ($stick)
		{
			$dw = XenForo_DataWriter::create('BS_BRMSStick_DataWriter_Thread');
			$dw->setExistingData($threadId);
			$dw->set('brms_stick', 0);
			$dw->save();
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('threads', $thread)
			);	
	}

	public function actionStick()
	{
		$this->_assertPostOnly();
        $this->_assertPermissions();

		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		$thread = $this->_getThreadModel()->getThreadById($threadId);
		$stick = $this->_getBSModel()->getStickStatus($threadId);

		if (!$stick)
		{
			$dw = XenForo_DataWriter::create('BS_BRMSStick_DataWriter_Thread');
			$dw->setExistingData($threadId);
			$dw->set('brms_stick', 1);
			$dw->save();
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('threads', $thread)
			);	
	}

	public function _getBSModel()
	{
		return $this->getModelFromCache('BS_BRMSStick_Model');
	}

	public function _assertPermissions()
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('BR_ModernStatistics', 'canManageStickLinks'))
		{
			throw $this->getNoPermissionResponseException();
		}
	}
}
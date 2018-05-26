<?php

class BS_BRMSStick_ControllerPublic_BRMSStick extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$this->_assertPermissions();

		$links = $this->_getBSModel()->getStickedLinks();

		$viewParams = array(
			'links' => $links,
			);
		return $this->responseView('BS_BRMSStick_ViewPublic_BRMSStick', 'BRMSStick_Stick_Link_Form', $viewParams);
	}

	public function actionSticklink()
	{
		$this->_assertPostOnly();
		$this->_assertPermissions();

		$link = $this->_input->filterSingle('link', XenForo_Input::STRING); 
		$title = $this->_input->filterSingle('title', XenForo_Input::STRING); 

		if (strlen($link) < 1 || strlen($title) < 1)
		{
			throw new XenForo_Exception(new XenForo_Phrase('please_entry_all_inputs'), true);	
		}
		else if (strlen($link) > 699)
		{
			throw new XenForo_Exception(new XenForo_Phrase('link_too_long'), true);	
		}
		else if (strlen($title) > 699)
		{
			throw new XenForo_Exception(new XenForo_Phrase('title_too_long'), true);	
		}

		$dw = XenForo_DataWriter::create('BS_BRMSStick_DataWriter_BRMSStick');
		$dw->set('link', $link);
		$dw->set('title', $title);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('')
			);
	}

	public function actionUnsticklink()
	{
		$this->_assertPostOnly();
		$this->_assertPermissions();

		$linkId = $this->_input->filterSingle('link_id', XenForo_Input::STRING); 

		if (is_numeric($linkId))
		{
			$dw = XenForo_DataWriter::create('BS_BRMSStick_DataWriter_BRMSStick');
			$dw->setExistingData($linkId);
			$dw->delete();
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('')
			);
	}

	protected function _getBSModel()
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
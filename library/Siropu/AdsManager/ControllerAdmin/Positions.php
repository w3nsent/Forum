<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Positions extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$categories = $this->_getPositionsCategoriesModel()->getAllCategories();
		$positions  = $this->_getModel()->getAllPositions();

		$viewParams = array(
			'positions'      => $this->_getHelperGeneral()->groupPositionsByCategory($positions, $categories),
			'totalPositions' => count($positions)
		);

		return $this->responseView('', 'siropu_ads_manager_position_list', $viewParams);
	}
	public function actionAdd()
	{
		return $this->_getPositionAddEditResponse();
	}
	public function actionEdit()
	{
		$position = $this->_getPositionOrError();

		$viewParams = array(
			'position' => $position,
			'cssClass' => $this->_getHelperGeneral()->generateClassFromHook($position['hook'])
		);

		return $this->_getPositionAddEditResponse($viewParams);
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'hook'           => XenForo_Input::STRING,
			'cat_id'         => XenForo_Input::UINT,
			'name'           => XenForo_Input::STRING,
			'description'    => XenForo_Input::STRING,
			'display_order'  => XenForo_Input::UINT,
			'visible'        => XenForo_Input::UINT
		));

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Positions');
		if ($positionId = $this->_getPositionID())
		{
			$dw->setExistingData($positionId);
		}
		$dw->bulkSet($dwData);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('ad-positions') . $this->getLastHash($dw->get('position_id'))
		);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Siropu_AdsManager_DataWriter_Positions', 'position_id',
				XenForo_Link::buildAdminLink('ad-positions')
			);
		}

		$viewParams['position'] = $this->_getPositionOrError();
		return $this->responseView('', 'siropu_ads_manager_position_delete_confirm', $viewParams);
	}
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getModel()->getAllPositions(),
			'Siropu_AdsManager_DataWriter_Positions',
			'ad-positions',
			'visible');
	}
	protected function _getPositionAddEditResponse($viewParams = array())
	{
		$viewParams['categoryList'] = $this->_getHelperGeneral()->getPositionCategorySelectList($this->_getPositionsCategoriesModel()->getAllCategories());
		$viewParams['catId'] = $this->_input->filterSingle('cat_id', XenForo_Input::UINT);

		return $this->responseView('', 'siropu_ads_manager_position_edit', $viewParams);
	}
	protected function _getPositionOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getPositionID();
		}

		if ($info = $this->_getModel()->getPositionById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_ads_manager_position_not_found'), 404));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _getPositionsCategoriesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PositionsCategories');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getPositionID()
	{
		return $this->_input->filterSingle('position_id', XenForo_Input::UINT);
	}
}
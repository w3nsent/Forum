<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_PositionsCategories extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('ad-positions')
		);
	}
	public function actionAdd()
	{
		return $this->responseView('', 'siropu_ads_manager_position_category_edit');
	}
	public function actionEdit()
	{
		$viewParams['category'] = $this->_getCategoryOrError();
		return $this->responseView('', 'siropu_ads_manager_position_category_edit', $viewParams);
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'title'         => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT
		));

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_PositionsCategories');
		if ($catId = $this->_getCatID())
		{
			$dw->setExistingData($catId);
		}
		$dw->bulkSet($dwData);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('ad-positions')
		);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			$response = $this->_deleteData(
				'Siropu_AdsManager_DataWriter_PositionsCategories', 'cat_id',
				XenForo_Link::buildAdminLink('ad-positions')
			);

			$this->_getPositionsModel()->updatePositionsByCategoryId($this->_getCatID(), array('cat_id' => 0));
			return $response;
		}

		$viewParams['category'] = $this->_getCategoryOrError();
		return $this->responseView('', 'siropu_ads_manager_position_category_delete_conf', $viewParams);
	}
	protected function _getCategoryOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getCatID();
		}

		if ($info = $this->_getModel()->getCategoryById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_ads_manager_position_category_not_found'), 404));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PositionsCategories');
	}
	protected function _getPositionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _getCatID()
	{
		return $this->_input->filterSingle('cat_id', XenForo_Input::UINT);
	}
}
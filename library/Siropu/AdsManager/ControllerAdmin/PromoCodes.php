<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_PromoCodes extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$viewParams['promoCodes'] = $this->_getPromoCodesModel()->getAllPromoCodes();
		return $this->responseView('', 'siropu_ads_manager_promo_code_list', $viewParams);
	}
	public function actionAdd()
	{
		return $this->_getPromoCodeAddEditResponse();
	}
	public function actionEdit()
	{
		$promoCode = $this->_getPromoCodeOrError();

		return $this->_getPromoCodeAddEditResponse(array(
			'promoCode' => $promoCode,
			'packages'  => $promoCode['packages'] ? explode(',', $promoCode['packages']) : ''
		));
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'code'                  => XenForo_Input::STRING,
			'description'           => XenForo_Input::STRING,
			'value'                 => XenForo_Input::FLOAT,
			'type'                  => XenForo_Input::STRING,
			'packages'              => XenForo_Input::ARRAY_SIMPLE,
			'min_transaction_value' => XenForo_Input::FLOAT,
			'date_expire'           => XenForo_Input::DATE_TIME,
			'usage_limit_total'     => XenForo_Input::UINT,
			'usage_limit_user'      => XenForo_Input::UINT,
			'user_criteria'         => XenForo_Input::ARRAY_SIMPLE,
			'enabled'               => XenForo_Input::UINT
		));

		if ($packages = array_filter($dwData['packages']))
		{
			$dwData['packages'] = implode(',', $packages);
		}
		else
		{
			$dwData['packages'] = '';
		}

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_PromoCodes');
		if ($promoCodeId = $this->_getPromoCodeID())
		{
			$dw->setExistingData($promoCodeId);
		}
		$dw->bulkSet($dwData);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('promo-codes') . $this->getLastHash($dw->get('code_id'))
		);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Siropu_AdsManager_DataWriter_PromoCodes', 'code_id',
				XenForo_Link::buildAdminLink('promo-codes')
			);
		}
		else
		{
			$viewParams['promoCode'] = $this->_getPromoCodeOrError();
			return $this->responseView('', 'siropu_ads_manager_promo_code_delete_confirm', $viewParams);
		}
	}
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getPromoCodesModel()->getAllPromoCodes(),
			'Siropu_AdsManager_DataWriter_PromoCodes',
			'promo-codes',
			'enabled');
	}
	protected function _getPromoCodeAddEditResponse($viewParams = array())
	{
		$userCriteria = array();

		if (isset($viewParams['promoCode']))
		{
			$userCriteria = $viewParams['promoCode']['user_criteria'];
		}

		$viewParams = array_merge($viewParams, array(
			'packageList'      => $this->_getPackagesModel()->getAllPackages(),
			'userCriteria'     => XenForo_Helper_Criteria::prepareCriteriaForSelection($userCriteria),
			'userCriteriaData' => XenForo_Helper_Criteria::getDataForUserCriteriaSelection(),
		));

		return $this->responseView('', 'siropu_ads_manager_promo_code_edit', $viewParams);
	}
	protected function _getPromoCodeOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getPromoCodeID();
		}

		if ($info = $this->_getPromoCodesModel()->getPromoCodeById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_ads_manager_promo_code_not_found'), 404));
	}
	protected function _getPromoCodesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PromoCodes');
	}
	protected function _getPackagesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Packages');
	}
	protected function _getPromoCodeID()
	{
		return $this->_input->filterSingle('code_id', XenForo_Input::UINT);
	}
}
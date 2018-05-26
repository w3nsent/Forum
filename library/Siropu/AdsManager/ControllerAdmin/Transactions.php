<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Transactions extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$search = $this->_input->filterSingle('search', XenForo_Input::ARRAY_SIMPLE);

		$viewParams = array(
			'search'         => $search,
			'transactions'   => $this->_getTransactionsModel()->getAllTransactions($search),
			'statusList'     => $this->_getHelperGeneral()->getStatusList(),
			'paymentOptions' => $this->_getHelperGeneral()->paymentOptions(),
		);

		return $this->responseView('', 'siropu_ads_manager_transaction_list', $viewParams);
	}
	public function actionAdd()
	{
		$viewParams = array(
			'adList' => $this->_getAdsModel()->getAllAds('', '', array('field' => 'date_created', 'dir' => 'desc'))
		);

		return $this->responseView('', 'siropu_ads_manager_transaction_add',
			$this->_getInvoiceAddEditResponse($viewParams));
	}
	public function actionEdit()
	{
		$viewParams = array(
			'transaction' => $this->_getTransactionOrError($this->_getID())
		);

		return $this->responseView('', 'siropu_ads_manager_transaction_edit',
			$this->_getInvoiceAddEditResponse($viewParams));
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'cost_amount'    => XenForo_Input::FLOAT,
			'cost_currency'  => XenForo_Input::STRING,
			'promo_code'     => XenForo_Input::STRING,
			'payment_method' => XenForo_Input::STRING,
			'payment_txn_id' => XenForo_Input::STRING,
			'status'         => XenForo_Input::STRING
		));

		$id          = $this->_getID();
		$adId        = $this->_input->filterSingle('ad_id', XenForo_Input::UINT);
		$username    = $this->_input->filterSingle('username', XenForo_Input::STRING);
		$status      = $this->_input->filterSingle('current_status', XenForo_Input::STRING);
		$transaction = $this->_getTransactionsModel()->getTransactionJoinAdsJoinPackagesById($id);

		if (!$id)
		{
			$errors = array();

			if (!$adId)
			{
				$errors[] = new XenForo_Phrase('siropu_ads_manager_transaction_ad_required');
			}

			if (!$username)
			{
				$errors[] = new XenForo_Phrase('siropu_ads_manager_transaction_user_required');
			}

			if ($username && (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserByName($username)))
			{
				$errors[] = new XenForo_Phrase('requested_user_not_found');
			}

			if ($errors)
			{
				return $this->responseError($errors);
			}

			$ad = $this->_getAdsModel()->getAdById($adId);

			if ($ad['status'] != 'Active')
			{
				$writer = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$writer->setExistingData($adId);
				$writer->set('pending_transaction', 1);
				$writer->save();
			}

			$dwData['ad_id']    = $adId;
			$dwData['user_id']  = $user['user_id'];
			$dwData['username'] = $username;
			$dwData['status']   = 'Pending';

			$this->_getHelperGeneral()->sendEmailNotification('siropu_ads_manager_new_invoice', array(
				'name'  => $ad['name'],
				'limit' => XenForo_Application::get('options')->siropu_ads_manager_transaction_time_limit,
				'url'   => XenForo_Link::buildPublicLink('canonical:advertising/invoices')), $ad['user_id']);

			XenForo_Model_Alert::alert(
				$user['user_id'],
				$user['user_id'],
				$username,
				'siropu_ads_manager',
				$adId,
				'pending_invoice'
			);
		}

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
		if ($id)
		{
			$dw->setExistingData($id);
		}
		$dw->bulkSet($dwData);
		$dw->save();

		if ($transaction && $status && $status != $dwData['status'])
		{
			$this->_getTransactionsModel()->processTransaction(
				$transaction,
				$dwData['status'],
				$dwData['payment_method'],
				$dwData['payment_txn_id']
			);

			$this->_getHelperGeneral()->refreshActiveAdsCache();
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('invoices') . $this->getLastHash($dw->get('transaction_id'))
		);
	}
	public function actionDetails()
	{
		if ($transaction = $this->_getTransactionsModel()->getTransactionJoinAdsJoinPackagesById($this->_getID()))
		{
			$viewParams = array(
				'transaction'    => $transaction,
				'costPerList'    => $this->_getHelperGeneral()->getCostPerList(),
				'paymentOptions' => $this->_getHelperGeneral()->paymentOptions(),
			);

			return $this->responseView('', 'siropu_ads_manager_transaction_details', $viewParams);
		}
	}
	public function actionUpload()
	{
		$transaction = $this->_getTransactionOrError($this->_getID());

		$viewParams = array(
			'transaction' => $transaction
		);

		if ($upload = XenForo_Upload::getUploadedFile('invoice'))
		{
			$filePath = XenForo_Helper_File::getExternalDataPath() . "/Siropu/invoices/{$this->_getID()}";

			if (!file_exists($filePath))
			{
				XenForo_Helper_File::createDirectory($filePath, true);
			}

			$fileName = $upload->getFileName();
			$file     = "$filePath/$fileName";

			XenForo_Helper_File::safeRename($upload->getTempFile(), $file);

			if ($transaction['download'])
			{
				@unlink($transaction['download']);
			}

			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
			$dw->setExistingData($this->_getID());
			$dw->set('download', $fileName);
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('invoices/upload', $transaction)
			);
		}

		return $this->responseView('', 'siropu_ads_manager_transaction_upload', $viewParams);
	}
	public function actionDeleteUpload()
	{
		$transaction = $this->_getTransactionOrError($this->_getID());

		$viewParams = array(
			'transaction' => $transaction
		);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
			$dw->setExistingData($this->_getID());
			$dw->set('download', '');
			$dw->save();

			@unlink(XenForo_Helper_File::getExternalDataPath() . "/Siropu/invoices/{$this->_getID()}/{$transaction['download']}");

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('invoices/upload', $transaction)
			);
		}

		return $this->responseView('', 'siropu_ads_manager_invoice_delete_upload_confirm', $viewParams);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Siropu_AdsManager_DataWriter_Transactions', 'transaction_id',
				XenForo_Link::buildAdminLink('invoices')
			);
		}
		else
		{
			$viewParams['transaction'] = $this->_getTransactionOrError();
			return $this->responseView('', 'siropu_ads_manager_transaction_delete_confirm', $viewParams);
		}
	}
	protected function _getInvoiceAddEditResponse($viewParams = array())
	{
		return array_merge($viewParams, array(
			'currencyList'   => $this->_getHelperGeneral()->getCurrencyList('', true),
			'prefCurrency'   => $this->_getOptions()->siropu_ads_manager_currency,
			'paymentOptions' => $this->_getHelperGeneral()->paymentOptions(),
		));
	}
	protected function _getTransactionOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getID();
		}

		if ($info = $this->_getTransactionsModel()->getTransactionById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_ads_manager_transaction_not_found'), 404));
	}
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getID()
	{
		return $this->_input->filterSingle('transaction_id', XenForo_Input::UINT);
	}
}

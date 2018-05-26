<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerPublic_Ipn extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionPayPal()
	{
		$sandbox = $this->_getOptions()->siropu_ads_manager_paypal_ipn_sandbox;

		define('DEBUG', $this->_getOptions()->siropu_ads_manager_ipn_debug);
		define('USE_SANDBOX', $sandbox['enabled']);

		$raw_post_data  = file_get_contents('php://input');
		$raw_post_array = explode('&', $raw_post_data);

		$myPost = array();
		foreach ($raw_post_array as $keyval)
		{
			$keyval = explode ('=', $keyval);

			if (count($keyval) == 2)
			{
				$myPost[$keyval[0]] = urldecode($keyval[1]);
			}
		}

		$req = 'cmd=_notify-validate';

		if (function_exists('get_magic_quotes_gpc'))
		{
			$get_magic_quotes_exists = true;
		}

		foreach ($myPost as $key => $value)
		{
			if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1)
			{
				$value = urlencode(stripslashes($value));
			}
			else
			{
				$value = urlencode($value);
			}
			$req .= "&$key=$value";
		}

		if (USE_SANDBOX == true)
		{
			$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		}
		else
		{
			$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		}

		$ch = curl_init($paypal_url);

		if ($ch == FALSE)
		{
			return FALSE;
		}

		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

		if (DEBUG == true)
		{
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		}

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

		$res = curl_exec($ch);
		if (curl_errno($ch) != 0)
		{
			if (DEBUG == true)
			{
				XenForo_Error::logError("[PayPal] Can't connect to PayPal to validate IPN message: " . curl_error($ch));
			}
			curl_close($ch);
			exit;
		}
		else
		{
			if (DEBUG == true)
			{
				XenForo_Error::logError('[PayPal] HTTP request of validation request: ' . curl_getinfo($ch, CURLINFO_HEADER_OUT) . " for IPN payload: {$req}\nHTTP response of validation request: {$res}");
			}
			curl_close($ch);
		}

		$tokens = explode("\r\n\r\n", trim($res));
		$res = trim(end($tokens));

		if (strcmp ($res, "VERIFIED") == 0)
		{
			$vars = $this->_input->filter(array(
				'payment_status' => XenForo_Input::STRING,
				'business'       => XenForo_Input::STRING,
				'receiver_email' => XenForo_Input::STRING,
				'txn_id'         => XenForo_Input::STRING,
				'mc_gross'       => XenForo_Input::UNUM,
				'mc_amount3'     => XenForo_Input::UNUM,
				'mc_currency'    => XenForo_Input::STRING,
				'item_number'    => XenForo_Input::STRING,
				'invoice'        => XenForo_Input::STRING,
				'custom'         => XenForo_Input::STRING,
				'txn_type'       => XenForo_Input::STRING,
				'subscr_id'      => XenForo_Input::STRING,
				'subscr_date'    => XenForo_Input::STRING,
			));

			$amount = $vars['mc_gross'] ? $vars['mc_gross'] : $vars['mc_amount3'];

			if ($vars['subscr_id'] && ($ad = $this->_getAdsModel()->getAdJoinPackageById($vars['item_number'])))
			{
				$subscription = $this->_getSubscriptionsModel()->getSubscriptionByAdId($vars['item_number']);

				$transactionData = array(
					'date_completed' => time(),
					'payment_method' => 'PayPal',
					'payment_txn_id' => $vars['txn_id'],
					'status'         => 'Completed'
				);

				$dateEnd = strtotime("+{$ad['purchase']} {$ad['cost_per']}");

				switch ($vars['txn_type'])
				{
					case 'subscr_signup':
						$this->_getHelperGeneral()->sendEmailNotification('siropu_ads_manager_subscription_confirmation',
							array('name' => $ad['name']), $ad['user_id']);
						break;
					case 'subscr_payment':
						if ($invoice = $this->_getTransactionsModel()->getPendingTransactionByAdId($ad['ad_id']))
						{
							if (str_replace(',', '', $amount) < $invoice['cost_amount']
								&& $invoice['cost_currency'] != $vars['mc_currency'])
							{
								if (DEBUG == true)
								{
									XenForo_Error::logError('[PayPal] Invalid Subscription Payment');
								}

								exit;
							}

							$this->_manageSubscription(array(
								'subscr_id'     => $vars['subscr_id'],
								'package_id'    => $ad['package_id'],
								'ad_id'         => $ad['ad_id'],
								'user_id'       => $ad['user_id'],
								'username'      => $ad['username'],
								'amount'        => $amount,
								'currency'      => $vars['mc_currency'],
								'subscr_date'   => time(),
								'subscr_method' => 'PayPal'
							), $subscription);

							$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
							$dw->setExistingData($invoice['transaction_id']);
							$dw->BulkSet($transactionData);
							$dw->save();

							$this->_manageSubscriptionAd($ad, array(
								'pending_invoice' => 0,
								'date_active'     => time(),
								'date_end'        => $dateEnd,
								'subscription'    => 1,
								'status'          => 'Active'
							));
						}
						else
						{
							$this->_manageSubscription(array('last_payment_date' => time()), $subscription);

							if ($this->_getOptions()->siropu_ads_manager_subscription_invoices)
							{
								$this->_getTransactionsModel()->generateTransaction($ad, $transactionData);
							}

							if ($ad['date_end'] > time())
							{
								$dateEnd += $ad['date_end'] - time();
							}

							$this->_manageSubscriptionAd($ad, array('date_end' => $dateEnd));
						}
						$this->_getUserModel()->changeUserGroups($ad['user_id']);
						break;
					case 'subscr_cancel':
					case 'subscr_eot':
						$this->_manageSubscription(array(
							'status' => $vars['txn_type'] == 'subscr_cancel' ? 'Cancelled' : 'Inactive'
						), $subscription);

						$this->_manageSubscriptionAd($ad, array(
							'subscription'     => 0,
							'date_active'      => 0,
							'date_last_active' => time(),
							'date_end'         => 0,
							'status'           => 'Inactive'
						));
						$this->_getUserModel()->changeUserGroups($ad['user_id'], 'remove');
						break;
				}
			}

			if (($IDs = array_filter(explode(',', $vars['invoice'])))
				&& ($resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs)))
			{
				$total = $this->_getTransactionsTotal($resultArray);

				if (USE_SANDBOX == true && $sandbox['email'])
				{
					$PPEmail = $sandbox['email'];
				}
				else
				{
					$PPEmail = $this->_getOptions()->siropu_ads_manager_paypal_email_account;
				}

				if (str_replace(',', '', $amount) >= $this->_getHelperGeneral()->formatPrice($total)
					&& $vars['mc_currency'] == $resultArray[0]['cost_currency']
					&& $vars['business'] == strtolower(trim($PPEmail)))
				{
					$txnId = $vars['txn_id'];

					foreach ($resultArray as $row)
					{
						switch ($vars['payment_status'])
						{
							case 'Completed':
								$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'PayPal', $txnId);
								break;
							case 'Reversed':
							case 'Refunded':
								$this->_getTransactionsModel()->processTransaction($row, 'Cancelled', 'PayPal', $txnId);
								break;
							case 'Canceled_Reversal':
								break;
						}
					}
				}
			}

			if (DEBUG == true)
			{
				XenForo_Error::logError("[PayPal] Verified IPN: {$req}");
			}
		}
		else if (strcmp ($res, "INVALID") == 0)
		{
			if (DEBUG == true)
			{
				XenForo_Error::logError("[PayPal] Invalid IPN: {$req}");
			}
		}

		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionPayza()
	{
		define('DEBUG', $this->_getOptions()->siropu_ads_manager_ipn_debug);
		define("IPN_V2_HANDLER", "https://secure.payza.com/ipn2.ashx");
		define("TOKEN_IDENTIFIER", "token=");

		$token = urlencode($this->_input->filterSingle('token', XenForo_Input::STRING));
		$token = TOKEN_IDENTIFIER.$token;

		$response = '';
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, IPN_V2_HANDLER);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		curl_close($ch);

		if(strlen($response) > 0)
		{
			if(urldecode($response) == "INVALID TOKEN")
			{
				if (DEBUG == true)
				{
					XenForo_Error::logError("[Payza] The token is not valid");
				}
			}
			else
			{
				$aps  = explode("&", urldecode($response));
				$info = array();

				foreach ($aps as $ap)
				{
					$ele = explode("=", $ap);
					$info[$ele[0]] = $ele[1];
				}

				//setting information about the transaction from the IPN information array
				$receivedMerchantEmailAddress = $info['ap_merchant'];
				$transactionStatus            = $info['ap_status'];
				$transactionState             = $info['ap_transactionstate'];
				$testModeStatus               = $info['ap_test'];
				$purchaseType                 = $info['ap_purchasetype'];
				$totalAmountReceived          = $info['ap_totalamount'];
				$feeAmount                    = $info['ap_feeamount'];
				$netAmount                    = $info['ap_netamount'];
				$transactionReferenceNumber   = $info['ap_referencenumber'];
				$currency                     = $info['ap_currency'];
				$transactionDate              = $info['ap_transactiondate'];
				$transactionType              = $info['ap_transactiontype'];

				//setting the customer's information from the IPN information array
				$customerFirstName    = $info['ap_custfirstname'];
				$customerLastName     = $info['ap_custlastname'];
				$customerAddress      = $info['ap_custaddress'];
				$customerCity         = $info['ap_custcity'];
				$customerState        = $info['ap_custstate'];
				$customerCountry      = $info['ap_custcountry'];
				$customerZipCode      = $info['ap_custzip'];
				$customerEmailAddress = $info['ap_custemailaddress'];

				//setting information about the purchased item from the IPN information array
				$myItemName        = $info['ap_itemname'];
				$myItemCode        = $info['ap_itemcode'];
				$myItemDescription = $info['ap_description'];
				$myItemQuantity    = $info['ap_quantity'];
				$myItemAmount      = $info['ap_amount'];

				//setting extra information about the purchased item from the IPN information array
				$additionalCharges = $info['ap_additionalcharges'];
				$shippingCharges   = $info['ap_shippingcharges'];
				$taxAmount         = $info['ap_taxamount'];
				$discountAmount    = $info['ap_discountamount'];

				//setting your customs fields received from the IPN information array
				$myCustomField_1 = $info['apc_1'];
				$myCustomField_2 = $info['apc_2'];
				$myCustomField_3 = $info['apc_3'];
				$myCustomField_4 = $info['apc_4'];
				$myCustomField_5 = $info['apc_5'];
				$myCustomField_6 = $info['apc_6'];

				if (($IDs = array_filter(explode(',', $myCustomField_1)))
					&& ($resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs)))
				{
					$total = $this->_getTransactionsTotal($resultArray);

					if ($this->_getHelperGeneral()->formatPrice($total) >= $this->_getHelperGeneral()->formatPrice($myItemAmount)
						&& $currency == $resultArray[0]['cost_currency'])
					{
						foreach ($resultArray as $row)
						{
							switch ($transactionState)
							{
								case 'Completed':
									$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'Payza', $transactionReferenceNumber);
									break;
								case 'Refunded':
								case 'Reversed':
									$this->_getTransactionsModel()->processTransaction($row, 'Cancelled', 'Payza', $transactionReferenceNumber);
									break;
							}
						}
					}
				}
			}
		}
		else
		{
			if (DEBUG == true)
			{
				XenForo_Error::logError("[Payza] Something is wrong, no response is received from Payza");
			}
		}

		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionROBOKASSA()
	{
		$vars = $this->_input->filter(array(
			'OutSum'         => XenForo_Input::STRING,
			'InvId'          => XenForo_Input::UINT,
			'Shp_item'       => XenForo_Input::STRING,
			'SignatureValue' => XenForo_Input::STRING
		));

		$myCrc = $this->_getHelperGeneral()->getRobokassaSignature($vars);

		if (strtoupper($vars['SignatureValue']) == strtoupper($myCrc))
		{
			if (($IDs = array_filter(explode(',', $vars['Shp_item'])))
				&& ($resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs)))
			{
				$total = $this->_getTransactionsTotal($resultArray);

				if ($this->_getHelperGeneral()->formatPrice($total) == $this->_getHelperGeneral()->formatPrice($vars['OutSum']))
				{
					foreach ($resultArray as $row)
					{
						$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'ROBOKASSA', $myCrc);
					}
				}
			}
		}

		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionZarinpal()
	{
		$vars = $this->_input->filter(array(
			'Authority' => XenForo_Input::STRING,
			'Status'    => XenForo_Input::STRING,
			'Amount'    => XenForo_Input::UINT,
			'Invoice'   => XenForo_Input::STRING
		));

		if ($vars['Status'] == 'OK')
		{
			$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8'));

			$result = $client->PaymentVerification(array(
				'MerchantID' => $this->_getOptions()->siropu_ads_manager_zarinpal_merchant_id,
				'Authority'  => $vars['Authority'],
				'Amount'     => $vars['Amount']
			));

			if ($result->Status == 100)
			{
				if (($IDs = array_filter(explode(',', $vars['Invoice'])))
					&& ($resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs)))
				{
					if (floatval($this->_getTransactionsTotal($resultArray)) == $vars['Amount'])
					{
						foreach ($resultArray as $row)
						{
							$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'ZarinPal', $result->RefID);
						}
					}
				}

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('advertising/thank-you')
				);
			}
			else
			{
				XenForo_Error::logError("Transation failed. Status: {$result->Status}");
			}
		}
		else if ($vars['Status'] == 'NOK')
		{
			XenForo_Error::logError('Transaction canceled by user');
		}

		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionStripe()
	{
		$this->_assertPostOnly();

		require dirname(dirname(__FILE__)) . '/ThirdParty/Stripe/lib/Stripe.php';

		$vars = $this->_input->filter(array(
			'invoice'     => XenForo_Input::STRING,
			'stripeToken' => XenForo_Input::STRING
		));

		if (!$vars['stripeToken'])
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_stripe_token_error'));
		}

		$IDs = array_filter(explode(',', $vars['invoice']));

		if (!$resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		Stripe::setApiKey($this->_getOptions()->siropu_ads_manager_stripe_api_key);

		try {
			$total = $this->_getTransactionsTotal($resultArray);
			$row   = $resultArray[0];

			switch ($row['cost_currency'])
			{
				case 'BIF':
				case 'DJF':
				case 'JPY':
				case 'KRW':
				case 'PYG':
				case 'VND':
				case 'XAF':
				case 'XPF':
				case 'CLP':
				case 'GNF':
				case 'KMF':
				case 'MGA':
				case 'RWF':
				case 'VUV':
				case 'XOF':
					$amount = floatval($total);
					break;
				default:
					$amount = $total * 100 + ltrim(substr($total, strpos($total, '.'), strlen($total)), '.');
					break;
			}

			$charge = Stripe_Charge::create(array(
				'amount'      => $amount,
				'currency'    => strtolower($row['cost_currency']),
				'card'        => $vars['stripeToken'],
				'metadata'    => array(
					'user_id'  => $row['user_id'],
					'username' => $row['username'],
					'invoice'  => $vars['invoice']),
				'description' => new XenForo_Phrase('siropu_ads_manager_payment_name',
					array('boardTitle' => $this->_getOptions()->boardTitle))
			));

			if ($charge && !is_object($charge))
			{
				$charge = json_decode($charge);
			}

			if (!empty($charge->id))
			{
				foreach ($resultArray as $row)
				{
					$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'Stripe', substr($charge->id, 3, 25));
				}

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('advertising/thank-you', '', array('success' => true))
				);
			}
			else
			{
				return $this->responseMessage('We are sorry, something went wrong. Please contact us.');
			}
		}
		catch (Exception $e) {
			XenForo_Error::logError($e->getMessage());
			return $this->responseError($e->getMessage());
		}

		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionSkrill()
	{
		$vars = $this->_input->filter(array(
			'merchant_id'       => XenForo_Input::STRING,
			'transaction_id'    => XenForo_Input::STRING,
			'mb_transaction_id' => XenForo_Input::STRING,
			'mb_amount'         => XenForo_Input::UNUM,
			'mb_currency'       => XenForo_Input::STRING,
			'md5sig'            => XenForo_Input::STRING,
			'status'            => XenForo_Input::STRING
		));

		$md5sig = $vars['merchant_id'] .
		$vars['transaction_id'] .
		strtoupper(md5($this->_getOptions()->siropu_ads_manager_skrill_secret_word)) .
		$vars['mb_amount'] .
		$vars['mb_currency'] .
		$vars['status'];

		if ($md5sig == $vars['md5sig']
			&& ($IDs = array_filter(explode(',', $vars['transaction_id'])))
			&& ($resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs)))
		{
			$total = $this->_getTransactionsTotal($resultArray);

			if ($this->_getHelperGeneral()->formatPrice($total) == $this->_getHelperGeneral()->formatPrice($vars['mb_amount']) && $vars['mb_currency'] == $resultArray[0]['cost_currency'])
			{
				foreach ($resultArray as $row)
				{
					switch ($vars['status'])
					{
						case 2:
							$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'Skrill', $vars['mb_transaction_id']);
							break;
						case -3:
							$this->_getTransactionsModel()->processTransaction($row, 'Cancelled', 'Skrill', $vars['mb_transaction_id']);
							break;
					}
				}
			}
		}

		return $this->responseView('', 'siropu_ads_manager_ipn');
	}
	public function actionBitcoin()
	{
		$vars = $this->_input->filter(array(
			'id'                     => XenForo_Input::STRING,
			'secret'                 => XenForo_Input::STRING,
			'status'                 => XenForo_Input::STRING,
			'posData'                => XenForo_Input::STRING,
			'value'                  => XenForo_Input::UINT,
			'input_address'          => XenForo_Input::STRING,
			'confirmations'          => XenForo_Input::STRING,
			'transaction_hash'       => XenForo_Input::STRING,
			'input_transaction_hash' => XenForo_Input::STRING,
			'destination_address'    => XenForo_Input::STRING,
			'invoice'                => XenForo_Input::STRING
		));

		$bitcoinApi = $this->_getOptions()->siropu_ads_manager_bitcoin_api;
		$invoice    = '';

		switch ($bitcoinApi)
		{
			case 'bitpay':
				$invoice = $vars['posData'];
				break;
			case 'coinbase':
				$data    = json_decode(file_get_contents('php://input'), true);
				$order   = $data['order'];
				$invoice = $order['custom'];
				break;
			case 'blockchain':
				$invoice = $vars['invoice'];
				break;
			default:
				break;
		}

		$secretKey  = $this->_getOptions()->siropu_ads_manager_bitcoin_api_secret;
		$IDs        = array_filter(array_map('intval', explode(',', $invoice)));
		$viewParams = array('output' => 'Pending');

		if ($IDs && ($resultArray = $this->_getTransactionsModel()->getTransactionsJoinAdsJoinPackagesByIds($IDs)))
		{
			$paymentValid = false;
			$txnId        = 'N/A';

			switch ($bitcoinApi)
			{
				case 'bitpay':
					if (in_array($vars['status'], array('paid', 'confirmed', 'complete')) && $vars['secret'] == $secretKey)
					{
						$paymentValid = true;
						$txnId        = $vars['id'];
					}
					break;
				case 'coinbase':
					if ($order['status'] == 'completed' && $vars['secret'] == $secretKey)
					{
						$paymentValid = true;
						$txnId        = $order['id'];
					}
					break;
				case 'blockchain':
					$total = substr($this->_getTransactionsTotal($resultArray, 'cost_amount_btc'), 0, 7);
					$btc   = substr($vars['value'] / 100000000, 0, 7);

					if ($vars['destination_address'] == $this->_getOptions()->siropu_ads_manager_bitcoin_address
						&& $vars['secret'] == $secretKey
						&& $total == $btc
						&& $vars['confirmations'] >= 6)
					{
						$paymentValid = true;
						$txnId        = $vars['input_transaction_hash'];
					}
					break;
			}

			if ($paymentValid)
			{
				foreach ($resultArray as $row)
				{
					$this->_getTransactionsModel()->processTransaction($row, 'Completed', 'Bitcoin', $txnId);
				}

				$viewParams['output'] = '*ok*';
			}
		}

		$this->_routeMatch->setResponseType('raw');
		return $this->responseView('Siropu_AdsManager_ViewPublic_Raw', '', $viewParams);
	}
	protected function _getTransactionsTotal($resultArray, $field = 'cost_amount')
	{
		$total = 0;

		foreach ($resultArray as $row)
		{
			$total += $row[$field];
		}

		return $total;
	}
	protected function _manageSubscription($data, $subscription)
	{
		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Subscriptions');
		if ($subscription)
		{
			$dw->setExistingData($subscription['subscription_id']);
		}
		$dw->BulkSet($data);
		$dw->save();
	}
	protected function _manageSubscriptionAd($ad, $data)
	{
		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		$dw->setExistingData($ad['ad_id']);
		$dw->bulkSet($data);
		$dw->save();

		if ($ad['type'] == 'sticky' && !empty($data['status']) && $data['status'] == 'Inactive')
		{
			$this->_getThreadsModel()->toggleStickyThreadById('Inactive', $ad['items'], true);
		}
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getSubscriptionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Subscriptions');
	}
	protected function _getThreadsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Threads');
	}
	protected function _getUserModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_User');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}

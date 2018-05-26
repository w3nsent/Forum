<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Tools extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex()
	{
		$viewParams = array();
		return $this->responseView('', 'siropu_ads_manager_tools', $viewParams);
	}
	public function actionEmailAdvertisers()
	{
		$input = $this->_input->filter(array(
			'subject'  => XenForo_Input::STRING,
			'message'  => XenForo_Input::STRING,
			'package'  => XenForo_Input::ARRAY_SIMPLE,
			'status'   => XenForo_Input::ARRAY_SIMPLE,
			'username' => XenForo_Input::ARRAY_SIMPLE,
			'send'     => XenForo_Input::STRING,
		));

		if ($input['send'])
		{
			$error = $where = array();

			if (!$input['subject'])
			{
				$error[] = new XenForo_Phrase('siropu_ads_manager_tools_email_subject_error');
			}
			if (!$input['message'])
			{
				$error[] = new XenForo_Phrase('siropu_ads_manager_tools_email_message_error');
			}

			if ($error)
			{
				return $this->responseError($error);
			}

			if ($userData = $this->_getAdsModel()->getUserDataForEmail($input))
			{
				$mailParams = array(
					'subject' => $input['subject'],
					'message' => $input['message']
				);

				$userList = array();
				foreach ($userData as $user)
				{
					$mail = XenForo_Mail::create('siropu_ads_manager_advertisers', $mailParams, $user['language_id']);
					$mail->send($user['email'], $user['username']);

					$userList[] = $user['username'];
				}

				if ($userList)
				{
					return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildAdminLink('ad-tools/email-advertisers'),
						new XenForo_Phrase('siropu_ads_manager_tools_email_sent',
							array('count' => count($userList), 'userList' => implode(', ', $userList)))
					);
				}
			}

			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_tools_email_no_advertisers_found'));
		}

		$viewParams = array(
			'advertiserAds'  => $this->_getAdsModel()->getAdvertiserAds(),
			'packageList'    => $this->_getHelperGeneral()->getPackageSelectList($this->_getPackagesModel()->getAllPackages()),
			'advertiserList' => $this->_getAdsModel()->getAllAdvertisers()
		);

		return $this->responseView('', 'siropu_ads_manager_tools_email_advertisers', $viewParams);
	}
	public function actionChangeOwner()
	{
		$data = $this->_input->filter(array(
			'user_id'          => XenForo_Input::UINT,
			'ads'              => XenForo_Input::ARRAY_SIMPLE,
			'new_owner'        => XenForo_Input::STRING,
			'include_invoices' => XenForo_Input::UINT,
			'add_to_groups'    => XenForo_Input::UINT,
			'save'             => XenForo_Input::STRING
		));

		$viewParams = array(
			'advertiserList' => $this->_getAdsModel()->getAllAdvertisers(),
			'userId'         => $data['user_id']
		);

		if ($data['user_id'])
		{
			$viewParams['adsList'] = $this->_getAdsModel()->getAllAds('', array('user_id' => $data['user_id']));
		}

		if ($data['save'])
		{
			$errors = array();

			if (!$data['ads'])
			{
				$errors[] = new XenForo_Phrase('siropu_ads_manager_no_ads_selected');
			}

			if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserByName($data['new_owner']))
			{
				$errors[] = new XenForo_Phrase('siropu_ads_manager_no_user_selected');
			}

			if (!$errors)
			{
				foreach ($data['ads'] as $id)
				{
					$ad = $this->_getAdsModel()->getAdJoinPackageById($id);

					$dwData = array(
						'user_id'  => $user['user_id'],
						'username' => $user['username']
					);

					if (!$ad['purchase'])
					{
						$dwData['purchase'] = $ad['min_purchase'] ? $ad['min_purchase'] : 1;
					}

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($id);
					$dw->bulkSet($dwData);
					$dw->save();

					if ($data['include_invoices']
						&& ($invoices = $this->_getTransactionsModel()->getAllTransactions(array('ad_id' => $id))))
					{
						foreach ($invoices as $invoice)
						{
							$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
							$dw->setExistingData($invoice['transaction_id']);
							$dw->bulkSet(array(
								'user_id'  => $user['user_id'],
								'username' => $user['username']
							));
							$dw->save();
						}
					}
				}

				if ($data['add_to_groups'])
				{
					$this->_getUserModel()->changeUserGroups($data['new_owner']);
				}

				$viewParams['success'] = true;
			}
			else
			{
				$viewParams['errors'] = implode('<br>', $errors);
			}
		}

		return $this->responseView('', 'siropu_ads_manager_tools_change_owner', $viewParams);
	}
	public function actionPlaceholders()
	{
		$packages = $this->_input->filterSingle('packages', XenForo_Input::ARRAY_SIMPLE);
		$generate = $this->_input->filterSingle('generate', XenForo_Input::STRING);
		$delete   = $this->_input->filterSingle('delete', XenForo_Input::STRING);

		if (($generate || $delete) && !$packages)
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_tools_placeholders_no_packages_selected'));
		}

		if ($generate)
		{
			$packageList = $this->_getPackagesModel()->getPackagesByIds(array_map('intval', $packages));
			$costPerList = $this->_getHelperGeneral()->getCostPerList();

			foreach ($packageList as $package)
			{
				$packageId  = $package['package_id'];
				$packageAds = $this->_getAdsModel()->getAllAds('Active', array('package_id' => $packageId));

				if (!$this->_getAdsModel()->packagePlaceholderExists($packageId))
				{
					$adUrl = XenForo_Link::buildPublicLink('canonical:advertising/ads/create', '',
						array('package_id' => $packageId));
					$adDescription = new XenForo_Phrase('siropu_ads_manager_advertise_here_description', array(
						'costAmount'   => $this->_getHelperTemplate()->helperCostAmount($package),
						'costCurrency' => $this->_getHelperTemplate()->helperCostCurrency($package['cost_currency']),
						'costPer'      => $costPerList[$package['cost_per']]['singular'],
					));
					$adStyle = @unserialize($package['style']);

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->bulkSet($this->_getPackagesModel()->getInheritData(array(
						'type'             => $package['type'],
						'name'             => new XenForo_Phrase('siropu_ads_manager_advertise_here_placeholder'),
						'url'              => $adUrl,
						'title'            => $package['type'] == 'link' ? $adDescription : new XenForo_Phrase('siropu_ads_manager_advertise_here_title'),
						'description'      => $adDescription,
						'code'             => $adStyle['size'] ? '<a href="' . $adUrl . '"><img src="http://placehold.it/' . $adStyle['size'] . '.png/' . $this->_getOptions()->siropu_ads_manager_placeholder_backgound_color . '/' . $this->_getOptions()->siropu_ads_manager_placeholder_text_color . '/&title=' . urlencode($adDescription) . '" class="samPlaceholder"></a>' : '<a href="' . $adUrl . '">' . $adDescription . '</a>',
						'package_id'       => $packageId,
						'geoip_criteria'   => array(),
						'inherit_settings' => 1,
						'is_placeholder'   => 1,
						'count_exclude'    => 1,
						'status'           => $packageAds ? 'Inactive' : 'Active'
					)));
					$dw->save();
				}
			}

			$this->_getHelperGeneral()->refreshActiveAdsCache();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('ad-tools/placeholders'));
		}

		if ($delete)
		{
			$this->_getAdsModel()->deletePlaceholdersByPackageIds($packages);
			$this->_getHelperGeneral()->refreshActiveAdsCache();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('ad-tools/placeholders'));
		}

		$viewParams = array(
			'placeholders' => $this->_getAdsModel()->getPlaceholders(),
			'statusList'   => $this->_getHelperGeneral()->getStatusList(),
			'typeList'     => $this->_getHelperGeneral()->getAdTypes(),
			'packages'     => $this->_getPackagesModel()->getPackagesForPlaceholders()
		);

		return $this->responseView('', 'siropu_ads_manager_tools_placeholders', $viewParams);
	}
	public function actionExport()
	{
		$packageId = $this->_input->filterSingle('package_id', XenForo_Input::UINT);
		$adId      = $this->_input->filterSingle('ad_id', XenForo_Input::UINT);
		$export    = $this->_input->filterSingle('export', XenForo_Input::ARRAY_SIMPLE);

		if ($packageId && !$this->_input->filterSingle('submit', XenForo_Input::STRING))
		{
			return $this->responseView('', 'siropu_ads_manager_tools_export', array('packageId' => $packageId));
		}

		$exported = 0;
		$where    = array();

		if ($adId)
		{
			$where['ad_id'] = $adId;
			$export['ads']  = true;
		}
		if ($packageId)
		{
			$where['package_id'] = $packageId;
		}

		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;

		$parentNode = $document->createElement('items');

		if (isset($export['package']) && ($package = $this->_getPackagesModel()->getPackageById($packageId)))
		{
			$node = $document->createElement('package');

			$idNode = $document->createElement('package_id', $package['package_id']);
			$node->appendChild($idNode);

			$typeNode = $document->createElement('type', $package['type']);
			$node->appendChild($typeNode);

			$nameNode = $document->createElement('name');
			$nameNode->appendChild($document->createCDATASection($package['name']));
			$node->appendChild($nameNode);

			$descriptionNode = $document->createElement('description');
			$descriptionNode->appendChild($document->createCDATASection($package['description']));
			$node->appendChild($descriptionNode);

			$positionsNode = $document->createElement('positions', $package['positions']);
			$node->appendChild($positionsNode);

			$itemIdNode = $document->createElement('item_id', $package['item_id']);
			$node->appendChild($itemIdNode);

			$costAmountNode = $document->createElement('cost_amount', $package['cost_amount']);
			$node->appendChild($costAmountNode);

			$costListNode = $document->createElement('cost_list', $package['cost_list']);
			$node->appendChild($costListNode);

			$costCurrencyNode = $document->createElement('cost_currency', $package['cost_currency']);
			$node->appendChild($costCurrencyNode);

			$costPerNode = $document->createElement('cost_per', $package['cost_per']);
			$node->appendChild($costPerNode);

			$minPurchaseNode = $document->createElement('min_purchase', $package['min_purchase']);
			$node->appendChild($minPurchaseNode);

			$maxPurchaseNode = $document->createElement('max_purchase', $package['max_purchase']);
			$node->appendChild($maxPurchaseNode);

			$discountNode = $document->createElement('discount', $package['discount']);
			$node->appendChild($discountNode);

			$advertiseHereNode = $document->createElement('advertise_here', $package['advertise_here']);
			$node->appendChild($advertiseHereNode);

			$styleNode = $document->createElement('style', $package['style']);
			$node->appendChild($styleNode);

			$maxItemsAllowedNode = $document->createElement('max_items_allowed', $package['max_items_allowed']);
			$node->appendChild($maxItemsAllowedNode);

			$maxItemsDisplayNode = $document->createElement('max_items_display', $package['max_items_display']);
			$node->appendChild($maxItemsDisplayNode);

			$adsOrderNode = $document->createElement('ads_order', $package['ads_order']);
			$node->appendChild($adsOrderNode);

			$countAdViewsNode = $document->createElement('count_ad_views', $package['count_ad_views']);
			$node->appendChild($countAdViewsNode);

			$countAdClicksNode = $document->createElement('count_ad_clicks', $package['count_ad_clicks']);
			$node->appendChild($countAdClicksNode);

			$dailyStatsNode = $document->createElement('daily_stats', $package['daily_stats']);
			$node->appendChild($dailyStatsNode);

			$clickStatsNode = $document->createElement('click_stats', $package['click_stats']);
			$node->appendChild($clickStatsNode);

			$nofollowNode = $document->createElement('nofollow', $package['nofollow']);
			$node->appendChild($nofollowNode);

			$targetBlankNode = $document->createElement('target_blank', $package['target_blank']);
			$node->appendChild($targetBlankNode);

			$hideFromRobotsNode = $document->createElement('hide_from_robots', $package['hide_from_robots']);
			$node->appendChild($hideFromRobotsNode);

			$jsRotatorNode = $document->createElement('js_rotator', $package['js_rotator']);
			$node->appendChild($jsRotatorNode);

			$jsIntervalNode = $document->createElement('js_interval', $package['js_interval']);
			$node->appendChild($jsIntervalNode);

			$keywordLimitNode = $document->createElement('keyword_limit', $package['keyword_limit']);
			$node->appendChild($keywordLimitNode);

			$positionCriteriaNode = $document->createElement('position_criteria');
			$positionCriteriaNode->appendChild($document->createCDATASection($package['position_criteria']));
			$node->appendChild($positionCriteriaNode);

			$pageCriteriaNode = $document->createElement('page_criteria', $package['page_criteria']);
			$node->appendChild($pageCriteriaNode);

			$userCriteriaNode = $document->createElement('user_criteria');
			$userCriteriaNode->appendChild($document->createCDATASection($package['user_criteria']));
			$node->appendChild($userCriteriaNode);

			$deviceCriteriaNode = $document->createElement('device_criteria', $package['device_criteria']);
			$node->appendChild($deviceCriteriaNode);

			$geoIpCriteriaNode = $document->createElement('geoip_criteria', $package['geoip_criteria']);
			$node->appendChild($geoIpCriteriaNode);

			$guidelinesNode = $document->createElement('guidelines');
			$guidelinesNode->appendChild($document->createCDATASection($package['guidelines']));
			$node->appendChild($guidelinesNode);

			$advertiserCriteriaNode = $document->createElement('advertiser_criteria', $package['advertiser_criteria']);
			$node->appendChild($advertiserCriteriaNode);

			$previewNode = $document->createElement('preview', $package['preview']);
			$node->appendChild($previewNode);

			$enabledNode = $document->createElement('enabled', $package['enabled']);
			$node->appendChild($enabledNode);

			$parentNode->appendChild($node);

			$exported += 1;
		}

		if (isset($export['ads']) && ($ads = $this->_getAdsModel()->getAllAds('', $where)))
		{
			$adsNode = $document->createElement('ads');
			$parentNode->appendChild($adsNode);

			foreach ($ads as $val)
			{
				$adNode = $document->createElement('ad');

				if ($exported)
				{
					$packageIdNode = $document->createElement('package_id', intval($val['package_id']));
					$adNode->appendChild($packageIdNode);
				}

				$userIdNode = $document->createElement('user_id', intval($val['user_id']));
				$adNode->appendChild($userIdNode);

				$usernameNode = $document->createElement('username');
				$usernameNode->appendChild($document->createCDATASection($val['username']));
				$adNode->appendChild($usernameNode);

				$nameNode = $document->createElement('name');
				$nameNode->appendChild($document->createCDATASection($val['name']));
				$adNode->appendChild($nameNode);

				$typeNode = $document->createElement('type', $val['type']);
				$adNode->appendChild($typeNode);

				$positionsNode = $document->createElement('positions', $val['positions']);
				$adNode->appendChild($positionsNode);

				$itemIdNode = $document->createElement('item_id', $val['item_id']);
				$adNode->appendChild($itemIdNode);

				$codeNode = $document->createElement('code');
				$codeNode->appendChild(XenForo_Helper_DevelopmentXml::createDomCdataSection($document, $val['code']));
				$adNode->appendChild($codeNode);

				$urlNode = $document->createElement('url');
				$urlNode->appendChild($document->createCDATASection($val['url']));
				$adNode->appendChild($urlNode);

				$bannerNode = $document->createElement('banner', $val['banner']);
				$adNode->appendChild($bannerNode);

				$bannerExtraNode = $document->createElement('banner_extra', $val['banner_extra']);
				$adNode->appendChild($bannerExtraNode);

				$titleNode = $document->createElement('title');
				$titleNode->appendChild($document->createCDATASection($val['title']));
				$adNode->appendChild($titleNode);

				$descNode = $document->createElement('description');
				$descNode->appendChild($document->createCDATASection($val['description']));
				$adNode->appendChild($descNode);

				$itemsNode = $document->createElement('items', $val['items']);
				$adNode->appendChild($itemsNode);

				$purchaseNode = $document->createElement('purchase', $val['purchase']);
				$adNode->appendChild($purchaseNode);

				$extendNode = $document->createElement('extend', $val['extend']);
				$adNode->appendChild($extendNode);

				$dateStartNode = $document->createElement('date_start', $val['date_start']);
				$adNode->appendChild($dateStartNode);

				$dateEndNode = $document->createElement('date_end', $val['date_end']);
				$adNode->appendChild($dateEndNode);

				$countViewsNode = $document->createElement('count_views', $val['count_views']);
				$adNode->appendChild($countViewsNode);

				$viewLimitNode = $document->createElement('view_limit', $val['view_limit']);
				$adNode->appendChild($viewLimitNode);

				$countClicksNode = $document->createElement('count_clicks', $val['count_clicks']);
				$adNode->appendChild($countClicksNode);

				$clickLimitNode = $document->createElement('click_limit', $val['click_limit']);
				$adNode->appendChild($clickLimitNode);

				$dailyStatsNode = $document->createElement('daily_stats', $val['daily_stats']);
				$adNode->appendChild($dailyStatsNode);

				$clickStatsNode = $document->createElement('click_stats', $val['click_stats']);
				$adNode->appendChild($clickStatsNode);

				$gaStatsNode = $document->createElement('ga_stats', $val['ga_stats']);
				$adNode->appendChild($gaStatsNode);

				$nofollowNode = $document->createElement('nofollow', $val['nofollow']);
				$adNode->appendChild($nofollowNode);

				$targetBlankNode = $document->createElement('target_blank', $val['target_blank']);
				$adNode->appendChild($targetBlankNode);

				$hideFromRobotsNode = $document->createElement('hide_from_robots', $val['hide_from_robots']);
				$adNode->appendChild($hideFromRobotsNode);

				$adOrderNode = $document->createElement('ad_order', $val['ad_order']);
				$adNode->appendChild($adOrderNode);

				$priorityNode = $document->createElement('priority', $val['priority']);
				$adNode->appendChild($priorityNode);

				$displayAferNode = $document->createElement('display_after', $val['display_after']);
				$adNode->appendChild($displayAferNode);

				$hideAferNode = $document->createElement('hide_after', $val['hide_after']);
				$adNode->appendChild($hideAferNode);

				$inheritSettingsNode = $document->createElement('inherit_settings', $val['inherit_settings']);
				$adNode->appendChild($inheritSettingsNode);

				$isPlaceholderNode = $document->createElement('is_placeholder', $val['is_placeholder']);
				$adNode->appendChild($isPlaceholderNode);

				$countExcludeNode = $document->createElement('count_exclude', $val['count_exclude']);
				$adNode->appendChild($countExcludeNode);

				$keywordLimitNode = $document->createElement('keyword_limit', $val['keyword_limit']);
				$adNode->appendChild($keywordLimitNode);

				$positionCriteriaNode = $document->createElement('position_criteria');
				$positionCriteriaNode->appendChild($document->createCDATASection($val['position_criteria']));
				$adNode->appendChild($positionCriteriaNode);

				$pageCriteriaNode = $document->createElement('page_criteria', $val['page_criteria']);
				$adNode->appendChild($pageCriteriaNode);

				$userCriteriaNode = $document->createElement('user_criteria', $val['user_criteria']);
				$adNode->appendChild($userCriteriaNode);

				$deviceCriteriaNode = $document->createElement('device_criteria', $val['device_criteria']);
				$adNode->appendChild($deviceCriteriaNode);

				$geoIpCriteriaNode = $document->createElement('geoip_criteria', $val['geoip_criteria']);
				$adNode->appendChild($geoIpCriteriaNode);

				$notesNode = $document->createElement('notes');
				$notesNode->appendChild($document->createCDATASection($val['notes']));
				$adNode->appendChild($notesNode);

				$dateCreatedNode = $document->createElement('date_created', $val['date_created']);
				$adNode->appendChild($dateCreatedNode);

				$dateActivedNode = $document->createElement('date_active', $val['date_active']);
				$adNode->appendChild($dateActivedNode);

				$dateLastChangeNode = $document->createElement('date_last_change', $val['date_last_change']);
				$adNode->appendChild($dateLastChangeNode);

				$dateLastActiveNode = $document->createElement('date_last_active', $val['date_last_active']);
				$adNode->appendChild($dateLastActiveNode);

				$viewCountNode = $document->createElement('view_count', $val['view_count']);
				$adNode->appendChild($viewCountNode);

				$clickCountNode = $document->createElement('click_count', $val['click_count']);
				$adNode->appendChild($clickCountNode);

				$ctrNode = $document->createElement('ctr', $val['ctr']);
				$adNode->appendChild($ctrNode);

				$emailNotificationsNode = $document->createElement('email_notifications', $val['email_notifications']);
				$adNode->appendChild($emailNotificationsNode);

				$alertNotificationsNode = $document->createElement('alert_notifications', $val['alert_notifications']);
				$adNode->appendChild($alertNotificationsNode);

				$subscriptionNode = $document->createElement('subscription', $val['subscription']);
				$adNode->appendChild($subscriptionNode);

				$rejectReasonNode = $document->createElement('reject_reason');
				$rejectReasonNode->appendChild($document->createCDATASection($val['reject_reason']));
				$adNode->appendChild($rejectReasonNode);

				$noticeSentNode = $document->createElement('notice_sent', $val['notice_sent']);
				$adNode->appendChild($noticeSentNode);

				$pendingTransactionNode = $document->createElement('pending_transaction', $val['pending_transaction']);
				$adNode->appendChild($pendingTransactionNode);

				$statusOldNode = $document->createElement('status_old', $val['status_old']);
				$adNode->appendChild($statusOldNode);

				$statusNode = $document->createElement('status', $val['status']);
				$adNode->appendChild($statusNode);

				$adsNode->appendChild($adNode);
			}

			$exported += 1;
		}

		$document->appendChild($parentNode);

		if (!$exported)
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_export_error'));
		}

		$this->_routeMatch->setResponseType('xml');
		return $this->responseView('Siropu_AdsManager_ViewAdmin_Export', '', array('xml' => $document, 'packageId' => $packageId, 'adId' => $adId));
	}
	public function actionImport()
	{
		$fileTransfer = new Zend_File_Transfer_Adapter_Http();
		if ($fileTransfer->isUploaded('upload_file'))
		{
			$fileInfo = $fileTransfer->getFileInfo('upload_file');
			$fileName = $fileInfo['upload_file']['tmp_name'];
		}
		else
		{
			$fileName = $this->_input->filterSingle('upload_file', XenForo_Input::STRING);
		}

		$viewParams = array();

		if ($fileName)
		{
			try
			{
				$document = XenForo_Helper_DevelopmentXml::scanFile($fileName);
			}
			catch (Exception $e)
			{
				throw new XenForo_Exception(
					new XenForo_Phrase('provided_file_was_not_valid_xml_file'), true
				);
			}

			$packageId = 0;

			if (isset($document->package->package_id))
			{
				$package = $document->package;

				$dwData = array(
					'package_id'          => $package->package_id,
					'type'                => $package->type,
					'name'                => $package->name,
					'description'         => $package->description,
					'positions'           => $package->positions,
					'item_id'             => $package->item_id,
					'cost_amount'         => $package->cost_amount,
					'cost_list'           => $package->cost_list,
					'cost_currency'       => $package->cost_currency,
					'cost_per'            => $package->cost_per,
					'min_purchase'        => $package->min_purchase,
					'max_purchase'        => $package->max_purchase,
					'discount'            => $package->discount,
					'advertise_here'      => $package->advertise_here,
					'style'               => $package->style,
					'max_items_allowed'   => $package->max_items_allowed,
					'max_items_display'   => $package->max_items_display,
					'ads_order'           => $package->ads_order,
					'count_ad_views'      => $package->count_ad_views,
					'count_ad_clicks'     => $package->count_ad_clicks,
					'daily_stats'         => $package->daily_stats,
					'click_stats'         => $package->click_stats,
					'nofollow'            => $package->nofollow,
					'target_blank'        => $package->target_blank,
					'hide_from_robots'    => $package->hide_from_robots,
					'js_rotator'          => $package->js_rotator,
					'js_interval'         => $package->js_interval,
					'keyword_limit'       => $package->keyword_limit,
					'position_criteria'   => $package->position_criteria,
					'page_criteria'       => $package->page_criteria,
					'user_criteria'       => $package->user_criteria,
					'device_criteria'     => $package->device_criteria,
					'geoip_criteria'      => $package->geoip_criteria,
					'advertiser_criteria' => $package->advertiser_criteria,
					'guidelines'          => $package->guidelines,
					'preview'             => $package->preview,
					'enabled'             => $package->enabled
				);

				if ($this->_getPackagesModel()->getPackageById($package->package_id))
				{
					unset($dwData['package_id']);
				}

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Packages');
				$dw->bulkSet($dwData);
				$dw->save();

				if (!isset($dwData['package_id']))
				{
					$packageId = $dw->get('package_id');
				}

				$viewParams['package'] = $dw->get('name');
			}

			if (isset($document->ads->ad))
			{
				foreach ($document->ads->ad as $ad)
				{
					$dwData = array(
						'package_id'          => $packageId ? $packageId : $ad->package_id,
						'user_id'             => $ad->user_id,
						'username'            => $ad->username,
						'name'                => $ad->name,
						'type'                => $ad->type,
						'positions'           => $ad->positions,
						'item_id'             => $ad->item_id,
						'code'                => $ad->code,
						'url'                 => $ad->url,
						'banner'              => $ad->banner,
						'banner_extra'        => $ad->banner_extra,
						'title'               => $ad->title,
						'description'         => $ad->description,
						'items'               => $ad->items,
						'purchase'            => $ad->purchase,
						'extend'              => $ad->extend,
						'date_start'          => $ad->date_start,
						'date_end'            => $ad->date_end,
						'count_views'         => $ad->count_views,
						'view_limit'          => $ad->view_limit,
						'count_clicks'        => $ad->count_clicks,
						'click_limit'         => $ad->click_limit,
						'daily_stats'         => $ad->daily_stats,
						'click_stats'         => $ad->click_stats,
						'ga_stats'            => $ad->ga_stats,
						'nofollow'            => $ad->nofollow,
						'target_blank'        => $ad->target_blank,
						'hide_from_robots'    => $ad->hide_from_robots,
						'ad_order'            => $ad->ad_order,
						'priority'            => $ad->priority,
						'display_after'       => $ad->display_after,
						'hide_after'          => $ad->hide_after,
						'inherit_settings'    => $ad->inherit_settings,
						'is_placeholder'      => $ad->is_placeholder,
						'count_exclude'       => $ad->count_exclude,
						'keyword_limit'       => $ad->keyword_limit,
						'position_criteria'   => $ad->position_criteria,
						'page_criteria'       => $ad->page_criteria,
						'user_criteria'       => $ad->user_criteria,
						'device_criteria'     => $ad->device_criteria,
						'geoip_criteria'      => $ad->geoip_criteria,
						'notes'               => $ad->notes,
						'date_created'        => $ad->date_created,
						'date_active'         => $ad->date_active,
						'date_last_change'    => $ad->date_last_change,
						'date_last_active'    => $ad->date_last_active,
						'view_count'          => $ad->view_count,
						'click_count'         => $ad->click_count,
						'ctr'                 => $ad->ctr,
						'email_notifications' => $ad->email_notifications,
						'alert_notifications' => $ad->alert_notifications,
						'subscription'        => $ad->subscription,
						'reject_reason'       => $ad->reject_reason,
						'notice_sent'         => $ad->notice_sent,
						'pending_transaction' => $ad->pending_transaction,
						'status_old'          => $ad->status_old,
						'status'              => $ad->status
					);

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->bulkSet($dwData);
					$dw->save();

					$viewParams['ads'][] = $dw->get('name');
				}
			}
		}

		return $this->responseView('', 'siropu_ads_manager_tools_import', $viewParams);
	}
	public function actionReset()
	{
		$this->assertDebugMode();

		if ($this->isConfirmedPost())
		{
			$this->_getToolsModel()->reset($this->_input->filterSingle('reset', XenForo_Input::ARRAY_SIMPLE));

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('ads-manager'));
		}

		return $this->responseView('', 'siropu_ads_manager_tools_reset');
	}
	public function actionContentTemplateList()
	{
		return $this->responseView('', 'siropu_ads_manager_tools_content_template_list');
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
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getToolsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Tools');
	}
	protected function _getUserModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_User');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getHelperTemplate()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_Template');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getVisitor()
	{
		return XenForo_Visitor::getInstance();
	}
}

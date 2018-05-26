<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerPublic_Index extends XenForo_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
	{
		$guestMode = $this->_getOptions()->siropu_ads_manager_guest_mode;

		if (!$this->_getHelperGeneral()->userHasPermission('create')
			&& (!$guestMode['enabled'] || $guestMode['enabled'] && $this->_getUserID()))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('do_not_have_permission')));
		}
	}
	public static function getSessionActivityDetailsForList(array $activities)
    {
        return new XenForo_Phrase('siropu_ads_manager_viewing_page');
    }
	public function actionIndex()
	{
		$packages = $this->_getPackagesModel()->getPackagesForUser();
		$typeList = $this->_getHelperGeneral()->getPackageTypeList($packages);

		$viewParams = array(
			'typeCount'      => count($typeList),
			'statusList'     => $this->_getHelperGeneral()->getStatusList(),
			'typeList'       => implode(', ', $typeList),
			'pauseOptions'   => $this->_getOptions()->siropu_ads_manager_user_pause_ads,
			'adTypeList'     => $this->_getHelperGeneral()->getAdTypes(),
			'promoCodeCount' => count($this->_getPromoCodesModel()->getPromoCodesForUser())
		);

		if ($pendingTransactions = $this->_getUserModel()->getUserPendingTransactions($this->_getUserID()))
		{
			$viewParams['transactions'] = $pendingTransactions;
			$viewParams['pendingCount'] = count($pendingTransactions);
		}

		if ($expiringAds = $this->_getUserModel()->getUserExpiringAds($this->_getUserID()))
		{
			$viewParams['ads']           = $expiringAds;
			$viewParams['expiringCount'] = count($expiringAds);
			$viewParams['statusList']    = $this->_getHelperGeneral()->getStatusList();
		}

		if ($this->_getVisitor()->is_admin && ($pendingAds = $this->_getAdsModel()->getAllAds('Pending')))
		{
			$viewParams['pendingAds'] = $pendingAds;
		}

		return $this->responseView('', 'siropu_ads_manager', $viewParams);
	}
	public function actionAds()
	{
		$ads = $this->_getUserModel()->getAllUserAds($this->_getUserID());

		foreach ($ads as $key => $val)
		{
			$adIsExpiring = $this->_getHelperGeneral()->adIsExpiring($val);

			if ($adIsExpiring)
			{
				$ads[$key]['expiring'] = $this->_getHelperGeneral()->calculateAdTimeActionLeft($val);
			}
			if (in_array($val['status'], array('Active', 'Inactive'))
				&& !$val['extend']
				&& !$this->_getOptions()->siropu_ads_manager_free_mode)
			{
				$ads[$key]['optionExtend'] = true;
			}
			if ($val['status'] == 'Active' && $this->_getOptions()->siropu_ads_manager_user_pause_ads['enabled'])
			{
				$ads[$key]['optionPause'] = true;
			}
			if ($val['status'] == 'Paused')
			{
				$ads[$key]['optionUnpause'] = true;
			}
			if (!in_array($val['status'], array('Pending', 'Rejected', 'Queued')) && $val['daily_stats'] && $this->_getHelperGeneral()->userHasPermission('daily_stats'))
			{
				$ads[$key]['optionStatsDaily'] = true;
			}
			if (!in_array($val['status'], array('Pending', 'Rejected', 'Queued')) && $val['click_stats'] && $this->_getHelperGeneral()->userHasPermission('click_stats'))
			{
				$ads[$key]['optionStatsClicks'] = true;
			}
		}

		$viewParams = array(
			'pageSelected' => 'ads',
			'ads'          => $ads,
			'statusList'   => $this->_getHelperGeneral()->getStatusList(),
			'adTypeList'   => $this->_getHelperGeneral()->getAdTypes()
		);

		return $this->responseView('', 'siropu_ads_manager_ad_list', $viewParams);
	}
	public function actionAdsEdit()
	{
		if ($ad = $this->_getUserAd())
		{
			if ($this->_getHelperGeneral()->isSwf($ad['banner']))
			{
				$ad['flash'] = true;
			}

			$ad['banner_extra'] = $this->_getHelperGeneral()->getExtraBannersList($ad['banner_extra']);

			$viewParams = array(
				'packageId' => $ad['package_id'],
				'ad'        => $ad
			);

			return $this->_getAdAddEditResponse($viewParams);
		}

		return $this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_not_found'));
	}
	public function actionAdsCreate()
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);

		if ($packageId = $this->_input->filterSingle('package_id', XenForo_Input::UINT))
		{
			$guestMode = $this->_getOptions()->siropu_ads_manager_guest_mode;

			if ($guestMode['enabled'] && !$this->_getHelperGeneral()->userHasPermission('create'))
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink($guestMode['page'] ? $guestMode['page'] : 'login')
				);
			}

			return $this->_getAdAddEditResponse(array('packageId' => $packageId));
		}

		$packageList      = $this->_getPackagesModel()->getPackagesForUser();
		$packageGroupType = $this->_getHelperGeneral()->groupPackagesByType($this->_getHelperGeneral()->setAvailableAdSlotCount($packageList, true));
		$packageTypeList  = array();

		if ($packageList && count($packageGroupType) == 1 && !$type)
		{
			$type = current(array_keys($packageGroupType));
		}

		if (isset($packageGroupType[$type]))
		{
			$packageTypeList = $packageGroupType[$type];
		}
		else
		{
			foreach ($packageGroupType as $key => $val)
			{
				$packageTypeList = array_merge($packageTypeList, $val);
			}
		}

		$viewParams = array(
			'pageSelected' => 'ads/create',
			'typeList'     => $this->_getHelperGeneral()->getAdTypes(),
			'costPerList'  => $this->_getHelperGeneral()->getCostPerList(),
			'currentType'  => $type,
			'packageList'  => $packageTypeList,
			'packageTypes' => $packageGroupType
		);

		return $this->responseView('', 'siropu_ads_manager_package_list', $viewParams);
	}
	public function actionAdsSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'name'                => XenForo_Input::STRING,
			'package_id'          => XenForo_Input::STRING,
			'code'                => XenForo_Input::STRING,
			'banner'              => XenForo_Input::STRING,
			'banner_url'          => XenForo_Input::STRING,
			'url'                 => XenForo_Input::STRING,
			'title'               => XenForo_Input::STRING,
			'description'         => XenForo_Input::STRING,
			'items'               => XenForo_Input::STRING,
			'purchase'            => XenForo_Input::UINT,
			'email_notifications' => XenForo_Input::UINT,
			'alert_notifications' => XenForo_Input::UINT,
			'page_criteria'       => XenForo_Input::ARRAY_SIMPLE,
			'user_criteria'       => XenForo_Input::ARRAY_SIMPLE,
			'geoip_criteria'      => XenForo_Input::ARRAY_SIMPLE,
			'device_criteria'     => XenForo_Input::ARRAY_SIMPLE,
			'notes'               => XenForo_Input::STRING
		));

		if ($threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT))
		{
			$dwData['items'] = $threadId;
		}

		if ($resourceId = $this->_input->filterSingle('resource_id', XenForo_Input::UINT))
		{
			$dwData['items'] = $resourceId;
		}

		if ($dwData['banner_url'])
		{
			if (!$this->_getHelperGeneral()->userHasPermission('useBannerUrl'))
			{
				$dwData['banner_url'] = '';
			}
		}

		if (!$pkg = $this->_getPackagesModel()->getPackageById($dwData['package_id']))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_user_no_package_selected'));
		}

		$dwData['type']            = $pkg['type'];
		$dwData['geoip_criteria']  = serialize($dwData['geoip_criteria']);
		$dwData['device_criteria'] = serialize($dwData['device_criteria']);

		if ($dwData['type'] == 'banner' && $dwData['code'])
		{
			if (!$this->_getHelperGeneral()->userHasPermission('banner_code'))
			{
				$dwData['code'] = '';
			}

			if ($dwData['code'])
			{
				if ($pkg['nofollow'] && !preg_match('/(rel=("|\')nofollow("|\'))/i', $dwData['code']))
				{
					$dwData['code'] = preg_replace('/(<a)/i', '$1 rel="nofollow"', $dwData['code']);
				}

				if ($pkg['target_blank'])
				{
					if (preg_match('/(target=("|\')(.+?)("|\'))/i', $dwData['code']))
					{
						$dwData['code'] = preg_replace('/(target=("|\')(.+?)("|\'))/i', 'target="_blank"', $dwData['code']);
					}
					else if (!preg_match('/(target=("|\')_blank("|\'))/i', $dwData['code']))
					{
						$dwData['code'] = preg_replace('/(<a)/i', '$1 target="_blank"', $dwData['code']);
					}
				}
			}
		}

		$ad = $this->_getUserAd();

		if ($error = $this->_getHelperGeneral()->validateAdInput($dwData, $ad))
		{
			return $this->responseError($error);
		}

		if (!$dwData['purchase'])
		{
			$dwData['purchase'] = $ad ? $ad['purchase'] : $pkg['min_purchase'];
		}

		$pkg['style'] = @unserialize($pkg['style']);
		$bannerSize = $this->_getHelperGeneral()->getAdWidthHeight($pkg, true);

		if (($upload = XenForo_Upload::getUploadedFile('banner'))
			&& ($banner = $this->_getHelperGeneral()->uploadBanner($upload, $this->_getUserID(), $bannerSize)))
		{
			if ($dwData['banner'])
			{
				$this->_getHelperGeneral()->deleteBanner($dwData['banner']);
			}

			$dwData['banner'] = $banner;
		}

		if ($this->_getHelperGeneral()->userHasPermission('extraBanners')
			&& ($banners = XenForo_Upload::getUploadedFiles('banner_extra')))
		{
			$extraBanners = array();

			if ($ad && ($currentBanners = @unserialize($ad['banner_extra'])))
			{
				$extraBanners = $currentBanners;
			}

			foreach ($banners as $file)
			{
				if ($banner = $this->_getHelperGeneral()->uploadBanner($file, $this->_getUserID(), $bannerSize))
				{
					array_unshift($extraBanners, $banner);
				}
			}

			$dwData['banner_extra'] = @serialize($extraBanners);
		}

		if ($dwData['type'] == 'keyword' && ($notUnique = $this->_getHelperGeneral()->checkKeywordUniqueness($dwData['items'], ($ad ? $ad['ad_id'] : 0))))
		{
			return $this->responseError($notUnique);
		}

		$username = $this->_getVisitor()->username;

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		if ($ad)
		{
			if ($ad['package_id'] != $dwData['package_id'])
			{
				return $this->responseError(new XenForo_Phrase('siropu_ads_manager_user_action_not_allowed'));
			}

			if (in_array($ad['status'], array('Active', 'Approved'))
				&& in_array($ad['type'], array('keyword', 'sticky', 'featured')))
			{
				unset($dwData['items']);
			}

			$changes = array();

			if ($ad['status'] != 'Pending')
			{
				if ($ad['url'] != $dwData['url'])
				{
					$changes[] = new XenForo_Phrase('siropu_ads_manager_ad_url');
				}
				if ($ad['banner'] != $dwData['banner']
					|| !empty($dwData['banner_extra']) && $ad['banner_extra'] != $dwData['banner_extra'])
				{
					$changes[] = new XenForo_Phrase('siropu_ads_manager_ad_banner');
				}
				if ($ad['code'] != $dwData['code'])
				{
					$changes[] = new XenForo_Phrase('siropu_ads_manager_ad_input_code');
				}
				if ($ad['title'] != $dwData['title'])
				{
					$changes[] = new XenForo_Phrase('siropu_ads_manager_ad_title');
				}
				if ($ad['description'] != $dwData['description'])
				{
					$changes[] = new XenForo_Phrase('siropu_ads_manager_ad_description');
				}
			}

			if (!$this->_getHelperGeneral()->userHasPermission('edit_no_approval') && $changes)
			{
				if ($ad['status'] == 'Active')
				{
					$dwData['date_active']      = 0;
					$dwData['date_last_active'] = time();
				}

				if ($ad['status'] != 'Rejected')
				{
					$dwData['status_old'] = $ad['status'];
				}

				$dwData['status'] = 'Pending';

				$this->_sendAdminEmailNotification(array(
					'username' => $username,
					'name'     => $ad['name'],
					'type'     => $ad['type'],
					'changes'  => implode(', ', $changes),
					'id'       => $ad['ad_id']
				), 'siropu_ads_manager_admin_notification_ad_change');

				$this->_sendAdminAlertNotification(array(
					'user_id'  => $this->_getUserID(),
					'username' => $username,
					'ad_id'    => $ad['ad_id']
				), 'ad_changed');
			}

			$dw->setExistingData($ad['ad_id']);
		}
		else
		{
			$geoIPCriteria  = XenForo_Helper_Criteria::unserializeCriteria($dwData['geoip_criteria']);
			$deviceCriteria = XenForo_Helper_Criteria::unserializeCriteria($dwData['device_criteria']);

			$dwData = array_merge($dwData, array(
				'user_id'           => $this->_getUserID(),
				'username'          => $username,
				'positions'         => $pkg['positions'],
				'item_id'           => $pkg['item_id'],
				'count_views'       => $pkg['count_ad_views'],
				'count_clicks'      => $pkg['count_ad_clicks'],
				'daily_stats'       => $pkg['daily_stats'],
				'click_stats'       => $pkg['click_stats'],
				'nofollow'          => $pkg['nofollow'],
				'target_blank'      => $pkg['target_blank'],
				'hide_from_robots'  => $pkg['hide_from_robots'],
				'position_criteria' => $pkg['position_criteria'],
				'page_criteria'     => $pkg['page_criteria'],
				'user_criteria'     => $pkg['user_criteria'],
				'device_criteria'   => $deviceCriteria ? $deviceCriteria : unserialize($pkg['device_criteria']),
				'geoip_criteria'    => $geoIPCriteria ? $geoIPCriteria : unserialize($pkg['geoip_criteria'])
			));
		}
		$dw->bulkSet($dwData);
		$dw->save();

		if (!$ad)
		{
			if ($this->_getHelperGeneral()->userHasPermission('bypassApproval'))
			{
				$this->_getAdsModel()->approveAd($dw->get('ad_id'));

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('advertising/invoices'),
					new XenForo_Phrase('siropu_ads_manager_ad_successfully_created')
				);
			}
			else
			{
				$this->_sendAdminEmailNotification(array(
					'username' => $username,
					'name'     => $dwData['name'],
					'type'     => $dwData['type'],
					'changes'  => '',
					'id'       => $dw->get('ad_id')
				));

				$this->_sendAdminAlertNotification(array(
					'user_id'  => $this->_getUserID(),
					'username' => $username,
					'ad_id'    => $dw->get('ad_id')
				));
			}
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('advertising/ads'),
			($ad ? new XenForo_Phrase('siropu_ads_manager_ad_successfully_updated') : new XenForo_Phrase('siropu_ads_manager_ad_successfully_created'))
		);
	}
	public function actionAdsPause()
	{
		$pauseOptions = $this->_getOptions()->siropu_ads_manager_user_pause_ads;

		if ($pauseOptions['enabled'] && ($ad = $this->_getUserAd()) && $ad['status'] == 'Active')
		{
			$pauseLimit = $pauseOptions['max_length'];

			if ($this->isConfirmedPost())
			{
				$pauseLength = $this->_input->filterSingle('pause_length', XenForo_Input::UINT);
				$pauseOption = $this->_input->filterSingle('pause_option', XenForo_Input::STRING);

				if (!in_array($pauseOption, array('hours', 'days'))
					|| ($pauseOption == 'hours' && ($pauseLength / 24) > $pauseLimit)
					|| ($pauseOption == 'days' && $pauseLength > $pauseLimit))
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_pause_limit', array('limit' => $pauseLimit)));
				}

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($ad['ad_id']);
				$dw->set('date_start', strtotime("+{$pauseLength} {$pauseOption}"));
				$dw->set('date_last_active', time());
				$dw->set('status', 'Paused');
				$dw->save();

				if ($ad['type'] == 'sticky')
				{
					$this->_toggleSticky('Paused', $ad['items']);
				}

				if ($ad['type'] == 'featured')
				{
					$this->_toggleFeatured('Paused', $ad['items']);
				}

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('advertising/ads')
				);
			}
			else
			{
				$viewParams = array(
					'ad'         => $ad,
					'pauseLimit' => $pauseLimit
				);

				return $this->responseView('', 'siropu_ads_manager_ad_pause', $viewParams);
			}
		}
		return $this->responseError(new XenForo_Phrase('siropu_ads_manager_user_action_not_allowed'));
	}
	public function actionAdsUnpause()
	{
		if (($ad = $this->_getUserAd()) && $ad['status'] == 'Paused')
		{
			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
			$dw->setExistingData($ad['ad_id']);
			if ($end = $ad['date_end'])
			{
				$dw->set('date_end', $end + (time() - $ad['date_last_active']));
			}
			$dw->set('status', 'Active');
			$dw->save();

			if ($ad['type'] == 'sticky')
			{
				$this->_toggleSticky('Active', $ad['items']);
			}

			if ($ad['type'] == 'featured')
			{
				$this->_toggleFeatured('Active', $ad['items']);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('advertising/ads')
			);
		}
	}
	public function actionAdsExtend()
	{
		if ($ad = $this->_getUserModel()->getUserAdJoinPackageById($this->_getID(), $this->_getUserID()))
		{
			if ($ad['status'] != 'Active')
			{
				if (!$this->_getHelperGeneral()->checkForAvailableAdSlots($ad))
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_no_slots_available'));
				}

				if ($ad['type'] == 'keyword' && ($notUnique = $this->_getHelperGeneral()->checkKeywordUniqueness($ad['items'], $ad['ad_id'])))
				{
					return $this->responseError($notUnique);
				}
			}

			if ($this->isConfirmedPost())
			{
				$extend = $this->_input->filterSingle('extend', XenForo_Input::UINT);
				$extend = $extend ? $extend : $ad['min_purchase'];

				$ad['purchase'] = $extend;
				$this->getModelFromCache('Siropu_AdsManager_Model_Transactions')->generateTransaction($ad);

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($ad['ad_id']);
				$dw->set('extend', $extend);
				$dw->set('pending_transaction', 1);
				$dw->save();

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('advertising/invoices')
				);
			}

			$viewParams = array(
				'ad'           => $ad,
				'cost'         => $this->_getHelperGeneral()->calculateAdCost($ad),
				'discountList' => $this->_getHelperGeneral()->prepareGroupListForDisplay($ad['discount']),
				'costPerList'  => $this->_getHelperGeneral()->getCostPerList(),
				'step'         => $this->_getHelperGeneral()->getSpinboxStep($ad['cost_per'])
			);

			return $this->responseView('', 'siropu_ads_manager_ad_extend', $viewParams);
		}
		return $this->responseError(new XenForo_Phrase('siropu_ads_manager_user_action_not_allowed'));
	}
	public function actionAdsStats()
	{
		return $this->_getAdStatsResponse();
	}
	public function actionAdsStatsDaily()
	{
		if (!$this->_getHelperGeneral()->userHasPermission('daily_stats'))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('do_not_have_permission')));
		}

		return $this->_getAdStatsResponse('daily');
	}
	public function actionAdsStatsClicks()
	{
		if (!$this->_getHelperGeneral()->userHasPermission('click_stats'))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('do_not_have_permission')));
		}

		return $this->_getAdStatsResponse('clicks');
	}
	protected function _getAdStatsResponse($type = '')
	{
		if (!$ad = $this->_getUserAd())
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_not_found'));
		}

		$adId    = $this->_getID();
		$page    = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = 25;

		$conditions = $this->_input->filter(array(
			'start_date' => XenForo_Input::DATE_TIME,
			'end_date'   => XenForo_Input::DATE_TIME,
			'preset'     => XenForo_Input::DATE_TIME,
			'position'   => XenForo_Input::STRING,
			'group_by'   => XenForo_Input::STRING,
			'chart'      => XenForo_Input::UINT
		));

		$viewParams = array(
			'ad'           => $ad,
			'type'         => $type,
			'page'         => $page,
			'perPage'      => $perPage,
			'pageParams'   => array('id' => $adId),
			'datePresets'  => XenForo_Helper_Date::getDatePresets(),
			'conditions'   => $conditions,
			'pageSelected' => 'ads'
		);

		$conditions['chart'] = true;

		if ($startDate = $conditions['start_date'])
		{
			$viewParams['pageParams']['start_date'] = $startDate;
		}
		if ($endDate = $conditions['end_date'])
		{
			$viewParams['pageParams']['end_date'] = $endDate;
		}
		if ($preset = $conditions['preset'])
		{
			$viewParams['pageParams']['preset'] = $preset;
		}
		if ($position = $conditions['position'])
		{
			$viewParams['pageParams']['position'] = $position;
		}
		if ($groupBy = $conditions['group_by'])
		{
			$viewParams['pageParams']['group_by'] = $groupBy;
		}

		$fetchOptions = array(
			'page'    => $page,
			'perPage' => $perPage
		);

		switch ($type)
		{
			case 'daily':
				$viewParams['dailyStats'] = $this->_getStatsModel()->getDailyStats($adId, $conditions, $fetchOptions);
				$viewParams['total'] = $this->_getStatsModel()->getDailyStatsCount($adId, $conditions);
				return $this->responseView('', 'siropu_ads_manager_ad_stats_daily', $viewParams);
				break;
			case 'clicks':
				$viewParams['clickStats'] = $this->_getHelperGeneral()->prepareClicksStatsTooltipInfo($this->_getStatsModel()->getClickStats($adId, $conditions, $fetchOptions), false);
				$viewParams['total'] = $this->_getStatsModel()->getClickStatsCount($adId, $conditions);
				return $this->responseView('', 'siropu_ads_manager_ad_stats_clicks', $viewParams);
				break;
			default:
				return $this->responseView('', 'siropu_ads_manager_ad_stats_general', $viewParams);
				break;
		}
	}
	public function actionAdsDeleteBanner()
	{
		if (!$this->_getHelperGeneral()->userHasPermission('extraBanners'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		if (!$ad = $this->_getUserAd())
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_not_found'));
		}

		$file = $this->_input->filterSingle('file', XenForo_Input::STRING);
		$this->_getHelperGeneral()->deleteBanner($file);

		$extraBanners = @unserialize($ad['banner_extra']);

		foreach ($extraBanners as $key => $val)
		{
			if ($file == $val)
			{
				unset($extraBanners[$key]);
			}
		}

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		$dw->setExistingData($ad['ad_id']);
		$dw->set('banner_extra', serialize($extraBanners));
		$dw->save();

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', array('success' => true));
	}
	public function actionAdsDelete()
	{
		if ($ad = $this->_getUserAd())
		{
			if ($this->isConfirmedPost())
			{
				if ($ad['status'] == 'Active')
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_user_action_not_allowed'));
				}
				else
				{
					$this->_getAdsModel()->deleteAd($ad['ad_id']);

					if ($ad['banner'])
					{
						$this->_getHelperGeneral()->deleteBanner($ad['banner']);
					}

					return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('advertising/ads')
					);
				}
			}

			$viewParams['ad'] = $ad;
			return $this->responseView('', 'siropu_ads_manager_ad_delete_confirm', $viewParams);
		}
	}
	public function actionInvoices()
	{
		$viewParams = array(
			'pageSelected'   => 'invoices',
			'statusList'     => $this->_getHelperGeneral()->getStatusList(),
			'transactions'   => $this->_getUserModel()->getAllUserTransactions($this->_getUserID()),
			'promoCodeCount' => count($this->_getPromoCodesModel()->getPromoCodesForUser())
		);

		return $this->responseView('', 'siropu_ads_manager_transaction_list', $viewParams);
	}
	public function actionInvoicesDetails()
	{
		if (($id = $this->_input->filterSingle('id', XenForo_Input::UINT))
			&& ($transaction = $this->_getUserModel()->getUserTransactionJoinAdJoinPackageById($id, $this->_getUserID())))
		{
			$viewParams = array(
				'transaction'    => $transaction,
				'costPerList'    => $this->_getHelperGeneral()->getCostPerList(),
				'paymentOptions' => $this->_getHelperGeneral()->paymentOptions(),
			);

			return $this->responseView('', 'siropu_ads_manager_transaction_details', $viewParams);
		}
	}
	public function actionInvoicesPay()
	{
		$this->_assertPostOnly();

		if (($ids = $this->_input->filterSingle('id', XenForo_Input::ARRAY_SIMPLE))
			&& ($resultArray = $this->_getUserModel()->getUserTransactionsByIds($ids, $this->_getUserID())))
		{
			$total = 0;

			foreach ($resultArray as $row)
			{
				$total += $row['cost_amount'];
			}

			$invoice = implode(',', $ids);
			$amount  = $this->_getHelperGeneral()->formatPrice($total);

			$viewParams = array(
				'resultArray'  => $resultArray,
				'invoice'      => $invoice,
				'amount'       => $amount,
				'currency'     => $resultArray[0]['cost_currency'],
				'currencies'   => $this->_getHelperGeneral()->getCurrencyList(),
				'robokassaSig' => $this->_getHelperGeneral()->getRobokassaSignature(array('OutSum' => $amount, 'Shp_item' => $invoice)),
			);

			return $this->responseView('', 'siropu_ads_manager_transaction_pay', $viewParams);
		}

		return $this->responseError(new XenForo_Phrase('siropu_ads_manager_no_transactions_selected'));
	}
	public function actionInvoicesGetBankTransferData()
	{
		$viewParams = array(
			'invoice'      => str_replace(',', '-', $this->_input->filterSingle('invoice', XenForo_Input::STRING)),
			'amount'       => $this->_input->filterSingle('amount', XenForo_Input::STRING),
			'currency'     => $this->_getOptions()->siropu_ads_manager_currency,
			'accName'      => $this->_getOptions()->siropu_ads_manager_bank_account_name,
			'accNumber'    => $this->_getOptions()->siropu_ads_manager_bank_account_number,
			'bankName'     => $this->_getOptions()->siropu_ads_manager_bank_name,
			'swiftCode'    => $this->_getOptions()->siropu_ads_manager_bank_swift_code,
			'pidPrefix'    => $this->_getOptions()->siropu_ads_manager_bank_transfer_payment_id_prefix,
			'instructions' => $this->_getOptions()->siropu_ads_manager_bank_transfer_instructions
		);

		$template = new XenForo_Template_Public('siropu_ads_manager_bank_transfer_details', $viewParams);

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', 'siropu_ads_manager_bank_transfer_details',
			array_merge($viewParams, array('templateHtml' => $template)));
	}
	public function actionInvoicesGetZarinpalData()
	{
		$this->_assertPostOnly();

		$invoice = $this->_input->filterSingle('invoice', XenForo_Input::STRING);
		$amount  = floatval($this->_input->filterSingle('amount', XenForo_Input::STRING));

		$client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8'));

		$result = @$client->PaymentRequest(array(
			'MerchantID'  => $this->_getOptions()->siropu_ads_manager_zarinpal_merchant_id,
			'Amount' 	  => $amount,
			'Description' => new XenForo_Phrase('siropu_ads_manager_payment_name', array('boardTitle' => $this->_getOptions()->boardTitle)),
			'CallbackURL' => XenForo_Link::buildPublicLink('canonical:ipn/zarinpal', '', array('Invoice' => $invoice, 'Amount' => $amount))
		));

		if ($result->Status == 100)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
				'https://www.zarinpal.com/pg/StartPay/' . $result->Authority
			);
		}
		else
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_zarinpal_api_error'));
		}
	}
	public function actionInvoicesGetStripeData()
	{
		$this->_assertPostOnly();

		$viewParams = $this->_input->filter(array(
			'amount'   => XenForo_Input::STRING,
			'invoice'  => XenForo_Input::STRING,
			'currency' => XenForo_Input::STRING
		));

		return $this->responseView('', 'siropu_ads_manager_stripe_form', $viewParams);
	}
	public function actionInvoicesGetBitcoinData()
	{
		$this->_assertPostOnly();

		$inv            = $this->_input->filterSingle('invoice', XenForo_Input::STRING);
		$BtcAddress     = array_filter(explode("\n", $this->_getOptions()->siropu_ads_manager_bitcoin_address));
		shuffle($BtcAddress);
		$BtcAddress     = $BtcAddress[0];
		$currency       = $this->_getOptions()->siropu_ads_manager_currency;
		$bitcoinApi     = $this->_getOptions()->siropu_ads_manager_bitcoin_api;
		$secret         = $this->_getOptions()->siropu_ads_manager_bitcoin_api_secret;
		$fallback       = $this->_getOptions()->siropu_ads_manager_bitcoin_api_fallback;
		$callbackURL    = XenForo_Link::buildPublicLink('canonical:ipn/bitcoin', '', array('secret' => $secret));
		$redirectURL    = XenForo_Link::buildPublicLink('canonical:advertising/thank-you');
		$paymentName    = new XenForo_Phrase('siropu_ads_manager_payment_name',
			array('boardTitle' => $this->_getOptions()->boardTitle));
		$blockchainRoot = 'https://blockchain.info/';
		$amount         = 0;
		$BtcAmount      = 0;

		if ($resultArray = $this->_getTransactionsModel()->getTransactionsByIds(array_map('intval', explode(',', $inv))))
		{
			foreach ($resultArray as $row)
			{
				if ($row['status'] == 'Pending')
				{
					$amount += $row['cost_amount'];

					if ($row['cost_amount_btc'] > 0)
					{
						$BtcAmount += $row['cost_amount_btc'];
					}
					else
					{
						$btc = @file_get_contents($blockchainRoot . 'tobtc?currency=' . $row['cost_currency'] . '&value=' . $row['cost_amount']);

						$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
						$dw->setExistingData($row['transaction_id']);
						$dw->set('cost_amount_btc', $btc);
						$dw->save();

						$BtcAmount += $btc;
					}
				}
			}

			switch ($bitcoinApi)
			{
				case 'bitpay':
					require dirname(dirname(__FILE__)) . '/ThirdParty/BitPay/bp_lib.php';

					$response = bpCreateInvoice($inv, $amount, $inv, array(
						'currency'          => $currency,
						'posData'           => $inv,
						'redirectURL'       => $redirectURL,
						'notificationURL'   => $callbackURL,
						'apiKey'            => $this->_getOptions()->siropu_ads_manager_bitpay_api_key,
					));

					if (isset($response['url']))
					{
						return $this->responseRedirect(
							XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
							$response['url']
						);
					}
					else if (!$fallback)
					{
						return $this->responseError(new XenForo_Phrase('siropu_ads_manager_bitcoin_api_error'));
					}

					break;
				case 'coinbase':
					require dirname(dirname(__FILE__)) . '/ThirdParty/CoinBase/CoinBase.php';
					$response = json_decode(coinbaseRequest('buttons', 'post', json_encode(array(
						'name'               => $paymentName,
						'price_string'       => $amount,
						'price_currency_iso' => $currency,
						'custom'             => $inv,
						'callback_url'       => $callbackURL,
						'success_url'        => $redirectURL,
						'cancel_url'         => XenForo_Link::buildPublicLink('canonical:advertising/invoices')
					)), $this->_getOptions()->siropu_ads_manager_coinbase_api_key,
						$this->_getOptions()->siropu_ads_manager_coinbase_api_secret), true);

					if ($response && $response['success'])
					{
						return $this->responseRedirect(
							XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
							'https://www.coinbase.com/checkouts/' . $response['button']['code']
						);
					}
					else if (!$fallback)
					{
						return $this->responseError(new XenForo_Phrase('siropu_ads_manager_bitcoin_api_error'));
					}

					break;
				case 'blockchain':
					$response = json_decode(@file_get_contents('https://api.blockchain.info/v2/receive?xpub=' . $this->_getOptions()->siropu_ads_manager_xpub . '&callback='. urlencode($callbackURL . '&invoice=' . $inv) . '&key=' . $this->_getOptions()->siropu_ads_manager_blockchain_api_key_v2));

					if (isset($response->address))
					{
						$BtcAddress = $response->address;
					}
					else if (!$fallback)
					{
						return $this->responseError(new XenForo_Phrase('siropu_ads_manager_bitcoin_api_error'));
					}
					break;
			}
		}

		if (!$BtcAmount)
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_bitcoin_conversion_error'));
		}

		$viewParams = array(
			'BtcAmount'      => $BtcAmount,
			'BtcAddress'     => $BtcAddress,
			'timeLimit'      => $this->_getOptions()->siropu_ads_manager_transaction_time_limit,
			'blockchainRoot' => $blockchainRoot,
			'paymentName'    => urlencode($paymentName),
			'returnUrl'      => urlencode($redirectURL),
			'invoice'        => $inv
		);

		$template = new XenForo_Template_Public('siropu_ads_manager_bitcoin_details', $viewParams);

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', 'siropu_ads_manager_bitcoin_details',
			array_merge($viewParams, array('templateHtml' => $template)));
	}
	public function actionThankYou()
	{
		return $this->responseView('', 'siropu_ads_manager_thank_you',
			array('success' => $this->_input->filterSingle('success', XenForo_Input::STRING)));
	}
	protected function _getAdAddEditResponse($viewParams = array())
	{
		if (($packageId = $viewParams['packageId'])
			&& ($package = $this->_getPackagesModel()->getPackageById($packageId))
			&& $package['enabled'])
		{
			if (!$this->_getHelperGeneral()->advertiserMatchesCriteria($package['advertiser_criteria']))
			{
				return $this->responseError(new XenForo_Phrase('siropu_ads_manager_advertiser_criteria_error'));
			}

			$type = $package['type'];

			if ($type == 'featured' && !XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('XenResource'))
			{
				return $this->responseError(new XenForo_Phrase('siropu_ads_manager_resource_manager_required'));
			}

			$items          = isset($viewParams['ad']) ? $viewParams['ad']['items'] : 0;
			$packageAdCount = $this->_getAdsModel()->getAdCountByPackageId($packageId);
			$slotsInUse     = $packageAdCount['Active'] + $packageAdCount['Paused'] + $packageAdCount['oldActive'] + $packageAdCount['oldPaused'];

			if ($type == 'sticky')
			{
				$viewParams['stickyForumList'] = $this->_getHelperGeneral()->prepareForumListForDisplay($this->_getForumsModel()->getStickyForumList($this->_getOptions()->siropu_ads_manager_sticky_forum_list), $package['cost_amount'], $package['cost_list'], $package['cost_currency']);

				if (isset($viewParams['ad']))
				{
					$viewParams['sticky'] = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($items);
				}

				$forumsStickyList = $this->_getOptions()->siropu_ads_manager_sticky_forum_list;
				$slotsInUse = count($this->_getForumsModel()->getForumsStickyList($forumsStickyList));
				$package['max_items_allowed'] = $this->_getOptions()->siropu_ads_manager_max_stickies_per_forum * count($forumsStickyList['node_id']);

				if ($pausedAds = $this->_getAdsModel()->getAllAds('Paused', array('type' => 'sticky')))
				{
					$slotsInUse += count($pausedAds);
				}
			}

			if ($type == 'keyword')
			{
				$viewParams['keywordList'] = $this->_getHelperGeneral()->prepareGroupListForDisplay($package['cost_list']);
			}

			if ($type == 'featured')
			{
				$viewParams['categoryList'] = $this->_getHelperGeneral()->prepareResourceCategoryListForDisplay($this->getModelFromCache('XenResource_Model_Category')->getCategoriesByIds($this->_getOptions()->siropu_ads_manager_resource_cat_list), $package['cost_amount'], $package['cost_list'], $package['cost_currency']);

				if (isset($viewParams['ad']))
				{
					$viewParams['resource'] = $this->_getResourceModel()->getResourceById($items);
				}
			}

			if (!$this->_getID() && $package['cost_amount'] == '0.00' && $slotsInUse >= $package['max_items_allowed'])
			{
				return $this->responseError(new XenForo_Phrase('siropu_ads_manager_no_slots_available'));
			}

			$package['style'] = @unserialize($package['style']);
			$geoIPCriteria    = isset($viewParams['ad']) ? $viewParams['ad']['geoip_criteria'] : false;
			$deviceCriteria   = isset($viewParams['ad']) ? $viewParams['ad']['device_criteria'] : false;

			if (isset($viewParams['ad']))
			{
				$viewParams['ad']['size']     = $this->_getHelperGeneral()->getAdWidthHeight($package);
				$viewParams['ad']['keywords'] = implode(', ', explode("\n", $items));
			}

			$allowedExtensions = array();

			foreach ($this->_getOptions()->siropu_ads_manager_allowed_img_extensions as $key => $val)
			{
				if ($val)
				{
					$allowedExtensions[] = strtoupper($key);
				}
			}

			if ($this->_getOptions()->siropu_ads_manager_flash_allowed)
			{
				$allowedExtensions[] = 'SWF';
			}

			$viewParams = array_merge($viewParams, array(
				'packageAdCount' => $packageAdCount,
				'slotsInUse'     => $slotsInUse,
				'package'        => $package,
				'type'           => $type,
				'size'           => $this->_getHelperGeneral()->getAdWidthHeight($package, true),
				'bannerPath'     => $this->_getHelperGeneral()->getBannerPath(),
				'typeList'       => $this->_getHelperGeneral()->getAdTypes(),
				'costPerList'    => $this->_getHelperGeneral()->getCostPerList(),
				'discountList'   => $this->_getHelperGeneral()->prepareGroupListForDisplay($package['discount']),
				'deviceList'     => $this->_getHelperDevice()->getDeviceList(),
				'step'           => $this->_getHelperGeneral()->getSpinboxStep($package['cost_per']),
				'ttlMaxLength'   => $this->_getOptions()->siropu_ads_manager_ad_title_max_length,
				'dscMaxLength'   => $this->_getOptions()->siropu_ads_manager_ad_description_max_length,
				'kwdMaxWords'    => $this->_getOptions()->siropu_ads_manager_keyword_max_words,
				'autoValidator'  => ($type != 'banner') ? 1 : 0,
				'geoIPCriteria'  => XenForo_Helper_Criteria::unserializeCriteria($geoIPCriteria),
				'deviceCriteria' => XenForo_Helper_Criteria::unserializeCriteria($deviceCriteria),
				'countryList'    => $this->getHelper('Siropu_AdsManager_Helper_GeoIP')->getCountryList(),
				'allowedExt'     => implode(', ', $allowedExtensions)
			));

			return $this->responseView('', 'siropu_ads_manager_ad_edit', $viewParams);
		}
		return $this->responseError(new XenForo_Phrase('siropu_ads_manager_user_no_package_selected'));
	}
	public function actionInvoicesPromoCode()
	{
		$data = $this->_input->filter(array(
			'id'         => XenForo_Input::UINT,
			'promo_code' => XenForo_Input::STRING
		));

		if (!$transaction = $this->_getTransactionsModel()->getTransactionJoinAdsJoinPackagesById($data['id']))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_transaction_not_found'));
		}

		if ($transaction['user_id'] != $this->_getUserID())
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$promoCode = $data['promo_code'];

		if ($this->isConfirmedPost())
		{
			if ($promoCode && ($promoCodeInfo = $this->_getPromoCodesModel()->getPromoCodeByCode($promoCode)) && $promoCodeInfo['enabled'])
			{
				if ($transaction['promo_code'])
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_promo_code_already_applied'));
				}

				$userCriteria  = XenForo_Helper_Criteria::unserializeCriteria($promoCodeInfo['user_criteria']);
				$costAmount    = $transaction['cost_amount'];
				$newAmount     = $this->_getHelperGeneral()->applyPromoCode($transaction, $promoCodeInfo);
				$promoCodeVal  = $promoCodeInfo['value'];
				$promoCodeType = $promoCodeInfo['type'];

				if (!empty($userCriteria) && !XenForo_Helper_Criteria::userMatchesCriteria($userCriteria)
					|| (($packages = array_filter(explode(',', $promoCodeInfo['packages'])))
						&& !in_array($transaction['package_id'], $packages))
					|| (($minValue = $promoCodeInfo['min_transaction_value']) && $costAmount < $minValue)
					|| ($newAmount == 0
						&& (($promoCodeType == 'percent' && $promoCodeVal != 100)
							|| ($promoCodeType == 'amount' && $promoCodeVal != $costAmount)))
					|| ($promoCodeType == 'amount' && $promoCodeInfo['value'] > $costAmount))
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_promo_code_not_applicable',
						array('promoCode' => $promoCode)));
				}

				$usageCount = $promoCodeInfo['usage_count'] + 1;
				$dateExpire = $promoCodeInfo['date_expire'];
				$totalLimit = $promoCodeInfo['usage_limit_total'];
				$userLimit  = $promoCodeInfo['usage_limit_user'];

				if ($totalLimit && $usageCount > $totalLimit || $dateExpire && $dateExpire < time())
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_promo_code_has_expired'));
				}

				if ($userLimit && $this->_getUserModel()->getUserPromoCodeUsageCount($this->_getUserID(), $promoCode) >= $userLimit)
				{
					return $this->responseError(new XenForo_Phrase('siropu_ads_manager_promo_code_user_limit_reached',
						array('limit' => $userLimit)));
				}

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Transactions');
				$dw->setExistingData($data['id']);
				$dw->bulkSet(array(
					'cost_amount' => $newAmount,
					'promo_code'  => $promoCode
				));
				$dw->save();

				if ($newAmount == 0)
				{
					$this->_getTransactionsModel()->processTransaction($transaction, 'Completed', new XenForo_Phrase('siropu_ads_manager_promo_code'), false);

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($transaction['ad_id']);
					$dw->set('pending_transaction', 0);
					$dw->save();
				}

				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_PromoCodes');
				$dw->setExistingData($promoCodeInfo['code_id']);
				$dw->set('usage_count', $usageCount);
				$dw->save();

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('advertising/invoices')
				);
			}

			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_promo_code_not_valid'));
		}

		$viewParams = array(
			'transaction' => $transaction
		);

		return $this->responseView('', 'siropu_ads_manager_apply_promo_code', $viewParams);
	}
	public function actionInvoicesSubscribe()
	{
		if (!$transaction = $this->_getTransactionsModel()->getTransactionJoinAdsJoinPackagesById($this->_getId()))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_transaction_not_found'));
		}

		if ($transaction['user_id'] != $this->_getUserID())
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$viewParams = array(
			'transaction' => $transaction,
			'costPerList' => $this->_getHelperGeneral()->getCostPerList(),
			't3'          => substr($transaction['cost_per'], 0, 1)
		);

		return $this->responseView('', 'siropu_ads_manager_subscribe', $viewParams);
	}
	public function actionInvoicesDownload()
	{
		if (!$transaction = $this->_getTransactionsModel()->getTransactionJoinAdsJoinPackagesById($this->_getId()))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_transaction_not_found'));
		}

		if ($transaction['user_id'] != $this->_getUserID())
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$file = XenForo_Helper_File::getExternalDataPath() . "/Siropu/invoices/{$transaction['transaction_id']}/{$transaction['download']}";

		if (!$transaction['download'] || ($transaction['download'] && !file_exists($file)))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_invoice_download_error'));
		}

		$this->_routeMatch->setResponseType('raw');
		return $this->responseView('Siropu_AdsManager_ViewPublic_Download', '', array('invoice' => $transaction));
	}
	public function actionTerms()
	{
		return $this->responseView('', 'siropu_ads_manager_terms');
	}
	public function actionAdvertisers()
	{
		$ads = $this->_getAdsModel()->getActiveAdvertisersAds();

		foreach ($ads as $key => $val)
		{
			if ($val['type'] == 'banner')
			{
				$bannerType = array();

				if ($val['banner'] || $val['banner_url'])
				{
					$bannerType[] = 1;
				}
				if ($val['code'])
				{
					$bannerType[] = 2;
				}

				shuffle($bannerType);
				$ads[$key]['banner_type'] = $bannerType[0];

				if ($extraBanners = @unserialize($val['banner_extra']))
				{
					array_unshift($extraBanners, $val['banner']);
					shuffle($extraBanners);
					$ads[$key]['banner'] = $extraBanners[0];
				}

				if ($this->_getHelperGeneral()->isSwf($val['banner']))
				{
					$ads[$key]['flash'] = true;
				}
			}
		}

		$viewParams = array(
			'ads'        => $this->_getHelperGeneral()->groupAdsByType($ads),
			'bannerPath' => Siropu_AdsManager_Helper_General::getBannerPath()
		);

		return $this->responseView('', 'siropu_ads_manager_advertisers', $viewParams);
	}
	protected function _getUserAd()
	{
		if ($ad = $this->_getUserModel()->getUserAdById($this->_getID(), $this->_getUserID()))
		{
			return $ad;
		}
	}
	protected function _toggleSticky($status, $theadId)
	{
		$this->getModelFromCache('Siropu_AdsManager_Model_Threads')->toggleStickyThreadById($status, $theadId);
	}
	protected function _toggleFeatured($status, $resourceId)
	{
		$resource = $this->_getResourceModel()->getResourceById($resourceId);

		if ($status == 'Active')
		{
			$this->_getResourceModel()->featureResource($resource);
		}
		else
		{
			$this->_getResourceModel()->unfeatureResource($resource);
		}
	}
	protected function _getPackagesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Packages');
	}
	protected function _getUserModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_User');
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getForumsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Forums');
	}
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getPromoCodesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PromoCodes');
	}
	protected function _getStatsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Stats');
	}
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getHelperDevice()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_Device');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getVisitor()
	{
		return XenForo_Visitor::getInstance();
	}
	protected function _getID()
	{
		return $this->_input->filterSingle('id', XenForo_Input::UINT);
	}
	protected function _getUserID()
	{
		return $this->_getVisitor()->user_id;
	}
	protected function _sendAdminEmailNotification($data, $emailTemplate = 'siropu_ads_manager_admin_notification')
	{
		if ($this->_getOptions()->siropu_ads_manager_admin_email_notifications && ($adminList = $this->_getAdminList()))
		{
			$typeList = $this->_getHelperGeneral()->getAdTypes();

			$mailParams = array(
				'name'     => $data['name'],
				'username' => $data['username'],
				'type'     => $typeList[$data['type']],
				'changes'  => $data['changes'],
				'url'      => XenForo_Link::buildAdminLink('canonical:ads/details', array('ad_id' => $data['id']))
			);

			foreach ($adminList as $admin)
			{
				$adminPermission = XenForo_Model::create('XenForo_Model_Admin')->getAdminPermissionCacheForUser($admin['user_id']);

				if (!empty($adminPermission['siropu_ads_manager']))
				{
					$mail = XenForo_Mail::create($emailTemplate, $mailParams, $admin['language_id']);
					$mail->send($admin['email'], $admin['username']);
				}
			}
		}
	}
	protected function _sendAdminAlertNotification($data, $action = 'ad_submitted')
	{
		if ($this->_getOptions()->siropu_ads_manager_admin_alert_notifications && ($adminList = $this->_getAdminList()))
		{
			foreach ($adminList as $admin)
			{
				$adminPermission = XenForo_Model::create('XenForo_Model_Admin')->getAdminPermissionCacheForUser($admin['user_id']);

				if (!empty($adminPermission['siropu_ads_manager']))
				{
					XenForo_Model_Alert::alert(
						$admin['user_id'],
						$data['user_id'],
						$data['username'],
						'siropu_ads_manager',
						$data['ad_id'],
						$action
					);
				}
			}
		}
	}
	protected function _getAdminList()
	{
		return $this->getModelFromCache('XenForo_Model_User')->getUsers(array('is_admin' => 1));
	}
}

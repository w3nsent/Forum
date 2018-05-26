<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerAdmin_Ads extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('siropu_ads_manager');
	}
	public function actionIndex($status = '')
	{
		$search = $this->_input->filterSingle('search', XenForo_Input::ARRAY_SIMPLE);
		$order  = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);

		if ($order)
		{
			XenForo_Helper_Cookie::setCookie('adsOrder', serialize($order), 86400 * 365);
		}
		else if ($orderCookie = XenForo_Helper_Cookie::getCookie('adsOrder'))
		{
			$order = unserialize($orderCookie);
		}

		$ads      = $this->_getAdsModel()->getAllAds($status, $search, $order);
		$packages = $this->_getPackagesModel()->getAllPackages();

		$viewParams = array(
			'typeList'    => $this->_getHelperGeneral()->getAdTypes(),
			'statusList'  => $this->_getHelperGeneral()->getStatusList(),
			'packageList' => $packages,
			'groupedAds'  => $this->_getHelperGeneral()->groupAdsByPackage($ads, $packages),
			'adCount'     => count($ads),
			'status'      => $status,
			'search'      => $search,
			'order'       => $order,
			'debugMode'   => XenForo_Application::debugMode()
		);

		return $this->responseView('', 'siropu_ads_manager_ad_list', $viewParams);
	}
	public function actionActive()
	{
		return $this->actionIndex('Active');
	}
	public function actionInactive()
	{
		return $this->actionIndex('Inactive');
	}
	public function actionPending()
	{
		return $this->actionIndex('Pending');
	}
	public function actionApproved()
	{
		return $this->actionIndex('Approved');
	}
	public function actionQueued()
	{
		return $this->actionIndex('Queued');
	}
	public function actionPaused()
	{
		return $this->actionIndex('Paused');
	}
	public function actionRejected()
	{
		return $this->actionIndex('Rejected');
	}
	public function actionAdd()
	{
		$viewParams = array();

		if (($package_id = $this->_input->filterSingle('package_id', XenForo_Input::UINT))
			&& ($package = $this->_getPackagesModel()->getPackageById($package_id)))
		{
			$viewParams = array(
				'package_id'   => $package_id,
				'package'      => $package,
				'countViews'   => $package['count_ad_views'],
				'countClicks'  => $package['count_ad_clicks'],
				'nofollow'     => $package['nofollow'],
				'targetBlank'  => $package['target_blank'],
				'keywordLimit' => $package['keyword_limit']
			);
		}

		return $this->_getAdAddEditResponse($viewParams);
	}
	public function actionEdit()
	{
		$ad = $this->_getAdOrError();

		if ($this->_getHelperGeneral()->isSwf($ad['banner']))
		{
			$ad['flash'] = true;
		}
		if ($package = $this->_getPackagesModel()->getPackageById($ad['package_id']))
		{
			$package['style'] = @unserialize($package['style']);
			$ad['size'] = $this->_getHelperGeneral()->getAdWidthHeight($package);
		}

		$ad['banner_extra'] = $this->_getHelperGeneral()->getExtraBannersList($ad['banner_extra']);

		return $this->_getAdAddEditResponse(array('ad' => $ad));
	}
	public function actionDetails()
	{
		$ad = $this->_getAdsModel()->getAdJoinPackageById($this->_getAdID());

		if ($this->_getHelperGeneral()->isSwf($ad['banner']))
		{
			$ad['flash'] = true;
			$ad['style'] = @unserialize($ad['style']);
			$ad['size']  = $this->_getHelperGeneral()->getAdWidthHeight($ad);
		}

		$ad['banner_extra'] = $this->_getHelperGeneral()->getExtraBannersList($ad['banner_extra']);

		$viewParams = array(
			'ad'          => $ad,
			'cost'        => $this->_getHelperGeneral()->calculateAdCost($ad),
			'bannerPath'  => $this->_getHelperGeneral()->getBannerPath(),
			'typeList'    => $this->_getHelperGeneral()->getAdTypes(),
			'costPerList' => $this->_getHelperGeneral()->getCostPerList(),
			'statusList'  => $this->_getHelperGeneral()->getStatusList()
		);

		$positionList = $this->_getHelperGeneral()->getPositionSelectListByAdType($this->_getPositionsModel()->getAllPositions());

		foreach (explode("\n", $ad['positions']) as $position)
		{
			$viewParams['positionList'][] = $position ? @$positionList[$position] : 'N/A';
		}

		if ($itemsArray = array_filter(explode("\n", $ad['items'])))
		{
			switch ($ad['type'])
			{
				case 'keyword':
					foreach ($itemsArray as $keyword)
					{
						$viewParams['keywordList'][] = $keyword;
					}
					break;
				case 'sticky':
					$viewParams['sticky'] = $this->getModelFromCache('XenForo_Model_Thread')->getThreadById($ad['items']);
					break;
				case 'featured':
					$viewParams['resource'] = $this->_getResourceModel()->getResourceById($ad['items']);
					break;
			}
		}

		return $this->responseView('', 'siropu_ads_manager_ad_details', $viewParams);
	}
	public function actionClone()
	{
		$ad = $this->_getAdOrError();

		if ($this->isConfirmedPost())
		{
			unset($ad['ad_id']);

			$data = $this->_input->filter(array(
				'name'        => XenForo_Input::STRING,
				'code'        => XenForo_Input::STRING,
				'url'         => XenForo_Input::STRING,
				'title'       => XenForo_Input::STRING,
				'description' => XenForo_Input::STRING,
				'items'       => XenForo_Input::STRING,
				'status'      => XenForo_Input::STRING
			));

			if ($data['name'])
			{
				$ad['name'] = $data['name'];
			}
			else
			{
				$ad['name'] = new XenForo_Phrase('siropu_ads_manager_clone_of', array('name' => $ad['name']));
			}

			if ($data['code'])
			{
				$ad['code'] = $data['code'];
			}

			if ($data['url'])
			{
				$ad['url'] = $data['url'];
			}

			if ($data['title'])
			{
				$ad['title'] = $data['title'];
			}

			if ($data['description'])
			{
				$ad['description'] = $data['description'];
			}

			if ($data['items'])
			{
				$ad['items'] = $data['items'];
			}

			if (($upload = XenForo_Upload::getUploadedFile('banner'))
				&& ($banner = $this->_getHelperGeneral()->uploadBanner($upload, 1, array(), 'acp')))
			{
				$ad['banner'] = $banner;
			}
			else
			{
				$ad['banner'] = '';
			}

			$extraBanners = array();

			if ($banners = XenForo_Upload::getUploadedFiles('banner_extra'))
			{
				foreach ($banners as $file)
				{
					if ($banner = $this->_getHelperGeneral()->uploadBanner($file, 1, array(), 'acp'))
					{
						array_push($extraBanners, $banner);
					}
				}
			}

			$ad['banner_extra'] = @serialize($extraBanners);
			$ad['view_count']   = 0;
			$ad['click_count']  = 0;
			$ad['ctr']          = 0;

			if ($data['status'])
			{
				$ad['status'] = $data['status'];
			}

			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
			$dw->bulkSet($ad);
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('ads') . $this->getLastHash($dw->get('ad_id'))
			);
		}

		return $this->responseView('', 'siropu_ads_manager_ad_clone', array('ad' => $ad));
	}
	public function actionStats()
	{
		$input = $this->_input->filter(array(
			'type'  => XenForo_Input::STRING,
			'ad_id' => XenForo_Input::UINT,
			'page'  => XenForo_Input::UINT
		));

		$adId    = $input['ad_id'];
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
			'ad'           => $this->_getAdOrError(),
			'type'         => $input['type'],
			'positionList' => $this->_getHelperGeneral()->getPositionSelectList($this->_getPositionsModel()->getAllPositions()),
			'page'         => $input['page'],
			'perPage'      => $perPage,
			'pageParams'   => array('ad_id' => $adId, 'type' => $input['type']),
			'datePresets'  => XenForo_Helper_Date::getDatePresets(),
			'conditions'   => $conditions
		);

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
			'page'    => $input['page'],
			'perPage' => $perPage
		);

		switch ($input['type'])
		{
			case 'daily':
				$viewParams['dailyStats'] = $this->_getStatsModel()->getDailyStats($adId, $conditions, $fetchOptions);
				$viewParams['total']      = $this->_getStatsModel()->getDailyStatsCount($adId, $conditions);
				$viewParams['positions']  = $this->_getStatsModel()->getDailyStatsPositions($adId);
				return $this->responseView('', 'siropu_ads_manager_ad_stats_daily', $viewParams);
				break;
			case 'clicks':
				$viewParams['clickStats'] = $this->_getHelperGeneral()->prepareClicksStatsTooltipInfo($this->_getStatsModel()->getClickStats($adId, $conditions, $fetchOptions));
				$viewParams['total']      = $this->_getStatsModel()->getClickStatsCount($adId, $conditions);
				$viewParams['positions']  = $this->_getStatsModel()->getClickStatsPositions($adId);
				return $this->responseView('', 'siropu_ads_manager_ad_stats_clicks', $viewParams);
				break;
			default:
				return $this->responseView('', 'siropu_ads_manager_ad_stats_general', $viewParams);
				break;
		}
	}
	public function actionStatsReset()
	{
		$input = $this->_input->filter(array(
			'type'        => XenForo_Input::STRING,
			'ad_id'       => XenForo_Input::UINT
		));

		$adId = $input['ad_id'];
		$ad   = $this->_getAdOrError();

		if ($this->isConfirmedPost())
		{
			switch ($input['type'])
			{
				case 'all':
					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($adId);
					$dw->set('view_count', 0);
					$dw->set('click_count', 0);
					$dw->set('ctr', 0);
					$dw->save();
					$this->_getStatsModel()->deleteDailyStatsByAdId($adId);
					$this->_getStatsModel()->deleteClicksStatsByAdId($adId);
					break;
				case 'daily':
					$this->_getStatsModel()->deleteDailyStatsByAdId($adId);
					break;
				case 'clicks':
					$this->_getStatsModel()->deleteClicksStatsByAdId($adId);
					break;
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('ads/stats', $ad)
			);
		}

		$viewParams = array(
			'ad'         => $ad,
			'type'       => $input['type'],
			'typePhrase' => array(
				'all'    => new XenForo_Phrase('siropu_ads_manager_ad_stats_all'),
				'daily'  => new XenForo_Phrase('siropu_ads_manager_ad_stats_daily'),
				'clicks' => new XenForo_Phrase('siropu_ads_manager_ad_stats_clicks'),
			)
		);

		return $this->responseView('', 'siropu_ads_manager_ad_stats_reset_confirm', $viewParams);
	}
	public function actionGetStats()
	{
		$ad   = $this->_getAdOrError();
		$adId = $ad['ad_id'];

		$conditions = $this->_input->filter(array(
			'start_date' => XenForo_Input::DATE_TIME,
			'end_date'   => XenForo_Input::DATE_TIME,
			'preset'     => XenForo_Input::DATE_TIME,
			'position'   => XenForo_Input::STRING,
			'group_by'   => XenForo_Input::STRING,
			'chart'      => XenForo_Input::UINT
		));

		$fetchOptions = array(
			'page'    => 0,
			'perPage' => 25
		);

		$viewParams = array(
			'ad'           => $ad,
			'dailyStats'   => $this->_getStatsModel()->getDailyStats($adId, $conditions, $fetchOptions),
			'clickStats'   => $this->_getHelperGeneral()->prepareClicksStatsTooltipInfo($this->_getStatsModel()->getClickStats($adId, $conditions, $fetchOptions)),
			'positionList' => $this->_getHelperGeneral()->getPositionSelectList($this->_getPositionsModel()->getAllPositions())
		);

		return $this->responseView('Siropu_AdsManager_ViewAdmin_Ajax', 'siropu_ads_manager_ad_stats_alt', $viewParams);
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'type'                => XenForo_Input::STRING,
			'name'                => XenForo_Input::STRING,
			'package_id'          => XenForo_Input::UINT,
			'positions'           => XenForo_Input::STRING,
			'item_id'             => XenForo_Input::STRING,
			'code'                => XenForo_Input::STRING,
			'backup'              => XenForo_Input::STRING,
			'url'                 => XenForo_Input::STRING,
			'banner'              => XenForo_Input::STRING,
			'banner_url'          => XenForo_Input::STRING,
			'title'               => XenForo_Input::STRING,
			'description'         => XenForo_Input::STRING,
			'items'               => XenForo_Input::STRING,
			'date_start'          => XenForo_Input::DATE_TIME,
			'date_end'            => XenForo_Input::DATE_TIME,
			'count_views'         => XenForo_Input::UINT,
			'view_limit'          => XenForo_Input::UINT,
			'count_clicks'        => XenForo_Input::UINT,
			'click_limit'         => XenForo_Input::UINT,
			'daily_stats'         => XenForo_Input::UINT,
			'click_stats'         => XenForo_Input::UINT,
			'ga_stats'            => XenForo_Input::UINT,
			'nofollow'            => XenForo_Input::UINT,
			'target_blank'        => XenForo_Input::UINT,
			'hide_from_robots'    => XenForo_Input::UINT,
			'ad_order'            => XenForo_Input::UINT,
			'priority'            => XenForo_Input::UINT,
			'display_after'       => XenForo_Input::UINT,
			'hide_after'          => XenForo_Input::UINT,
			'inherit_settings'    => XenForo_Input::UINT,
			'count_exclude'       => XenForo_Input::UINT,
			'keyword_limit'       => XenForo_Input::UINT,
			'pending_transaction' => XenForo_Input::UINT,
			'position_criteria'   => XenForo_Input::ARRAY_SIMPLE,
			'page_criteria'       => XenForo_Input::ARRAY_SIMPLE,
			'user_criteria'       => XenForo_Input::ARRAY_SIMPLE,
			'device_criteria'     => XenForo_Input::ARRAY_SIMPLE,
			'geoip_criteria'      => XenForo_Input::ARRAY_SIMPLE,
			'notes'               => XenForo_Input::STRING,
			'status'              => XenForo_Input::STRING
		));

		if ($start = $dwData['date_start'])
		{
			$hour   = $this->_input->filterSingle('date_start_hour', XenForo_Input::STRING);
			$minute = $this->_input->filterSingle('date_start_minute', XenForo_Input::STRING);

			$dwData['date_start'] = $start + (strtotime("+ {$hour} hour {$minute} minutes") - time());
		}

		if ($end = $dwData['date_end'])
		{
			$hour   = $this->_input->filterSingle('date_end_hour', XenForo_Input::STRING);
			$minute = $this->_input->filterSingle('date_end_minute', XenForo_Input::STRING);

			$dwData['date_end'] = $end + (strtotime("+ {$hour} hour {$minute} minutes") - time());
		}

		if ($positions = $this->_input->filterSingle('positions', XenForo_Input::ARRAY_SIMPLE))
		{
			$dwData['positions'] = implode("\n", $positions);
		}

		if ($error = $this->_getHelperGeneral()->validateAdInput($dwData, null, 'acp'))
		{
			return $this->responseError($error);
		}

		$visitor = XenForo_Visitor::getInstance();
		$userId  = $visitor->user_id;
		$adID    = $this->_getAdID();
		$adData  = $adID ? $this->_getAdsModel()->getAdById($adID) : false;

		if (($upload = XenForo_Upload::getUploadedFile('banner'))
			&& ($banner = $this->_getHelperGeneral()->uploadBanner($upload, $userId, array(), 'acp')))
		{
			if ($dwData['banner'])
			{
				$this->_getHelperGeneral()->deleteBanner($dwData['banner']);
			}

			$dwData['banner'] = $banner;
		}

		if ($banners = XenForo_Upload::getUploadedFiles('banner_extra'))
		{
			$extraBanners = array();

			if ($adData && ($currentBanners = @unserialize($adData['banner_extra'])))
			{
				$extraBanners = $currentBanners;
			}

			foreach ($banners as $file)
			{
				if ($banner = $this->_getHelperGeneral()->uploadBanner($file, $userId, array(), 'acp'))
				{
					array_push($extraBanners, $banner);
				}
			}

			$dwData['banner_extra'] = @serialize($extraBanners);
		}

		if ($dwData['daily_stats'] || $dwData['click_stats'] || $dwData['ga_stats'])
		{
			$dwData['count_views']  = 1;
			$dwData['count_clicks'] = 1;
		}

		if ($adID && $this->_input->filterSingle('current_status', XenForo_Input::STRING) == 'Active'
			&& $dwData['status'] != 'Active')
		{
			$dwData['date_last_active'] = time();
		}

		if ($dwData['type'] == 'sticky')
		{
			$dwData['items'] = intval($dwData['items']);
		}

		if ($dwData['status'] == 'Active')
		{
			$dwData['date_active'] = time();
			$dwData['status_old']  = '';
		}

		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		if ($adID)
		{
			$dw->setExistingData($adID);

			if($dwData['status'] != 'Active')
			{
				$dwData['date_active'] = 0;
			}
		}
		else
		{
			$dwData['user_id']  = $visitor->user_id;
			$dwData['username'] = $visitor->username;
		}
		$dw->bulkSet($this->_getPackagesModel()->getInheritData($dwData));
		$dw->save();

		if ($dwData['type'] == 'sticky' && $dwData['items'])
		{
			$this->_toggleSticky($dwData['status'], $dwData['items']);

			if ($adData && $adData['items'] != $dwData['items'])
			{
				$this->_toggleSticky('Inactive', $adData['items']);
			}
		}
		if ($dwData['type'] == 'featured' && $dwData['items'])
		{
			$this->_toggleFeatured($dwData['status'], $dwData['items']);

			if ($adData && $adData['items'] != $dwData['items'])
			{
				$this->_toggleFeatured('Inactive', $adData['items']);
			}
		}

		$this->_getHelperGeneral()->refreshAdsForCache();

		return $this->_getAdResponseRedirect($dw->get('ad_id'));
	}
	public function actionApprove()
	{
		$this->_getAdsModel()->approveAd($this->_getAdID());

		return $this->responseRedirect(
	          XenForo_ControllerResponse_Redirect::SUCCESS,
	          XenForo_Link::buildAdminLink('ads-manager')
	     );
	}
	public function actionReject()
	{
		if ($this->isConfirmedPost())
		{
			if ($rejectReason = $this->_input->filterSingle('reject_reason', XenForo_Input::STRING))
			{
				$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
				$dw->setExistingData($this->_getAdID());
				$dw->set('reject_reason', $rejectReason);
				$dw->set('status', 'Rejected');
				$dw->save();

				$this->_getHelperGeneral()->sendEmailNotification('siropu_ads_manager_ad_rejected', array(
					'name'   => $this->_input->filterSingle('ad_name', XenForo_Input::STRING),
					'reason' => $rejectReason), $this->_input->filterSingle('user_id', XenForo_Input::UINT));

				return $this->_getAdResponseRedirect();
			}
			else
			{
				return $this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_reject_reason_is_required'));
			}
		}
		else
		{
			$viewParams['ad'] = $this->_getAdOrError();
			return $this->responseView('', 'siropu_ads_manager_ad_reject_reason', $viewParams);
		}
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			$ad = $this->_getAdOrError();

			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
			$dw->setExistingData($this->_getAdID());
			$dw->delete();

			if ($ad['banner'])
			{
				$this->_getHelperGeneral()->deleteBanner($ad['banner']);

				if ($banners = @unserialize($ad['banner_extra']))
				{
					foreach ($banners as $banner)
					{
						$this->_getHelperGeneral()->deleteBanner($banner);
					}
				}
			}

			$this->_getStatsModel()->deleteDailyStatsByAdId($ad['ad_id']);
			$this->_getStatsModel()->deleteClicksStatsByAdId($ad['ad_id']);

			if ($ad['type'] == 'sticky' && $ad['items'])
			{
				$this->_toggleSticky('Inactive', $ad['items']);
			}

			if ($ad['type'] == 'featured' && $ad['items'])
			{
				$this->_toggleFeatured('Inactive', $ad['items']);
			}

			$this->_getHelperGeneral()->refreshAdsForCache();

			if ($this->_input->filterSingle('delete_transactions', XenForo_Input::UINT))
			{
				$this->_getTransactionsModel()->deleteTransactionsByAdId($ad['ad_id']);
			}

			return $this->_getAdResponseRedirect();
		}
		else
		{
			$viewParams['ad'] = $this->_getAdOrError();

			if ($transactionCount = $this->_getTransactionsModel()->getTransactionCountByAdId($this->_getAdID()))
			{
				$viewParams['transactionCount'] = $transactionCount;
			}

			return $this->responseView('', 'siropu_ads_manager_ad_delete_confirm', $viewParams);
		}
	}
	public function actionToggle()
	{
		$idExists = $this->_input->filterSingle('exists', array(XenForo_Input::UINT, 'array' => true));
		$ids      = $this->_input->filterSingle('id', array(XenForo_Input::UINT, 'array' => true));

		foreach ($this->_getAdsModel()->getAllAds() as $id => $data)
		{
			if (isset($idExists[$id]))
			{
				if ($data['status'] != 'Active' && isset($ids[$id]))
				{
					$data['status'] = 'Active';

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($id);
					$dw->bulkSet(array(
						'date_active' => time(),
						'status_old'  => '',
						'status'      => 'Active'
					));
					$dw->save();
				}

				if ($data['status'] == 'Active' && !isset($ids[$id]))
				{
					$data['status'] = 'Inactive';

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($id);
					$dw->bulkSet(array(
						'date_active'      => 0,
						'date_last_active' => time(),
						'status'           => 'Inactive'
					));
					$dw->save();
				}

				if ($data['type'] == 'sticky' && $data['items'])
				{
					$this->_toggleSticky($data['status'], $data['items']);
				}

				if ($data['type'] == 'featured' && $data['items'])
				{
					$this->_toggleFeatured($data['status'], $data['items']);
				}
			}
		}

		$this->_getHelperGeneral()->refreshAdsForCache();

		return $this->_getAdResponseRedirect();
	}
	public function actionDeleteBanner()
	{
		$ad = $this->_getAdOrError();

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

		return $this->responseView('Siropu_AdsManager_ViewAdmin_Ajax', '', array('success' => true));
	}
	public function actionEmbed()
	{
		$viewParams = array(
			'embedUrl' => XenForo_Link::buildPublicLink('canonical:sam-embed', '', array('aid' => $this->_getAdID())),
			'sizeList' => $this->_getHelperGeneral()->getAdSizes()
		);

		return $this->responseView('', 'siropu_ads_manager_embed', $viewParams);
	}
	protected function _getAdAddEditResponse($viewParams)
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);

		if (!$type)
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_type_required'));
		}

		if ($type == 'featured' && !XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('XenResource'))
		{
			return $this->responseError(new XenForo_Phrase('siropu_ads_manager_resource_manager_required'));
		}

		$positions = $positionCriteria = $pageCriteria = $userCriteria = $deviceCriteria = $geoIPCriteria = array();
		$countViews = $countClicks = $hideFromRobots = 0;

		if (isset($viewParams['ad']))
		{
			$positions        = explode("\n", $viewParams['ad']['positions']);
			$countViews       = $viewParams['ad']['count_views'];
			$countClicks      = $viewParams['ad']['count_clicks'];
			$hideFromRobots   = $viewParams['ad']['hide_from_robots'];
			$positionCriteria = $viewParams['ad']['position_criteria'];
			$pageCriteria     = $viewParams['ad']['page_criteria'];
			$userCriteria     = $viewParams['ad']['user_criteria'];
			$deviceCriteria   = $viewParams['ad']['device_criteria'];
			$geoIPCriteria    = $viewParams['ad']['geoip_criteria'];
		}
		else if (isset($viewParams['package']))
		{
			$positions        = explode("\n", $viewParams['package']['positions']);
			$countViews       = $viewParams['package']['count_ad_views'];
			$countClicks      = $viewParams['package']['count_ad_clicks'];
			$hideFromRobots   = $viewParams['package']['hide_from_robots'];
			$positionCriteria = $viewParams['package']['position_criteria'];
			$pageCriteria     = $viewParams['package']['page_criteria'];
			$userCriteria     = $viewParams['package']['user_criteria'];
			$deviceCriteria   = $viewParams['package']['device_criteria'];
			$geoIPCriteria    = $viewParams['package']['geoip_criteria'];
		}

		$packageList     = $this->_getPackagesModel()->getPackagesByType($type);
		$positionList    = $this->_getPositionsModel()->getAllPositions();
		$positionCatList = $this->_getPositionsCateoriesModel()->getAllCategories();

		$viewParams = array_merge($viewParams, array(
			'type'             => $type,
			'positions'        => $positions,
			'bannerPath'       => $this->_getHelperGeneral()->getBannerPath(),
			'packageList'      => $this->_getHelperGeneral()->getPackageSelectList($packageList),
			'positionList'     => $this->_getHelperGeneral()->groupPositionsByCategory($positionList, $positionCatList, true),
			'hiddenPosCount'   => $this->_getHelperGeneral()->getHiddenPositionsCount($positionList),
			'typeList'         => $this->_getHelperGeneral()->getAdTypes(),
			'hourList'         => $this->_getHelperGeneral()->getHourMinuteList(23),
			'minuteList'       => $this->_getHelperGeneral()->getHourMinuteList(59),
			'countViews'       => $countViews,
			'countClicks'      => $countClicks,
			'hideFromRobots'   => $hideFromRobots,
			'positionCriteria' => XenForo_Helper_Criteria::unserializeCriteria($positionCriteria),
			'pageCriteria'     => XenForo_Helper_Criteria::prepareCriteriaForSelection($pageCriteria),
			'pageCriteriaData' => XenForo_Helper_Criteria::getDataForPageCriteriaSelection(),
			'userCriteria'     => XenForo_Helper_Criteria::prepareCriteriaForSelection($userCriteria),
			'userCriteriaData' => XenForo_Helper_Criteria::getDataForUserCriteriaSelection(),
			'deviceCriteria'   => XenForo_Helper_Criteria::unserializeCriteria($deviceCriteria),
			'geoIPCriteria'    => XenForo_Helper_Criteria::unserializeCriteria($geoIPCriteria),
			'deviceList'       => $this->_getHelperDevice()->getDeviceList(),
			'countryList'      => $this->_getHelperGeoIP()->getCountryList(),
			'autoValidator'    => ($type != 'banner') ? 1 : 0
		));

		if (!isset($viewParams['package']))
		{
			$viewParams['nofollow']    = 1;
			$viewParams['targetBlank'] = 1;
		}

		return $this->responseView('', 'siropu_ads_manager_ad_edit', $viewParams);
	}
	protected function _getAdOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getAdID();
		}

		if ($info = $this->_getAdsModel()->getAdById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_ads_manager_ad_not_found'), 404));
	}
	protected function _changeAdStatus($adId, $status)
	{
		$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
		$dw->setExistingData($adId);
		$dw->set('status', $status);
		$dw->save();
	}
	protected function _getAdResponseRedirect($lastHash = '')
	{
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('ads') . $this->getLastHash($lastHash)
		);
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getPositionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _getPositionsCateoriesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_PositionsCategories');
	}
	protected function _getPackagesModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Packages');
	}
	protected function _getTransactionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Transactions');
	}
	protected function _getStatsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Stats');
	}
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}
	protected function _toggleSticky($status, $theadId)
	{
		$this->getModelFromCache('Siropu_AdsManager_Model_Threads')->toggleStickyThreadById($status, $theadId);
	}
	protected function _toggleFeatured($status, $resourceId)
	{
		if ($resource = $this->_getResourceModel()->getResourceById($resourceId))
		{
			if ($status == 'Active')
			{
				$this->_getResourceModel()->featureResource($resource);
			}
			else
			{
				$this->_getResourceModel()->unfeatureResource($resource);
			}
		}
	}
	protected function _getHelperGeneral()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_General');
	}
	protected function _getHelperDevice()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_Device');
	}
	protected function _getHelperGeoIP()
	{
		return $this->getHelper('Siropu_AdsManager_Helper_GeoIP');
	}
	protected function _getAdID()
	{
		return $this->_input->filterSingle('ad_id', XenForo_Input::UINT);
	}
}

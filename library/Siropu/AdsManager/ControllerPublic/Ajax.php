<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ControllerPublic_Ajax extends XenForo_ControllerPublic_Abstract
{
	public function actionAdAction()
	{
		if (XenForo_Application::getSession()->get('robotId') || !$this->_getHelperGeneral()->userHasPermission('view'))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('do_not_have_permission')));
		}

		$this->_assertPostOnly();

		$data = $this->_input->filter(array(
			'id'       => XenForo_Input::UINT,
			'action'   => XenForo_Input::STRING,
			'position' => XenForo_Input::STRING,
			'page_url' => XenForo_Input::STRING,
		));

		$fields = array(
			'view'  => 'view_count',
			'click' => 'click_count'
		);

		$ajaxResponse = array();

		if (isset($fields[$data['action']]) && ($ad = $this->_getAdsModel()->getAdById($data['id'])))
		{
			$field   = $fields[$data['action']];
			$count   = $ad[$field] + 1;
			$expired = (($data['action'] == 'view' && $ad['view_limit'] && $count >= $ad['view_limit'])
				|| ($data['action'] == 'click' && $ad['click_limit'] && $count >= $ad['click_limit'])) ? true : false;

			$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
			$dw->setExistingData($data['id']);
			$dw->set($field, $count);
			if ($expired)
			{
				$dw->set('date_active', 0);
				$dw->set('date_last_active', time());
				$dw->set('notice_sent', 0);
				$dw->set('status', 'Inactive');
			}
			if ($data['action'] == 'click' && ($view_count = $ad['view_count']))
			{
				$dw->set('ctr', $count / $view_count * 100);
			}
			$dw->save();

			if ($ad['daily_stats'])
			{
				$statsDate = $this->_getHelperGeneral()->getStatsDate();

				$this->_getStatsModel()->insertDailyStats(array(
					'id'          => sha1($statsDate . $data['position'] . $data['id']),
					'date'        => $statsDate,
					'ad_id'       => $data['id'],
					'position'    => $data['position'],
					'view_count'  => ($data['action'] == 'view') ? 1 : 0,
					'click_count' => ($data['action'] == 'click') ? 1 : 0,
				));
			}

			if ($data['action'] == 'click' && $ad['click_stats'])
			{
				$username = $this->_getVisitor()->username;
				$username = $username ? $username : 'Guest';

				$insertData = array(
					'date'             => time(),
					'ad_id'            => $data['id'],
					'page_url'         => urldecode($data['page_url']),
					'position'         => $data['position'],
					'visitor_username' => $username,
					'visitor_gender'   => '',
					'visitor_age'      => 0,
					'visitor_ip'       => 'N/A',
					'visitor_device'   => $this->getHelper('Siropu_AdsManager_Helper_Device')->getDeviceType(),
				);

				if ($gender = $this->_getVisitor()->gender)
				{
					$insertData['visitor_gender'] = $gender;
				}
				if ($year = $this->_getVisitor()->dob_year)
				{
					$insertData['visitor_age'] = date('Y', time()) - $year;
				}
				if (isset($_SERVER['REMOTE_ADDR']))
				{
					$insertData['visitor_ip'] = $_SERVER['REMOTE_ADDR'];
				}

				$this->_getStatsModel()->insertClickStats($insertData);
			}

			$this->_getHelperGeneral()->saveUserAdAction($data['id'], substr($data['action'], 0, 1));

			if ($ad['ga_stats'])
			{
				$positions = $this->_getPositionsModel()->getPositionsForHookMatch();
				$hook      = preg_replace('/[0-9]+$/', 'x', $data['position']);

				$ajaxResponse = array(
					'posName' => isset($positions[$hook]) ? $this->_getHelperGeneral()->getPositionName($data['position'], $positions[$hook]) : 'N/A',
					'adName'  => $ad['name']
				);
			}
		}

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', $ajaxResponse);
	}
	public function actionCountImpressions()
	{
		if (XenForo_Application::getSession()->get('robotId') || !$this->_getHelperGeneral()->userHasPermission('view'))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('do_not_have_permission')));
		}

		$ajaxResponse = array();

		if ($impressions = $this->_input->filterSingle('impressions', XenForo_Input::ARRAY_SIMPLE))
		{
			$adList = array();

			foreach ($impressions as $key => $val)
			{
				if (isset($val[1]))
				{
					$adList[$val[0]][$val[1]] = $val[0];
				}
				else
				{
					$adList[$val[0]] = $val[0];
				}
			}

			$statsDate = $this->_getHelperGeneral()->getStatsDate();

			if ($ads = $this->_getAdsModel()->getAdsByIds(array_keys($adList)))
			{
				foreach ($ads as $ad)
				{
					$adId = $ad['ad_id'];

					if (!$ad['count_views'])
					{
						continue;
					}

					$dw = XenForo_DataWriter::create('Siropu_AdsManager_DataWriter_Ads');
					$dw->setExistingData($adId);
					$dw->set('view_count', $ad['view_count'] + 1);
					if ($ad['view_limit'] && $ad['view_count'] + 1 >= $ad['view_limit'])
					{
						$dw->set('date_active', 0);
						$dw->set('date_last_active', time());
						$dw->set('notice_sent', 0);
						$dw->set('status', 'Inactive');
					}
					$dw->save();

					if ($ad['daily_stats'])
					{
						foreach ($adList[$adId] as $key => $val)
						{
							$this->_getStatsModel()->insertDailyStats(array(
								'id'          => sha1($statsDate . $key . $adId),
								'date'        => $statsDate,
								'ad_id'       => $adId,
								'position'    => $key,
								'view_count'  => 1,
								'click_count' => 0
							));
						}
					}

					if ($ad['ga_stats'])
					{
						$positions    = $this->_getPositionsModel()->getPositionsForHookMatch();
						$positionList = array();

						foreach ($adList[$adId] as $key => $val)
						{
							$hook               = preg_replace('/[0-9]+$/', 'x', $key);
							$name               = isset($positions[$hook]) ? $positions[$hook] : 'N/A';
							$positionList[$key] = $this->_getHelperGeneral()->getPositionName($key, $name);
						}

						$ajaxResponse['gaData'][$adId] = array(
							'name'      => $ad['name'],
							'positions' => $positionList
						);
					}
				}
			}
		}

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', $ajaxResponse);
	}
	public function actionGetUserForumThreadList()
	{
		$this->_assertPostOnly();

		$viewParams = array();

		if (($forumID = $this->_input->filterSingle('forum_id', XenForo_Input::UINT))
			&& ($userID = $this->_getVisitor()->user_id))
		{
			$threadsModel     = $this->getModelFromCache('XenForo_Model_Thread');
			$stickyList       = $this->_getForumsModel()->getForumStickyList($forumID);
			$stickyCount      = count($stickyList);
			$forumStickyLimit = $this->_getOptions()->siropu_ads_manager_max_stickies_per_forum;

			$userThreads = array();
			foreach ($stickyList as $row)
			{
				$userThreads[$row['user_id']][] = $row;
			}

			$forumPendingAds = 0;

			if ($pendingStickyIds = $this->_getAdsModel()->getPendingStickyAdsThreadIds())
			{
				$pendingStickyData = $threadsModel->getThreadsByIds($pendingStickyIds);
				$pendingForumList  = $this->_getHelperGeneral()->groupThreadsByForumId($pendingStickyData);
				$forumPendingAds   = isset($pendingForumList[$forumID]) ? count($pendingForumList[$forumID]) : 0;
			}

			if ($pausedAds = $this->_getAdsModel()->getAllAds('Paused', array('type' => 'sticky')))
			{
				foreach ($pausedAds as $row)
				{
					if (($thread = $threadsModel->getThreadById($row['items'])) && $thread['node_id'] == $forumID)
					{
						$stickyCount += 1;
					}
				}
			}

			$viewParams['threadList'] = new XenForo_Template_Public('siropu_ads_manager_user_thread_list', array(
				'resultArray'      => $this->_getUserModel()->getUserForumThreads($forumID, $userID),
				'stickyCount'      => $stickyCount,
				'forumStickyLimit' => $forumStickyLimit,
				'forumPendingAds'  => $forumPendingAds,
				'slotsAvailable'   => $forumStickyLimit - $stickyCount,
				'userStickyCount'  => isset($userThreads[$userID]) ? count($userThreads[$userID]) : 0,
				'userStickyLimit'  => $this->_getOptions()->siropu_ads_manager_max_stickies_per_user_per_forum,
				'currentStickies'  => $this->_getUserModel()->getUserStickyAds($userID)
			));
		}

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', $viewParams);
	}
	public function actionCheckKeywordUniqueness()
	{
		$this->_assertPostOnly();
		$viewParams = array();

		$postData = $this->_input->filter(array(
			'keywords' => XenForo_Input::STRING,
			'adId'     => XenForo_Input::UINT
		));

		if ($postData['keywords']
			&& ($notUnique = $this->_getHelperGeneral()->checkKeywordUniqueness($postData['keywords'], $postData['adId'])))
		{
			$viewParams['notUnique'] = $notUnique;
		}

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', $viewParams);
	}
	public function actionGetUserResourceList()
	{
		$this->_assertPostOnly();

		$viewParams = array();

		if (($catID = $this->_input->filterSingle('category_id', XenForo_Input::UINT))
			&& ($userID = $this->_getVisitor()->user_id))
		{
			$viewParams['resourceList'] = new XenForo_Template_Public('siropu_ads_manager_user_resource_list', array(
				'resultArray' => $this->_getResourceModel()->getResources(array('user_id' => $userID, 'resource_category_id' => $catID)),
				'currentResources' => $this->_getUserModel()->getUserFeaturedAds($userID)
			));
		}

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', $viewParams);
	}
	public function actionGetRobokassaSigValue()
	{
		$input = $this->_input->filter(array(
			'OutSum'       => XenForo_Input::STRING,
			'IncCurrLabel' => XenForo_Input::STRING,
			'Shp_item'     => XenForo_Input::STRING,
		));

		$currencies = $this->_getHelperGeneral()->getCurrencyList('ROBOKASSA');

		$viewParams = array(
			'sigValue'     => $this->_getHelperGeneral()->getRobokassaSignature($input),
			'IncCurrLabel' => @$currencies[$input['IncCurrLabel']]
		);

		return $this->responseView('Siropu_AdsManager_ViewPublic_Ajax', '', $viewParams);
	}
	protected function _getUserModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_User');
	}
	protected function _getAdsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Ads');
	}
	protected function _getPositionsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Positions');
	}
	protected function _getForumsModel()
	{
		return $this->getModelFromCache('Siropu_AdsManager_Model_Forums');
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
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getVisitor()
	{
		return XenForo_Visitor::getInstance();
	}
}

<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Listener
{
	private static $adsForDisplay = array();
	private static $threads       = array();
	private static $thread        = array();
	private static $forum         = array();
	private static $posts         = array();
	private static $itemCount     = 0;
	private static $firstUnread   = 0;
	private static $adsenseCount  = 0;
	private static $adCount       = 0;
	private static $firstMessage  = '';

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Template_Helper_Core::$helperCallbacks += array(
            	'sam_cost_amount'     => array('Siropu_AdsManager_Helper_Template', 'helperCostAmount'),
			'sam_cost_amount_alt' => array('Siropu_AdsManager_Helper_Template', 'helperCostAmountAlt'),
			'sam_cost_currency'   => array('Siropu_AdsManager_Helper_Template', 'helperCostCurrency'),
			'sam_position_name'   => array('Siropu_AdsManager_Helper_Template', 'helperPositionName'),
			'sam_ctr'             => array('Siropu_AdsManager_Helper_Template', 'helperCtr')
        );
	}
	public static function load_class_controller($class, &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Thread':
				$extend[] = 'Siropu_AdsManager_ControllerPublic_Thread';
				break;
			case 'XenForo_ControllerPublic_InlineMod_Thread':
				$extend[] = 'Siropu_AdsManager_ControllerPublic_InlineModThread';
				break;
		}
	}
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
    {
		if ($templateName == 'PAGE_CONTAINER')
        	{
            	$template->preloadTemplate('siropu_ads_manager_tab_title');
			$template->preloadTemplate('siropu_ads_manager_ad_type_code');
			$template->preloadTemplate('siropu_ads_manager_ad_type_banner');
			$template->preloadTemplate('siropu_ads_manager_ad_type_text');
			$template->preloadTemplate('siropu_ads_manager_ad_type_link');
			$template->preloadTemplate('siropu_ads_manager_ad_type_keyword');
			$template->preloadTemplate('siropu_ads_manager_support_us');
			$template->preloadTemplate('siropu_ads_manager_page_criteria_page_info');
			$template->preloadTemplate('siropu_ads_manager_footer_links');
			$template->preloadTemplate('siropu_ads_manager_admin_home');
        	}

		if ($templateName == 'forum_view' && ($allowedStickyForums = self::_getOptions()->siropu_ads_manager_sticky_forum_list['node_id']) && !empty($params['threads']))
		{
			$nodeId = !empty($params['forum']['node_id']) ? $params['forum']['node_id'] : 0;

			if (in_array($nodeId, $allowedStickyForums) && ($package = XenForo_Application::getSimpleCacheData('advertiseHereStickyPackage')))
			{
				if (($discountList = Siropu_AdsManager_Helper_General::prepareGroupListForDisplay($package['cost_list'])) && isset($discountList[$nodeId]))
				{
					$package['cost_amount'] = $discountList[$nodeId];
				}

				$params['samPackage']     = $package;
				$params['samCostPerList'] = Siropu_AdsManager_Helper_General::getCostPerList();
			}
		}

		$mediaTemplates = array(
			'xengallery_media_index',
			'xengallery_category_view',
			'xengallery_album_view'
		);

		if (in_array($templateName, $mediaTemplates) && !empty($params['media']))
		{
			$i = 1;

			foreach ($params['media'] as $key => $val)
			{
				$params['media'][$key]['position_on_page'] = $i++;
			}
		}

		$mediaAlbumTemplates = array(
			'xengallery_album_index',
			'xengallery_user_albums',
			'xengallery_album_shared',
		);

		if (in_array($templateName, $mediaAlbumTemplates) && !empty($params['albums']))
		{
			$i = 1;

			foreach ($params['albums'] as $key => $val)
			{
				$params['albums'][$key]['position_on_page'] = $i++;
			}
		}

		$resourceTemplates = array(
			'resource_index',
			'resource_category',
			'resource_author_view',
			'resource_featured'
		);

		if (in_array($templateName, $resourceTemplates) && !empty($params['resources']))
		{
			$i = 1;

			foreach ($params['resources'] as $key => $val)
			{
				$params['resources'][$key]['position_on_page'] = $i++;
			}
		}

		if ($templateName == 'member_view' && !empty($params['profilePosts']))
		{
			$i = 1;

			foreach ($params['profilePosts'] as $key => $val)
			{
				$params['profilePosts'][$key]['position_on_page'] = $i++;
			}
		}

		if ($templateName == 'thread_view' && !empty($params['thread']))
		{
			self::$forum        = $params['forum'];
			self::$thread       = $params['thread'];
			self::$posts        = $params['posts'];
			self::$firstMessage = $params['firstPost']['message'];
		}


    }
	public static function criteria_page($rule, array $data, array $params, array $containerData, &$returnValue)
	{
		switch ($rule)
		{
			case 'template_is_not':
				if (!in_array($params['contentTemplate'], array_map('trim', array_filter(explode(',', $data['name'])))))
				{
					$returnValue = true;
				}
				break;
			case 'nodes_not':
					if (!empty($data['display_outside'])
						&& empty($containerData['navigation'])
						&& empty($containerData['quickNavSelected']))
					{
						$returnValue = true;
						return false;
					}
					if (!isset($containerData['navigation']) || !is_array($containerData['navigation']))
					{
						return false;
					}
					if (empty($data['node_ids']))
					{
						return false;
					}

					if (empty($data['node_only']))
					{
						foreach ($containerData['navigation'] AS $i => $navItem)
						{
							if (isset($navItem['node_id']) && in_array($navItem['node_id'], $data['node_ids']))
							{
								break 2;
							}
						}
					}

					if (isset($containerData['quickNavSelected']))
					{
						$quickNavSelected = $containerData['quickNavSelected'];
					}
					else
					{
						$quickNavSelected = false;
						foreach ($containerData['navigation'] AS $i => $navItem)
						{
							if (isset($navItem['node_id']))
							{
								$quickNavSelected = 'node-' . $navItem['node_id'];
							}
						}
					}

					if ($quickNavSelected && in_array(preg_replace('/^.+-(\d+)$/', '$1', $quickNavSelected), $data['node_ids']))
					{
						break;
					}

					$returnValue = true;

				break;
		}
	}
	public static function criteria_user($rule, array $data, array $user, &$returnValue)
	{
		switch ($rule)
		{
			case 'dow':
				$datetime = new DateTime('', new DateTimeZone(($data['user_tz'] ? $user['timezone'] : $data['timezone'])));

				if (empty($data['days']))
				{
					$returnValue = true;
				}

				if (!empty($data['days']) && in_array(date('w', $datetime->format('U')), $data['days']))
				{
					$returnValue = true;
				}
				break;
		}
	}
	public static function navigation_tabs(array &$extraTabs, $selectedTabId)
	{
		$visitor   = XenForo_Visitor::getInstance();
		$options   = XenForo_Application::get('options');
		$userTab   = $options->siropu_ads_manager_user_navigation_tab;
		$guestMode = $options->siropu_ads_manager_guest_mode;

		if ($userTab['enabled']
			&& (Siropu_AdsManager_Helper_General::userHasPermission('create')
				|| ($guestMode['enabled']) && !$visitor->user_id))
		{
			$extraTabs['advertising'] = array(
				'href'          => XenForo_Link::buildPublicLink('advertising'),
				'title'         => new XenForo_Phrase('siropu_ads_manager_tab_title'),
				'selected'      => $selectedTabId == 'advertising',
				'position'      => $userTab['position'] ? $userTab['position'] : 'end',
				'linksTemplate' => 'siropu_ads_manager_tabs'
			);
		}
	}
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$templateParams = $template->getParams();

		if (Siropu_AdsManager_Helper_General::userHasPermission('view')
			&& $templateParams['controllerName'] != 'Siropu_AdsManager_ControllerPublic_Index')
		{
			if (!self::$itemCount)
			{
				if (!empty($templateParams['stickyThreads']))
				{
					self::$itemCount += count($templateParams['stickyThreads']);
				}
				if (!empty($templateParams['threads']))
				{
					self::$itemCount += count($templateParams['threads']);
				}
				if (!empty($templateParams['posts']))
				{
					self::$itemCount += count($templateParams['posts']);
				}
				if (!empty($templateParams['messages']))
				{
					self::$itemCount += count($templateParams['messages']);
				}
				if (!empty($templateParams['profilePosts']))
				{
					self::$itemCount += count($templateParams['profilePosts']);
				}
				if (!empty($templateParams['results']))
				{
					self::$itemCount += count($templateParams['results']);
				}
				if (!empty($templateParams['resources']))
				{
					self::$itemCount += count($templateParams['resources']);
				}
				if (!empty($templateParams['media']))
				{
					self::$itemCount += count($templateParams['media']);
				}
				if (!empty($templateParams['albums']))
				{
					self::$itemCount += count($templateParams['albums']);
				}
			}

			if (!self::$threads)
			{
				if (!empty($templateParams['threads']))
				{
					self::$threads += array_keys($templateParams['threads']);
				}
				if (!empty($templateParams['stickyThreads']))
				{
					foreach (array_reverse(array_keys($templateParams['stickyThreads'])) as $id)
					{
						array_unshift(self::$threads, $id);
					}
				}
			}

			if (!self::$thread && !empty($templateParams['thread']['thread_id']))
			{
				self::$thread = $templateParams['thread'];
			}

			if (!self::$posts && !empty($templateParams['posts']))
			{
				self::$posts = $templateParams['thread'];
			}

			if (self::$thread)
			{
				if (self::$thread['isNew'] && self::$posts)
				{
					foreach (self::$posts as $post)
					{
						if ($post['post_date'] > self::$thread['thread_read_date'])
						{
							self::$thread['first_unread_post_id'] = $post['position_on_page'];
							break;
						}
					}
				}
			}

			if (!self::$firstMessage && !empty($templateParams['firstPost']['message']))
			{
				self::$firstMessage = $templateParams['firstPost']['message'];
			}

			if (empty($hookParams['query']) && ($query = XenForo_Input::rawFilter($_GET['q'], XenForo_Input::STRING)))
			{
				$hookParams['query'] = urldecode($query);
			}

			if (!self::$adsForDisplay)
			{
				self::$adsForDisplay = self::_getAdsModel()->getActiveAdsForDisplay(false, array(
					'threads' => self::$threads,
					'thread'  => self::$thread
				), self::$itemCount);
			}

			$viewParams = array();

			foreach (self::$adsForDisplay as $hook => $typeAds)
			{
				if ($hookName == $hook)
				{
					$parentNodeId    = !empty($templateParams['forum']) ? $templateParams['forum']['parent_node_id'] : 0;
					$nodeId          = !empty($templateParams['forum']) ? $templateParams['forum']['node_id'] : 0;
					$noPriorityCount = 0;
					$priorityValues  = array();

					if (!$nodeId && self::$forum)
					{
						$parentNodeId = self::$forum['parent_node_id'];
						$nodeId       = self::$forum['node_id'];
					}

					if ($nodeId && !isset($templateParams['navigation']))
					{
						$templateParams['navigation'][]['node_id'] = $parentNodeId;
						$templateParams['navigation'][]['node_id'] = $nodeId;
					}

					foreach ($typeAds as $type => $ads)
					{
						$countExclude = 0;

						foreach ($ads as $key => $val)
						{
							$positionCriteria = XenForo_Helper_Criteria::unserializeCriteria($val['position_criteria']);
							$pageCriteria     = XenForo_Helper_Criteria::unserializeCriteria($val['page_criteria']);
							$userCriteria     = XenForo_Helper_Criteria::unserializeCriteria($val['user_criteria']);
							$deviceCriteria   = XenForo_Helper_Criteria::unserializeCriteria($val['device_criteria']);
							$geoIPCriteria    = XenForo_Helper_Criteria::unserializeCriteria($val['geoip_criteria']);

							if ((!empty($positionCriteria) && !Siropu_AdsManager_Helper_General::positionMatchesCriteria($positionCriteria, $hookParams, self::$thread, self::$firstMessage, self::$itemCount))
								|| (!empty($pageCriteria) && !XenForo_Helper_Criteria::pageMatchesCriteria($pageCriteria, false, $templateParams, $templateParams))
								|| (!empty($userCriteria) && !XenForo_Helper_Criteria::userMatchesCriteria($userCriteria))
								|| (!empty($deviceCriteria) && !Siropu_AdsManager_Helper_Device::deviceMatchesCriteria($deviceCriteria))
								|| (!empty($geoIPCriteria) && !Siropu_AdsManager_Helper_GeoIP::countryMatchesCriteria($geoIPCriteria))
								|| ($val['hide_from_robots'] && $templateParams['session']['robotId'])
								|| ($templateParams['controllerName'] == 'Siropu_AdsManager_ControllerPublic_Index')
								|| (!empty($templateParams['conversation']['conversation_id']) && $_POST['message_html'] && $type != 'keyword')
								|| self::$adsenseCount == self::_getOptions()->siropu_ads_manager_maximum_adsense_on_page)
							{
								unset($ads[$key]);
							}
							else
							{
								if ($type == 'code')
								{
									if (preg_match('/adsbygoogle/', $val['code']))
									{
										self::$adsenseCount += 1;
									}

									if (($val['target_blank'] || $val['nofollow']) && (preg_match('/<a(.+?)href="(.*?)"/', $val['code'])))
									{
										$addCode = '';

										if ($val['target_blank'] && !preg_match('/target="(.*?)"/', $val['code']))
									     {
									          $addCode .= ' target="_blank"';
									     }

									     if ($val['nofollow'] && !preg_match('/rel="nofollow"/', $val['code']))
									     {
									          $addCode .= ' rel="nofollow"';
									     }

										if ($addCode)
										{
											$ads[$key]['code'] = preg_replace('/(href="(.*?)")/', "$1$addCode", $val['code']);
										}
									}
								}

								if ($val['backup'])
								{
									$ads[$key]['backup'] = preg_replace('/src="(.*?)"/ui', 'data-src="$1"', $val['backup']);
								}

								if ($type == 'banner')
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

									if (Siropu_AdsManager_Helper_General::isSwf($val['banner']))
									{
										$ads[$key]['flash'] = true;
									}
								}

								if ($val['daily_stats'] || $val['click_stats'] || $val['ga_stats'])
								{
									$viewParams['stats'] = true;
								}

								if ($val['count_exclude'])
								{
									$countExclude += 1;
								}

								$ads[$key]['style']      = @unserialize($val['style']);
								$ads[$key]['attributes'] = Siropu_AdsManager_Helper_General::getAdAttributes($ads[$key]);
								$ads[$key]['size']       = Siropu_AdsManager_Helper_General::getAdWidthHeight($ads[$key]);

								if ($val['priority'] == 1)
								{
									$noPriorityCount += 1;
								}

								$priorityValues[$val['ad_id']] = $val['priority'];
							}
						}

						if ($adCount = count($ads))
						{
							$ad = current($ads);

							if (($type == 'code' || $type == 'banner' && $ads[$key]['banner_type'] == 2) && !self::$adCount)
							{
								self::$adCount = $adCount;
							}

							if ($adCount > 1 && ($order = $ad['ads_order']))
							{
								if ($order == 'random')
								{
									if (count($priorityValues) != $noPriorityCount)
									{
										Siropu_AdsManager_Helper_General::sortAdsByPriority($ads, $priorityValues);
									}
									else
									{
										shuffle($ads);
									}
								}
								else
								{
									usort($ads, 'Siropu_AdsManager_Helper_General::sortAdsByPackageCriteria');
								}

								if (in_array($type, array('code', 'banner', 'text')) && $ad['js_interval'])
								{
									for ($i = 0; $i < $adCount; $i++)
									{
										$ads[$i]['display'] = ($i == 0) ? '' : ' style="display: none;"';
									}

									$viewParams['jsRotator'] = $ad['js_interval'];
								}
							}

							if ($type != 'keyword' && ($maxDisplay = $ad['max_items_display']) && $adCount > $maxDisplay)
							{
								$ads = array_slice($ads, 0, $maxDisplay);
							}

							$viewParams['ads']            = $ads;
							$viewParams['ad']             = $ad;
							$viewParams['adCount']        = $adCount;
							$viewParams['countExclude']   = $countExclude;
							$viewParams['unitAttributes'] = Siropu_AdsManager_Helper_General::getUnitAttributes($hook, $type, $viewParams);
							$viewParams['costPerList']    = Siropu_AdsManager_Helper_General::getCostPerList();

							switch ($hook)
							{
								case 'ad_sidebar_top':
								case 'ad_sidebar_below_visitor_panel':
								case 'ad_sidebar_bottom':
									$viewParams['is_sidebar']['forum'] = true;
									break;
								case 'ad_member_view_below_avatar':
								case 'ad_member_view_sidebar_bottom':
									$viewParams['is_sidebar']['member'] = true;
									break;
							}

							$viewParams = array_merge($viewParams, $templateParams);

							switch ($type)
							{
								case 'code':
									$contents .= $template->create('siropu_ads_manager_ad_type_code', $viewParams);
									break;
								case 'banner':
									$viewParams['bannerPath'] = Siropu_AdsManager_Helper_General::getBannerPath();
									$contents .= $template->create('siropu_ads_manager_ad_type_banner', $viewParams);
									break;
								case 'text':
									$contents .= $template->create('siropu_ads_manager_ad_type_text', $viewParams);
									break;
								case 'link';
									$contents .= $template->create('siropu_ads_manager_ad_type_link', $viewParams);
									break;
								case 'keyword':
									$display = (isset($hookParams['message']['conversation_id']) && !self::_getOptions()->siropu_ads_manager_keyword_ads_in_conversations || ($nodeId && in_array($nodeId, (array) self::_getOptions()->siropu_ads_manager_keyword_exclude_nodes['node_id']))) ? false : true;

									if ($display)
									{
										preg_match('/<article>(.*)<\/article>/s', $contents, $match);

										$content = $currentContent = preg_replace_callback(
											'/([\\\]+|[\\\]*?\$)(\d+|{\d+})/',
											'self::_escapeBackreferences',
											$match[0]
										);

										foreach ($ads as $ad)
										{
											$viewParams['ad'] = $ad;
											$limit            = $ad['keyword_limit'] ? $ad['keyword_limit'] : self::_getOptions()->siropu_ads_manager_keyword_limit;

											foreach (array_map('trim', array_filter(explode("\n", $ad['items']))) as $keyword)
											{
												$content = preg_replace('/(<img[^>]*>|<article>)(*SKIP)(*FAIL)|\b(?<!\/|\.|-)' . preg_quote($keyword, '/') . '\b(?!(\.\S)|(.*?)<\/a>)/ui', $template->create('siropu_ads_manager_ad_type_keyword', $viewParams), $content, ($limit ? $limit : -1));
											}
										}

										if ($content != $currentContent)
										{
											$contents = preg_replace('/<article>(.*)<\/article>/s', $content, $contents);
										}
									}
									break;
							}
						}
					}
				}
			}
		}

		if ($hookName == 'footer_links')
		{
			$viewParams = array();

			if (self::_getOptions()->siropu_ads_manager_user_navigation_link
				&& (Siropu_AdsManager_Helper_General::userHasPermission('create')
					|| (self::_getOptions()->siropu_ads_manager_guest_mode['enabled']
						&& !XenForo_Visitor::getInstance()->user_id)))
			{
				$viewParams['advertiseLink'] = true;
			}

			if (self::_getOptions()->siropu_ads_manager_advertisers_list)
			{
				$viewParams['advertisersLink'] = true;
			}

			if ($viewParams)
			{
				$contents = $template->create('siropu_ads_manager_footer_links', array_merge($viewParams, $template->getParams())) . $contents;
			}
		}

		if ($hookName == 'page_criteria_page_info')
		{
			$contents .= $template->create('siropu_ads_manager_page_criteria_page_info', $template->getParams());
		}

		$adBlock = self::_getOptions()->siropu_ads_manager_ad_block_detector;

		if ($hookName == 'footer' && self::$adCount && ($adBlock == 'replace' || $adBlock == 'force') && empty($templateParams['session']['robotId']))
		{
			$contents .= $template->create('siropu_ads_manager_support_us', $template->getParams());
		}

		if ($hookName == 'admin_sidebar_home'
			&& self::_getOptions()->siropu_ads_manager_admin_home_menu
			&& XenForo_Visitor::getInstance()->hasAdminPermission('siropu_ads_manager'))
		{
			$contents = $template->create('siropu_ads_manager_admin_home',
				array_merge(array(
					'typeList' => Siropu_AdsManager_Helper_General::getAdTypes()
				), $template->getParams())) . $contents;
		}
	}
	protected static function _getAdsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Ads');
	}
	protected static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected static function _escapeBackreferences($matches)
	{
		$count = substr_count($matches[0], '\\');

		if (strpos($matches[0], '$') !== false)
		{
			$count = $count ? $count + 1 : 1;
		}

		return str_repeat('\\', $count) . $matches[0];
	}
}

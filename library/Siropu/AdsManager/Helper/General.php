<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Helper_General
{
	private static $posId = 0;

	public static function advertiserMatchesCriteria($criteria)
	{
		$visitor      = XenForo_Visitor::getInstance();
		$userGroups   = explode(',', $visitor->secondary_group_ids);
		$userGroups[] = $visitor->user_group_id;
		$criteria     = XenForo_Helper_Criteria::unserializeCriteria($criteria);

		if ($criteria && !array_intersect($criteria['user_groups'], $userGroups))
		{
			return false;
		}

		return true;
	}
	public static function positionMatchesCriteria($criteria, $hookParams, $thread, $message, $itemCount)
	{
		$threadIdIs              = array_filter(array_map('trim', explode(',', $criteria['thread_id'])));
		$threadIdNot             = array_filter(array_map('trim', explode(',', $criteria['thread_id_not'])));
		$threadTagIs             = array_filter(array_map('trim', explode(',', utf8_strtolower($criteria['thread_tag']))));
		$threadTagNot            = array_filter(array_map('trim', explode(',', utf8_strtolower($criteria['thread_tag_not']))));
		$threadTitleContanins    = array_filter(array_map('trim', explode(',', $criteria['thread_title_contains'])));
		$threadTitleNotContains  = array_filter(array_map('trim', explode(',', $criteria['thread_title_not_contains'])));
		$firstMessageContains    = array_filter(array_map('trim', explode(',', $criteria['first_post_contains'])));
		$firstMessageNotContains = array_filter(array_map('trim', explode(',', $criteria['first_post_not_contains'])));
		$messageContains         = array_filter(array_map('trim', explode(',', $criteria['post_contains'])));
		$messageNotContains      = array_filter(array_map('trim', explode(',', $criteria['post_not_contains'])));
		$keywordIs               = array_filter(array_map('trim', explode(',', $criteria['search_keyword'])));
		$keywordNot              = array_filter(array_map('trim', explode(',', $criteria['search_keyword_not'])));

		$minResults = $criteria['min_results'] > 1 ? $criteria['min_results'] : self::_getOptions()->siropu_ads_manager_discussion_min_results;

		if ($itemCount && $itemCount < $minResults)
		{
			return false;
		}

		if ($thread && ($threadIdIs && !in_array($thread['thread_id'], $threadIdIs)
			|| $threadIdNot && in_array($thread['thread_id'], $threadIdNot)))
		{
			return false;
		}

		if ($thread && ($threadTagIs || $threadTagNot))
		{
			if ($threadTagIs && !$thread['tagsList'])
			{
				return false;
			}

			$hasTag = $threadTagIs ? false : true;

			foreach ($thread['tagsList'] as $tag)
			{
				if ($threadTagIs && in_array($tag['tag'], $threadTagIs))
				{
					$hasTag = true;
				}
				if ($threadTagNot && in_array($tag['tag'], $threadTagNot))
				{
					$hasTag = false;
				}
			}

			if (!$hasTag)
			{
				return false;
			}
		}

		if (!empty($thread['title'])
			&& ($threadTitleContanins && !preg_match('/\b' . implode('|', $threadTitleContanins) . '\b/ui', $thread['title'])
				|| $threadTitleNotContains && preg_match('/\b' . implode('|', $threadTitleNotContains) . '\b/ui', $thread['title'])))
		{
			return false;
		}

		if (!empty($hookParams['tag'])
			&& ($threadTagIs && !in_array($hookParams['tag'], $threadTagIs)
				|| $threadTagNot && in_array($hookParams['tag'], $threadTagNot)))
		{
			return false;
		}

		if ($message
			&& ($firstMessageContains && !preg_match('/\b' . implode('|', $firstMessageContains) . '\b/ui', $message)
				|| $firstMessageNotContains && preg_match('/\b' . implode('|', $firstMessageNotContains) . '\b/ui', $message)))
		{
			return false;
		}

		if (!empty($hookParams['message']['message'])
			&& ($messageContains && !preg_match('/\b' . implode('|', $messageContains) . '\b/ui', $hookParams['message']['message'])
				|| $messageNotContains && preg_match('/\b' . implode('|', $messageNotContains) . '\b/ui', $hookParams['message']['message'])))
		{
			return false;
		}

		if (!empty($hookParams['query']) && ($keywordIs || $keywordNot))
		{
			if ($keywordIs)
			{
				$hasKeyword = false;

				foreach($keywordIs as $keyword)
				{
					if (preg_match('/\b' . preg_quote($keyword) . '\b/ui', $hookParams['query']))
					{
						$hasKeyword = true;
					}
				}
			}

			if ($keywordNot)
			{
				$hasKeyword = true;

				foreach($keywordNot as $keyword)
				{
					if (preg_match('/\b' . preg_quote($keyword) . '\b/ui', $hookParams['query']))
					{
						$hasKeyword = false;
					}
				}
			}

			if (!$hasKeyword)
			{
				return false;
			}
		}

		return true;
	}
	public static function getPositionCategorySelectList($resultArray)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			$list[$row['cat_id']] = $row['title'];
		}
		return $list;
	}
	public static function getPositionSelectList($resultArray)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			$list[$row['hook']] = $row['name'];
		}
		return $list;
	}
	public static function getPositionSelectListByAdType($resultArray)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			$list[$row['hook']] = $row['name'];
		}
		return $list;
	}
	public static function getPackageSelectList($resultArray)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			$list[$row['package_id']] = $row['name'];
		}
		return $list;
	}
	public static function groupPackagesByType($resultArray)
	{
		$list = array();

		foreach ($resultArray as $row)
		{
			if (!self::advertiserMatchesCriteria($row['advertiser_criteria']))
			{
				$row['noAccess'] = true;
			}

			$list[$row['type']][] = $row;
		}

		ksort($list);
		return $list;
	}
	public static function getPackageTypeList($resultArray)
	{
		$types = self::getAdTypes();
		$list  = array();

		foreach ($resultArray as $row)
		{
			$list[$row['type']] = $types[$row['type']];
		}

		return $list;
	}
	public static function getCurrencyList($option = '', $list = false)
	{
		$currecies = array(
			'PayPal' => array(
				'AUD' => 'AUD',
				'BRL' => 'BRL',
				'CAD' => 'CAD',
				'CZK' => 'CZK',
				'DKK' => 'DKK',
				'EUR' => 'EUR',
				'HKD' => 'HKD',
				'HUF' => 'HUF',
				'ILS' => 'ILS',
				'JPY' => 'JPY',
				'MYR' => 'MYR',
				'MXN' => 'MXN',
				'NOK' => 'NOK',
				'NZD' => 'NZD',
				'PHP' => 'PHP',
				'PLN' => 'PLN',
				'GBP' => 'GBP',
				'RUB' => 'RUB',
				'SGD' => 'SGD',
				'SEK' => 'SEK',
				'CHF' => 'CHF',
				'TWD' => 'TWD',
				'THB' => 'THB',
				'TRY' => 'TRY',
				'USD' => 'USD',
			),
			'ROBOKASSA' => array(
				'RUB' => 'WMR',
				'USD' => 'WMZ',
				'EUR' => 'WME',
				'UAH' => 'WMU',
				'UZS' => 'WMY',
				'BYR' => 'WMB',
				'WMG' => 'WMG'
			),
			'ZarinPal' => array(
				'IRR' => 'IRR'
			),
			'Stripe' => array(
				'AED' => 'AED',
				'AFN' => 'AFN',
				'ALL' => 'ALL',
				'AMD' => 'AMD',
				'ANG' => 'ANG',
				'AOA' => 'AOA',
				'ARS' => 'ARS',
				'AUD' => 'AUD',
				'AWG' => 'AWG',
				'AZN' => 'AZN',
				'BAM' => 'BAM',
				'BBD' => 'BBD',
				'BDT' => 'BDT',
				'BGN' => 'BGN',
				'BIF' => 'BIF',
				'BMD' => 'BMD',
				'BND' => 'BND',
				'BOB' => 'BOB',
				'BRL' => 'BRL',
				'BSD' => 'BSD',
				'BWP' => 'BWP',
				'BZD' => 'BZD',
				'CAD' => 'CAD',
				'CHF' => 'CHF',
				'CLP' => 'CLP',
				'CNY' => 'CNY',
				'COP' => 'COP',
				'CRC' => 'CRC',
				'CVE' => 'CVE',
				'CZK' => 'CZK',
				'DJF' => 'DJF',
				'DKK' => 'DKK',
				'DOP' => 'DOP',
				'DZD' => 'DZD',
				'EGP' => 'EGP',
				'ETB' => 'ETB',
				'EUR' => 'EUR',
				'FJD' => 'FJD',
				'FKP' => 'FKP',
				'GEL' => 'GEL',
				'GBP' => 'GBP',
				'GIP' => 'GIP',
				'GMD' => 'GMD',
				'GNF' => 'GNF',
				'GTQ' => 'GTQ',
				'GYD' => 'GYD',
				'HKD' => 'HKD',
				'HNL' => 'HNL',
				'HRK' => 'HRK',
				'HTG' => 'HTG',
				'HUF' => 'HUF',
				'IDR' => 'IDR',
				'ILS' => 'ILS',
				'INR' => 'INR',
				'ISK' => 'ISK',
				'JMD' => 'JMD',
				'JPY' => 'JPY',
				'KGS' => 'KGS',
				'KMF' => 'KMF',
				'KRW' => 'KRW',
				'KYD' => 'KYD',
				'KZT' => 'KZT',
				'LAK' => 'LAK',
				'LBP' => 'LBP',
				'LKR' => 'LKR',
				'LSL' => 'LSL',
				'LRD' => 'LRD',
				'MAD' => 'MAD',
				'MDL' => 'MDL',
				'MGA' => 'MGA',
				'MNT' => 'MNT',
				'MOP' => 'MOP',
				'MRO' => 'MRO',
				'MUR' => 'MUR',
				'MVR' => 'MVR',
				'MKD' => 'MKD',
				'MWK' => 'MWK',
				'MXN' => 'MXN',
				'MYR' => 'MYR',
				'MZN' => 'MZN',
				'NAD' => 'NAD',
				'NGN' => 'NGN',
				'NIO' => 'NIO',
				'NOK' => 'NOK',
				'NPR' => 'NPR',
				'NZD' => 'NZD',
				'PAB' => 'PAB',
				'PEN' => 'PEN',
				'PGK' => 'PGK',
				'PHP' => 'PHP',
				'PKR' => 'PKR',
				'PLN' => 'PLN',
				'PYG' => 'PYG',
				'QAR' => 'QAR',
				'RON' => 'RON',
				'RUB' => 'RUB',
				'RSD' => 'RSD',
				'RWF' => 'RWF',
				'SAR' => 'SAR',
				'SBD' => 'SBD',
				'SCR' => 'SCR',
				'SEK' => 'SEK',
				'SGD' => 'SGD',
				'SHP' => 'SHP',
				'SLL' => 'SLL',
				'SOS' => 'SOS',
				'SRD' => 'SRD',
				'STD' => 'STD',
				'SVC' => 'SVC',
				'SZL' => 'SZL',
				'THB' => 'THB',
				'TJS' => 'TJS',
				'TOP' => 'TOP',
				'TRY' => 'TRY',
				'TTD' => 'TTD',
				'TWD' => 'TWD',
				'TZS' => 'TZS',
				'UAH' => 'UAH',
				'UGX' => 'UGX',
				'USD' => 'USD',
				'UYU' => 'UYU',
				'UZS' => 'UZS',
				'VND' => 'VND',
				'VUV' => 'VUV',
				'WST' => 'WST',
				'XAF' => 'XAF',
				'XCD' => 'XCD',
				'XOF' => 'XOF',
				'XPF' => 'XPF',
				'YER' => 'YER',
				'ZAR' => 'ZAR',
				'ZMW' => 'ZMW'
				),
				'Skrill' => array(
				'AED' => 'AED',
				'AUD' => 'AUD',
				'BGN' => 'BGN',
				'BHD' => 'BHD',
				'CAD' => 'CAD',
				'CHF' => 'CHF',
				'CZK' => 'CZK',
				'DKK' => 'DKK',
				'EUR' => 'EUR',
				'GBP' => 'GBP',
				'HKD' => 'HKD',
				'HRK' => 'HRK',
				'HUF' => 'HUF',
				'ILS' => 'ILS',
				'INR' => 'INR',
				'ISK' => 'ISK',
				'JOD' => 'JOD',
				'JPY' => 'JPY',
				'KRW' => 'KRW',
				'KWD' => 'KWD',
				'MAD' => 'MAD',
				'MYR' => 'MYR',
				'NOK' => 'NOK',
				'NZD' => 'NZD',
				'OMR' => 'OMR',
				'PLN' => 'PLN',
				'QAR' => 'QAR',
				'RON' => 'RON',
				'RSD' => 'RSD',
				'SAR' => 'SAR',
				'SEK' => 'SEK',
				'SGD' => 'SGD',
				'TND' => 'TND',
				'TRY' => 'TRY',
				'TWD' => 'TWD',
				'USD' => 'USD',
				'ZAR' => 'ZAR'
			)
		);

		if ($option)
		{
			return $currecies[$option];
		}

		if ($list)
		{
			return array_merge(
				$currecies['PayPal'],
				$currecies['ZarinPal'],
				$currecies['Stripe'],
				$currecies['Skrill']
			);
		}

		return $currecies;
	}
	public static function paymentOptions()
	{
		return array(
			'PayPal'        => 'PayPal',
			'ROBOKASSA'     => 'ROBOKASSA',
			'ZarinPal'      => 'ZarinPal',
			'Stripe'        => 'Stripe',
			'Skrill'        => 'Skrill',
			'Bitcoin'       => 'Bitcoin',
			'Bank Transfer' => new XenForo_Phrase('siropu_ads_manager_payment_option_bank_transfer'),
			'Cash'          => new XenForo_Phrase('siropu_ads_manager_payment_option_cash'),
			'Promo Code'    => new XenForo_Phrase('siropu_ads_manager_promo_code'),
		);
	}
	public static function getAdsByStatus($resultArray, $status)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			if ($row['status'] == $status)
			{
				$list[] = $row;
			}
		}
		return $list;
	}
	public function getAdsExpiring($resultArray)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			if ($row['status'] == 'Active' && self::adIsExpiring($row))
			{
				$list[] = $row;
			}
		}
		return $list;
	}
	public static function getHiddenPositionsCount($positions)
	{
		$count = 0;
		foreach ($positions as $pos)
		{
			if (!$pos['visible'])
			{
				$count++;
			}
		}
		return $count;
	}
	public static function groupPositionsByCategory($positions, $categories, $excludeEmpty = false)
	{
		foreach ($positions as $pos)
		{
			$categories[$pos['cat_id']]['positions'][] = $pos;
		}

		if ($excludeEmpty)
		{
			foreach ($categories as $key => $val)
			{
				if (!isset($val['positions']))
				{
					unset($categories[$key]);
				}
			}
		}

		return $categories;
	}
	public static function groupAdsByPackage($ads, $packages)
	{
		$list = array();

		foreach ($ads as $ad)
		{
			$packageId = $ad['package_id'];

			if (isset($packages[$packageId]) && !isset($list[$packageId]['ads']))
			{
				$list[$packageId] = $packages[$packageId];
			}

			$list[$packageId]['ads'][] = $ad;
		}

		ksort($list);
		return $list;
	}
	public static function groupAdsByHookAndType($resultArray, $item, $itemCount)
	{
		$data = array();

		foreach ($resultArray as $row)
		{
			foreach (array_filter(explode("\n", $row['positions'])) as $position)
			{
				if (preg_match('/_x$/i', $position) && ($ids = self::prepareItemIds($row['item_id'])))
				{
					$isThreadList = preg_match('/sam_thread_list_after_item_/', $position) && !empty($item['threads']);

					foreach ($ids as $id)
					{
						switch ($id)
						{
							case 'r':
								if (!self::$posId)
								{
									if ($isThreadList)
									{
										shuffle($item['threads']);
										self::$posId = $item['threads'][0];
									}
									else
									{
										self::$posId = rand(1, $itemCount);
									}
								}
								break;
							case 'l':
								if ($isThreadList)
								{
									self::$posId = end($item['threads']);
								}
								else
								{
									self::$posId = $itemCount;
								}
								break;
							case 'u':
								if (!empty($item['thread']['first_unread_post_id']))
								{
									self::$posId = $item['thread']['first_unread_post_id'];
								}
								break;
							default:
								if ($isThreadList && !empty($item['threads'][$id - 1]))
								{
									self::$posId = $item['threads'][$id - 1];
								}
								else if (self::$posId)
								{
									self::$posId = $id;
								}
								break;
						}

						$posX = preg_replace('/_x$/i', '_' . (self::$posId ? self::$posId : $id), $position);
						$data[$posX][$row['type']][$row['ad_id']] = $row;

						if (!empty($item['thread']['isNew']) && !empty($item['thread']['first_unread_post_id']))
						{
							break;
						}
					}
				}
				else
				{
					$data[$position][$row['type']][] = $row;
				}
			}
		}
		return $data;
	}
	public static function groupThreadsByForumId($resultArray)
	{
		$data = array();
		foreach ($resultArray as $row)
		{
			$data[$row['node_id']][] = $row['thread_id'];
		}
		return $data;
	}
	public static function prepareItemIds($ids)
	{
		$num = $str = array();

		foreach (array_filter(array_map('trim', explode(',', strtolower($ids)))) as $id)
		{
			if (is_numeric($id))
			{
				$num[] = $id;
			}
			else
			{
				$str[] = $id;
			}
		}

		sort($num);
		rsort($str);

		return array_merge($str, $num);
	}
	public static function setAvailableAdSlotCount($packages, $hideExtra = false)
	{
		$options      = self::_getOptions();
		$stickyForums = $options->siropu_ads_manager_sticky_forum_list;
		$forumsModel  = XenForo_Model::create('Siropu_AdsManager_Model_Forums');

		foreach ($packages as $key => $val)
		{
			if ($packages[$key]['type'] == 'sticky')
			{
				$adCount = count($forumsModel->getForumsStickyList($stickyForums));
				$limit = $options->siropu_ads_manager_max_stickies_per_forum * count($stickyForums['node_id']);

				if ($pausedAds = self::_getAdsModel()->getAllAds('Paused', array('type' => 'sticky')))
				{
					$adCount += count($pausedAds);
				}
			}
			else
			{
				$adCount = $packages[$key]['adCount'];
				$limit   = $packages[$key]['max_items_allowed'];
			}

			$packages[$key]['style']             = @unserialize($packages[$key]['style']);
			$packages[$key]['adCount']           = ($adCount > $limit && $hideExtra) ? $limit : $adCount;
			$packages[$key]['max_items_allowed'] = $limit;
		}

		return $packages;
	}
	public static function adIsExpiring($row)
	{
		$purchase = $row['purchase'];
		$active   = $row['date_active'];
		$end      = $row['date_end'];
		$length   = 0;

		if ($end)
		{
			if ($purchase)
			{
				switch ($row['cost_per'])
				{
					case 'Day':
						if ($purchase == 1)
						{
							$length = '6 Hours';
						}
						else if ($purchase > 1 && $purchase <= 7)
						{
							$length = '1 Day';
						}
						else if ($purchase > 7 && $purchase <= 14)
						{
							$length = '3 Days';
						}
						else if ($purchase > 30)
						{
							$length = '7 Days';
						}
						break;
					case 'Week':
						$length = '1 Day';
						break;
					case 'Month':
						$length = '3 Days';
						break;
					case 'Year':
						$length = '7 Days';
						break;
				}
			}
			else
			{
				$elapsedTime = $end - time();
				$activeSince = time() - $active;

				if ($activeSince >= 2592000)
				{
					$length = '7 Days';
				}
				else if ($activeSince >= 1209600)
				{
					$length = '3 Days';
				}
				else
				{
					$length = '1 Day';
				}
			}
		}

		if (($row['count_views'] && $row['view_limit'] && ($row['view_count'] * 100 / $row['view_limit']) >= 70)
			|| ($row['count_clicks'] && $row['click_limit'] && ($row['click_count'] * 100 / $row['click_limit']) >= 70)
			|| ($end && $end <= strtotime("+{$length}")))
		{
			return true;
		}
	}
	public static function checkForAvailableAdSlots($ad)
	{
		$pendingTransactionAdCount = self::_getAdsModel()->getPendingTransactionAdCount($ad['package_id']);
		$pendingQueuedAdCount      = count(self::_getAdsModel()->getAllAds('', array('package_id' => $ad['package_id'], 'status_old' => 'Queued')));

		if ($ad['type'] == 'sticky')
		{
			$threadsModel  = XenForo_Model::create('XenForo_Model_Thread');
			$thread        = $threadsModel->getThreadById($ad['items']);
			$forumId       = $thread ? $thread['node_id'] : 0;
			$pausedAdCount = 0;

			if ($pausedAds = self::_getAdsModel()->getAllAds('Paused', array('type' => 'sticky')))
			{
				foreach ($pausedAds as $row)
				{
					if (($pThread = $threadsModel->getThreadById($row['items'])) && $pThread['node_id'] == $forumId)
					{
						$pausedAdCount += 1;
					}
				}
			}

			if ($forumId && (count(XenForo_Model::create('Siropu_AdsManager_Model_Forums')->getForumStickyList($forumId)) + $pendingTransactionAdCount + $pausedAdCount) < self::_getOptions()->siropu_ads_manager_max_stickies_per_forum)
			{
				return true;
			}
		}
		else if ($ad['package_id'] && (XenForo_Model::create('Siropu_AdsManager_Model_Packages')->getPackageActiveAdCount($ad['package_id']) + $pendingTransactionAdCount + $pendingQueuedAdCount) < $ad['max_items_allowed'])
		{
			return true;
		}
	}
	public static function prepareGroupListForDisplay($data)
	{
		$list = array();
		foreach (explode("\n", $data) as $group)
		{
			if ($items = array_filter(explode('=', $group)))
			{
				$list[$items[0]] = $items[1];
			}
		}
		return $list;
	}
	public static function prepareGroupListForStorage($inputArray)
	{
		$key  = current($inputArray);
		$val  = end($inputArray);
		$list = '';

		for ($i = 0; $i < count($key); $i++)
		{
			if (isset($key[$i]) && trim($key[$i]) && isset($val[$i]) && trim($val[$i]))
			{
				$list .= trim($key[$i]) . '=' . trim($val[$i]) . "\n";
			}
		}
		return $list;
	}
	public static function prepareForumListForDisplay($resultArray, $costAmount, $costList, $costCurrency)
	{
		$costList = self::prepareGroupListForDisplay($costList);
		$list    = array();

		if ($resultArray)
		{
			foreach ($resultArray as $row)
			{
				$id     = $row['node_id'];
				$list[] = array(
					'id'       => $id,
					'title'    => strip_tags($row['title']),
					'cost'     => isset($costList[$id]) ? $costList[$id] : $costAmount,
					'currency' => $costCurrency
				);
			}
		}

		return $list;
	}
	public static function prepareResourceCategoryListForDisplay($resultArray, $costAmount, $costList, $costCurrency)
	{
		$costList = self::prepareGroupListForDisplay($costList);
		$list    = array();

		if ($resultArray)
		{
			foreach ($resultArray as $row)
			{
				$id     = $row['resource_category_id'];
				$list[] = array(
					'id'       => $id,
					'title'    => strip_tags($row['category_title']),
					'cost'     => isset($costList[$id]) ? $costList[$id] : $costAmount,
					'currency' => $costCurrency
				);
			}
		}

		return $list;
	}
	public static function sortAdsById($resultArray)
	{
		$data = array();
		foreach ($resultArray as $row)
		{
			$data[$row['ad_id']] = $row;
		}
		return $data;
	}
	public static function sortAdsByPackageCriteria($a, $b)
	{
		$order = $a['ads_order'];

		switch ($order)
		{
			case 'dateAsc':
			case 'dateDesc':
				$f = 'date_created';
				break;
			case 'orderAsc':
			case 'orderDesc':
				$f = 'ad_order';
				break;
			case 'ctrAsc':
			case 'ctrDesc':
				$f = 'ctr';
				break;
		}

		if ($a[$f] == $b[$f])
		{
			return 0;
		}

		return strpos($order, 'Desc') ? (($a[$f] > $b[$f]) ? -1 : 1) : (($a[$f] < $b[$f]) ? -1 : 1);
	}
	public static function sortAdsByPriority(&$ads, $priorityValues)
	{
		$adCount   = count($ads);
		$orderList = array();
		$newAds    = array();

		for ($i = 0; $i < $adCount; $i++)
		{
			$id = self::getRandomWeightedElement($priorityValues);
			$orderList[$id] = $i;
			unset($priorityValues[$id]);
		}

		foreach ($ads as $ad)
		{
			if (isset($orderList[$ad['ad_id']]))
			{
				$newAds[$orderList[$ad['ad_id']]] = $ad;
			}
		}

		ksort($newAds);
		$ads = $newAds;
	}
	public static function getRandomWeightedElement(array $weightedValues)
	{
		$rand = mt_rand(1, (int) array_sum($weightedValues));

		foreach ($weightedValues as $key => $value)
		{
			$rand -= $value;

			if ($rand <= 0)
			{
				return $key;
			}
		}
	}
	public static function groupAdsByStatus($resultArray)
	{
		$data = array();
		foreach ($resultArray as $row)
		{
			$data[$row['status']][] = $row;
		}
		return $data;
	}
	public static function groupAdsByType($resultArray)
	{
		$data = array();
		foreach ($resultArray as $row)
		{
			$data[$row['type']][] = $row;
		}
		return $data;
	}
	public static function getAdTypes()
	{
		return array(
			'code'     => new XenForo_Phrase('siropu_ads_manager_type_code'),
			'banner'   => new XenForo_Phrase('siropu_ads_manager_type_banner'),
			'text'     => new XenForo_Phrase('siropu_ads_manager_type_text'),
			'link'     => new XenForo_Phrase('siropu_ads_manager_type_link'),
			'sticky'   => new XenForo_Phrase('siropu_ads_manager_type_sticky'),
			'keyword'  => new XenForo_Phrase('siropu_ads_manager_type_keyword'),
			'featured' => new XenForo_Phrase('siropu_ads_manager_type_featured'),
		);
	}
	public static function getAdSizes()
	{
		return array(
			array(
				'group' => new XenForo_Phrase('siropu_ads_manager_unit_size_custom'),
				'sizes' => array_map('trim', array_filter(explode("\n", strtolower(self::_getOptions()->siropu_ads_manager_custom_ad_sizes))))
			),
			array(
				'group' => new XenForo_Phrase('siropu_ads_manager_unit_size_horizontal'),
				'sizes' => array('728x90', '468x60', '320x100')
			),
			array(
				'group' => new XenForo_Phrase('siropu_ads_manager_unit_size_vertical'),
				'sizes'    => array('300x600', '160x600', '120x600')
			),
			array(
				'group' => new XenForo_Phrase('siropu_ads_manager_unit_size_rectangular'),
				'sizes' => array('300x250', '250x250', '200x200')
			)
		);
	}
	public static function getAdWidthHeight($data, $raw = false)
	{
		if (($values = array_map('intval', explode('x', str_ireplace('px', '', @$data['style']['size'])))) && (count($values) == 2))
		{
			if ($raw)
			{
				return array('width' => $values[0], 'height' =>  $values[1]);
			}

			return ' width="' . $values[0] . '" height="' . $values[1] . '"';
		}
	}
	public static function isSwf($file)
	{
		return preg_match('/\.swf/i', $file);
	}
	public static function getStatusList()
	{
		return array(
			'Active'    => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_active'), 'state' => 1),
			'Inactive'  => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_inactive'), 'state' => 0),
			'Pending'   => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_pending'), 'state' => 0),
			'Approved'  => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_approved'), 'state' => 0),
			'Queued'    => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_queued'), 'state' => 0),
			'Paused'    => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_paused'), 'state' => 0),
			'Rejected'  => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_rejected'), 'state' => 0),
			'Completed' => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_completed'), 'state' => 0),
			'Cancelled' => array('phrase' => new XenForo_Phrase('siropu_ads_manager_status_cancelled'), 'state' => 0),
		);
	}
	public static function getCostPerList()
	{
		return array(
			'Day'   => array(
				'singular' => new XenForo_Phrase('siropu_ads_manager_day'),
				'plural'   => new XenForo_Phrase('siropu_ads_manager_days')
			),
			'Week'  => array(
				'singular' => new XenForo_Phrase('siropu_ads_manager_week'),
				'plural'   => new XenForo_Phrase('siropu_ads_manager_weeks')
			),
			'Month' => array(
				'singular' => new XenForo_Phrase('siropu_ads_manager_month'),
				'plural'   => new XenForo_Phrase('siropu_ads_manager_months')
			),
			'Year'  => array(
				'singular' => new XenForo_Phrase('siropu_ads_manager_year'),
				'plural'   => new XenForo_Phrase('siropu_ads_manager_years')
			),
			'CPM'   => array(
				'singular' => new XenForo_Phrase('siropu_ads_manager_cpm'),
				'plural'   => new XenForo_Phrase('siropu_ads_manager_cpm')
			),
			'CPC'   => array(
				'singular' => new XenForo_Phrase('siropu_ads_manager_cpc'),
				'plural'   => new XenForo_Phrase('siropu_ads_manager_cpc')
			)
		);
	}
	public static function getBannerPath($path = 'relative')
	{
		switch ($path)
		{
			case 'relative':
				return 'data/Siropu/images';
				break;
			case 'absolute':
				return XenForo_Helper_File::getExternalDataPath() . '/Siropu/images';
				break;
			case 'url':
				return self::_getOptions()->boardUrl . '/data/Siropu/images';
				break;
		}
	}
	public static function deleteBanner($banner)
	{
		@unlink(self::getBannerPath() . '/' . $banner);
	}
	public static function uploadBanner(XenForo_Upload $upload, $userId, $size = array(), $cp = '')
	{
		$extension = XenForo_Helper_File::getFileExtension($upload->getFileName());

		if (!$userId)
		{
			throw new XenForo_Exception('Missing user ID.');
		}

		if (!$upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		$fileTypes = array();

		foreach (self::_getOptions()->siropu_ads_manager_allowed_img_extensions as $key => $val)
		{
			if ($val)
			{
				$fileTypes[] = $key;

				if ($key == 'jpg')
				{
					$fileTypes[] = 'jpe';
					$fileTypes[] = 'jpeg';
				}
			}
		}

		if (self::_getOptions()->siropu_ads_manager_flash_allowed || $cp == 'acp')
		{
			$fileTypes[] = 'swf';
		}

		if (!in_array($extension, $fileTypes))
		{
			throw new XenForo_Exception(new XenForo_Phrase('siropu_ads_manager_extension_not_allowed',
				array('extensions' => implode(', ', array_map('strtoupper', $fileTypes)))), true);
		}

		if ($size
			&& $extension != 'swf'
			&& ($upload->getImageInfoField('width') != $size['width']
				|| $upload->getImageInfoField('height') != $size['height']))
		{
			throw new XenForo_Exception(new XenForo_Phrase('siropu_ads_manager_banner_size_info',
				array('width' => $size['width'], 'height' => $size['height'])), true);
		}

		$bannerPath = self::getBannerPath('absolute');
		$fileName   = uniqid($userId) . '.' . $extension;
		$filePath   = $bannerPath . '/' . $fileName;

		if (!file_exists($bannerPath))
		{
			XenForo_Helper_File::createDirectory($bannerPath, true);
		}

		if (XenForo_Helper_File::safeRename($upload->getTempFile(), $filePath))
		{
			return $fileName;
		}
	}
	public static function getHourMinuteList($limit)
	{
		$list = array();
		foreach (range(0, $limit) as $num)
		{
			$num = str_pad($num, 2, '0',  STR_PAD_LEFT);
			$list[$num] = $num;
		}
		return $list;
	}
	public static function getSpinboxStep($costPer)
	{
		switch ($costPer)
		{
			case 'CPM':
				return 1000;
				break;
			case 'CPC':
				return 10;
				break;
			default:
				return 1;
				break;
		}
	}
	public static function calculateAdCost($data)
	{
		$items    = array_filter(array_unique(explode("\n", $data['items'])));
		$costList = self::prepareGroupListForDisplay($data['cost_list']);
		$s_cost   = $data['cost_amount'];
		$c_cost   = 0;

		switch ($data['type'])
		{
			case 'sticky':
				$sticky  = XenForo_Model::create('XenForo_Model_Thread')->getThreadById($items[0]);
				$forumId = isset($sticky['node_id']) ? $sticky['node_id'] : 0;
				$c_cost += (isset($costList[$forumId]) ? $costList[$forumId] : $s_cost);
				break;
			case 'keyword':
				if ($costList)
				{
					$premium = 0;
					foreach ($costList as $key => $val)
					{
						foreach ($items as $item)
						{
							if (preg_match("/\b{$key}\b/i", $item))
							{
								$c_cost += $val;
								$premium++;
							}
						}
					}

					if ($common = (count($items) - $premium))
					{
						$c_cost += $common * $s_cost;
					}
				}
				else
				{
					$c_cost += count($items) * $s_cost;
				}
				break;
		}

		$purchase         = $data['purchase'] ? $data['purchase'] : 1;
		$discount_percent = 0;

		if ($discountList = self::prepareGroupListForDisplay($data['discount']))
		{
			foreach ($discountList as $key => $val)
			{
				if ($purchase >= $key)
				{
					$discount_percent = $val;
				}
			}
		}

		$cost  = $c_cost ? $c_cost : $s_cost;
		$total = ($data['cost_per'] == 'CPM') ? ($cost * ($purchase / 1000)) : $cost * $purchase;

		$discount_amount = $discount_percent ? ($total * $discount_percent) / 100 : 0;

		return array(
			'total'      => self::formatPrice($total),
			'discounted' => self::formatPrice($total - $discount_amount),
			'unit'       => @self::formatPrice($total / (($data['cost_per'] == 'CPM') ? $purchase / 1000 : $purchase)),
			'discount'   => self::formatPrice($discount_amount),
			'percent'    => $discount_percent,
			'currency'   => $data['cost_currency']
		);
	}
	public static function getTransactionsRevenue($resultArray, $getStatus = 'Completed')
	{
		foreach ($resultArray as $row)
		{
			if ($row['cost_amount'] > 0)
			{
				if (!isset($statusList[$row['status']][$row['cost_currency']]))
				{
					$statusList[$row['status']][$row['cost_currency']] = 0;
				}

				$statusList[$row['status']][$row['cost_currency']] += self::formatPrice($row['cost_amount']);
			}

			if ($row['status'] == 'Completed' && $row['cost_amount_btc'] > 0)
			{
				if (!isset($statusList[$row['status']]['BTC']))
				{
					$statusList[$row['status']]['BTC'] = 0;
				}

				$statusList[$row['status']]['BTC'] += $row['cost_amount_btc'];
			}
		}

		if (isset($statusList[$getStatus]))
		{
			return $statusList[$getStatus];
		}

		return 0;
	}
	public static function validateAdInput($inputArray, $ad, $cp = '')
	{
		$type        = $inputArray['type'];
		$title       = $inputArray['title'];
		$description = $inputArray['description'];
		$items       = $inputArray['items'];
		$purchase    = isset($inputArray['purchase']) ? $inputArray['purchase'] : 0;
		$url         = true;
		$error       = array();

		if (!$inputArray['name'])
		{
			$error[] = new XenForo_Phrase('siropu_ads_manager_ad_name_is_required');
		}

		$options              = self::_getOptions();
		$maxTitleLength       = $options->siropu_ads_manager_ad_title_max_length;
		$maxDescriptionLength = $options->siropu_ads_manager_ad_description_max_length;
		$maxKeywordWords      = $options->siropu_ads_manager_keyword_max_words;

		switch ($type)
		{
			case 'code':
				if (!$inputArray['code'])
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_code_is_required');
				}
				break;
			case 'banner':
				$bannerFile = $inputArray['banner'] || XenForo_Upload::getUploadedFile('banner');
				$bannerCode = $inputArray['code'];

				if (!($bannerFile || $inputArray['banner_url']) && !$bannerCode)
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_banner_is_required');
				}

				if ($bannerCode
					&& preg_match('/(<script|<iframe|<object|<embed)/i', htmlspecialchars_decode($bannerCode))
					&& $cp != 'acp')
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_banner_code_disallowed');
				}

				if ($bannerCode && !$bannerFile)
				{
					$url = false;
				}

				break;
			case 'text':
				if (!$title)
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_title_is_required');
				}
				if (!$description)
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_description_is_required');
				}
				else if ($maxDescriptionLength && strlen($description) > $maxDescriptionLength && $purchase)
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_description_max_length',
						array('limit' => $maxDescriptionLength));
				}
				break;
			case 'link':
				if (!$title)
				{
					$error[] = new XenForo_Phrase('siropu_ads_manager_ad_title_is_required');
				}
				break;
			case 'sticky':
			case 'keyword':
				if ($ad && in_array($ad['status'], array('Active', 'Approved')))
				{
					continue;
				}

				if (!$items)
				{
					if ($type == 'sticky')
					{
						$error[] = new XenForo_Phrase('siropu_ads_manager_ad_sticky_is_required');
					}
					else
					{
						$error[] = new XenForo_Phrase('siropu_ads_manager_ad_keyword_is_required');
					}
				}
				else if ($type == 'keyword' && $maxKeywordWords)
				{
					$limitReached = 0;

					foreach (array_filter(explode("\n", $items)) as $keyword)
					{
						if (str_word_count($keyword, 0) > $maxKeywordWords)
						{
							$limitReached++;
						}
					}

					if ($limitReached)
					{
						$error[] = new XenForo_Phrase('siropu_ads_manager_ad_keyword_max_words',
							array('limit' => $maxKeywordWords));
					}
				}
				break;
		}

		if (!in_array($type, array('code', 'sticky', 'featured'))
			&& $url
			&& !filter_var(filter_var($inputArray['url'], FILTER_SANITIZE_URL), FILTER_VALIDATE_URL))
		{
			$error[] = new XenForo_Phrase('siropu_ads_manager_ad_url_not_valid');
		}

		if (($titleLength = strlen($title)) && $maxTitleLength && $titleLength > $maxTitleLength && $purchase)
		{
			$error[] = new XenForo_Phrase('siropu_ads_manager_ad_description_max_length',
				array('limit' => $maxTitleLength));
		}

		if ($error)
		{
			if ($type == 'banner')
			{
				$error[] = new XenForo_Phrase('siropu_ads_manager_ad_error');
			}

			return $error;
		}
	}
	public static function formatPrice($price)
	{
		switch (self::_getOptions()->siropu_ads_manager_currency)
		{
			case 'TWD':
			case 'HUF':
			case 'JPY':
			case 'IRR':
				return number_format($price, 0, '.', '');
				break;
			default:
				return number_format($price, 2, '.', '');
				break;
		}
	}
	public static function getStatsDate()
	{
		$date = new dateTime('now', new DateTimeZone(XenForo_Application::get('options')->guestTimeZone));
		return strtotime($date->format('Y-m-d H:00'));
	}
	public static function sendEmailNotification($template, $mailParams, $userId)
	{
		if ($user = XenForo_Model::create('XenForo_Model_User')->getUserById($userId))
		{
			$mail = XenForo_Mail::create($template, $mailParams, $user['language_id']);
			$mail->send($user['email'], $user['username']);
		}
	}
	public static function userHasPermission($permission)
	{
		$visitor = XenForo_Visitor::getInstance();

		switch ($permission)
		{
			case 'create':
				if (self::_getOptions()->siropu_ads_manager_user_enabled
					&& (XenForo_Permission::hasPermission($visitor->getPermissions(), 'siropu_ads_manager', 'create'))
						|| self::_getOptions()->siropu_ads_manager_guest_mode['enabled'] && !$visitor->user_id)
				{
					return true;
				}
				break;
			default:
				return XenForo_Permission::hasPermission($visitor->getPermissions(), 'siropu_ads_manager', $permission);
				break;
		}
	}
	public static function checkKeywordUniqueness($keywordList, $adId)
	{
		$notUnique = array();
		foreach (XenForo_Model::create('Siropu_AdsManager_Model_Ads')->getKeywordAds($adId) as $row)
		{
			$lastActive = $row['date_last_active'];

			if (!$lastActive || ($lastActive && $lastActive <= strtotime('- 7 Days')))
			{
				foreach (explode("\n", $keywordList) as $keyword)
				{
					if (in_array(strtolower(trim($keyword)), explode("\n", strtolower(trim($row['items'])))))
					{
						$notUnique[] = $keyword;
					}
				}
			}
		}

		$list = implode(', ', $notUnique);

		if (count($notUnique) > 1)
		{
			return new XenForo_Phrase('siropu_ads_manager_ad_keywords_uniqueness_warning', array('keywords' => $list));
		}
		else if (count($notUnique) == 1)
		{
			return new XenForo_Phrase('siropu_ads_manager_ad_keyword_uniqueness_warning', array('keyword' => $list));
		}
	}
	public static function calculateAdTimeActionLeft($data)
	{
		$left = array();

		if ($data['count_views'])
		{
			$left['viewsLeft'] = $data['view_limit'] - $data['view_count'];
		}

		if ($data['click_count'])
		{
			$left['clicksLeft'] = $data['click_limit'] - $data['click_count'];
		}

		if ($dateEnd = $data['date_end'])
		{
			$timeLeft = $dateEnd - time();

			if ($timeLeft <= 0)
			{
				$left['timeLeft'] = 0;
			}
			else if ($timeLeft > 259200)
			{
				$left['timeLeft'] = round($timeLeft / 86400) . ' ' . new XenForo_Phrase('days');
			}
			else
			{
				$left['timeLeft'] = round($timeLeft / 3600) . ' ' . new XenForo_Phrase('hours');
			}
		}
		return $left;
	}
	public static function getUserAdAction()
	{
		return (array) @unserialize(XenForo_Helper_Cookie::getCookie('adAction'));
	}
	public static function saveUserAdAction($adId, $action)
	{
		$data = self::removeUserAdExpiredAction();

		$data[$adId][$action] = 1;
		$data[$adId]["{$action}t"] = time();

		XenForo_Helper_Cookie::setCookie('adAction', serialize($data), 86400 * 365);
	}
	public static function userAdActionIsValid($adId, $action)
	{
		$data = self::getUserAdAction();

		if ($action == 'v')
		{
			$countCondition = self::_getOptions()->siropu_ads_manager_ad_view_count_condition;
		}
		else
		{
			$countCondition = self::_getOptions()->siropu_ads_manager_ad_click_count_condition;
		}

		if ((!$data || !isset($data[$adId]) || !isset($data[$adId][$action]) || !$countCondition)
			|| (isset($data[$adId][$action]) && $data[$adId]["{$action}t"] <= strtotime("-{$countCondition} hours")))
		{
			return true;
		}
	}
	public static function removeUserAdExpiredAction()
	{
		$data = self::getUserAdAction();

		foreach ($data as $id => $action)
		{
			$vTime = isset($action['vt']) ? $action['vt'] : 0;
			$cTime = isset($action['ct']) ? $action['ct'] : 0;

			if ($vTime && $vTime <= strtotime('-24 hours') && !($cTime && $cTime >= strtotime('-24 hours')))
			{
				unset($data[$id]);
			}
		}

		return $data;
	}
	public static function applyPromoCode($transaction, $promoCode)
	{
		$amount   = $transaction['cost_amount'];
		$discount = $promoCode['value'];

		if ($promoCode['type'] == 'percent')
		{
			$amount = $amount - ($amount * $discount / 100);
		}
		else
		{
			$amount = $amount - $discount;
		}

		return $amount;
	}
	public static function generateClassFromHook($hook)
	{
		return lcfirst(preg_replace('/(^ad|\d)/i', '', implode('', array_map('ucwords', explode('_', $hook)))));
	}
	public static function getUnitAttributes($hook, $type, $data)
	{
		switch ($type)
		{
			case 'banner':
				$class = 'samBannerUnit';
				break;
			case 'code':
				$class = 'samCodeUnit';
				break;
			case 'text':
				$class = 'samTextUnit';
				break;
			case 'link':
				$class = 'samLinkUnit';
				break;
		}

		$size = '';

		if (!empty($data['ads'][0]['style']))
		{
			$style = $data['ads'][0]['style'];
			$size  = self::getAdWidthHeight($data['ads'][0], true);

			switch ($style['align'])
			{
				case 'left':
					$class .= $size ? ' samAlignLeft' : ' samAlignLeftAuto';
					break;
				case 'right';
					$class .= $size ? ' samAlignRight' : ' samAlignRightAuto';
					break;
				case 'center':
					$class .= $size ? ' samAlignCenter' : ' samAlignCenterAuto';
					break;
			}
		}

		$class .= ' ' . self::generateClassFromHook($hook);

		if (count($data['ads']) > 1 && !empty($data['jsRotator']))
		{
			$output = 'class="' . $class . ' SamRotator" data-interval="' . $data['jsRotator'] . '"';
		}
		else
		{
			$output = 'class="' . $class . '"';
		}

		if ($size)
		{
			$output .= ' style="width: ' . $size['width'] . 'px; height: ' . $size['height'] . 'px;"';
		}

		return $output . (!empty($data['stats']) ? ' data-pos="' . $hook . '"' : '');
	}
	public static function getAdAttributes($ad)
	{
		$after_d = $ad['display_after'];
		$after_h = $ad['hide_after'];

		$output = 'class="SamLink' . ($after_d || $after_h ? ' SamTimer' : '') . '"';

		if ($ad['type'] == 'keyword')
		{
			$output = 'class="SamLink samKeyword' . ($ad['description'] ? ' Tooltip" title="' . $ad['description'] : '') . '"';

			if ($ad['daily_stats'] || $ad['click_stats'])
			{
				$output .= ' data-pos="message_content"';
			}
		}
		if (!in_array($ad['type'], array('code', 'banner')))
		{
			if ($ad['target_blank'])
			{
				$output .= ' target="_blank"';
			}
			if ($ad['nofollow'])
			{
				$output .= ' rel="nofollow"';
			}
		}
		if ($ad['count_views'] || $ad['count_clicks'])
		{
			$output .= ' data-id="' . $ad['ad_id'] . '"';
		}
		if ($ad['count_views'])
		{
			$output .= ' data-cv="1"';
		}
		if ($ad['count_clicks'])
		{
			$output .= ' data-cc="1"';
		}
		if ($ad['ga_stats'])
		{
			$output .= ' data-ga="1"';
		}
		if ($after_d)
		{
			$output .= ' data-display="' . $after_d . '"';
		}
		if ($after_h)
		{
			$output .= ' data-hide="' . $after_h . '"';
		}
		if ($ad['count_views'] && !self::userAdActionIsValid($ad['ad_id'], 'v'))
		{
			$output .= ' data-viewed="1"';
		}
		if ($ad['count_clicks'] && !self::userAdActionIsValid($ad['ad_id'], 'c'))
		{
			$output .= ' data-clicked="1"';
		}
		if ($after_d)
		{
			$output .= ' style="display: none;"';
		}

		return $output;
	}
	public static function getTopPerformingAds($resultArray)
	{
		foreach ($resultArray as $key => $val)
		{
			if ($val['status'] == 'Active' && $val['count_clicks'] && $val['count_views'] && $val['ctr'] != '0.00')
			{
				$resultArray[$key]['ads_order'] = 'ctrDesc';
			}
			else
			{
				unset($resultArray[$key]);
			}
		}

		usort($resultArray, 'self::sortAdsByPackageCriteria');

		if (count($resultArray) > 5)
		{
			$resultArray = array_slice($resultArray, 0, 5);
		}

		return $resultArray;
	}
	public static function prepareClicksStatsTooltipInfo($resultArray, $ip = true)
	{
		if ($resultArray)
		{
			foreach ($resultArray as $key => $val)
			{
				$tooltip = array();

				if ($val['visitor_username'] != 'Guest')
				{
					if ($gender = $val['visitor_gender'])
					{
						$genderPhrase = array(
							'male'   => new XenForo_Phrase('male'),
							'female' => new XenForo_Phrase('female')
						);

						$tooltip[] = new XenForo_Phrase('gender') . ': ' . $genderPhrase[$gender];
					}
					if ($age = $val['visitor_age'])
					{
						$tooltip[] = new XenForo_Phrase('age') . ': ' . $age;
					}
				}

				if ($ip)
				{
					$tooltip[] = new XenForo_Phrase('ip') . ': ' . $val['visitor_ip'];
				}

				if ($device = $val['visitor_device'])
				{
					$deviceList = Siropu_AdsManager_Helper_Device::getDeviceListPhrase();

					$tooltip[] = new XenForo_Phrase('siropu_ads_manager_device') . ': ' . @$deviceList[$device];
				}

				$resultArray[$key]['tooltip'] = implode(', ', $tooltip);
			}
		}

		return $resultArray;
	}
	public static function getRobokassaSignature($data)
	{
		$mrhLogin = self::_getOptions()->siropu_ads_manager_robokassa_mrh_login;
		$mrhPass1 = self::_getOptions()->siropu_ads_manager_robokassa_mrh_pass1;

		return md5("{$mrhLogin}:{$data['OutSum']}:{$mrhPass1}:0:Shp_item={$data['Shp_item']}");
	}
	public static function getExtraBannersList($banners)
	{
		$extraBanners = @unserialize($banners);
		$bannerList   = array();

		if ($extraBanners)
		{
			foreach ($extraBanners as $key => $banner)
			{
				$bannerList[$key]['file'] = $banner;

				if (self::isSwf($banner))
				{
					$bannerList[$key]['flash'] = true;
				}
			}
		}

		return $bannerList;
	}
	public static function getPositionName($hook, $name)
	{
		if (preg_match('/[0-9]+$/', $hook, $match))
		{
			$name = preg_replace('/\((.*?)\)/', '', $name);
			$name = preg_replace('/x/', current($match), $name);
		}

		return trim($name);
	}
	public static function refreshAdsForCache()
	{
		XenForo_Application::setSimpleCacheData('adsForCache', '');
	}
	public static function refreshActiveAdsCache()
	{
		XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
	}
	public static function refreshAdPositionsCache()
	{
		XenForo_Application::setSimpleCacheData('adPositionList', '');
	}
	public static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	public static function _getAdsModel()
	{
		return XenForo_Model::create('Siropu_AdsManager_Model_Ads');
	}
	public function getRMInstallStatus($type)
	{
		return $type == 'featured' && XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('XenResource');
	}
}

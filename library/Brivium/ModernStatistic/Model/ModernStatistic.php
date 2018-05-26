<?php
class Brivium_ModernStatistic_Model_ModernStatistic extends XenForo_Model
{
	public function getAllModernStatistics()
	{
		$modernStatistics = $this->fetchAllKeyed('
			SELECT *
			FROM xf_brivium_modern_statistic
		', 'modern_statistic_id');
		return $modernStatistics;
	}

	public function getActiveModernStatistics()
	{
		$modernStatistics = $this->fetchAllKeyed('
			SELECT *
			FROM xf_brivium_modern_statistic
			WHERE active = 1
		', 'modern_statistic_id');
		return $modernStatistics;
	}

	/**
	*	get product type by its id
	* 	@param integer $modernStatisticId
	*	@return array|false statistic info
	*/
	public function getModernStatisticById($modernStatisticId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_modern_statistic
			WHERE modern_statistic_id = ?
		', $modernStatisticId);
	}

	public function getModernStatisticByIds(array $modernStatisticIds, $fetchOptions = array())
	{
		if (!$modernStatisticIds)
		{
			return array();
		}
		$joinOptions = $this->prepareProductFetchOptions($fetchOptions);
		return $this->fetchAllKeyed('
			SELECT modern_statistic.*
			' .$joinOptions['selectFields']. '
			FROM xf_brivium_modern_statistic AS modern_statistic
			' .$joinOptions['joinTables']. '
			WHERE modern_statistic.modern_statistic_id IN (' . $this->_getDb()->quote($modernStatisticIds) . ')
		', 'modern_statistic_id');
	}

	public function getAllModernStatisticsForCache()
	{
		$this->resetLocalCacheData('allModernStatistics');

		$modernStatistics = $this->getActiveModernStatistics();
		return $modernStatistics;
	}

	/**
	 * Rebuilds the full Statistic cache.
	 *
	 * @return array Format: [statistic id] => info, with phrase cache as array
	 */
	public function rebuildModernStatisticCache()
	{
		$this->resetLocalCacheData('allStatistics');

		$modernStatistics = $this->getAllModernStatisticsForCache();
		$positions = array();

		foreach($modernStatistics as &$modernStatistic){
			if(!empty($modernStatistic['position'])){
				$positionMap =	preg_split('/\s+/', trim($modernStatistic['position']));
				foreach($positionMap as $position){
					if(empty($positions[$position])) {
						$positions[$position] = array();
					}
					$positions[$position][] = $modernStatistic['modern_statistic_id'];
				}
				$modernStatistic = $this->prepareModernStatistic($modernStatistic);
			}
		}
		$cache = array(
			'modernStatistics'	=> $modernStatistics,
			'positions'	=> $positions,
		);
		$this->_getDataRegistryModel()->set('brmsModernStatisticCache', $cache);
		return $cache;
	}

	/**
	 * Rebuilds all statistic caches.
	 */
	public function rebuildModernStatisticCaches()
	{
		$this->rebuildModernStatisticCache();
	}

	public function prepareModernStatistic($modernStatistic, $edit = false)
	{
		$modernStatistic['tabData'] = @unserialize($modernStatistic['tab_data']);
		$modernStatistic['styleSettings'] = @unserialize($modernStatistic['style_settings']);
		$modernStatistic['itemLimit'] = @unserialize($modernStatistic['item_limit']);
		$modernStatistic['modernCriteria'] = @unserialize($modernStatistic['modern_criteria']);
		if(!$modernStatistic['tabData'] || !is_array($modernStatistic['tabData'])){
			$modernStatistic['tabData'] = array();
		}

		$tabDataOrder = array();
		$tabData = array();
		foreach($modernStatistic['tabData'] as $key => $tab){
			$tabDataOrder[$key] = !empty($tab['display_order'])?$tab['display_order']:0;
		}
		asort($tabDataOrder);
		foreach($tabDataOrder as $key => $order){
			if(!empty($modernStatistic['tabData'][$key])){
				$tabData[$key] = $modernStatistic['tabData'][$key];
			}
		}
		$modernStatistic['tabData'] = $tabData;
		foreach($modernStatistic['tabData'] as $key => &$tab){
			if(!$edit){
				if(!empty($tab['active']) && (($tab['kind']!='resource' && $tab['type']!='user_most_resources') || $this->checkXenForoResourceAddon())){
				}else{
					unset($modernStatistic['tabData'][$key]);
					continue;
				}
			}
			$tab['defaultTitle'] = $tab['title'];
			if(empty($tab['title']) && !empty($tab['type'])){
				switch($tab['type']){
					case 'thread_latest':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_latest_threads');
						break;
					case 'thread_hotest':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_most_viewed_threads');
						break;
					case 'post_latest':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_latest_replies');
						break;
					case 'most_reply':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_most_replied_threads');
						break;
					case 'sticky_threads':
					case 'promoted_threads':
					case 'my_threads':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_' . $tab['type']);
						break;
					case 'resource_last_update':
						$tab['defaultTitle'] = new XenForo_Phrase('latest_updates');
						break;
					case 'resource_resource_date':
						$tab['defaultTitle'] = new XenForo_Phrase('newest_resources');
						break;
					case 'resource_rating_weighted':
						$tab['defaultTitle'] = new XenForo_Phrase('top_resources');
						break;
					case 'resource_download_count':
						$tab['defaultTitle'] = new XenForo_Phrase('most_downloaded');
						break;
					case 'user_most_messages':
						$tab['defaultTitle'] = new XenForo_Phrase('most_messages');
						break;
					case 'user_most_likes':
						$tab['defaultTitle'] = new XenForo_Phrase('most_likes');
						break;
					case 'user_most_points':
						$tab['defaultTitle'] = new XenForo_Phrase('most_points');
						break;
					case 'user_staff_members':
						$tab['defaultTitle'] = new XenForo_Phrase('staff_members');
						break;
					case 'user_most_resources':
						$tab['defaultTitle'] = new XenForo_Phrase('most_resources');
						break;
					case 'user_latest_members':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_latest_members');
						break;
					case 'user_latest_banned':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_latest_banned_members');
						break;
					case 'user_richest':
						$tab['defaultTitle'] = new XenForo_Phrase('BRC_top_richest');
						break;
					case 'user_poorest':
						$tab['defaultTitle'] = new XenForo_Phrase('BRC_top_poorest');
						break;
					case 'latest_profile_posts':
						$tab['defaultTitle'] = new XenForo_Phrase('new_profile_posts');
						break;
					case 'your_profile_posts':
						$tab['defaultTitle'] = new XenForo_Phrase('BRMS_your_profile_posts');
						break;
				}
				if($tab['defaultTitle']){
					$tab['defaultTitle'] = $tab['defaultTitle']->render();
				}
			}
		}
		if(!$modernStatistic['itemLimit'] || !is_array($modernStatistic['itemLimit'])){
			$modernStatistic['itemLimit'] = array('default'=>15);
		}
		return $modernStatistic;
	}

	public function prepareModernStatistics($modernStatistics, $edit = false)
	{
		foreach ($modernStatistics as &$modernStatistic)
		{
			$modernStatistic = $this->prepareModernStatistic($modernStatistic, $edit);
		}
		return $messages;
	}

	public function getStatisticTabParams($modernStatisticId, $tabId, $userId, $limit = 0, $useCache = true, $cachedStatistic = array())
	{
		$statisticParams = array();
		if(XenForo_Application::isRegistered('brmsModernStatistics')){
			$modernStatistic = XenForo_Application::get('brmsModernStatistics')->get($modernStatisticId);
		}else{
			$modernStatistic = array();
		}

		$viewParams = array(
			'items'		=> array(),
			'useCacheParam'	=> false,
			'cachedStatistic'	=> array(),
			'statisticParams'	=> array(),
			'modernStatistic'	=> $modernStatistic,
		);

		if(!empty($modernStatistic) && !empty($modernStatistic['tabData'][$tabId])){
			$itemLimit = 0;
			if(!$limit){
				$itemLimit = !empty($modernStatistic['itemLimit']['default'])?$modernStatistic['itemLimit']['default']:15;
			}else{
				$itemLimit = $limit;
			}
			$tabParam = $modernStatistic['tabData'][$tabId];
			$cachedStatistic = $cachedStatistic?$cachedStatistic:$this->getModernCacheDataForUserId($modernStatisticId, $userId);
			$viewParams['cachedStatistic'] = $cachedStatistic;
			if($useCache
				&& !empty($modernStatistic['enable_cache'])
				&& !empty($modernStatistic['cache_time'])
				&& !empty($cachedStatistic['last_update'])

			){
				$cacheTime = max(1, $modernStatistic['cache_time']);
				$lastUpdate =  XenForo_Application::$time - $cacheTime*60;
				if($cachedStatistic['last_update'] >= $lastUpdate){
					if(!empty($cachedStatistic['tabCacheHtmls'])){
						if(!empty($cachedStatistic['tabCacheHtmls'][$tabId])){
							$statisticParams['renderedHtml'] = $cachedStatistic['tabCacheHtmls'][$tabId];
							$viewParams['useCacheParam'] = true;
						}
					}else if(!empty($cachedStatistic['tabCacheParams'])){
						if(!empty($cachedStatistic['tabCacheParams'][$tabId])){
							$statisticParams = $cachedStatistic['tabCacheParams'][$tabId];
							$viewParams['useCacheParam'] = true;
						}
					}
				}
			}

			if(empty($viewParams['useCacheParam'])){
				$tabParam['kind'] = !empty($tabParam['kind'])?$tabParam['kind']:'thread';
				switch($tabParam['kind']){
					case 'resource':
						$statisticParams = $this->getResourceStatistics($tabParam, $itemLimit, $modernStatistic);
						break;
					case 'user':
						$statisticParams = $this->getUserStatistics($tabParam, $itemLimit, $modernStatistic);
						break;
					case 'profile_post':
						$statisticParams = $this->getProfilePostStatistics($tabParam, $itemLimit, $modernStatistic);
						break;
					case 'thread':
					default:
						$statisticParams = $this->getThreadStatistics($tabParam, $itemLimit, $modernStatistic);
						break;
				}
			}
			if($userId && $limit && (empty($cachedStatistic['item_limit']) || $cachedStatistic['item_limit'] != $limit)){
				$data = array(
					'item_limit'	=>	$limit
				);
				$this->saveCacheForStatistic($modernStatisticId, $userId, $data);
				if(is_array($viewParams['cachedStatistic'])){
					$viewParams['cachedStatistic']['item_limit'] = $limit;
				}else{
					$viewParams['cachedStatistic'] = $data;
				}
			}
			$viewParams['tabParams'] = $statisticParams;
			$viewParams = array_merge($statisticParams, $viewParams);
		}
		return $viewParams;
	}

	public function getModernCacheDataForUserId($modernStatisticId, $userId)
	{
		$cache = $this->_getDb()->fetchRow('
			SELECT *
				FROM xf_brivium_modern_cache
			WHERE modern_statistic_id = ? AND user_id = ?
			ORDER BY last_update DESC
		', array($modernStatisticId, $userId));

		if(!empty($cache['item_limit'])){
			$cache['userSetting'] = @unserialize($cache['item_limit']);
		}
		if(!empty($cache['cache_params'])){
			$cache['hintData'] = @unserialize($cache['cache_params']);
		}
		if(!empty($cache['tab_cache_htmls'])){
			$cache['tabCacheHtmls'] = @unserialize($cache['tab_cache_htmls']);
		}
		if(!empty($cache['tab_cache_params'])){
			$cache['tabCacheParams'] = @unserialize($cache['tab_cache_params']);
		}
		return $cache;
	}

	public function getProfilePostStatistics($tab, $limit, $modernStatistic)
	{
		$profilePostModel = $this->_getProfilePostModel();

		$fetchOptions = array(
			'limit' => $limit,
			'join' => XenForo_Model_ProfilePost::FETCH_USER_POSTER |
					XenForo_Model_ProfilePost::FETCH_USER_RECEIVER |
					XenForo_Model_ProfilePost::FETCH_USER_RECEIVER_PRIVACY,
			'likeUserId' => XenForo_Visitor::getUserId(),
		);
		$viewParams = array(
			'template' => '',
			'items' => array(),
			'limit' => $limit,
		);
		$profilePosts = array();
		$template = '';
		$criteria = array(
		);
		if(empty($tab['type']) && !empty($tab['profile_post_type'])) {
			$tab['type'] = $tab['profile_post_type'];
		}
		switch ($tab['type'])
		{
			case 'latest_profile_posts':
				$profilePosts = $profilePostModel->getLatestProfilePosts($criteria, $fetchOptions);
				$template	=	'BRMS_latest_profile_posts';
				break;
			case 'your_profile_posts':
				$user = XenForo_Visitor::getInstance()->toArray();
				if($user['user_id']){
					$profilePosts = $profilePostModel->getProfilePostsForUserId($user['user_id'], $criteria, $fetchOptions);
					$template	=	'BRMS_your_profile_posts';
				}
				break;
		}
		if(!$template){
			return $viewParams;
		}
		if($profilePosts){
			foreach($profilePosts as $key => $profilePost){
				$receivingUser = $profilePostModel->getProfileUserFromProfilePost($profilePost);
				if (!$profilePostModel->canViewProfilePostAndContainer($profilePost, $receivingUser))
				{
					unset($profilePosts[$key]);
					continue;
				}
				$profilePost = $profilePostModel->prepareProfilePost($profilePost, $receivingUser);
				$formatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Text');
				$parser = XenForo_BbCode_Parser::create($formatter);
				$profilePost['messageParsed'] = $parser->render($profilePost['message']);
				$profilePosts[$key] = $profilePost;
			}
		}

		$viewParams['template'] = $template;
		$viewParams['items'] = $profilePosts;
		$viewParams['limit'] = $limit;
		return $viewParams;
	}

	public function getResourceStatistics($tab, $limit, $modernStatistic)
	{
		if($this->checkXenForoResourceAddon()){
			$resourceModel = $this->_getResourceModel();
			$categoryModel = $this->_getCategoryModel();
			$fetchOptions = array(
				'join' => XenResource_Model_Resource::FETCH_VERSION
				| XenResource_Model_Resource::FETCH_USER
				| XenResource_Model_Resource::FETCH_CATEGORY
				| XenResource_Model_Resource::FETCH_FEATURED,
				'limit' => $limit,
				'order' => 'last_update',
				'direction' => 'desc',
			);
			$template = '';
			$criteria = array();
			if(empty($tab['type']) && !empty($tab['resource_type'])) {
				$tab['type'] = $tab['resource_type'];
			}
			switch ($tab['type'])
			{
				case 'resource_last_update':
					$fetchOptions['order'] = 'last_update';
					$template	=	'BRMS_resource_last_update';
					break;
				case 'resource_resource_date':
					$fetchOptions['order'] = 'resource_date';
					$template	=	'BRMS_resource_resource_date';
					break;
				case 'resource_rating_weighted':
					$fetchOptions['order'] = 'rating_weighted';
					$template	=	'BRMS_resource_rating_weighted';
					break;
				case 'resource_download_count':
					$fetchOptions['order'] = 'download_count';
					$template	=	'BRMS_resource_download_count';
					break;
			}

			$criteria += $categoryModel->getPermissionBasedFetchConditions();

			$criteria['resource_state'] = 'visible';
			$viewableCategories = $this->_getCategoryModel()->getViewableCategories();
			$categoryIds = array_keys($viewableCategories);
			if(!empty($tab['categories']) && $tab['categories'] != array(0=>0)){
				$categoryIds = array_intersect($categoryIds, $tab['categories']);
			}
			$criteria['resource_category_id'] = $categoryIds;

			if(!empty($tab['resource_prefix_id']) && $tab['resource_prefix_id'] != array(0)){
				$criteria['prefix_id'] = $tab['resource_prefix_id'];
			}
			if(!empty($tab['resource_state'])){
				$criteria['resource_state'] = $tab['resource_state'];
			}else{
				$criteria['resource_state'] = 'visible';
			}

			$resources = $resourceModel->getResources($criteria, $fetchOptions);
			$resources = $resourceModel->filterUnviewableResources($resources);
			$resources = $resourceModel->prepareResources($resources);
			$viewParams = array(
				'template' => $template,
				'items' => $resources,
				'limit' => $limit,
			);
		}else{
			$viewParams = array(
				'template' => '',
				'items' => array(),
				'limit' => $limit,
			);
		}

		return $viewParams;
	}

	public function getUserStatistics($tab, $limit, $modernStatistic)
	{
		$userModel = $this->_getUserModel();
		$fetchOptions = array(
			'join' => XenForo_Model_User::FETCH_USER_FULL,
			'limit' => $limit,
			'order' => 'username',
			'direction' => 'desc',
		);
		$viewParams = array(
			'template' => '',
			'items' => array(),
			'limit' => $limit,
		);
		$template = '';
		$criteria = array(
			'is_banned' => 0
		);
		if(empty($tab['type']) && !empty($tab['resource_type'])) {
			$tab['type'] = $tab['resource_type'];
		}
		if(empty($tab['type']) && !empty($tab['user_type'])) {
			$tab['type'] = $tab['user_type'];
		}
		switch ($tab['type'])
		{
			case 'user_most_messages':
				$fetchOptions['order'] = 'message_count';
				$template	=	'BRMS_user_most_messages';
				break;
			case 'user_most_likes':
				$fetchOptions['order'] = 'like_count';
				$template	=	'BRMS_user_most_likes';
				break;
			case 'user_most_points':
				$fetchOptions['order'] = 'trophy_points';
				$template	=	'BRMS_user_most_points';
				break;
			case 'user_staff_members':
				$criteria['is_staff'] = true;
				$fetchOptions['order'] = 'username';
				$fetchOptions['direction'] = 'asc';
				$template	=	'BRMS_user_staff_members';
				break;
			case 'user_most_resources':
				$fetchOptions['order'] = 'resource_count';
				$template	=	'BRMS_user_most_resources';
				break;
			case 'user_latest_members':
				$fetchOptions['order'] = 'register_date';
				$template	=	'BRMS_user_latest_members';
				break;
			case 'user_latest_banned':
				$fetchOptions['order'] = 'ban_date';
				$fetchOptions['BRMS_fetch_banned_user'] = true;
				$criteria['is_banned'] = true;
				$template	=	'BRMS_user_latest_banned';
				break;
			case 'user_richest':
			case 'user_poorest':
				$creditVersion = $this->checkBriviumCreditsAddon();
				$column = '';
				if($creditVersion >= 1000000 && !empty($tab['currency_id'])){
					$currency = XenForo_Application::get('brcCurrencies')->$tab['currency_id'];
					if(!empty($currency['column'])){
						$column = $currency['column'];
					}
				}else{
					$column = 'credits';
				}
				if($column){
					$fetchOptions['order'] = $column;
					$viewParams['currency'] = $currency;
					$viewParams['currencyId'] = $tab['currency_id'];
					$viewParams['column'] = $currency['column'];
					if($tab['type']=='user_poorest'){
						$fetchOptions['direction'] = 'asc';
					}
					$template	=	'BRMS_user_richest';
				}
				break;
		}
		if(!$template){
			return $viewParams;
		}

		$userGroupsIds = array();
		if(!empty($tab['user_groups']) && $tab['user_groups'] != array(0=>0)){
			$userGroupsIds = $tab['user_groups'];
		}
		$criteria['user_group_id'] = $userGroupsIds;

		if(!empty($tab['user_state'])){
			$criteria['user_state'] = $tab['user_state'];
		}else{
			$criteria['user_state'] = 'valid';
		}
		if(!empty($tab['gender']) && is_array($tab['gender']) && count($tab['gender']) < 3){
			$criteria['gender'] = $tab['gender'];
		}
		$users = $userModel->getUsers($criteria, $fetchOptions);
		foreach($users as &$user){
			$user = $userModel->prepareUser($user);
		}
		$viewParams['template'] = $template;
		$viewParams['items'] = $users;
		$viewParams['limit'] = $limit;
		return $viewParams;
	}

	public function getThreadStatistics($tab, $limit, $modernStatistic)
	{
		$conditions = array();
		$fetchOptions = array(
			'limit' => $limit,
			'order' => 'post_date',
			'direction' => 'desc',
			'join' => XenForo_Model_Thread::FETCH_FORUM,
			'readUserId' => XenForo_Visitor::getUserId(),
			'includeForumReadDate' => true,
			'BRMS_fetch_user' => true,
		);
		if(!$modernStatistic['usename_marke_up']){
			$fetchOptions['BRMS_fetch_user'] = false;
		}

		$now = XenForo_Application::$time;
		$template = '';
		switch ($tab['type'])
		{
			case 'thread_latest':
				$fetchOptions['order'] = 'post_date';
				$template	=	'BRMS_thread_latest';
				break;
			case 'thread_hotest':
				$fetchOptions['order'] = 'view_count';
				$template	=	'BRMS_thread_most_viewed';
				if (!empty($tab['cut_off']) && $tab['cut_off'] > 0) {
					$conditions['BRMS_post_date'] = array('>', $now - $tab['cut_off'] * 86400);
				}else if($modernStatistic['thread_cutoff'] > 0){
					$conditions['BRMS_post_date'] = array('>', $now - $modernStatistic['thread_cutoff'] * 86400);
				}
				break;
			case 'post_latest':
				$fetchOptions['order'] = 'last_post_date';
				if($modernStatistic['usename_marke_up']){
					$fetchOptions['BRMS_fetch_user'] = false;
					$fetchOptions['BRMS_join_last_post'] = true;
				}
				$template	=	'BRMS_thread_post_latest';
				break;
			case 'most_reply':
				if (!empty($tab['cut_off']) && $tab['cut_off'] > 0) {
					$conditions['BRMS_post_date'] = array('>', $now - $tab['cut_off'] * 86400);
				}else if($modernStatistic['thread_cutoff'] > 0){
					$conditions['BRMS_post_date'] = array('>', $now - $modernStatistic['thread_cutoff'] * 86400);
				}
				$fetchOptions['order'] = 'reply_count';
				$template	=	'BRMS_thread_most_reply';
				break;
			case 'my_threads':
				if(!empty($tab['order_type'])){
					$fetchOptions['order'] = $tab['order_type'];
				}
				if(!empty($tab['order_direction'])){
					$fetchOptions['order'] = $tab['order_direction'];
				}
				$conditions['user_id'] = XenForo_Visitor::getUserId();
				$template	=	'BRMS_thread_my_threads';
				break;
			case 'sticky_threads':
				if(!empty($tab['order_type'])){
					$fetchOptions['order'] = $tab['order_type'];
				}
				if(!empty($tab['order_direction'])){
					$fetchOptions['order'] = $tab['order_direction'];
				}
				$conditions['sticky'] = 1;
				$template	=	'BRMS_thread_my_threads';
				break;
			case 'promoted_threads':
				$fetchOptions['order'] = 'brms_promote_date';
				$conditions['brms_promote'] = 1;
				$template	=	'BRMS_thread_my_threads';
				break;
		}
		$GLOBALS['BRMS_ControllerPublic_ModernStatistic'] = true;

		/* get thread by viewable forum
		$viewableNodes = $this->_getViewableNode();
		$nodeIds = array_keys($viewableNodes);
		if(!empty($tab['forums']) && $tab['forums'] != array(0=>0)){
			$nodeIds = array_intersect($nodeIds, $tab['forums']);
		}
		$conditions['node_id'] = $nodeIds;
		 */
		/* get thread by unviewable forum */

		$unviewableNodes = $this->_getUnviewableNode();
		$unviewableNodeIds = array_keys($unviewableNodes);
		$viewableNodeIds = array();

		if(!empty($tab['forums']) && $tab['forums'] != array(0=>0)){
			$viewableNodeIds = array_diff($tab['forums'], $unviewableNodeIds);
			if($viewableNodeIds){
			}else{
				$viewableNodeIds = array(0=>0);
			}
		}else{
			$conditions['BRMS_not_node_id'] = $unviewableNodeIds;
		}

		$conditions['node_id'] = $viewableNodeIds;

		if(!empty($tab['discussion_open']) && count($tab['discussion_open']) < 2){
			if(!empty($tab['discussion_open']['unlocked'])){
				$conditions['discussion_open'] = 1;
			}else if(!empty($tab['discussion_open']['locked'])){
				$conditions['discussion_open'] = 0;
			}
		}
		if(!empty($tab['prefix_id']) && $tab['prefix_id'] != array(0)){
			$conditions['prefix_id'] = $tab['prefix_id'];
		}
		if(!empty($tab['discussion_state'])){
			$conditions['discussion_state'] = $tab['discussion_state'];
		}else{
			$conditions['discussion_state'] = 'visible';
		}

		$conditions['not_discussion_type'] = 'redirect';
		$threadModel = $this->_getThreadModel();
		$threads = $threadModel->getThreads($conditions, $fetchOptions);
		$threads = $threadModel->modernStatisticPrepareThreads($threads, $modernStatistic);
		unset($GLOBALS['BRMS_ControllerPublic_ModernStatistic']);
		$viewParams = array(
			'template' => $template,
			'items' => $threads,
			'limit' => $limit,
		);
		return $viewParams;
	}

	protected static $_viewAbleNode = null;

	protected function _getViewableNode()
	{
		if (!isset(self::$_viewAbleNode)) {
			self::$_viewAbleNode = $this->_getNodeModel()->getViewableNodeList();
		}
		return self::$_viewAbleNode;
	}

	protected static $_unviewableNode = null;

	protected function _getUnviewableNode()
	{
		if (!isset(self::$_unviewableNode)) {
			self::$_unviewableNode = $this->_getNodeModel()->getUnviewableNodeList();
		}
		return self::$_unviewableNode;
	}

	protected static $_resourceAddOn = null;

	public function checkXenForoResourceAddon()
	{
		if(self::$_resourceAddOn != null){
			return self::$_resourceAddOn;
		}
		if (XenForo_Application::isRegistered('addOns'))
		{
			$addOns = XenForo_Application::get('addOns');
			if (!empty($addOns['XenResource']))
			{
				return true;
			}
		}else{
			if ($this->getModelFromCache('XenForo_Model_AddOn')->getAddOnVersion('XenResource')) {
				return true;
			}
		}
		return false;
	}

	protected static $_creditAddOn = null;

	public function checkBriviumCreditsAddon()
	{
		if(self::$_creditAddOn != null){
			return self::$_creditAddOn;
		}
		if (XenForo_Application::isRegistered('addOns'))
		{
			$addOns = XenForo_Application::get('addOns');
			if (!empty($addOns['Brivium_Credits']))
			{
				return $addOns['Brivium_Credits'];
			}
		}else{
			$creditAddOn = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnVersion('Brivium_Credits');
			if (!empty($creditAddOn['version_id'])) {
				return $creditAddOn['version_id'];
			}
		}
		return false;
	}

	public function getStatisticsContentFromStatisticIds($statisticIds)
	{

		$content = '';
		$modernStatistics = $this->getModernStatisticByIds($statisticIds);
		if($modernStatistics){
			foreach($modernStatistics as $modernStatistic){
				$content .= $this->getStatisticContent($modernStatistic);
			}
		}
		return $content;
	}

	public function getModernStatisticForHook($hookName, $loadedTemplates, $templateParams, XenForo_Template_Abstract $template)
	{
		$renderedContents = '';
		$positions = XenForo_Application::get('brmsPositions');
		if(!empty($positions) && is_array($positions) && array_key_exists($hookName, $positions)){
			$renderedContents = $this->renderModernStatistics($positions[$hookName], $loadedTemplates, $templateParams, $template);
		}
		return $renderedContents;
	}

	public function validateStatisticCriteria($statisticCriteria, $loadedTemplates, $templateParams)
	{
		if(!$statisticCriteria){
			return true;
		}
		if(!empty($statisticCriteria['template_name'])){
			if(!$loadedTemplates || !is_array($loadedTemplates)){
				return false;
			}
			$templateNames = preg_split('/\s+/', trim($statisticCriteria['template_name']));
			if($templateNames && !array_intersect($templateNames, $loadedTemplates)){
				return false;
			}
		}
		if(!empty($statisticCriteria['user_group_ids']) && is_array($statisticCriteria['user_group_ids']) && !$this->checkExcludeUserGroups($statisticCriteria['user_group_ids'])){
			return false;
		}
		if(!empty($statisticCriteria['node_ids']) && $statisticCriteria['node_ids']!=array(0=>'') && !empty($templateParams)){
			$nodeId = 0;
			if(!empty($templateParams['forum']['node_id'])){
				$nodeId = $templateParams['forum']['node_id'];
			}else if(!empty($templateParams['category']['node_id'])){
				$nodeId = $templateParams['category']['node_id'];
			}else if(!empty($templateParams['page']['node_id'])){
				$nodeId = $templateParams['page']['node_id'];
			}
			if(!empty($GLOBALS['BRMS_category']) && !empty($GLOBALS['BRMS_category']['node_id'])){
				$nodeId = $GLOBALS['BRMS_category']['node_id'];
			}
			if($nodeId && !in_array($nodeId, $statisticCriteria['node_ids'])){
				return false;
			}
		}

		if(!empty($statisticCriteria['language_ids']) && is_array($statisticCriteria['language_ids'])){
			$viewingUser = null;
			$this->standardizeViewingUserReference($viewingUser);
			if(empty($viewingUser['language_id'])){
				$viewingUser['language_id'] = XenForo_Application::get('options')->defaultLanguageId;
			}
			if(!in_array($viewingUser['language_id'], $statisticCriteria['language_ids'])){
				return false;
			}
		}
		return true;
	}

	public function renderModernStatistics($modernStatisticIds, $loadedTemplates, $templateParams, XenForo_Template_Abstract $template)
	{
		$renderedContents = '';
		$statisticObj = XenForo_Application::get('brmsModernStatistics');
		$request = new Zend_Controller_Request_Http();
		$visitor = XenForo_Visitor::getInstance()->toArray();
		$userId = $visitor['user_id'];
		$visitorPerferences = !empty($visitor['brms_statistic_perferences'])?@unserialize($visitor['brms_statistic_perferences']):array();

		foreach($modernStatisticIds as $modernStatisticId){
			$modernStatistic = $statisticObj->get($modernStatisticId);
			if(!empty($modernStatistic['active'])){
				if(!empty($modernStatistic['allow_user_setting']) && !empty($visitorPerferences[$modernStatisticId])){
					continue;
				}
				if(!empty($modernStatistic['modernCriteria']) && !$this->validateStatisticCriteria($modernStatistic['modernCriteria'], $loadedTemplates, $templateParams)){
					$renderedContents .= '';
					continue;
				}
				$rendered = false;
				$cachedStatistic = $this->getModernCacheDataForUserId($modernStatisticId, $userId);

				if(!empty($modernStatistic['enable_cache']) && !empty($modernStatistic['cache_time']) && $cachedStatistic){
					$cacheTime = max(1, $modernStatistic['cache_time']);
					$lastUpdate =  XenForo_Application::$time - $cacheTime*60;
					if(!empty($cachedStatistic['last_update']) && $cachedStatistic['last_update'] >= $lastUpdate && !empty($cachedStatistic['cache_html'])){
						if(isset($templateParams['visitorStyle']['style_id'])){
							$styleId = $templateParams['visitorStyle']['style_id'];
							if(!empty($modernStatistic['styleSettings']) && !empty($modernStatistic['styleSettings'][$styleId])){
								if($modernStatistic['styleSettings'][$styleId] =='dark'){
									if(!strpos($cachedStatistic['cache_html'], 'BRMSContainerDark')){
										$cachedStatistic['cache_html'] = str_replace('BRMSContainer', 'BRMSContainer BRMSContainerDark', $cachedStatistic['cache_html']);
									}
								}else{
									$cachedStatistic['cache_html'] = str_replace('BRMSContainerDark', '', $cachedStatistic['cache_html']);
								}
							}
						}
						$renderedContents .= $cachedStatistic['cache_html'];
						$rendered = true;
					}
				}
				if(!$rendered){
					$newTemplate = $template->create('BRMS_ModernStatistic', $template->getParams());

					$tabCacheHtmls = array();
					$tabCacheParams = array();

					if(!empty($modernStatistic['load_fisrt_tab']) && !empty($modernStatistic['tabData'])){
						$tabId = -1;
						foreach($modernStatistic['tabData'] as $key => $tab){
							if(($tab['type']!='my_threads' && $tab['type']!='your_profile_posts')|| !empty($userId)){
								$tabId = $key;
								break;
							}
						}
						if($tabId!=-1){
							$limit = 0;
							if(!empty($modernStatistic['itemLimit']['enabled'])){
								if(!empty($cachedStatistic['item_limit'])){
									$limit = $cachedStatistic['item_limit'];
								}else{
									$limit = $request->getCookie('brmsNumberEntry' . $modernStatisticId);
								}
							}
							$firstTabParams = $this->getStatisticTabParams($modernStatisticId, $tabId, $userId, $limit, false, $cachedStatistic);
							if(!empty($firstTabParams['tabParams'])){
								$firstTabTemplate = $template->create($firstTabParams['template'], $template->getParams());
								$firstTabTemplate->setParams($firstTabParams['tabParams']);
								$firstTabTemplate->setParam('modernStatistic', $modernStatistic);
								$firstTabHtml = $firstTabTemplate->render();
								$tabCacheHtmls[$tabId] = $firstTabHtml;
								$tabCacheParams[$tabId] = $firstTabParams['tabParams'];
								$newTemplate->setParam('firstTabHtml', $firstTabHtml);
							}
						}
					}
					$templateParams = $template->getParams();
					if(!empty($modernStatistic['style_display']) && $modernStatistic['style_display']=='dark'){
						$modernStatistic['displayStyle'] = 'BRMSContainerDark';
					}
					if(isset($templateParams['visitorStyle']['style_id'])){
						$styleId = $templateParams['visitorStyle']['style_id'];
						if(!empty($modernStatistic['styleSettings']) && !empty($modernStatistic['styleSettings'][$styleId])){
							if($modernStatistic['styleSettings'][$styleId] =='dark'){
								$modernStatistic['displayStyle'] = 'BRMSContainerDark';
							}else{
								$modernStatistic['displayStyle'] = '';
							}
						}
					}

					$newTemplate->setParam('modernStatistic', $modernStatistic);
					$newTemplate->setParam('cachedStatistic', $cachedStatistic);

					$modernHtml = $newTemplate->render();
					if(!empty($modernStatistic['enable_cache'])){
						$saveData = array(
							'cache_html' => $modernHtml,
							'cache_params' => $modernStatistic,
							'tab_cache_htmls' => $tabCacheHtmls,
							'tab_cache_params' => $tabCacheParams
						);
						$this->saveCacheForStatistic($modernStatisticId, $userId, $saveData);
					}
					$renderedContents .= $modernHtml;
				}
			}
		}
		return $renderedContents;
	}

	public function saveCacheForStatistic($modernStatisticId, $userId, $data)
	{
		$db = $this->_getDb();
		if(!$this->getModernCacheDataForUserId($modernStatisticId, $userId)){
			$insertData = array(
				'modern_statistic_id' 	=> $modernStatisticId,
				'user_id' 	=> $userId,
				'cache_html' 	=> !empty($data['cache_html'])?$data['cache_html']:'',
				'cache_params' 	=> !empty($data['cache_params'])?serialize($data['cache_params']):'',
				'tab_cache_htmls' 	=> !empty($data['tab_cache_htmls'])?serialize($data['tab_cache_htmls']):'',
				'tab_cache_params' 	=> !empty($data['tab_cache_params'])?serialize($data['tab_cache_params']):'',
				'last_update' 	=> XenForo_Application::$time,
			);
			$addOns = XenForo_Application::get('addOns');
			if (!empty($addOns['Brivium_ModernStatistics']) && $addOns['Brivium_ModernStatistics'] >= 2020300)
			{
				$insertData['item_limit'] = !empty($data['item_limit'])?$data['item_limit']:0;
			}
			$db = $this->_getDb();
			$db->insert('xf_brivium_modern_cache', $insertData);
		}else{
			$this->updateCacheSettingForStatistic($modernStatisticId, $userId, $data);
		}
	}

	public function updateCacheSettingForStatistic($modernStatisticId, $userId, $data)
	{
		$db = $this->_getDb();
		$updateData = array();
		if(isset($data['last_update'])){
			$updateData['last_update'] = $data['last_update']?$data['last_update']:XenForo_Application::$time;
		}
		$addOns = XenForo_Application::get('addOns');
		if (!empty($addOns['Brivium_ModernStatistics']) && $addOns['Brivium_ModernStatistics'] >= 2020300)
		{
			if(isset($data['item_limit'])){
				$updateData['item_limit'] = $data['item_limit']?$data['item_limit']:0;
			}
		}
		if($updateData){
			$db->update(
				'xf_brivium_modern_cache',
				$updateData,
				'`modern_statistic_id` = ' . $db->quote($modernStatisticId) . ' AND `user_id` = ' . $db->quote($userId)
			);
		}
	}

	public function canChangePreference(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_ModernStatistics', 'BRMS_allowPreference');
	}

	protected static $_userGroups = null;

	public function checkExcludeUserGroups($excludeGroups = array())
	{
		/* if (XenForo_Application::isRegistered('addOns'))
		{
			$addOns = XenForo_Application::get('addOns');
			if (!empty($addOns['Brivium_ModernStatistics']) && $addOns['Brivium_ModernStatistics'] < 1080000)
			{
				//throw new XenForo_Exception(new XenForo_Phrase('board_currently_being_upgraded'));
				return true;
			}
		} */
		if($excludeGroups){
			if(self::$_userGroups === null){
				$visitor = XenForo_Visitor::getInstance();
				$userGroups = $visitor['user_group_id'];
				if (!empty($visitor['secondary_group_ids']))
				{
					$userGroups .= ','.$visitor['secondary_group_ids'];
				}
				$userGroups = explode(',', $userGroups);
				self::$_userGroups = $userGroups;
			}
			if(!is_array(self::$_userGroups)) {
				self::$_userGroups = array();
			}
			if(!is_array($excludeGroups)) {
				$excludeGroups = array();
			}
			if(array_intersect(self::$_userGroups, $excludeGroups)){
				return false;
			}
		}
		return true;
	}

	protected function _getNodeModel()
	{
		return $this->getModelFromCache('XenForo_Model_Node');
	}

	protected function _getThreadModel()
	{
		return $this->getModelFromCache('XenForo_Model_Thread');
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}

	/**
	 * @return XenResource_Model_Category
	 */
	protected function _getCategoryModel()
	{
		return $this->getModelFromCache('XenResource_Model_Category');
	}

	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	protected function _getUserGroupModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserGroup');
	}

	protected function _getProfilePostModel()
	{
		return $this->getModelFromCache('XenForo_Model_ProfilePost');
	}
}

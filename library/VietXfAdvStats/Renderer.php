<?php
class VietXfAdvStats_Renderer {
	protected static $_models = array();
	protected static $_shared = array();
	
	const SHARED_DATA_VIEWABLE_NODES = 'viewableNodes';
	
	public static function renderWrapper(XenForo_Template_Abstract $template) {
		$sections1st = array();
		$sections2nd = array();
		
		$autoRenderData = array('template' => $template);
		
		$sections1stRaw = VietXfAdvStats_Option::get('sections1st');
		foreach ($sections1stRaw as $section) {
			$sections1st[] = self::_prepareSectionForWrapper(
				$section,
				count($sections1st) == 0 ? $autoRenderData : array() // only auto render the first section
			);
		}
		
		$sections2ndRaw = VietXfAdvStats_Option::get('sections2nd');
		foreach ($sections2ndRaw as $section) {
			$sections2nd[] = self::_prepareSectionForWrapper(
				$section,
				count($sections2nd) == 0 ? $autoRenderData : array() // only auto render the first section
			);
		}
		
		$template->setParam('sections1st', $sections1st);
		$template->setParam('sections2nd', $sections2nd);
		$template->setParam('bulkUpdateUrl', XenForo_Link::buildPublicLink(VietXfAdvStats_Option::get('routePrefix') . '/bulk-update'));
		$template->setParam('updateInterval', VietXfAdvStats_Option::get('updateInterval'));
		$template->setParam('updateIntervals', VietXfAdvStats_Option::get('updateIntervalsSorted'));
		$template->setParam('itemLimit', VietXfAdvStats_Option::get('itemLimit'));
		$template->setParam('itemLimits', VietXfAdvStats_Option::get('itemLimitsSorted'));
		$template->setParam('backLink', VietXfAdvStats_Option::get('backLink'));
		
		return $template->render();
	}
	
	public static function renderSectionUserPrepare($controller, $type) {
		if ($controller instanceof XenForo_Controller) {
			$input = $controller->getInput()->filter(self::_getInputFilter());
		} else {
			$input = $controller['pseudoInput'];
		}
		
		// start setting up important params
		$childTemplate = false;
		$fetchOptions = array();
		$conditions = array();
		$tran = false;
		switch ($type) {
			case 'users_new':
				$childTemplate = 'VietxfAdvStats_users_new';
				$fetchOptions = array(
					'order' => 'register_date',
					'direction' => 'desc',
				);
				break;
			case 'users_top_posters':
				$childTemplate = 'VietxfAdvStats_users_top_posters';
				$fetchOptions = array(
					'order' => 'message_count',
					'direction' => 'desc',
				);
				break;
			case 'users_top_trophy_points':
				$childTemplate = 'VietxfAdvStats_users_top_trophy_points';
				$fetchOptions = array(
					'order' => 'trophy_points',
					'direction' => 'desc',
				);
				break;
			case 'users_top_liked':
				$childTemplate = 'VietxfAdvStats_users_top_liked';
				$fetchOptions = array(
					'order' => 'like_count',
					'direction' => 'desc',
				);
				break;
			case 'users_top_richest':
				$childTemplate = 'VietxfAdvStats_users_top_richest';
				$fetchOptions = array(
					'order' => 'credits',
					'direction' => 'desc',
				);
				break;
			case 'users_top_poorest':
				$childTemplate = 'VietxfAdvStats_users_top_poorest';
				$fetchOptions = array(
					'order' => 'credits',
					'direction' => 'asc',
				);
				break;
			case 'users_top_earned_in_day':
				$childTemplate = 'VietxfAdvStats_users_top_earned_in_day';
				$tran = true;
				break;
			case 'users_top_spent_in_day':
				$childTemplate = 'VietxfAdvStats_users_top_spent_in_day';
				$tran = true;
				break;
		}
		// finished important params
		
		// prepare various fetch options
		$fetchOptions['limit'] = VietXfAdvStats_Option::get('itemLimit', $input);
		
		// start getting users data
		$userModel = self::getModelFromCache('XenForo_Model_User');
		$fetchOptions['validOnly'] = true;
		$users = $userModel->getAllUsers($fetchOptions);
		if($tran && XenForo_Model::create('XenForo_Model_AddOn')->getAddOnVersion('Brivium_Credits')){
			$transactionModel = self::getModelFromCache('Brivium_Credits_Model_Transaction');
			// top transaction in day user
			$dayStartTimestamps = XenForo_Locale::getDayStartTimestamps();
			$conditions = array(
				'start' => $dayStartTimestamps['today'],
			);
			if($type == 'users_top_earned_in_day'){
				$topTranInDay = $transactionModel->getTopEarnedTransactions($conditions, array('limit' => VietXfAdvStats_Option::get('itemLimit', $input)));
			}else{
				$topTranInDay = $transactionModel->getTopSpentTransactions($conditions, array('limit' => VietXfAdvStats_Option::get('itemLimit', $input)));
			}
			$users = array();
			if(is_array($topTranInDay) && !empty($topTranInDay)){
				foreach($topTranInDay AS $userId=>$tranInfo){
					$users[$userId] = $userModel->getUserById($userId);
					$users[$userId]['credits'] = $tranInfo['credits'];
				}
			}
		}
			
		$viewParams = array(
			'users' => $users,
			'childTemplate' => $childTemplate,
			'requested' => self::_getControllerRequestedData($controller, $input, $type),
		);
		
		$templateName = 'VietXfAdvStats_users';
		
		return array($templateName, $viewParams);
	}
	
	public static function renderSectionUserFinalize(array &$params, $templateFactory) {
		$usersPrepared = array();
		
		$userNameChars = XenForo_Template_Helper_Core::styleProperty('VietXfAdvStats_UserNameChars');
		
		foreach ($params['users'] as $user) {
			if ($userNameChars > 0) {
				$user['username'] = XenForo_Template_Helper_Core::helperWordTrim($user['username'], $userNameChars);
				// TODO: use XenForo_Helper_String::wholeWordTrim directly?
			}
			
			$childTemplateParams = array(
				'user' => $user,
			);
			
			$usersPrepared[] = self::_createTemplateFromTemplateFactory($templateFactory, $params['childTemplate'], $childTemplateParams);
		}
		
		$params['usersPrepared'] = $usersPrepared;
	}
	
	public static function renderSectionThreadPrepare($controller, $type) {
		if ($controller instanceof XenForo_Controller) {
			$input = $controller->getInput()->filter(self::_getInputFilter(array(
				'node_ids' => XenForo_Input::STRING,
				'section_forum_id' => XenForo_Input::UINT,
				'hash' => XenForo_Input::STRING,
			)));
		} else {
			$input = $controller['pseudoInput'];
		}
		
		// start setting up important params
		$childTemplate = false;
		$custom = false;
		$fetchOptions = array();
		$conditions = array();
		switch ($type) {
			case 'threads_latest_custom_forum':
				$custom = true;
			case 'threads_latest':
				$childTemplate = 'VietxfAdvStats_threads_latest';
				$fetchOptions = array(
					'order' => 'post_date',
					'direction' => 'desc',
					'VietXfAdvStats_join' => XenForo_Model_Thread::FETCH_USER,
				);
				break;
			case 'threads_hot':
				$childTemplate = 'VietxfAdvStats_threads_hot';
				$fetchOptions = array(
					'order' => 'view_count',
					'orderDirection' => 'desc'
				);
				
				$hotThreadCutOff = VietXfAdvStats_Option::get('hotThreadCutOff');
				if ($hotThreadCutOff > 0) {
					$conditions['VietXfAdvStats_post_date'] = array('>', XenForo_Application::$time - $hotThreadCutOff * 86400);
				}
				break;
			case 'threads_recent_custom_forum':
				$custom = true;
			case 'threads_recent':
				$childTemplate = 'VietxfAdvStats_threads_recent';
				$fetchOptions = array(
					'order' => 'last_post_date',
					'orderDirection' => 'desc',
					'VietXfAdvStats_join_last_post' => XenForo_Model_Thread::FETCH_USER,
				);
				break;
			case 'threads_random':			
				$childTemplate = 'VietxfAdvStats_threads_random';
				$fetchOptions = array(
					'order' => 'title',
					'orderDirection' => 'RANDOM()',
					'VietXfAdvStats_join' => XenForo_Model_Thread::FETCH_USER,					
					'VietXfAdvStats_random' => true,					
				);
				break;
		}
		// finished important params
		
		// start looking for forums scope
		if (!empty($input['node_ids'])) {
			$nodeIds = explode(',', $input['node_ids']);
			if ($input['hash'] == self::calcHash($nodeIds)) {
				// the hash is validated, use the list of forums now!
				$conditions['VietXfAdvStats_forum_ids'] = $nodeIds;
			}
		}
		
		if (empty($conditions['VietXfAdvStats_forum_ids']) AND isset($input['section_forum_id']) > 0) {
			// this is a custom section but hash was invalid or something like that
			// we will have to calculate the list of forums here
			$viewableNodes = self::getSharedData(VietXfAdvStats_Renderer::SHARED_DATA_VIEWABLE_NODES);
			$nodeIds = self::getNodeIds($viewableNodes, $input['section_forum_id']);
			$conditions['VietXfAdvStats_forum_ids'] = $nodeIds;
		}

		if (empty($conditions['VietXfAdvStats_forum_ids'])) {
			// still nothing, this is a all-in-one section
			// simply use list of all viewable nodes here
			$viewableNodes = self::getSharedData(VietXfAdvStats_Renderer::SHARED_DATA_VIEWABLE_NODES);
			$conditions['VietXfAdvStats_forum_ids'] = array_keys($viewableNodes);
		}
		$options = XenForo_Application::get('options');
		//print_r($options->superloginGroups);die;
		if(!$custom){
			$get_fid = $options->Excep_id;
			$excep_id = explode(',',$get_fid);
			$conditions['VietXfAdvStats_using'] = true;
			
			$conditions['VietXfAdvStats_forum_ids'] = array_diff($conditions['VietXfAdvStats_forum_ids'], $excep_id);
		}
		//print_r($conditions['VietXfAdvStats_forum_ids']);
		//die;
		$fetchOptions['VietXfAdvStats_join_forum'] = true;
		// fnished forums scope
		
		// prepare various fetch options
		$fetchOptions['limit'] = VietXfAdvStats_Option::get('itemLimit', $input);
		$fetchOptions['readUserId'] = XenForo_Visitor::getUserId();
		$fetchOptions['includeForumReadDate'] = true;
		
		// start getting threads data
		$threadModel = self::getModelFromCache('XenForo_Model_Thread');
		$threads = $threadModel->getThreads($conditions, $fetchOptions);
		
		foreach ($threads as &$thread) {
			$thread = $threadModel->VietXfAdvStats_prepareThread($thread);
		}
		
		$viewParams = array(
			'threads' => $threads,
			'childTemplate' => $childTemplate,
			'requested' => self::_getControllerRequestedData($controller, $input, $type),
		);
		
		$templateName = 'VietXfAdvStats_threads';
		
		return array($templateName, $viewParams);
	}
	
	public static function renderSectionThreadFinalize(array &$params, $templateFactory) {
		$threadsPrepared = array();
		
		$userNameChars = XenForo_Template_Helper_Core::styleProperty('VietXfAdvStats_ThreadInfoUserNameChars');
		
		foreach ($params['threads'] as $thread) {
			if ($userNameChars > 0) {
				$thread['username'] = XenForo_Template_Helper_Core::helperWordTrim($thread['username'], $userNameChars);
				// TODO: use XenForo_Helper_String::wholeWordTrim directly?
			}
			
			$childTemplateParams = array(
				'thread' => $thread,
			);
			
			$threadsPrepared[] = self::_createTemplateFromTemplateFactory($templateFactory, $params['childTemplate'], $childTemplateParams);
		}
		
		$params['threadsPrepared'] = $threadsPrepared;
	}
	
	public static function renderSection($majorType, $type, $action, $params, $templateFactory, array $pseudoInput = array()) {
		switch ($majorType) {
			case 'users':
				$prepare = 'renderSectionUserPrepare';
				$finalize = 'renderSectionUserFinalize';
				break;
			case 'threads':
				$prepare = 'renderSectionThreadPrepare';
				$finalize = 'renderSectionThreadFinalize';
				break;
		}
		
		if (!is_array($params)) {
			// params was encoded earlier
			// see _getControllerRequestedData() for more detail
			$params = json_decode(base64_decode($params), true);
		}
		
		if (!empty($finalize)) {
			$pseudoController = array(
				'pseudoAction' => $action,
				'pseudoInput' => array_merge($params, $pseudoInput),
			);

			list($templateName, $viewParams) = call_user_func_array(array(__CLASS__, $prepare), array($pseudoController, $type));
			call_user_func_array(array(__CLASS__, $finalize), array(&$viewParams, $templateFactory));
			
			return self::_createTemplateFromTemplateFactory($templateFactory, $templateName, $viewParams);
		}
		
		return '';
	}
	
	public static function calcHash(array $nodeIds, $csrfToken = false) {
		if ($csrfToken === false) {
			$csrfToken = XenForo_Visitor::getInstance()->get('csrf_token');
		}
		
		if (empty($csrfToken)) {
			$csrfToken = 'this-is-for-guest';
		}
		
		return md5(implode('', $nodeIds) . $csrfToken);
	}
	
	public static function getSharedData($dataName) {
		if (!isset(self::$_shared[$dataName])) {
			switch ($dataName) {
				case self::SHARED_DATA_VIEWABLE_NODES:
					$nodeModel = self::getModelFromCache('XenForo_Model_Node');
					self::$_shared[$dataName] = $nodeModel->getViewableNodeList();
					break;
				default:
					self::$_shared[$dataName] = false;
			}
		}
		
		return self::$_shared[$dataName];
	}
	
	public static function getNodeIds(array &$viewableNodes, $nodeId) {
		$nodeIds = array();
		if(is_array($nodeId)){
			foreach ($nodeId as $nodeIdSub) {
				$nodeIds = array_merge($nodeIds, self::getNodeIds($viewableNodes, $nodeIdSub));
			}
		}else{
			if (isset($viewableNodes[$nodeId])) {
				$nodeIds[] = $nodeId;
				
				foreach ($viewableNodes as &$node) {
					if ($node['parent_node_id'] == $nodeId) {
						$nodeIds = array_merge($nodeIds, self::getNodeIds($viewableNodes, $node['node_id']));
					}
				}
			}
		}
		return $nodeIds;
	}
	
	public static function getModelFromCache($modelName) {
		if (!isset(self::$_models[$modelName])) {
			self::$_models[$modelName] = XenForo_Model::create($modelName);
		}
		
		return self::$_models[$modelName];
	}
	
	protected static function _prepareSectionForWrapper(array $sectionRaw, array $autoRenderData = array()) {
		$section = array();

		// prepare common info
		$typeEscaped = self::_escapeTypeString($sectionRaw['type']);
		// finished common info
		
		$section['section_id'] = self::_getUniqueId($typeEscaped);
		
		if (!empty($sectionRaw['title'])) {
			$section['section_title'] = $sectionRaw['title'];
		} else {
			$section['section_title'] = new XenForo_Phrase('VietXfAdvStats_section_' . $sectionRaw['type']);
		}
		
		// prepare link
		$routePrefix = VietXfAdvStats_Option::get('routePrefix');
		$linkAction = $typeEscaped;
		$linkParams = array();
		
			// prepare link detail
			$typeParts = explode('_', $sectionRaw['type']);
			$typeMajor = array_shift($typeParts);
			$section['section_type'] = $sectionRaw['type'];
			$section['section_type_major'] = $typeMajor;
			switch ($typeMajor) {
				case 'threads':
					$viewableNodes = self::getSharedData(self::SHARED_DATA_VIEWABLE_NODES);
					if (!empty($sectionRaw['forum_id'])) {
						// this is a custom section, include selected node and its children only
						$nodeIds = self::getNodeIds($viewableNodes, $sectionRaw['forum_id']);
						$linkParams['node_ids'] = implode(',', $nodeIds);
						$linkParams['section_forum_id'] = $sectionRaw['forum_id'];
						if (!empty($nodeIds) && empty($sectionRaw['title']) && !is_array($sectionRaw['forum_id'])) {
							$section['section_title'] = $viewableNodes[$sectionRaw['forum_id']]['title'];
						}
					} else {
						// include all nodes
						$linkParams['node_ids'] = implode(',', array_keys($viewableNodes));
					}
					break;
			}
			if (!empty($linkParams['node_ids'])) {
				$linkParams['hash'] = self::calcHash(explode(',', $linkParams['node_ids']));
			}
			// finished link detail
			
		$section['section_link'] = XenForo_Link::buildPublicLink($routePrefix . '/' . $linkAction, array(), $linkParams);
		// finished link
		
		// try to render the section immediately
		if (!empty($autoRenderData['template'])) {
			$section['rendered'] = self::renderSection($section['section_type_major'], $section['section_type'], $linkAction, $linkParams, $autoRenderData['template']);
		}
		
		return $section;
	}
	
	protected static function _getControllerRequestedData($controller, array $input, $type) {
		$linkParams = array();
		foreach ($input as $key => $value) {
			if (!empty($value)) {
				$linkParams[$key] = $value;
			}
		}
		
		if ($controller instanceof XenForo_Controller) {
			$routeMatch = $controller->getRouteMatch();
			$action = $routeMatch->getAction();
		} else {
			$action = $controller['pseudoAction'];
		}
		
		return array(
			'action' => $action,
			'params' => base64_encode(json_encode($linkParams)),
			'type' => $type,
		);
	}
	
	protected static function _getInputFilter(array $filter = array()) {
		static $defaultFilter = array(
			'itemLimit' => XenForo_Input::UINT,
			'updateInterval' => XenForo_Input::UINT,
		);
		
		return array_merge($defaultFilter, $filter);
	}
	
	protected static function _getUniqueId($type) {
		static $ids = array();
		
		$i = 0;
		
		do {
			$id = $type . ($i > 0 ? $i : '');
			$i++;	
		} while (in_array($id, $ids));
		
		$ids[] = $id;
		
		return $id;
	}
	
	protected static function _escapeTypeString($type) {
		return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z]/', '-', strtolower($type))), '-');
	}
	
	protected static function _createTemplateFromTemplateFactory($templateFactory, $templateName, array $templateParams = array()) {
		if ($templateFactory instanceof XenForo_View) {
			return $templateFactory->createTemplateObject($templateName, $templateParams);
		} elseif ($templateFactory instanceof XenForo_Template_Abstract) {
			return $templateFactory->create($templateName, $templateParams);
		} else {
			return '';
		}
	}
}
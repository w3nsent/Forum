<?php

class Brivium_ModernStatistic_ControllerAdmin_ModernStatistic extends XenForo_ControllerAdmin_Abstract
{

	public function actionIndex()
	{
		$modernStatisticModel = $this->_getModernStatisticModel();
		$modernStatistics = $modernStatisticModel->getAllModernStatistics();

		$viewParams = array(
			'modernStatistics' => $modernStatistics,
		);

		return $this->responseView('Brivium_ModernStatistic_ViewAdmin_ModernStatistic_List', 'BRMS_modern_statistic_list', $viewParams);
	}

	public function actionAdd()
	{
		return $this->responseReroute('Brivium_ModernStatistic_ControllerAdmin_ModernStatistic', 'edit');
	}

	public function actionEdit()
	{
		$modernStatisticModel = $this->_getModernStatisticModel();
		$lastOrder = 0;
		if ($modernStatisticId = $this->_input->filterSingle('modern_statistic_id', XenForo_Input::UINT))
		{
			// if a statistic ID was specified, we should be editing, so make sure a statistic exists
			$modernStatistic = $modernStatisticModel->getModernStatisticById($modernStatisticId);
			if (!$modernStatistic)
			{
				return $this->responseError(new XenForo_Phrase('BRMS_requested_statistic_not_found'), 404);
			}

			$modernStatistic = $modernStatisticModel->prepareModernStatistic($modernStatistic, true);
			$tabsData = $modernStatistic['tabData'];
			if(!empty($tabsData)){
				$end = end($tabsData);
				$lastOrder = isset($end['display_order'])?$end['display_order']+1:0;
				if($lastOrder==0){
					$lastOrder = count($tabsData)+1;
				}
			}

			$itemLimit = $modernStatistic['itemLimit'];
		}
		else
		{
			// add a new statistic
			$modernStatistic = array(
				'title'					=> '',
				'preview_tooltip'		=> 'custom_preview',
				'control_position'		=> 'brmsLeftTabs',
				'usename_marke_up'		=> 1,
				'show_thread_prefix'	=> 1,
				'show_resource_prefix'	=> 1,
				'allow_change_layout'	=> 1,
				'allow_manual_refresh'	=> 1,
				'auto_update'			=> 60,
				'active'				=> 1,
				'enable_cache'			=> 1,
				'allow_user_setting'	=> 1,
				'cache_time'			=> 5,
				'modernCriteria'		=> array(),
			);
			$tabsData = array();
			$itemLimit = array('default'=>15);
		}
		$nodeModel = $this->_getNodeModel();

		$nodeList = $nodeModel->getNodeOptionsArray(
			$nodeModel->getAllNodes(),
			-1,
			false
		);
		$forumOptions = array();
		foreach ($nodeList as $key => $node)
		{
			if (!empty($node['node_type_id']) && $node['node_type_id'] != 'Forum')
			{
				$node['disabled'] = 'disabled';
			}
			unset($node['node_type_id']);
			$forumOptions[$key] = $node;
		}
		$userGroupOptions = XenForo_Option_UserGroupChooser::getUserGroupOptions(-1, false);
		$listKinds = array(
			array('value' => 'thread', 'label' => new XenForo_Phrase('thread')),
			array('value' => 'user', 'label' => new XenForo_Phrase('user')),
			array('value' => 'profile_post', 'label' => new XenForo_Phrase('profile_post')),
		);

		$creditVersion = $modernStatisticModel->checkBriviumCreditsAddon();
		$currencyOptions = array();
		$listTypeUsers = array(
			array('value' => 'user_most_messages', 	'label' => new XenForo_Phrase('most_messages')),
			array('value' => 'user_most_likes', 	'label' => new XenForo_Phrase('most_likes')),
			array('value' => 'user_most_points', 	'label' => new XenForo_Phrase('most_points')),
			array('value' => 'user_staff_members', 	'label' => new XenForo_Phrase('staff_members')),
			array('value' => 'user_latest_members', 'label' => new XenForo_Phrase('BRMS_latest_members')),
			array('value' => 'user_latest_banned', 	'label' => new XenForo_Phrase('BRMS_latest_banned_members')),
		);
		if($creditVersion){
			$listTypeUsers[] = array('value' => 'user_richest', 	'label' => new XenForo_Phrase('BRC_top_richest'));
			$listTypeUsers[] = array('value' => 'user_poorest', 	'label' => new XenForo_Phrase('BRC_top_poorest'));
			if($creditVersion >= 1000000){
				$currencyOptions = $this->_getCurrencyModel()->getCurrenciesForOptionsTag();
			}
		}

		$resourceVersion = $modernStatisticModel->checkXenForoResourceAddon();
		$categoryOptions = array();
		$resourcePrefixes = array();
		if($resourceVersion){
			$listTypeUsers[] = array('value' => 'user_most_resources', 	'label' => new XenForo_Phrase('most_resources'));
			$listKinds[] = array('value' => 'resource', 'label' => new XenForo_Phrase('resource'));
			$categoryModel = $this->getModelFromCache('XenResource_Model_Category');
			$categories = $categoryModel->prepareCategories($categoryModel->getViewableCategories());

			foreach ($categories as $categoryId => $category)
			{
				$category['depth'] += 1;

				$categoryOptions[$categoryId] = array(
					'value' => $categoryId,
					'label' => $category['category_title'],
					'depth' => $category['depth']
				);
			}
			$resourcePrefixModel = $this->_getResourcePrefixModel();
			$resourcePrefixes = $resourcePrefixModel->getPrefixOptions();
		}

		$listTypeThreads = array(
			array('value' => 'thread_latest', 	'label' => new XenForo_Phrase('BRMS_latest_threads')),
			array('value' => 'thread_hotest', 	'label' => new XenForo_Phrase('BRMS_most_viewed_threads')),
			array('value' => 'post_latest', 	'label' => new XenForo_Phrase('BRMS_latest_replies')),
			array('value' => 'most_reply', 		'label' => new XenForo_Phrase('BRMS_most_replied_threads')),
			array('value' => 'sticky_threads', 	'label' => new XenForo_Phrase('BRMS_sticky_threads')),
			array('value' => 'my_threads', 		'label' => new XenForo_Phrase('BRMS_my_threads')),
			array('value' => 'promoted_threads','label' => new XenForo_Phrase('BRMS_promoted_threads')),
		);
		$listTypeProfilePosts = array(
			array('value' => 'latest_profile_posts', 'label' => new XenForo_Phrase('BRMS_latest_profile_posts')),
			array('value' => 'your_profile_posts', 'label' => new XenForo_Phrase('BRMS_your_profile_posts')),
		);
		$listTypeResources = array(
			array('value' => 'resource_last_update', 		'label' => new XenForo_Phrase('latest_updates')),
			array('value' => 'resource_resource_date', 		'label' => new XenForo_Phrase('newest_resources')),
			array('value' => 'resource_rating_weighted', 	'label' => new XenForo_Phrase('top_resources')),
			array('value' => 'resource_download_count', 	'label' => new XenForo_Phrase('most_downloaded')),
		);
		$listThreadOrders = array(
			array('value' => 'title',				'label' => new XenForo_Phrase('title_alphabetical')),
			array('value' => 'post_date', 			'label' => new XenForo_Phrase('thread_creation_time')),
			array('value' => 'view_count', 			'label' => new XenForo_Phrase('number_of_views')),
			array('value' => 'reply_count', 		'label' => new XenForo_Phrase('number_of_replies')),
			array('value' => 'first_post_likes', 	'label' => new XenForo_Phrase('first_message_likes')),
		);

		$styleModel = $this->getModelFromCache('XenForo_Model_Style');
		$styles = $styleModel->getAllStylesAsFlattenedTree();

		$defaultTabData = array(
			'kind'					=> '',
			'title'					=> '',
			'type'					=> '',
			'order_type'			=> '',
			'order_direction'		=> '',
			'cut_off'				=> 0,
			'active'				=> 1,
			'prefix_id'				=> array(),
			'forums'				=> array(),
			'discussion_state'		=> array(),
			'discussion_open'		=> array(),
			'categories'			=> array(),
			'user_groups'			=> array(),
			'resource_prefix_id'	=> array(),
			'resource_state'		=> array(),
			'gender'				=> array(),
			'user_state'			=> array(),
			'currency_id'			=> 0,
			'display_order'			=> $lastOrder,
		);
		$viewParams = array(
			'modernStatistic'		=> $modernStatistic,

			'lastOrder'				=> $lastOrder,

			'styles'				=> $styles,
			'defaultTabData'		=> $defaultTabData,

			'resourceVersion'		=> $resourceVersion,
			'userGroupOptions'		=> $userGroupOptions,

			'categoryList'			=> $categoryOptions,
			'resourcePrefixes'		=> $resourcePrefixes,
			'currencyOptions'		=> $currencyOptions,
			'forumList'				=> $forumOptions,
			'nodeList'				=> $nodeList,
			'prefixes'				=> $this->getModelFromCache('XenForo_Model_ThreadPrefix')->getPrefixOptions(),

			'nextCounter'			=> count($tabsData),
			'tabsData'				=> $tabsData,
			'itemLimit'				=> $itemLimit,

			'listTypeThreads'		=> $listTypeThreads,
			'listTypeProfilePosts'	=> $listTypeProfilePosts,
			'listTypeResources'		=> $listTypeResources,
			'listTypeUsers'			=> $listTypeUsers,
			'listThreadOrders'		=> $listThreadOrders,
			'languages'				=> $this->getModelFromCache('XenForo_Model_Language')->getLanguagesForOptionsTag(),

			'listKinds'				=> $listKinds,
		);
		return $this->responseView('Brivium_ModernStatistic_ViewAdmin_ModernStatistic_Edit', 'BRMS_modern_statistic_edit', $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$modernStatisticId = $this->_input->filterSingle('modern_statistic_id', XenForo_Input::UINT);

		$writerData = $this->_input->filter(array(
			'title'					=> XenForo_Input::STRING,
			'active'				=> XenForo_Input::UINT,
			'usename_marke_up'		=> XenForo_Input::UINT,
			'show_thread_prefix'	=> XenForo_Input::UINT,
			'show_resource_prefix'	=> XenForo_Input::UINT,
			'load_fisrt_tab'		=> XenForo_Input::UINT,
			'control_position'		=> XenForo_Input::STRING,
			'preview_tooltip'		=> XenForo_Input::STRING,
			'position'				=> XenForo_Input::STRING,
			'style_display'			=> XenForo_Input::STRING,
			'thread_cutoff'			=> XenForo_Input::UINT,
			'enable_cache'			=> XenForo_Input::UINT,
			'cache_time'			=> XenForo_Input::UINT,
			'auto_update'			=> XenForo_Input::UINT,
			'allow_change_layout'	=> XenForo_Input::UINT,
			'allow_manual_refresh'	=> XenForo_Input::UINT,
			'allow_user_setting'	=> XenForo_Input::UINT,
			'modern_criteria'		=> XenForo_Input::ARRAY_SIMPLE,
			'style_settings'		=> XenForo_Input::ARRAY_SIMPLE,
		));
		if(!empty($writerData['modern_criteria']['language_ids'])){
			$writerData['modern_criteria']['language_ids'] = array_filter($writerData['modern_criteria']['language_ids']);
		}

		$itemLimit = $this->_input->filterSingle('item_limit', XenForo_Input::ARRAY_SIMPLE);
		$output = array();
		if(!empty($itemLimit['value'])){
			foreach ($itemLimit['value'] as $number) {
				if (!empty($number) && $number > 0) {
					$output[] = intval($number);
				}
			}
			asort($output);
			$itemLimit['value'] = array_values(array_unique($output));
		}
		if(!empty($itemLimit['default'])){
			$itemLimit['default'] = intval($itemLimit['default']);
		}else{
			$itemLimit['default'] = 15;
		}
		if(!empty($itemLimit['enabled']) && empty($itemLimit['value'])){
			return $this->responseError(new XenForo_Phrase('BRMS_must_have_value_for_item_limit'));
		}
		$modernStatisticModel = $this->_getModernStatisticModel();
		$creditVersion = $modernStatisticModel->checkBriviumCreditsAddon();
		$tabs = $this->_input->filterSingle('tab_data', XenForo_Input::ARRAY_SIMPLE);
		$output = array();
		foreach ($tabs as $key => $tab) {
			if (!empty($tab['kind'])) {
				switch($tab['kind']){
					case 'profile_post':
						if (!empty($tab['type_profile_post']) && isset($tab['kind'])) {
							$tab['type']= $tab['type_profile_post'];
							$output[] = $tab;
						}
						break;
					case 'resource':
						if (!empty($tab['type_resource']) && isset($tab['kind'])) {
							$tab['type']= $tab['type_resource'];
							$output[] = $tab;
						}
						break;
					case 'user':
						if (!empty($tab['type_user']) && isset($tab['kind'])) {
							$tab['type']= $tab['type_user'];
							if(($tab['type_user']=='user_poorest' || $tab['type_user']=='user_richest') && $creditVersion >= 1000000 && empty($tab['currency_id'])){
								return $this->responseError(new XenForo_Phrase('BRMS_must_select_currency'));
							}
							$output[] = $tab;
						}
						break;
					case 'thread':
					default:
						if (!empty($tab['type']) && !empty($tab['kind'])) {
							$output[] = $tab;
						}
						break;
				}
			}
		}
		$tabs = $output;
		//prd($tabs);
		$itemLimit = $this->_input->filterSingle('item_limit', XenForo_Input::ARRAY_SIMPLE);

		$writer = XenForo_DataWriter::create('Brivium_ModernStatistic_DataWriter_ModernStatistic');
		if ($modernStatisticId)
		{
			$writer->setExistingData($modernStatisticId);
		}
		$writer->bulkSet($writerData);
		$writer->set('item_limit', $itemLimit);
		$writer->set('tab_data', $tabs);
		$writer->save();

		$this->_getModernStatisticModel()->rebuildModernStatisticCaches();
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('brms-statistics') . $this->getLastHash($writer->get('modern_statistic_id'))
		);
	}

	/**
	 * This method should be sufficiently generic to handle deletion of any extended statistic type
	 *
	 * @return XenForo_ControllerResponse_Reroute
	 */
	public function actionDelete()
	{
		$modernStatisticModel = $this->_getModernStatisticModel();
		$modernStatisticId = $this->_input->filterSingle('modern_statistic_id', XenForo_Input::STRING);
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Brivium_ModernStatistic_DataWriter_ModernStatistic');
			$dw->setExistingData($modernStatisticId);
			$dw->delete();
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brms-statistics')
			);
		}
		else // show confirmation dialog
		{
			$viewParams['modernStatistic'] = $modernStatisticModel->getModernStatisticById($modernStatisticId);
			return $this->responseView('Brivium_ModernStatistic_ViewAdmin_Product_Delete', 'BRMS_modern_statistic_delete', $viewParams);
		}
	}
	/**
	 * Gets the product model.
	 *
	 * @return Brivium_ModernStatistic_Model_ModernStatistic
	 */
	protected function _getModernStatisticModel()
	{
		return $this->getModelFromCache('Brivium_ModernStatistic_Model_ModernStatistic');
	}

	protected function _getResourcePrefixModel()
	{
		return $this->getModelFromCache('XenResource_Model_Prefix');
	}

	protected function _getNodeModel()
	{
		return $this->getModelFromCache('XenForo_Model_Node');
	}

	/**
	 * Gets the currency model.
	 *
	 * @return Brivium_Credits_Model_Currency
	 */
	protected function _getCurrencyModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Currency');
	}
}

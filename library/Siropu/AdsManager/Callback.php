<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

abstract class Siropu_AdsManager_Callback
{
	public static function renderMultiSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
		$forumOpt = XenForo_Option_NodeChooser::getNodeOptions(false, false, 'Forum');
		$forumLst = $preparedOption['option_value']['node_id'];
		$forumLst = is_array($forumLst) ? array_flip($forumLst) : $forumLst;
		$selected = array();

		foreach ($forumOpt as $key => $val)
		{
			$forumOpt[$key]['label'] = strip_tags($forumOpt[$key]['label']);

			if (isset($forumLst[$key]))
			{
				$forumOpt[$key]['selected'] = true;

				if ($preparedOption['option_id'] != 'siropu_ads_manager_keyword_exclude_nodes')
				{
					$selected[] = $forumOpt[$key]['label'] . " (ID: {$key})";
				}
			}
		}

		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('siropu_ads_manager_option_template_multiselect', array(
			'fieldPrefix'     => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption'  => $preparedOption,
			'formatParams'    => $forumOpt,
			'selectedForums'  => $selected,
			'editLink'        => $editLink
		));
	}
	public static function renderThreadPrefixSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('siropu_ads_manager_option_template_prefix_select', array(
			'fieldPrefix'     => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption'  => $preparedOption,
			'formatParams'    => XenForo_Model::create('XenForo_Model_ThreadPrefix')->getPrefixOptions(),
			'editLink'        => $editLink
		));
	}
	public static function renderCurrencySelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('siropu_ads_manager_option_template_currency_select', array(
			'fieldPrefix'     => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption'  => $preparedOption,
			'formatParams'    => Siropu_AdsManager_Helper_General::getCurrencyList('', true),
			'editLink'        => $editLink
		));
	}
	public static function renderUserGroupSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		$userGroups    = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
		$defaultGroups = array(
			XenForo_Model_User::$defaultGuestGroupId,
			XenForo_Model_User::$defaultRegisteredGroupId,
			XenForo_Model_User::$defaultAdminGroupId,
			XenForo_Model_User::$defaultModeratorGroupId
		);

		$customGroups = array();

		foreach ($userGroups as $group)
		{
			if (!in_array($group['user_group_id'], $defaultGroups))
			{
				$customGroups[$group['user_group_id']] = $group['title'];
			}
		}

		return $view->createTemplateObject('siropu_ads_manager_option_template_user_groups', array(
			'fieldPrefix'     => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption'  => $preparedOption,
			'formatParams'    => $customGroups,
			'editLink'        => $editLink
		));
	}
	public static function renderResourceCategorySelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
		if (!XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('XenResource'))
		{
			return false;
		}

		$catModel = XenForo_Model::create('XenResource_Model_Category');
		$catOpt   = $catModel->getAllCategories($catModel->getAllCategories());
		$catList  = $preparedOption['option_value']['cat_id'];
		$catList  = is_array($catList) ? array_flip($catList) : $catList;
		$selected = array();

		foreach ($catOpt as $key => $val)
		{
			$catOpt[$key]['value'] = $key;
			$catOpt[$key]['label'] = strip_tags($catOpt[$key]['category_title']);

			if (isset($catList[$key]))
			{
				$catOpt[$key]['selected'] = true;
				$selected[] = $catOpt[$key]['label'] . " (ID: {$key})";
			}
		}

		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('siropu_chat_option_template_categories_multiselect', array(
			'fieldPrefix'        => $fieldPrefix,
			'listedFieldName'    => $fieldPrefix . '_listed[]',
			'preparedOption'     => $preparedOption,
			'formatParams'       => $catOpt,
			'selectedCategories' => $selected,
			'editLink'           => $editLink
		));
	}
}

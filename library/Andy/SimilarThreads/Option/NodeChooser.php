<?php

abstract class Andy_SimilarThreads_Option_NodeChooser
{
	public static function renderSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		return self::_render('andy_option_list_option_select_similar_threads', $view, $fieldPrefix, $preparedOption, $canEdit);
	}
	
	public static function getNodeOptions($selectedForum, $includeRoot = false, $filter = false)
	{
		/* @var $nodeModel XenForo_Model_Node */
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');

		$options = $nodeModel->getNodeOptionsArray(
			$nodeModel->getAllNodes(),
			$selectedForum,
			$includeRoot
		);
		
		// define filter
		$filter = 'Forum';

		if ($filter)
		{
			foreach ($options AS &$option)
			{
				if (!empty($option['node_type_id']) && $option['node_type_id'] != $filter)
				{
					$option['disabled'] = 'disabled';
				}

				unset($option['node_type_id']);
			}
		}

		return $options;
	}	
	
	protected static function _render($templateName, XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$filter = isset($preparedOption['nodeFilter']) ? $preparedOption['nodeFilter'] : false;

		$preparedOption['formatParams'] = self::getNodeOptions(
			$preparedOption['option_value'],
			sprintf('(%s)', new XenForo_Phrase('unspecified')),
			$filter
		);

		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
			$templateName, $view, $fieldPrefix, $preparedOption, $canEdit
		);
	}	
}
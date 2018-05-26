<?php
class VietXfAdvStats_Option {
	protected static function _renderOptionSections(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit, array $availableSectionsRaw) {
		$value = $preparedOption['option_value'];
		static $forums = false;
		if ($forums === false) {
			$nodeModel = XenForo_Model::create('XenForo_Model_Node');
			$nodes = $nodeModel->getAllNodes();
			$forums = array();
			
			foreach ($nodes as $node) {
				if ($node['node_type_id'] == 'Forum') {
					$forums[] = array(
						'value' => $node['node_id'],
						'label' => $node['title'],
						'depth' => $node['depth'],
					//	'selected' => in_array($node['node_id'] , $event['user_groups']),
					);
				}
			}
		}
		$configured = array();
		foreach ($value as $section) {
			if(!empty($section['type'])) {
				if(!empty($forums) && !empty($section['forum_id']) && is_array($section['forum_id'])){
					foreach ($forums AS $node)
					{
						$node['selected'] = in_array($node['value'] , $section['forum_id']);
						$section['forums'][] = $node;
					}
				}
				$configured[] = $section;
			}
		}
		if($preparedOption['option_id']=='VietXfAdvStats_sections2nd'){
			//prd($configured);
		}
		
		
		$availableSections = array();
		foreach ($availableSectionsRaw as $key => $value) {
			if (is_numeric($key)) {
				// auto phrase this section
				$key = $value;
				$value = new XenForo_Phrase('VietXfAdvStats_section_' . $value);
			} else {
				// seems to be good, do nothing
			}
			$availableSections[$key] = $value;
		}
		//prd($availableSections);
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('VietXfAdvStats_option_sections', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,

			'availableSections' => $availableSections,
			'configured' => $configured,
			'nextCounter' => count($configured),
			'forums' => $forums,
		));
	}
	
	public static function renderOptionSections1st(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit) {
		$sections = array(
			'users_new',
			'users_top_posters',
			// 'users_top_starters',
			'users_top_trophy_points',
			'users_top_liked',
			'users_top_richest',
			'users_top_poorest',
			'users_top_earned_in_day',
			'users_top_spent_in_day',
		);
		
		return self::_renderOptionSections($view, $fieldPrefix, $preparedOption, $canEdit, $sections);
	}
	
	public static function renderOptionSections2nd(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit) {
		$sections = array(
			'threads_latest',
			'threads_hot',
			'threads_recent',
			'threads_latest_custom_forum',
			'threads_recent_custom_forum',
			'threads_random',
		);
		return self::_renderOptionSections($view, $fieldPrefix, $preparedOption, $canEdit, $sections);
	}
	
	public static function verifyOptionSections(array &$sections, XenForo_DataWriter $dw, $fieldName) {
		$output = array();

		foreach ($sections as $section) {
			if (!empty($section['type'])) {
				foreach (array_keys($section) as $key) {
					if (empty($section[$key])) {
						unset($section[$key]);
					}
				}
				
				$output[] = $section;
			}
		}

		$sections = $output;

		return true;
	}
	
	public static function renderOptionNumbers(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit) {
		$value = $preparedOption['option_value'];
		
		$numbers = array();
		foreach ($value as $number) {
			if ($number > 0) {
				$numbers[] = $number;
			}
		}
		
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('VietXfAdvStats_option_numbers', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $preparedOption['formatParams'],
			'editLink' => $editLink,

			'numbers' => $numbers,
			'nextCounter' => count($numbers),
		));
	}
	
	public static function verifyOptionNumbers(array &$numbers, XenForo_DataWriter $dw, $fieldName) {
		$output = array();

		foreach ($numbers as $number) {
			if ($number > 0) {
				$output[] = $number;
			}
		}

		$numbers = array_values(array_unique($output));

		return true;
	}
	
	public static function get($key) {
		switch ($key) {
			case 'routePrefix': return 'advstats';
			case 'backLink': return '';
			
			case 'updateInterval':
			case 'itemLimit':
				// get the default value
				$numbers = self::get($key . 's');
				$number = reset($numbers);
				
				$args = func_get_args();
				if (count($args) > 1) {
					$secondArg = $args[1];
					if (is_array($secondArg)) {
						// an array was passed in
						// we will try to get the value from requested data
						if (!empty($secondArg[$key]) AND $secondArg[$key] > 0) {
							$number = $secondArg[$key];
						}
					}
				}
				
				return $number;
			case 'updateIntervalsSorted':
			case 'itemLimitsSorted';
				// get the list of sorted values
				$numbers = self::get(substr($key, 0, -1 * strlen('Sorted')));
				sort($numbers);
				return $numbers;
			case 'updateIntervals':
			case 'itemLimits':
				// get the list of all values safely
				// safely == return the default numbers if nothing found
				$real = self::get($key . 'Real');
				if (!empty($real)) {
					return $real;
				} else {
					return self::get($key . 'Default');
				}
			case 'updateIntervalsDefault': return array(60);
			case 'itemLimitsDefault': return array(5, 10, 15);
		}
		
		return XenForo_Application::get('options')->get('VietXfAdvStats_' . $key);
	}
	public static function renderOptionGroups(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{

		$preparedOption['formatParams'] = XenForo_Model::create('VietXfAdvStats_Model_GetUserGroup')->getUserGroupOptions(
		$preparedOption['option_value']
		);

		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
		'option_list_option_checkbox',
		$view, $fieldPrefix, $preparedOption, $canEdit
		);

	}
	
}
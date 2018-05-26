<?php
class VietXfAdvStats_Listener {
	const POSITION_PLACE_HOLDER = '<!-- VietXfAdvStats -->';
	const POSITION_READY_FLAG = 'VietXfAdvStats_positionReady';
	const TEMPLATE_READY_FLAG = 'VietXfAdvStats_templateReady';
	
	public static function load_class($class, array &$extend) {
		static $classes = array(
			'XenForo_Model_User',
			'XenForo_Model_Thread',
		);
		
		if (in_array($class, $classes)) {
			$extend[] = 'VietXfAdvStats_' . $class;
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
		switch ($hookName) {
			case 'page_container_content_top':
			case 'page_container_content_title_bar':
			case 'forum_list_nodes':
				// check to see if we should work on this hook
				$position = VietXfAdvStats_Option::get('position');
				if (strpos($position, $hookName) === 0) {
					// our position is set to this hook position
					// now we will check to see if it's "above" or "below"
					$relativePosition = trim(substr($position, strlen($hookName)), '_');
					switch ($relativePosition) {
						case 'above':
							$contents = self::POSITION_PLACE_HOLDER . $contents;
							break;
						default:
							// if it's not above, it's definitely below!
							$contents .= self::POSITION_PLACE_HOLDER;
					}
					
					define(self::POSITION_READY_FLAG, true);
				}
				break;
			case 'vietxf_advanced_forum_statistics':
				// this is our special hook
				// $contents = self::POSITION_PLACE_HOLDER; // should we use this instead?
				$contents .= self::POSITION_PLACE_HOLDER;
				define(self::POSITION_READY_FLAG, true);
				break;
		}
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template) {		
		$model = XenForo_Model::create('VietXfAdvStats_Model_GetUserGroup');
		
		if($model->checkGroup()){
		
		
		if ($templateName == 'PAGE_CONTAINER') {
			$options = XenForo_Application::get('options');
			$position = $options->VietXfAdvStats_position;
			
			$position_portal = $options->VietXfAdvStats_portal;
			
			if ($position == 'all' OR (!empty($params['contentTemplate']) AND $params['contentTemplate'] == 'forum_list' )) {
				// 2 cases:
				// position set to 'all': we should work on all pages...
				// position set to something else: forum_list ONLY
				$template->preloadTemplate('VietXfAdvStats_wrapper');
				define(self::TEMPLATE_READY_FLAG, true);
				
			}elseif($position_portal){
				if(!empty($params['contentTemplate']) AND $params['contentTemplate'] == 'EWRporta_Portal' ){
					$template->preloadTemplate('VietXfAdvStats_wrapper');
					define(self::TEMPLATE_READY_FLAG, true);
				}
			}
		}
		}
	}
	
	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template) {
		$model = XenForo_Model::create('VietXfAdvStats_Model_GetUserGroup');
		if($model->checkGroup()){
			if ($templateName == 'PAGE_CONTAINER' AND defined(self::POSITION_READY_FLAG) AND defined(self::TEMPLATE_READY_FLAG)) {
				$ourTemplate = $template->create('VietXfAdvStats_wrapper', $template->getParams());
				$rendered = VietXfAdvStats_Renderer::renderWrapper($ourTemplate);
				
				$content = str_replace(self::POSITION_PLACE_HOLDER, $rendered, $content);
			}
		}
	}
}
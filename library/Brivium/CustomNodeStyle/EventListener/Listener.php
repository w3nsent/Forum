<?php
class Brivium_CustomNodeStyle_EventListener_Listener extends Brivium_BriviumHelper_EventListeners
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'forum_edit':
			case 'page_edit':
			case 'link_forum_edit':
			case 'category_edit':
				$template->preloadTemplate('BRCNS_admin_node_edit');
	            break;
		}
	}

	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$params = $template->getParams();
		switch ($hookName) {
			case 'forum_edit_basic_information':
				if(!empty($params['forum'])){
					$nodeId = $params['forum']['node_id'];
				}
			case 'admin_page_edit_panes':
				if(empty($nodeId) && !empty($params['page'])){
					$nodeId = $params['page']['node_id'];
				}
			case 'admin_link_forum_edit':
				if(empty($nodeId) && !empty($params['link'])){
					$nodeId = $params['link']['node_id'];
				}
			case 'admin_category_edit':
				if(empty($nodeId) && !empty($params['category'])){
					$nodeId = $params['category']['node_id'];
				}
				if(!empty($nodeId)){
					$nodeModel = self::getModelFromCache('XenForo_Model_Node');
					$node = $nodeModel->getNodeById($nodeId);
					$node = $nodeModel->brcnsPrepareNode($node);
					if(!empty($node['brcnsIconData'])){
						$params['brcnsIconData'] = $node['brcnsIconData'];
					}
					if(!empty($node['brcnsStyleData'])){
						$params['brcnsStyleData'] = $node['brcnsStyleData'];
					}
					$params['node'] = $node;
				}
				$newTemplate = $template->create('BRCNS_admin_node_edit', $params);
				$contents .= $newTemplate->render();
				break;
		}
	}

	public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Template_Helper_Core::$helperCallbacks['nodeicon'] = array('Brivium_CustomNodeStyle_EventListener_Helpers', 'helperGetCustomIcon');
	}

	public static function templatePostRender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch($templateName){
			case 'node_list.css':
				$nodeModel = self::getModelFromCache('XenForo_Model_Node');

				$options = XenForo_Application::getOptions();
				$nodes = $nodeModel->getAllNodes();
				$rootNodes = array();
				foreach ($nodes as $nodeId => $node) {
					$node = $nodeModel->brcnsPrepareNode($node);
					if(empty($node['parent_node_id']) && strtolower($node['node_type_id']) == 'category'){
						$rootNodes[$nodeId] = $node;
						//unset($nodes[$nodeId]);
						//continue;
					}
					$nodes[$nodeId] = $node;
				}

				$dimensions1 = $options->BRCNS_iconLv1Dimension;
				if($dimensions1['width'] && ($dimensions1['width'] < $dimensions1['height']) || !$dimensions1['height']){
					$fontSize1 = $dimensions1['width'];
				}else{
					$fontSize1 = $dimensions1['height'];
				}

				$dimensions2 = $options->BRCNS_iconLv2Dimension;
				if($dimensions2['width'] && ($dimensions2['width'] < $dimensions2['height']) || !$dimensions2['height']){
					$fontSize2 = $dimensions2['width'];
				}else{
					$fontSize2 = $dimensions2['height'];
				}

				$dimensions3 = $options->BRCNS_iconLv3Dimension;
				if($dimensions3['width'] && ($dimensions3['width'] < $dimensions3['height']) || !$dimensions3['height']){
					$fontSize3 = $dimensions3['width'];
				}else{
					$fontSize3 = $dimensions3['height'];
				}

				$param = array(
					'nodes'     => $nodes,
					'rootNodes' => $rootNodes,
					'fontSize1' => $fontSize1?round($fontSize1 * 0.8):0,
					'fontSize2' => $fontSize2?round($fontSize2 * 0.8):0,
					'fontSize3' => $fontSize3?round($fontSize3 * 0.8):0,
				);

				$newTemplate = $template->create('BRCNS_custom_node_style.css', $template->getParams());
				$newTemplate->setParams($param);
				$content = $content .$newTemplate->render();
				break;
		}
	}
}

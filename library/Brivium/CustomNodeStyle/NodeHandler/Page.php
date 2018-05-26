<?php

class Brivium_CustomNodeStyle_NodeHandler_Page extends XFCP_Brivium_CustomNodeStyle_NodeHandler_Page
{
	/*public function renderNodeForTree(XenForo_View $view, array $node, array $permissions,
		array $renderedChildren, $level
	)
	{
		if (!empty($node['node_id']))
		{
			$page = $this->_getPageModel()->getPageById($node['node_id']);
			$node = array_merge($node, $page);
		}
		return parent::renderNodeForTree($view, $node, $permissions, $renderedChildren, $level);
	}*/
}

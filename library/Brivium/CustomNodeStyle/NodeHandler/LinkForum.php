<?php

class Brivium_CustomNodeStyle_NodeHandler_LinkForum extends XFCP_Brivium_CustomNodeStyle_NodeHandler_LinkForum
{
	/*public function renderNodeForTree(XenForo_View $view, array $node, array $permissions,
		array $renderedChildren, $level
	)
	{
		if (!empty($node['node_id']))
		{
			$link = $this->_getLinkForumModel()->getLinkForumById($node['node_id']);
			if(is_array($link))
			{
				$node = array_merge($node, $link);
			}
		}
		return parent::renderNodeForTree($view, $node, $permissions, $renderedChildren, $level);
	}*/
}

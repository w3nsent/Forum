<?php

class SvgColorsGroups_Node extends XFCP_SvgColorsGroups_Node
{
	public function getNodeDataForListDisplay($parentNode, $displayDepth, array $nodePermissions = null)
	{
		$nodeData = parent::getNodeDataForListDisplay($parentNode, $displayDepth, $nodePermissions);

		if (!empty($nodeData['nodesGrouped']))
		{			
			$nodeData['nodesGrouped'] = $this->_addDisplayStyleGroupdId($nodeData['nodesGrouped']);
							
			return $nodeData;
		}
		else
		{
			return array();
		}
	}
	
	protected function _addDisplayStyleGroupdId(array $nodes)
	{
		$styles = array();

		foreach($nodes as &$depthNodes)
		{
			foreach ($depthNodes AS &$node)
			{
				if (isset($node['lastPost']['user_id']))
				{
					$styles[$node['lastPost']['user_id']] = array();
				}
			}
		}
		
		if (!empty($styles))
		{
			$styles = $this->_getDisplayStyleGroupdId(array_keys($styles));

			foreach($nodes as &$depthNodes)
			{
				foreach ($depthNodes AS &$node)
				{
					if (($node['node_type_id'] == "Forum" OR $node['node_type_id'] == "Category") and isset($node['lastPost']['user_id'])
						and !empty($styles[$node['lastPost']['user_id']]))
					{
						$node['lastPost']['display_style_group_id'] = $styles[$node['lastPost']['user_id']]['display_style_group_id'];
					}
				}
			}
		}

		return $nodes;
	}
	
	protected function _getDisplayStyleGroupdId($userIds)
	{
		if (!$userIds) {
			return array();
		}

		return $this->fetchAllKeyed('
					SELECT user.user_id, user.display_style_group_id
					FROM xf_user AS user
					WHERE user.user_id IN (' . $this->_getDb()->quote($userIds) . ')
			', 'user_id');
	}
}

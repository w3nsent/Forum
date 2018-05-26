<?php

class Brivium_ModernStatistic_Model_Node extends XFCP_Brivium_ModernStatistic_Model_Node
{
	public function getUnviewableNodeList(array $nodePermissions = null, $listView = false)
	{
		$nodes = $this->getAllNodes(false, $listView);
		if (!$nodes)
		{
			return array();
		}

		if (!is_array($nodePermissions))
		{
			$nodePermissions = $this->getNodePermissionsForPermissionCombination();
		}

		$nodeHandlers = $this->getNodeHandlersForNodeTypes(
			$this->getUniqueNodeTypeIdsFromNodeList($nodes)
		);

		return $this->getUnviewableNodesFromNodeList($nodes, $nodeHandlers, $nodePermissions);
	}

	public function getUnviewableNodesFromNodeList(array $nodes, array $nodeHandlers, array $nodePermissions)
	{
		$unviewable = array();
		foreach ($nodes as $nodeId => $node)
		{
			$handler = $nodeHandlers[$node['node_type_id']];
			$permissions = (isset($nodePermissions[$node['node_id']]) ? $nodePermissions[$node['node_id']] : array());

			if (!$handler->isNodeViewable($node, $permissions))
			{
				$unviewable[$nodeId] = $node;
			}
		}

		return $unviewable;
	}
}

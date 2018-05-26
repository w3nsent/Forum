<?php
class Brivium_AdvancedReputationSystem_Route_PrefixAdmin_Reputations extends XenForo_Route_PrefixAdmin_Nodes
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'reputation_id');
		return $router->getRouteMatch ( 'Brivium_AdvancedReputationSystem_ControllerAdmin_Reputations', $action,'BRARS_reputationsList');
	}
	
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'reputation_id');
	}
}
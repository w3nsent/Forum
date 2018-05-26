<?php
class Brivium_AdvancedReputationSystem_Route_Prefix_Reputations implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'reputation_id');
		return $router->getRouteMatch ( 'Brivium_AdvancedReputationSystem_ControllerPublic_Reputation', $action,'forums');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'reputation_id');
	}
}
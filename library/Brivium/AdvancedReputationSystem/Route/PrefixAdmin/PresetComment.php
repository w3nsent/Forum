<?php
class Brivium_AdvancedReputationSystem_Route_PrefixAdmin_PresetComment extends XenForo_Route_PrefixAdmin_Nodes
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'preset_id');
		return $router->getRouteMatch ( 'Brivium_AdvancedReputationSystem_ControllerAdmin_PresetComment', $action,'BRARS_presetComment');
	}
	
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'preset_id', 'title');
	}
}
<?php
class Brivium_ModernStatistic_Route_Prefix_ModernStatistic implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Brivium_ModernStatistic_ControllerPublic_ModernStatistic', $routePath, 'BR_ModernStatistic');
	}
}

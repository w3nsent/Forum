<?php

class BS_BRMSStick_Route_Prefix_BRMSStick implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('BS_BRMSStick_ControllerPublic_BRMSStick', $routePath);
	}
}
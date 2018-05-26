<?php

class Andy_WeeklyDigest_Route_Prefix_WeeklyDigest implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Andy_WeeklyDigest_ControllerPublic_WeeklyDigest', $routePath);
	}
}
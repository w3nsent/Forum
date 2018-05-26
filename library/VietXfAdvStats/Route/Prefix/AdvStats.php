<?php
class VietXfAdvStats_Route_Prefix_AdvStats implements XenForo_Route_Interface {
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router) {
		return $router->getRouteMatch('VietXfAdvStats_ControllerPublic_AdvStats', $routePath, 'forums');
	}
}
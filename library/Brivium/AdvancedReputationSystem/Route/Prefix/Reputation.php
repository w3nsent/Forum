<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Route_Prefix_Reputation implements XenForo_Route_Interface
{
	/**
	 * Match a specific route for an already matched prefix.
	 *
	 * @see XenForo_Route_Interface::match()
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('Brivium_AdvancedReputationSystem_ControllerPublic_Index', 'index', 'forums');
	}
}
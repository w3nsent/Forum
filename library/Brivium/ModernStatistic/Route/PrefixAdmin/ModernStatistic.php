<?php

/**
 * Route prefix handler for hread prefixes in the admin control panel.
 */
class Brivium_ModernStatistic_Route_PrefixAdmin_ModernStatistic implements XenForo_Route_Interface
{
	/**
	 * Match a specific route for an already matched prefix.
	 *
	 * @see XenForo_Route_Interface::match()
	 */
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'modern_statistic_id');
		return $router->getRouteMatch('Brivium_ModernStatistic_ControllerAdmin_ModernStatistic', $action, 'BRMS_Statistics');
	}

	/**
	 * Method to build a link to the specified page/action with the provided
	 * data and params.
	 *
	 * @see XenForo_Route_BuilderInterface
	 */
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'modern_statistic_id', 'title');
	}
}

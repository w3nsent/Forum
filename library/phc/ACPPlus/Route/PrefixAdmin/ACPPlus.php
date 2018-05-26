<?php

class phc_ACPPlus_Route_PrefixAdmin_ACPPlus implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, null);
        return $router->getRouteMatch('phc_ACPPlus_ControllerAdmin_ACPPlus', $action, 'acpp_db');
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'acpp_db');
    }
}
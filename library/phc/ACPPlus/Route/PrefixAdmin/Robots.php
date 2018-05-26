<?php

class phc_ACPPlus_Route_PrefixAdmin_Robots implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithIntegerParam($routePath, $request, 'spider_id');
        return $router->getRouteMatch('phc_ACPPlus_ControllerAdmin_Robots', $action, 'robots');
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'spider_id', 'title');
    }
}
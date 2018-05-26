<?php

class phc_ACPPlus_ControllerAdmin_Robots extends XenForo_ControllerAdmin_Abstract
{
    public function actionIndex()
    {
        $robots = $this->_getACPPlusModel()->fetchAllRobots();

        $params = array(
            'robots' => $robots,
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_Robots', 'acpp_robots_list', $params);
    }

    public function actionAdd()
    {
        $params = array(
            //'xf_controller' => phc_ACPPlus_Helper_ACPPlus::getXFControllerListOption(),
            'routes' => $this->_generateRoutes(),
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_Robots_Add', 'acpp_robot_edit', $params);
    }

    public function actionEdit()
    {
        $spiderId = $this->_input->filterSingle('spider_id', XenForo_Input::UINT);

        $robot = $this->_getACPPlusModel()->fetchRobotById($spiderId);

        if(!$robot)
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_requested_robot_not_found'), 404));

        $robot['routes'] = @preg_split('/(\r\n|\n|\r)+/', $robot['routes'], -1, PREG_SPLIT_NO_EMPTY);

        $params = array(
            //'xf_controller' => phc_ACPPlus_Helper_ACPPlus::getXFControllerListOption($robot),
            'routes' => $this->_generateRoutes($robot),
            'robot' => $robot,
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_Robots_Edit', 'acpp_robot_edit', $params);
    }

    public function actionSave()
    {
        $spiderId = $this->_input->filterSingle('spider_id', XenForo_Input::UINT);

        if($spiderId)
        {
            $robot = $this->_getACPPlusModel()->fetchRobotById($spiderId);

            if(!$robot)
                throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_requested_robot_not_found'), 404));
        }

        if($this->isConfirmedPost())
        {
            $title = $this->_input->filterSingle('title', XenForo_Input::STRING);
            $robotId = $this->_input->filterSingle('robot_id', XenForo_Input::STRING);
            $contact = $this->_input->filterSingle('contact', XenForo_Input::STRING);
            $active = $this->_input->filterSingle('active', XenForo_Input::BOOLEAN);

            $block = $this->_input->filterSingle('block', XenForo_Input::BOOLEAN);
            $routes = $this->_input->filterSingle('routes', XenForo_Input::ARRAY_SIMPLE);
            $routesWhitelist = $this->_input->filterSingle('routes_whitelist', XenForo_Input::STRING);

            $routes = @implode("\n", $routes);

            if(!$title)
                return $this->responseError(new XenForo_Phrase('acpp_please_enter_a_title'));

            if(!$robotId)
                return $this->responseError(new XenForo_Phrase('acpp_please_enter_a_robot_match'));

            $robotDw = XenForo_DataWriter::create('phc_ACPPlus_DataWriter_Robots');

            if($spiderId)
                $robotDw->setExistingData($spiderId);

            $robotDw->set('title', $title);
            $robotDw->set('robot_id', $robotId);
            $robotDw->set('contact', $contact);
            $robotDw->set('active', $active);
            $robotDw->set('block', $block);
            $robotDw->set('routes', $routes);
            $robotDw->set('routes_whitelist', $routesWhitelist);

            $robotDw->save();
        }

        phc_ACPPlus_Helper_ACPPlus::generateSpiderXML();

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('robots')
        );
    }

    public function actionDelete()
    {
        $spiderId = $this->_input->filterSingle('spider_id', XenForo_Input::UINT);

        $robot = $this->_getACPPlusModel()->fetchRobotById($spiderId);

        if(!$robot)
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_requested_robot_not_found'), 404));

        if($this->isConfirmedPost())
        {
            $robotDw = XenForo_DataWriter::create('phc_ACPPlus_DataWriter_Robots');
            $robotDw->setExistingData($robot);
            $robotDw->delete();

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('robots')
            );
        }

        $params = array(
            'robot' => $robot,
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_Robots_Delete', 'acpp_robot_delete', $params);
    }

    public function actionToggle()
    {
        return $this->_getToggleResponse(
            $this->_getACPPlusModel()->fetchAllRobots(),
            'phc_ACPPlus_DataWriter_Robots',
            'robots',
            'block',
            'spider_id_');
    }

    protected function _generateRoutes($robot = array())
    {
        $routesArray = [];

        $routes = $this->_getACPPlusModel()->fetchPublicRoutes();

        foreach($routes as $route)
        {
            $addonId = $route['addon_id'];

            if(!isset($routesArray[$addonId]))
            {
                $routesArray[$addonId]['addon_id'] = $route['addon_id'];
                $routesArray[$addonId]['title'] = $route['title'];
            }

            //$routePrefix = ($route['replace_route'] ? $route['replace_route'] : $route['original_prefix']);

            $routesArray[$addonId]['routes'][] = array(
                'label' => ($route['original_prefix'] == 'index' ? 'home' : $route['original_prefix']),
                'value' => $route['original_prefix'],
                'selected' => (isset($robot['routes']) && in_array($route['original_prefix'], $robot['routes']) ? true : false),
            );
        }

        return $routesArray;
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
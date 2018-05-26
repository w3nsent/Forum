<?php

class phc_ACPPlus_Extend_XenForo_ControllerHelper_ACPPlus extends XenForo_ControllerHelper_Abstract
{
    public function getWrapper($selectedGroup, $selectedLink, XenForo_ControllerResponse_View $subView)
    {
        $viewParams = array(
            'selectedGroup' => $selectedGroup,
            'selectedLink' => $selectedLink,
            'selectedKey' => "$selectedGroup/$selectedLink",
        );

		$wrapper = $this->_controller->responseView('XenForo_ViewAdmin_Log_ServerErrorView', 'acpp_error_wrapper', $viewParams);
		$wrapper->subView = $subView;

		return $wrapper;
	}

	public static function wrap(XenForo_Controller $controller, $selectedGroup, $selectedLink, XenForo_ControllerResponse_View $subView)
	{
		$class = XenForo_Application::resolveDynamicClass(__CLASS__);
		$helper = new $class($controller);
		return $helper->getWrapper($selectedGroup, $selectedLink, $subView);
	}
}
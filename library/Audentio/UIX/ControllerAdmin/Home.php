<?php

class Audentio_UIX_ControllerAdmin_Home extends XFCP_Audentio_UIX_ControllerAdmin_Home
{
    public function actionIndex()
    {
        $response = parent::actionIndex();
        $visitor = XenForo_Visitor::getInstance();
        $xenOptions = XenForo_Application::get('options')->getOptions();
        if ($visitor->hasAdminPermission('uix_styles')) {
            $outdatedStyles = count($this->getModelFromCache('XenForo_Model_Style')->getOutOfDateAudentioStyles());
        } else {
            $outdatedStyles = 0;
        }

        $viewParams = array(
            'outdatedStyles' => $outdatedStyles,
        );

        $viewParams = array_merge($viewParams, $response->params);

        return $this->responseView('XenForo_ViewAdmin_Home', 'home', $viewParams);
    }
}

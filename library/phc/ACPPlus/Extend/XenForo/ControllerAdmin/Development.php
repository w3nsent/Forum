<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Development extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Development
{
	public function actionIndex()
	{
        $listType = $this->_input->filterSingle('listType', XenForo_Input::STRING);

        if(!$listType)
            $listType = 'active';

        $addOnModel = $this->getModelFromCache('XenForo_Model_AddOn');

        $viewParams = array(
            'addOns' => $addOnModel->getAllAddOns($listType),
            'canAccessDevelopment' => $addOnModel->canAccessAddOnDevelopmentAreas(),
            'listType' => $listType,
            'tabNav' => 'development',
        );

        return $this->responseView('XenForo_ViewAdmin_Development_Splash', 'development_splash', $viewParams);
	}
}
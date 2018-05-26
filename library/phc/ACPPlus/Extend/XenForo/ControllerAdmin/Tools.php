<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Tools extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Tools
{
    public function actionDebugToggle()
    {
        $this->_assertPostOnly();

        $this->assertAdminPermission('enableDebugMode');

        XenForo_Application::setSimpleCacheData('acpp_debug_mode',
            $this->_input->filterSingle('enable_debug_mode', XenForo_Input::UINT)
        );

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
            XenForo_Link::buildAdminLink('index')
        );
    }

    public function actionProtectAcpInfo()
    {
        $viewParams = array(
        );

        return $this->responseView('phc_ACPPlus_Extend_XenForo_ViewAdmin_Tools_ProtectedAdminInfo', 'acpp_protect_acp_info', $viewParams);
    }


    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
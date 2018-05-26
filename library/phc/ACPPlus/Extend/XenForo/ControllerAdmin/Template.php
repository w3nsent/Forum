<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Template extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Template
{
    public function actionTogglefavorite()
    {
        $favoritOn = 'styles/phc/acpplus/favorit_on.png';
        $favoritOff = 'styles/phc/acpplus/favorit_off.png';

        $view = $this->responseView();
        $this->_assertPostOnly();

        if(!XenForo_Visitor::getInstance()->hasAdminPermission('style'))
            return $view;

        $tId = $this->_input->filterSingle('tid', XenForo_Input::UINT);

        $status = $this->_getACPPlusModel()->toggleTempalteFavorit($tId);

        $view->jsonParams = array(
            'status' => $status,
            'favorit' => ($status == true ? $favoritOn : $favoritOff),
        );

        return $view;
    }

    public function actionRemoveFavorite()
    {
        if(!XenForo_Visitor::getInstance()->hasAdminPermission('style'))
            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('appearance')
            );

        $tId = $this->_input->filterSingle('tid', XenForo_Input::UINT);

        $this->_getACPPlusModel()->toggleTempalteFavorit($tId);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('appearance')
        );
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
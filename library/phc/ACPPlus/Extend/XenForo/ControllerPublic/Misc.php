<?php

class phc_ACPPlus_Extend_XenForo_ControllerPublic_Misc extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerPublic_Misc
{
    public function actionCheckacprotected()
    {
        $view = $this->responseView();

        $acppSecurity = new phc_ACPPlus_Helper_Security();

        $view->jsonParams = array(
            'http_code' => $acppSecurity->checkACPProtected()
        );
        return $view;
    }

    public function actionBanUser()
    {
        $hash = $this->_input->filterSingle('hash', XenForo_Input::STRING);

        if(!$hash)
            throw $this->getNoPermissionResponseException();

        $data = $this->_getACPPlusModel()->fetchLogByHash($hash);

        if(!empty($data['ip']))
        {
            if($this->_getDBHModel()->writeIpToHtaccess($data['ip']))
            {
                throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_user_successfully_banned')));
            }
            else
            {
                throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_user_successfully_banned_failed')));
            }
        }

        throw $this->getNoPermissionResponseException();
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }

    /**
     * @return phc_ACPPlus_Model_DenyByHtaccess
     */
    protected function _getDBHModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_DenyByHtaccess');
    }
}
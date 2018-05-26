<?php

class phc_ACPPlus_Extend_XenForo_ControllerPublic_Online extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerPublic_Online
{
    public function actionIndex()
    {
        $res = parent::actionIndex();

        if(!empty($res->params['onlineUsers']))
        {
            foreach ($res->params['onlineUsers'] as &$onlineUser)
            {
                if($onlineUser['robot_key'])
                {
                    $onlineUser['robotInfo'] = phc_ACPPlus_Helper_ACPPlus::getRobotInfo($onlineUser['robot_key']);
                }
            }
        }

        return $res;
    }

    public function actionUserIp()
    {
        $res = parent::actionUserIp();

        $res->params['canUseDenyByHtaccess'] = $this->_getDBHModel()->canUseDenyByHtaccess();

        return $res;
    }

    public function actionGuestIp()
    {
        $res = parent::actionGuestIp();

        $res->params['canUseDenyByHtaccess'] = $this->_getDBHModel()->canUseDenyByHtaccess();

        return $res;
    }

    public function actionDenybyhtaccess()
    {
        $DBHModel = $this->_getDBHModel();

        if(!$DBHModel->canUseDenyByHtaccess())
            throw $this->getNoPermissionResponseException();

        $ip = $this->_input->filterSingle('ip', XenForo_Input::STRING);

        if($DBHModel->ip_check($ip))
        {
            $DBHModel->writeIpToHtaccess($ip);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('online'),
                new XenForo_Phrase('acpp_ip_successfully_added')
            );
        }
        else
        {
            return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
        }
    }

    /**
     * @return phc_ACPPlus_Model_DenyByHtaccess
     */
    protected function _getDBHModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_DenyByHtaccess');
    }
}
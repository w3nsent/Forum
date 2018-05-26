<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Login extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Login
{
    public function actionForm()
    {
        if(XenForo_Application::getOptions()->acpp_IpFirewall)
        {
            $acppSecurity = new phc_ACPPlus_Helper_Security();
            $ip = $acppSecurity->fetchUserIp();
            $acppSecurity->getIpType($ip);


            if(!$ip)
                return $this->responseError(new XenForo_Phrase('acpp_ip_not_allowed'));

            $loginAllowed = false;

            //Check IP Range
            $acpIpInRange = new phc_ACPPlus_Helper_IpInRange();

            $allowdIps = preg_split('#\s+|,|;|(\r\n|\n|\r)#s', XenForo_Application::getOptions()->acpp_allowedIPAddresses, -1, PREG_SPLIT_NO_EMPTY);
            $eMails = preg_split('#\s+|,|;|(\r\n|\n|\r)#s', XenForo_Application::getOptions()->acpp_loginAlertEmails, -1, PREG_SPLIT_NO_EMPTY);

            if($allowdIps && is_array($allowdIps))
            {
                foreach($allowdIps as $allowdIp)
                {
                    $loginAllowed = $acpIpInRange->checkIfIpInRange($ip, $allowdIp, $acppSecurity->getIpType($ip));

                    if($loginAllowed)
                        break;
                }

                if(!$loginAllowed)
                {
                    if(XenForo_Application::getOptions()->acpp_IPNotAllowed)
                    {
                        $this->_getACPPlusModel()->logLogins($ip, 0, '', 'ip_not_allowed', 'acp');
                        $acppSecurity->sendLoginAlertMail(array(), $ip, 'ip_not_allowed', $eMails);
                    }

                    return $this->responseError(new XenForo_Phrase('acpp_ip_not_allowed'));
                }
            }
        }

        return parent::actionForm();
    }

    public function actionLogin()
    {
        $userModel = $this->_getUserModel();

        $sendMail = false;
        $login = false;

        $input = $this->_input->filter(array(
            'login' => XenForo_Input::STRING,
            'password' => XenForo_Input::STRING
        ));

        $eMails = preg_split('#\s+|,|;|(\r\n|\n|\r)#s', XenForo_Application::getOptions()->acpp_loginAlertEmails, -1, PREG_SPLIT_NO_EMPTY);

        if($eMails && is_array($eMails))
            $sendMail = true;


        $acppSecurity = new phc_ACPPlus_Helper_Security();
        $ip = $acppSecurity->fetchUserIp();

        $user = $userModel->getUserByNameOrEmail($input['login']);

        // Log User not Exists
        if(!$user)
        {
            $this->_getACPPlusModel()->logLogins($ip, $user['user_id'], $input['login'], 'user_not_exists', 'acp');

            if($sendMail && !$user['user_id'] && XenForo_Application::getOptions()->acpp_userNotFound)
                $acppSecurity->sendLoginAlertMail($input, $ip, 'user_not_found', $eMails);
        }

        // Log password failed
        if($user)
        {
            $authentication = $userModel->getUserAuthenticationObjectByUserId($user['user_id']);
            if(!$authentication || !$authentication->authenticate($user['user_id'], $input['password']))
            {
                $this->_getACPPlusModel()->logLogins($ip, $user['user_id'], $input['login'], 'password_incorrect', 'acp');
            }
            else
            {
                $login = true;
            }

        }

        if($sendMail && $user && $login)
        {
            // Alert User is Not Admin
            if(!$user['is_admin'] && XenForo_Application::getOptions()->acpp_userNotAdmin)
            {
                $this->_getACPPlusModel()->logLogins($ip, $user['user_id'], $input['login'], 'no_admin', 'acp');
                $acppSecurity->sendLoginAlertMail($input, $ip, 'user_not_admin', $eMails);
            }

            // Log the Login
            if($user['user_id'] && $user['is_admin'])
            {
                $input['hash'] = $this->_getACPPlusModel()->logLogins($ip, $user['user_id'], $input['login'], 'success', 'acp');
            }

            // Alert user Loged in
            if($user['user_id'] && $user['is_admin'] && XenForo_Application::getOptions()->acpp_loginSuccess)
            {
                $acppSecurity->sendLoginAlertMail($input, $ip, 'user_login_success', $eMails);
            }
        }

        return parent::actionLogin();
    }

    /**
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return parent::_getUserModel();
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
<?php

class phc_ACPPlus_Extend_XenForo_ControllerPublic_Login extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerPublic_Login
{
    public function actionLogin()
    {
        $data = $this->_input->filter(array(
            'login' => XenForo_Input::STRING,
            'password' => XenForo_Input::STRING,
            'remember' => XenForo_Input::UINT,
            'register' => XenForo_Input::UINT,
            'cookie_check' => XenForo_Input::UINT,
            'postData' => XenForo_Input::JSON_ARRAY
        ));

        if(!$data['register'])
        {
            $userModel = $this->_getUserModel();

            $acppSecurity = new phc_ACPPlus_Helper_Security();
            $ip = $acppSecurity->fetchUserIp();

            $user = $userModel->getUserByNameOrEmail($data['login']);

            // Log User not Exists
            if(!$user)
                $this->_getACPPlusModel()->logLogins($ip, $user['user_id'], $data['login'], 'user_not_exists', 'front');

            // Log password failed
            if($user)
            {
                $authentication = $userModel->getUserAuthenticationObjectByUserId($user['user_id']);
                if(!$authentication || !$authentication->authenticate($user['user_id'], $data['password']))
                    $this->_getACPPlusModel()->logLogins($ip, $user['user_id'], $data['login'], 'password_incorrect', 'front');
            }
        }

        return parent::actionLogin();
    }

    public function completeLogin($userId, $remember, $redirect, array $postData = array())
    {
        $acppSecurity = new phc_ACPPlus_Helper_Security();
        $ip = $acppSecurity->fetchUserIp();

        $user = $this->_getUserModel()->getUserById($userId);

        $this->_getACPPlusModel()->logLogins($ip, $userId, $user['username'], 'success', 'front');

        return parent::completeLogin($userId, $remember, $redirect, $postData);
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
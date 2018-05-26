<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Banning extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Banning
{
    /*
     * MassEMails
     */
    public function actionDenybyhtaccess()
    {
        if(phc_ACPPlus_Helper_ACPPlus::getWebServer() != 'apache')
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_php_is_not_running_under_apache')));

        $DBHModel = $this->_getDBHModel();

        if(!$DBHModel->readIpsFromHtaccess())
            return $this->responseError('File is not exists or not writeable!');

        $viewParams = array(
            'ips' => $DBHModel->_denys,
        );

        return $this->responseView('phc_ACPPlus_Extend_XenForo_AdminView_Banning', 'acpp_ban_ip_list', $viewParams);
    }

    public function actionDenybyhtaccessEdit()
    {
        if(phc_ACPPlus_Helper_ACPPlus::getWebServer() != 'apache')
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_php_is_not_running_under_apache')));

        $DBHModel = $this->_getDBHModel();

        $oldIp = $this->_input->filterSingle('ip', XenForo_Input::STRING);
        $newip = $this->_input->filterSingle('newip', XenForo_Input::STRING);

        if($this->isConfirmedPost())
        {
            if(!$DBHModel->ip_check($newip))
                return $this->responseError(new XenForo_Phrase('acpp_this_is_not_a_valid_ip_address'));

            $DBHModel->writeIpToHtaccess($newip, $oldIp);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('banning/denybyhtaccess'),
                new XenForo_Phrase('acpp_ip_successfully_added')
            );
        }

        $viewParams = array(
            'ip' => $oldIp,
            'newip' => ($newip == '' ? $oldIp : $newip),
        );
        return $this->responseView('phc_ACPPlus_Extend_XenForo_AdminView_Banning', 'acpp_ban_ip_list_edit', $viewParams);
    }


    public function actionDenybyhtaccessDelete()
    {
        if(phc_ACPPlus_Helper_ACPPlus::getWebServer() != 'apache')
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_php_is_not_running_under_apache')));

        $DBHModel = $this->_getDBHModel();

        $ip = $this->_input->filterSingle('ip', XenForo_Input::STRING);

        if(!$DBHModel->ip_check($ip))
            return $this->responseError(new XenForo_Phrase('acpp_this_is_not_a_valid_ip_address'));

        if(!$DBHModel->removeIpFromHtaccess($ip))
            return $this->responseError('File is not exists or not writeable!');


        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('banning/denybyhtaccess'),
            new XenForo_Phrase('acpp_ip_successfully_removed')
        );
    }

    /*
     * MassEMails
     */
    public function actionEmailsMassadd()
    {
        $this->_assertPostOnly();

        $emails = $this->_input->filterSingle('emails', XenForo_Input::STRING);
        $emails_array = preg_split('#\s+|,|;|(\r\n|\n|\r)#s', $emails, -1, PREG_SPLIT_NO_EMPTY);

        if($emails_array)
        {
            foreach($emails_array as $email)
            {
                $this->_getBanningModel()->banEmail($email);
            }
        }

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('banning/emails')
        );
    }

    public function actionEmailsExport()
    {
        $this->_routeMatch->setResponseType('xml');

        $emails = $this->_getBanningModel()->getBannedEmails();

        $viewParams = array(
            'xml' => $this->_getMassBanEMailsModel()->getXML($emails),
            'fileName' => 'mass_email_ban',
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_Export', '', $viewParams);
    }

    public function actionEmailsImport()
    {
        if($this->isConfirmedPost())
        {
            $upload = XenForo_Upload::getUploadedFile('upload');

            if (!$upload)
            {
                return $this->responseError(new XenForo_Phrase('provided_file_was_not_valid_xml_file'));
            }

            $this->_getMassBanEMailsModel()->insertXML(
                $this->getHelper('Xml')->getXmlFromFile($upload)
            );

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('banning/emails')
            );
        }
        else
        {
            return $this->responseView('phc_MassBanEMails_Extend_XenForo_ViewAdmin_Import', 'acpp_massbanemails_import', array());
        }
    }

    /**
     * @return phc_ACPPlus_Model_DenyByHtaccess
     */
    protected function _getDBHModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_DenyByHtaccess');
    }

    /**
     * @return phc_ACPPlus_Model_MassBanEMails
     */
    protected function _getMassBanEMailsModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_MassBanEMails');
    }
}
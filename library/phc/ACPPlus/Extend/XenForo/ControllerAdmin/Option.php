<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Option extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Option
{
    public function actionOptionsExport()
    {
        if(!XenForo_Application::debugMode())
        {
            return $this->responseNoPermission();
        }

        $groupId = $this->_input->filterSingle('group_id', XenForo_Input::STRING);
        $exportIds = $this->_input->filterSingle('exportIds', XenForo_Input::ARRAY_SIMPLE);

        $optionModel = $this->_getOptionModel();
        $optionExInModel = $this->_getOptionExInModel();

        if($groupId || ($exportIds && $this->isConfirmedPost()))
        {
            if($groupId)
            {
                $exportIds[] = $groupId;
            }

            $groups = $optionModel->getOptionGroupsByIds($exportIds);

            $optionGroups = array();
            foreach($groups as $group)
            {
                $optionGroups[$group['group_id']] = $optionModel->getOptionsInGroup($group['group_id']);
            }

            $xmlData = $optionExInModel->getXML($optionGroups);

            $this->_routeMatch->setResponseType('xml');

            $filename = 'Options_MassExport';
            if($groupId)
                $filename = 'Options_' . $groupId;

            $viewParams = array(
                'xml' => $xmlData,
                'fileName' => $filename,
            );

            return $this->responseView('phc_ACPPlus_ViewAdmin_Export', '', $viewParams);
        }
        else
        {
            $groups = $optionModel->getOptionGroupList(array('join' => XenForo_Model_Option::FETCH_ADDON));
            $groups = $optionModel->prepareOptionGroups($groups,false);

            $params = array(
                'groups' => $groups,
            );

            return $this->responseView('phc_ACPPlus_ViewAdmin_Import', 'acpp_options_export', $params);
        }
    }

    public function actionOptionsImport()
    {
        if(!XenForo_Application::debugMode())
        {
            return $this->responseNoPermission();
        }

        if($this->isConfirmedPost())
        {
            $upload = XenForo_Upload::getUploadedFile('upload');

            if (!$upload)
            {
                return $this->responseError(new XenForo_Phrase('provided_file_was_not_valid_xml_file'));
            }

            $errors = array();
            $status = $this->_getOptionExInModel()->insertXML($this->getHelper('Xml')->getXmlFromFile($upload), $errors);

            if($status)
            {
                switch($status)
                {
                    case 'fail':
                        return $this->responseError(new XenForo_Phrase('acpp_provided_file_was_not_valid_option_xml_file'));
                        break;

                    case 'notOption':
                        return $this->responseError(new XenForo_Phrase('acpp_provided_file_was_not_valid_option_xml_file_for_this_option'));
                        break;

                    case 'errors':
                        if($errors)
                        {
                            $error_messsage = phc_ACPPlus_Model_OptionsExIn::generateImportErrors($errors);
                            return $this->responseError(
                                new XenForo_Phrase('acpp_options_import_errors', array('error_messages' => $error_messsage), false)
                            );
                        }
                        break;
                }
            }

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
                XenForo_Link::buildAdminLink('options')
            );
        }
        else
        {
            $params = array(
            );

            return $this->responseView('phc_ACPPlus_ViewAdmin_Import', 'acpp_options_import', $params);
        }
    }


    public function actionPositionReset()
    {
        $acpPlusModel = $this->_getACPPlusModel();

        $order = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);

        if($order)
        {
            $acpPlusModel->updatePositionen($order, 'options');
        }

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('options')
        );
    }

    public function actionAbcOrder()
    {
        $acpPlusModel = $this->_getACPPlusModel();
        $optionModel = $this->_getOptionModel();

        $groups = $optionModel->prepareOptionGroups(
            $optionModel->getOptionGroupList(),
            true);

        $newGroups = array();
        foreach($groups as $group)
        {
            $newGroups[$group['group_id']] = $group['title']->render();
        }

        $groups = array();
        asort($newGroups, SORT_STRING);

        foreach($newGroups as $key => $value)
        {
            $groups[] = $key;
        }

        if($groups)
        {
            $acpPlusModel->updatePositionen($groups, 'options');
        }

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('options')
        );
    }

    public function actionDefaultOrder()
    {
        $db = XenForo_Application::getDb();
        $db->query('UPDATE xf_option_group SET display_order = default_display_order');

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('options')
        );
    }

    public function actionSaveGroup()
    {
        $GLOBALS['acppGroupUpdate'] = true;
        return parent::actionSaveGroup();
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }

    /**
     * @return XenForo_Model_Option
     */
    protected function _getOptionModel()
    {
        return $this->getModelFromCache('XenForo_Model_Option');
    }

    /**
     * @return phc_ACPPlus_Model_OptionsExIn
     */
    protected function _getOptionExInModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_OptionsExIn');
    }

}
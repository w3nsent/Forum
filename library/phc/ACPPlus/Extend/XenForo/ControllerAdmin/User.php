<?php


class phc_ACPPlus_Extend_XenForo_ControllerAdmin_User extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_User
{
    public function actionList()
    {
        if($this->_input->filterSingle('export', XenForo_Input::STRING))
        {
            return $this->responseReroute(__CLASS__, 'export');
        }

        return parent::actionList();
    }

    public function actionBatchUpdate()
    {
        if($this->_input->filterSingle('export', XenForo_Input::STRING))
        {
            return $this->responseReroute(__CLASS__, 'export');
        }

        return parent::actionBatchUpdate();
    }

    public function actionDelete()
    {
        $res = parent::actionDelete();

        $deleteUserContent = $this->_input->filterSingle('delete_user_content', XenForo_Input::UINT);

        if($this->isConfirmedPost() && !(empty($deleteUserContent)) && $res instanceof XenForo_ControllerResponse_Redirect)
        {
            $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
            $deleteUserContent = $this->_input->filterSingle('delete_user_content', XenForo_Input::ARRAY_SIMPLE);

            XenForo_Application::defer('phc_ACPPlus_Deferred_ACPPlus', array
            (
                'user_id' => $userId,
                'delete_user_content' => $deleteUserContent,
            ),
                'ACPPlus_DeleteUserContent', true);
        }

        return $res;
    }

    public function actionImport()
    {
        if($this->isConfirmedPost())
        {
            $upload = XenForo_Upload::getUploadedFile('csvxmlimport');

            if(!$upload)
                return $this->responseError(new XenForo_Phrase('acpp_please_upload_a_file'));

            XenForo_Application::setSimpleCacheData('acpp_import_user_file', false);

            // verschiebe File
            $tmpDir = XenForo_Helper_File::getTempDir();

            foreach(glob($tmpDir . '/importUser*.*') as $v)
            {
                @unlink($v);
            }

            XenForo_Application::getDb()->query('TRUNCATE TABLE `phc_acpp_user_import`;');

            $tempFile = $upload->getTempFile();
            $fileExt = XenForo_Helper_File::getFileExtension($upload->getFileName());

            $importUserFile = $tmpDir . '/importUser_' . XenForo_Application::generateRandomString(20) . '.' . $fileExt;
            XenForo_Helper_File::safeRename($tempFile, $importUserFile);

            $userImExportModel = $this->_getUserImExportModel();

            XenForo_Application::setSimpleCacheData('acpp_import_user_file', $importUserFile);

            $fh = @fopen($importUserFile, 'r');
            $data = @fgets($fh);
            @fclose($fh);

            if(empty($data))
            {
                @unlink($importUserFile);
                throw new XenForo_Exception(new XenForo_Phrase('acpp_please_upload_a_file'), true);
            }

            if($fileExt == 'csv')
            {
                ini_set('auto_detect_line_endings',TRUE);

                $csvOptions = $this->_input->filter(
                    array(
                        'firstrow' => XenForo_Input::BOOLEAN,
                        'seperator' => XenForo_Input::UINT,
                        'same_user' => XenForo_Input::UINT,
                        'other_separator' => XenForo_Input::STRING,
                        'user_group_id' => XenForo_Input::UINT,
                        'secondary_group_ids' => array(XenForo_Input::UINT, 'array' => true),
                        'replace_add' => XenForo_Input::UINT,
                    ));

                $separator = phc_ACPPlus_Helper_ACPPlus::getSeparator($csvOptions['seperator'], $csvOptions['other_separator']);
                $firstRowHeader = $csvOptions['firstrow'];

                // Read Header
                $fh = @fopen($importUserFile, 'r');
                $header = @fgetcsv($fh, null, $separator);
                @fclose($fh);

                $relation = [];

                if($firstRowHeader)
                {
                    foreach($header as $head)
                    {
                        $relation[] = array(
                            'csv_field' => $head,
                            'new_field' => phc_ACPPlus_Helper_ACPPlus::getSameUserFields($head)
                        );
                    }
                }
                else
                {
                    for($i = 0; $i < count($header); $i++)
                    {
                        $relation[] = array(
                            'csv_field' => $i,
                            'new_field' => null
                        );
                    }
                }

                // List Auwahlen
                $fieldModel = $this->_getFieldModel();
                $fields = $fieldModel->prepareUserFields($fieldModel->getUserFields());

                $groupUserFields = [];

                foreach($fields as $fieldId => &$field)
                {
                    $groupUserFields[$field['display_group']]['cuf_' .$fieldId] = $field['title']->render();
                }

                // Generiere Target Fields
                $newFields = phc_ACPPlus_Helper_ACPPlus::getUserImportMatrix();
                $newFields = array_merge($newFields, $groupUserFields);

                $viewParams = array(
                    'relation' => $relation,
                    'counter' => count($relation),
                    'firstrow' => $firstRowHeader,
                    'new_fields' => $newFields,
                    'separator' => $separator,
                    'secondary_group_ids' => implode(',', $csvOptions['secondary_group_ids']),
                    'user_group_id' => $csvOptions['user_group_id'],
                    'same_user' => $csvOptions['same_user'],
                    'replace_add' => $csvOptions['replace_add'],
                );

                return $this->responseView('phc_ACPPlus_Extend_XenForo_ControllerAdmin_Import_CSV_View', 'acpp_import_user_csv_view', $viewParams);
            }
            elseif($fileExt == 'xml')
            {
                $xmlOptions = $this->_input->filter(
                    array(
                        'same_user' => XenForo_Input::UINT,
                        'user_group_id' => XenForo_Input::UINT,
                        'secondary_group_ids' => array(XenForo_Input::UINT, 'array' => true),
                        'replace_add' => XenForo_Input::UINT,
                    ));

                $document = $this->getHelper('Xml')->getXmlFromFile($importUserFile);
                $userImExportModel->checkXmlData($document);

                XenForo_Application::defer('phc_ACPPlus_Deferred_ImportXMLUsers', $xmlOptions,'ACPPlus_ImportXMLUsers', true);

                return $this->responseRedirect(
                    XenForo_ControllerResponse_Redirect::SUCCESS,
                    XenForo_Link::buildAdminLink('users/finalize-import', null, array('success' => 1))
                );
            }
        }

        $userGroupModel = XenForo_Model::create('XenForo_Model_UserGroup');
        $userGroups = $userGroupModel->getUserGroupOptions(2);

        $secondaryGroups = [];
        foreach($userGroups as $key => $value)
        {
            if(isset($value['selected']))
                $value['selected'] = 0;

            $secondaryGroups[$key] = $value;
        }

        return $this->responseView('phc_ACPPlus_Extend_XenForo_ControllerAdmin_Import', 'acpp_import_user',
            array(
                'userGroups' => $userGroups,
                'secondaryGroups' => $secondaryGroups,
            )
        );
    }

    public function actionExport()
    {
        //$this->_assertPostOnly();
        $userIds = $this->_input->filterSingle('user_ids', XenForo_Input::ARRAY_SIMPLE);

        // List Auwahlen
        $fieldModel = $this->_getFieldModel();
        $fields = $fieldModel->prepareUserFields($fieldModel->getUserFields());

        $groupUserFields = [];

        foreach($fields as $fieldId => &$field)
        {
            $groupUserFields[$field['display_group']]['cuf_' .$fieldId] = $field['title']->render();
        }

        // Generiere Target Fields
        $userFields = phc_ACPPlus_Helper_ACPPlus::getUserImportMatrix();
        $userFields = array_merge($userFields, $groupUserFields);
        unset($userFields[null]);

        $newUserFields = [];
        foreach($userFields as $gKey => $group)
        {
            if($group)
            {
                foreach($group as $fieldKey => $fieldValue)
                {
                    $newUserFields[$fieldKey] = array(
                        'label' => $fieldValue,
                        'value' => $fieldKey,
                        'selected' => 0
                    );
                }
            }
        }

        $viewParams = array(
            'userIds' => XenForo_Helper_Php::safeSerialize($userIds),
            'new_fields' => $newUserFields,
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_ExportAll', 'acpp_export_user', $viewParams);
    }

    public function actionGoExport()
    {
        @ini_set('memory_limit', '512M');
        $this->_assertPostOnly();

        $userIds = $this->_input->filterSingle('user_ids', XenForo_Input::STRING);
        $userIds = XenForo_Helper_Php::safeUnserialize($userIds);

        $exportMode = strtolower($this->_input->filterSingle('export_mode', XenForo_Input::STRING));
        $exportdatas = $this->_input->filterSingle('export_datas', XenForo_Input::ARRAY_SIMPLE);

        $exportdatas = array_flip($exportdatas);

        if($exportMode == 'csv')
        {
            $csvUsers = $this->_getUserImExportModel()->getUsersForCSVExport($userIds, $exportdatas);

            $session = XenForo_Application::getSession();
            $exportUserFile = $session->get('acpp_export_file');

            if(!$exportUserFile || !file_exists($exportUserFile) || !is_readable($exportUserFile))
            {
                throw new XenForo_Exception(new XenForo_Phrase('acpp_unknow_error'), true);
            }

            $this->_routeMatch->setResponseType('raw');

            $viewParams = array(
                'fileName' => 'ACPP_Users',
                'csv' => $csvUsers,
                'filePath' => $exportUserFile
            );
            return $this->responseView('phc_ACPPlus_ViewAdmin_Export', '', $viewParams);
        }
        elseif($exportMode == 'xml')
        {
            $xmlUsers = $this->_getUserImExportModel()->getUserForXMLexport($userIds, $exportdatas);

            $this->_routeMatch->setResponseType('xml');

            $viewParams = array(
                'fileName' => 'ACPP_Users',
                'xml' => $xmlUsers
            );
            return $this->responseView('phc_ACPPlus_ViewAdmin_Export', '', $viewParams);
        }

        return $this->responseView('', '', array());
    }

    public function actionStartCsvImport()
    {
        if($this->isConfirmedPost())
        {
            $csvOptions = $this->_input->filter(
                array(
                    'firstrow' => XenForo_Input::BOOLEAN,
                    'separator' => XenForo_Input::STRING,
                    'same_user' => XenForo_Input::UINT,
                    'user_group_id' => XenForo_Input::UINT,
                    'secondary_group_ids' => XenForo_Input::STRING,
                    'replace_add' => XenForo_Input::UINT,
                    'import_as' => array(XenForo_Input::STRING, 'array' => true),
                ));

            // check if UserName sleceted!
            if(!in_array('username', $csvOptions['import_as']))
            {
                throw new XenForo_Exception(new XenForo_Phrase('acpp_import_not_possible_because_no_username_field_specified'), true);
            }

            XenForo_Application::defer('phc_ACPPlus_Deferred_ImportCSVUsers', $csvOptions,'ACPPlus_ImportCSVUsers', true);
        }

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('users/finalize-import', null, array('success' => 1))
        );
    }

    public function actionFinalizeImport()
    {
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT, array('default' => 0));
        $perPage = 500;

        $fetchOptions = array(
            'page' => $page,
            'perPage' => $perPage
        );

        $userFailed = $this->_getUserImExportModel()->fetchAllFailedUser($fetchOptions);

        if($userFailed)
        {
            $countUserFailed = $this->_getUserImExportModel()->countAllFailedUser();

            foreach($userFailed as &$user)
            {
                $user['data'] = XenForo_Helper_Php::safeUnserialize($user['data']);
                $user['import_error'] = XenForo_Helper_Php::safeUnserialize($user['import_error']);
                $user['reason'] = new XenForo_Phrase('acpp_failed_status_' . $user['reason']);
            }

            $viewParams = array(
                'users' => $userFailed,
                'perPage' => $perPage,
                'total' => $countUserFailed,
                'page' => $page,
            );

            return $this->responseView('phc_ACPPlus_Extend_XenForo_ControllerAdmin_Import_Final', 'acpp_import_failed_user', $viewParams);
        }

        return $this->responseMessage(new XenForo_Phrase('acpp_import_successfully_completed'));
    }

    /**
     * @return phc_ACPPlus_Model_UserImExport
     */
    protected function _getUserImExportModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_UserImExport');
    }

}
<?php
class phc_ACPPlus_Deferred_ImportCSVUsers extends XenForo_Deferred_Abstract
{
    /** @var $userImExportModel phc_ACPPlus_Model_UserImExport */
    protected $userImExportModel = null;

    /** @var $userModel XenForo_Model_User */
    protected $userModel = null;

    /** @var $fieldModel XenForo_Model_UserField */
    protected $fieldModel = null;

    public function execute(array $deferred, array $options, $targetRunTime, &$status)
    {
        ini_set('auto_detect_line_endings',TRUE);

        $options = array_merge(array(
            'position' => 0,
            'currentLine' => 0,
            'totalLines' => 'unknow',
        ), $options);

        $importUserFile = XenForo_Application::getSimpleCacheData('acpp_import_user_file');

        if(!file_exists($importUserFile) || !is_readable($importUserFile))
        {
            return false;
        }

        $this->userImExportModel = $this->_getUserImExportModel();
        $this->userModel = $this->_getUserModel();
        $this->fieldModel = $this->_getUserFieldModel();

        $skipReason = null;
        $existingUser = [];

        $regDefaults = XenForo_Application::getOptions()->registrationDefaults;

        $newUserArray = array(
            'user_id' => null,
            'username' => null,
            'email' => null,
            'user_group_id' => null,
            'secondary_group_ids' => null,
            'gender' => '',
            'custom_title' => '',
            'timezone' => XenForo_Application::getOptions()->guestTimeZone,
            'message_count' => 0,
            'register_date' => XenForo_Application::$time,
            'last_activity' => XenForo_Application::$time,
            'is_moderator' => 0,
            'is_admin' => 0,
            'is_banned' => 0,
            'is_staff' => 0,
            'show_dob_year' => $regDefaults['show_dob_year'],
            'show_dob_date' => $regDefaults['show_dob_date'],
            'content_show_signature' => $regDefaults['content_show_signature'],
            'receive_admin_email' => $regDefaults['receive_admin_email'],
            'email_on_conversation' => $regDefaults['email_on_conversation'],
            'is_discouraged' => null,
            'default_watch_state' => $regDefaults['default_watch_state'],
            'enable_rte' => null,
            'enable_flash_uploader' => null,
            'allow_view_profile' => $regDefaults['allow_view_profile'],
            'allow_post_profile' => $regDefaults['allow_post_profile'],
            'allow_send_personal_conversation' => $regDefaults['allow_send_personal_conversation'],
            'allow_view_identities' => $regDefaults['allow_view_identities'],
            'allow_receive_news_feed' => $regDefaults['allow_receive_news_feed'],
            'dob_day' => null,
            'dob_month' => null,
            'dob_year' => null,
            'signature' => '',
            'homepage' => '',
            'location' => '',
            'occupation' => '',
            'about' => '',
        );


        $fields = $this->fieldModel->prepareUserFields($this->fieldModel->getUserFields());
        $customFields = [];

        foreach($fields as $fieldId => $field)
        {
            $newUserArray[$fieldId] = null;
            $customFields[] = $fieldId;
        }

        try
        {
            if($options['totalLines'] === 'unknow')
            {
                $row = 0;
                if(($fp = fopen($importUserFile, "r")) !== FALSE)
                {
                    while((fgetcsv($fp, 1024*8, $options['separator'], '"', '"')) !== FALSE)
                    {
                        $row++;
                    }
                }

                if($options['firstrow'])
                    $row--;

                $options['totalLines'] = $row;
                fclose($fp);
            }

            $fh = fopen($importUserFile, 'r');
            if($options['position'] === 0 && $options['firstrow'])
            {
                fgets($fh, 1024*8);
                $options['position'] = ftell($fh);
                $options['currentLine'] = 1;
            }

            if($options['position'] > 0)
            {
                fseek($fh, $options['position']);
                $options['currentLine']++;
            }


            $lineData = fgetcsv($fh, 1024*8, $options['separator'], '"', '"');
            $options['position'] = ftell($fh);
            fclose($fh);

            if(!$lineData)
            {
                return false;
            }

            foreach ($lineData as $key => $value)
            {
                if(!empty($options['import_as'][$key]))
                {
                    $value = trim($value);
                    $k = $options['import_as'][$key];

                    if(substr($k, 0, 4) == 'cuf_')
                    {
                        $fieldId = substr($k, 4, utf8_strlen($k));

                        if(isset($fields[$fieldId]))
                        {
                            if($this->_getUserImExportModel()->_verifyUserFieldValue($fields[$fieldId], $value))
                            {
                                $newUserArray[$fieldId] = $value;
                            }

                        }
                    }
                    else
                    {
                        $newUserArray[$k] = $value;
                    }
                }
            }

            @fclose($fh);
        }
        catch (Exception $e)
        {
            @fclose($fh);
            XenForo_Error::logException($e, false);
        }

        // Prüfe username existiert
        if(strpos($newUserArray['username'], ',') !== false || utf8_strlen($newUserArray['username']) == 0)
        {
            $skipReason = 'incorrect_username';
            $this->userImExportModel->setSkipedUser($existingUser, $newUserArray, $skipReason);
            return $this->goNext($options, $status);
        }

        if(!XenForo_Helper_Email::isEmailValid($newUserArray['email']))
        {
            $skipReason = 'incorrect_email';
            $this->userImExportModel->setSkipedUser($existingUser, $newUserArray, $skipReason);
            return $this->goNext($options, $status);
        }

        // Get Exisiting user and Import Mode
        $importMode = $this->userImExportModel->importOrMergeUser($newUserArray, $options, $this->userModel, $skipReason, $existingUser);
        if($skipReason)
        {
            if(!$existingUser)
                $existingUser = [];

            $this->userImExportModel->setSkipedUser($existingUser, $newUserArray, $skipReason);
            return $this->goNext($options, $status);
        }

        // $importMode ID = 10  ist ein frischer import ohne existierende Daten Dann muss die ID geprüft werden aber nur wenn ID zugeordnet wurde!!!
        // Prüfe User ID
        if($importMode == 10 && $newUserArray['user_id'])
        {
            if($existingUser = $this->userModel->getUserById($newUserArray['user_id']))
            {
                $skipReason = 'user_id';
                $this->userImExportModel->setSkipedUser($existingUser, $newUserArray, $skipReason);
                return $this->goNext($options, $status);
            }
        }

        if($existingUser)
        {
            $uFields = $this->fieldModel->getUserFieldValues($existingUser['user_id']);

            if(!empty($uFields))
                $existingUser = array_merge($existingUser, $uFields);
        }

        // OK es kann weitergehen ;)
        if($importMode && !$skipReason)
        {
            // GroupId Check und säubern
            $userGroupIds = [];
            if($newUserArray['user_group_id'])
            {
                if(strpos($newUserArray['user_group_id'], ',') !== false)
                {
                    $userGroupIds = @explode(',', $newUserArray['user_group_id']);
                    $userGroupIds = array_map('intval', $userGroupIds);
                    $newUserArray['user_group_id'] = (!empty($userGroupIds[0]) ? $userGroupIds[0] : $options['user_group_id']);

                    unset($userGroupIds[0]);
                }
                elseif((int)$newUserArray['user_group_id'])
                {
                    $newUserArray['user_group_id'] = (int)$newUserArray['user_group_id'];
                }
            }
            else
            {
                $newUserArray['user_group_id'] = $options['user_group_id'];
            }

            if($newUserArray['secondary_group_ids'])
            {
                if(strpos($newUserArray['secondary_group_ids'], ',') !== false)
                {
                    $ids = @explode(',', $newUserArray['secondary_group_ids']);
                    $ids = array_map('intval', $ids);

                    $ids = array_merge($ids, $userGroupIds);
                    $ids = array_unique($ids);

                    $newUserArray['secondary_group_ids'] = implode(',', $ids);
                }
                elseif((int)$newUserArray['secondary_group_ids'])
                {
                    $newUserArray['secondary_group_ids'] = (int)$newUserArray['secondary_group_ids'];
                }
            }
            else
            {
                $newUserArray['secondary_group_ids'] = $options['secondary_group_ids'];
            }

            /* @var $userDw XenForo_DataWriter_User */
            $userDw = XenForo_DataWriter::create('XenForo_DataWriter_User', XenForo_DataWriter::ERROR_SILENT);
            $userDw->setOption(XenForo_DataWriter_User::OPTION_ADMIN_EDIT, true);

            $userDw->setImportMode(true);

            // Frischer User
            if($existingUser)
            {
                $userDw->setExistingData($existingUser);
                unset($newUserArray['user_id']);
            }
            else
            {
                $userDw->set('user_id', $newUserArray['user_id']);

                $password = XenForo_Application::generateRandomString(12);
                $userDw->setPassword($password);
            }

            if($setValue = $this->userImExportModel->setDataWriterValue('username', $newUserArray, $existingUser, $options))
                $userDw->set('username', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('email', $newUserArray, $existingUser, $options))
                $userDw->set('email', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('user_group_id', $newUserArray, $existingUser, $options))
                $userDw->set('user_group_id', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('secondary_group_ids', $newUserArray, $existingUser, $options))
                $userDw->set('secondary_group_ids', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('gender', $newUserArray, $existingUser, $options))
                $userDw->set('gender', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('custom_title', $newUserArray, $existingUser, $options))
                $userDw->set('custom_title', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('timezone', $newUserArray, $existingUser, $options))
                $userDw->set('timezone', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('message_count', $newUserArray, $existingUser, $options))
                $userDw->set('message_count', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('register_date', $newUserArray, $existingUser, $options))
                $userDw->set('register_date', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('last_activity', $newUserArray, $existingUser, $options))
                $userDw->set('last_activity', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('is_moderator', $newUserArray, $existingUser, $options))
                $userDw->set('is_moderator', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('is_admin', $newUserArray, $existingUser, $options))
                $userDw->set('is_admin', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('is_banned', $newUserArray, $existingUser, $options))
                $userDw->set('is_banned', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('is_staff', $newUserArray, $existingUser, $options))
                $userDw->set('is_staff', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('show_dob_year', $newUserArray, $existingUser, $options))
                $userDw->set('show_dob_year', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('show_dob_date', $newUserArray, $existingUser, $options))
                $userDw->set('show_dob_date', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('content_show_signature', $newUserArray, $existingUser, $options))
                $userDw->set('content_show_signature', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('receive_admin_email', $newUserArray, $existingUser, $options))
                $userDw->set('receive_admin_email', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('email_on_conversation', $newUserArray, $existingUser, $options))
                $userDw->set('email_on_conversation', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('is_discouraged', $newUserArray, $existingUser, $options))
                $userDw->set('is_discouraged', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('default_watch_state', $newUserArray, $existingUser, $options))
                $userDw->set('default_watch_state', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('enable_rte', $newUserArray, $existingUser, $options))
                $userDw->set('enable_rte', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('enable_flash_uploader', $newUserArray, $existingUser, $options))
                $userDw->set('enable_flash_uploader', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('allow_view_profile', $newUserArray, $existingUser, $options))
                $userDw->set('allow_view_profile', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('allow_post_profile', $newUserArray, $existingUser, $options))
                $userDw->set('allow_post_profile', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('allow_send_personal_conversation', $newUserArray, $existingUser, $options))
                $userDw->set('allow_send_personal_conversation', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('allow_view_identities', $newUserArray, $existingUser, $options))
                $userDw->set('allow_view_identities', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('allow_receive_news_feed', $newUserArray, $existingUser, $options))
                $userDw->set('allow_receive_news_feed', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('signature', $newUserArray, $existingUser, $options))
                $userDw->set('signature', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('homepage', $newUserArray, $existingUser, $options))
                $userDw->set('homepage', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('location', $newUserArray, $existingUser, $options))
                $userDw->set('location', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('occupation', $newUserArray, $existingUser, $options))
                $userDw->set('occupation', $setValue);

            if($setValue = $this->userImExportModel->setDataWriterValue('about', $newUserArray, $existingUser, $options))
                $userDw->set('about', $setValue);

            // Sonderfall Birthday
            if($setValue = $this->userImExportModel->setDataWriterValue('birthday', $newUserArray, $existingUser, $options))
            {
                if($setValue)
                {
                    $userDw->set('dob_day', date('j', $setValue));
                    $userDw->set('dob_month', date('n', $setValue));
                    $userDw->set('dob_year', date('Y', $setValue));
                }
            }

            $newCustomData = [];
            foreach($customFields as $field)
            {
                if(isset($newUserArray[$field]))
                {
                    if($setValue = $this->userImExportModel->setDataWriterValue($field, $newUserArray, $existingUser, $options))
                        $newCustomData[$field] = $setValue;
                }
            }

            if($newCustomData)
            {
                if($newCustomData)
                    $userDw->setCustomFields($newCustomData);
            }

            $userDw->preSave();
            $dwErrors = $userDw->getErrors();

            if($dwErrors)
            {
                $this->userImExportModel->setSkipedUser($existingUser, $newUserArray, 'other', serialize($dwErrors));
                return $this->goNext($options, $status);
            }

            if ($userDw->save())
            {
                $userDw->updateCustomFields();

                $userDw->rebuildPermissionCombinationId(false);

                $finallyUser = $userDw->getMergedNewData();
                $newId = $userDw->get('user_id');

                if(!empty($finallyUser['is_admin']))
                {
                    // maintain admin stuff for user if retaining keys and it's UID 1
                    //$adminId = $this->_importData('', 'XenForo_DataWriter_Admin', '', 'user_id', array('user_id' => $newId));
                    //if ($adminId && $adminPerms)
                    //{
                    //    $this->getModelFromCache('XenForo_Model_Admin')->updateUserAdminPermissions($newId, $adminPerms);
                    //}
                }

            }
        }

        return $this->goNext($options, $status);
    }

    public function goNext($options, &$status)
    {
        $status = sprintf('Importing... User (%s/%s)', $options['currentLine'], $options['totalLines']);

        return $options;
    }

	public function canCancel()
	{
		return true;
	}

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPModel()
    {
        return XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');
    }

    /**
     * @return phc_ACPPlus_Model_UserImExport
     */
    protected function _getUserImExportModel()
    {
        return XenForo_Model::create('phc_ACPPlus_Model_UserImExport');
    }

    /**
     * @return XenForo_Model_Import
     */
    protected function _getImportModel()
    {
        return XenForo_Model::create('XenForo_Model_Import');
    }

    /**
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return XenForo_Model::create('XenForo_Model_User');
    }

    /**
     * @return XenForo_Model_UserField
     */
    protected function _getUserFieldModel()
    {
        return XenForo_Model::create('XenForo_Model_UserField');
    }

}

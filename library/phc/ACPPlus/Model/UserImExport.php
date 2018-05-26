<?php

class phc_ACPPlus_Model_UserImExport extends XenForo_Model
{
    public function fetchAllFailedUser($fetchOptions)
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $db = $this->_getDb();

        return $db->fetchAll($this->limitQueryResults('
                                   			SELECT import.*, user.username, user.email
                                            FROM phc_acpp_user_import as import
                                            LEFT JOIN xf_user as user USING(user_id)

                                            ORDER BY import_id ASC
                                ', $limitOptions['limit'], $limitOptions['offset']));
    }

    public function countAllFailedUser()
    {
        return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM phc_acpp_user_import as import
			LEFT JOIN xf_user as user USING(user_id)
		');
    }

    public function getUserIdById($id)
    {
        return $this->_getDb()->fetchOne('
			SELECT user_id
			FROM xf_user
			WHERE user_id = ?
		', $id);
    }

    public function getUserIdByEmail($email)
    {
        return $this->_getDb()->fetchOne('
			SELECT user_id
			FROM xf_user
			WHERE email = ?
		', trim($email));
    }

    public function getUserIdByUserName($name)
    {
        return $this->_getDb()->fetchOne('
			SELECT user_id
			FROM xf_user
			WHERE username = ?
		', utf8_substr(trim($name),  0, 50));
    }

    public function setSkipedUser(array $user, array $newUser, $reason, $otherError = '')
    {
        $userId = (!empty($user['user_id']) ? $user['user_id'] : 0);

        $serialized = serialize($newUser);

        $this->_getDb()->query('
			INSERT INTO phc_acpp_user_import
				(user_id, data, reason, import_error)
			VALUES
				(?, ?, ?, ?)
		', array($userId, $serialized, $reason, $otherError));
    }

    /** @var $userModel XenForo_Model_User */
    public function importOrMergeUser(array &$user, array $options, $userModel, &$skipReason, array &$existingUser)
    {
        $user['email'] = $this->_convertToUtf8(trim($user['email']));
        $user['username'] = $this->_convertToUtf8(trim($user['username']));

        if($user['email'] && $existingUser = $userModel->getUserByEmail($user['email'], array('join' => XenForo_Model_User::FETCH_USER_FULL)))
        {
            if ($options['same_user'] == 3)
            {
                return 3;
            }
            else
            {
                if($userModel->getUserByName($user['username'], array('join' => XenForo_Model_User::FETCH_USER_FULL)))
                {
                    if($options['same_user'] == 1 || $options['same_user'] == 2)
                    {
                        return $options['same_user'];
                    }
                    else
                    {
                        $skipReason = 'both';
                    }
                }
                else
                {
                    $skipReason = 'email';
                }

                return false;
            }
        }

        $name = utf8_substr($user['username'], 0, 50);

        if($existingUser = $userModel->getUserByName($name, array('join' => XenForo_Model_User::FETCH_USER_FULL)))
        {
            if($options['same_user'] == 1 || $options['same_user'] == 2)
            {
                return $options['same_user'];
            }
            else
            {
                $skipReason = 'username';
                return false;
            }
        }

        return 10;
    }

    public function setDataWriterValue($key, $newUser, $existsUser, $options)
    {
        if(isset($newUser[$key]))
        {
            switch($key)
            {
                case 'username':
                    //$newUser[$key] = $newUser[$key];
                    break;

                case 'email':
                    //$newUser[$key] = $newUser[$key];
                    break;

                case 'gender':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);

                    if($newUser[$key] && ($newUser[$key] != 'male' || $newUser[$key] != 'female'))
                    {
                        $newUser[$key] = null;
                    }
                    break;

                case 'custom_title':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);
                    break;

                case 'timezone':
                    if(is_numeric($newUser[$key]))
                    {

                    }

                    break;

                case 'message_count':
                    $newUser[$key] = (int)$newUser[$key];
                    break;

                case 'register_date':
                    $newUser[$key] = phc_ACPPlus_Helper_ACPPlus::checkDateAndConvert($newUser[$key]);

                    break;

                case 'last_activity':
                    $newUser[$key] = phc_ACPPlus_Helper_ACPPlus::checkDateAndConvert($newUser[$key]);
                    break;

                case 'is_moderator':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'is_admin':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'is_banned':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'is_staff':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'show_dob_year':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'show_dob_date':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'content_show_signature':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'receive_admin_email':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'email_on_conversation':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'is_discouraged':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'default_watch_state':
                    $newUser[$key] = strtolower($newUser[$key]);
                    if(!in_array($newUser[$key], array('', 'watch_no_email', 'watch_email')))
                        $newUser[$key] = '';
                    break;

                case 'enable_rte':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'enable_flash_uploader':
                    if(!is_bool($newUser[$key]))
                        $newUser[$key] = false;
                    break;

                case 'allow_view_profile':
                    $newUser[$key] = $this->verifyPrivacyChoice($newUser[$key]);
                    break;

                case 'allow_post_profile':
                    $newUser[$key] = $this->verifyPrivacyChoice($newUser[$key]);
                    break;

                case 'allow_send_personal_conversation':
                    $newUser[$key] = $this->verifyPrivacyChoice($newUser[$key]);
                    break;

                case 'allow_view_identities':
                    $newUser[$key] = $this->verifyPrivacyChoice($newUser[$key]);
                    break;

                case 'allow_receive_news_feed':
                    $newUser[$key] = $this->verifyPrivacyChoice($newUser[$key]);
                    break;

                case 'birthday':
                    $newUser[$key] = phc_ACPPlus_Helper_ACPPlus::checkDateAndConvert($newUser[$key]);
                    break;

                case 'signature':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);
                    break;

                case 'homepage':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);
                    break;

                case 'location':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);
                    break;

                case 'occupation':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);
                    break;

                case 'about':
                    $newUser[$key] = $this->_convertToUtf8($newUser[$key]);
                    break;
            }
        }


        /*
         * 0 = Replace = alle daten des Exisiteirenden Users werden mit denen des NewUsers überschrieben
         *
         * 1 = ADD =  Im existierenden user leere Felder werden diese Aufgefüllt mit den Daten des NewUsers
         */
        if($options['replace_add'] == 0)// replace
        {
            if(isset($newUser[$key]))
            {
                return $newUser[$key];
            }
            else
            {
                return false;
            }
        }
        elseif($options['replace_add'] == 1) // add
        {
            if(empty($existsUser[$key]) && !empty($newUser[$key]))
            {
                return $newUser[$key];
            }
            else
            {
                return false;
            }
        }

        //if(!isset($newUser[$key]))
        //    return (isset($existsUser[$key]) ? $existsUser[$key] : $newUser[$key]);

        return false;
    }

    protected function verifyPrivacyChoice(&$choice)
    {
        $choice = strtolower($choice);

        if (!in_array(strtolower($choice), array('everyone', 'members', 'followed', 'none')))
        {
            $choice = 'none';
        }

        return $choice;
    }

    protected function _getUserAndFields($userIds, &$users, &$userFieldsAray)
    {
        if(!empty($userIds) && !is_array($userIds))
        {
            $userIds[] = $userIds;
        }

        $users = $this->_getDb()->fetchAll('
                                            SELECT
                                                user.user_id,user.username,user.email,user.user_group_id,user.secondary_group_ids,user.gender,user.custom_title,user.timezone,user.message_count,user.register_date,user.last_activity,user.is_moderator,user.is_admin,user.is_banned,user.is_staff,
                                                options.show_dob_year,options.show_dob_date,options.content_show_signature,options.receive_admin_email,options.email_on_conversation,options.is_discouraged,options.default_watch_state,options.enable_rte,options.enable_flash_uploader,
                                                privacy.allow_view_profile,privacy.allow_post_profile,privacy.allow_send_personal_conversation,privacy.allow_view_identities,privacy.allow_receive_news_feed,
                                                profile.dob_day,profile.dob_month,profile.dob_year,profile.signature,profile.homepage,profile.location,profile.occupation,profile.about
                                            FROM xf_user AS user
                                            LEFT JOIN xf_user_option as options USING(user_id)
                                            LEFT JOIN xf_user_privacy as privacy USING(user_id)
                                            LEFT JOIN xf_user_profile as profile USING(user_id)
                                            ' . ($userIds ? 'WHERE user.user_id IN (' . $this->_getDb()->quote($userIds) . ')' : '') . '
                                            ORDER BY user.user_id
                                    ');
        if(!$users)
            return false;

        $userIds = [];
        foreach($users as $user)
        {
            $userIds[] = $user['user_id'];
        }

        $userFieldsAray = [];
        if($userIds)
        {
            $userFieldsAray = $this->getUsersFieldValues($userIds);
        }

        return true;
    }

    public function checkXmlData(SimpleXMLElement $document)
    {
        if($document->getName() != 'acpp_users')
        {
            throw new XenForo_Exception(new XenForo_Phrase('acpp_provided_file_is_not_valid_users_xml'));
        }
    }

    public function getUserForXMLexport(array $userIds, array $exportdatas)
    {
        $users = $userFieldsAray = [];
        $this->_getUserAndFields($userIds, $users, $userFieldsAray);

        $document = new DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $rootNode = $document->createElement('acpp_users');
        $document->appendChild($rootNode);


        foreach($users as $user)
        {
            $userNode = $document->createElement('user');

            $stadardNode = $document->createElement('standard');

            if(isset($exportdatas['user_id']))
                $stadardNode->setAttribute('user_id', $user['user_id']);

            if(isset($exportdatas['username']))
                $stadardNode->setAttribute('username', $user['username']);

            if(isset($exportdatas['email']))
                $stadardNode->setAttribute('email', $user['email']);

            if(isset($exportdatas['user_group_id']))
                $stadardNode->setAttribute('user_group_id', $user['user_group_id']);

            if(isset($exportdatas['secondary_group_ids']))
                $stadardNode->setAttribute('secondary_group_ids', $user['secondary_group_ids']);

            if(isset($exportdatas['gender']))
                $stadardNode->setAttribute('gender', $user['gender']);

            if(isset($exportdatas['custom_title']))
                $stadardNode->setAttribute('custom_title', $user['custom_title']);

            if(isset($exportdatas['timezone']))
                $stadardNode->setAttribute('timezone', $user['timezone']);

            if(isset($exportdatas['message_count']))
                $stadardNode->setAttribute('message_count', $user['message_count']);

            if(isset($exportdatas['register_date']))
                $stadardNode->setAttribute('register_date', $user['register_date']);

            if(isset($exportdatas['last_activity']))
                $stadardNode->setAttribute('last_activity', $user['last_activity']);

            if(isset($exportdatas['is_moderator']))
                $stadardNode->setAttribute('is_moderator', $user['is_moderator']);

            if(isset($exportdatas['is_admin']))
                $stadardNode->setAttribute('is_admin', $user['is_admin']);

            if(isset($exportdatas['is_banned']))
                $stadardNode->setAttribute('is_banned', $user['is_banned']);

            if(isset($exportdatas['is_staff']))
                $stadardNode->setAttribute('is_staff', $user['is_staff']);

            $userNode->appendChild($stadardNode);


            $optionsNode = $document->createElement('options');

            if(isset($exportdatas['show_dob_year']))
                $optionsNode->setAttribute('show_dob_year', $user['show_dob_year']);

            if(isset($exportdatas['show_dob_date']))
                $optionsNode->setAttribute('show_dob_date', $user['show_dob_date']);

            if(isset($exportdatas['content_show_signature']))
                $optionsNode->setAttribute('content_show_signature', $user['content_show_signature']);

            if(isset($exportdatas['receive_admin_email']))
                $optionsNode->setAttribute('receive_admin_email', $user['receive_admin_email']);

            if(isset($exportdatas['email_on_conversation']))
                $optionsNode->setAttribute('email_on_conversation', $user['email_on_conversation']);

            if(isset($exportdatas['is_discouraged']))
                $optionsNode->setAttribute('is_discouraged', $user['is_discouraged']);

            if(isset($exportdatas['default_watch_state']))
                $optionsNode->setAttribute('default_watch_state', $user['default_watch_state']);

            if(isset($exportdatas['enable_rte']))
                $optionsNode->setAttribute('enable_rte', $user['enable_rte']);

            if(isset($exportdatas['enable_flash_uploader']))
                $optionsNode->setAttribute('enable_flash_uploader', $user['enable_flash_uploader']);

            $userNode->appendChild($optionsNode);


            $privacyNode = $document->createElement('privacy');

            if(isset($exportdatas['allow_view_profile']))
                $privacyNode->setAttribute('allow_view_profile', $user['allow_view_profile']);

            if(isset($exportdatas['allow_post_profile']))
                $privacyNode->setAttribute('allow_post_profile', $user['allow_post_profile']);

            if(isset($exportdatas['allow_send_personal_conversation']))
                $privacyNode->setAttribute('allow_send_personal_conversation', $user['allow_send_personal_conversation']);

            if(isset($exportdatas['allow_view_identities']))
                $privacyNode->setAttribute('allow_view_identities', $user['allow_view_identities']);

            if(isset($exportdatas['allow_receive_news_feed']))
                $privacyNode->setAttribute('allow_receive_news_feed', $user['allow_receive_news_feed']);

            $userNode->appendChild($privacyNode);


            $profileNode = $document->createElement('profile');

            /*
            if(isset($exportdatas['dob_day']))
                $profileNode->setAttribute('dob_day', $user['dob_day']);

            if(isset($exportdatas['dob_month']))
                $profileNode->setAttribute('dob_month', $user['dob_month']);

            if(isset($exportdatas['dob_year']))
                $profileNode->setAttribute('dob_year', $user['dob_year']);

            */

            // SonderFall Birthday
            if(isset($exportdatas['birthday']))
            {
                if(!empty($user['dob_day']))
                    $profileNode->setAttribute('dob_day', $user['dob_day']);

                if(!empty($user['dob_month']))
                    $profileNode->setAttribute('dob_month', $user['dob_month']);

                if(!empty($user['dob_year']))
                    $profileNode->setAttribute('dob_year', $user['dob_year']);
            }


            if(isset($exportdatas['signature']))
                $profileNode->setAttribute('signature', $user['signature']);

            if(isset($exportdatas['homepage']))
                $profileNode->setAttribute('homepage', $user['homepage']);

            if(isset($exportdatas['location']))
                $profileNode->setAttribute('location', $user['location']);

            if(isset($exportdatas['occupation']))
                $profileNode->setAttribute('occupation', $user['occupation']);

            if(isset($exportdatas['about']))
                $profileNode->setAttribute('about', $user['about']);

            $userNode->appendChild($profileNode);


            $customFieldsNode = $document->createElement('custom_fields');


            if($userFieldsAray)
            {
                $userId = $user['user_id'];
                if(isset($userFieldsAray[$userId]))
                {
                    foreach($userFieldsAray[$userId] as $fieldId => $fieldValue)
                    {
                        if(isset($exportdatas['cuf_' . $fieldId]) && $fieldValue)
                        {
                            if(is_array($fieldValue))
                            {
                                $fieldValue = XenForo_Helper_Php::safeSerialize($fieldValue);
                            }

                            $customFieldNode = $document->createElement('custom_field');

                            $customFieldNode->setAttribute('field_id', $fieldId);
                            $customFieldNode->setAttribute('field_value', $fieldValue);

                            $customFieldsNode->appendChild($customFieldNode);
                        }
                    }
                }
            }

            $userNode->appendChild($customFieldsNode);

            $rootNode->appendChild($userNode);
        }

        return $document;
    }

    public function getUsersForCSVExport(array $userIds, array $exportdatas)
    {
        ini_set('auto_detect_line_endings',TRUE);

        $users = $userFieldsAray = [];
        $this->_getUserAndFields($userIds, $users, $userFieldsAray);

        $tmpDir = XenForo_Helper_File::getTempDir();

        foreach(glob($tmpDir . '/exportUser*.csv') as $v)
        {
            @unlink($v);
        }

        $exportUserFile = $tmpDir . '/exportUser_' . XenForo_Application::generateRandomString(20) . '.csv';

        $session = XenForo_Application::getSession();
        $session->set('acpp_export_file', $exportUserFile);

        $fp = fopen($exportUserFile, 'a+');

        $csvHeader = array_keys($exportdatas);
        fputcsv($fp, $csvHeader, ';');

        foreach($users AS &$user)
        {
            $userId = $user['user_id'];

            if($userFieldsAray)
            {
                if(isset($userFieldsAray[$userId]))
                {
                    foreach($userFieldsAray[$userId] as $fieldId => $fieldValue)
                    {
                        if(isset($exportdatas['cuf_' . $fieldId]))
                        {
                            if(is_array($fieldValue))
                            {
                                $fieldValue = @implode(',', $fieldValue);
                            }

                            $user['cuf_' . $fieldId] = $fieldValue;
                        }
                    }
                }
            }

            $csvUser = [];

            foreach($csvHeader as $key)
            {
                $csvUser[$key] = null;
                if(isset($user[$key]))
                {
                    $csvUser[$key] = $user[$key];
                }

                // SonderFall BirthDay
                if($key == 'birthday')
                {
                    $birthday = 0;

                    if($user['dob_day'] && $user['dob_month'] && $user['dob_year'])
                    {
                        $birthday = mktime(0, 0, 0, $user['dob_month'], $user['dob_day'], $user['dob_year']);
                    }

                    $csvUser['birthday'] = $birthday;
                }
            }

            fputcsv($fp, $csvUser, ';', '"', '"');
        }

        fclose($fp);
    }

    public function getUsersFieldValues($userIds)
    {
        if(!is_array($userIds))
            $userIds[] = $userIds;


        $userFields = $this->_getDb()->fetchAll('
			SELECT value.*, field.field_type
			FROM xf_user_field_value AS value
			INNER JOIN xf_user_field AS field ON (field.field_id = value.field_id)
			WHERE value.user_id IN (' . $this->_getDb()->quote($userIds) . ')
		');

        $userValues = array();
        foreach ($userFields AS $field)
        {
            if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
            {
                $userValues[$field['user_id']][$field['field_id']] = XenForo_Helper_Php::safeUnserialize($field['field_value']);
            }
            else
            {
                $userValues[$field['user_id']][$field['field_id']] = $field['field_value'];
            }
        }

        return $userValues;
    }

    public function _verifyUserFieldValue(array $field, &$value, &$error = '')
    {
        $error = false;

        switch ($field['field_type'])
        {
            case 'textbox':
                $value = preg_replace('/\r?\n/', ' ', strval($value));
            // break missing intentionally

            case 'textarea':
                $value = trim(strval($value));

                if ($field['max_length'] && utf8_strlen($value) > $field['max_length'])
                {
                    $error = new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', array('count' => $field['max_length']));
                    return false;
                }

                $matched = true;

                if ($value !== '')
                {
                    switch ($field['match_type'])
                    {
                        case 'number':
                            $matched = preg_match('/^[0-9]+(\.[0-9]+)?$/', $value);
                            break;

                        case 'alphanumeric':
                            $matched = preg_match('/^[a-z0-9_]+$/i', $value);
                            break;

                        case 'email':
                            $matched = XenForo_Helper_Email::isEmailValid($value);
                            break;

                        case 'url':
                            if ($value === 'http://')
                            {
                                $value = '';
                                break;
                            }
                            if (substr(strtolower($value), 0, 4) == 'www.')
                            {
                                $value = 'http://' . $value;
                            }
                            $matched = Zend_Uri::check($value);
                            break;

                        case 'regex':
                            $matched = preg_match('#' . str_replace('#', '\#', $field['match_regex']) . '#sU', $value);
                            break;

                        case 'callback':
                            $matched = call_user_func_array(
                                array($field['match_callback_class'], $field['match_callback_method']),
                                array($field, &$value, &$error)
                            );

                        default:
                            // no matching
                    }
                }

                if (!$matched)
                {
                    if (!$error)
                    {
                        $error = new XenForo_Phrase('please_enter_value_that_matches_required_format');
                    }
                    return false;
                }
                break;

            case 'radio':
            case 'select':
                $choices = XenForo_Helper_Php::safeUnserialize($field['field_choices']);
                $value = strval($value);

                if (!isset($choices[$value]))
                {
                    $value = '';
                }
                break;

            case 'checkbox':
            case 'multiselect':

                if(phc_ACPPlus_Helper_ACPPlus::is_serialized($value))
                {
                    $value = XenForo_Helper_Php::safeUnserialize($value);
                }
                else
                {
                    $tmp = @explode(',', $value);

                    if(is_array($tmp))
                    {
                        $value = $tmp;
                    }
                    else
                    {
                        if(phc_ACPPlus_Helper_ACPPlus::is_json($value))
                        {
                            $value = @json_decode($value);
                        }
                    }
                }

                $choices = XenForo_Helper_Php::safeUnserialize($field['field_choices']);
                if (!is_array($value))
                {
                    $value = array();
                }

                $newValue = array();

                foreach ($value AS $key => $choice)
                {
                    $choice = strval($choice);
                    if (isset($choices[$choice]))
                    {
                        $newValue[$choice] = $choice;
                    }
                }

                $value = $newValue;
                break;
        }

        return true;
    }

    /**
     * Convert the given text to valid UTF-8
     *
     * @param string $string
     * @param boolean $entities Convert &lt; (and other) entities back to < characters
     *
     * @return string
     */
    public function _convertToUtf8($string, $entities = null)
    {
        if(!(mb_detect_encoding($string, 'UTF-8', true) == 'UTF-8'))
        {
            // note: assumes charset is ascii compatible
            if (preg_match('/[\x80-\xff]/', $string))
            {
                $newString = false;
                if (function_exists('iconv'))
                {
                    $newString = @iconv($this->_charset, 'utf-8//IGNORE', $string);
                }
                if (!$newString && function_exists('mb_convert_encoding'))
                {
                    $newString = @mb_convert_encoding($string, 'utf-8', $this->_charset);
                }
                $string = ($newString ? $newString : preg_replace('/[\x80-\xff]/', '', $string));
            }

            $string = utf8_unhtml($string, $entities);
            $string = preg_replace('/[\xF0-\xF7].../', '', $string);
            $string = preg_replace('/[\xF8-\xFB]..../', '', $string);
        }

        return $string;
    }

    /**
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    /**
     * @return XenForo_Model_UserField
     */
    protected function _getUserFieldModel()
    {
        return $this->getModelFromCache('XenForo_Model_UserField');
    }
}
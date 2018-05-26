<?php

class phc_ACPPlus_Model_ACPPlus extends XenForo_Model
{
    public function updatePositionen($ids, $table)
    {
        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);

        foreach($ids as $key => $val)
        {
            if(substr($val, 0, 1) == '_')
            {
                $val = trim(substr($val, 1));
            }

            switch($table)
            {
                case 'addons':
                    $db->update('xf_addon',
                        array(
                            'position' => $key
                        ), "addon_id = '$val'");

                    break;

                case 'options':

                    $db->update('xf_option_group',
                        array(
                            'display_order' => $key
                        ), "group_id = '$val'");
                    break;
            }
        }

        XenForo_Db::commit($db);
    }

    public function calcDBSize()
    {
        $config = XenForo_Application::getConfig();
        $db = $this->_getDb();

        return $db->fetchOne('
                              SELECT
                                sum(data_length + index_length) as dbsize
                              FROM
                                information_schema.TABLES
                              WHERE
                                TABLE_SCHEMA = "' . $config->get('db')->dbname . '"
                              GROUP BY
                                table_schema'
        );
    }

    public function getTables()
    {
        $config = XenForo_Application::getConfig();
        $db = $this->_getDb();

        $tables = $db->fetchAll('
                              SELECT
                              *, sum(data_length + index_length) as table_size
                              FROM
                                information_schema.TABLES
                              WHERE
                                TABLE_SCHEMA = "' . $config->get('db')->dbname . '"                        
                              GROUP BY
                                TABLE_NAME '
        );

        foreach($tables as &$table)
        {
            $table['rows'] = $db->fetchOne('SELECT COUNT(*) FROM ' . $table['TABLE_NAME']);
        }

        return $tables;
    }

    public function generateAttachmentTotals()
    {
        $db = $this->_getDb();

        $diskUsage = $db->fetchOne('
			SELECT SUM(file_size) AS diskusage
			FROM xf_attachment_data
		');

        $downloadCount = $this->_getDb()->fetchOne('
			SELECT SUM(view_count) AS count
			FROM xf_attachment
		');

        $attachmentTotals = array(
            'attachments_count' => $this->getModelFromCache('XenForo_Model_Attachment')->countAttachments(),
            'disk_usage' => $diskUsage,
            'download_count' => $downloadCount
        );

        $this->getModelFromCache('XenForo_Model_DataRegistry')->set('acpplus_attachment_totals', $attachmentTotals);

        return $attachmentTotals;
    }

    public function errorCounter()
    {
        return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_error_log
		');
    }

    public function getServerErrorLogsByType(array $fetchOptions = array())
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $where = $this->prepareErrorLogsConditions($fetchOptions);

        return $this->fetchAllKeyed($this->limitQueryResults(
            '
				SELECT *
				FROM xf_error_log				
				WHERE ' . $where . '
				ORDER BY exception_date DESC
			', $limitOptions['limit'], $limitOptions['offset']
        ), 'error_id');
    }

    public function countServerErrorsbyType(array $fetchOptions = array())
    {
        $where = $this->prepareErrorLogsConditions($fetchOptions);

        return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_error_log
			WHERE ' . $where . '
		');
    }

    public function prepareErrorLogsConditions(array &$fetchOptions)
    {
        $db = $this->_getDb();
        $sqlConditions = array();

        if(!empty($fetchOptions['type']))
        {
            switch($fetchOptions['type'])
            {
                default:
                case 'fatal_error':
                    $sqlConditions[] = ' (exception_type = ' . $db->quote('ErrorException') .
                                       ' OR exception_type = ' . $db->quote('XenForo_Exception') . ') ' .
                                       ' AND message NOT LIKE ' . XenForo_Db::quoteLike('Undefined ', 'lr', $db);
                    break;

                case 'mysql_error':
                    $sqlConditions[] = ' exception_type = ' . $db->quote('Zend_Db_Statement_Mysqli_Exception');
                    break;

                case 'notice':
                    $sqlConditions[] =  ' message LIKE ' . XenForo_Db::quoteLike('Undefined ', 'lr', $db);
                    break;

                case 'unknow':
                    $sqlConditions[] = ' (exception_type != ' . $db->quote('ErrorException') .
                        ' AND exception_type != ' . $db->quote('XenForo_Exception') . ') ' .
                        ' AND exception_type != ' . $db->quote('Zend_Db_Statement_Mysqli_Exception') .
                        ' AND message NOT LIKE ' . XenForo_Db::quoteLike('Undefined ', 'lr', $db);
                    break;
            }
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    public function clearServerErrorLogByType(array $fetchOptions = array())
    {
        if(empty($fetchOptions['type']))
        {
            $this->_getDb()->query('TRUNCATE TABLE xf_error_log');
        }

        $where = $this->prepareErrorLogsConditions($fetchOptions);

        $this->_getDb()->query('DELETE FROM xf_error_log WHERE ' . $where);
    }

    public function clearAdminLog($userId = 0)
    {
        if($userId == 0)
        {
            $this->_getDb()->query('TRUNCATE TABLE xf_admin_log');
        }
        else
        {
            $this->_getDb()->query('DELETE FROM xf_admin_log WHERE user_id = ?', $userId);
        }
    }

    public function clearModeratorLog($userId = 0)
    {
        if($userId == 0)
        {
            $this->_getDb()->query('TRUNCATE TABLE xf_moderator_log');
        }
        else
        {
            $this->_getDb()->query('DELETE FROM xf_moderator_log WHERE user_id = ?', $userId);
        }
    }

    public function clearUserChangeLog($userId = 0)
    {
        if($userId == 0)
        {
            $this->_getDb()->query('TRUNCATE TABLE xf_user_change_log');
        }
        else
        {
            $this->_getDb()->query('DELETE FROM xf_user_change_log WHERE edit_user_id = ?', $userId);
        }
    }

    public function clearAcpLoginLog($userId = 0)
    {
        $this->_getDb()->query("DELETE FROM phc_acpp_logins WHERE login = 'acp'");
    }

    public function dropTable($table)
    {
        $this->_getDb()->query('DROP TABLE ' . $table );
    }

    public function runQuery($query, $page = 0)
    {
        $results = array();
        $perPage = 30;

        $db = $this->_getDb();

        // Query has Not Data Output
        if(preg_match('/^\s*(alter|create|drop|rename|insert|delete|update|replace|truncate) /i', $query))
        {
            try
            {
                $queryResult = $db->query($query);
            }
            catch (Exception $e)
            {
                return array('error' => $e->getMessage());
            }

            $results['affected_rows'] = true;
            $results['rows'] = $queryResult->rowCount();
        }
        else
        {
            $start = ($page == 0 ? 0 : ($page -1) * $perPage);

            $query = preg_replace('#\sLIMIT\s+(\d+(\s*,\s*\d+)?)#i', ' ' , $query);

            try
            {
                $queryResult = $db->query($query);
            }
            catch (Exception $e)
            {
                return array('error' => $e->getMessage());
            }

            $results['rows'] = $queryResult->rowCount();

            $query = $query . ' LIMIT ' . $start . ', 30';

            $results['pages'] = ceil($results['rows'] / $perPage);

            $pages = '';
            for($i = 1; $i < $results['pages'] + 1; $i++)
            {
                $pages .= '<option value="' . $i . '"' . ($page == $i ? 'selected="selected"' : '') . '>' . $i . '</option>' . "\n";
            }

            $results['pages'] = $pages;

            $queryResult = $db->query($query);

            if($queryResult->rowCount())
            {
                $firstRow = true;

                while($row = $queryResult->fetch())
                {
                    if($firstRow)
                    {
                        $results['header'] = array_keys($row);
                        $firstRow = false;
                    }

                    $results['data'][] = array_values($row);
                }
            }
        }

        return $results;
    }

    public function getVersionStringByAddOnId($addonId)
    {
        return $this->_getDb()->fetchOne('
			SELECT version_string
			FROM xf_addon 
			WHERE addon_id = ?
		', $addonId);
    }

    public function getVersionIdByAddOnId($addonId)
    {
        return $this->_getDb()->fetchOne('
			SELECT version_id
			FROM xf_addon 
			WHERE addon_id = ?
		', $addonId);
    }

    public function canViewDebugMode(array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'acpplus', 'acpp_canViewDebugMode');
    }

    public function fetchAdminsSessions()
    {
        $sessions = $this->_getDb()->fetchAll('
				SELECT *
				FROM xf_session_admin
				WHERE expiry_date >= ?
				ORDER BY expiry_date ASC
			', array(XenForo_Application::$time));


        $admins = array();

        if($sessions)
        {
            $timeOut = (XenForo_Application::debugMode() ? 86400 : 3600);
            $timeOut = XenForo_Application::$time + ($timeOut - XenForo_Application::getOptions()->onlineStatusTimeout);

            foreach($sessions as &$session)
            {
                $session['session_data'] = @unserialize($session['session_data']);

                if(!isset($session['session_data']['user_id']))
                {
                    unset($session);
                    continue;
                }

                $session['expiry_date'] = $session['expiry_date'] - $timeOut;

                $admins[$session['session_data']['user_id']] = $session['expiry_date'];
            }
        }
    }

    public function fetchLogByHash($hash)
    {
        return $this->_getDb()->fetchRow('
                              SELECT
                                  *
                              FROM
                                  phc_acpp_acp_logins
                              WHERE
                                hash = ?
        ', $hash);


    }

    public function updateNote($addon, $text)
    {
        $db = $this->_getDb();

        $db->update('xf_addon',
            array(
                'note' => $text
            ), "addon_id = '" . $addon['addon_id'] . "'");
    }

    /*
     * Login Logs
     */
    public function logLogins($ip, $userId, $username, $status, $login)
    {
        if(!$ip)
            return false;

        $hash = '';

        if(!$userId)
            $userId = 0;

        if($login == 'acp' && $status == 'success')
        {
            $hash = sha1(md5(XenForo_Application::$time . $ip . $userId . XenForo_Application::generateRandomString(256, true)));
        }

        if(utf8_strlen($username) > 50)
            $username = substr($username, 0, 50);

        $this->_getDb()->query('
			INSERT INTO phc_acpp_logins
				(user_id, data, status, login, dateline, hash, ip)
			VALUES
				(?, ?, ?, ?, ?, ?, ?)
		', array($userId, $username, $status, $login, XenForo_Application::$time, $hash, $ip));

        return $hash;
    }

    public function fetchLogLogins(array $conditions = array(), array $fetchOptions = array())
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $whereConditions = $this->prepareLogLoginsConditions($conditions, $fetchOptions);

        return $this->fetchAllKeyed($this->limitQueryResults(
            "
				SELECT logins.*
				FROM phc_acpp_logins AS logins
				WHERE $whereConditions
				ORDER BY logins.dateline DESC
			", $limitOptions['limit'], $limitOptions['offset']
        ), 'log_id');
    }

    public function countLogLogins(array $conditions = array())
    {
        $fetchOptions = array();
        $whereConditions = $this->prepareLogLoginsConditions($conditions, $fetchOptions);

        return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM phc_acpp_logins AS logins
			WHERE $whereConditions
		");
    }

    public function prepareLogLoginsConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if(!empty($conditions['login']))
        {
            $sqlConditions[] = 'logins.login = ' . $db->quote($conditions['login']);
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    public function prepareLogLogins($logs)
    {
        if($logs)
        {
            foreach($logs as &$log)
            {
                $log['status'] = new XenForo_Phrase('acpp_log_logins_' . $log['status']);
            }
        }

        return $logs;
    }

    /*
     * Blocked Log
     */
    public function fetchBlockedLogsByType(array $conditions = array(), array $fetchOptions = array())
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $whereConditions = $this->prepareBlockedLogsConditions($conditions, $fetchOptions);

        return $this->fetchAllKeyed($this->limitQueryResults(
            "
				SELECT bannlog.*
				FROM phc_acpp_banned_log AS bannlog
				WHERE $whereConditions
				ORDER BY bannlog.dateline DESC
			", $limitOptions['limit'], $limitOptions['offset']
        ), 'bann_id');
    }

    public function countBlockedLogsByType(array $conditions = array())
    {
        $fetchOptions = array();
        $whereConditions = $this->prepareBlockedLogsConditions($conditions, $fetchOptions);

        return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM phc_acpp_banned_log AS bannlog
			WHERE $whereConditions
		");
    }

    public function prepareBlockedLogsConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if(!empty($conditions['type']))
        {
            switch($conditions['type'])
            {
                case 'referrer':
                    $sqlConditions[] = 'bannlog.type = ' . $db->quote($conditions['type']);
                    break;

                case 'useragent':
                    $sqlConditions[] = 'bannlog.type = ' . $db->quote($conditions['type']);
                    break;
            }
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    public function writeBlockedLog($type, $value, $ipAddress)
    {
        $userInfos = array(
            'url' => '',
            'referrer' => '',
            'remote_address' => '',
            'user_agent' => '',
        );

        if(!empty($_SERVER['REQUEST_URI']))
            $userInfos['url'] = $_SERVER['REQUEST_URI'];

        if(!empty($_SERVER['HTTP_REFERER']))
            $userInfos['referrer'] = $_SERVER['HTTP_REFERER'];

        if(!empty($_SERVER['REMOTE_ADDR']))
            $userInfos['remote_address'] = $_SERVER['REMOTE_ADDR'];

        if(!empty($_SERVER['HTTP_USER_AGENT']))
            $userInfos['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        $this->_getDb()->query('
			INSERT INTO phc_acpp_banned_log
				(dateline, type, value, data, ip_address)
			VALUES
				(?, ?, ?, ?, ?)
		', array(XenForo_Application::$time, $type, $value, serialize($userInfos), $ipAddress));
    }

    /*
     * DELETE USER
     */
    public function getPostsByUserId($userId, $limit = 100)
    {
        return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT post.*
			FROM xf_post AS post
			WHERE post.user_id = ?
		', $limit),
            $userId,
            'post_id');
    }

    public function getProfilePostsByUserId($userId, $limit = 100)
    {
        return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT profile_post.*
			FROM xf_profile_post AS profile_post
			WHERE profile_post.user_id = ?
		', $limit), 'profile_post_id', $userId);
    }

    public function getProfilePostCommentsByUserId($userId, $limit)
    {
        return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT profile_post_comment.*
			FROM xf_profile_post_comment AS profile_post_comment
			WHERE profile_post_comment.user_id = ?
		', $limit), 'profile_post_comment_id', $userId);
    }

    public function getMediaByUserId($userId, $limit = 100)
    {
        return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT media.*
			FROM xengallery_media AS media
			WHERE media.user_id = ?
		', $limit), 'media_id', $userId);
    }

    public function getMediaCommentsByUserId($userId, $limit = 100)
    {
        return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT media_comment.*
			FROM xengallery_comment AS media_comment
			WHERE media_comment.user_id = ?
		', $limit), 'comment_id', $userId);
    }

    public function getResourcesByUserId($userId, $limit = 100)
    {
        return $this->fetchAllKeyed($this->limitQueryResults('
				SELECT resource.*
				FROM xf_resource AS resource
				WHERE resource.user_id = ?
			', $limit), 'resource_id', $userId);
    }

    /*
     * Soft Threads/Posts
     */
    public function fetchSoftThreads($fetchOptions)
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->fetchAllKeyed($this->limitQueryResults('
                                                                    SELECT
                                                                        thread.*, node.title AS node_title, log.*
                                                                    FROM
                                                                        xf_thread as thread
                                                                    
                                                                    LEFT JOIN
                                                                        xf_node AS node USING(node_id)
                                                                    
                                                                    LEFT JOIN
                                                                        xf_deletion_log as log ON (log.content_id = thread.thread_id)
                                                                    
                                                                    WHERE
                                                                        discussion_state = \'deleted\'
                                                                    
                                                                    ORDER BY
                                                                        log.delete_date DESC
       ', $limitOptions['limit'], $limitOptions['offset']
        ), 'thread_id');
    }

    public function countSoftThreads()
    {
        return $this->_getDb()->fetchOne('
                              SELECT
                                  COUNT(*)
                              FROM
                                  xf_thread
                              WHERE
                                discussion_state = \'deleted\'
        ');
    }

    /*
     * Phrases
     */
    public function fetchPhraseInLanguageByTitles(array $titles, $languageId)
    {
        return $this->fetchAllKeyed('
			SELECT *
			FROM xf_phrase
			WHERE title IN (' . $this->_getDb()->quote($titles) . ')
			AND language_id = ?
		', 'title', $languageId);
    }


    public function updateRobotActivity($robotId)
    {
        try
        {
            $db = $this->_getDb();
            $db->update('phc_acpp_spiders',
                array('last_activity' => XenForo_Application::$time),
                ' LOWER(robot_id) = ' . $db->quote($robotId)
            );
        }
        catch (Zend_Db_Exception $e) {}
    }

    public function fetchAllRobots()
    {
        return $this->fetchAllKeyed('
                                            SELECT
                                                *
                                            FROM
                                                phc_acpp_spiders
                                                
                                            ORDER BY
                                                active DESC, title
       ', 'spider_id');
    }

    public function fetchRobotById($spiderId)
    {
        return $this->_getDb()->fetchRow('
                                            SELECT
                                                *
                                            FROM
                                                phc_acpp_spiders
                                                
                                            WHERE spider_id = ?
       ', $spiderId);
    }

    public function fetchRobotByRobotId($robotId)
    {
        return $this->_getDb()->fetchRow('
                                            SELECT
                                                *
                                            FROM
                                                phc_acpp_spiders
                                                
                                            WHERE robot_id = ?
       ', $robotId);
    }

    public function fetchPublicRoutes()
    {
        return $this->fetchAllKeyed('
			SELECT route_prefix.*, addon.title, filter.replace_route

			FROM xf_route_prefix AS route_prefix
			LEFT JOIN xf_addon  as addon USING (addon_id)
			
			LEFT JOIN xf_route_filter  as filter on (filter.prefix = route_prefix.original_prefix)
			
			WHERE route_prefix.route_type = \'public\'

			ORDER BY addon.title
		', 'original_prefix');
    }

    public function fetchSoftPosts($fetchOptions)
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->fetchAllKeyed($this->limitQueryResults('
                                                                    SELECT
                                                                        post.*, thread.title, log.*
                                                                    FROM
                                                                        xf_post as post
                                                                        
                                                                    LEFT JOIN
                                                                        xf_thread as thread USING(thread_id)
                                                                    
                                                                    LEFT JOIN
                                                                        xf_node as node ON (node.node_id = thread.node_id)
                                                                    
                                                                    LEFT JOIN
                                                                        xf_deletion_log as log ON (log.content_id = post.post_id)
                                                                    
                                                                    WHERE
                                                                        post.message_state = \'deleted\'
                                                                        
                                                                    AND post.position > 0    
                                                                    
                                                                    ORDER BY
                                                                        log.delete_date DESC
       ', $limitOptions['limit'], $limitOptions['offset']
        ), 'post_id');
    }

    public function countSoftPosts()
    {
        return $this->_getDb()->fetchOne('
                              SELECT
                                  COUNT(*)
                              FROM
                                  xf_post as post
                              WHERE
                                post.message_state = \'deleted\'
                              AND post.position > 0
        ');
    }

    public function toggleTempalteFavorit($tId)
    {
        $db = $this->_getDb();

        $exists = $db->fetchOne('SELECT favorit FROM xf_template WHERE template_id = ?', $tId);

        $newFavoritValue = ($exists ? 0 : 1);

        $db->update('xf_template', array('favorit' => $newFavoritValue), "template_id = $tId");

        return $newFavoritValue;
    }

    public function getTemplateFavorites()
    {
        return $this->fetchAllKeyed('
			SELECT tpl.template_id, tpl.title, tpl.style_id, style.title as style_title
			FROM xf_template as tpl
			LEFT JOIN xf_style as style USING(style_id)
			WHERE favorit = 1
			ORDER BY CONVERT(tpl.title USING utf8)
		', 'template_id');
    }

    public function checkIfATPInstalled()
    {
        return $this->_getDb()->fetchOne('SELECT active FROM xf_addon WHERE addon_id = \'phc_AttachmentPlus\' ');
    }

}
<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Log extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Log
{
	public function actionServerError()
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);

        if(!$type)
            $type = 'fatal_error';

        $ACPPlusModel = $this->_getACPPlusModel();

        $id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		if($id)
		{
			$entry = $this->_getLogModel()->getServerErrorLogById($id);
			if (!$entry)
			{
				return $this->responseError(new XenForo_Phrase('requested_server_error_log_entry_not_found'), 404);
			}

			$entry['requestState'] = XenForo_Helper_Php::safeUnserialize($entry['request_state']);

			$viewParams = array(
				'entry' => $entry
			);
			return $this->responseView('XenForo_ViewAdmin_Log_ServerErrorView', 'log_server_error_view', $viewParams);
		}
		else
		{
			$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
			$perPage = 10;

            $errorLogsCounts = array(
                'fatal_errors' => $ACPPlusModel->countServerErrorsbyType(array('type' => 'fatal_error')),
                'mysql_errors' => $ACPPlusModel->countServerErrorsbyType(array('type' => 'mysql_error')),
                'notice_errors' => $ACPPlusModel->countServerErrorsbyType(array('type' => 'notice')),
                'unknow_errors' => $ACPPlusModel->countServerErrorsbyType(array('type' => 'unknow')),
            );

			$viewParams = array(
				'entries' => $ACPPlusModel->getServerErrorLogsByType(array(
					'page' => $page,
					'perPage' => $perPage,
                    'type' => $type,
				)),

				'allEntries' => $this->_getLogModel()->countServerErrors(),

				'page' => $page,
				'perPage' => $perPage,
				'total' => $ACPPlusModel->countServerErrorsbyType(array(
                    'type' => $type,
                )),
				'type' => $type,
				'type_phrase' => new XenForo_Phrase('acpp_' . $type),
				'error_counts' => $errorLogsCounts,

                'pageParams' => array('type' => $type),
			);

			if(!empty($viewParams['entries']))
            {
                foreach($viewParams['entries'] as &$entry)
                {
                    $entry['requestState'] = XenForo_Helper_Php::safeUnserialize($entry['request_state']);
                    $entry['requestState'] = print_r($entry['requestState'], true);
                }
            }

			return $this->responseView('XenForo_ViewAdmin_Log_ServerError', 'log_server_error', $viewParams);
		}
	}

	public function actionServerErrorPrint()
    {
        $id = $this->_input->filterSingle('id', XenForo_Input::UINT);

        $entry = $this->_getLogModel()->getServerErrorLogById($id);
        if (!$entry)
        {
            return $this->responseError(new XenForo_Phrase('requested_server_error_log_entry_not_found'), 404);
        }

        $entry['requestState'] = XenForo_Helper_Php::safeUnserialize($entry['request_state']);

        $viewParams = array(
            'entry' => $entry
        );

        return $this->responseView(
            'XenForo_ViewAdmin_Log_ServerErrorView',
            'acpp_log_server_error_view',
            $viewParams,
            array('containerTemplate' =>
                'PAGE_CONTAINER_SIMPLE')
        );
    }

    public function actionServerErrorClearType()
    {
        $type = $this->_input->filterSingle('type', XenForo_Input::STRING);

        if($this->isConfirmedPost())
        {
            $this->_getACPPlusModel()->clearServerErrorLogByType(array('type' => $type));

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('logs/server-error')
            );
        }
        else
        {
            $phrase = new XenForo_Phrase('acpp_' . $type);
            $viewParams = array(
                'type' => $type,
                'type_phrase' => $phrase->render(),
            );
            return $this->responseView('XenForo_ViewAdmin_Log_ServerErrorDelete', 'acpp_log_server_error_clear', $viewParams);
        }
    }

    public function actionBlockedLogReferrer()
    {
        $this->_request->setParam('type', 'referrer');
        return $this->responseReroute(__CLASS__, 'BlockedLog');
    }

    public function actionBlockedLogUseragent()
    {
        $this->_request->setParam('type', 'useragent');
        return $this->responseReroute(__CLASS__, 'BlockedLog');
    }

    public function actionBlockedLog()
    {
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $type = $this->_input->filterSingle('type', XenForo_Input::STRING);

        $perPage = 20;

        $ACPPlusModel = $this->_getACPPlusModel();
        $conditions = array(
            'type' => $type
        );

        $logs = $ACPPlusModel->fetchBlockedLogsByType($conditions, array(
            'page' => $page,
            'perPage' => $perPage
        ));

        if($logs)
        {
            foreach($logs as &$log)
            {
                $log['ip_address'] = XenForo_Helper_Ip::convertIpBinaryToString($log['ip_address']);
            }
        }

        $totalLogs = $ACPPlusModel->countBlockedLogsByType($conditions);

        $viewParams = array(
            'page' => $page,
            'perPage' => $perPage,

            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'type' => $type,
        );
        return $this->responseView('XenForo_ViewAdmin_Log_BlockedReferrer', 'acpp_log_blocked_referrer', $viewParams);
    }

    public function actionLogins()
    {
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $type = $this->_input->filterSingle('type', XenForo_Input::STRING);

        $perPage = 20;

        $ACPPlusModel = $this->_getACPPlusModel();
        $conditions = array(
            'login' => 'acp'
        );

        $logs = $ACPPlusModel->fetchLogLogins($conditions, array(
            'page' => $page,
            'perPage' => $perPage
        ));

        $logs = $ACPPlusModel->prepareLogLogins($logs);

        $totalLogs = $ACPPlusModel->countLogLogins($conditions);

        $viewParams = array(
            'page' => $page,
            'perPage' => $perPage,

            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'type' => $type,
        );
        return $this->responseView('XenForo_ViewAdmin_Log_BlockedReferrer', 'acpp_log_logins', $viewParams);
    }

    public function actionLoginsClear()
    {
        $this->_getACPPlusModel()->clearAcpLoginLog();

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('logs/logins')
        );
    }


    public function actionAdmin()
    {
        $res = parent::actionAdmin();

        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        if($this->_input->inRequest('user_id') && $userId)
        {
            $user = $this->_getUserModel()->getUserById($userId);

            $userName = 'Unknow';
            if($user)
            {
                $userName = $user['username'];
            }

            $res->params['username'] = $userName;
        }

        return $res;
    }

    public function actionAdminClear()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        $this->_getACPPlusModel()->clearAdminLog($userId);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('logs/admin')
        );
    }


    public function actionModerator()
    {
        $res = parent::actionModerator();

        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        if($this->_input->inRequest('user_id') && $userId)
        {
            $user = $this->_getUserModel()->getUserById($userId);

            $userName = 'Unknow';
            if($user)
            {
                $userName = $user['username'];
            }

            $res->params['username'] = $userName;
        }

        return $res;
    }

    public function actionModeratorClear()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        $this->_getACPPlusModel()->clearModeratorLog($userId);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('logs/moderator')
        );
    }


    public function actionUserChangeLog()
    {
        $res = parent::actionUserChangeLog();

        if(isset($res->params['pageNavParams']['edit_user_id']))
        {
            $user = $this->_getUserModel()->getUserById($res->params['pageNavParams']['edit_user_id']);

            $userName = 'Unknow';
            if($user)
            {
                $userName = $user['username'];
            }

            $res->params['username'] = $userName;
            $res->params['userId'] = $res->params['pageNavParams']['edit_user_id'];
        }

        return $res;
    }

    public function actionUserChangeLogClear()
    {
        $userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

        $this->_getACPPlusModel()->clearUserChangeLog($userId);

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('logs/user-change-log')
        );
    }


    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }

    /**
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}
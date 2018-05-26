<?php

class phc_ACPPlus_ControllerAdmin_ACPPlus extends XenForo_ControllerAdmin_Abstract
{
    protected function _preDispatch($action)
    {
        @set_time_limit(0);
        $this->_checkAdminStatus();
    }

    public function actionTableList()
    {
        $xfTables = $this->_getACPPlusModel()->getTables();

        $totalSize = 0;
        foreach($xfTables as $table)
        {
            $totalSize += $table['table_size'];
        }

        $params = array(
            'xf_tables' => $xfTables,
            'total_size' => $totalSize,
        );

        return $this->responseView('phc_ACPPlus_ViewAdmin_DBList', 'acpp_dbtools_tablelist', $params);
    }

    public function actionTableListDelete()
    {
        $tablename = $this->_input->filterSingle('table', XenForo_Input::STRING);

        if($this->isConfirmedPost())
        {
            $this->_getACPPlusModel()->dropTable($tablename);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('table-list')
            );
        }
        else
        {
            $viewParams = array(
                'table' => $tablename
            );

            return $this->responseView('phc_ACPPlus_ViewAdmin_DBList', 'acpp_dbtools_table_delete', $viewParams);
        }
    }

    public function actionSqlQuery()
    {
        $acpPlusModel = $this->_getACPPlusModel();

        if($this->isConfirmedPost())
        {
            $sqlQuery = $this->_input->filterSingle('sqlquery', XenForo_Input::STRING);
            $page = $this->_input->filterSingle('page', XenForo_Input::UINT);

            if(!$sqlQuery)
                return $this->responseError(new XenForo_Phrase('acpp_dbtools_sqlquery_please_provide_query'));

            $queryResult = $acpPlusModel->runQuery($sqlQuery, $page);

            $params = array(
                'result' => $queryResult,
                'sqlquery' => $sqlQuery,
            );

            return $this->responseView('phc_ACPPlus_ViewAdmin_DBList', 'acpp_dbtools_sqlquery_result', $params);
        }
        else
        {
            $params = array(

            );

            return $this->responseView('phc_ACPPlus_ViewAdmin_DBList', 'acpp_dbtools_sqlquery', $params);
        }
    }

    protected function _checkAdminStatus()
    {
        if(!XenForo_Visitor::getInstance()->isSuperAdmin() || !XenForo_Application::getConfig()->acpp_sql_password)
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_dbtools_no_accesss')));

        $pw = md5(XenForo_Application::getConfig()->acpp_sql_password);
        $sendPw = md5($this->_input->filterSingle('password', XenForo_Input::STRING));

        $cookie = XenForo_Helper_Cookie::getCookie('acp_dbtools');

        if($cookie == $pw)
        {
            return true;
        }

        if($this->isConfirmedPost())
        {
            if($sendPw != $pw)
            {
                throw $this->responseException($this->responseNoPermission());
            }
            else
            {
                XenForo_Helper_Cookie::setCookie('acp_dbtools', $sendPw, 86400 * 30, true);
                unset($_POST['_xfConfirm']);
                return true;
            }
        }
        else
        {
            throw $this->responseException(
                $this->responseReroute('XenForo_ControllerAdmin_Error', 'loginRequired')
            );
        }
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
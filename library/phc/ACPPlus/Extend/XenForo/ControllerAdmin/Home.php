<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Home extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Home
{
	public function actionIndex()
	{
        $GLOBALS['bypassAddOnList'] = true;

        $res = parent::actionIndex();

        $db = XenForo_Application::getDb();

        $options = XenForo_Application::getOptions();

        $acppSecurity = new phc_ACPPlus_Helper_Security();

        $acpPlusModel = $this->_getACPPlusModel();

        $acpPlusArray = array();

        $acpPlusArray['db_size'] = XenForo_Locale::numberFormat($acpPlusModel->calcDBSize(), 'size');

        if(!$acpPlusModel->checkIfATPInstalled())
        {
            $attachmentTotals = XenForo_Model::create('XenForo_Model_DataRegistry')->get('acpplus_attachment_totals');
            if(!$attachmentTotals)
            {
                $attachmentTotals = $acpPlusModel->generateAttachmentTotals();
            }
            $acpPlusArray['attachment_totals'] = $attachmentTotals;
        }
        else
        {
            $res->params['atpExists'] = true;
        }


        $acpPlusArray['debug_mode'] = XenForo_Application::getSimpleCacheData('acpp_debug_mode');
        $acpPlusArray['errorCounter'] = $acpPlusModel->errorCounter();

        /*
         * Server Umgebung
         */
        if($options->acpp_showHostInfos)
        {
            $serverCacheInfos = '';
            if(!XenForo_Application::debugMode())
                $serverCacheInfos = XenForo_Application::getSimpleCacheData('acpp_server_infos');

            if(!$serverCacheInfos)
            {
                $acpPlusArray['server']['host']['host_ip'] = gethostbyname(gethostname());
                $acpPlusArray['server']['host']['host_name'] = gethostname();
                $acpPlusArray['server']['host']['host_isp'] = '';
                $acpPlusArray['server']['host']['host_region'] = '';
                $acpPlusArray['server']['host']['host_countrycode'] = '';

                phc_ACPPlus_Helper_ACPPlus::fetchHostInfos($acpPlusArray);

                XenForo_Application::setSimpleCacheData('acpp_server_infos', serialize($acpPlusArray['server']));
            }
            else
            {
                $acpPlusArray['server'] = @unserialize($serverCacheInfos);
            }
        }

        if($options->acpp_showServerInfos)
        {
            $acpPlusArray['server']['memorylimit'] = phc_ACPPlus_Helper_ACPPlus::getMemoryLimit();
            $acpPlusArray['server']['php_version'] = phpversion();
            $acpPlusArray['server']['mysql_version'] = $db->fetchOne("SELECT VERSION() AS version");

            $acpPlusArray['server']['os'] = PHP_OS;
            $acpPlusArray['server']['software'] = phc_ACPPlus_Helper_ACPPlus::getWebServer(true);

            // LIVE SYSTEM DATA
            $acpPlusArray['server']['cpu_load'] = phc_ACPPlus_Helper_ACPPlus::cpuLoad();
            $acpPlusArray['server']['memory_usage'] = memory_get_usage();
        }

        if($options->acpp_showSecurityChmodInfos)
            $acpPlusArray['checkPermissionsFiles'] = $acppSecurity->checkPermissionsFiles();



        // AddOnList
        $listType = $this->_input->filterSingle('listType', XenForo_Input::STRING);

        if(!$listType)
            $listType = 'active';

        $acpPlusArray['listType'] = $listType;
        $acpPlusArray['tabNav'] = 'index';

        // check XenForo's Versionen
        $newVersion = null;
        $acpPlusArray['newVersions'] = array();
        $xfVersions = @unserialize(XenForo_Application::getSimpleCacheData('acpp_xf_versions'));

        if($xfVersions)
        {
            foreach($xfVersions as $key => $xfVersion)
            {
                if(isset($res->params['addOns'][$key]) && $res->params['addOns'][$key]['active'])
                {
                    if(phc_ACPPlus_Helper_ACPPlus::checkIfNewVersion($xfVersion, $key, $res->params['addOns'][$key]['version_string']))
                    {
                        $acpPlusArray['newVersions'][$key] = new XenForo_Phrase('acpp_new_version_for_x_version_y', array(
                            'addon_title' => $res->params['addOns'][$key]['title'],
                            'new_version' => $xfVersion,
                        ));
                    }
                }
            }
        }

        // Gehe AddOn Array Durch um querys zu sparen!
        if($res->params['addOns'])
        {
            foreach($res->params['addOns'] as $key => $addon)
            {
                if($addon['active'] == true && $listType == 'disabled')
                {
                    unset($res->params['addOns'][$key]);
                }
                elseif($addon['active'] == false && $listType == 'active')
                {
                    unset($res->params['addOns'][$key]);
                }
            }
        }

        // Template Favoriten
        $canEditStyles = XenForo_Visitor::getInstance()->hasAdminPermission('style');
        if($canEditStyles)
        {
            $res->params['favorites'] = $acpPlusModel->getTemplateFavorites();
            $res->params['canEditStyles'] = $canEditStyles;
        }

        $res->params = array_merge($res->params, $acpPlusArray);

        return $res;
	}

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }

    /**
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }
}
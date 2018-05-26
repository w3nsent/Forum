<?php


class phc_ACPPlus_Helper_BadReferrerAgent
{
    private $_db = null;

    private $_ipAddress = null;

    public function __construct()
    {
        $this->_db = XenForo_Application::getDb();

        $this->_ipAddress = XenForo_Helper_Ip::getBinaryIp();
    }

    public function checkBadReferrer()
    {
        if(empty($_SERVER['HTTP_REFERER']))
        {
            return false;
        }

        $referrer = strtolower($_SERVER['HTTP_REFERER']);

        $badReferrers = preg_split('/(\r\n|\n|\r)+/', strtolower(XenForo_Application::getOptions()->acpp_blacklistReferrer), -1, PREG_SPLIT_NO_EMPTY);

        foreach($badReferrers as $badReferrer)
        {
            $refData = @parse_url($referrer);

            if(!empty($refData['host']))
            {
                $referrer = $refData['host'];
            }

            if($referrer == $badReferrer)
            {
                XenForo_Model::create('phc_ACPPlus_Model_ACPPlus')->writeBlockedLog('referrer', $badReferrer, $this->_ipAddress);
                unset($badReferrers);
                return true;
            }
        }

        unset($badReferrers);
        return false;
    }

    public function checkBadAgent()
    {
        if(empty($_SERVER['HTTP_USER_AGENT']))
        {
            return false;
        }

        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

        $badUserAgents = preg_split('/(\r\n|\n|\r)+/', strtolower(XenForo_Application::getOptions()->acpp_blacklistUserAgent), -1, PREG_SPLIT_NO_EMPTY);

        foreach($badUserAgents as $badUserAgent)
        {
            if(strpos($userAgent, $badUserAgent) !== false)
            {
                XenForo_Model::create('phc_ACPPlus_Model_ACPPlus')->writeBlockedLog('useragent', $badUserAgent, $this->_ipAddress);
                unset($badUserAgents);
                return true;
            }
        }

        unset($badUserAgents);
        return false;
    }
}
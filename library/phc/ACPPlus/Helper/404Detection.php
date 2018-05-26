<?php


class phc_ACPPlus_Helper_404Detection
{
    private $_db = null;

    private $_ipAddress = null;

    private $_requestUrl = null;

    public function __construct()
    {
        $this->_db = XenForo_Application::getDb();

        $this->_ipAddress = XenForo_Helper_Ip::getBinaryIp();

        $this->_requestUrl = str_replace('/index.php?', '', $_SERVER['REQUEST_URI']);

        // Clear Log
        $this->cleanUpLogs();
    }

    public function checkUser()
    {
        $referrer = '';
        if(!empty($_SERVER['HTTP_REFERER']))
            $referrer = $_SERVER['HTTP_REFERER'];

        $whiteUrlList = @unserialize(XenForo_Application::getSimpleCacheData('acpp_404_white_url_list'));
        if(!is_array($whiteUrlList))
            $whiteUrlList = array();

        //WhiteExtension
        $whiteListExtensions = preg_split('/(\r\n|\n|\r)+/', XenForo_Application::getOptions()->acpp_404WhiteExtensions, -1, PREG_SPLIT_NO_EMPTY);
        if(!is_array($whiteListExtensions))
            $whiteListExtensions = array();

        // White List?
        if(in_array($this->_requestUrl, $whiteUrlList))
            return false;

        // Extension on white list?
        $pathInfo = @pathinfo($this->_requestUrl);
        $ext = '';
        if(isset($pathInfo['extension']))
            $ext = '.' . $pathInfo['extension'];

        if(in_array($ext, $whiteListExtensions))
            return false;

        //Write 404 Log
        $this->write404Log($this->_ipAddress, $referrer, array());
    }

    public function checkUserBlocked()
    {
        // Block?
        if($this->countIpLogs($this->_ipAddress) > 5)
        {
            return true;
        }

        return false;
    }

    protected function write404Log($ipAddress, $referrer, array $data = array())
    {
        if(!$ipAddress)
        {
            return false;
        }

        $this->_db->insert('phc_acpp_404log', array(
            'dateline' => XenForo_Application::$time,
            'ip' => $ipAddress,
            'url' => $this->_requestUrl,
            'referrer' => $referrer,
            'data' => serialize($data)
        ));
    }

    protected function countIpLogs($ipAddress)
    {
        $periode = XenForo_Application::$time - (60 * 60);

        return $this->_db->fetchOne('
                                  SELECT COUNT(*) 
                                  FROM phc_acpp_404log 
                                  WHERE ip = ?
                                  AND dateline > ?
        
            ', array($ipAddress, $periode)
        );
    }

    protected function cleanUpLogs()
    {
        $periode = XenForo_Application::$time - (60 * 60);
        $this->_db->query('DELETE FROM phc_acpp_404log WHERE dateline <= ?', $periode);
    }
}
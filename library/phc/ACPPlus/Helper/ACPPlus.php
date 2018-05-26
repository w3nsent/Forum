<?php

class phc_ACPPlus_Helper_ACPPlus
{
    public static $_htaccessFile = '.htaccess';

    public static $_spiders = null;

    public static $_xfController = array(
        'XenForo_ControllerPublic_Index',
        'XenForo_ControllerPublic_Forum',
        'XenForo_ControllerPublic_Account',
        'XenForo_ControllerPublic_Attachment',
        'XenForo_ControllerPublic_Category',
        'XenForo_ControllerPublic_Conversation',
        'XenForo_ControllerPublic_EditHistory',
        'XenForo_ControllerPublic_Editor',
        'XenForo_ControllerPublic_FindNew',
        'XenForo_ControllerPublic_Goto',
        'XenForo_ControllerPublic_Help',
        'XenForo_ControllerPublic_LinkForum',
        'XenForo_ControllerPublic_Login',
        'XenForo_ControllerPublic_Logout',
        'XenForo_ControllerPublic_LostPassword',
        'XenForo_ControllerPublic_Member',
        'XenForo_ControllerPublic_ModerationQueue',
        'XenForo_ControllerPublic_Online',
        'XenForo_ControllerPublic_Page',
        'XenForo_ControllerPublic_Post',
        'XenForo_ControllerPublic_ProfilePost',
        'XenForo_ControllerPublic_RecentActivity',
        'XenForo_ControllerPublic_Register',
        'XenForo_ControllerPublic_Search',
        'XenForo_ControllerPublic_Tag',
        'XenForo_ControllerPublic_Thread',
        'XenForo_ControllerPublic_Warning',
        'XenForo_ControllerPublic_Watched',
        'XenForo_ControllerPublic_Misc',
    );


    public static function cpuLoad()
    {
        $load = 0;
        if('WIN' == strtoupper(substr(PHP_OS, 0, 3)))
        {
            @exec('wmic cpu get LoadPercentage', $sys_load);

            if(!empty($sys_load[1]))
                $load = $sys_load[1];
        }
        else
        {
            $sys_load = @sys_getloadavg();

            if(!empty($sys_load[0]))
                $load = $sys_load[0];
        }

        return (int)$load;
    }

    public static function getMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');

        if(preg_match('/^(\d+)(.)$/', $memoryLimit, $matches))
        {
            if(!empty($matches[2]))
            {
                switch(strtolower($matches[2]))
                {
                    case 'k':
                        $matches[1] = $matches[1]  * 1024;
                        break;

                    case 'm':
                        $matches[1] = $matches[1] * 1024 * 1024;
                        break;

                    case 'G':
                        $matches[1] = $matches[1] * 1024 * 1024 * 1024;
                        break;

                    case 'T':
                        $matches[1] = $matches[1] * 1024 * 1024 * 1024 * 1024;
                        break;

                    case 'P':
                        $matches[1] = $matches[1] * 1024 * 1024 * 1024 * 1024 * 1024;
                        break;
                }

                $memoryLimit = $matches[1];

                if($memoryLimit < 0)
                    $memoryLimit = 0;
            }
        }

        return $memoryLimit;
    }

    public static function checkIfNewVersion($version, $product = 'xenforo', $productVersion = 0)
    {
        switch($product)
        {
            case 'xenforo':
                $productVersion = XenForo_Application::$version;
                break;

            case 'XenResource':
                if($productVersion == 0)
                    $productVersion = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus')->getVersionStringByAddOnId($product);
                break;

            case 'XenGallery':
                if($productVersion == 0)
                    $productVersion = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus')->getVersionStringByAddOnId($product);
                break;

            case 'XenES':
                if($productVersion == 0)
                    $productVersion = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus')->getVersionStringByAddOnId($product);
                break;
        }

        $version = strtolower($version);
        $productVersion = strtolower($productVersion);

        if(version_compare($productVersion, $version))
        {
            return true;
        }

        return false;
    }

    public static function getWebServer($raw = false)
    {
        $server = '';

        if(isset($_SERVER['SERVER_SOFTWARE']))
            $server = strtolower($_SERVER['SERVER_SOFTWARE']);

        if(strpos($server, 'apache' ) !== false)
        {
            if($raw)
            {
                return $_SERVER['SERVER_SOFTWARE'];
            }
            else
            {
                return 'apache';
            }
        }
    }

    public static function getXFPath($mode = '')
    {
        $xfLibraryPath = XenForo_Autoloader::getInstance()->getRootDir();
        $xfLibraryPath = str_replace('/', DIRECTORY_SEPARATOR, str_replace('\\', DIRECTORY_SEPARATOR, $xfLibraryPath));
        $xfLibraryPath = rtrim($xfLibraryPath, DIRECTORY_SEPARATOR);

        switch($mode)
        {
            case 'home':
                $path = str_replace(DIRECTORY_SEPARATOR . 'library', '', $xfLibraryPath);
                break;

            case 'install':
                $path = str_replace(DIRECTORY_SEPARATOR . 'library', DIRECTORY_SEPARATOR . 'install', $xfLibraryPath);
                break;

            case 'js':
                $path = str_replace(DIRECTORY_SEPARATOR . 'library', DIRECTORY_SEPARATOR . 'js', $xfLibraryPath);
                break;

            default:
            case 'library':
                $path = $xfLibraryPath;
                break;
        }

        return $path;
    }

    public static function getFilterData($data, $type)
    {
        if($data == 'all')
        {
            XenForo_Helper_Cookie::deleteCookie($type);
            $GLOBALS[$type] = '';
            return false;
        }

        if($data)
        {
            XenForo_Helper_Cookie::setCookie($type, $data);
        }
        else
        {
            $data = XenForo_Helper_Cookie::getCookie($type);
        }

        $GLOBALS[$type] = $data;
        return true;
    }

    public static function fetchHostInfos(&$acpPlusArray)
    {
        $query = $acpPlusArray['server']['host']['host_ip'];

        // ist valid IP? nein? Check Host
        if(!filter_var($query, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE))
        {
            $query = preg_replace("/^([a-zA-Z0-9].*\.)?([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z.]{2,})$/", '$2', $acpPlusArray['server']['host']['host_name']);

            if(!checkdnsrr($query))
                return false;
        }

        $xml = array();
        $hostInfos = '';

        try
        {
            $client = XenForo_Helper_Http::getClient('http://www.ip-api.com/xml/' . $query);
            $request = $client->request('GET');
            $hostInfos = $request->getBody();
        }
        catch(Exception $e) {}

        if($hostInfos)
            $xml = @simplexml_load_string($hostInfos);

        if(!empty($xml->isp))
            $acpPlusArray['server']['host']['host_isp'] = (string)$xml->isp;

        if(!empty($xml->city))
            $acpPlusArray['server']['host']['host_region'] = (string)$xml->city;

        if(!empty($xml->regionName))
            $acpPlusArray['server']['host']['host_region'] .= ', ' . (string)$xml->regionName;

        if(!empty($xml->countryCode))
            $acpPlusArray['server']['host']['host_countrycode'] = (string)$xml->countryCode;

        return true;
    }

    public static function countAdminsOnline()
    {
        self::_getACPPlusModel()->fetchAdminsSessions();
    }

    public static function getUserImportMatrix()
    {
        $xfDefaultMatrix =  array(
            NULL => 'do_not_import',

            'standard' => array(
                'user_id' => 'user_id',
                'username' => 'username',
                'email' => 'email',
                'user_group_id' => 'user_group_id',
                'secondary_group_ids' => 'secondary_group_ids',
                'gender' => 'gender',
                'custom_title' => 'custom_title',
                'timezone' => 'timezone',
                'message_count' => 'message_count',
                'register_date' => 'register_date',
                'last_activity' => 'last_activity',
                'is_moderator' => 'is_moderator',
                'is_admin' => 'is_admin',
                'is_banned' => 'is_banned',
                'is_staff' => 'is_staff'
            ),

            'options' => array(
                'show_dob_year' => 'show_dob_year',
                'show_dob_date' => 'show_dob_date',
                'content_show_signature' => 'content_show_signature',
                'receive_admin_email' => 'receive_admin_email',
                'email_on_conversation' => 'email_on_conversation',
                'is_discouraged' => 'is_discouraged',
                'default_watch_state' => 'default_watch_state',
                'enable_rte' => 'enable_rte',
                'enable_flash_uploader' => 'enable_flash_uploader'
            ),

            'privacy' => array(
                'allow_view_profile' => 'allow_view_profile',
                'allow_post_profile' => 'allow_post_profile',
                'allow_send_personal_conversation' => 'allow_send_personal_conversation',
                'allow_view_identities' => 'allow_view_identities',
                'allow_receive_news_feed' => 'allow_receive_news_feed'
            ),

            'profile' => array(
                //'dob_day' => 'dob_day',
                //'dob_month' => 'dob_month',
                //'dob_year' => 'dob_year',
                'birthday' => 'birthday',
                'signature' => 'signature',
                'homepage' => 'homepage',
                'location' => 'location',
                'occupation' => 'occupation',
                'about' => 'about'
            )
        );

        return $xfDefaultMatrix;
    }

    public static function getSameUserFields($name)
    {
        switch($name)
        {
            /*
            case 'user_id':
            case 'id':
            case 'uid':
            case 'userid':
                return'user_id';
                break;
            */

            case 'name':
            case 'username':
            case 'displayname':
            case 'uname':
            case 'loginname':
                return'username';
                break;

            case 'email':
            case 'user_email':
            case 'emailaddress':
                return'email';
                break;

            case 'gender':
                return'gender';
                break;

            case 'custom_title':
                return'custom_title';
                break;

            case 'message_count':
            case 'memberposts':
            case 'posts':
                return'message_count';
                break;

            case 'register_date':
            case 'user_regdate':
            case 'joined':
            case 'joineddate':
            case 'joindate':
            case 'regdate':
                return'register_date';
                break;

            case 'user_group_id':
            case 'group':
            case 'primarygroup':
            case 'primarygroupid':
                return'user_group_id';
                break;

            case 'secondary_group_ids':
            case 'secondarygroup':
            case 'secondarygroupids':
                return'secondary_group_ids';
                break;

            case 'timezone':
                return'timezone';
                break;

            case 'dateofbirth':
            case 'bday':
            case 'birthday':
            case 'birthdate':
                return'birthday';
                break;

            case 'dob_day':
                return'dob_day';
                break;

            case 'dob_month':
                return'dob_month';
                break;

            case 'dob_year':
                return'dob_year';
                break;

            case 'last_activity':
            case 'last_login':
            case 'lastvisit':
            case 'lastactivity':
                return'last_activity';
                break;

            case 'is_moderator':
                return'is_moderator';
                break;

            case 'is_admin':
                return'is_admin';
                break;

            case 'is_banned':
                return'is_banned';
                break;

            case 'is_staff':
                return'is_staff';
                break;


            case 'show_dob_year':
                return'show_dob_year';
                break;

            case 'show_dob_date':
                return'show_dob_date';
                break;

            case 'content_show_signature':
                return'content_show_signature';
                break;

            case 'receive_admin_email':
                return'receive_admin_email';
                break;

            case 'email_on_conversation':
                return'email_on_conversation';
                break;

            case 'is_discouraged':
                return'is_discouraged';
                break;

            case 'default_watch_state':
                return'default_watch_state';
                break;

            case 'enable_rte':
                return'enable_rte';
                break;

            case 'enable_flash_uploader':
                return'enable_flash_uploader';
                break;

            case 'allow_view_profile':
                return'allow_view_profile';
                break;

            case 'allow_post_profile':
                return'allow_post_profile';
                break;

            case 'allow_send_personal_conversation':
                return'allow_send_personal_conversation';
                break;

            case 'allow_view_identities':
                return'allow_view_identities';
                break;

            case 'allow_receive_news_feed':
                return'allow_receive_news_feed';
                break;

            case 'signature':
            case 'sig':
                return'signature';
                break;

            case 'homepage':
                return'homepage';
                break;

            case 'location':
                return'location';
                break;

            case 'occupation':
                return'occupation';
                break;

            case 'about':
                return'about';
                break;

        }

        return null;
    }

    public static function getSeparator($id, $other)
    {
        switch ($id)
        {
            case 1:
                return "\t";
                break;

            case 2:
            default:
                return ';';
                break;

            case 3:
                return ',';
                break;

            case 4:
                return ' ';
                break;

            case 5:
                return $other;
                break;
        }
    }

    public static function is_serialized($data)
    {
        $array = @unserialize($data);
        if(is_array($array) && $array !== false)
        {
            return true;
        }

        return false;
    }

    public static function is_json($string)
    {
        if(@json_decode($string) == null)
        {
            return false;
        }

        return true;
    }

    public static function is_timestamp($timestamp)
    {
        if(@strtotime(date('d-m-Y H:i:s', $timestamp)) === (int)$timestamp)
        {
            return true;
        }

        return false;
    }

    public static function checkDateAndConvert($date)
    {
        if(strtotime($date) === false)
        {
            $date = (int)$date;
            if(self::is_timestamp($date))
            {
                return (int)$date;
            }
        }
        else
        {
            return strtotime($date);
        }

        return 0;
    }


    public static function generateSpiderXML()
    {
        $db = XenForo_Application::getDb();

        $spiders = $db->fetchAll('SELECT *  FROM phc_acpp_spiders WHERE active = 1');

        try
        {
            $document = new DOMDocument('1.0', 'utf-8');
            $document->formatOutput = true;

            $rootNode = $document->createElement('searchspiders');
            $document->appendChild($rootNode);

            foreach($spiders as $spider)
            {
                $spiderEl = $document->createElement('spider');
                $spiderEl->setAttribute('ident', strtolower($spider['robot_id']));

                $nameNode = $document->createElement('name');
                $title = $document->createTextNode($spider['title']);
                $nameNode->appendChild($title);
                $spiderEl->appendChild($nameNode);


                $contactNode = $document->createElement('info');

                if($spider['contact'])
                {
                    $info = $document->createTextNode($spider['contact']);
                    $contactNode->appendChild($info);
                }

                $spiderEl->appendChild($contactNode);

                $rootNode->appendChild($spiderEl);
            }

            $path = XenForo_Helper_File::getInternalDataPath();
            $fileName = $path . '/spiders.xml';

            @unlink($fileName);

            $document->save($fileName);
        }
        catch (Exception $e)
        {
            XenForo_Error::logException($e, false);
        }

        return true;
    }

    public static function getRobotId($userAgent)
    {
        $path = XenForo_Helper_File::getInternalDataPath();
        $fileName = $path . '/spiders.xml';

        if(!file_exists($fileName) || !is_readable($fileName))
        {
            return false;
        }

        self::loadSpidersXML();

        if(self::$_spiders)
        {
            $userAgent = trim(strtolower($userAgent));

            if(!empty(self::$_spiders[$userAgent]))
            {
                return $userAgent;
            }
        }

        return '';
    }

    public static function getRobotInfo($robotId)
    {
        if (!$robotId)
        {
            return false;
        }

        $robotTitle = array(
            'title' => 'Unknown',
            'link' => ''
        );

        self::loadSpidersXML();

        if(self::$_spiders)
        {
            if(!empty($spider = self::$_spiders[$robotId]))
            {
                return $spider;
            }
        }

        return $robotTitle;
    }

    protected static function loadSpidersXML()
    {
        if(self::$_spiders === null)
        {
            $path = XenForo_Helper_File::getInternalDataPath();
            $fileName = $path . '/spiders.xml';

            try
            {
                $document = Zend_Xml_Security::scanFile($fileName);

                foreach($document->spider as $spider)
                {
                    $robotID = (string)$spider->attributes()->ident;
                    $name = (string)$spider->name;
                    $info = (string)$spider->info;

                    self::$_spiders[$robotID] = array(
                        'title' => $name,
                        'link' => $info,
                    );
                }
            }
            catch (Exception $e)
            {
                XenForo_Error::logException($e, false);
            }
        }
    }

    public static function getXFControllerListOption(&$robot = array())
    {
        $controllers = [];

        if($robot)
        {
            $robot['controllers'] = @preg_split('/(\r\n|\n|\r)+/', $robot['controllers'], -1, PREG_SPLIT_NO_EMPTY);
        }

        $robot['controllers'] = array_flip($robot['controllers']);

        foreach(self::$_xfController as $controller)
        {
            $active = false;
            if(isset($robot['controllers']) && isset($robot['controllers'][$controller]))
            {
                $active = true;
                unset($robot['controllers'][$controller]);
            }

            $controllers[$controller] = array(
                'label' => $controller,
                'value' => $controller,
                'selected' => $active,
            );
        }

        $robot['controllers'] = array_flip($robot['controllers']);
        $robot['controllers'] = @implode("\n", $robot['controllers']);


        return $controllers;
    }



    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected static function _getACPPlusModel()
    {
        static $model = null;

        if ($model === null)
        {
            $model = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');
        }

        return $model;
    }
}
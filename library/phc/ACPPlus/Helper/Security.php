<?php


class phc_ACPPlus_Helper_Security
{
    const IP_TYPE_V4    = 0x04;
    const IP_TYPE_V6    = 0x06;

    public $_ip_type = null;

    public function getServerConfigFile()
    {
        $server = phc_ACPPlus_Helper_ACPPlus::getWebServer();

        if($server == 'apache' || $server == 'litespeed')
        {
            return $this->getXFPath('home') . DIRECTORY_SEPARATOR . phc_ACPPlus_Helper_ACPPlus::$_htaccessFile;
        }

        return false;
    }

    public function checkPermissionsFiles()
    {
        $xfFiles = array(
            array($this->getXFPath('home'), 0755),
            array($this->getXFPath('library'), 0755),
            array($this->getXFPath('install'), 0755),
            array($this->getXFPath('js'), 0755),
            array($this->getXFPath('library') . DIRECTORY_SEPARATOR . 'config.php', 0444)
        );

        // Check Webserver for ConfigFile
        $configFile = $this->getServerConfigFile();

        if($configFile)
            $xfFiles[] = array($configFile, 0444);

        $results = array();

        foreach($xfFiles as $xfFile)
        {
            list($path, $permission) = $xfFile;

            $realFilePermission = @fileperms($path) & 0777 ;

            $path = str_replace($this->getXFPath('home'), '', $path);


            $results[] = array(
                'path' => ($path == '' ? DIRECTORY_SEPARATOR : $path),
                'permission' => sprintf('%o', $permission),
                'real_permission' => sprintf('%o', $realFilePermission),
                'status' => ((!$realFilePermission || $realFilePermission != $permission) ? false : true)
            );
        }

        return $results;
    }

    public function checkACPProtected()
    {
        $httpCode = 0;
        $boardUrl = XenForo_Application::getOptions()->boardUrl;

        try
        {
            $ch = curl_init($boardUrl . '/admin.php');
            curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
        }catch(Exception $e) { return $httpCode; }

        return $httpCode;
    }

    public function getXFPath($mode = '')
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

    public function fetchUserIp()
    {
        $ip = 0;

        if(!empty($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif(!empty($_SERVER['REMOTE_ADDR']))
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if($ip)
        {
            if(!$this->getIpType($ip))
                $ip = 0;
        }

        return $ip;
    }

    public function getIpType($ip)
    {
        $status = null;
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
        {
            $status = self::IP_TYPE_V4;
        }
        elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        {
            $status = self::IP_TYPE_V6;
        }

        return $status;
    }

    public function sendLoginAlertMail($input, $ip, $type, array $emails)
    {
        $mail = XenForo_Mail::create('acpp_firewall_email_' . $type, array(
            'ip' => $ip,
            'name' => (!empty($input['login']) ? $input['login'] : new XenForo_Phrase('acpp_unknow')),
            'hash' => (!empty($input['hash']) ? $input['hash'] : ''),
        ));

        foreach($emails as $email)
        {
            $mail->send($email);
        }
    }
}
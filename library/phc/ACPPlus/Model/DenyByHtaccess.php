<?php


class phc_ACPPlus_Model_DenyByHtaccess extends XenForo_Model
{
    public $_denys = array();
    public $_preContent = array();
    public $_afterContent = array();

    protected $_startmarker = '# Begin Blocked IPs';
    protected $_endmarker = '# End Blocked IPs';

    public function canUseDenyByHtaccess(array $viewingUser = null)
    {
        if(!phc_ACPPlus_Helper_ACPPlus::getWebServer())
            return false;

        $this->standardizeViewingUserReference($viewingUser);
        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'acpplus', 'acpp_canUseDenyByHtaccess');
    }

    public function readIpsFromHtaccess()
    {
        $htaccessFile = phc_ACPPlus_Helper_ACPPlus::getXFPath('home') . DIRECTORY_SEPARATOR . phc_ACPPlus_Helper_ACPPlus::$_htaccessFile;

        if(file_exists($htaccessFile) && is_readable($htaccessFile))
        {
            // Lese Daten ein!
            $file = @fopen($htaccessFile, 'r');
            @flock($file, LOCK_EX);

            $lines = array();

            while (!feof($file))
            {
                $lines[] = rtrim(fgets($file), "\r\n");
            }

            $beginMarkerFound = false;
            $endMarkerFound = false;
            foreach($lines as $line)
            {
                if($line == $this->_startmarker)
                {
                    $beginMarkerFound = true;
                    continue;
                }

                if($line == $this->_endmarker)
                {
                    $endMarkerFound = true;
                    continue;
                }

                if(!$beginMarkerFound && !$endMarkerFound)
                    $this->_preContent[] = $line;

                if($beginMarkerFound && $endMarkerFound)
                    $this->_afterContent[] = $line;

                if($beginMarkerFound && !$endMarkerFound)
                    $this->_denys[] = trim(str_replace('deny from', '', strtolower($line)));
            }

            @flock($file, LOCK_UN);
            @fclose($file);

            return true;
        }
        else
        {
            return false;
        }
    }

    public function writeIpToHtaccess($ip, $oldIp = null)
    {
        if($this->readIpsFromHtaccess())
        {
            if(!in_array($ip, $this->_denys))
            {
                $this->_denys[] = $ip;
            }

            if($oldIp)
            {
                foreach($this->_denys as $key => $ip)
                {
                    if($oldIp == $ip)
                    {
                        unset($this->_denys[$key]);
                    }
                }
            }

            $htaccessFile = phc_ACPPlus_Helper_ACPPlus::getXFPath('home') . DIRECTORY_SEPARATOR . phc_ACPPlus_Helper_ACPPlus::$_htaccessFile;

            $realFilePermission = @fileperms($htaccessFile);

            if(!is_writable($htaccessFile))
            {
                @chmod($htaccessFile, '0677');
            }

            if(!is_writable($htaccessFile))
                return false;

            $this->writeFile();

            @chmod($htaccessFile, $realFilePermission);

            return true;
        }

        return false;
    }

    public function removeIpFromHtaccess($removeIp)
    {
        if($this->readIpsFromHtaccess())
        {
            foreach($this->_denys as $key => $ip)
            {
                if($removeIp == $ip)
                {
                    unset($this->_denys[$key]);
                }
            }

            $this->writeFile();

            return true;
        }

        return;
    }

    protected function writeFile()
    {
        $htaccessFile = phc_ACPPlus_Helper_ACPPlus::getXFPath('home') . DIRECTORY_SEPARATOR . phc_ACPPlus_Helper_ACPPlus::$_htaccessFile;

        if(is_writable($htaccessFile))
        {
            $file = @fopen($htaccessFile, 'r+');
            @flock($file, LOCK_EX);

            foreach($this->_denys as &$deny)
            {
                $deny = 'deny from ' . $deny;
            }

            $newHtaccess = implode("\n", array_merge(
                $this->_preContent,
                array( $this->_startmarker ),
                $this->_denys,
                array( $this->_endmarker ),
                $this->_afterContent
            ) );

            @fwrite($file, $newHtaccess);

            @flock($file, LOCK_UN);
            @fclose($file);

        }
    }

    public static function ip_check(&$ip)
    {
        @list($oct1, $oct2, $oct3, $oct4) = @preg_split('/\./', $ip, -1,  PREG_SPLIT_NO_EMPTY);

        $flag = false;

        if($oct1 >= 1 && $oct1 <= 255)
        {
            $flag = true;
        }
        if($oct2)
        {
            $flag = false;
            if($oct2 >= 0 && $oct2 <= 255)
            {
                $flag = true;
            }
        }
        if($oct3)
        {
            $flag = false;
            if($oct3 >= 0 && $oct3 <= 255)
            {
                $flag = true;
            }
        }
        if($oct4)
        {
            $flag = false;
            if($oct4 >= 0 && $oct4 <= 255)
            {
                $flag = true;
            }
        }

        // Rebuild IP
        $ip = '';
        if($oct1)
        {
            $ip .= $oct1 . '.';
        }
        if($oct2)
        {
            $ip .= $oct2 . '.';
        }
        if($oct3)
        {
            $ip .= $oct3 . '.';
        }
        if($oct4)
        {
            $ip .= $oct4;
        }

        return $flag;

        /*
        if(!filter_var($ip, FILTER_VALIDATE_IP) === false)
        {
            return true;
        }
        else
        {
            return false;
        }
        */
    }
}
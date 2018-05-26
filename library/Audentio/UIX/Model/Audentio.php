<?php

class Audentio_UIX_Model_Audentio extends XenForo_Model
{
    public $filePath = false;

    protected $_ftp;
    protected $_ftpHost;
    protected $_ftpPort;
    protected $_ftpUser;
    protected $_ftpPass;
    protected $_ftpPath;

    public function rrmdir($dir)
    {
        foreach (glob($dir.'/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                @unlink($file);
            }
        }
        rmdir($dir);
    }

    public function ftpConnect($host, $port, $user, $pass, $path)
    {
        $this->_ftpHost = $host;
        $this->_ftpPort = $port;
        $this->_ftpUser = $user;
        $this->_ftpPass = $pass;
        $this->_ftpPath = $path;

        $this->_ftp = ftp_connect($host, $port);
        if (!$this->_ftp) {
            return false;
        }

        try {
            $result = ftp_login($this->_ftp, $user, $pass);
        } catch (Exception $e) {
            throw new XenForo_Exception(new XenForo_Phrase('uix_invalid_ftp_details'), true);
        }

        ftp_chdir($this->_ftp, $path);

        // /data/adstyle-1424455006/Upload/styles/reneue/xenforo/smilies.doodle
        $list = ftp_nlist($this->_ftp, $path);
        if (!in_array('index.php', $list) && !in_array('proxy.php', $list) && !in_array('data', $list) && !in_array('internal_data', $list) && !in_array('styles', $list) && !in_array('js', $list)) {
            return false;
        }

        return $result;
    }

    public function getFtpPath()
    {
        return $this->_ftpPath;
    }

    public function getFtp()
    {
        if ($this->_ftp) {
            return $this->_ftp;
        }

        return false;
    }

    public function rmove($dirSource, $dirDest, $ftpRequired = false)
    {
        if (is_dir($dirSource)) {
            $dirHandle = opendir($dirSource);
        }
        $dirName = substr($dirSource, strrpos($dirSource, DIRECTORY_SEPARATOR) + 1);
        if (!is_dir($dirDest.DIRECTORY_SEPARATOR.$dirName) && !file_exists($dirDest.DIRECTORY_SEPARATOR.$dirName)) {
            $this->_mkdir($dirDest.DIRECTORY_SEPARATOR.$dirName, $ftpRequired);
        }
        while ($file = readdir($dirHandle)) {
            if ($this->_ftp) {
                $ftpRequired = true;
            }
            if ($file == 'Thumbs.db') {
                @unlink($file);
                continue;
            }
            if ($file != '.' && $file != '..') {
                if (!is_dir($dirSource.DIRECTORY_SEPARATOR.$file)) {
                    if (file_exists($dirDest.DIRECTORY_SEPARATOR.$dirName.DIRECTORY_SEPARATOR.$file)) {
                        $this->_unlink($dirDest.DIRECTORY_SEPARATOR.$dirName.DIRECTORY_SEPARATOR.$file, $ftpRequired);
                    }
                    $this->copy($dirSource.DIRECTORY_SEPARATOR.$file, $dirDest.DIRECTORY_SEPARATOR.$dirName.DIRECTORY_SEPARATOR.$file, $ftpRequired);
                    $this->_unlink($dirSource.DIRECTORY_SEPARATOR.$file, $ftpRequired);
                } else {
                    $this->rmove($dirSource.DIRECTORY_SEPARATOR.$file, $dirDest.DIRECTORY_SEPARATOR.$dirName, $ftpRequired);
                }
            }
        }

        @closedir($dirHandle);
        @rmdir($dirSource);
    }

    public function mkdir($dirName, $requireFtp)
    {
        $this->_mkdir($dirName, $requireFtp);
    }

    protected function _mkdir($dirName, $requireFtp)
    {
        $dirName = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dirName);
        $dirName = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dirName);
        $dirName = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dirName);
        $dirName = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dirName);

        if ($requireFtp) {
            $curDir = ftp_pwd($this->_ftp);
            if (!@ftp_chdir($dirName, $this->_ftp)) {
                $dirName = str_replace(XenForo_Application::getInstance()->getRootDir(), $this->_ftpPath, $dirName);
                if ($dirName == $this->_ftpPath) {
                    return true;
                }
                $pwd = ftp_pwd($this->_ftp);
                $chdir = @ftp_chdir($this->_ftp, $dirName);
                if ($chdir) {
                    ftp_chdir($this->_ftp, $pwd);
                } else {
                    $return = ftp_mkdir($this->_ftp, $dirName);
                    ftp_chmod($this->_ftp, 0666, $dirName);

                    return $return;
                }
            } else {
                @ftp_chdir($curDir, $this->_ftp);
            }
        } else {
            try {
                return XenForo_Helper_File::createDirectory($dirName);
            } catch (Exception $e) {
                return false;
            }
        }
    }

    protected function copy($sourceFile, $destFile, $requireFtp)
    {
        $sourceFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $sourceFile);
        $destFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $destFile);
        $sourceFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $sourceFile);
        $destFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $destFile);
        if ($requireFtp) {
            $sourceFile = str_replace(XenForo_Application::getInstance()->getRootDir(), '', $sourceFile);
            $destFile = str_replace($this->_ftpPath, '', $destFile);

            $sourceFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $sourceFile);
            $destFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $destFile);

            $firstCharSource = substr($sourceFile, 0, 1);
            $firstCharDest = substr($destFile, 0, 1);

            if ($firstCharSource != DIRECTORY_SEPARATOR) {
                $sourceFile = DIRECTORY_SEPARATOR.$sourceFile;
            }
            if ($firstCharDest != DIRECTORY_SEPARATOR) {
                $destFile = DIRECTORY_SEPARATOR.$destFile;
            }

            $sourceFile = XenForo_Application::getInstance()->getRootDir().$sourceFile;
            $destFile = $this->_ftpPath.$destFile;

            $sourceFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $sourceFile);
            $destFile = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $destFile);

            $put = ftp_put($this->_ftp, $destFile, $sourceFile, FTP_BINARY);
        } else {
            $response = @copy($sourceFile, $destFile);
            if (!$response) {
                throw new XenForo_Exception(new XenForo_Phrase('uix_not_writable'), 1);
            }

            return $response;
        }
    }

    protected function _unlink($file, $requireFtp)
    {
        return @unlink($file);
    }

    public function getStylesFromApi()
    {
        $styles = $this->_apiCall('product-categories/1');
        if ($styles['status'] == 'error') {
            return $styles;
        }

        if ($styles['status'] == 'success') {
            $products = array();
            foreach ($styles['payload']['products'] as $product) {
                if ($product['product_type'] !== 'theme') continue;
                $products[$product['id']] = $product;
            }

            return array(
                'status' => 'success',
                'styles' => $products
            );
        } else {
            return array(
                'status' => 'error',
                'error' => $styles['error']
            );
        }
    }

    public function downloadStyleFromApi($pid)
    {
        $extraData['type'] = 'style';
        $extraData['pid'] = $pid;

        $styleResponse = $this->getStyleFromApi($pid);
        if ($styleResponse['status'] == 'error') {
            return $styleResponse;
        }
        $product = $styleResponse['payload']['product'];

        $latestVersion = end($styleResponse['payload']['versions']);

        $return = $this->_apiCall('products/'.$pid.'/download/'.$latestVersion['id'], $extraData);
        if ($return['status'] == 'error') {
            return $return;
        }
        $filePath = XenForo_Helper_File::getTempDir().DIRECTORY_SEPARATOR.'style-'.time().'.zip';

        $response = $this->_downloadFile($return['payload']['version']['download_url'], $filePath);

        if ($response['status'] == 'error') {
            return $response;
        }

        return array(
            'status' => 'success',
            'product' => $product,
            'version' => $latestVersion
        );
    }

    protected function _downloadFile($fileUrl, $savePath)
    {
        if (function_exists('curl_version')) {
            $ch = @curl_init($fileUrl);
            if (!$ch) {
                return array(
                    'status' => 'error',
                    'error' => 'cURL is not installed on your server. Please contact your host.'
                );
            }
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            $return = @curl_exec($ch);
            if (!$return) {
                return array(
                    'status' => 'error',
                    'error' => 'Timeout connecting to the ThemeHouse API. Please try again later.'
                );
            }
        } else {
            return array(
                'status' => 'error',
                'error' => 'cURL is not installed on your server. Please contact your host.'
            );
        }

        $fh = fopen($savePath, 'w+');
        fwrite($fh, $return);
        fclose($fh);
        $this->filePath = $savePath;
        XenForo_Helper_File::makeWritableByFtpUser($savePath);

        return array(
            'status' => 'success'
        );
    }

    public function getStyleFromApi($pid)
    {
        return $this->_apiCall('products/'.$pid);
    }

    protected function _isJson($string)
    {
        $array = json_decode($string);
        if (!$array) {
            return false;
        }

        return true;
    }

    public function isUixOutdated()
    {
        $update = $this->_getDataRegistryModel()->get('uix_update');
        if ($update) {
            if ($update['show_notice']) {
                $uixAddon = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('uix');
                $latestVersion = $update['latest_version_string'];
                $currentVersion = $uixAddon['version_string'];
                if ($currentVersion !== $latestVersion) {
                    return true;
                }

                $update['show_notice'] = 0;
                $this->_getDataRegistryModel()->set('uix_update', $update);
            }

            return false;
        } else {
            return 0;
        }
    }

    public function checkForUixUpdate()
    {
        $response = $this->_apiCall('products/129');

        if ($response['status'] == 'error') {
            return false;
        }

        $latest = end($response['payload']['versions']);
        $requiresUpdate = true;
        $uix = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('uix');
        if ($uix['version_string'] != $latest['version']) {
            $updateCheck = $this->_getDataRegistryModel()->get('uix_update');
            if ($updateCheck) {
                if (isset($updateCheck['latest_version_string']) && $latest['version'] !== $updateCheck['latest_version_string']) {
                    $requiresUpdate = false;
                }
            }
        } else {
            $requiresUpdate = false;
        }

        if ($requiresUpdate) {
            $update = array(
                'show_notice' => 1,
                'latest_version_string' => $latest['version'],
            );

            $this->_getDataRegistryModel()->set('uix_update', $update);
        } else {
            $update = array(
                'show_notice' => 0,
                'latest_version_string' => $latest['version'],
            );

            $this->_getDataRegistryModel()->set('uix_update', $update);
        }
    }

    protected function _apiCall($action, array $extraData = array())
    {
        $xenOptions = XenForo_Application::get('options')->getOptions();
        if (empty($xenOptions['uix_apiKey'])) {
            return array(
                'status' => 'error',
                'error' => 'Invalid API key.'
            );
        }

        $apiUrl = $this->_apiUrl.$action;
        $data = array();
        $headers = array();
        $headers[] = 'ApiKey: '.$xenOptions['uix_apiKey'];
        $headers[] = 'SiteUrl: '.$xenOptions['boardUrl'];

        if (!empty($extraData)) {
            $data = array_merge($data, $extraData);
        }

        if (function_exists('curl_version')) {
            $ch = @curl_init($apiUrl);
            if (!$ch) {
                return array(
                    'status' => 'error',
                    'error' => 'cURL is not installed on your server. Please contact your host.'
                );
            }
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $return = @curl_exec($ch);
            if (!$return) {
                return array(
                    'status' => 'error',
                    'error' => 'Timeout connecting to the ThemeHouse API. Please try again later.'
                );
            }

            if (!$this->_isJson($return)) {
                return array(
                    'status' => 'error',
                    'error' => 'Invalid response from ThemeHouse API. Please try again later.'
                );
            }
        } else {
            return array(
                'status' => 'error',
                'error' => 'cURL is not installed on your server. Please contact your host.'
            );
        }

        return json_decode($return, true);
    }
}

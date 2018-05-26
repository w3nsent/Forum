<?php

class Audentio_UIX_Listener_Install
{
    protected static $_updates = array(
        '1000031' => 'Audentio_UIX_Listener_Install_1000031', // 1.0.0 Beta 1
        '1000032' => 'Audentio_UIX_Listener_Install_1000032', // 1.0.0 Beta 2
        '1000101' => 'Audentio_UIX_Listener_Install_1000101', // 1.0.1 Dev Build 1
        '1000104' => 'Audentio_UIX_Listener_Install_1000104', // 1.0.1 Dev Build 4
        '1000106' => 'Audentio_UIX_Listener_Install_1000106', // 1.0.1 Dev Build 6
        '1000108' => 'Audentio_UIX_Listener_Install_1000108', // 1.0.1 Dev Build 8
        '1000110' => 'Audentio_UIX_Listener_Install_1000110', // 1.0.1 Dev Build 10
        '1000171' => 'Audentio_UIX_Listener_Install_1000171', // 1.0.1 Patch 1
        '1000203' => 'Audentio_UIX_Listener_Install_1000203', // 1.0.2 Dev Build 3
        '1000271' => 'Audentio_UIX_Listener_Install_1000271', // 1.0.2 Patch 1
        '1000301' => 'Audentio_UIX_Listener_Install_1000301', // 1.0.3 Dev Build 1
        '1000371' => 'Audentio_UIX_Listener_Install_1000371', // 1.0.3 Patch 1
        '1000501' => 'Audentio_UIX_Listener_Install_1000501', // 1.0.5 Dev Build 1
        '1001170' => 'Audentio_UIX_Listener_Install_1001170', // 1.0.11 Dev Build 1
        '1001270' => 'Audentio_UIX_Listener_Install_1001270', // 1.0.12 Dev Build 1
        '1010175' => 'Audentio_UIX_Listener_Install_1010175', // 1.1.1e Dev Build 1
    );

    protected static $_db = null;

    public static function run($existingAddOn, $addOnData)
    {
        self::_recurseInvalidateCache('./library/Audentio/UIX/');
        $installedVersion = is_array($existingAddOn) ? $existingAddOn['version_id'] : 0;
        foreach (self::$_updates as $version => $updateClass) {
            if ($installedVersion == 0 || $version > $installedVersion) {
                $installer = new $updateClass();
                $installer->run();
            }
        }
        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
    }

    protected static function _recurseInvalidateCache($src)
    {
        if (!function_exists('opcache_invalidate')) {
            return false;
        }
        if (!is_dir($src)) {
            return true;
        }

        $dir = new DirectoryIterator($src);
        foreach ($dir as $path) {
            if ($path->isFile()) {
                opcache_invalidate($path->getRealPath());
            } elseif ($path->isDir() && !$path->isDot()) {
                self::_recurseInvalidateCache($path->getRealPath());
            }
        }
    }

}

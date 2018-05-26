<?php

/**
 * Helper Brivium Addon for EventListener.
 *
 * @package Brivium_BriviumHelper
 * Version 1.0.0
 */
$helperDir = XenForo_Autoloader::getInstance()->getRootDir() . '/Brivium/BriviumHelper/';
$helperVersion = 0;
if (is_dir($helperDir)) {
    if ($dh = opendir($helperDir)) {
        while (($folder = readdir($dh)) !== false) {
			if( '.' == $folder || '..' == $folder || filetype($helperDir . $folder)!='dir'){
				continue;
			}
			if (intval($folder) > $helperVersion) {
				$helperVersion = intval($folder);
			}
        }
        closedir($dh);
    }
}
require_once($helperDir . $helperVersion. '/Installer.php');
<?php

class BS_BRMSStick_Installer
{
	public static function install($existingAddOn, array $addOnData)
	{
		self::checkBrmsExists();

		$db = XenForo_Application::getDb();	
		$version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;

		try
		{	
			$db->query("
				ALTER TABLE xf_thread 
				ADD brms_stick INT(1) NOT NULL DEFAULT '0';
				");		
		}
		catch (Zend_Db_Exception $e) {}	

		if ($version < 1010470)
		{
			$db->query("
				CREATE TABLE IF NOT EXISTS  xf_brmsstick_links ( 
				link_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
				link VARCHAR(700) NOT NULL, 
				title VARCHAR(700) NOT NULL
				) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
			");
		}	
	}

	public static function checkBrmsExists()
	{
		$addOnModel = self::_getAddOnModel();
		$brms = $addOnModel->getAddOnById('Brivium_ModernStatistics');
		if (!$brms)
		{
			throw new XenForo_Exception('BRMS Not Installed', true);
		}
	}

	public static function _getAddOnModel()
	{
		return XenForo_Model::create('XenForo_Model_AddOn');
	}

	public static function uninstall()
	{
		$db = XenForo_Application::getDb();	

		$db->query("
			ALTER TABLE xf_thread 
			DROP brms_stick;
			");
		$db->query("
			DROP TABLE xf_brmsstick_links;
			");
	}
}
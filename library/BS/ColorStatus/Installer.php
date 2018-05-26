<?php

class BS_ColorStatus_Installer
{
	public static function install()
    {
        $db = XenForo_Application::get('db');		
		
		try
		{	
			$db->query("
				ALTER TABLE xf_user ADD status_color VARCHAR(10) NULL;
			");
		}
		catch (Zend_Db_Exception $e) {}
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');
		
		try
		{		
			$db->query("
				ALTER TABLE xf_user DROP status_color;
			");
		}
		catch (Zend_Db_Exception $e) {}	
    }
}
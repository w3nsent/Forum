<?php

class Andy_WeeklyDigest_Install
{
    public static function install()
    {
        $db = XenForo_Application::get('db');		
		
		try
		{	
			$db->query("
				ALTER TABLE xf_user
					ADD weekly_digest_opt_out TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
			");
		}
		catch (Zend_Db_Exception $e) {}
    }
}
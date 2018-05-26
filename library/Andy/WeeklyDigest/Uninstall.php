<?php

class Andy_WeeklyDigest_Uninstall
{
    public static function uninstall()
    {
        $db = XenForo_Application::get('db');
		
		try
		{		
			$db->query("
				ALTER TABLE xf_user
					DROP weekly_digest_opt_out
			");
		}
		catch (Zend_Db_Exception $e) {}		
    }
}
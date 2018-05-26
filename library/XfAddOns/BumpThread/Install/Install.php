<?php

class XfAddOns_BumpThread_Install_Install
{

	/**
	 * Create the tables as needed by this add-on
	 */
	public static function install($installedAddon)
	{
		$version = is_array($installedAddon) ? $installedAddon['version_id'] : 0;
		if ($version < 110)
		{
			$db = XenForo_Application::getDb();
			$db->query("
				CREATE TABLE xfa_bump_thread
				(
					user_id int not null primary key,
					last_bump_thread int not null,	
					last_bump_date int not null
				)					
			");
		}
		if ($version < 120)
		{
			$db = XenForo_Application::getDb();
			$db->query("
				ALTER TABLE xfa_bump_thread DROP PRIMARY KEY
			");
			$db->query("
				ALTER TABLE xfa_bump_thread ADD COLUMN bump_id INT NOT NULL PRIMARY KEY auto_increment FIRST
			");
		}		
	}

	/**
	 * Deletes anything created by this add-on
	 */
	public static function uninstall()
	{
		$db = XenForo_Application::getDb();
		$db->query("DROP TABLE xfa_bump_thread");
	}

}



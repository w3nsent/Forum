<?php

/**
 * Helper class to figure out if a person can bump a thread
 */
class XfAddOns_BumpThread_Helper_Permissions
{

	/**
	 * Check if the permission combination enables the person to bump a thread
	 * @param boolean $nodePermissions
	 */
	public static function canBump($thread, $nodePermissions)
	{
		// shortcut the method if we don't have the permission information, it doesn't make any sense to continue
		if (!$nodePermissions || !isset($nodePermissions['xfa_bump_thread']))
		{
			return false;
		}

		// figure out if the person can bump the thread
		if ($nodePermissions['xfa_bump_thread_any'])
		{
			return true;
		}
		if ($nodePermissions['xfa_bump_thread'] && $thread['user_id'] == XenForo_Visitor::getUserId())
		{
			return true;
		}

		return false;
	}


}
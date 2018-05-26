<?php

/**
 * Model class for retrieving Meta-Data of what has been bumped
 */
class XfAddOns_BumpThread_Model_BumpThread extends XenForo_Model
{

	/**
	 * Return the data for a thread that was recently bumped
	 * @param int $userId
	 * @return array
	 */
	public function getBumpData($userId)
	{
		$db = XenForo_Application::getDb();
		return $db->fetchRow("SELECT * FROM xfa_bump_thread WHERE user_id = ?", $userId);
	}
	
	/**
	 * Returns the amount of threads that have been bumped since a particular date
	 */
	public function totalBumpedSince($userId, $sinceDate)
	{
		$db = XenForo_Application::getDb();
		return $db->fetchRow("
			SELECT
				count(*) totalBumped,
				min(last_bump_date) firstBumpDate
			FROM xfa_bump_thread
			WHERE
				user_id = ? AND
				last_bump_date >= ?
				",
			array($userId, $sinceDate));
	}
	
	/**
	 * Insert or Update the data for the bump
	 * @param int $userId
	 * @param int $threadId
	 */
	public function insertBumpData($userId, $threadId)
	{
		$db = XenForo_Application::getDb();
		$db->query("
			INSERT INTO xfa_bump_thread
				(user_id, last_bump_thread, last_bump_date)
			VALUES
				(?, ?, ?)
			",
			array($userId, $threadId, XenForo_Application::$time) 
		);
	}
	
}
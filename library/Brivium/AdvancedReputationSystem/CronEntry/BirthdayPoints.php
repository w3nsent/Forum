<?php

// ######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_CronEntry_BirthdayPoints
{

	public static function birthdayPoints()
	{
		$options = XenForo_Application::get( 'options');
		$db = XenForo_Application::getDb();
		
		if ($options->birthdaypoints != 0)
		{
			$userModel = XenForo_Model::create( 'XenForo_Model_User');
			
			// Don't send the automated birthday reputation points to banned and unconfirmed users
			$criteria = array(
				'user_state' => 'valid',
				'is_banned' => 0
			);
			
			list ($month, $day) = explode( '/', XenForo_Locale::date( XenForo_Application::$time, 'n/j'));
			
			$birthdays = XenForo_Model::create( 'XenForo_Model_User')->getBirthdayUsers( $month, $day, $criteria, array(
				'join' => XenForo_Model_User::FETCH_USER_FULL
			));
			
			// Send the birthday reputation points to the birthday users
			foreach($birthdays as $user)
			{
				$db->query( "
				    UPDATE `xf_user`
				    SET reputation_count = reputation_count + ?
				    WHERE user_id = ?
			        ", array(
					$options->birthdaypoints,
					$user['user_id']
				));
				
				if(XenForo_Model_Alert::userReceivesAlert($user, 'reputation', 'extra'))
				{
					XenForo_Model_Alert::alert($user['user_id'], 0, '', 'user', $user['user_id'], 'rep_happy_birthdays', array(
						'points' => $options->birthdaypoints
					));
				}
			}
		}
	}
}
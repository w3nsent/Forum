<?php

// ######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_Avatar extends XFCP_Brivium_AdvancedReputationSystem_Model_Avatar
{
	// Add rep point(s) to users when they upload an avatar
	public function applyAvatar($userId, $fileName, $imageType = false, $width = false, $height = false, $permissions = false)
	{
		$parent = parent::applyAvatar( $userId, $fileName, $imageType, $width, $height, $permissions);
		
		if (! empty( $parent))
		{
			$options = XenForo_Application::get( 'options');
			$db = XenForo_Application::getDb();
			
			if ($options->avatar_rep_points != 0)
			{
				$db->query( "
				    UPDATE `xf_user`
				    SET reputation_count = reputation_count + ?
				    WHERE user_id = ?
			        ", array(
					$options->avatar_rep_points,
					$userId
				));
				if(XenForo_Model_Alert::userReceivesAlert(array('user_id' => $userId), 'reputation', 'extra'))
				{
					XenForo_Model_Alert::alert( $userId, 0, '', 'user', $userId, 'reputation_avatar', array(
						'points' => $options->avatar_rep_points
					));
				}
			}
		}
		
		return $parent;
	}
	
	// Remove the rep point(s) to users when they remove their avatars
	public function deleteAvatar($userId, $updateUser = true)
	{
		$parent = parent::deleteAvatar( $userId, $updateUser);
		
		if (! empty( $parent))
		{
			$options = XenForo_Application::get( 'options');
			$db = XenForo_Application::getDb();
			
			if ($options->avatar_rep_points != 0)
			{
				$db->query( "
				    UPDATE `xf_user`
				    SET reputation_count = reputation_count - ?
				    WHERE user_id = ?
			        ", array(
					$options->avatar_rep_points,
					$userId
				));
				$this->getModelFromCache( 'XenForo_Model_Alert')->deleteAlerts( 'user', $userId, $userId, 'reputation_avatar');
			}
		}
		
		return $parent;
	}
}
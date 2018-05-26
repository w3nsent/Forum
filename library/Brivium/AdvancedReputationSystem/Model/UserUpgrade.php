<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_UserUpgrade extends XFCP_Brivium_AdvancedReputationSystem_Model_UserUpgrade 
{
    //Add rep point(s) to users when they upgrade their account
	public function upgradeUser($userId, array $upgrade, $allowInsertUnpurchasable = false, $endDate = null) 
	{
		$parent = parent::upgradeUser($userId, $upgrade, $allowInsertUnpurchasable, $endDate);
		
		if (!empty($parent)) 
		{
			$options = XenForo_Application::get('options');
			$db = XenForo_Application::getDb();

			if ($options->upgradeaccountpoints != 0) 
			{
				$db->query("
				    UPDATE `xf_user`
				    SET reputation_count = reputation_count + ?
				    WHERE user_id = ?
			        ", array(
				    $options->upgradeaccountpoints,
				    $userId,
			    ));
				if(XenForo_Model_Alert::userReceivesAlert(array('user_id' => $userId), 'reputation', 'extra'))
				{
					XenForo_Model_Alert::alert($userId, 0, '', 'user', $userId, 'reputation_upgrade', array('points'=>$options->upgradeaccountpoints));
				}
			}	
		}
		
		return $parent;
	}
}
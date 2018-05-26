<?php

class Brivium_AdvancedReputationSystem_Deferred_ImportFromBD extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$reputationModel = XenForo_Model::create('Brivium_AdvancedReputationSystem_Model_Reputation');
		$rowCount = $reputationModel->importReputationFromBd();
		if(empty($rowCount))
		{
			return false;
		}
		
		XenForo_Application::defer('Brivium_AdvancedReputationSystem_Deferred_ReputationToPost', array(), 'BRARS_reputaton_to_posts', true);
		XenForo_Application::defer('Brivium_AdvancedReputationSystem_Deferred_ReputationToUser', array(), 'BRARS_reputaton_to_users', true);
		return true;
	}

	public function canCancel()
	{
		return true;
	}
}
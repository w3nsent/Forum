<?php

class Brivium_AdvancedReputationSystem_Deferred_ReputationToUser extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge( array(
				'position' => 0,
				'batch' => 250
		), $data);
		$data['batch'] = max( 1, $data['batch']);
	
		$reputationModel = XenForo_Model::create('Brivium_AdvancedReputationSystem_Model_Reputation');
		$userIds = $reputationModel->getGiverUserIdsInRange($data['position'], $data['batch']);

		if(!empty($userIds))
		{
			$reputationModel->rebuildReputationsToUsers($userIds);
			$data['position'] += count($userIds);
			
			$actionPhrase = new XenForo_Phrase( 'rebuilding');
			$typePhrase = new XenForo_Phrase( 'BRARS_reputation_to_giver_user');
			$status = sprintf( '%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));
			return $data;
		}
		$reputationModel->updateReputationCounter('all');
		return true;
	}

	public function canCancel()
	{
		return true;
	}
}
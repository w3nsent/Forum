<?php

class Brivium_AdvancedReputationSystem_Deferred_ReputationToPost extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge( array(
				'position' => 0,
				'batch' => 250
		), $data);
		$data['batch'] = max( 1, $data['batch']);
		
		$reputationModel = XenForo_Model::create('Brivium_AdvancedReputationSystem_Model_Reputation');
		$postIds = $reputationModel->getPostIdsInRange($data['position'], $data['batch']);
		if(!empty($postIds))
		{
			$reputationModel->rebuildReputationsToPosts($postIds);
			$data['position'] += count($postIds);
			
			$actionPhrase = new XenForo_Phrase( 'rebuilding');
			$typePhrase = new XenForo_Phrase( 'BRARS_reputation_point_to_post');
			$status = sprintf( '%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));
			return $data;
		}
		return true;
	}

	public function canCancel()
	{
		return true;
	}
}
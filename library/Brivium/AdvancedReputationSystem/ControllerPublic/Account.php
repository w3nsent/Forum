<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerPublic_Account extends XFCP_Brivium_AdvancedReputationSystem_ControllerPublic_Account
{
    //Display reputations that this user has given
    public function actionGivenReputations() 
	{
		$userId = XenForo_Visitor::getUserId();
		
		$reputationModel = $this->_getReputationModel();
		
		if(!$reputationModel->canViewReputationStatistics())
		{
			return $this->responseNoPermission();
		}
		
		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = XenForo_Application::getOptions()->BRARS_perPage;
		
		$conditions =  array(
				'giver_user_id' => $userId,
				'reputation_state' => $reputationModel->reputationStateViews()
		);
		
		$fetchOptions = array(
				'join' => 	Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_RECEIVER|
							Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST,
				'page' => $page,
				'perPage' => $perPage
		);
		
		$reputations = $reputationModel->getReputations($conditions, $fetchOptions);
		
		$totalReputations = 0;
		if(!empty($reputations))
		{
			$reputations = $reputationModel->prepareReputations($reputations);
			$totalReputations = $reputationModel->countReputations($conditions);
		}
		
		foreach ($reputations as $key=>$reputation)
		{
			if(empty($reputation['canView']))
			{
				$totalReputations--;
				unset($reputations[$key]);
			}
		}
		$viewParams = array(
			'user' => $userId,
		    'reputations' => $reputations,
			'totalReputations' => max(0, $totalReputations),
			
			'startOffset' => ($page-1)*$perPage +1,
			'endOffset' => ($page-1)*$perPage + count($reputations),
			'page' => $page,
			'perPage' => $perPage,
			'useStarInterface' => $reputationModel->canUseStarInterface()
		);
		
		return $this->_getWrapper(
			'account', 'givenreputations',
			$this->responseView('XenForo_ViewPublic_Base', 'BRARS_given_reputations', $viewParams)
		);
	}
	
	protected function _getReputationModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
	}
}
<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerPublic_Member extends XFCP_Brivium_AdvancedReputationSystem_ControllerPublic_Member 
{
	//Display the Reputation Stats on users profiles
    public function actionReputation() 
	{
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$userProfile = $this->getHelper('UserProfile')->getUserOrError($userId);
		
		$reputationModel = $this->_getReputationModel();
		if(!$reputationModel->canViewReputationProfile($userProfile))
		{
			return $this->responseNoPermission();
		}
		
		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$perPage = XenForo_Application::getOptions()->BRARS_perPage;
		
		$conditions =  array(
				'receiver_user_id' => $userId,
				'reputation_state' => $reputationModel->reputationStateViews()
		);
		
		$fetchOptions = array(
				'join' => Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER|  Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST,
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
			'user' => $userProfile,
		    'reputations' => $reputations,
			'totalReputations' => max(0, $totalReputations),

			'startOffset' => ($page-1)*$perPage +1,
			'endOffset' => ($page-1)*$perPage + count($reputations),
			'page' => $page,
			'perPage' => $perPage,
			'useStarInterface' => $reputationModel->canUseStarInterface()
		);
		
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Member_Reputation', 'BRARS_profile_reputation_stats', $viewParams);
	}
	
	//Adds most reputation users to the notables area at the member list
	protected function _getNotableMembers($type, $limit)
	{
		$parent = parent::_getNotableMembers( $type, $limit);
		$options = XenForo_Application::getOptions();
		if (! $parent && $type == 'reputation' && $options->reputation_users_members_sidebar_enable)
		{
			$userModel = $this->_getUserModel();
			
			$notableCriteria = array(
				'user_state' => 'valid',
				'is_banned' => 0,
				'reputation_count' => array('>', 0)
			);
			
			$typeMap = array(
				'reputation' => 'reputation_count'
			);
			
			if (! isset( $typeMap[$type]))
			{
				return false;
			}
			
			return array(
				$userModel->getUsers( $notableCriteria, array(
					'join' => XenForo_Model_User::FETCH_USER_FULL,
					'limit' => $limit,
					'order' => 'reputation_count',
					'direction' => 'desc'
				)),
				$typeMap[$type]
			);
		} 
		return $parent;
	}
	
	protected function _getReputationModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
	}
}
<?php

/**
 * Handles alerts of reputations.
 *
 * @package XenForo_Alert
 */
class Brivium_AdvancedReputationSystem_AlertHandler_Reputations extends XenForo_AlertHandler_Abstract
{
	/**
	 *
	 * @var Brivium_AdvancedReputationSystem_Model_Reputation
	 */
	protected $_reputationModel = null;

	/**
	 * Gets the reputation content.
	 * 
	 * @see XenForo_AlertHandler_Abstract::getContentByIds()
	 */
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$reputationModel = $this->_getReputationModel();
		$fetchOptions = array(
			'join' => 	Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST|
						Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER
		);
		return $reputationModel->getReputationsByIds( $contentIds, $fetchOptions);
	}

	/**
	 * Determines if the reputation is viewable.
	 * 
	 * @see XenForo_AlertHandler_Abstract::canViewAlert()
	 */
	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return $this->_getReputationModel()->canViewReputationAndContainer( $content, $content, $content, $content);
	}

	protected function _prepareTrophy(array $item)
	{
		if ($item['extra_data'])
		{
			$item['extra'] = unserialize($item['extra_data']);
	
			$item['trophy'] = new XenForo_Phrase(
					XenForo_Model::create('XenForo_Model_Trophy')->getTrophyTitlePhraseName($item['extra']['trophy_id'])
			);
		}
		unset($item['extra_data']);
	
		return $item;
	}
	
	protected function _getDefaultTemplateTitle($contentType, $action)
	{
		return 'BRARS_alert_reputation_' . $action;
	}

	/**
	 *
	 * @return Brivium_AdvancedReputationSystem_Model_Reputation
	 */
	protected function _getReputationModel()
	{
		if (! $this->_reputationModel)
		{
			$this->_reputationModel = XenForo_Model::create( 'Brivium_AdvancedReputationSystem_Model_Reputation');
		}
		
		return $this->_reputationModel;
	}
}
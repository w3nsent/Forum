<?php
class Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewPreviousReputations extends XenForo_ViewPublic_Base
{

	public function renderJson()
	{
		$reputations = array();
		
		if(empty($this->_params['reputations']))
		{
			return array(
				'reputations' => $reputations
			);
		}

		foreach($this->_params['reputations'] as $reputation)
		{
			$reputations[] = $this->createTemplateObject( 'BRARS_reputation_item', array(
				'reputation' => $reputation
			) + $this->_params);
		}
		
		if (!empty($this->_params['firstReputationShown']))
		{
			$this->_params['post']['brivium_first_shown_reputation_date'] = $this->_params['firstReputationShown']['reputation_date'];
			$reputations[] = $this->createTemplateObject( 'BRARS_view_previous_reputations', $this->_params);
		}
		return array(
			'reputations' => $reputations
		);
	}
}
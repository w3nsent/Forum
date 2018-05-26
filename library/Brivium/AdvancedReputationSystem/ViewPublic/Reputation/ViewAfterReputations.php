<?php
class Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewAfterReputations extends XenForo_ViewPublic_Base
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
		
		if (!empty($this->_params['lastReputationShown']))
		{
			$this->_params['post']['brivium_last_shown_reputation_date'] = $this->_params['lastReputationShown']['reputation_date'];
			$reputations[] = $this->createTemplateObject( 'BRARS_view_next_reputations', $this->_params);
		}

		foreach($this->_params['reputations'] as $reputation)
		{
			$reputations[] = $this->createTemplateObject( 'BRARS_reputation_item', array(
				'reputation' => $reputation
			) + $this->_params);
		}
		
		return array(
			'reputations' => $reputations
		);
	}
}
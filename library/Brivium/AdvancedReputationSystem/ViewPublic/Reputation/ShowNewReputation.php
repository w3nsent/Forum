<?php
class Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ShowNewReputation extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return array(
			'reputation' => $this->createTemplateObject( 'BRARS_reputation_item', $this->_params)
		);
	}
}
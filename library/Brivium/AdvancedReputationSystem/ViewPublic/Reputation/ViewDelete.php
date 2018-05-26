<?php
class Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewDelete extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$output = array('messagesTemplateHtml' => array());
		$reputationId = $this->_params['reputation']['reputation_id'];
		$output['messagesTemplateHtml']["#reputation-$reputationId"] = $this->createTemplateObject('BRARS_reputation_item',$this->_params)->render();

		$template = $this->createTemplateObject('', array());
		$output['css'] = $template->getRequiredExternals('css');
		$output['js'] = $template->getRequiredExternals('js');

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}
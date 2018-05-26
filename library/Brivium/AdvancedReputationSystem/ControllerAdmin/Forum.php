<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerAdmin_Forum extends XFCP_Brivium_AdvancedReputationSystem_ControllerAdmin_Forum
{
    public function actionSave()
    {
		$GLOBALS['BRARS_reputation_count_entrance'] =  $this->_input->filterSingle('reputation_count_entrance', XenForo_Input::UINT);
        return parent::actionSave();
    }
}
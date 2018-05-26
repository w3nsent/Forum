<?php

class Brivium_CustomNodeStyle_ControllerAdmin_Forum extends XFCP_Brivium_CustomNodeStyle_ControllerAdmin_Forum
{
	public function actionSave()
	{
		$GLOBALS['BRCNS_ControllerAdmin_Node'] = $this;
		return parent::actionSave();
	}
}
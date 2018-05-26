<?php

class Brivium_CustomNodeStyle_ControllerAdmin_Page extends XFCP_Brivium_CustomNodeStyle_ControllerAdmin_Page
{
	public function actionSave()
	{
		$GLOBALS['BRCNS_ControllerAdmin_Node']= $this;
		return parent::actionSave();
	}
}
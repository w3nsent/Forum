<?php

class Brivium_CustomNodeStyle_ControllerAdmin_LinkForum extends XFCP_Brivium_CustomNodeStyle_ControllerAdmin_LinkForum
{
	public function actionSave()
	{
		$GLOBALS['BRCNS_ControllerAdmin_Node']= $this;
		return parent::actionSave();
	}
}
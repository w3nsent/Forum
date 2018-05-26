<?php

class Brivium_CustomNodeStyle_ControllerAdmin_Category extends XFCP_Brivium_CustomNodeStyle_ControllerAdmin_Category
{
	public function actionSave()
	{
		$GLOBALS['BRCNS_ControllerAdmin_Node']= $this;
		return parent::actionSave();
	}
}
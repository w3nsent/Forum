<?php

class BS_ColorStatus_XenForo_ControllerPublic_Account extends XFCP_BS_ColorStatus_XenForo_ControllerPublic_Account
{	
	public function actionPersonalDetailsSave()
	{
		$statusColor = $this->_input->filterSingle('status_color', XenForo_Input::STRING);
		if ($statusColor && preg_match("/^#([0-9a-f]{3}){1,2}$/i", $statusColor))
		{
			if (!XenForo_Visitor::getInstance()->hasPermission('general', 'canUseColorStatus'))
			{
				return $this->responseNoPermission();
			}
			else
			{
				$GLOBALS['statusColor'] = $statusColor;
			}
		}	
		else if ($statusColor === 'empty')
		{
			$GLOBALS['statusColor'] = '';
		}
		return parent::actionPersonalDetailsSave();
	}
}
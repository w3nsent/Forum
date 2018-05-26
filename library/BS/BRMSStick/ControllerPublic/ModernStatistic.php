<?php

class BS_BRMSStick_ControllerPublic_ModernStatistic extends XFCP_BS_BRMSStick_ControllerPublic_ModernStatistic
{
	public function actionStatistics()
	{
		$parent = parent::actionStatistics();
		$BSModel = $this->_getBSModel();
		$stickedThreads = $BSModel->getSticked();
		$links = $BSModel->getStickedLinks();
		$parent->params += array(
			'sticked' => $stickedThreads,
			'links' => $links, 
			);
		return $parent;
	}

	public function _getBSModel()
	{
		return $this->getModelFromCache('BS_BRMSStick_Model');
	}
}
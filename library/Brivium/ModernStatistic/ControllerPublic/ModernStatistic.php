<?php

class Brivium_ModernStatistic_ControllerPublic_ModernStatistic extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('index')
		);
	}
	public function actionStatistics()
	{
		$limit = $this->_input->filterSingle('limit', XenForo_Input::UINT);
		$tabId = $this->_input->filterSingle('tab_id', XenForo_Input::UINT);
		$modernStatisticId = $this->_input->filterSingle('modern_statistic_id', XenForo_Input::UINT);

		if($modernStatisticId){
			$hardReload = $this->_input->filterSingle('hard_reload', XenForo_Input::UINT);
			$statisticModel = $this->_getModernStatisticModel();
			$userId = XenForo_Visitor::getUserId();
			$viewParams = $statisticModel->getStatisticTabParams($modernStatisticId, $tabId, $userId, $limit, $hardReload?false:true);
			$viewParams['tabId'] = $tabId;
			$viewParams['userId'] = $userId;
			$viewParams['modernStatisticId'] = $modernStatisticId;
			return $this->responseView('Brivium_ModernStatistic_ViewPublic_ModernStatistic', !empty($viewParams['template'])?$viewParams['template']:'', $viewParams);
		}else{
			return $this->responseView('Brivium_ModernStatistic_ViewPublic_ModernStatistic', '', array());
		}
	}

	/**
	 * Gets the thread model.
	 *
	 * @return XenForo_Model_Node
	 */
	protected function _getModernStatisticModel()
	{
		return $this->getModelFromCache('Brivium_ModernStatistic_Model_ModernStatistic');
	}
	protected function _getThreadModel()
	{
		return $this->getModelFromCache('XenForo_Model_Thread');
	}
}

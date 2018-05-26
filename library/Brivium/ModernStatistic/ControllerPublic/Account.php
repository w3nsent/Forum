<?php

class Brivium_ModernStatistic_ControllerPublic_Account extends XFCP_Brivium_ModernStatistic_ControllerPublic_Account
{
	public function actionPreferences()
	{
		$response = parent::actionPreferences();

		$modernStatisticModel = $this->_getModernStatisticModel();
		if(!empty($response->subView->params) && $modernStatisticModel->canChangePreference()){
			$modernStatistics = $modernStatisticModel->getActiveModernStatistics();
			$visitor = XenForo_Visitor::getInstance()->toArray();
			$statisticPerferences = !empty($visitor['brms_statistic_perferences'])?@unserialize($visitor['brms_statistic_perferences']):array();
			$response->subView->params['modernStatistics'] = $modernStatistics;
			$response->subView->params['statisticPerferences'] = $statisticPerferences;
		}
		return $response;
	}

	public function actionPreferencesSave()
	{
		$response = parent::actionPreferencesSave();

		$this->_assertPostOnly();
		if($this->_getModernStatisticModel()->canChangePreference()){
			$data = $this->_input->filter(array(
				'brms_statistic_perferences'    => XenForo_Input::ARRAY_SIMPLE,
			));
			$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
			if (!$writer = $this->_saveVisitorSettings($data, $errors))
			{
				return $this->responseError($errors);
			}
		}
		return $response;
	}

	/**
	 * Gets the product model.
	 *
	 * @return Brivium_ModernStatistic_Model_ModernStatistic
	 */
	protected function _getModernStatisticModel()
	{
		return $this->getModelFromCache('Brivium_ModernStatistic_Model_ModernStatistic');
	}
}

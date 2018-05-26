<?php

/**
 * Controller for accessing categories.
 *
 * @package XenForo_Nodes
 */
class Brivium_ModernStatistic_ControllerPublic_Category extends XFCP_Brivium_ModernStatistic_ControllerPublic_Category
{

	public function actionIndex()
	{
		$response = parent::actionIndex();
		if(!empty($response->params) && !empty($response->params['category'])){
			$GLOBALS['BRMS_category'] = $response->params['category'];
		}
		return $response;
	}
}

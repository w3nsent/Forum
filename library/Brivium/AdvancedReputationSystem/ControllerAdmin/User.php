<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerAdmin_User extends XFCP_Brivium_AdvancedReputationSystem_ControllerAdmin_User
{
	/**
	 * Deletes the specified user 's content.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDeleteReputations()
	{
		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$user = $this->_getUserOrError($userId);

		if ($user['is_admin'] || $user['is_moderator'])
		{
			return $this->responseNoPermission();
		}

		if ($this->isConfirmedPost())
		{
			$this->_getReputationModel()->deleteReputationsByUser($userId);
            //Redirect to user edit page
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('users/edit', $user)
			);
		}

		return $this->responseView('Brivium_AdvancedReputationSystem_ViewAdmin_User_Reputations_Delete', 'user_reputations_delete', array(
			'user' => $user
		));
	}
	
	protected function _getReputationModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
	}
}
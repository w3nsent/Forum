<?php

class Andy_WeeklyDigest_ControllerPublic_WeeklyDigest extends XenForo_ControllerPublic_Abstract
{	
	public function actionManage()
	{
		//########################################
		// Display a page for the member to
		// subscribe or unsubscribe to the
		// Weekly Digest.
		//########################################
		
		// get userId
		$userId = XenForo_Visitor::getUserId();
		
		if ($userId == 0)
		{
			$this->_assertRegistrationRequired();	
		}

		if ($userId > 0)
		{
			// declare variable
			$subscribed = '';	
					
			// get database
			$db = XenForo_Application::get('db');		
			
			// get subscribed
			$weekly_digest_opt_out = $db->fetchOne("
			SELECT weekly_digest_opt_out
			FROM xf_user
			WHERE user_id = ?
			", $userId);
	
			// prepare viewParams
			$viewParams = array(
				'weekly_digest_opt_out' => $weekly_digest_opt_out
			);
			
			// send to template
			return $this->responseView('Andy_WeeklyDigest_ViewPublic_WeeklyDigest','andy_weeklydigest',$viewParams);
		}
	}
	
	public function actionManageSave()
	{
		//########################################
		// Save members choice.
		//########################################
				
		// get userId
		$userId = XenForo_Visitor::getUserId();
		
		if ($userId == 0)
		{
			$this->_assertRegistrationRequired();	
		}		
				
		// get database
		$db = XenForo_Application::get('db');
		
		if ($userId > 0)
		{
			// get members choice
			$subscribe = $this->_input->filterSingle('subscribe', XenForo_Input::UINT);
			
			if ($subscribe == 0)
			{
				$db->query('
				UPDATE xf_user SET
					weekly_digest_opt_out = 0
					WHERE user_id = ?
				', $userId);	
			}
			
			if ($subscribe == 1)
			{
				$db->query('
				UPDATE xf_user SET
					weekly_digest_opt_out = 1
					WHERE user_id = ?
				', $userId);	
			}
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('index')
		);
	}
}
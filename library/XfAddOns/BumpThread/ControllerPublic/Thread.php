<?php

class XfAddOns_BumpThread_ControllerPublic_Thread extends XFCP_XfAddOns_BumpThread_ControllerPublic_Thread
{

	/**
	 * Bumps the thread, putting it on top of the forum for a while
	 */
	public function actionBump()
	{
		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($threadFetchOptions, $forumFetchOptions) = $this->_getThreadForumFetchOptions();
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);

		$visitor = XenForo_Visitor::getInstance();
		$perms = $visitor->getNodePermissions($thread['node_id']);
	    if (!XfAddOns_BumpThread_Helper_Permissions::canBump($thread, $perms))
    	{
    		return $this->responseNoPermission();
    	}
    	
    	$this->checkForBumpTimeLimit($perms);

		/* @var $dw XenForo_DataWriter_Discussion_Thread */
		$dwThread = XenForo_DataWriter::create("XenForo_DataWriter_Discussion_Thread");
		$dwThread->setExistingData($threadId);
		$dwThread->set('last_post_date', XenForo_Application::$time);
		$dwThread->save();

		/* @var $dwPost XenForo_DataWriter_DiscussionMessage_Post */
		$dwPost = XenForo_DataWriter::create("XenForo_DataWriter_DiscussionMessage_Post");
		$dwPost->setExistingData($thread['last_post_id']);
		$dwPost->set('post_date', XenForo_Application::$time);
		$dwPost->save();
		
		// let's also log the bump
		/* @var $bumpModel XfAddOns_BumpThread_Model_BumpThread */
		$bumpModel = XenForo_Model::create('XfAddOns_BumpThread_Model_BumpThread');
		$bumpModel->insertBumpData($visitor->get('user_id'), $thread['thread_id']);

		// and into the moderator log
		XenForo_Model_Log::logModeratorAction('thread', $thread, 'bump');
		
		// if we bumped from the thread list, redirect to the forum, else, redirect to the thread itself
		$fromList = (boolean) $this->_input->filterSingle('from_list', XenForo_Input::UINT);
		if ($fromList)
		{
			$redirectTo = XenForo_Link::buildPublicLink('forums', $forum);
		}
		else
		{
			$redirectTo = XenForo_Link::buildPublicLink('threads', $thread);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirectTo,
			new XenForo_Phrase('xfa_bump_thread_has_been_bumped'),
			array ( 'fromList' => $fromList )
		);
	}
	
	/**
	 * Check if there is a time limit for the user to bump a thread. If there is one we will check whether we have
	 * exceeded it.
	 * 
	 * @param array $perms							The permissions for the user
	 * @throws XenForo_ControllerResponse_Exception	Thrown if the user cannot bump a thread yet	
	 */
	private function checkForBumpTimeLimit(array $perms)
	{
		// a limit is not set for the user
		if (!isset($perms['xfa_bump_thread_time']) || $perms['xfa_bump_thread_time'] <= 0)
		{
			return;
		}
		
		// max threads to bump. If not set (0), or unlimited (-1), switch to the max int value
		$maxThreadsToBump = isset($perms['xfa_bump_threads_total']) ? $perms['xfa_bump_threads_total'] : -1;
		if ($maxThreadsToBump == 0 || $maxThreadsToBump == -1)
		{
			$maxThreadsToBump = PHP_INT_MAX;
		}
		
		/* @var $bumpModel XfAddOns_BumpThread_Model_BumpThread */
		$bumpModel = XenForo_Model::create('XfAddOns_BumpThread_Model_BumpThread');
		$sinceTime = XenForo_Application::$time - $perms['xfa_bump_thread_time'] * 60;
		
		$data = $bumpModel->totalBumpedSince(XenForo_Visitor::getUserId(), $sinceTime);
		if ($data['totalBumped'] >= $maxThreadsToBump)
		{
			$allowedAtTime = $data['firstBumpDate'] + ($perms['xfa_bump_thread_time'] * 60); 
			
			$params = array(
				'allowedThreads' =>  $maxThreadsToBump,
				'minutes' => intval(($allowedAtTime - XenForo_Application::$time) / 60),
				'seconds' => intval(($allowedAtTime - XenForo_Application::$time) % 60)
			);
			$msg = new XenForo_Phrase('xfa_bump_thread_time_disallowed', $params);
			throw new XenForo_ControllerResponse_Exception($this->responseError($msg)); 
		}
	}
	




}

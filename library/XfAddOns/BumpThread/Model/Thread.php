<?php

class XfAddOns_BumpThread_Model_Thread extends XFCP_XfAddOns_BumpThread_Model_Thread
{

	/**
	 * Overrides the prepareThread functionality to add
	 */
	public function prepareThread(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
		$thread = parent::prepareThread($thread, $forum, $nodePermissions, $viewingUser);
		$this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);
		$thread['canBump'] = XfAddOns_BumpThread_Helper_Permissions::canBump($thread, $nodePermissions);
		return $thread;
	}

}
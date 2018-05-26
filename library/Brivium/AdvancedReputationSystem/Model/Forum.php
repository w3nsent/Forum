<?php
class Brivium_AdvancedReputationSystem_Model_Forum extends XFCP_Brivium_AdvancedReputationSystem_Model_Forum
{
	public function canViewForum(array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$parent =  parent::canViewForum($forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		
		if(!empty($forum['node_id']) && !empty($options->excludedrepsforums) && in_array($forum['node_id'], $options->excludedrepsforums))
		{
			return $parent;
		}
		
		$this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);
		
		if($parent && !empty($forum['reputation_count_entrance']))
		{
			$reputationPoints = !empty($viewingUser['reputation_count'])?$viewingUser['reputation_count']:0;
			
			if($reputationPoints < $forum['reputation_count_entrance'] && !XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'exlude_forum_rep'))
			{
				return false;
			}
		}
		
		return $parent;
	}
	
	public function canViewForumContent(array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$parent =  parent::canViewForumContent($forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		if(!empty($forum['node_id']) && !empty($options->excludedrepsforums) && in_array($forum['node_id'], $options->excludedrepsforums))
		{
			return $parent;
		}
		
		$this->standardizeViewingUserReferenceForNode($forum['node_id'], $viewingUser, $nodePermissions);
		
		if($parent && !empty($forum['reputation_count_entrance']))
		{
			$reputationPoints = !empty($viewingUser['reputation_count'])?$viewingUser['reputation_count']:0;
			
			if($reputationPoints < $forum['reputation_count_entrance'] && !XenForo_Permission::hasPermission($viewingUser['permissions'], 'reputation', 'exlude_forum_rep'))
			{
				return false;
			}
		}
		
		return $parent;
	}
}
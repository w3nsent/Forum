<?php

// ######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_Like extends XFCP_Brivium_AdvancedReputationSystem_Model_Like
{
	// Get rep point(s) on post and profile posts likes
	public function likeContent($contentType, $contentId, $contentUserId, $likeUserId = null, $likeDate = null)
	{
		$parent = parent::likeContent( $contentType, $contentId, $contentUserId, $likeUserId, $likeDate);
		
		$likePoint = XenForo_Application::getOptions()->like_posts_rep_points;
		if (!empty($parent) && $likePoint && in_array($contentType, array('post', 'profile_post')))
		{
			$visitor = XenForo_Visitor::getInstance();
			$db = $this->_getDb();
			$db->query( "
				      UPDATE `xf_user`
				      SET reputation_count = reputation_count + ?
				      WHERE user_id = ?
	         ", array($likePoint,$contentUserId));
			
			if(XenForo_Model_Alert::userReceivesAlert(array('user_id' => $contentUserId), 'reputation', 'extra'))
			{
				XenForo_Model_Alert::alert($contentUserId, $visitor['user_id'], $visitor['username'], $contentType, $contentId, 'reputation_like', array(
					'points' => $likePoint
				));
			}
		}
		return $parent;
	}
	
	// Remove rep point(s) on post and profile posts un like
	public function unlikeContent(array $like)
	{
		$parent = parent::unlikeContent( $like);
		
		$likePoint = XenForo_Application::getOptions()->like_posts_rep_points;
		if (!empty($parent) && $likePoint && in_array($like['content_type'], array('post', 'profile_post')))
		{
			$db = $this->_getDb();
			$db->query( "
				      UPDATE `xf_user`
				      SET reputation_count = reputation_count - ?
				      WHERE user_id = ?
	         ", array($likePoint,$like['content_user_id']));
			
			$this->_getAlertModel()->deleteAlerts(
					$like['content_type'], $like['content_id'], $like['like_user_id'], 'reputation_like'
			);
		}
		
		return $parent;
	}
}
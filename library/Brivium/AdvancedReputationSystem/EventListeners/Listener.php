<?php

//######################## Reputation System By Brivium ###########################

class Brivium_AdvancedReputationSystem_EventListeners_Listener extends Brivium_BriviumHelper_EventListeners
{
	private static $_postModel = null;
	private static $_reputationModel = null;
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'member_view_tabs_content':
			case 'member_view_tabs_heading':
				if(empty($hookParams['user']) || !self::_getReputationModel()->canViewReputationProfile($hookParams['user']))
				{
					break;
				}
			case 'account_alerts_messages_in_threads':
			case 'forum_list_sidebar':
			case 'member_card_stats':
			case 'message_user_info_extra':
			case 'navigation_visitor_tab_links2':
			case 'post_private_controls':
			case 'sidebar_visitor_panel_stats':
			case 'account_wrapper_sidebar_settings':
				$params = $template->getParams();
				$forum = !empty($params['forum'])?$params['forum']:array();
				$canUseReputation = self::_getPostModel()->canUseReputation($forum);
				if($canUseReputation)
				{
					$newTemplate = $template->create('BRARS_' . $hookName, $template->getParams());
					$newTemplate->setParams($hookParams);
					$contents .= $newTemplate->render();
				}
				break;
			case 'user_criteria_extra':
			case 'forum_edit_basic_information':
				$newTemplate = $template->create('BRARS_' . $hookName, $template->getParams());
				$contents .= $newTemplate->render();
				break;
			case 'moderator_bar' :
				$canManagerModeration = self::_getPostModel()->canManagerModeration();
				if (! empty( $canManagerModeration))
				{
					$conditions = array('reputation_state' => 'moderated');
					//$conditions = array();
					$fetchOptions =  array('join' => Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST);
					$countUnApproveReputations = self::_getReputationModel()->countReputations($conditions, $fetchOptions);
					if($countUnApproveReputations)
					{
						$newTemplate = $template->create( 'BRARS_' . $hookName, $template->getParams());
						$newTemplate->setParam('unApproveReputationCounts', $countUnApproveReputations);
						$contents .= $newTemplate->render();
					}
				}
				break;
			case 'message_content' :
				$forum = !empty($params['forum'])?$params['forum']:array();
				$canUseReputation = self::_getPostModel()->canUseReputation($forum);
				$post = !empty($hookParams['message'])?$hookParams['message']:array();
				if (! empty( $canUseReputation) && !empty($post['reputationStar']))
				{
					$newTemplate = $template->create('BRARS_' . $hookName, $template->getParams());
					$newTemplate->setParam('post', $post);
					$contents = $newTemplate->render() . $contents;
				}
				break;
			case 'navigation_tabs_forums':
				if(self::_getReputationModel()->canViewReputationStatistics())
				{
					$newTemplate = $template->create('BRARS_' . $hookName, $template->getParams());
					$newTemplate->setParams($hookParams);
					$contents .= $newTemplate->render();
				}
				break;
		}
	}
	
	//User Criteria
	public static function criteriaUser($rule, array $data, array $user, &$returnValue)
    {
        switch ($rule)
        {
            case 'reputations_points':
            	$returnValue = !(!isset($user['reputation_count']) || $user['reputation_count'] <= $data['points']); break;
            	
            case 'reputations_rated':
            	$returnValue = !(!isset($user['brars_rated_count']) || $user['brars_rated_count'] <= $data['rated']); break;
            	
            case 'reputations_rated_negative':
            	$returnValue = !(!isset($user['brars_rated_negative_count']) || $user['brars_rated_negative_count'] <= $data['rated_negative']); break;

            case 'reputations_received_not_negative':
            	$users = self::_getReputationModel()->getLastNumberReceviedNotNegative();
				$returnValue = !(!isset($users[$user['user_id']]['brNumberReputationNotNegativer']) || $users[$user['user_id']]['brNumberReputationNotNegativer'] < $data['received_not_negative']); break;
            
            case 'reputations_points_more_posts':
            	$returnValue = !(!isset($user['reputation_count']) || !isset($user['brars_post_count']) || ($user['reputation_count'] <= $user['brars_post_count'])); break;
        }
    }
	
	//vB 3x&4x and MyBB 1.6/1.8 reputation points importer
	public static function reputationImporter($class, array &$extend) 
	{
		if (strpos($class, 'vBulletin') != false AND !defined('Brivium_AdvancedReputationSystem_Importer_vBulletin_LOADED')) 
		{
			$extend[] = 'Brivium_AdvancedReputationSystem_Importer_vBulletin';
		}

		if (strpos($class, 'MyBb') != false AND !defined('Brivium_AdvancedReputationSystem_Importer_MyBb_LOADED')) 
		{
			$extend[] = 'Brivium_AdvancedReputationSystem_Importer_MyBb';
		}
	}
	
	
	private static function _getPostModel()
	{
		if(empty(self::$_postModel))
		{
			self::$_postModel = XenForo_Model::create('XenForo_Model_Post');
		}
		
		return self::$_postModel;
	}
	
	private static function _getReputationModel()
	{
		if(empty(self::$_reputationModel))
		{
			self::$_reputationModel = XenForo_Model::create('Brivium_AdvancedReputationSystem_Model_Reputation');
		}
		
		return self::$_reputationModel;
	}
}
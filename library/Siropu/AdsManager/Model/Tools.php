<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Tools extends Xenforo_Model
{
	public function reset($items)
	{
		$db = $this->_getDb();

		if (!empty($items['ads']))
		{
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_ads');
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_stats_daily');
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_stats_clicks');

			foreach (glob(Siropu_AdsManager_Helper_General::getBannerPath('absolute') . '/*') as $file)
			{
				if (!preg_match('/index\.html/', $file))
				{
					@unlink($file);
				}
			}

			XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
		}
		if (!empty($items['ads_stats']))
		{
			$db->update('xf_siropu_ads_manager_ads', array('view_count' => 0, 'click_count' => 0, 'ctr' => 0));
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_stats_daily');
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_stats_clicks');
		}
		if (!empty($items['positions']))
		{
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_positions');
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_positions_categories');

			$this->insertPositions();
		}
		if (!empty($items['packages']))
		{
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_packages');
		}
		if (!empty($items['invoices']))
		{
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_transactions');
		}
		if (!empty($items['promo_codes']))
		{
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_promo_codes');
		}
		if (!empty($items['subscriptions']))
		{
			$db->query('TRUNCATE TABLE xf_siropu_ads_manager_subscriptions');
		}
	}
	public function insertPositions()
	{
		$this->_getDb()->query("
			INSERT IGNORE INTO `xf_siropu_ads_manager_positions_categories`
				(`cat_id`, `title`, `display_order`)
			VALUES
				(1, 'Suggested Positions', '0'),
				(2, 'Thread Post', '1'),
				(3, 'Conversation Post', '2'),
				(4, 'Profile Post', '3'),
				(5, 'Forum Category After ID', '4'),
				(6, 'Forum Node ID', '5'),
				(7, 'Page Node ID', '6'),
				(8, 'Link Node ID', '7'),
				(9, 'Thread List Thread ID', '8'),
				(10, 'Page Results', '9'),
				(11, 'Members', '10'),
				(12, 'Resourse Manager', 11),
				(13, 'Media Gallery', 12)
		");

		$this->_getDb()->query("
			INSERT IGNORE INTO `xf_siropu_ads_manager_positions`
				(`position_id`, `hook`, `name`, `description`, `cat_id`)
			VALUES
				(
					NULL,
					'ad_header',
					'Header',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'ad_above_top_breadcrumb',
					'Above Top Breadcrumb',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'ad_below_top_breadcrumb',
					'Below Top Breadcrumb', 'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'ad_below_bottom_breadcrumb',
					'Below Bottom Breadcrumb',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'ad_above_content',
					'Above Content',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'ad_below_content',
					'Below Content',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'forum_list_nodes',
					'Below Forum List Nodes',
					'Leaderboard (728x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'ad_sidebar_top',
					'Sidebar Top',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'0'
				),
				(
					NULL,
					'ad_sidebar_below_visitor_panel',
					'Sidebar Below Visitor Panel',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'0'
				),
				(
					NULL,
					'ad_sidebar_bottom',
					'Sidebar Bottom',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'1'
				),
				(
					NULL,
					'ad_forum_view_above_node_list',
					'Forum View Above Node List',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'ad_forum_view_above_thread_list',
					'Forum View Above Thread List',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'ad_thread_list_below_stickies',
					'Thread List Below Stickies',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'ad_thread_view_above_messages',
					'Thread View Above Messages',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'ad_thread_view_below_messages',
					'Thread View Below Messages',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'1'
				),
				(
					NULL,
					'ad_member_view_below_avatar',
					'Member View Below Avatar',
					'Wide Skyscraper (160x600), Vertical Banner (120x240)',
					'11'
				),
				(
					NULL,
					'ad_member_view_sidebar_bottom',
					'Member View Sidebar Bottom',
					'Wide Skyscraper (160x600), Vertical Banner (120x240)',
					'11'
				),
				(
					NULL,
					'ad_member_view_above_messages',
					'Member View Above Message',
					'Leaderboard (728x90), Banner (468x60)',
					'11'
				),
				(
					NULL,
					'account_wrapper_sidebar',
					'Account Sidebar Bottom',
					'Banner (125x125)',
					'11'
				),
				(
					NULL,
					'account_wrapper_content',
					'Account Below Content',
					'Leaderboard (728x90), Banner (468x60)',
					'11'
				),
				(
					NULL,
					'sam_resource_index_sidebar_top',
					'Resource Index Sidebar Top',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'sam_resource_index_sidebar_below_categories',
					'Resource Index Sidebar Below Categories',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'sam_resource_index_sidebar_below_top_resources',
					'Resource Index Sidebar Below Top Resources',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'sam_resource_index_sidebar_below_latest_reviews',
					'Resource Index Sidebar Below Latest Reviews',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'sam_resource_index_sidebar_bottom',
					'Resource Index Sidebar Bottom',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'sam_resource_list_after_item_x',
					'Resource List After x Resource (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Banner (468x60)',
					'12'
				),
				(
					NULL,
					'resource_view_header_after_info',
					'Resource View Header After Info',
					'Leaderboard (728x90), Banner (468x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_below_info',
					'Resource View Sidebar Below Resource Info',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_below_version',
					'Resource View Sidebar Below Resource Version',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_below_support_button',
					'Resource View Sidebar Below Support Button',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_below_discussion_button',
					'Resource View Sidebar Below Discussion Button',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_below_other_resources',
					'Resource View Sidebar Below Other Resources',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_below_resource_controls',
					'Resource View Sidebar Below Resource Controlls',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'resource_view_sidebar_end',
					'Resource View Sidebar End',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'12'
				),
				(
					NULL,
					'sam_media_after_item_x',
					'After Media Item x (Works with position criteria \"Item ID\" option)',
					'Square (250x250), Medium Rectangle (300x250)',
					'13'
				),
				(
					NULL,
					'sam_media_album_after_item_x',
					'After Media Album x (Works with position criteria \"Item ID\" option)',
					'Square (250x250), Medium Rectangle (300x250)',
					'13'
				),
				(
					NULL,
					'sam_media_preview_video',
					'Media Preview Video',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_preview_video_full',
					'Media Preview Video Full',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_below_media',
					'Media View Below Media',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_video',
					'Media View Video',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_video_full',
					'Media View Video Full',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_image',
					'Media View Image',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_image_full',
					'Media View Image Full',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_sidebar_top',
					'Media View Sidebar Top',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'13'
				),
				(
					NULL,
					'sam_media_view_sidebar_end',
					'Media View Sidebar Bottom',
					'Wide Skyscraper (160x600), Square (250x250), Half Banner (234x60)',
					'13'
				),
				(
					NULL,
					'sam_media_above_editor',
					'Media Above Editor',
					'Leaderboard (728x90), Banner (468x60)',
					'13'
				),
				(
					NULL,
					'sam_media_sidebar_top',
					'Media Sidebar Top',
					'Square (250x250), Medium Rectangle (300x250)',
					'13'
				),
				(
					NULL,
					'sam_media_sidebar_bottom',
					'Media Sidebar Bottom',
					'Square (250x250), Medium Rectangle (300x250)',
					'13'
				),
				(
					NULL,
					'pagenode_container_article',
					'Page Node Container Article',
					'Leaderboard (728x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'message_content',
					'Message Content',
					'Keyword Ads Only',
					'0'
				),
				(
					NULL,
					'ad_message_body',
					'Message Body',
					'Not Recommended',
					'0'
				),
				(
					NULL,
					'ad_message_below',
					'Message Below',
					'Not Recommended',
					'0'
				),
				(
					NULL,
					'footer',
					'Footer',
					'Leaderboard (728x90), Large Leaderboard (970x90), Banner (468x60)',
					'0'
				),
				(
					NULL,
					'sam_thread_post_message_inside_x',
					'Inside Post x Message (Works with position criteria \"Item ID\" option)',
					'Large Rectangle (336x280), Medium Rectangle (300x250)',
					'2'
				),
				(
					NULL,
					'sam_thread_post_message_below_x',
					'Below Post x Message (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90)',
					'2'
				),
				(
					NULL,
					'sam_thread_post_container_after_x',
					'After Post x Container (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Large Leaderboard (970x90)',
					'2'
				),
				(
					NULL,
					'sam_conversation_post_message_inside_x',
					'Inside Post x Message (Works with position criteria \"Item ID\" option)',
					'Large Rectangle (336x280), Medium Rectangle (300x250)',
					'3'
				),
				(
					NULL,
					'sam_conversation_post_message_below_x',
					'Below Post x Message (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90)',
					'3'
				),
				(
					NULL,
					'sam_conversation_post_container_after_x',
					'After Post x Container (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Large Leaderboard (970x90)',
					'3'
				),
				(
					NULL,
					'sam_profile_post_container_after_x',
					'After Post x Container (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90)',
					'4'
				),
				(
					NULL,
					'sam_forum_category_after_x',
					'After Forum Category x (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90)',
					'5'
				),
				(
					NULL,
					'sam_node_forum_description_x',
					'Inside forum node x description (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'6'
				),
				(
					NULL,
					'sam_node_forum_right_x',
					'Right side forum node x (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'6'
				),
				(
					NULL,
					'sam_forum_level_2_before_lastpost_x',
					'Inside forum node x before last post (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Banner (468x60)',
					'6'
				),
				(
					NULL,
					'sam_node_page_description_x',
					'Inside page node x description (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'7'
				),
				(
					NULL,
					'sam_node_page_right_x',
					'Right side page node x (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'7'
				),
				(
					NULL,
					'sam_node_link_description_x',
					'Inside link node x description (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'8'
				),
				(
					NULL,
					'sam_node_link_right_x',
					'Right side link node x (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'8'
				),
				(
					NULL,
					'sam_thread_list_item_x',
					'Thread list on the right side of the thread x title (Works with position criteria \"Item ID\" option)',
					'Button (120x60, 120x30, 88x31)',
					'9'
				),
				(
					NULL, 'sam_thread_list_after_item_x',
					'Thread list after x thread (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Banner (468x60)',
					'10'
				),
				(
					NULL,
					'sam_search_results_after_result_x',
					'Search results after x result (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Banner (468x60)',
					'10'
				),
				(
					NULL,
					'sam_tag_view_after_result_x',
					'Tag results after x result (Works with position criteria \"Item ID\" option)',
					'Leaderboard (728x90), Banner (468x60)',
					'10'
				)
		");

		Siropu_AdsManager_Helper_General::refreshAdPositionsCache();
	}
}

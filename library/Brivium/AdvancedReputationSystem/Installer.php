<?php
class Brivium_AdvancedReputationSystem_Installer extends Brivium_BriviumHelper_Installer
{
	public function getTables()
	{
		$tables = array();
		
		$tables["xf_reputation"] = "
				CREATE TABLE IF NOT EXISTS `xf_reputation` (
				  `reputation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `post_id` int(10) unsigned NOT NULL,
				  `receiver_user_id` int(10) unsigned NOT NULL,
				  `receiver_username` varchar(50) NOT NULL,
				  `giver_user_id` int(10) unsigned NOT NULL,
				  `giver_username` varchar(50) NOT NULL,
				  `reputation_date` int(10) unsigned NOT NULL,
				  `points` int(11) NOT NULL,
				  `comment` varchar(255) DEFAULT NULL,
				  `is_anonymous` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `reputation_state` enum('visible','moderated','deleted') NOT NULL DEFAULT 'visible',
				  `email_address` varchar(150) NOT NULL,
				  `ip_address` int(10) unsigned NOT NULL DEFAULT '0',
				  `encode` varchar(36) NOT NULL,
				  PRIMARY KEY (`reputation_id`),
				  KEY `receiver_user_id` (`receiver_user_id`),
				  KEY `giver_user_id` (`giver_user_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				";
		
		$tables["xf_brivium_preset_reputation_message"] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_preset_reputation_message` (
				  `preset_id` int(10) NOT NULL AUTO_INCREMENT,
				  `title` varchar(200) NOT NULL,
				  `message` varchar(255) NOT NULL,
				  `post_date` int(10) unsigned NOT NULL DEFAULT '0',
				  `active` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  PRIMARY KEY (`preset_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				";
		
		$tables["xf_brivium_preset_delete"] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_preset_delete` (
				  `preset_delete_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `reason` varchar(255) NOT NULL,
				  `message` mediumtext NOT NULL,
				  `post_date` int(10) unsigned NOT NULL DEFAULT '0',
				  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
				  PRIMARY KEY (`preset_delete_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
				";
		return $tables;
	}

	public function getData()
	{
		$data = array();
		
		$data = array();
		$data['xf_content_type'] = "
			INSERT IGNORE INTO xf_content_type
				(content_type, addon_id, fields)
			VALUES
				('brivium_reputation_system', 'ReputationSystem', '');
		";
		
		$data['xf_content_type_field'] = "
			INSERT IGNORE INTO `xf_content_type_field`
				(`content_type`, `field_name`, `field_value`)
			VALUES
				('brivium_reputation_system', 'alert_handler_class', 'Brivium_AdvancedReputationSystem_AlertHandler_Reputations'),
				('brivium_reputation_system', 'moderator_log_handler_class', 'Brivium_AdvancedReputationSystem_ModeratorLogHandler_Reputations'),
				('brivium_reputation_system', 'report_handler_class', 'Brivium_AdvancedReputationSystem_ReportHandler_Reputations');
		";
		
		return $data;
	}
	
	protected function _postInstall()
	{
		XenForo_Application::set( 'contentTypes', XenForo_Model::create( 'XenForo_Model_ContentType')->getContentTypesForCache());
		if(!empty( $this->_existingVersionId) && $this->_existingVersionId <= 4000000)
		{
			XenForo_Application::defer('Brivium_AdvancedReputationSystem_Deferred_ReputationToPost', array(), 'BRARS_reputaton_to_posts', true);
			XenForo_Application::defer('Brivium_AdvancedReputationSystem_Deferred_ReputationToUser', array(), 'BRARS_reputaton_to_users', true);
		}
	}

	public function getAlters()
	{
		$alters = array();
		
		$alters["xf_user"] = array(
			"reputation_count" => "INT(11) DEFAULT '0'",
			"brars_post_count" => "INT(11) DEFAULT '0'",
			"brars_rated_count" => "INT(11) DEFAULT '0'",
			"brars_rated_negative_count" => "INT(11) DEFAULT '0'"
		);
		
		$alters["xf_forum"] = array(
			"reputation_count_entrance" => "INT(11) DEFAULT '0'"
		);
		
		$alters["xf_post"] = array(
			"reputations" => "INT(11) DEFAULT '0'",
			"br_reputations_count" => "INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
			"br_first_reputation_date" => "INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
			"br_last_reputations_date" => "INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
			"br_lastest_reputation_ids" => "varbinary(200) DEFAULT NULL"
		);
		
		$alters["xf_reputation"] = array(
			"reputation_state"		=> "enum('visible','moderated','deleted') NOT NULL DEFAULT 'visible'",
			"email_address"		=> "varchar(150) NOT NULL",
			"ip_address"		=> "int(10) unsigned NOT NULL DEFAULT '0'",
			"encode"		=> "varchar(36) NOT NULL",
			"is_anonymous"		=> "tinyint(3) unsigned NOT NULL DEFAULT '0'",
			'bdreputation_given_id' => 'INT( 10 ) UNSIGNED NULL',
		);
		return $alters;
	}
	
	public function getQueryBeforeAlter()
	{
		$query = array();
		$query[] = "ALTER TABLE  `xf_reputation` ADD UNIQUE `bdreputation_given_id` (`bdreputation_given_id`);";
		return $query;
	}

	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'ReputationSystem';
		";
		if($this->_triggerType != "uninstall"){
			
			$query[] = "
				DELETE FROM `xf_content_type` WHERE ((`content_type` = 'reputation') OR (`content_type` = 'borbole_alert')) AND (`addon_id` = 'ReputationSystem');
			";
				
			$query[] = "
				DELETE FROM `xf_content_type_field` WHERE ((`content_type` = 'reputation') OR (`content_type` = 'borbole_alert')) AND (`field_name` = 'alert_handler_class');
			";
			
			$query[] = "
				DELETE FROM `xf_content_type_field` WHERE (`content_type` = 'brivium_reputation_system') AND (`field_name` = 'moderation_queue_handler_class');
			";
			
			$query[] = "
				DROP INDEX post_id_giver_user_id ON xf_reputation;
			";
				
			$query[] = "
				REPLACE INTO `xf_brivium_addon` 
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`) 
				VALUES
					('ReputationSystem', 'Reputation System', '4000000', 0, 0, 0);
			";
			$query[] = "
				REPLACE INTO `xf_brivium_listener_class` 
					(`class`, `class_extend`, `event_id`, `addon_id`) 
				VALUES
					('XenForo_ControllerAdmin_Forum', 'Brivium_AdvancedReputationSystem_ControllerAdmin_Forum', 'load_class_controller', 'ReputationSystem'),
					('XenForo_Model_User', 'Brivium_AdvancedReputationSystem_Model_User', 'load_class_model', 'ReputationSystem'),
					('XenForo_Model_Thread', 'Brivium_AdvancedReputationSystem_Model_Thread', 'load_class_model', 'ReputationSystem'),
					('XenForo_Model_Post', 'Brivium_AdvancedReputationSystem_Model_Post', 'load_class_model', 'ReputationSystem'),
					('XenForo_Model_Like', 'Brivium_AdvancedReputationSystem_Model_Like', 'load_class_model', 'ReputationSystem'),
					('XenForo_Model_Forum', 'Brivium_AdvancedReputationSystem_Model_Forum', 'load_class_model', 'ReputationSystem'),
					('XenForo_Model_Avatar', 'Brivium_AdvancedReputationSystem_Model_Avatar', 'load_class_model', 'ReputationSystem'),
					('XenForo_DataWriter_User', 'Brivium_AdvancedReputationSystem_DataWriter_User', 'load_class_datawriter', 'ReputationSystem'),
					('XenForo_DataWriter_Forum', 'Brivium_AdvancedReputationSystem_DataWriter_Forum', 'load_class_datawriter', 'ReputationSystem'),
					('XenForo_DataWriter_DiscussionMessage_Post', 'Brivium_AdvancedReputationSystem_DataWriter_DiscussionMessage_Post', 'load_class_datawriter', 'ReputationSystem'),
					('XenForo_ControllerPublic_Thread', 'Brivium_AdvancedReputationSystem_ControllerPublic_Thread', 'load_class_controller', 'ReputationSystem'),
					('XenForo_ControllerPublic_Post', 'Brivium_AdvancedReputationSystem_ControllerPublic_Post', 'load_class_controller', 'ReputationSystem'),
					('XenForo_ControllerPublic_Member', 'Brivium_AdvancedReputationSystem_ControllerPublic_Member', 'load_class_controller', 'ReputationSystem'),
					('XenForo_ControllerPublic_Forum', 'Brivium_AdvancedReputationSystem_ControllerPublic_Forum', 'load_class_controller', 'ReputationSystem'),
					('XenForo_ControllerPublic_Account', 'Brivium_AdvancedReputationSystem_ControllerPublic_Account', 'load_class_controller', 'ReputationSystem'),
					('XenForo_ControllerAdmin_User', 'Brivium_AdvancedReputationSystem_ControllerAdmin_User', 'load_class_controller', 'ReputationSystem'),
					('XenForo_Model_UserUpgrade', 'Brivium_AdvancedReputationSystem_Model_UserUpgrade', 'load_class_model', 'ReputationSystem');
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'ReputationSystem';
			";
		}
		return $query;
	}
}

?>
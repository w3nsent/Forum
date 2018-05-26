<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_Install
{
	public static function install()
	{
		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_sessions`(
				`user_id` INT(10) UNSIGNED NOT NULL PRIMARY KEY,
				`user_room_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`user_rooms` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`user_is_banned` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`user_is_muted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`user_settings` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`user_status` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`user_message_count` INT(10) UNSIGNED NOT NULL,
				`user_last_activity` INT(10) UNSIGNED NOT NULL,
				INDEX `user_room_id` (`user_room_id`),
				INDEX `user_is_banned` (`user_is_banned`),
				INDEX `user_last_activity` (`user_last_activity`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_messages`(
				`message_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`message_room_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`message_user_id` INT(10) UNSIGNED NOT NULL,
				`message_bot_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`message_recipients` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`message_tagged` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`message_text` MEDIUMTEXT COLLATE utf8_unicode_ci NOT NULL,
				`message_type` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'chat',
				`message_date` INT(10) UNSIGNED NOT NULL,
				INDEX `message_room_id` (`message_room_id`),
				INDEX `message_user_id` (`message_user_id`),
				INDEX `message_type` (`message_type`),
				INDEX `message_date` (`message_date`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_rooms`(
				`room_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`room_user_id` INT(10) UNSIGNED NOT NULL,
				`room_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`room_description` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`room_password` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`room_permissions` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`room_locked` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`room_auto_delete` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`room_date` INT(10) UNSIGNED NOT NULL,
				`room_last_activity` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				INDEX `room_user_id` (`room_user_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_bans`(
				`ban_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`ban_user_id` INT(10) UNSIGNED NOT NULL,
				`ban_room_id` INT(10) NOT NULL,
				`ban_start` INT(10) UNSIGNED NOT NULL,
				`ban_end` INT(10) UNSIGNED NOT NULL,
				`ban_author` INT(10) UNSIGNED NOT NULL,
				`ban_reason` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`ban_type` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL,
				INDEX `ban_user_id` (`ban_user_id`,`ban_room_id`),
				INDEX `ban_type` (`ban_user_id`,`ban_type`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_reports`(
				`report_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`report_message_id` INT(10) UNSIGNED NOT NULL,
				`report_message_user_id` INT(10) UNSIGNED NOT NULL,
				`report_message_text` MEDIUMTEXT COLLATE utf8_unicode_ci NOT NULL,
				`report_message_date` INT(10) UNSIGNED NOT NULL,
				`report_room_id` INT(10) UNSIGNED NOT NULL,
				`report_user_id` INT(10) UNSIGNED NOT NULL,
				`report_reason` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`report_date` INT(10) UNSIGNED NOT NULL,
				`report_state` ENUM('open','resolved','rejected') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'open',
				`report_update_date` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`report_update_user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`report_update_username` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				INDEX `report_message_id` (`report_message_id`),
				INDEX `report_room_id` (`report_room_id`),
				INDEX `report_user_id` (`report_user_id`),
				INDEX `report_state` (`report_state`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_bot_responses`(
				`response_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`response_bot_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`response_keyword` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`response_message` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`response_description` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`response_rooms` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`response_user_groups` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`response_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`response_settings` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`response_last` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`response_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_bot_messages`(
				`message_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`message_bot_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`message_title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`message_text` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`message_rooms` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`message_rules` MEDIUMBLOB NOT NULL,
				`message_date` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`message_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`message_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				INDEX `message_enabled` (`message_enabled`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_chat_images`(
				`image_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`image_user_id` INT(10) UNSIGNED NOT NULL,
				`image_name` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`image_date` INT(10) UNSIGNED NOT NULL,
				INDEX `image_user_id` (`image_user_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		if ($addon = XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('siropu_chat'))
		{
			if ($addon['version_id'] < 18)
			{
				self::_getDb()->query("ALTER TABLE `xf_siropu_chat_messages` CHANGE `message_type` `message_type` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'chat';");
			}

			if ($addon['version_id'] < 63)
			{
				self::_getDb()->query("ALTER TABLE `xf_siropu_chat_bans` CHANGE `ban_room_id` `ban_room_id` INT(10) NOT NULL;");

				self::_getDb()->update('xf_siropu_chat_bans', array('ban_room_id' => -1), 'ban_type = "chat"');

				self::_getDb()->query("ALTER TABLE `xf_siropu_chat_bans` CHANGE `ban_type` `ban_type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");

				self::_getDb()->update('xf_siropu_chat_bans', array('ban_type' => 'ban'), 'ban_type IN ("chat", "room")');
			}
		}

		if (!self::_chatContentTypeExists())
		{
			self::_getDb()->query("
				INSERT IGNORE INTO xf_content_type
					(content_type, addon_id, fields)
				VALUES
					('siropu_chat', 'siropu_chat', '')
			");

			self::_getDb()->query("
				INSERT IGNORE INTO xf_content_type_field
					(content_type, field_name, field_value)
				VALUES
					('siropu_chat', 'alert_handler_class', 'Siropu_Chat_AlertHandler')
			");
		}

		self::addColumnIfNotExists(array(
			'xf_siropu_chat_messages' => array(
				array(
					'name'  => 'message_type',
					'attr'  => "VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'chat'",
					'after' => 'message_text'
				),
				array(
					'name'  => 'message_room_id',
					'attr'  => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
					'after' => 'message_id'
				),
				array(
					'name'  => 'message_recipients',
					'attr'  => "TEXT COLLATE utf8_unicode_ci NOT NULL",
					'after' => 'message_user_id'
				),
				array(
					'name'  => 'message_tagged',
					'attr'  => "TEXT COLLATE utf8_unicode_ci NOT NULL",
					'after' => 'message_recipients'
				),
				array(
					'name'  => 'message_bot_name',
					'attr'  => "VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL",
					'after' => 'message_user_id'
				)
			),
			'xf_siropu_chat_rooms' => array(
				array(
					'name'  => 'room_auto_delete',
					'attr'  => "TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'",
					'after' => 'room_locked'
				),
				array(
					'name'  => 'room_last_activity',
					'attr'  => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
					'after' => 'room_date'
				),
			),
			'xf_siropu_chat_sessions' => array(
				array(
					'name'  => 'user_room_id',
					'attr'  => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
					'after' => 'user_id'
				),
				array(
					'name'  => 'user_message_count',
					'attr'  => "INT(10) UNSIGNED NOT NULL DEFAULT '0'",
					'after' => 'user_settings'
				),
				array(
					'name'  => 'user_rooms',
					'attr'  => "TEXT COLLATE utf8_unicode_ci NOT NULL",
					'after' => 'user_room_id'
				),
				array(
					'name'  => 'user_status',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'user_settings'
				),
				array(
					'name'  => 'user_is_muted',
					'attr'  => "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
					'after' => 'user_is_banned'
				),
			),
			'xf_siropu_chat_bot_responses' => array(
				array(
					'name'  => 'response_settings',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'response_type'
				),
				array(
					'name'  => 'response_bot_name',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'response_id'
				),
				array(
					'name'  => 'response_last',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'response_settings'
				),
				array(
					'name'  => 'response_rooms',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'response_description'
				),
				array(
					'name'  => 'response_user_groups',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'response_rooms'
				)
			),
			'xf_siropu_chat_bot_messages' => array(
				array(
					'name'  => 'message_bot_name',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'message_id'
				),
			),
			'xf_siropu_chat_reports' => array(
				array(
					'name'  => 'report_message_text',
					'attr'  => 'MEDIUMTEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'report_message_id'
				),
				array(
					'name'  => 'report_message_user_id',
					'attr'  => 'INT(10) UNSIGNED NOT NULL',
					'after' => 'report_message_text'
				),
				array(
					'name'  => 'report_message_date',
					'attr'  => 'INT(10) UNSIGNED NOT NULL',
					'after' => 'report_message_text'
				),
			)
		));
	}
	public static function addColumnIfNotExists($columnArray)
	{
		foreach ($columnArray as $table => $columns)
		{
			foreach ($columns as $column)
			{
				if (!self::_getDb()->fetchRow('SHOW COLUMNS FROM ' . $table . ' WHERE Field = ?', $column['name']))
				{
					self::_getDb()->query('
						ALTER TABLE ' . $table . '
						ADD ' . $column['name'] . ' ' . $column['attr'] . '
						AFTER ' . $column['after']
					);
				}
			}
		}
	}
	public static function uninstall()
	{
		self::_getDb()->query('
			DROP TABLE
				`xf_siropu_chat_sessions`,
				`xf_siropu_chat_messages`,
				`xf_siropu_chat_rooms`,
				`xf_siropu_chat_bans`,
				`xf_siropu_chat_reports`,
				`xf_siropu_chat_bot_responses`,
				`xf_siropu_chat_bot_messages`,
				`xf_siropu_chat_images`
		');

		if (self::_chatContentTypeExists())
		{
			self::_getDb()->delete('xf_content_type', 'content_type = \'siropu_chat\'');
			self::_getDb()->delete('xf_content_type_field', 'content_type = \'siropu_chat\'');

			XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
		}

		XenForo_Application::setSimpleCacheData('chatRooms', '');
		Siropu_Chat_Helper::resetTopChatters(true);
	}
	protected static function _getDb()
	{
		return XenForo_Application::get('db');
	}
	protected static function _chatContentTypeExists()
	{
		return self::_getDb()->fetchRow('
			SELECT *
			FROM xf_content_type
			WHERE content_type = ?
		', 'siropu_chat');
	}
}
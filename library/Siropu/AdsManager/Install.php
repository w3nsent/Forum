<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Install
{
	public static function install()
	{
		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_positions_categories`(
				`cat_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`display_order` INT(10) UNSIGNED NOT NULL DEFAULT '0'
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_positions`(
				`position_id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`hook` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`description` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`display_order` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`cat_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`visible` INT(1) UNSIGNED NOT NULL DEFAULT '1',
				UNIQUE KEY `hook` (`hook`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::addColumnIfNotExists(array(
			'xf_siropu_ads_manager_positions' => array(
				array(
					'name'  => 'cat_id',
					'attr'  => 'INT(10) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'name'
				),
				array(
					'name'  => 'display_order',
					'attr'  => 'INT(10) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'description'
				),
				array(
					'name'  => 'visible',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "1"',
					'after' => 'display_order'
				),
			)
		));

		XenForo_Model::create('Siropu_AdsManager_Model_Tools')->insertPositions();

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_packages`(
				`package_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`type` ENUM('code','banner','text','link','sticky','keyword','featured') COLLATE utf8_unicode_ci NOT NULL,
				`positions` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`item_id` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`description` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`cost_amount` DECIMAL(12,2) UNSIGNED NOT NULL,
				`cost_list` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`cost_currency` VARCHAR(3) COLLATE utf8_unicode_ci NOT NULL,
				`cost_per` ENUM('Day','Week','Month','Year','CPM','CPC') COLLATE utf8_unicode_ci NOT NULL,
				`min_purchase` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`max_purchase` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`discount` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`advertise_here` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`style` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`max_items_allowed` TINYINT(2) UNSIGNED NOT NULL DEFAULT '3',
				`max_items_display` TINYINT(2) UNSIGNED NOT NULL DEFAULT '1',
				`ads_order` ENUM('random','dateAsc','dateDesc','orderAsc','orderDesc','ctrAsc','ctrDesc') COLLATE utf8_unicode_ci NOT NULL,
				`count_ad_views` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`count_ad_clicks` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`daily_stats` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`click_stats` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`nofollow` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`target_blank` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`hide_from_robots` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`js_rotator` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`js_interval` TINYINT(1) UNSIGNED NOT NULL DEFAULT '5',
				`keyword_limit` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`position_criteria` MEDIUMBLOB NOT NULL,
				`page_criteria` MEDIUMBLOB NOT NULL,
				`user_criteria` MEDIUMBLOB NOT NULL,
				`device_criteria` MEDIUMBLOB NOT NULL,
				`geoip_criteria` MEDIUMBLOB NOT NULL,
				`guidelines` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`advertiser_criteria` MEDIUMBLOB NOT NULL,
				`preview` VARCHAR(35) COLLATE utf8_unicode_ci NOT NULL,
				`display_order` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
				`enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'
				) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_ads`(
				`ad_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`package_id` INT(10) UNSIGNED NOT NULL,
				`user_id` INT(10) UNSIGNED NOT NULL,
				`username` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`type` ENUM('code','banner','text','link','sticky','keyword','featured') COLLATE utf8_unicode_ci NOT NULL,
				`positions` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`item_id` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`code` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`backup` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`url` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`banner` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`banner_extra` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`banner_url` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`description` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`items` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`purchase` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`extend` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_start` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_end` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`count_views` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`view_limit` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`count_clicks` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`click_limit` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`daily_stats` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`click_stats` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`ga_stats` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`nofollow` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`target_blank` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`hide_from_robots` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`ad_order` INT(10) UNSIGNED NOT NULL DEFAULT '1',
				`priority` INT(10) UNSIGNED NOT NULL DEFAULT '1',
				`display_after` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`hide_after` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`inherit_settings` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`is_placeholder` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`count_exclude` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`keyword_limit` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`position_criteria` MEDIUMBLOB NOT NULL,
				`page_criteria` MEDIUMBLOB NOT NULL,
				`user_criteria` MEDIUMBLOB NOT NULL,
				`device_criteria` MEDIUMBLOB NOT NULL,
				`geoip_criteria` MEDIUMBLOB NOT NULL,
				`notes` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`date_created` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_active` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_last_change` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_last_active` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`view_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`click_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`ctr` DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT '0',
				`email_notifications` INT(10) UNSIGNED NOT NULL DEFAULT '1',
				`alert_notifications` INT(10) UNSIGNED NOT NULL DEFAULT '1',
				`subscription` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`reject_reason` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`pending_transaction` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`notice_sent` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`status_old` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL,
				`status` ENUM('Active','Inactive','Pending','Approved','Queued','Rejected','Paused') COLLATE utf8_unicode_ci NOT NULL,
				INDEX `package_id` (`package_id`),
				INDEX `user_id` (`user_id`),
				INDEX `username` (`username`),
				INDEX `type` (`type`),
				INDEX `pending_transaction` (`pending_transaction`),
				INDEX `status` (`status`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_transactions`(
				`transaction_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`user_id` INT(10) UNSIGNED NOT NULL,
				`username` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`cost_amount` DECIMAL(12,2) UNSIGNED NOT NULL,
				`cost_amount_btc` DECIMAL(12,8) UNSIGNED NOT NULL DEFAULT '0',
				`cost_currency` VARCHAR(3) COLLATE utf8_unicode_ci NOT NULL,
				`discount_amount` DECIMAL(12,2) UNSIGNED NOT NULL,
				`discount_percent` FLOAT UNSIGNED NOT NULL DEFAULT '0',
				`promo_code` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`date_generated` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_completed` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`payment_method` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
				`payment_txn_id` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`download` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`status` ENUM('Pending', 'Completed','Cancelled') COLLATE utf8_unicode_ci NOT NULL,
				INDEX `user_id` (`user_id`),
				INDEX `username` (`username`),
				INDEX `payment_txn_id` (`payment_txn_id`),
				INDEX `status` (`status`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_promo_codes`(
				`code_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`code` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`description` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`value` FLOAT UNSIGNED NOT NULL DEFAULT '0',
				`type` ENUM('percent', 'amount') COLLATE utf8_unicode_ci NOT NULL,
				`packages` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`min_transaction_value` DECIMAL(5,2) UNSIGNED NOT NULL,
				`date_expire` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`usage_limit_total` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`usage_limit_user` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`user_criteria` mediumblob NOT NULL,
				`date_created` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`usage_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				UNIQUE KEY `code` (`code`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_stats_daily`(
				`id` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL PRIMARY KEY,
				`date` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`position` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
				`view_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`click_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				INDEX `date` (`date`),
				INDEX `ad_id` (`ad_id`),
				INDEX `position` (`position`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_stats_clicks`(
				`date` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`page_url` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`position` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
				`visitor_username` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`visitor_gender` ENUM('','male','female') COLLATE utf8_unicode_ci NOT NULL,
				`visitor_age` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
				`visitor_ip` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`visitor_device` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				INDEX `date` (`date`),
				INDEX `ad_id` (`ad_id`),
				INDEX `position` (`position`),
				INDEX `visitor_gender` (`visitor_gender`),
				INDEX `visitor_age` (`visitor_age`),
				INDEX `visitor_device` (`visitor_device`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::_getDb()->query("
			CREATE TABLE IF NOT EXISTS `xf_siropu_ads_manager_subscriptions`(
				`subscription_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`subscr_id` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
				`package_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`username` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`amount` DECIMAL(12,2) UNSIGNED NOT NULL,
				`currency` VARCHAR(3) COLLATE utf8_unicode_ci NOT NULL,
				`subscr_date` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`subscr_method` VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
				`last_payment_date` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`status` ENUM('Active','Inactive','Cancelled') COLLATE utf8_unicode_ci NOT NULL,
				INDEX `subscr_id` (`subscr_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
		");

		self::addColumnIfNotExists(array(
			'xf_siropu_ads_manager_packages' => array(
				array(
					'name'  => 'daily_stats',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'count_ad_clicks'
				),
				array(
					'name'  => 'click_stats',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'daily_stats'
				),
				array(
					'name'  => 'user_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'page_criteria'
				),
				array(
					'name'  => 'device_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'user_criteria'
				),
				array(
					'name'  => 'geoip_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'device_criteria'
				),
				array(
					'name'  => 'style',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'discount'
				),
				array(
					'name'  => 'keyword_limit',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'js_interval'
				),
				array(
					'name'  => 'advertiser_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'guidelines'
				),
				array(
					'name'  => 'advertise_here',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'discount'
				),
				array(
					'name'  => 'item_id',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'positions'
				),
				array(
					'name'  => 'position_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'keyword_limit'
				),
				array(
					'name'  => 'preview',
					'attr'  => 'VARCHAR(35) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'advertiser_criteria'
				),
				array(
					'name'  => 'display_order',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "1"',
					'after' => 'preview'
				),
			),
			'xf_siropu_ads_manager_ads' => array(
				array(
					'name'  => 'count_exclude',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'inherit_settings'
				),
				array(
					'name'  => 'daily_stats',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'click_limit'
				),
				array(
					'name'  => 'click_stats',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'daily_stats'
				),
				array(
					'name'  => 'ga_stats',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'click_stats'
				),
				array(
					'name'  => 'device_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'user_criteria'
				),
				array(
					'name'  => 'geoip_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'device_criteria'
				),
				array(
					'name'  => 'keyword_limit',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'count_exclude'
				),
				array(
					'name'  => 'is_placeholder',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'inherit_settings'
				),
				array(
					'name'  => 'banner_extra',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'banner'
				),
				array(
					'name'  => 'banner_url',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'banner_extra'
				),
				array(
					'name'  => 'item_id',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'positions'
				),
				array(
					'name'  => 'position_criteria',
					'attr'  => 'MEDIUMBLOB NOT NULL',
					'after' => 'keyword_limit'
				),
				array(
					'name'  => 'subscription',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'email_notifications'
				),
				array(
					'name'  => 'status_old',
					'attr'  => 'VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'pending_transaction'
				),
				array(
					'name'  => 'alert_notifications',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "1"',
					'after' => 'email_notifications'
				),
				array(
					'name'  => 'priority',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "1"',
					'after' => 'ad_order'
				),
				array(
					'name'  => 'display_after',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'priority'
				),
				array(
					'name'  => 'hide_after',
					'attr'  => 'TINYINT(1) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'display_after'
				),
				array(
					'name'  => 'notes',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'geoip_criteria'
				),
				array(
					'name'  => 'date_last_change',
					'attr'  => 'INT(10) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'date_created'
				),
				array(
					'name'  => 'backup',
					'attr'  => 'TEXT COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'code'
				)
			),
			'xf_siropu_ads_manager_transactions' => array(
				array(
					'name'  => 'promo_code',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'discount_percent'
				),
				array(
					'name'  => 'cost_amount_btc',
					'attr'  => 'DECIMAL(12,8) UNSIGNED NOT NULL DEFAULT "0"',
					'after' => 'cost_amount'
				),
				array(
					'name'  => 'download',
					'attr'  => 'VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL',
					'after' => 'payment_txn_id'
				)
			)
		));

		$oldStatsTable = self::_getDb()->fetchRow('
			SELECT TABLE_NAME
			FROM information_schema.TABLES
			WHERE TABLE_NAME = ?
			', 'xf_siropu_ads_manager_stats');

		if ($oldStatsTable)
		{
			try
			{
				self::_getDb()->query('
					DROP TABLE
					`xf_siropu_ads_manager_stats`
				');
			}
			catch (Exception $e) {}
		}

		if ($addon = XenForo_Model::create('XenForo_Model_AddOn')->getAddOnById('siropu_ads_manager'))
		{
			if ($addon['version_id'] < 97)
			{
				self::_getDb()->query("ALTER TABLE `xf_siropu_ads_manager_ads` CHANGE `type` `type` ENUM('code','banner','text','link','sticky','keyword','featured') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");

				self::_getDb()->query("ALTER TABLE `xf_siropu_ads_manager_packages` CHANGE `type` `type` ENUM('code','banner','text','link','sticky','keyword','featured') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;");
			}
		}

		if (!self::_contentTypeExists())
		{
			self::_getDb()->query("
				INSERT IGNORE INTO xf_content_type
					(content_type, addon_id, fields)
				VALUES
					('siropu_ads_manager', 'siropu_ads_manager', '')
			");

			self::_getDb()->query("
				INSERT IGNORE INTO xf_content_type_field
					(content_type, field_name, field_value)
				VALUES
					('siropu_ads_manager', 'alert_handler_class', 'Siropu_AdsManager_AlertHandler')
			");
		}

		XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
		XenForo_Application::setSimpleCacheData('adsForCache', '');
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
				`xf_siropu_ads_manager_ads`,
				`xf_siropu_ads_manager_packages`,
				`xf_siropu_ads_manager_positions`,
				`xf_siropu_ads_manager_positions_categories`,
				`xf_siropu_ads_manager_transactions`,
				`xf_siropu_ads_manager_promo_codes`,
				`xf_siropu_ads_manager_stats_daily`,
				`xf_siropu_ads_manager_stats_clicks`,
				`xf_siropu_ads_manager_subscriptions`;
		');

		if (self::_contentTypeExists())
		{
			self::_getDb()->delete('xf_content_type', 'content_type = \'siropu_ads_manager\'');
			self::_getDb()->delete('xf_content_type_field', 'content_type = \'siropu_ads_manager\'');

			XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
		}

		XenForo_Application::setSimpleCacheData('activeAdsForDisplay', '');
		XenForo_Application::setSimpleCacheData('adsForCache', '');
		XenForo_Application::setSimpleCacheData('adPositionList', '');
	}
	protected static function _contentTypeExists()
	{
		return self::_getDb()->fetchRow('
			SELECT *
			FROM xf_content_type
			WHERE content_type = ?
		', 'siropu_ads_manager');
	}
	protected static function _getDb()
	{
		return XenForo_Application::get('db');
	}
}

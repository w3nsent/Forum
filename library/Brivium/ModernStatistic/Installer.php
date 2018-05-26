<?php
class Brivium_ModernStatistic_Installer extends Brivium_BriviumHelper_Installer
{
	protected $_installerType = 1;
	protected $_modernStatistic = null;

	protected function _preInstall()
	{
		if(!empty($this->_existingAddOn['version_id']) && $this->_existingAddOn['version_id'] < 2000000){
			$options = XenForo_Application::get('options');
			$modernStatistic = array();
			$modernStatistic['title'] = 'Modern Statisitc';
			$tabList = $options->BRMS_tabsSelector;
			if(!$tabList){
				return;
			}
			foreach($tabList as &$tab){
				if(empty($tab['kind'])){
					$tab['kind'] = 'thread';
				}
			}
			$modernStatistic['tab_data']             = $tabList;
			$modernStatistic['position']             = $options->BRMS_position;
			$modernStatistic['control_position']     = $options->BRMS_navPosition;
			$modernStatistic['item_limit']           = $options->BRMS_itemLimit;
			$modernStatistic['auto_update']          = $options->BRMS_updateTime;
			$modernStatistic['style_display']        = $options->BRMS_styleDisplay;
			$modernStatistic['preview_tooltip']      = $options->BRMS_usePreviewTooltip;

			$tabCacheTime                            = $options->BRMS_tabCacheTime;
			$modernStatistic['enable_cache']         = !empty($tabCacheTime['enabled'])?1:0;
			$modernStatistic['cache_time']           = !empty($tabCacheTime['cache_time'])?$tabCacheTime['cache_time']:1;

			$modernStatistic['thread_cutoff']        = $options->BRMS_threadDateCutOff;
			$modernStatistic['usename_marke_up']     = $options->BRMS_usernameMakeUp;
			$modernStatistic['show_thread_prefix']   = $options->BRMS_showThreadPrefix;
			$modernStatistic['show_resource_prefix'] = $options->BRMS_showResourcePrefix;
			$modernStatistic['allow_change_layout']  = $options->BRMS_allowChangeLayout;
			$modernStatistic['allow_manual_refresh'] = $options->BRMS_allowRefresh;
			$modernStatistic['load_fisrt_tab']       = $options->BRMS_loadFirstTab;
			$modernCriteria = array(
				'template_name'	=> 'forum_list'
			);
			$modernStatistic['modern_criteria'] = $modernCriteria;

			$modernStatistic['active'] = 1;
			$this->_modernStatistic = $modernStatistic;
		}
	}

	protected function _postInstall()
	{
		if($this->_modernStatistic){
			$writer = XenForo_DataWriter::create('Brivium_ModernStatistic_DataWriter_ModernStatistic', XenForo_DataWriter::ERROR_SILENT);
			$writer->bulkSet($this->_modernStatistic);
			$writer->save();
		}
		$this->getModelFromCache('Brivium_ModernStatistic_Model_ModernStatistic')->rebuildModernStatisticCaches();
	}
	protected function _postUninstall()
	{
		$this->getModelFromCache('XenForo_Model_DataRegistry')->delete('brmsModernStatisticCache');
	}

	public function getTables()
	{
		$tables = array();
		$tables["xf_brivium_modern_cache"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_modern_cache` (
			  `modern_statistic_id` int(10) unsigned NOT NULL,
			  `user_id` int(10) unsigned NOT NULL,
			  `last_update` int(10) unsigned NOT NULL DEFAULT '0',
			  `item_limit` int(10) unsigned NOT NULL DEFAULT '0',
			  `cache_html` mediumtext,
			  `cache_params` mediumblob,
			  `tab_cache_htmls` mediumblob,
			  `tab_cache_params` mediumblob,
			  UNIQUE KEY `modern_statistic_id` (`modern_statistic_id`,`user_id`),
			  KEY `user_id` (`user_id`),
			  KEY `modern_statistic` (`modern_statistic_id`),
			  KEY `user_last_update` (`user_id`,`modern_statistic_id`,`last_update`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		$tables["xf_brivium_modern_statistic"] = "
			CREATE TABLE IF NOT EXISTS `xf_brivium_modern_statistic` (
			  `modern_statistic_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `title` varchar(50) NOT NULL,
			  `tab_data` mediumblob NOT NULL,
			  `position` text NOT NULL,
			  `control_position` varchar(50) NOT NULL DEFAULT '',
			  `item_limit` blob NOT NULL,
			  `auto_update` int(10) NOT NULL DEFAULT '0',
			  `style_display` varchar(100) NOT NULL DEFAULT '',
			  `preview_tooltip` varchar(30) NOT NULL DEFAULT '',
			  `enable_cache` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `cache_time` int(10) NOT NULL DEFAULT '0',
			  `thread_cutoff` int(10) unsigned NOT NULL DEFAULT '0',
			  `usename_marke_up` tinyint(3) NOT NULL DEFAULT '1',
			  `show_thread_prefix` tinyint(3) NOT NULL DEFAULT '1',
			  `show_resource_prefix` tinyint(3) NOT NULL DEFAULT '1',
			  `allow_change_layout` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  `allow_manual_refresh` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  `load_fisrt_tab` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `modern_criteria` mediumblob NOT NULL,
			  `style_settings` blob NOT NULL,
			  `allow_user_setting` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
			  PRIMARY KEY (`modern_statistic_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		";
		return $tables;
	}

	public function getAlters()
	{
		$alters = array();
		$alters['xf_brivium_modern_statistic'] = array(
			'style_settings'	=>	" BLOB NOT NULL ",
			'language_id'	=>	" INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
			'allow_user_setting'	=>	" TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT  '1'",
		);
		$alters['xf_brivium_modern_cache'] = array(
			'item_limit'	=>	" int(10) unsigned NOT NULL DEFAULT '0' ",
		);
		$alters['xf_user'] = array(
			'brms_statistic_perferences'	=>	" BLOB NULL",
		);
		$alters['xf_thread'] = array(
			'brms_promote'	=>	" TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT  '0'",
			'brms_promote_date'	=>	" INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'",
		);
		return $alters;
	}

	public function getQueryBeforeData()
	{
		$query = array();
		if($this->_existingVersionId < 2040300){
			$query[] = "
				ALTER TABLE  `xf_brivium_modern_cache`
					ADD KEY `user_id` (`user_id`),
					ADD KEY `modern_statistic` (`modern_statistic_id`),
					ADD KEY `user_last_update` (`user_id`, `modern_statistic_id`, `last_update`);
			";
		}
		$query[] = "
			ALTER TABLE  `xf_thread`
				ADD KEY `brms_promote` (`brms_promote`),
				ADD KEY `brms_thread_promote` (`brms_promote`, `brms_promote_date`);
		";
		return $query;
	}

	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'Brivium_ModernStatistics';
		";
		if($this->_triggerType != "uninstall"){
			$query[] = "
				REPLACE INTO `xf_brivium_addon`
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`)
				VALUES
					('Brivium_ModernStatistics', 'Brivium - Modern Statistics', '2060000', 0, 0, 0);
			";
			$query[] = "
				REPLACE INTO `xf_brivium_listener_class`
					(`class`, `class_extend`, `event_id`, `addon_id`)
				VALUES
					('XenForo_ControllerPublic_Account', 'Brivium_ModernStatistic_ControllerPublic_Account', 'load_class_controller', 'Brivium_ModernStatistics'),
					('XenForo_Model_User', 'Brivium_ModernStatistic_Model_User', 'load_class_model', 'Brivium_ModernStatistics'),
					('XenForo_Model_Thread', 'Brivium_ModernStatistic_Model_Thread', 'load_class_model', 'Brivium_ModernStatistics'),
					('XenForo_Model_Node', 'Brivium_ModernStatistic_Model_Node', 'load_class_model', 'Brivium_ModernStatistics'),
					('XenForo_Model_Forum', 'Brivium_ModernStatistic_Model_Forum', 'load_class_model', 'Brivium_ModernStatistics'),
					('XenForo_DataWriter_User', 'Brivium_ModernStatistic_DataWriter_User', 'load_class_datawriter', 'Brivium_ModernStatistics'),
					('XenForo_DataWriter_Discussion_Thread', 'Brivium_ModernStatistic_DataWriter_Discussion_Thread', 'load_class_datawriter', 'Brivium_ModernStatistics'),
					('XenForo_ControllerPublic_Thread', 'Brivium_ModernStatistic_ControllerPublic_Thread', 'load_class_controller', 'Brivium_ModernStatistics'),
					('XenForo_ControllerPublic_Category', 'Brivium_ModernStatistic_ControllerPublic_Category', 'load_class_controller', 'Brivium_ModernStatistics'),
					('XenResource_Model_Resource', 'Brivium_ModernStatistic_Model_Resource', 'load_class_model', 'Brivium_ModernStatistics');
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'Brivium_ModernStatistics';
			";
		}
		return $query;
	}
}

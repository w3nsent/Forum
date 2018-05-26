<?php

class phc_ACPPlus_Install extends XenForo_Model
{
    public static function Install($existingAddOn)
    {
        $db = XenForo_Application::getDb();

        // Check if old Addon "Reorder Add-On" exists
        if(self::_getInstallModel()->checkIfReorderExists())
        {
            throw new XenForo_Exception('Please remove "Reorder Add-On".', true);
        }

        // Check if old Addon "WhoVoted" exists
        if(self::_getInstallModel()->checkIfWhoVotedExists())
        {
            throw new XenForo_Exception('Please remove "Who voted" Add-On.', true);
        }

        // Check if old Addon "EMailMassBan" exists
        if(self::_getInstallModel()->checkIfEMailMassBanExists())
        {
            throw new XenForo_Exception('Please remove "MassBanEMails" Add-On.', true);
        }

        // Check if old Addon "denyByHtaccess" exists
        if(self::_getInstallModel()->checkIfDenyByHtaccessExists())
        {
            throw new XenForo_Exception('Please remove "Deny by .htaccess" Add-On.', true);
        }

        // Liam W AdminCP Firewall
        if(self::_getInstallModel()->checkIfLiamWAdminCPFirewall())
        {
            throw new XenForo_Exception('Please remove "Liam W AdminCP Firewall Add-On.', true);
        }

        $versionId = 0;
        if(isset($existingAddOn) && isset($existingAddOn['version_id']))
        {
            $versionId = $existingAddOn['version_id'];
        }

        // Check Fatal Error
        if($existingAddOn)
        {
            try{
                $db->query('RENAME TABLE phc_accp_addon_cats TO phc_acpp_addon_cats;');
            }
            catch(Exception $e) {}

            try{
                $db->query('RENAME TABLE phc_accp_logins TO phc_acpp_logins;');
            }
            catch(Exception $e) {}
        }
/*
        $db->query("
CREATE TABLE IF NOT EXISTS `phc_acpp_404log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dateline` int(10) unsigned NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `url` varchar(255) NOT NULL,
  `referrer` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `log_id` (`log_id`),
  KEY `dateline` (`dateline`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");
*/
        $db->query("
CREATE TABLE IF NOT EXISTS `phc_acpp_addon_cats` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`,`title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

");

        $db->query("
CREATE TABLE IF NOT EXISTS `phc_acpp_banned_log` (
  `bann_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dateline` int(10) unsigned NOT NULL,
  `type` enum('referrer','useragent') NOT NULL,
  `value` text NOT NULL,
  `data` text NOT NULL,
  `ip_address` varbinary(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`bann_id`),
  KEY `log_date` (`dateline`),
  KEY `content_type_id` (`type`),
  KEY `user_id_log_date` (`dateline`),
  KEY `type` (`type`),
  KEY `datline` (`dateline`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

        $db->query("
CREATE TABLE IF NOT EXISTS `phc_acpp_logins` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `data` varchar(50) NOT NULL,
  `status` enum('success','user_not_exists','password_incorrect','no_admin','ip_not_allowed') NOT NULL,
  `login` enum('front','acp') NOT NULL,
  `dateline` int(10) unsigned NOT NULL,
  `hash` varchar(60) NOT NULL,
  `ip` varchar(60) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `hash` (`hash`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `login` (`login`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

        $db->query("
CREATE TABLE IF NOT EXISTS `phc_acpp_user_import` (
  `import_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `data` mediumblob,
  `reason` enum('user_id','username','email','incorrect_username','incorrect_email','both','other') NOT NULL DEFAULT 'username',
  `import_error` mediumblob NOT NULL,
  PRIMARY KEY (`import_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ");

        $db->query("
CREATE TABLE IF NOT EXISTS `phc_acpp_spiders` (
  `spider_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `robot_id` varchar(255) NOT NULL,
  `title` tinytext,
  `contact` tinytext,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `block` tinyint(1) NOT NULL DEFAULT '0',
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `routes` longtext,
  `routes_whitelist` longtext,
  PRIMARY KEY (`spider_id`),
  UNIQUE KEY `spider_id` (`spider_id`),
  UNIQUE KEY `robot_id` (`robot_id`),
  KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
        ");

        // Import Bots
        if(!$existingAddOn || $versionId <= 1470596775)
        {
            try{
                $db->query("INSERT INTO `phc_acpp_spiders` (`robot_id`, `title`, `contact`, `active`, `last_activity`) VALUES
                                ('archive.org_bot', 'Internet Archive', 'http://www.archive.org/details/archive.org_bot', 1, 0),
                                ('baiduspider', 'Baidu', 'http://www.baidu.com/search/spider.htm', 1, 0),
                                ('bingbot', 'Bing', 'http://www.bing.com/bingbot.htm', 1, 0),
                                ('facebookexternalhit', 'Facebook', 'http://www.facebook.com/externalhit_uatext.php', 1, 0),
                                ('googlebot', 'Google', 'https://support.google.com/webmasters/answer/182072', 1, 0),
                                ('ia_archiver', 'Alexa', 'http://www.alexa.com/help/webmasters', 1, 0),
                                ('magpie-crawler', 'Brandwatch', 'http://www.brandwatch.com/how-it-works/gathering-data/', 1, 0),
                                ('mediapartners-google', 'Google AdSense', 'https://support.google.com/webmasters/answer/182072', 1, 0),
                                ('mj12bot', 'Majestic-12', 'http://majestic12.co.uk/bot.php', 1, 0),
                                ('msnbot', 'MSN', 'http://search.msn.com/msnbot.htm', 1, 0),
                                ('proximic', 'Proximic', 'http://www.proximic.com/info/spider.php', 1, 0),
                                ('sogou web spider', 'Sogou', 'http://www.sogou.com/docs/help/webmasters.htm#07', 1, 0),
                                ('scoutjet', 'Blekko', 'http://www.scoutjet.com/', 1, 0),
                                ('yahoo! slurp', 'Yahoo', 'http://help.yahoo.com/help/us/ysearch/slurp', 1, 0),
                                ('yandex', 'Yandex', 'http://help.yandex.com/search/?id=1112030', 1, 0);");
            }
            catch(Exception $e) {}

            phc_ACPPlus_Helper_ACPPlus::generateSpiderXML();
        }


        $columns = self::_getColumns('xf_option_group');
        if(!in_array('default_display_order', $columns))
        {
            $db->query("ALTER TABLE  `xf_option_group` ADD  `default_display_order` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `display_order`");
            $db->query("UPDATE xf_option_group SET default_display_order = display_order");
        }

        $columns = self::_getColumns('xf_template');
        if(!in_array('favorit', $columns))
        {
            $db->query("ALTER TABLE `xf_template` ADD `favorit` BOOLEAN NOT NULL DEFAULT FALSE");
            $db->query("ALTER TABLE `xf_template` ADD INDEX (  `favorit` )");
        }

        $columns = self::_getColumns('xf_addon');
        if(!in_array('position', $columns))
        {
            $db->query("ALTER TABLE `xf_addon` ADD `position` INT UNSIGNED NOT NULL DEFAULT  '0'");
        }

        if(!in_array('cat_id', $columns))
        {
            $db->query("ALTER TABLE `xf_addon` ADD `cat_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0'");

            if(!$db->fetchAll('SHOW KEYS FROM xf_addon WHERE Key_name=\'cat_id\''))
            {
                $db->query("ALTER TABLE `xf_addon` ADD INDEX (  `cat_id` )");
            }
        }

        if(!in_array('note', $columns))
        {
            $db->query("ALTER TABLE  `xf_addon` ADD  `note` TEXT NULL DEFAULT NULL");
        }

        if($versionId <= 19772002)
        {
            $disabledAddons = $db->fetchAll('SELECT * FROM  `xf_addon` WHERE  `active` = 0');

            if($disabledAddons)
            {
                foreach($disabledAddons as $disabledAddon)
                {
                    $db->query('UPDATE xf_addon SET position = ' . (10000 + $disabledAddon['position']) . ' WHERE addon_id = \'' . $disabledAddon['addon_id'] . '\'');
                }
            }
        }

        // DB Falsche Charsets!
        if($versionId && $versionId <= 1470596740)
        {
            $db->query("ALTER TABLE  `phc_acpp_logins` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
            $db->query("ALTER TABLE  `phc_acpp_logins` CHANGE  `data`  `data` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
        }

        //Add Error FullText index
        if(!$db->fetchAll('SHOW KEYS FROM xf_error_log WHERE Key_name=\'message\''))
        {
            try{
                $db->query('ALTER TABLE  `xf_error_log` ADD FULLTEXT (`message`)');
            }
            catch(Exception $e) {}
        }

        if(!$db->fetchAll('SHOW KEYS FROM xf_error_log WHERE Key_name=\'exception_type\''))
        {
            try{

                $db->query('ALTER TABLE  `xf_error_log` ADD INDEX (  `exception_type` )');
            }
            catch(Exception $e) {}
        }
    }

    public static function Uninstall()
    {
        $db = XenForo_Application::get('db');
        //$db->query("ALTER TABLE `xf_addon` DROP `position`");
    }

    private static function _getColumns($table)
    {
        $db = XenForo_Application::get('db');
        $query = $db->query("SHOW COLUMNS FROM $table");
        $columns = array();

        while($record = $query->fetch())
        {
            $columns[] = $record['Field'];
        }

        return $columns;
    }

    /**
     * @return phc_ACPPlus_Model_Install
     */
    protected static function _getInstallModel()
    {
        return XenForo_Model::create('phc_ACPPlus_Model_Install');
    }

    /**
     * @return XenForo_Model_Cron
     */
    protected static function _getCronModel()
    {
        return XenForo_Model::create('XenForo_Model_Cron');
    }
}
<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_Listener
{
	private static $chatSession   = array();
	private static $chatSettings  = array();
	private static $chatNoSession = false;

	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Template_Helper_Core::$helperCallbacks += array(
            'siropu_chat_room_last_message_id' => array('Siropu_Chat_HelperTemplate', 'helperRoomLastMessageId')
        );
	}
	public static function load_class_controller($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_SpamCleaner':
				$extend[] = 'Siropu_Chat_ControllerPublic_Extend_SpamCleaner';
				break;
		}
	}
	public static function load_class_datawriter($class, &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_User':
				$extend[] = 'Siropu_Chat_DataWriter_Extend_User';
				break;
			case 'XenForo_DataWriter_Discussion_Thread':
				$extend[] = 'Siropu_Chat_DataWriter_Extend_Thread';
				break;
			case 'XenForo_DataWriter_DiscussionMessage_Post':
				$extend[] = 'Siropu_Chat_DataWriter_Extend_Post';
				break;
		}
	}
	public static function load_class_model($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_Cron':
				$extend[] = 'Siropu_Chat_Model_Extend_Cron';
				break;
		}
	}
	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'PAGE_CONTAINER')
        {
            $template->preloadTemplate('siropu_chat');
			$template->preloadTemplate('siropu_chat_disabled');
        }
	}
	public static function navigation_tabs(array &$extraTabs, $selectedTabId)
	{
		$options   = XenForo_Application::get('options');
		$chatPage  = $options->siropu_chat_page;
		$userCount = $options->siropu_chat_display_tab_chatters_count;

		if (isset($_POST['_xfResponseType']) && $_POST['_xfResponseType'] == 'json')
		{
			$userCount = false;
		}

		if ($options->siropu_chat_enabled
			&& $chatPage['enabled']
			&& $chatPage['position']
			&& (Siropu_Chat_Helper::userHasPermission('view') || Siropu_Chat_Helper::userHasPermission('use')))
		{
			$extraTabs['chat'] = array(
				'href'          => XenForo_Link::buildPublicLink('chat'),
				'title'         => new XenForo_Phrase('siropu_chat'),
				'position'      => $chatPage['position'],
				'selected'      => ($selectedTabId == 'chat') ? true : false,
				'linksTemplate' => 'siropu_chat_tab_links',
				'counter'       => $userCount ? self::_getModel()->getActiveUsersCount() : 0
			);
		}
	}
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$hookList = array(
			'ad_above_content',
			'ad_below_content',
			'forum_list_nodes',
			'ad_sidebar_below_visitor_panel',
			'ad_sidebar_bottom',
			'footer'
		);

		if (in_array($hookName, $hookList) && self::_getOptions()->siropu_chat_enabled)
		{
			$displayMode    = self::_getOptions()->siropu_chat_display_mode;
			$templateParams = $template->getParams();

			if ($hookName == self::_getOptions()->siropu_chat_users_widget_position
				&& $templateParams['controllerName'] == 'XenForo_ControllerPublic_Forum'
				&& $displayMode == 'chat'
				&& self::_getOptions()->siropu_chat_users_widget_enabled
				&& Siropu_Chat_Helper::userHasPermission('view'))
			{
				$viewParams = array(
					'chatUsers' => self::_getModel()->getActiveUsersWidget()
				);

				$contents .= $template->create('siropu_chat_users_widget', array_merge($viewParams, $templateParams));
			}

			if ((Siropu_Chat_Helper::userHasPermission('view') || Siropu_Chat_Helper::userHasPermission('use'))
				&& $templateParams['controllerName'] != 'Siropu_Chat_ControllerPublic_Chat')
			{
				$userId = self::_getVisitor()->user_id;

				if ($userId && !self::$chatSession && !self::$chatNoSession)
				{
					self::$chatSession  = self::_getModel()->getSession($userId);
					self::$chatSettings = Siropu_Chat_Helper::prepareUserSettings(self::$chatSession);

					if (!self::$chatSession)
					{
						self::$chatNoSession = true;
					}
				}

				if (isset(self::$chatSettings['display_mode'])
					&& ($userDisplayMode = self::$chatSettings['display_mode'])
					&& Siropu_Chat_Helper::userHasPermission('chooseDisplayMode'))
				{
					$displayMode = $userDisplayMode == 'default' && $displayMode != 'embed' ? $displayMode : $userDisplayMode;
				}

				if ($displayMode == 'above_content' && $hookName == 'ad_above_content')
				{
					$contents = self::_getChat($template, $displayMode) . $contents;
				}
				else if ($displayMode == 'below_content' && $hookName == 'ad_below_content')
				{
					$contents .= self::_getChat($template, $displayMode);
				}
				else if ($displayMode == 'above_forums_list' && $hookName == 'forum_list_nodes')
				{
					$contents = self::_getChat($template, $displayMode) . $contents;
				}
				else if ($displayMode == 'below_forums_list' && $hookName == 'forum_list_nodes')
				{
					$contents .= self::_getChat($template, $displayMode);
				}
				else if ($displayMode == 'sidebar_below_visitor_panel' && $hookName == 'ad_sidebar_below_visitor_panel')
				{
					$contents .= self::_getChat($template, $displayMode);
				}
				else if ($displayMode == 'sidebar_bottom' && $hookName == 'ad_sidebar_bottom')
				{
					$contents .= self::_getChat($template, $displayMode);
				}
				else if ($displayMode == 'all' && $hookName == 'footer')
				{
					$contents .= self::_getChat($template);
				}
			}
		}
	}
	protected static function _getChat($template, $displayMode = 'all')
	{
		$userID   = self::_getVisitor()->user_id;
		$session  = self::$chatSession;
		$settings = self::$chatSettings;
		$settings = $settings ? $settings : self::_getOptions()->siropu_chat_default_user_settings;
		$userBans = array();

		if (!empty($settings['disabled']))
		{
			return $template->create('siropu_chat_disabled',
				array_merge($template->getParams(), array('displayMode' => $displayMode)));
		}

		if (!empty($session['user_is_banned']) || !empty($session['user_is_muted']))
		{
			if ($userBans = self::_getModel()->getAllUserBans($userID))
			{
				foreach ($userBans as $ban)
				{
					if ($ban['ban_room_id'] == -1 && !self::_getOptions()->siropu_chat_banned_view_access)
					{
						return false;
					}
				}
			}
			else
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
				$dw->setExistingData($userID);
				$dw->set('user_is_banned', 0);
				$dw->set('user_is_muted', 0);
				$dw->save();

				$session['user_is_banned'] = 0;
			}
		}

		$chatUsers = array();

		if (self::_getOptions()->siropu_chat_user_list_enabled
			&& self::_getOptions()->siropu_chat_user_list_refresh_interval)
		{
			$chatUsers = self::_getModel()->getActiveUsers(Siropu_Chat_Helper::getIgnoredUsers());
		}

		$rooms     = self::_getModel()->getAllRooms();
		$userRooms = Siropu_Chat_Helper::getUserRooms($session, null, $rooms);

		$viewParams = array(
			'chatClass'         => Siropu_Chat_Helper::getChatClass($settings),
			'chatSidebar'       => preg_match('/sidebar_/', $displayMode) ? true : false,
			'chatMode'          => $displayMode,
			'chatSession'       => $session,
			'chatSettings'      => $settings,
			'chatRoomId'        => Siropu_Chat_Helper::getRoomId($session),
			'chatUserRooms'     => $userRooms,
			'chatMessages'      => array(),
			'chatForumActivity' => array(),
			'chatLastRow'       => array(),
			'chatReports'       => self::_getModel()->getReportsCount(array('report_state' => 'open')),
			'chatUserBans'      => Siropu_Chat_Helper::prepareUserBans($userBans, $session),
			'chatRooms'         => $rooms,
			'chatUsers'         => Siropu_Chat_Helper::getChatRoomUsers($chatUsers),
			'chatUsersCount'    => Siropu_Chat_Helper::getChatRoomUsersCount($chatUsers),
			'chatColors'        => Siropu_Chat_Helper::prepareColorList(),
			'chatNotice'        => Siropu_Chat_Helper::getNotices(),
			'chatAds'           => Siropu_Chat_Helper::getAds(),
			'chatDBBCodes'      => Siropu_Chat_Helper::getDisallowedBBCodes(),
			'chatResponses'     => Siropu_Chat_Helper::prepareResponses(self::_getModel()->getResponseList(true))
		);

		return $template->create('siropu_chat', array_merge($viewParams, $template->getParams()));
	}
	protected static function _getVisitor()
	{
		return XenForo_Visitor::getInstance();
	}
	protected static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected static function _getModel()
	{
		return XenForo_Model::create('Siropu_Chat_Model');
	}
}
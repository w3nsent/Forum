<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_Helper
{
	public static function userHasPermission($permission)
	{
		$permissions = XenForo_Visitor::getInstance()->getPermissions();
		return XenForo_Permission::hasPermission($permissions, 'siropu_chat', $permission);
	}
	public static function userCanJoinMultipleRooms()
	{
		if (self::_getOptions()->siropu_chat_rooms_enabled
			&& self::userHasPermission('joinRooms')
			&& (self::userHasPermission('joinMultipleRooms') == -1
				|| self::userHasPermission('joinMultipleRooms') > 1))
		{
			return true;
		}
	}
	public static function prepareChatMessages(&$resultArray, $ignored, $inverse, $imagesAsLinks, $bbCodeParser, &$playSound, &$lastRow, $archive = false)
	{
		$userId       = XenForo_Visitor::getInstance()->user_id;
		$ignored      = array_filter($ignored);
		$viewWhispers = self::userHasPermission('viewWhispers');
		$readWhispers = self::userHasPermission('readWhispers');
		$displayAsBot = self::_getOptions()->siropu_chat_display_commands_by_bot;
		$roomMessages = array();

		if ($inverse)
		{
			krsort($resultArray);
		}
		else if (!$archive)
		{
			ksort($resultArray);
		}

		foreach ($resultArray as $message)
		{
			$roomId        = $message['message_room_id'];
			$messageType   = $message['message_type'];
			$recipients    = unserialize($message['message_recipients']);
			$whisperPolice = false;

			if (in_array($messageType, array('bot', 'activity')))
			{
				$playSound = 'bot';
			}

			if ($recipients && !isset($recipients[$userId]) && $viewWhispers)
			{
				$recipients[$userId] = $whisperPolice = true;
				unset($recipients[$message['user_id']]);

				if (!$readWhispers)
				{
					$message['message_text'] = '';
				}
			}

			if (($ignored && isset($ignored[$message['user_id']])) || ($recipients && !isset($recipients[$userId])))
			{
				if ($archive)
				{
					$message['message_text'] = '';

					if ($recipients)
					{
						$recipients = array(new XenForo_Phrase('siropu_chat_whisper_archive'));
					}
				}
				else
				{
					$message = null;
				}
			}

			if ($message)
			{
				$text = $message['message_text'];

				if ($imagesAsLinks)
				{
					$text = preg_replace('/\[IMG\](.*?)\[\/IMG\]/i', '[URL]$1[/URL]', $text);
				}

				$message['messageHtml'] = $bbCodeParser->render($text);

				$tagged = unserialize($message['message_tagged']);
				$message['message_tagged'] = $tagged ? $tagged : array();

				if (isset($tagged[$userId]))
				{
					$playSound = 'tagged';
				}

				if ($recipients)
				{
					unset($recipients[$userId]);
					$message['message_recipients'] = $recipients;
					$playSound = 'whisper';
				}

				switch ($messageType)
				{
					case 'bot':
						$message['class'] = 'siropuChatBot';
						break;
					case 'activity':
						$message['class'] = 'siropuChatForumActivityBot';
						break;
					case 'me':
						$message['class'] = 'siropuChatMe';
						break;
					case 'quit':
						$message['class'] = 'siropuChatQuit';
						break;
					case 'whisper':
						$message['class'] = 'siropuChatWhisper' . ($whisperPolice ? 'Police' : '');
						break;
				}

				$class = !empty($message['class']) ? $message['class'] : '';

				if ($message['is_admin'])
				{
					$message['class'] = $class . ' siropuChatAdmin';
				}
				else if ($message['is_moderator'])
				{
					$message['class'] = $class . ' siropuChatModerator';
				}
				else if ($message['is_staff'])
				{
					$message['class'] = $class . ' siropuChatStaff';
				}

				if (in_array($messageType, array('me', 'quit')) && !$displayAsBot)
				{
					$message['hideAuthor'] = true;
				}

				if ($inverse && !$lastRow || !$inverse)
				{
					$lastRow = $message;
				}

				if ($archive)
				{
					$roomMessages[] = $message;
				}
				else
				{
					$roomMessages[$roomId][$message['message_id']] = $message;
				}
			}
		}

		if (isset($message) && !$inverse)
		{
			$lastRow = $message;
		}

		$resultArray = $roomMessages;
	}
	public static function getUserRooms($session = array(), $roomId = null, $rooms = array())
	{
		if (!self::userCanJoinMultipleRooms())
		{
			return array(self::getRoomId($session) => 0);
		}

		if (!empty($session['user_rooms']))
		{
			$userRooms = @unserialize($session['user_rooms']);

			if (!$userRooms && self::_getOptions()->siropu_chat_general_room_default)
			{
				$userRooms = array(0);
			}
		}
		else if ($defaultRooms = self::_getOptions()->siropu_chat_default_joined_rooms['ids'])
		{
			$userRooms = array_flip($defaultRooms);

			if ($rooms)
			{
				foreach ($userRooms as $key => $val)
				{
					if (!empty($rooms[$key]['room_permissions']) && !self::checkRoomPermissions($rooms[$key]))
					{
						unset($userRooms[$key]);
					}
				}
			}
		}
		else
		{
			$userRooms = array(0);
		}

		if ($userRooms && $rooms)
		{
			foreach ($userRooms as $key => $val)
			{
				if (!isset($rooms[$key]))
				{
					unset($userRooms[$key]);
				}
			}
		}

		if ($roomId !== null)
		{
			unset($userRooms[$roomId]);
		}

		return $userRooms;
	}
	public function getUserLastRoomId($userRooms)
	{
		$roomIds = array_keys($userRooms);
		return $roomIds ? end($roomIds) : 0;
	}
	public static function prepareUserRoomsForSave($session = array(), $roomId, $rooms = array())
	{
		$userRooms = self::getUserRooms($session, null, $rooms);
		$userRooms[$roomId] = time();
		return serialize($userRooms);
	}
	public static function getRoomId($session)
	{
		if (self::_getOptions()->siropu_chat_rooms_enabled && isset($session['user_room_id']))
		{
			return $session['user_room_id'];
		}

		return 0;
	}
	public static function getChatClass($settings, $forceMode = false)
	{
		$displayMode = self::_getOptions()->siropu_chat_display_mode;

		if (!empty($settings['display_mode']))
		{
			$displayMode = $settings['display_mode'];
		}

		$class = array();

		switch ($forceMode)
		{
			case 'page':
				$displayMode = 'chat';
				break;
			case 'embed':
				$displayMode = 'embed';
				break;
		}

		switch ($displayMode)
		{
			case 'all':
				$class[] = 'siropuChatAllPages';
				break;
			case 'above_content':
				$class[] = 'siropuChatAboveContent';
				break;
			case 'below_content':
				$class[] = 'siropuChatBelowContent';
				break;
			case 'above_forums_list':
			case 'below_forums_list':
				$class[] = 'siropuChatForumsList';
				break;
			case 'sidebar_below_visitor_panel':
			case 'sidebar_bottom':
				$class[] = 'siropuChatSidebar';
				break;
			case 'chat':
				$class[] = 'siropuChatPage';
				if (self::_getOptions()->siropu_chat_sidebar_enabled)
				{
					$class[] = 'siropuChatPageSidebar';
				}
				break;
			case 'embed':
				$class[] = 'siropuChatEmbedded';
				break;
		}

		if (!empty($settings['maximized']))
		{
			$class[] = 'siropuChatMaximized';
		}
		if (!empty($settings['hide_chatters']) || !self::_getOptions()->siropu_chat_user_list_enabled)
		{
			$class[] = 'siropuChatNoUsers';
		}

		if ($class)
		{
			return implode(' ', $class);
		}
	}
	public static function getChatDisplayMode($settings)
	{
		$displayMode = self::_getOptions()->siropu_chat_display_mode;

		if (!empty($settings['display_mode']) && self::userHasPermission('chooseDisplayMode'))
		{
			$displayMode = $settings['display_mode'];
		}

		return $displayMode;
	}
	public static function getChatRoomUsers($users)
	{
		unset($users['count']);
		return $users;
	}
	public static function getChatRoomUsersCount($users)
	{
		return isset($users['count']) ? $users['count'] : 0;
	}
	public static function setSessionSettings($session = array(), $settings)
	{
		if ($session)
		{
			$session = unserialize($session['user_settings']);
		}

		foreach ($settings as $key => $val)
		{
			$session[$key] = $val;
		}

		return serialize($session);
	}
	public static function getNotices($data = array())
	{
		if ($notices = array_filter(explode('<--NOTICE-->', ($data ? $data : self::_getOptions()->siropu_chat_notices))))
		{
			shuffle($notices);
			return $notices[0];
		}
	}
	public static function prepareAds($ads)
	{
		return array_filter(explode('<--AD-->', $ads));
	}
	public static function getAds()
	{
		$ads = array();

		if (!self::userHasPermission('viewAds'))
		{
			return $ads;
		}

		if ($aboveMessages = self::prepareAds(self::_getOptions()->siropu_chat_ads_above_messages))
		{
			shuffle($aboveMessages);
			$ads['aboveMessages'] = $aboveMessages[0];
		}
		if ($belowMessages = self::prepareAds(self::_getOptions()->siropu_chat_ads_below_messages))
		{
			shuffle($belowMessages);
			$ads['belowMessages'] = $belowMessages[0];
		}
		if ($belowEditor = self::prepareAds(self::_getOptions()->siropu_chat_ads_below_editor))
		{
			shuffle($belowEditor);
			$ads['belowEditor'] = $belowEditor[0];
		}
		if ($belowVisitorPanel = self::prepareAds(self::_getOptions()->siropu_chat_ads_below_visitor_panel))
		{
			shuffle($belowVisitorPanel);
			$ads['belowVisitorPanel'] = $belowVisitorPanel[0];
		}

		return $ads;
	}
	public static function getLastRow($messages, $data)
	{
		$lastMessage = array();

		if ($messages)
		{
			$lastMessage = $data['inverse'] ? current($messages) : end($messages);
		}

		return $lastMessage;
	}
	public static function prepareUserSettings($session)
	{
		if ($session)
		{
			return unserialize($session['user_settings']);
		}

		return array();
	}
	public static function prepareColorList()
	{
		$list = array();
		foreach (XenForo_Helper_Color::$colors as $key => $val)
		{
			$list[$val] = ucfirst($key);
		}
		return $list;
	}
	public static function checkForSelectedForums($forumId)
	{
		$forums = self::_getOptions()->siropu_chat_forum_activity_select['selected'];

		if (empty($forums) || in_array($forumId, $forums))
		{
			return true;
		}
	}
	public static function checkRoomPermissions($room, $user = array())
	{
		if ($room['room_id'] && ($permissions = unserialize($room['room_permissions'])) && $permissions['in_group_ids'])
		{
			$user       = $user ? $user : XenForo_Visitor::getInstance()->toArray();
			$userGroups = explode(',', $user['secondary_group_ids']);
			array_unshift($userGroups, $user['user_group_id']);
			$inGroup    = false;

			foreach ($userGroups as $id)
			{
				if (in_array($id, $permissions['in_group_ids']))
				{
					$inGroup = true;
				}
			}

			return $inGroup;
		}

		return true;
	}
	public static function getDisallowedBBCodes()
	{
		return array_map('trim', array_filter(explode("\n", strtoupper(self::_getOptions()->siropu_chat_disallowed_bbcodes))));
	}
	public static function stripDisallowedBBCodes($message)
	{
		$standardBBCodes = array(
			'B'       => array('B' => '$1'),
			'I'       => array('I' => '$1'),
			'U'       => array('U' => '$1'),
			'S'       => array('S' => '$1'),
			'URL'     => array('URL' => '$1', 'URL\=(.+?)' => '$1'),
			'IMG'     => array('IMG' => '$1'),
			'FONT'    => array('FONT\=(.+?)' => '$2'),
			'SIZE'    => array('SIZE\=(.+?)' => '$2'),
			'COLOR'   => array('COLOR\=(.+?)' => '$2'),
			'MEDIA'   => array('MEDIA\=(.+?)' => false),
			'QUOTE'   => array('(QUOTE|QUOTE\=(.+?))' => false),
			'SPOILER' => array('(SPOILER|SPOILER\=(.+?))' => false),
			'CODE'    => array('(CODE|CODE\=(.+?))' => false),
			'PHP'     => array('PHP' => false),
			'HTML'    => array('HTML' => false)
		);

		if ($disallowed = self::getDisallowedBBCodes())
		{
			$message = XenForo_Helper_String::autoLinkBbCode($message, in_array('MEDIA', $disallowed) ? false : true);

			foreach ($disallowed as $BBCode)
			{
				if (!isset($standardBBCodes[$BBCode]))
				{
					$standardBBCodes[$BBCode] = array("({$BBCode}|{$BBCode}=(.+?))" => false);
				}

				foreach ($standardBBCodes[$BBCode] as $tag => $content)
				{
					$regex = '/\[' . $tag . '\](.+?)\[\/' . $BBCode . '\]/i';

					if (preg_match($regex, $message))
					{
						$message = preg_replace($regex, ($content ? $content : new XenForo_Phrase('siropu_chat_bbcode_disallowed', array('name' => $BBCode))), $message);
					}
				}
			}

			return $message;
		}

		return XenForo_Helper_String::autoLinkBbCode($message);
	}
	public static function sortBansByRoomId($bans)
	{
		$list = array();

		foreach ($bans as $ban)
		{
			$list[$ban['ban_room_id']][] = $ban;
		}

		return $list;
	}
	public static function prepareUserBans($bans, $session)
	{
		if ($bans)
		{
			$banType = array(
				'chat' => '',
				'room' => '',
				'kick' => ''
			);

			foreach ($bans as $ban)
			{
				if ($ban['ban_room_id'] == -1)
				{
					$banType['chat'] = $ban;
				}
				else if ($session && $session['user_room_id'] == $ban['ban_room_id'])
				{
					if ($ban['ban_type'] == 'kick')
					{
						$banType['kick'] = $ban;
					}
					else if ($ban['ban_type'] == 'ban')
					{
						$banType['room'] = $ban;
					}
				}
			}

			return $banType;
		}

		return $bans;
	}
	public static function getIgnoredUsers($showIgnored = false)
	{
		if ($showIgnored)
		{
			return array();
		}

		$visitor = XenForo_Visitor::getInstance();
		return (array) @unserialize($visitor['ignored']);
	}
	public static function getMessageColor($message, $settings)
	{
		if (self::_getOptions()->siropu_chat_colored_messages_enabled
			&& self::userHasPermission('useColor')
			&& !empty($settings['color'])
			&& !preg_match('/\[COLOR\=/i', $message))
		{
			$color = $settings['color'];

			if (preg_match('/(.*?)\[quote(.+?)\](.*)\[\/quote\](.*?)$/i', $message, $matches))
			{
				$message = '';

				if (!empty($matches[1]))
				{
					$message = "[COLOR=#{$color}]{$matches[1]}[/COLOR]";
				}

				$message .= "[quote{$matches[2]}]{$matches[3]}[/quote]";

				if (!empty($matches[4]))
				{
					$message .= "[COLOR=#{$color}]{$matches[4]}[/COLOR]";
				}

				return $message;
			}
			return "[COLOR=#{$settings['color']}]{$message}[/COLOR]";
		}

		return $message;
	}
	public static function prepareTaggedUsers($tagged)
	{
		$users = array();

		foreach ($tagged as $user)
		{
			$users[$user['user_id']] = $user['username'];
		}

		return serialize($users);
	}
	public static function prioritySort($a, $b)
	{
		$aSettings = @unserialize($a['response_settings']);
		$bSettings = @unserialize($b['response_settings']);

		if (@$aSettings['priority'] == @$bSettings['priority'])
		{
			return 0;
		}

		return (@$aSettings['priority'] > @$bSettings['priority']) ? -1 : 1;
	}
	public static function prepareResponses($resultArray = array())
	{
		usort($resultArray, 'self::prioritySort');

		foreach ($resultArray as $key => $val)
		{
			$keyword  = addslashes(strtolower($val['response_keyword']));
			$rooms    = @unserialize($val['response_rooms']);
			$groups   = @unserialize($val['response_user_groups']);
			$settings = @unserialize($val['response_settings']);
			$keywords = array();

			if (!empty($settings['multi_keywords']))
			{
				$keywords = array_map('trim', explode(self::_getOptions()->siropu_chat_bot_keyword_separator, $keyword));
			}
			else
			{
				$keywords[] = $keyword;
			}

			$resultArray[$key]['response_keyword'] = $keywords;
			$resultArray[$key]['response_rooms']   = json_encode(is_array($rooms) ? $rooms : array());

			if (!empty($groups))
			{
				$userGroups = explode(',', self::_getVisitor()->secondary_group_ids);
				array_unshift($userGroups, self::_getVisitor()->user_group_id);

				if (!array_intersect($groups, $userGroups))
				{
					unset($resultArray[$key]);
				}
			}
		}

		return $resultArray;
	}
	public static function isSmilieLimitReached($message, $smilieLimit)
	{
		$smilieCount = 0;
		$smilieList  = array();

		foreach (XenForo_Model::create('XenForo_Model_Smilie')->getAllSmilies() as $smilie)
		{
			$smilieText = preg_split('/\r?\n/', $smilie['smilie_text'], -1, PREG_SPLIT_NO_EMPTY);

			foreach ($smilieText AS $text)
			{
				$smilieList[$text] = "\0" . $smilie['smilie_id'] . "\0";
			}
		}

		$smilieCount += preg_match_all("#\\0(\d+)\\0#", strtr($message, $smilieList), $null);

		if ($smilieCount > $smilieLimit)
		{
			return true;
		}
	}
	public static function refreshRoomsCache()
	{
		XenForo_Application::setSimpleCacheData('chatRooms', '');
		XenForo_Model::create('Siropu_Chat_Model')->getAllRooms();
	}
	public static function resetTopChatters($unsetResetDate = false)
	{
		foreach (array('all', 'today', 'yesterday', 'thisWeek', 'thisMonth', 'lastWeek', 'lastMonth') as $type)
		{
			XenForo_Application::setSimpleCacheData('chatTop' . $type, '');
			XenForo_Application::setSimpleCacheData('chatTopLastUpdate' . $type, '');
		}

		if ($unsetResetDate)
		{
			XenForo_Application::setSimpleCacheData('chatTopResetDate', '');
		}
		else
		{
			XenForo_Application::setSimpleCacheData('chatTopResetDate', time());
		}
	}
	protected static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected static function _getVisitor()
	{
		return XenForo_Visitor::getInstance();
	}
}
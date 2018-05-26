<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

abstract class Siropu_Chat_Callback
{
	public static $chatLoaded = false;

	public static function renderMultiSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
		$forumOpt = XenForo_Option_NodeChooser::getNodeOptions(false, false, 'Forum');
		$forumLst = $preparedOption['option_value']['selected'];
		$forumLst = is_array($forumLst) ? array_flip($forumLst) : $forumLst;
		$selected = array();

		foreach ($forumOpt as $key => $val)
		{
			if (isset($forumLst[$key]))
			{
				$forumOpt[$key]['selected'] = true;
			}
		}

		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		return $view->createTemplateObject('siropu_chat_option_template_forum_multiselect', array(
			'fieldPrefix'     => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption'  => $preparedOption,
			'formatParams'    => $forumOpt,
			'editLink'        => $editLink
		));
	}
	public static function renderRoomSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption'          => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		$model = XenForo_Model::create('Siropu_Chat_Model');
		$rooms = array();

		foreach ($model->getAllRooms() as $room)
		{
			if (empty($room['room_password']))
			{
				$rooms[$room['room_id']] = $room['room_name'];
			}
		}

		return $view->createTemplateObject('siropu_chat_option_template_room_multiselect', array(
			'fieldPrefix'     => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption'  => $preparedOption,
			'formatParams'    => $rooms,
			'editLink'        => $editLink
		));
	}
	public static function getChat($content, $params, XenForo_Template_Abstract $template)
    {
		$options        = XenForo_Application::get('options');
		$templateParams = $template->getParams();

		if ($options->siropu_chat_enabled
			&& $options->siropu_chat_display_mode == 'embed'
			&& Siropu_Chat_Helper::userHasPermission('view')
			&& $templateParams['controllerName'] != 'Siropu_Chat_ControllerPublic_Chat'
			&& !self::$chatLoaded)
		{
			$visitor = XenForo_Visitor::getInstance();
			$model   = XenForo_Model::create('Siropu_Chat_Model');

			$userId       = $visitor->user_id;
			$session      = $model->getSession($userId);
			$settings     = Siropu_Chat_Helper::prepareUserSettings($session);
			$settings     = $settings ? $settings : $options->siropu_chat_default_user_settings;
			$roomId       = isset($params['room_id']) ? $params['room_id'] : 0;
			$forceRoom    = isset($params['force_room']) ? $params['force_room'] : 0;
			$usersRoom    = $usersAll = $userBans = array();
			$changeRoom   = $options->siropu_chat_embed_rooms_enabled;
			$rooms        = $model->getAllRooms();
			$userRooms    = Siropu_Chat_Helper::getUserRooms($session, null, $rooms);
			$joinMultiple = Siropu_Chat_Helper::userCanJoinMultipleRooms();

			if (!empty($settings['disabled']))
			{
				return $template->create('siropu_chat_disabled', array('displayMode' => 'embed'));
			}

			if ($session)
			{
				if (isset($settings['display_mode'])
					&& $settings['display_mode']
					&& $settings['display_mode'] != 'default'
					&& Siropu_Chat_Helper::userHasPermission('chooseDisplayMode'))
				{
					return false;
				}

				if ($session['user_is_banned'])
				{
					if ($userBans = $model->getAllUserBans($userId))
					{
						$chatBan = false;

						foreach ($userBans as $ban)
						{
							if ($ban['ban_room_id'] == -1)
							{
								$chatBan = true;
								break;
							}
						}

						if (!$options->siropu_chat_banned_view_access && $chatBan)
						{
							return false;
						}
					}
					else
					{
						$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
						$dw->setExistingData($userId);
						$dw->set('user_is_banned', 0);
						$dw->set('user_is_muted', 0);
						$dw->save();

						$session['user_is_banned'] = 0;
					}
				}

				if ($changeRoom && !$forceRoom)
				{
					$roomId = Siropu_Chat_Helper::getRoomId($session);
				}
			}

			if ($userId && (!$session || $session['user_room_id'] != $roomId))
			{
				$userRooms[$roomId] = time();

				$dwData = array(
					'user_id'            => $userId,
					'user_room_id'       => $roomId,
					'user_rooms'         => serialize($userRooms),
					'user_last_activity' => 0
				);

				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
				if ($session)
				{
					$dw->setExistingData($userId);
				}
				$dw->bulkSet($dwData);
				$dw->save();
			}

			$chatUsers = array();

			if ($options->siropu_chat_user_list_enabled && $options->siropu_chat_user_list_refresh_interval)
			{
				$chatUsers = $model->getActiveUsers(Siropu_Chat_Helper::getIgnoredUsers());
			}

			$viewParams = array(
				'chatClass'         => Siropu_Chat_Helper::getChatClass($settings, 'embed'),
				'chatMode'          => 'embed',
				'chatSidebar'       => isset($params['sidebar']),
				'chatSession'       => $session,
				'chatSettings'      => $settings,
				'chatRoomId'        => $roomId,
				'chatUserRooms'     => $userRooms,
				'chatMessages'      => array(),
				'chatLastRow'       => array(),
				'chatForumActivity' => array(),
				'chatReports'       => $model->getReportsCount(array('report_state' => 'open')),
				'chatUserBans'      => Siropu_Chat_Helper::prepareUserBans($userBans, $session),
				'chatRooms'         => $rooms,
				'chatUsers'         => Siropu_Chat_Helper::getChatRoomUsers($chatUsers),
				'chatUsersCount'    => Siropu_Chat_Helper::getChatRoomUsersCount($chatUsers),
				'chatColors'        => Siropu_Chat_Helper::prepareColorList(),
				'chatNotice'        => Siropu_Chat_Helper::getNotices(),
				'chatAds'           => Siropu_Chat_Helper::getAds(),
				'chatDBBCodes'      => Siropu_Chat_Helper::getDisallowedBBCodes(),
				'chatResponses'     => Siropu_Chat_Helper::prepareResponses($model->getResponseList(true))
			);

			return $template->create('siropu_chat', array_merge($viewParams, $templateParams));
			self::$chatLoaded = true;
		}
	}
	public static function getUserList($content, $params, XenForo_Template_Abstract $template)
	{
		$viewParams = array(
			'chatUsers' => XenForo_Model::create('Siropu_Chat_Model')->getActiveUsersWidget()
		);

		return $template->create('siropu_chat_users_widget',
			array_merge($viewParams, $template->getParams()));
	}
}
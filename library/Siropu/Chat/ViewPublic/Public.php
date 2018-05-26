<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ViewPublic_Public extends XenForo_ViewPublic_Base
{
	private $playSound = '';
	private $lastRow   = array();

	public function renderHtml()
    {
        Siropu_Chat_Helper::prepareChatMessages(
			$this->_params['chatMessages'],
			$this->_params['chatIgnored'],
			$this->_params['chatInverse'],
			$this->_params['chatImagesAsLinks'],
			$this->_bbCodeParser(),
			$this->playSound,
			$this->lastRow,
			isset($this->_params['chatArchive'])
		);

		$this->_params['chatLastRow'] = $this->lastRow;

		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages(
			$this->_params['chatForumActivity'],
			$this->_bbCodeParser(),
			array('messageKey' => 'message_text')
		);
    }
	public function renderJson()
	{
        Siropu_Chat_Helper::prepareChatMessages(
			$this->_params['chatMessages'],
			$this->_params['chatIgnored'],
			$this->_params['chatInverse'],
			$this->_params['chatImagesAsLinks'],
			$this->_bbCodeParser(),
			$this->playSound,
			$this->lastRow
		);

		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages(
			$this->_params['chatForumActivity'],
			$this->_bbCodeParser(),
			array('messageKey' => 'message_text')
		);

		$messages = $this->_params['chatMessages'];
		$activity = $this->_params['chatForumActivity'];
		$users    = $this->_params['users'];
		$data     = $this->_params['data'];

		$roomMessages = $roomUsers = $roomUsersInfo = array();
		$disallowedBBCodes = Siropu_Chat_Helper::getDisallowedBBCodes();
		$options = XenForo_Application::get('options');
		$userCount = isset($users['count']) ? $users['count'] : 0;
		unset($users['count']);

		$this->preloadTemplate('siropu_chat_messages');
		$this->preloadTemplate('siropu_chat_forum_activity');
		$this->preloadTemplate('siropu_chat_users');
		$this->preloadTemplate('siropu_chat_last_row');

		foreach ($data['user_rooms'] as $roomId => $lastId)
		{
			$roomMessages[$roomId] = isset($messages[$roomId]) ? $this->createTemplateObject('siropu_chat_messages', array(
				'chatMessages' => $messages[$roomId],
				'chatDBBCodes' => $disallowedBBCodes
			))->render() : '';

			$roomUsers[$roomId] = $data['usersRefresh'] ? $this->createTemplateObject('siropu_chat_users', array(
				'chatUsers'    => isset($users[$roomId]) ? $users[$roomId]['data'] : '',
				'chatSettings' => $this->_params['chatSettings']
			))->render() : '';

			$roomUsersInfo[$roomId] = array(
				'count' => isset($users[$roomId]) ? count($users[$roomId]['list']) : 0,
				'list'  => isset($users[$roomId]) ? implode(', ', $users[$roomId]['list']) : ''
			);
		}

		$forumActivity = $activity ? $this->createTemplateObject('siropu_chat_forum_activity', array(
			'chatForumActivity' => $activity
		))->render() : array();

		$roomLastMessage = $notification = array();

		if ($this->lastRow)
		{
			$roomLastMessage = $data['all_pages'] ? $this->createTemplateObject('siropu_chat_last_row',
				array('chatLastRow' => $this->lastRow))->render() : array();

			if ($options->siropu_chat_desktop_notifications)
			{
				$messageTagged = $this->lastRow['message_tagged'];
				$messageType   = $this->lastRow['message_type'];

				if (!empty($messageTagged) && isset($messageTagged[$data['userId']]))
				{
					$messageType = 'tagged';
				}

				if (in_array($messageType, array('chat', 'me', 'quit')))
				{
					$messageType = 'normal';
				}

				$notification = array(
					'type'    => $messageType,
					'message' => XenForo_Helper_String::bbCodeStrip($this->lastRow['message_text']),
					'icon'    => XenForo_Template_Helper_Core::callHelper('avatar', array($this->lastRow, 'l'))
				);
			}
		}

		return Xenforo_ViewRenderer_Json::jsonEncodeForOutput(array(
			'messages'         => $roomMessages,
			'forumActivity'    => $forumActivity,
			'users'            => $roomUsers,
			'info'             => $roomUsersInfo,
			'lastRowMessage'   => $roomLastMessage,
			'lastRowRoomId'    => $this->lastRow ? $this->lastRow['message_room_id'] : '',
			'lastRowMessageId' => $this->lastRow ? $this->lastRow['message_id'] : '',
			'lastRowUserId'    => $this->lastRow ? $this->lastRow['user_id'] : '',
			'lastRowUsername'  => $this->lastRow ? $this->lastRow['username'] : '',
			'lastId'           => $this->lastRow ? $this->lastRow['message_id'] : 0,
			'lastFakeId'       => !empty($data['lastFakeId']) ? $data['lastFakeId'] : 0,
			'userCount'        => $userCount,
			'messageActions'   => $data['action'] == 'refresh' ? Siropu_Chat_HelperActions::getMessageActions() : '',
			'prune'            => isset($data['prune']) ? $data['prune'] : '',
			'kick'             => isset($data['kick']) ? $data['kick'] : '',
			'mute'             => isset($data['mute']) ? $data['mute'] : '',
			'action'           => $data['action'],
			'roomId'           => $data['room_id'],
			'playSound'        => $this->playSound,
			'notice'           => Siropu_Chat_Helper::getNotices(),
			'activeSession'    => $data['activeSession'],
			'sessionUpdate'    => isset($data['sessionUpdate']) ? $data['sessionUpdate'] : false,
			'lastActive'       => $data['lastActive'],
			'notification'     => $notification,
			'lastUpdate'       => time()
		));
	}
	protected function _bbCodeParser()
	{
		return XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
	}
}
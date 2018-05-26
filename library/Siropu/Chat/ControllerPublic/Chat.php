<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ControllerPublic_Chat extends XenForo_ControllerPublic_Abstract
{
	protected $userId   = 0;
	protected $roomId   = 0;
	protected $session  = array();
	protected $settings = array();

	protected function _preDispatch($action)
	{
		if (!$this->_getOptions()->siropu_chat_enabled || !$this->_getHelper()->userHasPermission('view'))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('do_not_have_permission')));
		}

		$this->userId       = $this->_getVisitor()->user_id;
		$this->username     = $this->_getVisitor()->username;
		$this->roomId       = $this->_input->filterSingle('room_id', XenForo_Input::UINT);
		$this->joinMultiple = $this->_getHelper()->userCanJoinMultipleRooms();

		if ($this->userId)
		{
			$this->session = $this->_getModel()->getSession($this->userId);
		}

		$this->settings = $this->_getHelper()->prepareUserSettings($this->session);
		$this->settings = $this->settings ? $this->settings : $this->_getOptions()->siropu_chat_default_user_settings;

		if ($action == 'Submit' || $action == 'Refresh')
		{
			$userRoomsAjax    = $this->_input->filterSingle('user_rooms', XenForo_Input::ARRAY_SIMPLE);
			$userRoomsSession = $this->_getHelper()->getUserRooms($this->session);

			if ($this->session)
			{
				if (empty($userRoomsSession))
				{
					throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_chat_no_room_joined')));
				}
				else if (array_diff(array_keys($userRoomsAjax), array_keys($userRoomsSession)))
				{
					throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_chat_security_error')));
				}
			}
		}
	}
	public static function getSessionActivityDetailsForList(array $activities)
    {
        return new XenForo_Phrase('siropu_chat_viewing_chat_page');
    }
	public function actionIndex($fullPage = false)
	{
		$chatPage = $this->_getOptions()->siropu_chat_page;

		if (!$chatPage['enabled'])
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_page_disabled'));
		}

		$userBans = array();

		if (!empty($this->session['user_is_banned']) || !empty($this->session['user_is_muted']))
		{
			if ($userBans = $this->_getModel()->getAllUserBans($this->userId))
			{
				$chatBan = false;

				foreach ($userBans as $ban)
				{
					if ($ban['ban_room_id'] == -1)
					{
						$chatBan = $ban;
						break;
					}
				}

				if (!$this->_getOptions()->siropu_chat_banned_view_access && $chatBan)
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_banned_message', array('reason' => $chatBan['ban_reason'] ? $chatBan['ban_reason'] : 'N/A', 'ends' => $chatBan['ban_end'] ? XenForo_Template_Helper_Core::dateTime($chatBan['ban_end']) : new XenForo_Phrase('never'))));
				}
			}
			else
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
				$dw->setExistingData($this->userId);
				$dw->set('user_is_banned', 0);
				$dw->set('user_is_muted', 0);
				$dw->save();

				$this->session['user_is_banned'] = 0;
			}
		}

		$roomId    = $this->_getHelper()->getRoomId($this->session);
		$rooms     = $this->_getModel()->getAllRooms();
		$userRooms = $this->_getHelper()->getUserRooms($this->session, null, $rooms);

		if ($this->userId
			&& $this->_getOptions()->siropu_chat_display_page_visitors
			&& (!$this->session || ($this->session['user_last_activity'] <= $this->_getUserActiveTimeFrame())))
		{
			foreach ($userRooms as $key => $val)
			{
				if ($val <= $this->_getUserActiveTimeFrame())
				{
					if ($this->_displayNotification('join'))
					{
						$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
						$dw->bulkSet(array(
							'message_user_id' => $this->userId,
							'message_room_id' => $key,
							'message_text'    => new XenForo_Phrase('siropu_chat_bot_room_join', array('name' => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false),
							'message_type'    => 'bot'
						));
						$dw->save();
					}

					$userRooms[$key] = time();
				}
			}

			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
			if ($this->session)
			{
				$dw->setExistingData($this->userId);
			}
			else
			{
				$dw->set('user_id', $this->userId);
			}
			$dw->set('user_room_id', $roomId);
			$dw->set('user_rooms', serialize($userRooms));
			$dw->set('user_last_activity', time());
			$dw->save();

			if ($this->session)
			{
				$this->session['user_last_activity'] = time();
			}
			else
			{
				$this->session = $this->_getModel()->getSession($this->userId);
			}
		}

		$inverse    = !empty($this->settings['inverse']);
		$ignored    = !empty($this->settings['show_ignored']) ? array() : $this->_getHelper()->getIgnoredUsers();
		$conditions = array('room_id' => $roomId);

		if (!empty($this->settings['hide_bot']))
		{
			$conditions['hide_bot'] = true;
		}

		$messages = array();

		if ($this->joinMultiple)
		{
			foreach ($userRooms as $key => $lastId)
			{
				$conditions['room_id'] = $key;
				$messages += $this->_getModel()->getMessages($conditions);
			}
		}
		else
		{
			$messages = $this->_getModel()->getMessages($conditions);
		}

		$chatUsers = array();

		if ($this->_getOptions()->siropu_chat_user_list_enabled)
		{
			$chatUsers = $this->_getModel()->getActiveUsers($ignored);
		}

		$sidebar           = $this->_getOptions()->siropu_chat_sidebar_enabled;
		$topChattersWidget = $this->_getOptions()->siropu_chat_top_chatters_widget;
		$topChatters       = false;

		if ($sidebar && $topChattersWidget['enabled'])
		{
			$topChatters = $this->_getModel()->getTopChatters('all', 0, 0, $topChattersWidget['limit']);
		}

		$forumActivity = array();

		if ($this->_getOptions()->siropu_chat_forum_activity_tab)
		{
			$forumActivity = $this->_getModel()->getForumActivity(0, $inverse);
		}

		$viewParams = array(
			'chatClass'         => $this->_getHelper()->getChatClass($this->settings, 'page'),
			'chatMode'          => $this->_getHelper()->getChatDisplayMode($this->settings),
			'chatSession'       => $this->session,
			'chatSettings'      => $this->settings,
			'chatRoomId'        => $roomId,
			'chatUserRooms'     => $userRooms,
			'chatMessages'      => $messages,
			'chatLastRow'       => array(),
			'chatForumActivity' => $forumActivity,
			'chatReports'       => $this->_getModel()->getReportsCount(array('report_state' => 'open')),
			'chatUserBans'      => $this->_getHelper()->prepareUserBans($userBans, $this->session),
			'chatRooms'         => $rooms,
			'chatUsers'         => $this->_getHelper()->getChatRoomUsers($chatUsers),
			'chatUsersCount'    => $this->_getHelper()->getChatRoomUsersCount($chatUsers),
			'chatIgnored'       => $ignored,
			'chatInverse'       => $inverse,
			'chatImagesAsLinks' => !empty($this->settings['images_as_links']),
			'chatColors'        => $this->_getHelper()->prepareColorList(),
			'chatNotice'        => $this->_getHelper()->getNotices(),
			'chatAds'           => $this->_getHelper()->getAds(),
			'chatDBBCodes'      => $this->_getHelper()->getDisallowedBBCodes(),
			'chatResponses'     => $this->_getHelper()->prepareResponses($this->_getModel()->getResponseList(true)),
			'chatPageSidebar'   => $sidebar,
			'topChatters'       => $topChatters,
			'chatFullPage'      => $fullPage,
			'chatPage'          => true
		);

		$containerParams = array();

		if ($fullPage)
		{
			$containerParams['containerTemplate'] = 'SIROPU_CHAT_CONTAINER';
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Public', 'siropu_chat', $viewParams, $containerParams);
	}
	public function actionFullpage()
	{
		return $this->actionIndex(true);
	}
	public function actionUpload()
	{
		if (!$this->_getHelper()->userHasPermission('upload'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$viewParams = array(
			'maxSize' => $this->_getOptions()->siropu_chat_max_upload_file_size * 1024,
		);

		$maxUploads     = $this->_getHelper()->userHasPermission('maxUploads');
		$currentUploads = $this->_getImagesModel()->getUserImageCount($this->userId);
		$files          = XenForo_Upload::getUploadedFiles('upload');

		if ($maxUploads >= 1 && (count($files) > $maxUploads || (count($files) + $currentUploads) > $maxUploads))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_maximum_uploads_error',
				array('count' => $maxUploads)));
		}

		if ($files)
		{
			foreach ($files as $file)
			{
				$file->setConstraints(array(
					'size'       => $viewParams['maxSize'],
					'extensions' => array('gif', 'jpg', 'jpe', 'jpeg', 'png')
				));

				if (!$file->isValid())
				{
					return $this->responseError($file->getErrors());
				}

				if ($image = $this->_getHelperUpload()->doUpload($file, $this->userId))
				{
					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Images');
					$dw->bulkSet(array(
						'image_user_id' => $this->userId,
						'image_name'    => $image
					));
					$dw->save();
				}
			}

			return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
		}

		return $this->responseView('', 'siropu_chat_upload_image', $viewParams);
	}
	public function actionGetImages()
	{
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);

		$viewParams = array(
			'uploads'   => $this->_getImagesModel()->getImagesByUserId($this->userId, $page),
			'imagePath' => $this->_getHelperUpload()->getImagePath('url')
		);

		return $this->responseView('Siropu_Chat_ViewPublic_Media', 'siropu_chat_image_items', $viewParams);
	}
	public function actionDeleteImages()
	{
		$ids        = $this->_input->filterSingle('selected', XenForo_Input::ARRAY_SIMPLE, array('uint' => true));
		$viewParams = array('deleted' => '');

		if ($images = $this->_getImagesModel()->getUserImagesByIds($ids, $this->userId))
		{
			foreach ($images as $image)
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Images');
				$dw->setExistingData($image['image_id']);
				$dw->delete();

				$viewParams['deleted'][] = $image['image_id'];
			}
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', $viewParams);
	}
	public function actionRoomsGet()
	{
		$this->_assertPostOnly();

		if (!$this->_getHelper()->userHasPermission('joinRooms'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$rooms = $this->_getModel()->getAllRooms();

		if (count($rooms) > 1)
		{
			$userRoomBans = array();

			if (!empty($this->session['user_is_banned'])
				&& ($bans = $this->_getModel()->getUserBansAndKicks($this->userId)))
			{
				foreach ($bans as $ban)
				{
					$userRoomBans[$ban['ban_room_id']] = $ban;
				}
			}

			foreach ($rooms as $key => $val)
			{
				if ($this->_getHelper()->checkRoomPermissions($val))
				{
					$rooms[$key]['hasPermission'] = true;
				}

				if ($val['room_id'] && $val['room_user_id'] == $this->userId)
				{
					$rooms[$key]['isRoomAuthor'] = true;
				}

				$roomId = isset($userRoomBans[$val['room_id']]) ? $val['room_id'] : -1;

				if (isset($userRoomBans[$roomId]))
				{
					$rooms[$key]['isBanned'] = $userRoomBans[$roomId]['ban_type'];
				}
			}
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array(
			'rooms' => new XenForo_Template_Public('siropu_chat_rooms', array(
				'chatRooms'      => $rooms,
				'chatUsers'      => $this->_getModel()->getActiveUsers(),
				'chatSession'    => $this->session,
				'chatUserRooms'  => $this->_getHelper()->getUserRooms($this->session),
				'chatMultiRooms' => $this->joinMultiple,
				'visitor'        => $this->_getVisitor()->toArray(),
				'xenOptions'     => $this->_getOptions()->getOptions()
			))
		));
	}
	public function actionRoomsJoin()
	{
		$this->_assertPostOnly();

		if (!$this->_getHelper()->userHasPermission('joinRooms'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		if ($bannedMessage = $this->_getBannedMessage($this->session, $this->roomId))
		{
			return $this->responseError($bannedMessage);
		}

		$password = $this->_input->filterSingle('password', XenForo_Input::STRING);
		$rooms    = $this->_getModel()->getAllRooms();

		if ($this->roomId)
		{
			if (isset($rooms[$this->roomId]))
			{
				$room = $rooms[$this->roomId];

				if ($room['room_password'] && $room['room_password'] != $password
					&& $room['room_user_id'] != $this->userId
					&& !$this->_getHelper()->userHasPermission('bypassPassword'))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_room_invalid_password'));
				}

				if (!$this->_getHelper()->checkRoomPermissions($room))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_room_no_permission'));
				}
			}
			else
			{
				return $this->responseError(new XenForo_Phrase('siropu_chat_room_not_found'));
			}
		}

		$userRooms = $this->_getHelper()->getUserRooms($this->session);

		if ($this->joinMultiple && count($userRooms) == $this->_getHelper()->userHasPermission('joinMultipleRooms'))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_rooms_join_limit_reached',
				array('limit' => count($userRooms))));
		}

		$user = array('name' => '[USER=' . $this->userId . ']' . $this->username . '[/USER]');

		if ($this->_displayNotification('join'))
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
			$dw->bulkSet(array(
				'message_user_id' => $this->userId,
				'message_room_id' => $this->roomId,
				'message_text'    => new XenForo_Phrase('siropu_chat_bot_room_join', $user, false),
				'message_type'    => 'bot'
			));
			$dw->save();
		}

		if ($this->_displayNotification('left')
			&& $this->session
			&& $this->session['user_room_id'] != $this->roomId
			&& !$this->joinMultiple)
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
			$dw->bulkSet(array(
				'message_user_id' => $this->userId,
				'message_room_id' => $this->session['user_room_id'],
				'message_text'    => new XenForo_Phrase('siropu_chat_bot_room_left', $user, false),
				'message_type'    => 'bot'
			));
			$dw->save();
		}

		if ($this->userId)
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
			if ($this->session)
			{
				$dw->setExistingData($this->userId);
			}
			else
			{
				$dw->set('user_id', $this->userId);
			}
			$dw->set('user_room_id', $this->roomId);
			$dw->set('user_rooms', $this->_getHelper()->prepareUserRoomsForSave($this->session, $this->roomId, $rooms));
			$dw->set('user_last_activity', time());
			$dw->save();
		}

		return $this->_getChat(array('action' => 'join'));
	}
	public function actionRoomsLeave()
	{
		$this->_assertPostOnly();

		if (!$this->session)
		{
			return $this->responseError('');
		}

		$userRooms = $this->_getHelper()->getUserRooms($this->session, $this->roomId);

		$dwData = array(
			'user_room_id' => $this->_getHelper()->getUserLastRoomId($userRooms),
			'user_rooms'   => serialize($userRooms)
		);

		if (empty($userRooms) || (count($userRooms) == 1 && (!end($userRooms) || end($userRooms) && end($userRooms) <= $this->_getUserActiveTimeFrame())))
		{
			$dwData['user_last_activity'] = 0;
		}

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
		$dw->setExistingData($this->userId);
		$dw->bulkSet($dwData);
		$dw->save();

		if ($this->_displayNotification('left'))
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
			$dw->bulkSet(array(
				'message_user_id' => $this->userId,
				'message_room_id' => $this->roomId,
				'message_text'    => new XenForo_Phrase('siropu_chat_bot_room_left', array('name' => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false),
				'message_type'    => 'bot'
			));
			$dw->save();
		}
		
		return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
	}
	public function actionRoomsAdd()
	{
		$addRooms  = $this->_getHelper()->userHasPermission('addRooms');
		$roomCount = $this->_getModel()->getUserRoomCount($this->userId);

		if ($addRooms == 0) 
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}
		else if ($addRooms == $roomCount)
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_room_add_limit_reached',
				array('count' => $addRooms)));
		}
		else if ($this->_getModel()->isBanned($this->userId))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_room_add_banned'));
		}

		$viewParams = array(
			'userGroups' => $this->getModelFromCache('XenForo_Model_UserGroup')->getAllUserGroups()
		);

		return $this->responseView('', 'siropu_chat_room_edit', $viewParams);
	}
	public function actionRoomsEdit()
	{
		if (!$room = $this->_getModel()->getRoomById($this->roomId))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_room_not_found'));
		}

		if (($room['room_user_id'] == $this->userId
			&& $this->_getHelper()->userHasPermission('addRooms'))
			|| $this->_getHelper()->userHasPermission('editRooms'))
		{
			$viewParams = array(
				'room'        => $room,
				'user'        => $this->_getUserModel()->getUserById($room['room_user_id']),
				'permissions' => unserialize($room['room_permissions']),
				'userGroups'  => $this->getModelFromCache('XenForo_Model_UserGroup')->getAllUserGroups()
			);

			return $this->responseView('', 'siropu_chat_room_edit', $viewParams);
		}

		return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
	}
	public function actionRoomsSave()
	{
		$this->_assertPostOnly();

		$room = $this->_getModel()->getRoomById($this->roomId);

		if (!$this->_getHelper()->userHasPermission('addRooms')
			&& ($room && $room['room_user_id'] != $this->userId)
			&& !$this->_getHelper()->userHasPermission('editRooms'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$data = $this->_input->filter(array(
			'room_name'        => XenForo_Input::STRING,
			'room_description' => XenForo_Input::STRING,
			'room_password'    => XenForo_Input::STRING,
			'room_permissions' => XenForo_Input::ARRAY_SIMPLE,
			'room_locked'      => XenForo_Input::UINT
		));

		if (!$data['room_name'])
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_room_name_is_required'));
		}

		if ($data['room_password'] && !$this->_getHelper()->userHasPermission('passwordRooms'))
		{
			$data['room_password'] = '';
		}

		if ($data['room_permissions'] && !$this->_getVisitor()->is_admin)
		{
			$data['room_permissions'] = array();
		}

		if ($this->_getVisitor()->is_admin)
		{
			$data['room_auto_delete'] = $this->_input->filterSingle('room_auto_delete', XenForo_Input::UINT);
		}

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Rooms');
		if ($this->roomId)
		{
			$dw->setExistingData($this->roomId);
		}
		else
		{
			$data['room_user_id'] = $this->userId;
		}
		$dw->bulkSet($data);
		$dw->save();

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array('roomAdded' => true));
	}
	public function actionRoomsDelete()
	{
		if (!$room = $this->_getModel()->getRoomById($this->roomId))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_room_not_found'));
		}

		if ($room['room_user_id'] != $this->userId && !$this->_getHelper()->userHasPermission('deleteRooms'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Rooms');
			$dw->setExistingData($this->roomId);
			$dw->delete();

			$this->_getModel()->changeUsersRoomId($this->roomId);
			$this->_getModel()->deleteUsersRoomBan($this->roomId);
			$this->_getModel()->deleteMessagesByRoomId($this->roomId);

			return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array('roomId' => $this->roomId));
		}

		$viewParams = array(
			'room' => $room,
			'user' => $this->_getUserModel()->getUserById($room['room_user_id']),
		);

		return $this->responseView('', 'siropu_chat_room_delete', $viewParams);
	}
	public function actionTop()
	{
		$topChattersOptions = $this->_getOptions()->siropu_chat_top_chatters;

		if (!$topChattersOptions['enabled'])
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$action = $this->_input->filterSingle('action', XenForo_Input::STRING);
		$search = $this->_input->filterSingle('search', XenForo_Input::STRING);

		if ($action == 'reset' && $this->_getVisitor()->is_admin)
		{
			if ($this->isConfirmedPost())
			{
				$this->_getModel()->resetTopChatters();
				$this->_getHelper()->resetTopChatters();

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('chat/top')
				);
			}

			return $this->responseView('', 'siropu_chat_top_chatters_reset');
		}

		switch ($search)
		{
			case 'today':
				$start = strtotime('-' . date('G') . ' Hours');
				$end   = time();
				break;
			case 'yesterday':
				$start = strtotime('-1 Day 00:00');
				$end   = strtotime('-1 Day 23:59');
				break;
			case 'thisWeek':
				$start = strtotime('This Week Monday');
				$end   = time();
				break;
			case 'thisMonth':
				$start = strtotime('first day of this month 00:00');
				$end   = time();
				break;
			case 'lastWeek':
				$start = strtotime('-1 Week Last Monday');
				$end   = strtotime('-1 Week Sunday 23:59');
				break;
			case 'lastMonth':
				$start = strtotime('first day of last month 00:00');
				$end   = strtotime('last day of last month 23:59');
				break;
			default:
				$start = 0;
				$end   = 0;
				break;
		}

		$viewParams = array(
			'topChatters' => $this->_getModel()->getTopChatters($search, $start, $end, $topChattersOptions['limit']),
			'search'      => $search
		);

		return $this->responseView('', 'siropu_chat_top_chatters', $viewParams);
	}
	public function actionArchive()
	{
		if (!$this->_getHelper()->userHasPermission('viewArchive'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$data = $this->_input->filter(array(
			'message_id'     => XenForo_Input::UINT,
			'room_id'        => XenForo_Input::STRING,
			'keywords'       => XenForo_Input::STRING,
			'username'       => XenForo_Input::STRING,
			'user_id'        => XenForo_Input::STRING,
			'date_start'     => XenForo_Input::DATE_TIME,
			'date_end'       => XenForo_Input::DATE_TIME,
			'order'          => XenForo_Input::STRING,
			'page'           => XenForo_Input::UINT,
			'ignored'        => XenForo_Input::UINT,
			'forum_activity' => XenForo_Input::UINT
		));

		$data['room_id'] = ($data['room_id'] != '') ? $data['room_id'] : 'any';

		$linkParams = $rooms = array();
		$conditions = array('room_id' => 0);

		if ($data['message_id'])
		{
			$message = $this->_getModel()->getMessageById($data['message_id']);
			$conditions['message_id'] = $linkParams['message_id'] = $data['message_id'];
			$conditions['message_type'] = $message ? $message['message_type'] : null;
		}

		if ($this->_getHelper()->userHasPermission('searchArchive'))
		{
			$userId    = $data['user_id'];
			$dateStart = $data['date_start'];
			$dateEnd   = $data['date_end'];

			if ($this->_getOptions()->siropu_chat_rooms_enabled)
			{
				$rooms = $this->_getModel()->getAllRooms();

				if ($this->session)
				{
					$userRoomId = $this->session['user_room_id'];
				}
				else
				{
					$userRoomId = 0;
				}

				foreach ($rooms as $key => $val)
				{
					if (!$this->_getHelper()->checkRoomPermissions($val)
						|| ($key
							&& $val['room_password']
							&& $val['room_user_id'] != $this->userId
							&& $val['room_id'] != $userRoomId
							&& !$this->_getHelper()->userHasPermission('bypassPassword')))
					{
						unset($rooms[$key]);
					}
				}

				if ($data['room_id'] == 'any' && ($this->_getVisitor()->is_admin || $this->_getVisitor()->is_moderator))
				{
					$roomId = 'any';
				}
				else
				{
					$roomId = isset($rooms[$data['room_id']]) ? $rooms[$data['room_id']]['room_id'] : 0;
				}

				$linkParams['room_id'] = $conditions['room_id'] = $roomId;
			}

			if ($data['keywords'])
			{
				$linkParams['keywords'] = $conditions['keywords'] = $data['keywords'];
			}

			if ($username = array_filter(explode(',', $data['username'])))
			{
				if (!$userId)
				{
					$userIds = array();

					if ($users = $this->_getUserModel()->getUsersByNames($username))
					{
						foreach ($users as $user)
						{
							$userIds[] = $user['user_id'];
						}
					}

					if ($userIds)
					{
						$userId = implode(',', $userIds);
					}
				}
			}
			if ($userId)
			{
				$linkParams['user_id'] = $conditions['user_id'] = $userId;
			}
			if ($dateStart)
			{
				$linkParams['date_start'] = $conditions['date_start'] = $dateStart;

				if (!$dateEnd)
				{
					$dateEnd = time();
				}
			}
			if ($dateEnd)
			{
				$linkParams['date_end'] = $conditions['date_end'] = $dateEnd;
			}
			if ($data['ignored'])
			{
				$linkParams['ignored'] = true;
			}
			if ($data['forum_activity'])
			{
				$linkParams['forum_activity'] = $conditions['forum_activity'] = true;
			}
		}

		if ($data['order'])
		{
			XenForo_Helper_Cookie::setCookie('chatArchiveOrder', $data['order'], 86400 * 365);
		}
		else if ($orderCookie = XenForo_Helper_Cookie::getCookie('chatArchiveOrder'))
		{
			$data['order'] = $orderCookie;
		}

		$conditions['order'] = $data['order'];

		$page        = max(1, $data['page']);
		$results     = $this->_getModel()->getMessages($conditions, array('page' => $data['page'], 'perPage' => 50), true);
		$startOffset = ($page - 1) * 50 + 1;
		$endOffset   = ($page - 1) * 50 + count($results);

		$viewParams = array(
			'rooms'             => $rooms,
			'chatMessages'      => $results,
			'chatArchive'       => true,
			'chatForumActivity' => array(),
			'chatLastRow'       => array(),
			'chatIgnored'       => $data['ignored'] ? array() : $this->_getHelper()->getIgnoredUsers(),
			'chatInverse'       => false,
			'chatImagesAsLinks' => !empty($this->settings['images_as_links']),
			'search'            => $data,
			'linkParams'        => $linkParams,
			'total'             => $this->_getModel()->getMessagesCount($conditions),
			'resultStartOffset' => $startOffset,
			'resultEndOffset'   => $endOffset,
			'page'              => $data['page'],
			'perPage'           => 50,
		);

		return $this->responseView('Siropu_Chat_ViewPublic_Public', 'siropu_chat_archive', $viewParams);
	}
	public function actionReports()
	{
		if (!$this->_getHelper()->userHasPermission('manageReports'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$data = $this->_input->filter(array(
			'report_id'         => XenForo_Input::UINT,
			'report_message_id' => XenForo_Input::UINT,
			'report_user_id'    => XenForo_Input::UINT,
			'report_state'      => XenForo_Input::STRING,
			'save'              => XenForo_Input::STRING,
			'page'              => XenForo_Input::UINT,
		));

		if ($data['report_id'] && ($report = $this->_getModel()->getReportByIdComplete($data['report_id'])))
		{
			if ($data['save'])
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Reports');
				$dw->setExistingData($data['report_id']);
				$dw->bulkSet(array(
					'report_state'           => $data['report_state'],
					'report_update_date'     => time(),
					'report_update_user_id'  => $this->userId,
					'report_update_username' => $this->username,
				));
				$dw->save();

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('chat/reports')
				);
			}

			$viewParams = array(
				'report'         => $report,
				'messagesBefore' => $this->_getModel()->getMessagesBefore($report)
			);

			return $this->responseView('Siropu_Chat_ViewPublic_Report', 'siropu_chat_report_view', $viewParams);
		}

		if (!$data['report_state'])
		{
			$data['report_state'] = 'open';
		}

		$conditions = $linkParams = array();
		$conditions['report_state'] = $linkParams['report_state'] = $data['report_state'];

		$viewParams = array(
			'chatReports' => $this->_getModel()->getReports($conditions, array('page' => $data['page'], 'perPage' => 25)),
			'state'       => $data['report_state'],
			'linkParams'  => $linkParams,
			'total'       => $this->_getModel()->getReportsCount($conditions),
			'page'        => $data['page'],
			'perPage'     => 25,
		);

		return $this->responseView('', 'siropu_chat_reports', $viewParams);
	}
	public function actionReportsDelete()
	{
		if (!$this->_getHelper()->userHasPermission('manageReports'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$reportId = $this->_input->filterSingle('report_id', XenForo_Input::UINT);

		if (!$report = $this->_getModel()->getReportByIdComplete($reportId))
		{
			return $this->responseError(new XenForo_Phrase('requested_report_not_found'));
		}

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Reports');
			$dw->setExistingData($reportId);
			$dw->delete();

			return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array(
				'reportId' => $reportId
			));
		}

		$viewParams = array(
			'report' => $report
		);

		return $this->responseView('', 'siropu_chat_report_delete', $viewParams);
	}
	public function actionBan()
	{
		if (!$this->_getHelper()->userHasPermission('ban'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$input = $this->_input->filter(array(
			'user_id'       => XenForo_Input::UINT,
			'username'      => XenForo_Input::STRING,
			'room_id'       => XenForo_Input::UINT,
			'room_ids'      => XenForo_Input::ARRAY_SIMPLE,
			'ban_type'      => XenForo_Input::UINT,
			'length_type'   => XenForo_Input::STRING,
			'length_value'  => XenForo_Input::UINT,
			'length_option' => XenForo_Input::STRING,
			'end_date'      => XenForo_Input::DATE_TIME
		));

		$data = $this->_input->filter(array(
			'ban_reason'  => XenForo_Input::STRING,
			'ban_end'     => XenForo_Input::UINT
		));

		$data['ban_type']   = 'ban';
		$data['ban_author'] = $this->userId;

		if ($this->isConfirmedPost())
		{
			if ($input['length_type'] == 'temporary')
			{
				if ($length = $input['length_value'])
				{
					$data['ban_end'] = strtotime("+{$length} {$input['length_option']}");
				}
				else if ($endDate = $input['end_date'])
				{
					$data['ban_end'] = $endDate;
				}

				if (!$data['ban_end'])
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_ban_length_required'));
				}
			}

			if ($input['user_id'])
			{
				$users = $this->_getUserModel()->getUsersByIds(array($input['user_id']));
			}
			else
			{
				$users = $this->_getUserModel()->getUsersByNames(array_map('trim', explode(',', $input['username'])));
			}

			if ($users)
			{
				$exclude = array();

				foreach ($users as $user)
				{
					$userSession = $this->_getModel()->getSession($user['user_id']);

					if (!$userSession || $user['is_admin'] || $user['is_moderator'])
					{
						$exclude[] = $user['username'];
						continue;
					}

					if (empty($input['room_ids']))
					{
						$input['room_ids'][] = $input['room_id'];
					}

					$userBans  = $this->_getModel()->getUserBans($user['user_id']);
					$userRooms = $this->_getHelper()->getUserRooms($userSession);

					if ($input['ban_type'] == 2)
					{
						if ($userBans && $input['length_type'] == 'permanent')
						{
							$this->_getModel()->deleteUserBans($user['user_id']);
						}

						$userRooms = array();
					}

					$data['ban_user_id'] = $user['user_id'];

					foreach ($input['room_ids'] as $roomId)
					{
						$data['ban_room_id'] = $input['ban_type'] == 2 ? -1 : $roomId;

						$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Bans');
						if (isset($userBans[$roomId]))
						{
							$dw->setExistingData($userBans[$roomId]['ban_id']);
						}
						$dw->bulkSet($data);
						$dw->save();

						unset($userRooms[$roomId]);
					}

					$dwData = array(
						'user_room_id'   => $this->_getHelper()->getUserLastRoomId($userRooms),
						'user_rooms'     => serialize($userRooms),
						'user_is_banned' => 1,
					);

					if (!$this->joinMultiple
						|| (empty($userRooms)
							|| count($userRooms) == 1
							&& !end($userRooms)))
					{
						$dwData['user_last_activity'] = 0;
					}

					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
					$dw->setExistingData($user['user_id']);
					$dw->bulkSet($dwData);
					$dw->save();

					if ($this->_displayNotification('ban'))
					{
						$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
						$dw->bulkSet(array(
							'message_user_id' => $user['user_id'],
							'message_room_id' => $userSession['user_room_id'],
							'message_text'    => new XenForo_Phrase(($input['ban_type'] == 2 ? 'siropu_chat_bot_user_banned' : 'siropu_chat_bot_user_room_banned'), array('user' => '[USER=' . $user['user_id'] . ']' . $user['username'] . '[/USER]', 'mod'  => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false),
							'message_type'    => 'bot'
						));
						$dw->save();
					}
				}

				if ($exclude)
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_cannot_be_banned',
						array('users' => implode(', ', $exclude))));
				}
				else if (empty($input['username']))
				{
					return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array(
						'userId' => $user['user_id'],
						'roomId' => $input['room_id']
					));
				}
				else
				{
					return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('chat/banned')
					);
				}
			}
			else
			{
				return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
			}
		}

		$viewParams = array(
			'roomId' => $input['room_id'],
			'rooms'  => $this->_getModel()->getAllRooms(),
			'room'   => $this->_getModel()->getRoomById($input['room_id']),
			'user'   => $this->_getUserModel()->getUserById($input['user_id'])
		);

		return $this->responseView('', ($input['user_id'] ? 'siropu_chat_ban' : 'siropu_chat_ban_alt'), $viewParams);
	}
	public function actionBanned()
	{
		if (!$this->_getHelper()->userHasPermission('viewBanned'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$data = $this->_input->filter(array(
			'username' => XenForo_Input::STRING,
			'user_id'  => XenForo_Input::STRING,
			'type'     => XenForo_Input::STRING,
			'page'     => XenForo_Input::UINT,
		));

		$conditions = $linkParams = array();
		$userId     = $data['user_id'];
		$type       = $data['type'];

		if ($username = array_filter(explode(',', $data['username'])))
		{
			if (!$userId)
			{
				$userIds = array();

				if ($users = $this->_getUserModel()->getUsersByNames($username))
				{
					foreach ($users as $user)
					{
						$userIds[] = $user['user_id'];
					}
				}

				if ($userIds)
				{
					$userId = implode(',', $userIds);
				}
			}
		}

		if ($userId)
		{
			$linkParams['user_id'] = $conditions['user_id'] = $userId;
		}

		if ($type)
		{
			$linkParams['ban_type'] = $conditions['ban_type'] = $type;
		}

		$viewParams = array(
			'chatBanned' => $this->_getModel()->getBannedUsers($conditions, array('page' => $data['page'], 'perPage' => 50)),
			'linkParams' => $linkParams,
			'total'      => $this->_getModel()->getBannedUsersCount(),
			'page'       => $data['page'],
			'search'     => $data,
			'perPage'    => 50,
		);

		return $this->responseView('', 'siropu_chat_banned', $viewParams);
	}
	public function actionSettings()
	{
		$this->_assertPostOnly();

		if (!$this->userId)
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_settings_guest_info'));
		}

		$data = $this->_input->filter(array(
			'sound'            => XenForo_Input::ARRAY_SIMPLE,
			'notification'     => XenForo_Input::ARRAY_SIMPLE,
			'maximized'        => XenForo_Input::UINT,
			'inverse'          => XenForo_Input::UINT,
			'editor_on_top'    => XenForo_Input::UINT,
			'hide_bot'         => XenForo_Input::UINT,
			'hide_status'      => XenForo_Input::UINT,
			'hide_chatters'    => XenForo_Input::UINT,
			'show_ignored'     => XenForo_Input::UINT,
			'images_as_links'  => XenForo_Input::UINT,
			'disable_autohide' => XenForo_Input::UINT,
			'display_mode'     => XenForo_Input::STRING,
			'color'            => XenForo_Input::STRING,
			'disabled'         => XenForo_Input::UINT
		));

		foreach (array('normal', 'whisper', 'tagged', 'bot') as $option)
		{
			if (empty($data['sound'][$option]))
			{
				$data['sound'][$option] = 0;
			}
			if (empty($data['notification'][$option]))
			{
				$data['notification'][$option] = 0;
			}
		}

		if ($data['display_mode'] && !$this->_getHelper()->userHasPermission('chooseDisplayMode'))
		{
			$data['display_mode'] = '';
		}

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
		if ($this->session)
		{
			$dw->setExistingData($this->userId);
		}
		else
		{
			$userRooms = $this->_getHelper()->getUserRooms(array(), null, $this->_getModel()->getAllRooms());

			$dw->set('user_id', $this->userId);
			$dw->set('user_rooms', @serialize($userRooms));
			$dw->set('user_last_activity', 0);
		}
		$dw->set('user_settings', $this->_getHelper()->setSessionSettings($this->session, $data));
		$dw->save();

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
	}
	public function actionStatus()
	{
		if (!$this->_getHelper()->userHasPermission('setStatus'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		if ($this->isConfirmedPost())
		{
			$this->_updateStatus();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('chat'),
				new XenForo_Phrase('siropu_chat_status_set')
			);
		}

		return $this->responseView('', 'siropu_chat_status', array('chatSession' => $this->session));
	}
	public function actionSubmit()
	{
		$this->_assertPostOnly();

		if (!$this->_getHelper()->userHasPermission('use'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$message      = $this->_input->filterSingle('message', XenForo_Input::STRING);
		$messageClean = XenForo_Helper_String::bbCodeStrip($message);
		$options      = XenForo_Application::get('options');

		if ($maxLength = $options->siropu_chat_message_max_length)
		{
			$message = XenForo_Helper_String::wholeWordTrim($message, $maxLength);
		}

		if ($options->siropu_chat_disable_all_bbcodes)
		{
			$message = $messageClean;
		}

		$emptyTags = '/^(\[IMG\]\[\/IMG\]|\[SPOILER\]\[\/SPOILER\])/i';

		if (preg_match($emptyTags, $message))
		{
			$message = preg_replace($emptyTags, '', $message);
		}

		if (strlen($messageClean))
		{
			if ($bannedMessage = $this->_getBannedMessage($this->session, $this->roomId))
			{
				return $this->responseError($bannedMessage);
			}

			if (($floodLength = $this->_getOptions()->siropu_chat_flood_check_length)
				&& !$this->_getHelper()->userHasPermission('bypassFloodCheck')
				&& $this->_getModel()->userIsFlooding($this->userId, $floodLength))
			{
				return $this->responseMessage(new XenForo_Phrase('siropu_chat_flood_error',
					array('count' => $floodLength)));
			}

			if (($smilieLimit = $options->siropu_chat_smilie_limit)
				&& $this->_getHelper()->isSmilieLimitReached($message, $smilieLimit))
			{
				return $this->responseError(new XenForo_Phrase('siropu_chat_smilie_limit',
					array('limit' => $smilieLimit)));
			}

			$data = array(
				'message_room_id' => $this->roomId,
				'message_user_id' => $this->userId,
				'message_text'    => $message,
				'message_type'    => 'chat'
			);

			if (preg_match('/^\/me\s[^ ]/i', $messageClean)
				&& $options->siropu_chat_me_command_enabled
				&& $this->_getHelper()->userHasPermission('meCommand'))
			{
				$message = '[USER=' . $this->userId . ']' . $this->username . '[/USER] ' . trim(str_ireplace('/me', '', $message));

				$data['message_text'] = $message;
				$data['message_type'] = 'me';
			}

			if (preg_match('/^\/(?:whisper|w)\s+\[(.*)\]\s+(.*)$/Ui', $message, $matches)
				&& $options->siropu_chat_whisper_command_enabled
				&& $this->_getHelper()->userHasPermission('whisperCommand'))
			{
				$usernames = array_filter(array_map('trim', array_map('strtolower', explode(',', $matches[1]))));

				if (in_array(strtolower($this->username), $usernames))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_whisper_self_error'));
				}

				if ($users = $this->_getUserModel()->getUsersByNames($usernames))
				{
					$recipients = array($this->userId => $this->username);

					foreach ($users as $user)
					{
						$recipients[$user['user_id']] = $user['username'];
					}

					$data['message_recipients'] = serialize($recipients);
					$data['message_text']       = $this->_getHelper()->getMessageColor($matches[2], $this->settings, $options);
					$data['message_type']       = 'whisper';
				}
			}

			$data['message_text'] = $this->_getHelper()->getMessageColor($data['message_text'], $this->settings, $options);

			if (!$options->siropu_chat_disable_all_bbcodes)
			{
				$data['message_text'] = $this->_getHelper()->stripDisallowedBBCodes($data['message_text']);
			}

			if (preg_match('/^\/(?:giphy|g)(.+?)?$/i', $messageClean, $matches)
				&& $options->siropu_chat_giphy_command_enabled
				&& $this->_getHelper()->userHasPermission('giphyCommand'))
			{
				if (empty($matches[1]))
				{
					$giphyUrl = 'http://api.giphy.com/v1/gifs/trending?api_key=dc6zaTOxFJmzC&limit=100';
				}
				else
				{
					$giphyUrl = 'http://api.giphy.com/v1/gifs/search?q=' . urlencode($matches[1]) . '&api_key=dc6zaTOxFJmzC&limit=100';
				}

				$gifs = @json_decode(@file_get_contents($giphyUrl), true);

				if (!empty($gifs['data']))
				{
					shuffle($gifs['data']);
					$data['message_text'] = '[IMG]' . $gifs['data'][0]['images']['original']['url'] . '[/IMG]';
				}
			}

			$tagged = $this->getModelFromCache('XenForo_Model_UserTagging')->getTaggedUsersInMessage($data['message_text'], $newMessage, $options->siropu_chat_link_tagged_users ? 'bb' : '');

			if (isset($tagged[$this->userId]))
			{
				unset($tagged[$this->userId]);
			}

			if ($tagged)
			{
				$data['message_text']   = $newMessage;
				$data['message_tagged'] = $this->_getHelper()->prepareTaggedUsers($tagged);
			}

			$quit = $mute = $muted = $kick = false;

			if (!empty($this->session['user_is_muted'])
				&& $this->_getModel()->userIsRoomMuted($this->userId, $this->roomId))
			{
				$muted = true;
			}

			if (preg_match('/^\/(quit|leave)/i', $messageClean)
				&& $options->siropu_chat_quit_command_enabled
				&& $this->_getHelper()->userHasPermission('quitCommand'))
			{
				$quit = true;
			}

			if ($quit)
			{
				if (!empty($this->session['user_last_activity']))
				{
					if (!$muted)
					{
						$quitMessage = trim(str_ireplace(array('/quit', '/leave'), '', $messageClean));

						$data['message_text'] = new XenForo_Phrase($quitMessage ? 'siropu_chat_quit_message' : 'siropu_chat_quit', array('name' => '[USER=' . $this->userId . ']' . $this->username . '[/USER]', 'message' => $quitMessage ? $quitMessage : ''), false);
						$data['message_type'] = 'quit';
					}

					$userRooms = $this->_getHelper()->getUserRooms($this->session, $this->roomId);

					$dwData = array(
						'user_room_id' => $this->_getHelper()->getUserLastRoomId($userRooms),
						'user_rooms'   => serialize($userRooms),
					);

					if (!$this->joinMultiple
						|| (empty($userRooms)
							|| count($userRooms) == 1 && !end($userRooms)))
					{
						$dwData['user_last_activity'] = 0;
					}

					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
					$dw->setExistingData($this->userId);
					$dw->bulkSet($dwData);
					$dw->save();
				}
				else
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_quit_error'));
				}
			}

			$muteCommand = $options->siropu_chat_mute_command;

			if (preg_match('/^\/(?:mute|m)\s(.+?)$/i', $message, $matches)
				&& $muteCommand['enabled']
				&& ($this->_getHelper()->userHasPermission('muteCommand')
					|| ($this->_getHelper()->userHasPermission('muteCommandRoom')
						&& $this->_getModel()->getRoomByIdAndUserId($this->roomId, $this->userId))))
			{
				if (!$muteUser = $this->_getUserModel()->getUserByName($matches[1]))
				{
					return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
				}

				$muteUserId = $muteUser['user_id'];

				if ($muteUserId == $this->userId)
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_mute_error_self'));
				}

				if ($muteUser['is_admin'] || $muteUser['is_moderator'] || $muteUser['is_staff'])
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_mute_error_staff'));
				}

				if ((!$muteSess = $this->_getModel()->getSession($muteUserId)) || !$muteSess['user_last_activity'])
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_no_chat_session'));
				}

				$userRooms = $this->_getHelper()->getUserRooms($muteSess);

				if (!isset($userRooms[$this->roomId]))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_mute_error_room'));
				}

				if ($this->_getModel()->userIsRoomMuted($muteUserId, $this->roomId))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_mute_error_is_muted',
						array('user' => $muteUser['username'])));
				}

				$this->_muteUser($muteSess, $muteCommand['length']);

				if (!$this->_displayNotification('mute'))
				{
					return $this->responseMessage(new XenForo_Phrase('siropu_chat_mute_success',
						array('user' => $muteUser['username'])));
				}

				$data['message_text'] = new XenForo_Phrase('siropu_chat_bot_user_muted', array(
						'user' => '[USER=' . $muteUserId . ']' . $muteUser['username'] . '[/USER]',
						'mod'  => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false);
				$data['message_type'] = 'bot';

				$mute = $muteUserId;
			}

			$kickCommand = $options->siropu_chat_kick_command;

			if (preg_match('/^\/(?:kick|k)\s(.+?)$/i', $message, $matches)
				&& $kickCommand['enabled']
				&& ($this->_getHelper()->userHasPermission('kickCommand')
					|| ($this->_getHelper()->userHasPermission('kickCommandRoom')
						&& $this->_getModel()->getRoomByIdAndUserId($this->roomId, $this->userId))))
			{
				if (!$kickUser = $this->_getUserModel()->getUserByName($matches[1]))
				{
					return $this->responseError(new XenForo_Phrase('requested_user_not_found'));
				}

				$kickUserId = $kickUser['user_id'];

				if ($kickUserId == $this->userId)
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_kick_error_self'));
				}

				if ($kickUser['is_admin'] || $kickUser['is_moderator'] || $kickUser['is_staff'])
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_kick_error_staff'));
				}

				if ((!$kickSess = $this->_getModel()->getSession($kickUserId)) || !$kickSess['user_last_activity'])
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_no_chat_session'));
				}

				$userRooms = $this->_getHelper()->getUserRooms($kickSess);

				if (!isset($userRooms[$this->roomId]))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_kick_error_room'));
				}

				$this->_kickUser($kickSess, $kickCommand['length']);

				if (!$this->_displayNotification('kick'))
				{
					return $this->responseMessage(new XenForo_Phrase('siropu_chat_kick_success',
						array('user' => $kickUser['username'])));
				}

				$data['message_text'] = new XenForo_Phrase('siropu_chat_bot_user_kicked', array(
						'user' => '[USER=' . $kickUserId . ']' . $kickUser['username'] . '[/USER]',
						'mod'  => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false);
				$data['message_type'] = 'bot';

				$kick = $kickUserId;
			}

			$prune        = '';
			$statusChange = false;

			if (preg_match('/^\/(?:prune|p)(.+?)?$/i', trim($message), $matches)
				&& $this->_getHelper()->userHasPermission('deleteAll'))
			{
				$pruneExtra = isset($matches[1]) ? trim($matches[1]) : '';

				if (!$pruneExtra)
				{
					$this->_getModel()->deleteMessagesByRoomId($this->roomId);
					$this->_getHelperActions()->savePruneAction(array($this->_addPruneMessage($this->roomId)));
					$prune = 'room';
				}
				else if ($pruneExtra == 'all')
				{
					$this->_deleteAllRoomsMessages();
					$this->_getHelper()->resetTopChatters(true);
					$prune = 'all';
				}
				else if ($pruneExtra == 'forum')
				{
					$this->_getModel()->deleteForumActivity();
					$prune = 'forum';
				}
				else if ($user = $this->_getUserModel()->getUserByName($pruneExtra))
				{
					$this->_getModel()->deleteMessagesByUserId($user['user_id'], $this->roomId);
					$this->_getHelperActions()->savePruneAction(array($this->_addPruneMessage($this->roomId, array('username' => $user['username'], 'user_id' => $user['user_id']))));
					$prune = $user['username'];
				}
			}
			else if (preg_match('/^\/(?:status|s)(.*?)$/i', trim($messageClean), $matches)
				&& $this->_getHelper()->userHasPermission('setStatus'))
			{
				$this->_updateStatus($matches[1]);
				$statusChange = true;
			}
			else if (!$muted)
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
				$dw->bulkSet($data);
				$dw->save();
			}

			if ($tagged && $options->siropu_chat_tag_alert_enabled && $data['message_type'] != 'whisper' && !$muted)
			{
				$room  = $this->_getModel()->getRoomById($this->roomId);
				$users = $this->_getUserModel()->getUsersByIds(array_keys($tagged),
					array('join' => XenForo_Model_User::FETCH_USER_PERMISSIONS));

				foreach ($users as $user)
				{
					$permissions = XenForo_Permission::unserializePermissions($user['global_permission_cache']);

					if (!$this->_getHelper()->checkRoomPermissions($room, $user)
						|| ($room
							&& $room['room_password']
							&& $room['room_user_id'] != $user['user_id']
							&& !XenForo_Permission::hasPermission($permissions, 'siropu_chat', 'bypassPassword')))
					{
						unset($tagged[$user['user_id']]);
					}
				}

				if ($tagged)
				{
					foreach ($tagged as $tag)
					{
						XenForo_Model_Alert::alert(
							$tag['user_id'],
							$this->userId,
							$this->username,
							'siropu_chat',
							$dw->get('message_id'),
							'tag'
						);
					}
				}
			}

			$sessionUpdateInterval = $options->siropu_chat_session_update_interval;
			$sessionLastUpdate     = $this->_input->filterSingle('session_last_update', XenForo_Input::UINT);
			$sessionUpdate         = true;

			if ($sessionUpdateInterval && $sessionLastUpdate && $sessionLastUpdate >= time() - $sessionUpdateInterval)
			{
				$sessionUpdate = false;
			}

			if ($this->userId && $sessionUpdate && !$quit && !$muted && !$statusChange)
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
				if ($this->session)
				{
					$dw->setExistingData($this->userId);
				}
				else
				{
					$dw->set('user_id', $this->userId);
				}
				if ($this->session && $this->session['user_room_id'] != $this->roomId)
				{
					$dw->set('user_room_id', $this->roomId);
				}
				$dw->set('user_rooms', $this->_getHelper()->prepareUserRoomsForSave($this->session, $this->roomId));
				$dw->set('user_last_activity', time());
				$dw->save();
			}

			return $this->_getChat(array(
				'action'        => 'submit',
				'sessionUpdate' => $sessionUpdate,
				'mutedMessage'  => $muted && !$statusChange ? $data : '',
				'kick'          => $kick,
				'mute'          => $mute,
				'prune'         => $prune
			));
		}

		$errors[] = new XenForo_Phrase('siropu_chat_submit_no_message');

		if ($options->siropu_chat_disable_all_bbcodes)
		{
			$errors[] = new XenForo_Phrase('siropu_chat_bbcodes_disabled');
		}

		return $this->responseError($errors);
	}
	public function actionResponse()
	{
		$this->_assertPostOnly();

		$responseId = $this->_input->filterSingle('response_id', XenForo_Input::UINT);
		$post       = true;

		if ($this->_getOptions()->siropu_chat_bot_responses_enabled
			&& ($response = $this->_getModel()->getResponseById($responseId)))
		{
			$rooms = @unserialize($response['response_rooms']);

			if (!empty($rooms) && !in_array($this->roomId, $rooms))
			{
				$post = false;
			}

			$settings = @unserialize($response['response_settings']);

			if (!empty($settings['min_interval']))
			{
				$last = @unserialize($response['response_last']);
				$last = $last ? $last : array();

				if (isset($last[$this->roomId]) && $last[$this->roomId] >= time() - ($settings['min_interval'] * 60))
				{
					$post = false;
				}
				else
				{
					$last[$this->roomId] = time();

					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_BotResponses');
					$dw->setExistingData($responseId);
					$dw->set('response_last', @serialize($last));
					$dw->save();
				}
			}

			if ($post)
			{
				$tagUser   = !empty($settings['username']);
				$prefix    = $this->_getOptions()->userTagKeepAt ? '@' : '';
				$responses = explode("\n", $response['response_message']);
				shuffle($responses);

				$dwData = array(
					'message_room_id'  => $this->roomId,
					'message_bot_name' => $response['response_bot_name'],
					'message_text'     => ($tagUser ? $prefix . '[USER=' . $this->userId . ']' . $this->username . '[/USER], ' : '') . $responses[0],
					'message_type'     => 'bot'
				);

				if ($tagUser)
				{
					$dwData['message_tagged'] = $this->_getHelper()->prepareTaggedUsers(array(array(
						'user_id'  => $this->userId,
						'username' => $this->username
					)));
				}

				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
				$dw->bulkSet($dwData);
				$dw->save();
			}
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
	}
	public function actionRefresh()
	{
		$this->_assertPostOnly();
		return $this->_getChat(array('action' => 'refresh'));
	}
	public function actionMedia()
	{
		$type = $this->_input->filterSingle('type', XenForo_Input::STRING);

		$viewParams = array(
			'type'      => $type,
			'imagePath' => $this->_getHelperUpload()->getImagePath('url')
		);

		if ($type == 'media')
		{
			$viewParams['sites'] = $this->getModelFromCache('XenForo_Model_BbCode')->getAllBbCodeMediaSites();
		}

		if ($type == 'image')
		{
			$viewParams['uploads']    = $this->_getImagesModel()->getImagesByUserId($this->userId);
			$viewParams['imageCount'] = $this->_getImagesModel()->getUserImageCount($this->userId);
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Media', 'siropu_chat_media', $viewParams);
	}
	public function actionEdit()
	{
		$message = $this->_getMessageOrError();

		if ((!in_array($message['message_type'], array('bot', 'activity'))
			|| $this->_getVisitor()->is_admin || $this->_getVisitor()->is_moderator)
			&& ($this->_getHelper()->userHasPermission('editAny')
				|| ($this->_getHelper()->userHasPermission('editOwn')
					&& $message['message_user_id'] == $this->userId)))
		{
			$recipients = @unserialize($message['message_recipients']);

			if ($message['message_type'] == 'whisper'
				&& !isset($recipients[$this->userId])
				&& $this->_getHelper()->userHasPermission('viewWhispers')
				&& !$this->_getHelper()->userHasPermission('readWhispers'))
			{
				return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}

			$viewParams = array(
				'message' => $message,
				'user'    => $this->_getUserModel()->getUserById($message['message_user_id'])
			);

			if ($this->isConfirmedPost())
			{
				$text = $this->_input->filterSingle('message', XenForo_Input::STRING);

				if (($smilieLimit = $this->_getOptions()->siropu_chat_smilie_limit)
					&& $this->_getHelper()->isSmilieLimitReached($text, $smilieLimit))
				{
					return $this->responseError(new XenForo_Phrase('siropu_chat_smilie_limit',
						array('limit' => $smilieLimit)));
				}

				if ($this->_getOptions()->siropu_chat_disable_all_bbcodes)
				{
					$text = XenForo_Helper_String::bbCodeStrip($text);
				}
				else
				{
					$text = Siropu_Chat_Helper::stripDisallowedBBCodes($text);
				}

				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
				$dw->setExistingData($message['message_id']);
				$dw->set('message_text', $text);
				$dw->save();

				return $this->responseView('Siropu_Chat_ViewPublic_Edit', '', array(
					'message' => $this->_getMessageOrError())
				);
			}

			return $this->responseView('', 'siropu_chat_message_edit', $viewParams);
		}

		return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
	}
	public function actionDelete()
	{
		$message = $this->_getMessageOrError();

		if ((!in_array($message['message_type'], array('bot', 'activity'))
			|| $this->_getVisitor()->is_admin || $this->_getVisitor()->is_moderator)
			&& ($this->_getHelper()->userHasPermission('deleteAny')
				|| ($this->_getHelper()->userHasPermission('deleteOwn')
					&& $message['message_user_id'] == $this->userId)))
		{
			$viewParams = array(
				'message' => $message,
				'user'    => $this->_getUserModel()->getUserById($message['message_user_id'])
			);

			if ($this->isConfirmedPost())
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
				$dw->setExistingData($message['message_id']);
				$dw->delete();

				if ($this->_getOptions()->siropu_chat_delete_messages_delete_reports
					&& ($report = $this->_getModel()->getReportByMessageId($message['message_id'])))
				{
					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Reports');
					$dw->setExistingData($report['report_id']);
					$dw->delete();
				}

				$this->_getHelperActions()->saveMessageAction($message, 'delete');

				return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array(
					'messageId'     => $message['message_id'],
					'messageRoomId' => $message['message_room_id']
				));
			}

			return $this->responseView('', 'siropu_chat_message_delete', $viewParams);
		}

		return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
	}
	public function actionQuote()
	{
		if (!$message = $this->_getModel()->getMessageByIdJoinUsers($this->_getMessageID()))
		{
			$this->responseError(new XenForo_Phrase('siropu_chat_message_not_found'));
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Quote', '', array('message' => $message));
	}
	public function actionReport()
	{
		if ($this->_getModel()->getReportByMessageId($this->_input->filterSingle('message_id', XenForo_Input::UINT)))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_message_already_reported'));
		}

		$message = $this->_getMessageOrError();

		if (!in_array($message['message_type'], array('bot', 'activity'))
			&& $this->_getHelper()->userHasPermission('report'))
		{
			$messageRaw = $message['message_text'];
			$message['message_text'] = XenForo_Helper_String::bbCodeStrip($messageRaw);

			$viewParams = array(
				'message' => $message,
				'user'    => $this->_getUserModel()->getUserById($message['message_user_id'])
			);

			if ($this->isConfirmedPost())
			{
				if (!$reason = $this->_input->filterSingle('report_reason', XenForo_Input::STRING))
				{
					return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
				}

				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Reports');
				$dw->bulkSet(array(
					'report_message_id'      => $message['message_id'],
					'report_message_user_id' => $message['message_user_id'],
					'report_message_text'    => $messageRaw,
					'report_message_date'    => $message['message_date'],
					'report_room_id'         => $message['message_room_id'],
					'report_user_id'         => $this->userId,
					'report_reason'          => $reason
				));
				$dw->save();

				return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array(
					'messageId'     => $message['message_id'],
					'messageRoomId' => $message['message_room_id'],
				));
			}

			return $this->responseView('Siropu_Chat_ViewPublic_ReportView', 'siropu_chat_message_report', $viewParams);
		}

		return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
	}
	public function actionModerator()
	{
		if (!$this->_getHelper()->userHasPermission('ban'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$user   = $this->_getUserOrError();
		$action = $this->_input->filterSingle('action', XenForo_Input::STRING);
		$banId  = $this->_input->filterSingle('ban_id', XenForo_Input::UINT);
		$ban    = $this->_getModel()->getBanById($banId);

		if ($this->isConfirmedPost())
		{
			$phraseData = array('user' => '[USER=' . $user['user_id'] . ']' . $user['username'] . '[/USER]', 'mod'  => '[USER=' . $this->userId . ']' . $this->username . '[/USER]');

			switch ($action)
			{
				case 'unban':
				case 'unkick':
					if ($action == 'unban')
					{
						$dwData = array(
							'message_text' => new XenForo_Phrase(($ban['ban_room_id'] == -1 ? 'siropu_chat_bot_user_unbanned' : 'siropu_chat_bot_user_room_unbanned'), $phraseData, false)
						);
					}
					else
					{
						$dwData = array(
							'message_text' => new XenForo_Phrase('siropu_chat_bot_user_unkicked', $phraseData, false)
						);
					}

					break;
				case 'unmute':

					if (!$this->_getModel()->getUserMutes($user['user_id']))
					{
						$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
						$dw->setExistingData($user['user_id']);
						$dw->set('user_is_muted', 0);
						$dw->save();
					}

					$dwData = array(
						'message_text' => new XenForo_Phrase('siropu_chat_bot_user_unmuted', $phraseData, false)
					);

					break;
			}

			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Bans');
			$dw->setExistingData($ban['ban_id']);
			$dw->delete();

			if (!$this->_getModel()->getUserBansAndKicks($user['user_id']))
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
				$dw->setExistingData($user['user_id']);
				$dw->set('user_is_banned', 0);
				$dw->save();
			}

			if ($this->_displayNotification($action))
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
				$dw->bulkSet(array_merge(array(
					'message_user_id' => $user['user_id'],
					'message_room_id' => $ban['ban_room_id'] == -1 ? 0 : $ban['ban_room_id'],
					'message_type'    => 'bot'
					), $dwData));
				$dw->save();
			}

			return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array('banId' => $ban['ban_id']));
		}

		$viewParams = array(
			'action' => $action,
			'user'   => $user,
			'ban'    => $ban
		);

		return $this->responseView('', 'siropu_chat_unban', $viewParams);
	}
	public function actionRules()
	{
		$action = $this->_input->filterSingle('action', XenForo_Input::STRING);
		$rules  = $this->_input->filterSingle('rules', XenForo_Input::STRING);

		if ($action == 'edit')
		{
			if ($this->isConfirmedPost())
			{
				$this->_assertPostOnly();

				if (!$this->_getHelper()->userHasPermission('editRules'))
				{
					return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
				}

				$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
				$dw->setExistingData('siropu_chat_rules');
				$dw->set('option_value', $rules);
				$dw->save();

				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('chat')
				);
			}

			return $this->responseView('', 'siropu_chat_rules_edit',
				array('rules' => $this->_getOptions()->siropu_chat_rules));
		}

		return $this->responseView('', 'siropu_chat_rules');
	}
	public function actionAds()
	{
		$ads = $this->_input->filter(array(
			'ads_above_messages'      => XenForo_Input::STRING,
			'ads_below_messages'      => XenForo_Input::STRING,
			'ads_below_editor'        => XenForo_Input::STRING,
			'ads_below_visitor_panel' => XenForo_Input::STRING
		));

		if ($this->isConfirmedPost())
		{
			$this->_assertPostOnly();

			if (!$this->_getHelper()->userHasPermission('editAds'))
			{
				return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
			}

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
			$dw->setExistingData('siropu_chat_ads_above_messages');
			$dw->set('option_value', $ads['ads_above_messages']);
			$dw->save();

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
			$dw->setExistingData('siropu_chat_ads_below_messages');
			$dw->set('option_value', $ads['ads_below_messages']);
			$dw->save();

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
			$dw->setExistingData('siropu_chat_ads_below_editor');
			$dw->set('option_value', $ads['ads_below_editor']);
			$dw->save();

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
			$dw->setExistingData('siropu_chat_ads_below_visitor_panel');
			$dw->set('option_value', $ads['ads_below_visitor_panel']);
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('chat')
			);
		}

		return $this->responseView('', 'siropu_chat_ads_edit', array(
			'adsAboveMessages'     => $this->_getOptions()->siropu_chat_ads_above_messages,
			'adsBelowMessages'     => $this->_getOptions()->siropu_chat_ads_below_messages,
			'adsBelowEditor'       => $this->_getOptions()->siropu_chat_ads_below_editor,
			'adsBelowVisitorPanel' => $this->_getOptions()->siropu_chat_ads_below_visitor_panel
		));
	}
	public function actionHelp()
	{
		$roomAuthor = false;

		if ($this->session)
		{
			$roomAuthor = $this->_getModel()->getRoomByIdAndUserId($this->session['user_room_id'], $this->userId);
		}

		return $this->responseView('', 'siropu_chat_help', array('roomAuthor' => $roomAuthor));
	}
	public function actionMute()
	{
		$muteCommand = $this->_getOptions()->siropu_chat_mute_command;

		if (!$muteCommand['enabled'] && !$this->_getHelper()->userHasPermission('muteCommand'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);

		if (($user = $this->_getModel()->getSessionJoinUser($userId))
			&& !$this->_getModel()->userIsRoomMuted($userId, $this->roomId))
		{
			if ($user['is_admin'] || $user['is_moderator'] || $user['is_staff'])
			{
				return $this->responseError(new XenForo_Phrase('siropu_chat_mute_error_staff'));
			}

			$banId = $this->_muteUser($user, $muteCommand['length']);

			if ($this->_displayNotification('mute'))
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
				$dw->bulkSet(array(
					'message_room_id' => $this->roomId,
					'message_text'    => new XenForo_Phrase('siropu_chat_bot_user_muted', array(
						'user' => '[USER=' . $user['user_id'] . ']' . $user['username'] . '[/USER]',
						'mod'  => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false),
					'message_type'    => 'bot'
				));
				$dw->save();
			}

			return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array('muted'  => $userId));
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
	}
	public function actionKick()
	{
		$this->_assertPostOnly();

		$kickCommand = $this->_getOptions()->siropu_chat_kick_command;

		if (!$kickCommand['enabled'] && !$this->_getHelper()->userHasPermission('kickCommand'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		$user = $this->_getUserOrError();

		if ($user['is_admin'] || $user['is_moderator'] || $user['is_staff'])
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_kick_error_staff'));
		}

		if (!$user['user_last_activity'])
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_kick_no_chat_session'));
		}

		$userRooms = $this->_getHelper()->getUserRooms($user);

		if (!isset($userRooms[$this->roomId]))
		{
			return $this->responseError(new XenForo_Phrase('siropu_chat_kick_error_room'));
		}

		$this->_kickUser($user, $kickCommand['length']);

		if ($this->_displayNotification('kick'))
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
			$dw->bulkSet(array(
				'message_room_id' => $this->roomId,
				'message_text'    => new XenForo_Phrase('siropu_chat_bot_user_kicked', array(
					'user' => '[USER=' . $user['user_id'] . ']' . $user['username'] . '[/USER]',
					'mod'  => '[USER=' . $this->userId . ']' . $this->username . '[/USER]'), false),
				'message_type'    => 'bot'
			));
			$dw->save();
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', array('kicked' => $user['user_id']));
	}
	public function actionEnable()
	{
		$this->_assertPostOnly();

		$viewParams = array();

		if ($this->session)
		{
			$settings = unserialize($this->session['user_settings']);
			$settings['disabled'] = 0;

			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
			$dw->setExistingData($this->userId);
			$dw->set('user_settings', serialize($settings));
			$dw->save();

			$viewParams['enabled'] = true;
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '', $viewParams);
	}
	public function actionNotices()
	{
		if (!$this->_getHelper()->userHasPermission('editNotices'))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		if ($this->isConfirmedPost())
		{
			$this->_assertPostOnly();

			$notices = $this->_input->filterSingle('notices', XenForo_Input::STRING);

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
			$dw->setExistingData('siropu_chat_notices');
			$dw->set('option_value', $notices);
			$dw->save();

			return $this->responseView('Siropu_Chat_ViewPublic_Ajax', '',
				array('notice' => $notices ? $this->_getHelper()->getNotices($notices) : ''));
		}

		$notices = $this->getModelFromCache('XenForo_Model_Option')->getOptionById('siropu_chat_notices');

		$viewParams = array(
			'notices' => $notices['option_value']
		);

		return $this->responseView('', 'siropu_chat_notices', $viewParams);
	}
	public function actionSetDefaultRoom()
	{
		$this->_assertPostOnly();

		if ($this->userId)
		{
			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
			if ($this->session)
			{
				$dw->setExistingData($this->userId);
			}
			else
			{
				$userRooms = $this->_getHelper()->getUserRooms(array(), null, $this->_getModel()->getAllRooms());

				$dw->set('user_id', $this->userId);
				$dw->set('user_rooms', @serialize($userRooms));
				$dw->set('user_last_activity', 0);
			}
			$dw->set('user_room_id', $this->roomId);
			$dw->save();
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
	}
	public function actionUpdateSession()
	{
		$this->_assertPostOnly();

		if ($this->session)
		{
			$userRooms = $this->_getHelper()->getUserRooms($this->session);
			$userBans  = $this->_getHelper()->sortBansByRoomId($this->_getModel()->getAllUserBans($this->userId));

			foreach ($userRooms as $key => $val)
			{
				if (!isset($userBans[$key]))
				{
					$userRooms[$key] = time();
				}
			}

			$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
			$dw->setExistingData($this->userId);
			$dw->set('user_rooms', @serialize($userRooms));
			$dw->set('user_last_activity', time());
			$dw->save();
		}

		return $this->responseView('Siropu_Chat_ViewPublic_Ajax');
	}
	protected function _muteUser($user, $length)
	{
		$dwBan = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Bans');
		$dwBan->bulkSet(array(
			'ban_user_id' => $user['user_id'],
			'ban_room_id' => $this->roomId,
			'ban_author'  => $this->userId,
			'ban_end'     => strtotime("+{$length} hours"),
			'ban_type'    => 'mute'
		));
		$dwBan->save();

		if (!$user['user_is_muted'])
		{
			$dwSess = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
			$dwSess->setExistingData($user['user_id']);
			$dwSess->set('user_is_muted', 1);
			$dwSess->save();
		}

		return $dwBan->get('ban_id');
	}
	protected function _kickUser($user, $length)
	{
		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Bans');
		$dw->bulkSet(array(
			'ban_user_id' => $user['user_id'],
			'ban_room_id' => $this->roomId,
			'ban_author'  => $this->userId,
			'ban_end'     => strtotime("+{$length} hours"),
			'ban_type'    => 'kick'
		));
		$dw->save();

		$userRooms = $this->_getHelper()->getUserRooms($user, $this->roomId);

		$dwData = array(
			'user_room_id'   => $this->_getHelper()->getUserLastRoomId($userRooms),
			'user_rooms'     => serialize($userRooms),
			'user_is_banned' => 1,
		);

		if (!$this->joinMultiple
			|| (empty($userRooms)
				|| count($userRooms) == 1
				&& !end($userRooms)))
		{
			$dwData['user_last_activity'] = 0;
		}

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
		$dw->setExistingData($user['user_id']);
		$dw->bulkSet($dwData);
		$dw->save();
	}
	protected function _getChat($return = array())
	{
		$data = $this->_input->filter(array(
			'user_rooms'           => XenForo_Input::ARRAY_SIMPLE,
			'last_id'              => XenForo_Input::UINT,
			'activity_last_id'     => XenForo_Input::UINT,
			'activity_last_update' => XenForo_Input::UINT,
			'users_last_update'    => XenForo_Input::UINT,
			'inverse'              => XenForo_Input::UINT,
			'show_ignored'         => XenForo_Input::UINT,
			'hide_bot'             => XenForo_Input::UINT,
			'no_users'             => XenForo_Input::UINT,
			'all_pages'            => XenForo_Input::UINT,
			'loading'              => XenForo_Input::UINT
		));

		$ignored = $data['show_ignored'] ? array() : $this->_getHelper()->getIgnoredUsers();
		$inverse = $data['inverse'];

		$data = array_merge($data, $return, array(
			'room_id' => $this->roomId,
			'userId'  => $this->userId
		));

		if ($data['loading'])
		{
			$conditions = array();
		}
		else if ($this->joinMultiple)
		{
			$conditions = array('user_rooms' => $data['user_rooms']);
		}
		else
		{
			$conditions = array('last_id' => $data['last_id'], 'room_id' => $data['room_id']);
		}

		if ($data['hide_bot'])
		{
			$conditions['hide_bot'] = true;
		}

		$forumActivity = array();

		if ($this->_getOptions()->siropu_chat_forum_activity_tab && $data['activity_last_update'] <= time() - 30)
		{
			$forumActivity = $this->_getModel()->getForumActivity($data['activity_last_id'], $inverse);
		}

		$users = array();
		$data['usersRefresh'] = false;

		if ($this->_getOptions()->siropu_chat_user_list_enabled)
		{
			$usersRefreshInterval = $this->_getOptions()->siropu_chat_user_list_refresh_interval;

			if (!$usersRefreshInterval
				|| $data['users_last_update'] <= time() - $usersRefreshInterval
				|| $return['action'] == 'join')
			{
				$users = $this->_getModel()->getActiveUsers($ignored);
				$data['usersRefresh'] = true;
			}
		}

		$lastActive = !empty($this->session['user_last_activity']) ? $this->session['user_last_activity'] : 0;

		$data['activeSession'] = $lastActive >= $this->_getUserActiveTimeFrame();
		$data['lastActive']    = time() - $lastActive;

		$messages = array();

		if ($data['loading'] && $this->joinMultiple)
		{
			foreach ($data['user_rooms'] as $roomId => $lastId)
			{
				$conditions['room_id'] = $roomId;
				$messages += $this->_getModel()->getMessages($conditions);
			}
		}
		else
		{
			$messages = $this->_getModel()->getMessages($conditions);

			if (!empty($data['mutedMessage']))
			{
				$messageId = $data['last_id'] + rand(count($messages), 999999);

				$messages[$messageId] = array_merge(array(
					'message_id'              => $messageId,
					'message_room_id'         => $this->roomId,
					'message_date'            => time(),
					'user_id'                 => $this->userId,
					'username'                => $this->username,
					'avatar_date'             => $this->_getVisitor()->avatar_date,
					'gravatar'                => $this->_getVisitor()->gravatar,
					'display_style_group_id'  => $this->_getVisitor()->display_style_group_id,
					'message_bot_name'        => '',
					'message_recipients'      => '',
					'message_tagged'          => '',
					'is_admin'                => 0,
					'is_moderator'            => 0,
					'is_staff'                => 0,
				), $data['mutedMessage']);

				$data['lastFakeId'] = $messageId;
			}
		}

		$viewParams = array(
			'chatMessages'      => $messages,
			'chatForumActivity' => $forumActivity,
			'chatIgnored'       => $ignored,
			'chatInverse'       => $inverse,
			'chatImagesAsLinks' => !empty($this->settings['images_as_links']),
			'chatSettings'      => $this->settings,
			'users'             => $users,
			'data'              => $data
		);

		return $this->responseView('Siropu_Chat_ViewPublic_Public', 'siropu_chat_messages', $viewParams);
	}
	protected function _getBannedMessage($session, $roomId)
	{
		if (!empty($session['user_is_banned']) && ($userBans = $this->_getModel()->getUserBansAndKicks($this->userId)))
		{
			$banData = '';

			foreach ($userBans as $ban)
			{
				if ($ban['ban_room_id'] == $roomId || $ban['ban_room_id'] == -1)
				{
					$banData = $ban;
					break;
				}
			}

			if ($banData)
			{
				$phraseData = array('reason' => $banData['ban_reason'] ? $banData['ban_reason'] : 'N/A', 'ends' => $banData['ban_end'] ? XenForo_Locale::dateTime($banData['ban_end'], 'absolute') : new XenForo_Phrase('never'));

				if ($banData['ban_end'] && $banData['ban_end'] < time())
				{
					return false;
				}

				if ($banData['ban_type'] == 'ban')
				{
					if ($banData['ban_room_id'] == -1)
					{
						return new XenForo_Phrase('siropu_chat_banned_message', $phraseData);
					}
					else
					{
						return new XenForo_Phrase('siropu_chat_banned_room_message', $phraseData);
					}
				}
				else
				{
					return new XenForo_Phrase('siropu_chat_kicked_message',
						array('date' => XenForo_Locale::dateTime($banData['ban_end'], 'absolute')));
				}
			}
		}
	}
	protected function _deleteAllRoomsMessages()
	{
		$messages = array();

		foreach ($this->_getModel()->getAllRooms() as $room)
		{
			$this->_getModel()->deleteMessagesByRoomId($room['room_id']);
			$messages[] = $this->_addPruneMessage($room['room_id']);
		}

		$this->_getHelperActions()->savePruneAction($messages);
	}
	protected function _updateStatus($status = '')
	{
		$status = $status ? $status : $this->_input->filterSingle('status', XenForo_Input::STRING);
		$status = XenForo_Helper_String::wholeWordTrim($status, $this->_getOptions()->siropu_chat_status_max_length);

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
		if ($this->session)
		{
			$dw->setExistingData($this->userId);
		}
		else
		{
			$userRooms = $this->_getHelper()->getUserRooms(array(), null, $this->_getModel()->getAllRooms());

			$dw->set('user_id', $this->userId);
			$dw->set('user_rooms', @serialize($userRooms));
			$dw->set('user_last_activity', 0);
		}
		$dw->set('user_status', $status);
		$dw->save();
	}
	protected function _addPruneMessage($roomId = 0, $user = array())
	{
		$name = array('name' => '[USER=' . $this->userId . ']' . $this->username . '[/USER]');

		if (isset($user['username']))
		{
			$text = new XenForo_Phrase('siropu_chat_prune_user',
				array_merge($name, array('author' => '[USER=' . $user['user_id'] . ']' . $user['username'] . '[/USER]')));
		}
		else
		{
			$text = new XenForo_Phrase('siropu_chat_all_messages_deleted', $name);
		}

		$dwData = array(
			'message_room_id' => $roomId,
			'message_user_id' => $this->userId,
			'message_text'    => $text,
			'message_type'    => 'bot',
		);

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
		$dw->bulkSet($dwData);
		$dw->save();

		$dwData['message_id']   = $dw->get('message_id');
		$dwData['message_text'] = '';

		if (isset($user['username']))
		{
			$dwData['username'] = $user['username'];
			$dwData['user_id']  = $user['user_id'];
		}

		return $dwData;
	}
	protected function _displayNotification($notification)
	{
		return !empty($this->_getOptions()->siropu_chat_displayed_notifications[$notification]);
	}
	protected function _getUserActiveTimeFrame()
	{
		return time() - $this->_getOptions()->siropu_chat_last_activity_minutes * 60;
	}
	protected function _getMessageOrError()
	{
		if ($data = $this->_getModel()->getMessageById($this->_getMessageID()))
		{
			return $data;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_chat_message_not_found'), 404));
	}
	protected function _getUserOrError($id = null)
	{
		if (!$id)
		{
			$id = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		}

		if ($data = $this->_getModel()->getSessionJoinUser($id))
		{
			return $data;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('requested_user_not_found'), 404));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	protected function _getImagesModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model_Images');
	}
	protected function _getHelper()
	{
		return $this->getHelper('Siropu_Chat_Helper');
	}
	protected function _getHelperActions()
	{
		return $this->getHelper('Siropu_Chat_HelperActions');
	}
	protected function _getHelperUpload()
	{
		return $this->getHelper('Siropu_Chat_HelperUpload');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getVisitor()
	{
		return XenForo_Visitor::getInstance();
	}
	protected function _getMessageID()
	{
		return $this->_input->filterSingle('message_id', XenForo_Input::UINT);
	}
}
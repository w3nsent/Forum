<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_Model extends Xenforo_Model
{
	public $playSound = '';

	public function getSession($userId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_sessions
			WHERE user_id = ?
		', $userId);
	}
	public function getSessionJoinUser($userId)
	{
		return $this->_getDb()->fetchRow('
			SELECT s.*, u.username, u.is_moderator, u.is_admin, u.is_staff
			FROM xf_siropu_chat_sessions AS s
			LEFT JOIN xf_user AS u ON u.user_id = s.user_id
			WHERE s.user_id = ?
		', $userId);
	}
	public function getMessages($conditions = array(), $fetchOptions = array(), $isArchive = false)
	{
		$db = $this->_getDb();

		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions ? $fetchOptions : array('page' => 1, 'perPage' => $this->_getOptions()->siropu_chat_database_messages_limit));
		$joinMultipleRooms = Siropu_Chat_Helper::userCanJoinMultipleRooms();
		$userRooms = !empty($conditions['user_rooms']) ? $conditions['user_rooms'] : '';

		$where = '';
		$order = isset($conditions['order']) && $conditions['order'] == 'ASC' ? 'ASC' : 'DESC';

		if ($conditions)
		{
			if (!empty($conditions['last_id']))
			{
				$where .= ' WHERE message_id > ' . $db->quote($conditions['last_id']);
			}
			if (!empty($conditions['message_id']))
			{
				$messageId = $db->quote($conditions['message_id']);

				$where .= $this->_andWhere($where) . '(message_id <= ' . $messageId;
				$where .= ' OR (message_id > ' . $messageId . ' AND message_id < ' . ($messageId + 25) . '))';
				$where .= ' AND message_type = "' . (isset($conditions['message_type']) ? $conditions['message_type'] : '') . '"';
				$order  = 'DESC';
			}
			if ($userRooms && $joinMultipleRooms)
			{
				$where .= $this->_andWhere($where) . '(';

				$i = 0;

				foreach ($userRooms as $roomId => $lastId)
				{
					$i++;

					$where .= (($i == 1) ? '' : ' OR ') . 'message_room_id = ' . $db->quote($roomId) . ' AND message_id > ' . $db->quote($lastId);
				}

				$where .= ')';
			}
			else if (isset($conditions['room_id']) && $conditions['room_id'] !== 'any')
			{
				$where .= $this->_andWhere($where) . 'message_room_id = ' . $db->quote($conditions['room_id']);
			}
			if (isset($conditions['hide_bot']))
			{
				$where .= $this->_andWhere($where) . 'message_type NOT IN ("bot", "activity")';
			}
			if (isset($conditions['keywords']))
			{
				$where .= $this->_andWhere($where) . 'message_text LIKE ' . $db->quote('%' . $conditions['keywords'] . '%');
			}
			if (isset($conditions['user_id']))
			{
				$where .= $this->_andWhere($where) . 'message_user_id IN (' . $this->_prepareUserIds($conditions['user_id']) . ')';
			}
			if (isset($conditions['date_start']))
			{
				$where .= $this->_andWhere($where) . 'message_date BETWEEN ' . $db->quote($conditions['date_start']) . ' AND ' . $db->quote($conditions['date_end']);
			}
		}

		if ($isArchive && !isset($conditions['forum_activity']))
		{
			$where .= $this->_andWhere($where) . 'message_type <> "activity"';
		}

		if (!$isArchive
			&& ($this->_getOptions()->siropu_chat_forum_activity_threads
				|| $this->_getOptions()->siropu_chat_forum_activity_posts)
					&& $this->_getOptions()->siropu_chat_forum_activity_tab
					&& $joinMultipleRooms
					&& !isset($conditions['hide_bot']))
		{
			$where .= $this->_andWhere($where) . 'message_type <> "activity"';
		}

		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT
				message_id,
				message_room_id,
				message_user_id AS user_id,
				message_bot_name,
				message_recipients,
				message_tagged,
				message_text,
				message_type,
				message_date,
				username,
				gender,
				display_style_group_id,
				avatar_date,
				gravatar,
				is_admin,
				is_moderator,
				is_staff
			FROM xf_siropu_chat_messages
			LEFT JOIN xf_user ON user_id = message_user_id
			' . $where . '
			ORDER BY message_id ' . $order . '
		', $limitOptions['limit'], $limitOptions['offset']), 'message_id');
	}
	public function getMessagesCount($conditions = array())
	{
		$db = $this->_getDb();
		$where = '';

		if ($conditions)
		{
			if (!empty($conditions['last_id']))
			{
				$where .= $this->_andWhere($where) . 'message_id > ' . $db->quote($conditions['last_id']);
			}
			if (!empty($conditions['message_id']))
			{
				$where .= $this->_andWhere($where) . 'message_id = ' . $db->quote($conditions['message_id']);
			}
			if (isset($conditions['room_id']) && $conditions['room_id'] !== 'any')
			{
				$where .= $this->_andWhere($where) . 'message_room_id = ' . $db->quote($conditions['room_id']);
			}
			if (isset($conditions['keywords']))
			{
				$where .= $this->_andWhere($where) . 'message_text LIKE ' . $db->quote('%' . $conditions['keywords'] . '%');
			}
			if (isset($conditions['user_id']))
			{
				$where .= $this->_andWhere($where) . 'message_user_id IN (' . $this->_prepareUserIds($conditions['user_id']) . ')';
			}
			if (isset($conditions['date_start']))
			{
				$where .= $this->_andWhere($where) . 'message_date BETWEEN ' . $db->quote($conditions['date_start']) . ' AND ' . $db->quote($conditions['date_end']);
			}
		}

		if (!isset($conditions['forum_activity']))
		{
			$where .= $this->_andWhere($where) . 'message_type <> "activity"';
		}

		$result = $db->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_messages
			' . $where
		);

		return $result['count'];
	}
	public function getForumActivity($lastId = 0, $inverse = false)
	{
		$db = $this->_getDb();
		$resultArray = $db->fetchAll('
			SELECT
				message_id,
				message_text,
				message_date,
				user_id,
				username,
				gender,
				display_style_group_id,
				avatar_date,
				gravatar
			FROM xf_siropu_chat_messages
			LEFT JOIN xf_user ON user_id = message_user_id
			WHERE message_id > ' . $db->quote($lastId) . '
			AND message_type = "activity"
			ORDER BY message_id DESC
			LIMIT 25');

		return $inverse ? $resultArray : array_reverse($resultArray);
	}
	public function getMessageByIds($ids)
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_chat_messages
			WHERE message_id IN (' . implode(',', $ids) . ')
		', 'message_id');
	}
	public function getMessageById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_messages
			WHERE message_id = ?
		', $id);
	}
	public function getMessageByIdJoinUsers($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT
				m.*,
				u.user_id,
				u.username
			FROM xf_siropu_chat_messages AS m
			LEFT JOIN xf_user AS u ON u.user_id = m.message_user_id
			WHERE m.message_id = ?
		', $id);
	}
	public function getMessagesBefore($data)
	{
		if (!isset($data['message_id']))
		{
			return array();
		}

		$db = $this->_getDb();

		return $db->fetchAll($this->limitQueryResults('
			SELECT
				m.message_id,
				m.message_user_id AS user_id,
				m.message_recipients,
				m.message_tagged,
				m.message_text,
				m.message_text AS message,
				m.message_type,
				m.message_date,
				u.username,
				u.gender,
				u.display_style_group_id,
				u.avatar_date,
				u.gravatar
			FROM xf_siropu_chat_messages AS m
			LEFT JOIN xf_user AS u ON u.user_id = m.message_user_id
			WHERE message_id < ' . $data['message_id'] . ' AND message_room_id = ' . $data['message_room_id'] . ' AND message_type = ' . $db->quote($data['message_type']) . '
			ORDER BY m.message_id DESC
		', 5, 0));
	}
	public function userIsFlooding($userId, $floodLength)
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_messages
			WHERE message_user_id = ?
			AND message_date >= ?
			AND message_type <> "bot"
		', array($userId, XenForo_Application::$time - $floodLength));

		return $result['count'];
	}
	public function getRoomLastMessage($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_messages
			WHERE message_room_id = ?
			AND message_type NOT IN ("bot")
			ORDER BY message_date DESC
			LIMIT 1
		', $id);
	}
	public function getAllRooms()
	{
		$resultArray[0] = array(
			'room_id'          => 0,
			'room_name'        => new XenForo_Phrase('siropu_chat_room_general_name'),
			'room_description' => new XenForo_Phrase('siropu_chat_room_general_description'),
			'hasPermission'    => true
		);

		if (!$this->_getOptions()->siropu_chat_rooms_enabled)
		{
			return $resultArray;
		}

		$resultArray += $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_chat_rooms
			ORDER BY room_id ASC
		', 'room_id');

		return $resultArray;
	}
	public function getRoomsAutoDelete()
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_rooms
			WHERE room_auto_delete = 1
		');
	}
	public function getRoomById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_rooms
			WHERE room_id = ?
		', $id);
	}
	public function getRoomByIdAndUserId($roomId, $userId)
	{
		$db = $this->_getDb();
		return $db->fetchRow('
			SELECT *
			FROM xf_siropu_chat_rooms
			WHERE room_id = ' . $db->quote($roomId) . '
			AND room_user_id = ' . $db->quote($userId)
		);
	}
	public function getActiveUsersWidget()
	{
		$lastActivity = strtotime('-' . $this->_getOptions()->siropu_chat_last_activity_minutes . ' Minutes');

		return $this->_getDb()->fetchAll('
			SELECT
				s.user_id,
				s.user_room_id,
				s.user_rooms,
				s.user_status,
				s.user_last_activity,
				u.username,
				u.gender,
				u.display_style_group_id,
				u.avatar_date,
				u.gravatar,
				u.is_admin,
				u.is_moderator
			FROM xf_siropu_chat_sessions AS s
			LEFT JOIN xf_user AS u ON u.user_id = s.user_id
			WHERE s.user_last_activity >= ' . $lastActivity . '
			ORDER BY s.user_last_activity DESC
		');
	}
	public function getActiveUsers($ignored = array())
	{
		$lastActivity = strtotime('-' . $this->_getOptions()->siropu_chat_last_activity_minutes . ' Minutes');

		$resultArray = $this->_getDb()->fetchAll('
			SELECT
				s.user_id,
				s.user_room_id,
				s.user_rooms,
				s.user_status,
				s.user_last_activity,
				u.username,
				u.gender,
				u.display_style_group_id,
				u.avatar_date,
				u.gravatar,
				u.is_admin,
				u.is_moderator
			FROM xf_siropu_chat_sessions AS s
			LEFT JOIN xf_user AS u ON u.user_id = s.user_id
			WHERE s.user_last_activity >= ' . $lastActivity . '
			ORDER BY s.user_last_activity DESC
		');

		$roomUsers = array();

		foreach ($resultArray as $row)
		{
			if ($ignored && isset($ignored[$row['user_id']]))
			{
				$row['is_ignored'] = true;
			}

			$roomList = unserialize($row['user_rooms']);
			$roomList = $roomList ? $roomList : array($row['user_last_activity']);

			foreach ($roomList as $roomId => $lastRoomActivity)
			{
				if ($lastRoomActivity >= $lastActivity)
				{
					$roomUsers[$roomId]['data'][] = array_merge($row, array(
						'user_room_id'       => $roomId,
						'user_last_activity' => $lastRoomActivity ? $lastRoomActivity : $row['user_last_activity']
					));

					$roomUsers[$roomId]['list'][] = $row['username'];
				}
			}
		}

		$roomUsers['count'] = count($resultArray);
		return $roomUsers;
	}
	public function getActiveUsersCount()
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_sessions AS s
			WHERE s.user_last_activity >= ' . strtotime('-' . XenForo_Application::get('options')->siropu_chat_last_activity_minutes . ' Minutes') . '
		');

		return $result['count'];
	}
	public function getTopChatters($search, $start = 0, $end = 0, $limit = 10)
	{
		if (!in_array($search, array('today', 'yesterday', 'thisWeek', 'thisMonth', 'lastWeek', 'lastMonth')))
		{
			$search = 'all';
		}

		if (!$start)
		{
			$start = strtotime('June 5 2015');
		}

		if (!$end)
		{
			$end = time();
		}

		$resultArrayCache = XenForo_Application::getSimpleCacheData('chatTop' . $search);
		$resultLastUpdate = XenForo_Application::getSimpleCacheData('chatTopLastUpdate' . $search);

		if ($resultArrayCache && $resultLastUpdate >= time() - 3600)
		{
			return $resultArrayCache;
		}

		$reset = XenForo_Application::getSimpleCacheData('chatTopResetDate');
		$reset = $reset ? $reset : strtotime('June 5 2015');

		$db = $this->_getDb();
		$resultArray = $db->fetchAll('
			SELECT
				u.user_id,
				u.username,
				u.gender,
				u.display_style_group_id,
				u.message_count,
				u.trophy_points,
				u.avatar_date,
				u.gravatar,
				u.like_count,
				COUNT(*) AS messageCount
			FROM xf_siropu_chat_messages AS m
			LEFT JOIN xf_user AS u ON u.user_id = m.message_user_id
			WHERE m.message_date BETWEEN ? AND ?
			AND m.message_type IN ("chat", "me")
			AND m.message_date >= ?
			GROUP BY m.message_user_id
			ORDER BY messageCount DESC
			LIMIT ' . $limit
		, array($start, $end, $reset));

		if ($resultArray)
		{
			XenForo_Application::setSimpleCacheData('chatTop' . $search, $resultArray);
			XenForo_Application::setSimpleCacheData('chatTopLastUpdate' . $search, time());
		}

		return $resultArray;
	}
	public function isBanned($userId)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
			AND ban_type IN ("chat")
		', $userId);
	}
	public function userIsRoomMuted($userId, $roomId)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
			AND ban_room_id = ?
			AND ban_type IN ("mute")
		', array($userId, $roomId));
	}
	public function getBannedUsers($conditions = array(), $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$where = '';

		if (isset($conditions['user_id']))
		{
			$where .= ' WHERE b.ban_user_id IN (' . $this->_prepareUserIds($conditions['user_id']) . ')';
		}

		if (isset($conditions['ban_type']))
		{
			$where .= ($where ? ' AND ' : ' WHERE ') . 'b.ban_type = ' . $this->_getDb()->quote($conditions['ban_type']);
		}

		return $this->_getDb()->fetchAll($this->limitQueryResults('
			SELECT
				b.*,
				r.room_name,
				u1.user_id,
				u1.username,
				u1.gender,
				u1.display_style_group_id,
				u1.avatar_date,
				u1.gravatar,
				u2.user_id AS mod_user_id,
				u2.username AS mod_username
			FROM xf_siropu_chat_bans AS b
			LEFT JOIN xf_siropu_chat_rooms AS r ON r.room_id = b.ban_room_id
			LEFT JOIN xf_user AS u1 ON u1.user_id = b.ban_user_id
			LEFT JOIN xf_user AS u2 ON u2.user_id = b.ban_author
			' . $where . '
			ORDER BY b.ban_id DESC
		', $limitOptions['limit'], $limitOptions['offset']));
	}
	public function getBannedUsersCount($conditions = array())
	{
		$where = '';

		if (isset($conditions['user_id']))
		{
			$where .= ' WHERE user_id IN (' . $this->_prepareUserIds($conditions['user_id']) . ')';
		}

		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_bans
			' . $where
		);

		return $result['count'];
	}
	public function getAllUserBans($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
		', $userID);
	}
	public function getUserBans($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
			AND ban_type = "ban"
		', $userID);
	}
	public function getUserBansAndKicks($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
			AND ban_type <> "mute"
		', $userID);
	}
	public function getUserKicks($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
			AND ban_type = "kick"
		', $userID);
	}
	public function getUserMutes($userID)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_user_id = ?
			AND ban_type = "mute"
		', $userID);
	}
	public function getBanById($banID)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_bans
			WHERE ban_id = ?
		', $banID);
	}
	public function getReports($conditions = array(), $fetchOptions = array())
	{
		$db = $this->_getDb();
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$where = '';

		if ($conditions)
		{
			if (isset($conditions['report_state']))
			{
				if ($conditions['report_state'] == 'closed')
				{
					$where .= ' WHERE report_state NOT IN ("Open")';
				}
				else
				{
					$where .= ' WHERE report_state = ' . $db->quote($conditions['report_state']);
				}
			}
		}

		$resultArray = $db->fetchAll($this->limitQueryResults('
			SELECT
				r.*,
				m.message_id,
				m.message_text,
				m.message_date,
				rm.room_name,
				u.user_id,
				u.username
			FROM xf_siropu_chat_reports AS r
			LEFT JOIN xf_siropu_chat_messages AS m ON m.message_id = r.report_message_id
			LEFT JOIN xf_siropu_chat_rooms AS rm ON rm.room_id = r.report_room_id
			LEFT JOIN xf_user AS u ON (u.user_id = m.message_user_id OR u.user_id = r.report_message_user_id)
			' . $where . '
			ORDER BY report_id DESC
		', $limitOptions['limit'], $limitOptions['offset']));

		foreach ($resultArray as $key => $val)
		{
			$resultArray[$key]['message_text'] = $val['report_message_text'];
			$resultArray[$key]['message_date'] = $val['report_message_date'];
		}

		return $resultArray;
	}
	public function getReportsCount($conditions = array())
	{
		if (!Siropu_Chat_Helper::userHasPermission('manageReports'))
		{
			return false;
		}

		$db    = $this->_getDb();
		$where = '';

		if ($conditions)
		{
			if (isset($conditions['report_state']))
			{
				if ($conditions['report_state'] == 'closed')
				{
					$where .= ' WHERE report_state NOT IN ("Open")';
				}
				else
				{
					$where .= ' WHERE report_state = ' . $db->quote($conditions['report_state']);
				}
			}
		}

		$result = $db->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_reports
			' . $where
		);

		return $result['count'];
	}
	public function getReportById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_reports
			WHERE report_id = ?
		', $id);
	}
	public function getReportByIdComplete($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT
				r.*,
				m.*,
				rm.room_name,
				u1.user_id,
				u1.username,
				u2.username AS r_username
			FROM xf_siropu_chat_reports AS r
			LEFT JOIN xf_siropu_chat_messages AS m ON m.message_id = r.report_message_id
			LEFT JOIN xf_siropu_chat_rooms AS rm ON rm.room_id = r.report_room_id
			LEFT JOIN xf_user AS u1 ON (u1.user_id = m.message_user_id OR u1.user_id = r.report_message_user_id)
			LEFT JOIN xf_user AS u2 ON u2.user_id = r.report_user_id
			WHERE r.report_id = ?
		', $id);
	}
	public function getReportByMessageId($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_reports
			WHERE report_message_id = ?
		', $id);
	}
	public function changeUsersRoomId($roomId)
	{
		$db = $this->_getDb();
		$db->update('xf_siropu_chat_sessions', array('user_room_id' => 0), 'user_room_id = ' . $db->quote($roomId));
	}
	public function getUserRoomCount($userID)
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_rooms
			WHERE room_user_id = ?
		', $userID);

		return $result['count'];
	}
	public function deleteInactiveRooms($date)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_rooms', 'room_auto_delete = 1 AND room_last_activity > 0 AND room_last_activity <= ' . $db->quote($date));
	}
	public function deleteUsersRoomBan($roomId)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_bans', 'ban_room_id = ' . $db->quote($roomId));
	}
	public function resetTopChatters()
	{
		$this->_getDb()->update('xf_siropu_chat_sessions', array('user_message_count' => 0), 'user_message_count > 0');
	}
	public function deleteUserBans($userId)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_bans', 'ban_user_id = ' . $db->quote($userId));
	}
	public function deleteExpiredBans()
	{
		$this->_getDb()->delete('xf_siropu_chat_bans', 'ban_end > 0 AND  ban_end <=' . time());
	}
	public function deleteOlderMessages($date)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_messages', 'message_date <= ' . $db->quote($date));
	}
	public function deleteOlderReports($date)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_reports', 'report_date <= ' . $db->quote($date));
	}
	public function deleteInactiveSessions()
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_sessions', 'user_is_banned = 0 AND user_last_activity <= ' . $db->quote(strtotime('-30 Days')));
	}
	public function deleteMessagesByRoomId($id)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_chat_messages', 'message_room_id = ' . $db->quote($id) . ($this->_getOptions()->siropu_chat_forum_activity_tab ? ' AND message_type <> "activity"' : ''));

		if ($this->_getOptions()->siropu_chat_delete_messages_delete_reports)
		{
			$db->delete('xf_siropu_chat_reports', 'report_room_id = ' . $db->quote($id));
		}
	}
	public function deleteMessagesByUserId($userId, $roomId = 0)
	{
		$db = $this->_getDb();
		$where = 'message_user_id = ' . $db->quote($userId);

		if (is_numeric($roomId))
		{
			$where .= ' AND message_room_id = ' . $db->quote($roomId);
		}

		$db->delete('xf_siropu_chat_messages', $where);
	}
	public function deleteImagesByUserId($userId)
	{
		$db = $this->_getDb();
		$resultArray = $db->fetchAll('
			SELECT image_name
			FROM xf_siropu_chat_images
			WHERE image_user_id = ?
		', $userId);

		foreach ($resultArray as $row)
		{
			Siropu_Chat_HelperUpload::deleteImage($row['image_name']);
		}

		$db->delete('xf_siropu_chat_images', 'image_user_id = ' . $db->quote($userId));
	}
	public function deleteAllMessages()
	{
		$this->_getDb()->query('TRUNCATE xf_siropu_chat_messages');

		if ($this->_getOptions()->siropu_chat_delete_messages_delete_reports)
		{
			$this->_getDb()->query('TRUNCATE xf_siropu_chat_reports');
		}
	}
	public function deleteForumActivity()
	{
		$this->_getDb()->delete('xf_siropu_chat_messages', 'message_type = "activity"');
	}
	public function getResponseList($enabled = false)
	{
		if ($enabled && !$this->_getOptions()->siropu_chat_bot_responses_enabled)
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_chat_bot_responses
			' . ($enabled ? ' WHERE response_enabled = 1 ' : '') . '
			ORDER BY response_id DESC
		', 'response_id');
	}
	public function getResponseById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_bot_responses
			WHERE response_id = ?
		', $id);
	}
	public function getBotMessages($conditions = array())
	{
		$where = '';

		if (isset($conditions['enabled']))
		{
			$where .= ' WHERE message_enabled = 1';
		}
		if (isset($conditions['date']))
		{
			$where .= $this->_andWhere($where) . 'message_date = ' . (int) $conditions['date'];
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_siropu_chat_bot_messages
			' . $where . '
			ORDER BY message_id DESC
		', 'message_id');
	}
	public function getBotMessageById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_bot_messages
			WHERE message_id = ?
		', $id);
	}
	protected function _prepareUserIds($ids)
	{
		$list = array();

		foreach (array_filter(explode(',', $ids)) as $id)
		{
			$list[] = (int) $id;
		}

		return implode(',', $list);
	}
	protected function _andWhere($where)
	{
		return $where ? ' AND ' : ' WHERE ';
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}
<?php 

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_CronEntry
{
	public static function daily()
    {
		if (self::_getOptions()->siropu_chat_enabled)
		{
			self::deleteOlderMessages();
			self::deleteOlderReports();
			self::deleteInactiveSessions();
		}
	}
	public static function setRoomsLastActivity()
	{
		if (self::_getOptions()->siropu_chat_delete_inactive_rooms)
		{
			foreach (self::_getModel()->getRoomsAutoDelete() as $row)
			{
				$lastMessage = self::_getModel()->getRoomLastMessage($row['room_id']);

				if ($lastMessage || !$row['room_last_activity'])
				{
					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Rooms');
					$dw->setExistingData($row['room_id']);
					$dw->set('room_last_activity', $lastMessage ? $lastMessage['message_date'] : $row['room_date']);
					$dw->save();
				}
			}
		}
	}
	public static function postBotMessages()
	{
		if (self::_getOptions()->siropu_chat_bot_messages_enabled)
		{
			$date = new dateTime('', self::_getCronModel()->getTimeZone());
			$unix = strtotime($date->format('Y-m-d H:i'));

			foreach (self::_getModel()->getBotMessages(array('enabled' => 1, 'date' => $unix)) as $message)
			{
				if ($rules = unserialize($message['message_rules']))
				{
					$rooms = unserialize($message['message_rooms']);

					foreach ($rooms as $room)
					{
						$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
						$dw->bulkSet(array(
							'message_room_id'  => $room,
							'message_text'     => $message['message_text'],
							'message_bot_name' => $message['message_bot_name'],
							'message_type'     => 'bot',
						));
						$dw->save();
					}

					$nextRun = self::_getCronModel()->calculateNextRunTimeAlt($rules);

					$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_BotMessages');
					$dw->setExistingData($message['message_id']);
					$dw->set('message_date', $nextRun);
					if ($nextRun == $message['message_date'])
					{
						$dw->set('message_enabled', 0);
					}
					$dw->save();
				}
			}
		}
	}
	public static function deleteOlderMessages()
    {
		if ($days = self::_getOptions()->siropu_chat_delete_old_messages)
		{
			self::_getModel()->deleteOlderMessages(strtotime("-{$days} Days"));
		}
	}
	public static function deleteOlderReports()
    {
		if ($days = self::_getOptions()->siropu_chat_delete_old_reports)
		{
			self::_getModel()->deleteOlderReports(strtotime("-{$days} Days"));
		}
	}
	public static function deleteInactiveRooms()
    {
		if ($days = self::_getOptions()->siropu_chat_delete_inactive_rooms)
		{
			self::_getModel()->deleteInactiveRooms(strtotime("-{$days} Days"));
			Siropu_Chat_Helper::refreshRoomsCache();
		}
	}
	public static function deleteInactiveSessions()
    {
		return;
		self::_getModel()->deleteInactiveSessions();
	}
	public static function deleteExpiredBans()
	{
		self::_getModel()->deleteExpiredBans();
	}
	protected static function _getModel()
	{
		return XenForo_Model::create('Siropu_Chat_Model');
	}
	protected static function _getCronModel()
	{
		return XenForo_Model::create('XenForo_Model_Cron');
	}
	protected static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}
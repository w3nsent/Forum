<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_HelperActions
{
	public static function getActionsFilename($file = 'messages')
	{
		return XenForo_Helper_File::getExternalDataPath() . "/Siropu/Chat/Actions/$file.json";
	}
	public static function getActionsFileData($file)
	{
		$data = json_decode(@file_get_contents($file), true);
		return is_array($data) ? $data : array();
	}
	public static function saveMessageAction($message, $action)
	{
		$file = self::getActionsFilename();

		if ($data = self::getActionsFileData($file))
		{
			foreach ($data as $roomId => $actions)
			{
				foreach ($actions as $key => $val)
				{
					if ($val['date'] <= time() - 60)
					{
						unset($data[$roomId][$key]);
					}
				}
			}
		}

		if ($message['message_type'] == 'whisper')
		{
			$message['message_text'] = '<i class="siropuChatWhisperAction">' . new XenForo_Phrase('siropu_chat_whisper') . '</i> ' . $message['message_text'];
		}

		$data[$message['message_room_id']][$message['message_id']] = array(
			'author'     => self::_getVisitor()->user_id,
			'action'     => $action,
			'date'       => time(),
			'message'    => ($action == 'edit') ? $message['message_text'] : '',
			'recipients' => ($action == 'edit') ? unserialize($message['message_recipients']) : ''
		);

		@file_put_contents($file, json_encode(array_filter($data)));
	}
	public static function savePruneAction($messages, $all = false)
	{
		$file = self::getActionsFilename();
		$data = $all ? array() : self::getActionsFileData($file);

		foreach ($messages as $message)
		{
			$data[$message['message_room_id']][$message['message_id']] = array(
				'author'     => self::_getVisitor()->user_id,
				'action'     => 'prune',
				'date'       => time(),
				'user_id'    => isset($message['user_id']) ? $message['user_id'] : '',
				'username'   => isset($message['username']) ? $message['username'] : '',
				'recipients' => ''
			);
		}

		@file_put_contents($file, json_encode(array_filter($data)));
	}
	public static function getMessageActions()
	{
		if ($data = self::getActionsFileData(self::getActionsFilename()))
		{
			$userId = self::_getVisitor()->user_id;

			foreach ($data as $roomId => $actions)
			{
				foreach ($actions as $key => $val)
				{
					if (($val['recipients'] && !isset($val['recipients'][$userId]))
						|| ($val['date'] <= time() - 60)
						|| ($val['author'] == $userId))
					{
						unset($data[$roomId][$key]);
					}
				}
			}

			return array_filter($data);
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
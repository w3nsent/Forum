<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ViewPublic_Edit extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base',
			array('view' => $this)));
			
		$message     = $this->_params['message'];
		$messageText = $bbCodeParser->render(XenForo_Helper_String::censorString($message['message_text']));
		$messageType = $message['message_type'];
		$whisper     = '';

		if ($messageType == 'whisper')
		{
			$recipients = unserialize($message['message_recipients']);
			unset($recipients[$message['message_user_id']]);
			$message['message_recipients'] = $recipients;

			$whisper = $this->createTemplateObject('siropu_chat_whisper', array('message' => $message));
		}

		$this->_params['message']['message_text'] = $messageText;
		Siropu_Chat_HelperActions::saveMessageAction($this->_params['message'], 'edit');

		return Xenforo_ViewRenderer_Json::jsonEncodeForOutput(array(
			'messageEdited' => $whisper . $messageText,
			'messageId'     => $message['message_id'],
			'messageRoomId' => $message['message_room_id'],
		));
	}
}
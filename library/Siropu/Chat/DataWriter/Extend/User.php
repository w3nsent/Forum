<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Extend_User extends XFCP_Siropu_Chat_DataWriter_Extend_User
{
	protected function _postSave()
	{
		$options = XenForo_Application::get('options');
		$displayedNotification = $options->siropu_chat_displayed_notifications;

		if ($displayedNotification['welcome']
			&& ($this->isInsert() && $this->get('user_state') == 'valid'
				|| ($this->isUpdate()
					&& $this->isChanged('user_state')
					&& $this->getExisting('user_state') == 'email_confirm'
					&& $this->get('user_state') == 'valid')))
		{
			$writer = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
			$writer->bulkSet(array(
				'message_user_id' => $this->get('user_id'),
				'message_text'    => new XenForo_Phrase('siropu_chat_bot_new_user', array('name' => '[USER=' . $this->get('user_id') . ']' . $this->get('username') . '[/USER]')),
				'message_type'    => 'activity'
			));
			$writer->save();
		}

		return parent::_postSave();
	}
	protected function _postDelete()
	{
		$db = $this->_db;
		$userId = $this->get('user_id');
		$userIdQuoted = $db->quote($userId);

		$db->delete('xf_siropu_chat_sessions', "user_id = $userIdQuoted");
		$db->delete('xf_siropu_chat_messages', "message_user_id = $userIdQuoted");
		$db->delete('xf_siropu_chat_bans', "ban_user_id = $userIdQuoted");

		return parent::_postDelete();
	}
}
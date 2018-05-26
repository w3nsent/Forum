<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ControllerAdmin_BotMessages extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$messages = $this->_getModel()->getBotMessages();

		foreach ($messages as $key => $val)
		{
			$rules   = unserialize($val['message_rules']);
			$nextRun = $this->_getCronModel()->calculateNextRunTimeAlt($rules);
			$date    = new DateTime('@' . $nextRun, $this->_getCronModel()->getTimeZone());

			$messages[$key]['message_date'] = $date->format('M d, Y - g:i A');
		}

		$viewParams = array(
			'messages' => $messages
		);

		return $this->responseView('', 'siropu_chat_bot_message_list', $viewParams);
	}
	public function actionAdd()
	{
		$viewParams = array(
			'message' => array(
				'message_rules' => array(
					'day_type' => 'dow',
					'dow'      => array(-1),
					'hours'    => array(0),
					'minutes'  => array(0),
				)
			)
		);

		return $this->_getAddEditMessage($viewParams);
	}
	public function actionEdit()
	{
		$message = $this->_getMessageOrError();

		$message['message_rules'] = unserialize($message['message_rules']);
		$message['message_rooms'] = unserialize($message['message_rooms']);

		$viewParams = array(
			'message' => $message
		);

		return $this->_getAddEditMessage($viewParams);
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'message_title'    => XenForo_Input::STRING,
			'message_text'     => XenForo_Input::STRING,
			'message_bot_name' => XenForo_Input::STRING,
			'message_rooms'    => XenForo_Input::ARRAY_SIMPLE,
			'message_rules'    => XenForo_Input::ARRAY_SIMPLE
		));

		if (empty($dwData['message_rooms']))
		{
			$dwData['message_rooms'] = array(0);
		}

		if (empty($dwData['message_rules']['date']) && empty($dwData['message_rules']['dow']))
		{
			$dwData['message_rules']['dow'] = array(-1);
		}
		if (empty($dwData['message_rules']['hours']))
		{
			$dwData['message_rules']['hours'] = array(0);
		}
		if (empty($dwData['message_rules']['minutes']))
		{
			$dwData['message_rules']['minutes'] = array(0);
		}

		$dwData['message_date']  = $this->_getCronModel()->calculateNextRunTimeAlt($dwData['message_rules']);
		$dwData['message_rooms'] = serialize($dwData['message_rooms']);
		$dwData['message_rules'] = serialize($dwData['message_rules']);

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_BotMessages');
		if ($id = $this->_getMessageID())
		{
			$dw->setExistingData($id);
		}
		$dw->bulkSet($dwData);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('chat-bot-messages') . $this->getLastHash($dw->get('message_id'))
		);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Siropu_Chat_DataWriter_BotMessages', 'message_id',
				XenForo_Link::buildAdminLink('chat-bot-messages')
			);
		}

		$viewParams['message'] = $this->_getMessageOrError();
		return $this->responseView('', 'siropu_chat_bot_message_delete_confirm', $viewParams);
	}
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getModel()->getBotMessages(),
			'Siropu_Chat_DataWriter_BotMessages',
			'chat-bot-messages',
			'message_enabled');
	}
	protected function _getAddEditMessage($viewParams = array())
	{
		$viewParams = array_merge($viewParams, array(
			'rooms' => $this->_getModel()->getAllRooms()
		));

		return $this->responseView('', 'siropu_chat_bot_message_edit', $viewParams);
	}
	protected function _getMessageOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getMessageID();
		}

		if ($info = $this->_getModel()->getBotMessageById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_chat_bot_message_not_found'), 404));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
	protected function _getCronModel()
	{
		return $this->getModelFromCache('XenForo_Model_Cron');
	}
	protected function _getHelper()
	{
		return $this->getHelper('Siropu_Chat_Helper');
	}
	protected function _getOptions()
	{
		return XenForo_Application::get('options');
	}
	protected function _getMessageID()
	{
		return $this->_input->filterSingle('message_id', XenForo_Input::UINT);
	}
}
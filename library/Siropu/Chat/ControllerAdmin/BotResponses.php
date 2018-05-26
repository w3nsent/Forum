<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ControllerAdmin_BotResponses extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$responses = $this->_getModel()->getResponseList();

		foreach ($responses as $key => $val)
		{
			$responses[$key]['response_settings'] = unserialize($val['response_settings']);
		}

		$viewParams = array(
			'responses' => $responses
		);

		return $this->responseView('', 'siropu_chat_bot_response_list', $viewParams);
	}
	public function actionAdd()
	{
		return $this->_getAddEditResponse();
	}
	public function actionEdit()
	{
		$response = $this->_getResponseOrError();

		$response['response_rooms']       = unserialize($response['response_rooms']);
		$response['response_user_groups'] = unserialize($response['response_user_groups']);
		$response['response_settings']    = unserialize($response['response_settings']);

		$viewParams = array(
			'response' => $response
		);

		return $this->_getAddEditResponse($viewParams);
	}
	public function actionSave()
	{
		$this->_assertPostOnly();

		$dwData = $this->_input->filter(array(
			'response_keyword'     => XenForo_Input::STRING,
			'response_message'     => XenForo_Input::STRING,
			'response_bot_name'    => XenForo_Input::STRING,
			'response_description' => XenForo_Input::STRING,
			'response_rooms'       => XenForo_Input::ARRAY_SIMPLE,
			'response_user_groups' => XenForo_Input::ARRAY_SIMPLE,
			'response_type'        => XenForo_Input::UINT,
			'response_settings'    => XenForo_Input::ARRAY_SIMPLE,
		));

		if (!$dwData['response_type'])
		{
			$dwData['response_type'] = 2;
		}

		$dwData['response_rooms']       = serialize($dwData['response_rooms']);
		$dwData['response_user_groups'] = serialize($dwData['response_user_groups']);
		$dwData['response_settings']    = serialize($dwData['response_settings']);

		$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_BotResponses');
		if ($id = $this->_getResponseID())
		{
			$dw->setExistingData($id);
		}
		$dw->bulkSet($dwData);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('chat-bot-responses') . $this->getLastHash($dw->get('response_id'))
		);
	}
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Siropu_Chat_DataWriter_BotResponses', 'response_id',
				XenForo_Link::buildAdminLink('chat-bot-responses')
			);
		}

		$viewParams['response'] = $this->_getResponseOrError();
		return $this->responseView('', 'siropu_chat_bot_response_delete_confirm', $viewParams);
	}
	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getModel()->getResponseList(),
			'Siropu_Chat_DataWriter_BotResponses',
			'chat-bot-responses',
			'response_enabled');
	}
	protected function _getAddEditResponse($viewParams = array())
	{
		$viewParams = array_merge($viewParams, array(
			'rooms'      => $this->_getModel()->getAllRooms(),
			'userGroups' => $this->getModelFromCache('XenForo_Model_UserGroup')->getAllUserGroups()
		));

		return $this->responseView('', 'siropu_chat_bot_response_edit', $viewParams);
	}
	protected function _getResponseOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_getResponseID();
		}

		if ($info = $this->_getModel()->getResponseById($id))
		{
			return $info;
		}

		throw $this->responseException($this->responseError(new XenForo_Phrase('siropu_chat_bot_response_not_found'), 404));
	}
	protected function _getModel()
	{
		return $this->getModelFromCache('Siropu_Chat_Model');
	}
	protected function _getResponseID()
	{
		return $this->_input->filterSingle('response_id', XenForo_Input::UINT);
	}
}
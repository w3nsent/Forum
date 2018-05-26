<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ControllerPublic_Extend_SpamCleaner extends XFCP_Siropu_Chat_ControllerPublic_Extend_SpamCleaner
{
	public function actionIndex()
	{
		if ($this->isConfirmedPost() && $this->_input->filterSingle('delete_chat_messages', XenForo_Input::UINT))
		{
			$userId    = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
			$chatModel = $this->getModelFromCache('Siropu_Chat_Model');

			$chatModel->deleteMessagesByUserId($userId, false);
			$chatModel->deleteImagesByUserId($userId);

			if ($chatSession = $chatModel->getSession($userId))
			{
				$dw = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Sessions');
				$dw->setExistingData($userId);
				if ($this->_input->filterSingle('ban_user', XenForo_Input::UINT))
				{
					$dw->delete();
				}
				else
				{
					$dw->set('user_last_active', 0);
					$dw->save();
				}
			}
		}

		return parent::actionIndex();
	}
}
<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ViewPublic_Quote extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return Xenforo_ViewRenderer_Json::jsonEncodeForOutput(array(
			'quotedMessage' => '[QUOTE=' . $this->_params['message']['username'] . ']' . XenForo_Helper_String::bbCodeStrip($this->_params['message']['message_text'], true) . '[/QUOTE]'
		));
	}
}
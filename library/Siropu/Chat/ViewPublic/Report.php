<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_ViewPublic_Report extends XenForo_ViewPublic_Base
{
	public function renderHtml()
    {
        $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base',
			array('view' => $this)));

		$this->_params['report']['message_text'] = $bbCodeParser->render($this->_params['report']['report_message_text']);
		$this->_params['report']['message_date'] = $this->_params['report']['report_message_date'];

		foreach ($this->_params['messagesBefore'] as $key => $val)
		{
			if ($val['message_type'] == 'whisper')
			{
				$recipients = unserialize($val['message_recipients']);
				$this->_params['messagesBefore'][$key]['message_recipients'] = implode(', ', $recipients);
			}
		}

        XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($this->_params['messagesBefore'], $bbCodeParser);
    }
}
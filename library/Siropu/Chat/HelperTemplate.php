<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_HelperTemplate
{
	public static function helperRoomLastMessageId($messages, $inverse = 0)
	{
		if (!$messages)
		{
			return 0;
		}

		$lastMessage = $inverse ? current($messages) : end($messages);
		return $lastMessage['message_id'];
	}
}
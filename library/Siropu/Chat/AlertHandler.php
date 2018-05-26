<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_AlertHandler extends XenForo_AlertHandler_DiscussionMessage
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		return $model->getModelFromCache('Siropu_Chat_Model')->getMessageByIds($contentIds);
	}
}
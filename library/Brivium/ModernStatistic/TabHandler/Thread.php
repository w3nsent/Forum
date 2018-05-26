<?php

class Brivium_ModernStatistic_TabHandler_Thread extends Brivium_ModernStatistic_TabHandler_Abstract
{
	protected $_tabId = 'thread';

	public function getTabName(array $content)
	{
		return new XenForo_Phrase('thread');
	}
}

<?php

abstract class Brivium_ModernStatistic_TabHandler_Abstract
{
	protected $_tabId = '';

	abstract public function getTabName(array $content);
}

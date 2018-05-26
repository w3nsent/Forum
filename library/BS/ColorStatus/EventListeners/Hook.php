<?php

class BS_ColorStatus_EventListeners_Hook
{
	public static function render($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if ($hookName === 'status_color_account')
		{
			$visitor = XenForo_Visitor::getInstance();
			$hasPermission = $visitor->hasPermission('general', 'canUseColorStatus');
			$hookParams = array(
				'canUseColorStatus' => $hasPermission,
				'visitor' => $visitor->toArray()
				);
			$contents .= $template->create('status_color_account', $hookParams);
		}
	}
}
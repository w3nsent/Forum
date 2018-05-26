<?php

class BS_BRMSStick_Listener
{
	public static function Thread($class, array &$extend)
	{
		$extend[] = 'BS_BRMSStick_ControllerPublic_Thread';
	}

	public static function ModernStatistic($class, array &$extend)
	{
		$extend[] = 'BS_BRMSStick_ControllerPublic_ModernStatistic';
	}		
}
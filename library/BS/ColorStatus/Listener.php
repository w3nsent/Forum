<?php

class BS_ColorStatus_Listener
{
	const AddonNameSpace = 'BS_ColorStatus_';

	public static function load_class($class, array &$extend)
	{
		$extend[] = self::AddonNameSpace . $class;
	}
}
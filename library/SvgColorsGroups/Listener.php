<?php
class SvgColorsGroups_Listener
{
    public static function loadClassModel($class, array &$extend)
    {
        if($class == 'XenForo_Model_Thread')
        {
        	$extend[] = 'SvgColorsGroups_Thread';
        }
        else if($class == 'XenForo_Model_Node')
        {
            $extend[] = 'SvgColorsGroups_Node';
        }
    }

}
?>
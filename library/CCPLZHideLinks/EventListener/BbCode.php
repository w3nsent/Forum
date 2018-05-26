<?php

class CCPLZHideLinks_EventListener_BbCode
{
  public static function listen($class, array &$extend)
  {
    if ($class == 'XenForo_BbCode_Formatter_Base')
    {
      $extend[] = 'CCPLZHideLinks_BbCode_Formatter_Base';
    }
  }
}

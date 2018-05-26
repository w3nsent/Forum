<?php

class phc_ACPPlus_Extend_XenForo_Model_AdminNavigation extends XFCP_phc_ACPPlus_Extend_XenForo_Model_AdminNavigation
{
	public function filterUnviewableAdminNavigation(array $navigation)
	{
        foreach($navigation AS $key => $nav)
        {
            if($key == 'acpp_denybyhtaccess' && !phc_ACPPlus_Helper_ACPPlus::getWebServer())
            {
                unset($navigation[$key]);
            }
        }

		return parent::filterUnviewableAdminNavigation($navigation);
	}
}
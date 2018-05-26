<?php

class phc_ACPPlus_Extend_XenForo_Model_AdminTemplate extends XFCP_phc_ACPPlus_Extend_XenForo_Model_AdminTemplate
{
	public function prepareTemplateConditions(array $conditions, array &$fetchOptions)
	{
	    $res = parent::prepareTemplateConditions($conditions, $fetchOptions);
        $sqlConditions = array($res);

        if(!empty($GLOBALS['acpp_admintemplate_filter_addon']))
        {
            $db = $this->_getDb();

            $addonString = $GLOBALS['acpp_admintemplate_filter_addon'];
            $sqlConditions[] = ' template.addon_id = ' . $db->quote($addonString);
        }

		return $this->getConditionsForClause($sqlConditions);
	}
}
<?php

class phc_ACPPlus_Extend_XenForo_ViewAdmin_AddOn_Upgrade extends XFCP_phc_ACPPlus_Extend_XenForo_ViewAdmin_AddOn_Upgrade
{
    public function renderHtml()
    {
        if(isset($this->_params['addOn']['note']))
        {
            $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base'));
            $this->_params['addOn']['note'] = new XenForo_BbCode_TextWrapper($this->_params['addOn']['note'], $bbCodeParser);
        }
	}
}
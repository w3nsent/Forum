<?php

class phc_ACPPlus_Extend_XenForo_DataWriter_OptionGroup extends XFCP_phc_ACPPlus_Extend_XenForo_DataWriter_OptionGroup
{
    protected function _postSave()
    {
        $res = parent::_postSave();

        if(!empty($GLOBALS['acppGroupUpdate']))
        {
            $db = $this->_db;
            $db->update('xf_option_group',
                array('default_display_order' => $this->get('display_order')),
                'group_id = ' . $db->quote($this->get('group_id'))
            );
        }

        return $res;
    }
}
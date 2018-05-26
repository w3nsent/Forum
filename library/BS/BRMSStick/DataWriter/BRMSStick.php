<?php

class BS_BRMSStick_DataWriter_BRMSStick extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_brmsstick_links' => array(
                'link_id'
                    => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'link' 
                    => array('type' => self::TYPE_STRING, 'maxLength' => 700),
                'title' 
                    => array('type' => self::TYPE_STRING, 'maxLength' => 700)
                )
            );
    }

    protected function _getExistingData($data)
    {
        if (!$linkId = $this->_getExistingPrimaryKey($data))
        {
            return false;
        }

        return array('xf_brmsstick_links' => $this->_getBSModel()->getLinkDataById($linkId));
    }

    protected function _getUpdateCondition($tableName)
    {
        return 'link_id = ' . $this->_db->quote($this->getExisting('link_id'));
    }

    protected function _getBSModel()
    {
        return $this->getModelFromCache('BS_BRMSStick_Model');
    }
}
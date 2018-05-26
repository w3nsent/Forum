<?php

class BS_BRMSStick_DataWriter_Thread extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_thread' => array(
                'thread_id'
                    => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'brms_stick' 
                    => array('type' => self::TYPE_UINT, 'default' => 0)
                )
            );
    }

    protected function _getExistingData($data)
    {
        if (!$threadId = $this->_getExistingPrimaryKey($data))
        {
            return false;
        }

        return array('xf_thread' => $this->_getThreadModel()->getThreadById($threadId));
    }

    protected function _getUpdateCondition($tableName)
    {
        return 'thread_id = ' . $this->_db->quote($this->getExisting('thread_id'));
    }

    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }
}
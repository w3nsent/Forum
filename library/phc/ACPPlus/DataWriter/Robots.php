<?php

class phc_ACPPlus_DataWriter_Robots extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'phc_acpp_spiders'      => array(
				'spider_id' 		=> array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'robot_id'			=> array('type' => self::TYPE_STRING, 'required' => true, 'verification' => array('$this', '_verifyRobotId')),
				'title'             => array('type' => self::TYPE_STRING, 'required' => true),
				'contact'		    => array('type' => self::TYPE_STRING),
				'active'	        => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'block'	            => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'routes'	        => array('type' => self::TYPE_STRING, 'default' => ''),
				'routes_whitelist'	=> array('type' => self::TYPE_STRING, 'default' => ''),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if(!$spiderId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('phc_acpp_spiders' => $this->_getACPPlusModel()->fetchRobotById($spiderId));
	}

    protected function _getUpdateCondition($tableName)
    {
        return 'spider_id = ' . $this->_db->quote($this->getExisting('spider_id'));
    }

    protected function _verifyRobotId($robotId)
    {
        $robot = $this->_getACPPlusModel()->fetchRobotByRobotId($robotId);

        if($this->isInsert() || $this->get('spider_id') != $robot['spider_id'])
        {
            if($this->_getACPPlusModel()->fetchRobotByRobotId($robotId))
            {
                $this->error(new XenForo_Phrase('acpp_this_robot_id_is_already_used'), 'robot_id');
                return false;
            }
        }

        return true;
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
	protected function _getACPPlusModel()
	{
		return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
	}
}
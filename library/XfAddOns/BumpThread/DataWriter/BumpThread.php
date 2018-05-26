<?php

class XfAddOns_BumpThread_DataWriter_BumpThread extends XenForo_DataWriter
{

	/**
	 * Return the list of fields in the table
	 * These are all the fields that the datawriter will attempt to update
	 * @return array
	 */
	protected function _getFields()
	{
		$fields = array();
		$fields['xfa_bump_thread']['bump_id'] = array(
			'type' => self::TYPE_UINT,
			'required' => true,
			'autoIncrement' => true
		);		
		$fields['xfa_bump_thread']['user_id'] = array(
			'type' => self::TYPE_UINT,
			'required' => true
		);		
		$fields['xfa_bump_thread']['last_bump_thread'] = array(
			'type' => self::TYPE_UINT,
			'required' => true
		);
		$fields['xfa_bump_thread']['last_bump_date'] = array(
			'type' => self::TYPE_UINT,
			'required' => true,
			'default' => XenForo_Application::$time
		);
		return $fields;
	}
	
	/**
	 * Existing data is needed for the updates. This query will fetch whatever the data
	 * currently is in the database
	 *
	 * @param array $data	An array of the data currently configured in the data writer
	 * @return an array with the existing data, all tables included
	 */
	protected function _getExistingData($key)
	{
		if ($key)
		{
			$db = XenForo_Application::getDb();
			$data = $db->fetchRow("SELECT * FROM xfa_bump_thread WHERE bump_id = ?", array( $key ));
			return array( 'xfa_bump_thread' => $data );
		}
		return null;
	}
	
	/**
	 * Returns the query part used in the where condition for updating the table. This must match the primary key
	 *
	 * @param string $tableName		We ignore this, the data writer only supports one table
	 * @return						 the where part for updating the table
	 */
	protected function _getUpdateCondition($tableName)
	{
		return ' bump_id = ' . $this->_db->quote($this->getExisting('bump_id'));
	}

}
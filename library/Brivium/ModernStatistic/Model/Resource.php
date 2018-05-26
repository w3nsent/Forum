<?php

class Brivium_ModernStatistic_Model_Resource extends XFCP_Brivium_ModernStatistic_Model_Resource
{
	public function prepareResourceConditions(array $conditions, array &$fetchOptions)
	{
		$result = parent::prepareResourceConditions($conditions, $fetchOptions);

		$sqlConditions = array($result);
		$db = $this->_getDb();

		if (!empty($conditions['resource_state']))
		{
			if (is_array($conditions['resource_state']))
			{
				$sqlConditions[] = 'resource.resource_state IN (' . $db->quote($conditions['resource_state']) . ')';
			}
			else
			{
				$sqlConditions[] = 'resource.resource_state = ' . $db->quote($conditions['resource_state']);
			}
		}
		if (count($sqlConditions) > 1) {
			return $this->getConditionsForClause($sqlConditions);
		} else {
			return $result;
		}
	}
}

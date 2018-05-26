<?php
class Brivium_AdvancedReputationSystem_Model_PresetDelete extends XenForo_Model
{
	public function getPresetDeleteById($presetDeleteId = 0, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePresetDeleteFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchRow( '
			SELECT preset_delete.*
				' . $sqlClauses['selectFields'] . '
			FROM xf_brivium_preset_delete AS preset_delete
				' . $sqlClauses['joinTables'] . '
			WHERE  preset_delete_id = ?
		', array($presetDeleteId));
	}
	
	public function getPresetDeletes(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePresetDeleteConditions($conditions, $fetchOptions);
	
		$sqlClauses = $this->preparePresetDeleteFetchOptions($fetchOptions);
	
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
				'
				SELECT preset_delete.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_brivium_preset_delete AS preset_delete
					' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'preset_delete_id');
	
	}
	
	public function countPresetDeletes(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePresetDeleteConditions($conditions, $fetchOptions);
	
		$sqlClauses = $this->preparePresetDeleteFetchOptions($fetchOptions);
	
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_brivium_preset_delete AS preset_delete
			' . $sqlClauses['joinTables'] . '
			WHERE ' . $whereConditions . '
		');
	}
	
	public function preparePresetDeleteConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		
		if (!empty($conditions['preset_delete_id']))
		{
			if (is_array($conditions['preset_delete_id']))
			{
				$sqlConditions[] = 'preset_delete.preset_delete_id IN (' . $db->quote($conditions['preset_delete_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'preset_delete.preset_delete_id = ' . $db->quote($conditions['preset_delete_id']);
			}
		}
		
		if (!empty($conditions['reason']))
		{
			if (is_array($conditions['reason']))
			{
				$sqlConditions[] = 'preset_delete.reason LIKE ' . XenForo_Db::quoteLike($conditions['reason'][0], $conditions['reason'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'preset_delete.reason LIKE ' . XenForo_Db::quoteLike($conditions['reason'], 'lr', $db);
			}
		}

		
		if(isset($conditions['active']))
		{
			$sqlConditions[] = 'preset_delete.active = ' .$db->quote(!empty($conditions['active'])?1:0);
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function preparePresetDeleteFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		
		$orderBy = '';
		
		return array(
				'selectFields' => $selectFields,
				'joinTables'   => $joinTables,
				'orderClause'  => ($orderBy ? "ORDER BY $orderBy" : '')
		);
	}
}
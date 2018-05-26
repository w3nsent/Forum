<?php
class Brivium_AdvancedReputationSystem_Model_PresetComment extends XenForo_Model
{
	public function getPresetCommentById($presetCommentId = 0, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePresetCommentFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchRow( '
			SELECT preset_comment.*
				' . $sqlClauses['selectFields'] . '
			FROM xf_brivium_preset_reputation_message AS preset_comment
				' . $sqlClauses['joinTables'] . '
			WHERE  preset_id = ?
		', array($presetCommentId));
	}
	
	public function getPresetComments(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePresetCommentConditions($conditions, $fetchOptions);
	
		$sqlClauses = $this->preparePresetCommentFetchOptions($fetchOptions);
	
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
				'
				SELECT preset_comment.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_brivium_preset_reputation_message AS preset_comment
					' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'preset_id');
	
	}
	
	public function countPresetComments(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePresetCommentConditions($conditions, $fetchOptions);
	
		$sqlClauses = $this->preparePresetCommentFetchOptions($fetchOptions);
	
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_brivium_preset_reputation_message AS preset_comment
			' . $sqlClauses['joinTables'] . '
			WHERE ' . $whereConditions . '
		');
	}
	
	public function preparePresetCommentConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		
		if (!empty($conditions['preset_id']))
		{
			if (is_array($conditions['preset_id']))
			{
				$sqlConditions[] = 'preset_comment.preset_id IN (' . $db->quote($conditions['preset_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'preset_comment.preset_id = ' . $db->quote($conditions['preset_id']);
			}
		}
		
		if (!empty($conditions['title']))
		{
			if (is_array($conditions['title']))
			{
				$sqlConditions[] = 'preset_comment.title LIKE ' . XenForo_Db::quoteLike($conditions['title'][0], $conditions['title'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'preset_comment.title LIKE ' . XenForo_Db::quoteLike($conditions['title'], 'lr', $db);
			}
		}

		
		if(isset($conditions['active']))
		{
			$sqlConditions[] = 'preset_comment.active = ' .$db->quote(!empty($conditions['active'])?1:0);
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function preparePresetCommentFetchOptions(array $fetchOptions)
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
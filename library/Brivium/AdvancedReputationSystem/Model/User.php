<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_Model_User extends XFCP_Brivium_AdvancedReputationSystem_Model_User
{
	public function prepareUserConditions(array $conditions, array &$fetchOptions)
	{
		$result = parent::prepareUserConditions($conditions, $fetchOptions);
		$sqlConditions = array($result);
		$db = $this->_getDb();
		
		if (!empty($conditions['reputation_count']) && is_array($conditions['reputation_count']))
		{
			$sqlConditions[] = $this->getCutOffCondition("user.reputation_count", $conditions['reputation_count']);
		}
		return $this->getConditionsForClause($sqlConditions);
	}
	public function prepareUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'reputation_count' => 'reputation_count',
		);
		
		$parent = $this->getOrderByClause($choices, $fetchOptions, '');
		
		if($parent)
			return $parent;
		else
			return parent::prepareUserOrderOptions($fetchOptions, $defaultOrderSql);
	}
}
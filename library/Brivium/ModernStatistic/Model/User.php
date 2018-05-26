<?php

class Brivium_ModernStatistic_Model_User extends XFCP_Brivium_ModernStatistic_Model_User
{
	public function prepareUserFetchOptions(array $fetchOptions)
	{
		$result = parent::prepareUserFetchOptions($fetchOptions);
		$selectFields = !empty($result['selectFields'])?$result['selectFields']:'';
		$joinTables = !empty($result['joinTables'])?$result['joinTables']:'';

		if (!empty($fetchOptions['BRMS_fetch_banned_user']))
		{
			$selectFields .= ',
					user_ban.ban_date , user_ban.user_reason ,  user_ban.end_date';
				$joinTables .= '
					LEFT JOIN xf_user_ban AS user_ban ON
						(user_ban.user_id = user.user_id)';
		}
		$result['joinTables'] = $joinTables;
		$result['selectFields'] = $selectFields;

		return $result;
	}

	public function prepareUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$modernStatisticModel = $this->_getModernStatisticModel();
		$choices = array(
			'ban_date' => 'user_ban.ban_date',
		);
		if($modernStatisticModel->checkBriviumCreditsAddon()){
			if($modernStatisticModel->checkBriviumCreditsAddon() < 1000000){
				$choices['credits'] = 'user.`credits`';
			}else{
				$currencies = XenForo_Application::get('brcCurrencies')->getCurrencies();
				foreach($currencies as $currency){
					$choices[$currency['column']] = 'user.`'.$currency['column'].'`';
				}
			}
		}

		$result = $this->getOrderByClause($choices, $fetchOptions, '');
		if($result){
			return $result;
		}else{
			return parent::prepareUserOrderOptions($fetchOptions, $defaultOrderSql);
		}
	}

	protected function _getModernStatisticModel()
	{
		return $this->getModelFromCache('Brivium_ModernStatistic_Model_ModernStatistic');
	}
}

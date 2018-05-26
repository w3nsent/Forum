<?php
class VietXfAdvStats_XenForo_Model_User extends XFCP_VietXfAdvStats_XenForo_Model_User {
	public function prepareUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '') {
		$choices = array(
			'trophy_points' => 'user.trophy_points',
			'like_count' => 'user.like_count',
		);
		
		if (!empty($fetchOptions['order']) AND isset($choices[$fetchOptions['order']])) {
			return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
		} else {
			return parent::prepareUserOrderOptions($fetchOptions, $defaultOrderSql);
		}
	}
}
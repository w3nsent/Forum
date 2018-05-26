<?php

/**
* Data writer for product type.
*
* @package Brivium_Store
*/
class Brivium_ModernStatistic_DataWriter_ModernStatistic extends XenForo_DataWriter
{
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_brivium_modern_statistic' => array(
				'modern_statistic_id'  => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'title'                => array('type' => self::TYPE_STRING, 'required' => true),
				'tab_data'             => array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyTabData')),
				'position'             => array('type' => self::TYPE_UNKNOWN, 'required' => true),
				'control_position'     => array('type' => self::TYPE_STRING, 'default' => 'brmsLeftTabs'),
				'item_limit'           => array('type' => self::TYPE_UNKNOWN, 'verification' => array('$this', '_verifyItemLimit')),
				'auto_update'          => array('type' => self::TYPE_UINT, 'default' => 0),
				'style_display'        => array('type' => self::TYPE_STRING, 'default' => ''),
				'preview_tooltip'      => array('type' => self::TYPE_STRING, 'default' => 'custom_preview'),
				'enable_cache'         => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'cache_time'           => array('type' => self::TYPE_UINT, 'default' => 1),
				'thread_cutoff'        => array('type' => self::TYPE_UINT, 'default' => 30),
				'usename_marke_up'     => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'show_thread_prefix'   => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'show_resource_prefix' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'allow_change_layout'  => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'allow_manual_refresh' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'load_fisrt_tab'       => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'modern_criteria'      => array('type' => self::TYPE_UNKNOWN, 'required' => true,
					'verification'     => array('$this', '_verifyCriteria')
				),
				'style_settings'       => array('type' => self::TYPE_SERIALIZED, 'default' => ''),
				'allow_user_setting'   => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'active'               => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
			)
		);
	}

	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|false
	*/
	protected function _getExistingData($data)
	{
		if (!$linkId = $this->_getExistingPrimaryKey($data, 'modern_statistic_id'))
		{
			return false;
		}
		return array('xf_brivium_modern_statistic' => $this->_getModernStatisticModel()->getModernStatisticById($linkId));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'modern_statistic_id = ' . $this->_db->quote($this->getExisting('modern_statistic_id'));
	}


	protected function _verifyCriteria(&$criteria)
	{
		if ($criteria === null)
		{
			$criteria = '';
			return true;
		}

		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($criteria, $this, 'modern_criteria');
	}
	/**
	 * Verification method for tab data
	 *
	 * @param string $tabData
	 */
	protected function _verifyTabData(&$tabData)
	{
		if ($tabData === null || !$tabData)
		{
			$this->error(new XenForo_Phrase('BRMS_please_select_at_least_one_tab'), 'tab_data');
			return false;
		}

		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($tabData, $this, 'tab_data');
	}

	protected function _verifyItemLimit(&$itemLimit)
	{
		if ($itemLimit === null || !$itemLimit)
		{
			$this->error(new XenForo_Phrase('BRMS_please_enter_valid_item_limit'), 'item_limit');
			return false;
		}
		if(!empty($itemLimit['value'])){
			foreach ($itemLimit['value'] as $number) {
				if (!empty($number) && $number > 0) {
					$output[] = intval($number);
				}
			}
			asort($output);
			$itemLimit['value'] = array_values(array_unique($output));
		}
		if(!empty($itemLimit['default'])){
			$itemLimit['default'] = intval($itemLimit['default']);
		}else{
			$itemLimit['default'] = 15;
		}
		if(!empty($itemLimit['enabled']) && empty($itemLimit['value'])){
			$this->error(new XenForo_Phrase('BRMS_must_have_value_for_item_limit'), 'item_limit');
			return false;
		}

		return XenForo_DataWriter_Helper_Denormalization::verifySerialized($itemLimit, $this, 'item_limit');
	}

	/**
	 * Update notified user's total number of unread alerts
	 */
	protected function _postSave()
	{
		$this->_getModernStatisticModel()->rebuildModernStatisticCaches();
		$this->_cleanCaches();
	}

	/**
	 * Post-delete behaviors.
	 */
	protected function _postDelete()
	{
		$this->_getModernStatisticModel()->rebuildModernStatisticCaches();
		$this->_cleanCaches();
	}
	protected function _cleanCaches()
	{
		$updateData = array(
			'cache_html'	=>	'',
			'cache_params'	=>	'',
			'tab_cache_htmls'	=>	'',
			'tab_cache_params'	=>	'',
			'last_update'	=>	0,
		);
		$this->_db->update(
			'xf_brivium_modern_cache',
			$updateData,
			'`modern_statistic_id` = ' . $this->_db->quote($this->get('modern_statistic_id'))
		);
	}

	protected function _getModernStatisticModel()
	{
		return $this->getModelFromCache('Brivium_ModernStatistic_Model_ModernStatistic');
	}
}

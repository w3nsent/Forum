<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_Model_Stats extends Xenforo_Model
{
	public function insertDailyStats($data)
	{
		$db = $this->_getDb();

		$values = array();
		foreach ($data as $key => $val)
		{
			$values[] = $db->quote($val);
		}

		$db->query('
			INSERT INTO xf_siropu_ads_manager_stats_daily
				(id, date, ad_id, position, view_count, click_count)
			VALUES
				(' . implode(',', $values) . ')
			ON DUPLICATE KEY UPDATE
				view_count = VALUES(view_count) + view_count,
				click_count = VALUES(click_count) + click_count
		');
	}
	public function getDailyStats($id, $conditions, $fetchOptions)
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$select = 'date, ad_id, position, view_count AS viewCount, click_count AS clickCount';

		if ($conditions['group_by'] || $conditions['chart'])
		{
			$select = '*, SUM(view_count) AS viewCount, SUM(click_count) AS clickCount';
		}

		return $this->_getDb()->fetchAll($this->limitQueryResults('
			SELECT ' . $select . '
			FROM xf_siropu_ads_manager_stats_daily
			WHERE ad_id = ' . $this->_getDb()->quote($id) .
			$this->prepareWhereConditions($conditions) .
			$this->prepareGroupByConditions($conditions) . '
			ORDER BY date DESC
		', $limitOptions['limit'], $limitOptions['offset']));
	}
	public function getDailyStatsCount($id, $conditions)
	{
		return count($this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_stats_daily
			WHERE ad_id = ' . $this->_getDb()->quote($id) .
			$this->prepareWhereConditions($conditions) .
			$this->prepareGroupByConditions($conditions)
		));
	}
	public function getDailyStatsPositions($id)
	{
		$resultArray = $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_stats_daily
			WHERE ad_id = ' . $this->_getDb()->quote($id) . '
			GROUP BY position
		');

		return $this->_preparePositionList($resultArray);
	}
	public function deleteDailyStatsByAdId($id)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_ads_manager_stats_daily', 'ad_id = ' . $db->quote($id));
	}
	public function insertClickStats($data)
	{
		$db = $this->_getDb();

		$values = array();
		foreach ($data as $key => $val)
		{
			$values[] = $db->quote($val);
		}

		$db->query('
			INSERT INTO xf_siropu_ads_manager_stats_clicks
				(date, ad_id, page_url, position, visitor_username, visitor_gender, visitor_age, visitor_ip, visitor_device)
			VALUES
				(' . implode(',', $values) . ')
		');
	}
	public function getClickStats($id, $conditions, $fetchOptions)
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchAll($this->limitQueryResults('
			SELECT *
			FROM xf_siropu_ads_manager_stats_clicks
			WHERE ad_id = ' . $this->_getDb()->quote($id) . '
			' . $this->prepareWhereConditions($conditions) . '
			ORDER BY date DESC
		', $limitOptions['limit'], $limitOptions['offset']));
	}
	public function getClickStatsCount($id, $conditions)
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_ads_manager_stats_clicks
			WHERE ad_id = ' . $this->_getDb()->quote($id) .
			$this->prepareWhereConditions($conditions)
		);

		return $result['count'];
	}
	public function getClickStatsPositions($id)
	{
		$resultArray = $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_stats_clicks
			WHERE ad_id = ' . $this->_getDb()->quote($id) . '
			GROUP BY position
		');

		return $this->_preparePositionList($resultArray);
	}
	public function deleteClicksStatsByAdId($id)
	{
		$db = $this->_getDb();
		$db->delete('xf_siropu_ads_manager_stats_clicks', 'ad_id = ' . $db->quote($id));
	}
	public function prepareWhereConditions($conditions)
	{
		$db = $this->_getDb();

		$where = '';

		if ($preset = $conditions['preset'])
		{
			$where .= ' AND date BETWEEN ' . $preset . ' AND ' . time();
		}
		else if ($dateStart = $conditions['start_date'])
		{
			$where .= ' AND date BETWEEN ' . $dateStart;

			if ($dateEnd = $conditions['end_date'])
			{
				$where .= ' AND ' . (($dateStart == $dateEnd) ? $dateEnd + (strtotime('+23 hours') - time()) : $dateEnd);
			}
			else
			{
				$where .= ' AND ' . time();
			}
		}

		if ($position = $conditions['position'])
		{
			$where .= ' AND position = ' . $db->quote($position);
		}

		return $where;
	}
	public function prepareGroupByConditions($conditions)
	{
		$groupBy  = ' GROUP BY YEAR(FROM_UNIXTIME(date)), ';
		$position = $conditions['chart'] ? '' : ', position';

		switch ($conditions['group_by'])
		{
			case 'day':
				return $groupBy . 'DAYOFYEAR(FROM_UNIXTIME(date))' . $position;
			break;
			case 'week':
				return $groupBy . 'WEEKOFYEAR(FROM_UNIXTIME(date))' . $position;
				break;
			case 'month':
				return $groupBy . 'MONTH(FROM_UNIXTIME(date))' . $position;
				break;
			default:
				return $conditions['chart'] ? $groupBy . 'HOUR(FROM_UNIXTIME(date))' : '';
				break;
		}
	}
	public function getAllDailyStats()
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_stats_daily
		');
	}
	public function getAllClickStats()
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_ads_manager_stats_clicks
		');
	}
	public function deleteStatsOlderThan($date)
	{
		$this->_getDb()->delete('xf_siropu_ads_manager_stats_daily', 'date <= ' . $this->_getDb()->quote($date));
		$this->_getDb()->delete('xf_siropu_ads_manager_stats_clicks', 'date <= ' . $this->_getDb()->quote($date));
	}
	protected function _preparePositionList($resultArray)
	{
		$list = array();
		foreach ($resultArray as $row)
		{
			$list[$row['position']] = $row['position'];
		}
		return $list;
	}
}

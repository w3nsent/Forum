<?php

class Siropu_Chat_Model_Extend_Cron extends XFCP_Siropu_Chat_Model_Extend_Cron
{
	public function calculateNextRunTimeAlt(array $runRules)
	{
		$nextRun = new DateTime(!empty($runRules['date']) ? $runRules['date'] : '', $this->getTimeZone());
		$nextRun->modify('+1 minute');

		$this->_modifyRunTimeMinutes($runRules['minutes'], $nextRun);
		$this->_modifyRunTimeHours($runRules['hours'], $nextRun);

		if ($runRules['day_type'] == 'dow')
		{
			$this->_modifyRunTimeDayOfWeek($runRules['dow'], $nextRun);
		}

		return strtotime($nextRun->format('Y-m-d H:i'));
	}
	public function getTimeZone()
	{
		return new DateTimeZone(XenForo_Application::get('options')->guestTimeZone);
	}
}
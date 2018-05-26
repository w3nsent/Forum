<?php
class VietXfAdvStats_Model_GetUserGroup extends XenForo_Model
    {
	public function getUserGroupOptions($selectedGroupIds)
	{
		$userGroups = array();
		$list_group = $this->_getDb()->fetchAll('
						SELECT user_group_id, title
						FROM xf_user_group
						ORDER BY user_group_id
						');
		foreach ($list_group AS $userGroup)
		{
			$userGroups[] = array(
			'label' => $userGroup['title'],
			'value' => $userGroup['user_group_id'],
			'selected' => in_array($userGroup['user_group_id'], $selectedGroupIds)
			);
		}
		return $userGroups;
	}
	public function checkGroup(){
		$user = XenForo_Visitor::getInstance();
		$options = XenForo_Application::get('options');
		$applytogroups = $options->VietXfAdvStats_groups;
		$check = false;
		if(!empty($applytogroups)){
			$belongstogroups = $user['user_group_id'];
			if (!empty($user['secondary_group_ids']))
			{
				$belongstogroups .= ','.$user['secondary_group_ids'];
			}
			$groupcheck = explode(',',$belongstogroups);
			unset($belongstogroups);
			foreach ($groupcheck AS $groupId)
			{
				if(in_array($groupId, $applytogroups))
				{
					$check = true;
					break;
				}
			}
		}
		return $check;
	}
	
}
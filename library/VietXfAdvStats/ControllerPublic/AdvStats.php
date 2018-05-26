<?php
class VietXfAdvStats_ControllerPublic_AdvStats extends XenForo_ControllerPublic_Abstract {
	public function actionUsersNew() {
		return $this->_actionUsers('users_new');
	}
	
	public function actionUsersTopPosters() {
		return $this->_actionUsers('users_top_posters');
	}
	
	public function actionUsersTopStarters() {
		return $this->responseNoPermission();
	}
	
	public function actionUsersTopTrophyPoints() {
		return $this->_actionUsers('users_top_trophy_points');
	}
	
	public function actionUsersTopLiked() {
		return $this->_actionUsers('users_top_liked');
	}
	public function actionUsersTopRichest() {
		return $this->_actionUsers('users_top_richest');
	}
	public function actionUsersTopPoorest() {
		return $this->_actionUsers('users_top_poorest');
	}
	public function actionUsersTopEarnedInDay() {
		return $this->_actionUsers('users_top_earned_in_day');
	}
	public function actionUsersTopSpentInDay() {
		return $this->_actionUsers('users_top_spent_in_day');
	}
	
	protected function _actionUsers($type) {
		list($templateName, $viewParams) = VietXfAdvStats_Renderer::renderSectionUserPrepare($this, $type);
		
		return $this->responseView('VietXfAdvStats_ViewPublic_AdvStats_Users', $templateName, $viewParams);
	}
	
	public function actionThreadsLatest() {
		return $this->_actionThreads('threads_latest');
	}
	
	public function actionThreadsHot() {
		return $this->_actionThreads('threads_hot');
	}
	
	public function actionThreadsRecent() {
		return $this->_actionThreads('threads_recent');
	}
	
	public function actionThreadsLatestCustomForum() {
		return $this->_actionThreads('threads_latest_custom_forum');
	}
	
	public function actionThreadsRecentCustomForum() {
		return $this->_actionThreads('threads_recent_custom_forum');
	}
	
	public function actionThreadsRandom() {
		return $this->_actionThreads('threads_random');
	}
	
	protected function _actionThreads($type) {
		list($templateName, $viewParams) = VietXfAdvStats_Renderer::renderSectionThreadPrepare($this, $type);
		
		return $this->responseView('VietXfAdvStats_ViewPublic_AdvStats_Threads', $templateName, $viewParams);
	}
	
	public function actionBulkUpdate() {
		$this->_assertPostOnly();
		
		$input = $this->_input->filter(array(
			'sections' => XenForo_Input::ARRAY_SIMPLE,
			'itemLimit' => XenForo_Input::UINT,
			'intervalUpdate' => XenForo_Input::UINT,
		));
		
		$viewParams = array(
			'sections' => $input['sections'],
			'pseudoInput' => array(
				'itemLimit' => $input['itemLimit'],
				'intervalUpdate' => $input['intervalUpdate'],
			)
		);
		
		return $this->responseView('VietXfAdvStats_ViewPublic_AdvStats_BulkUpdate', '', $viewParams);
	}
}
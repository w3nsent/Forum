<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerPublic_Thread extends XFCP_Brivium_AdvancedReputationSystem_ControllerPublic_Thread 
{
	public function actionIndex()
	{
		$action = parent::actionIndex();
		if (! empty( $action->templateName) && $action->templateName == 'thread_view')
		{
			$posts = ! empty( $action->params['posts']) ? $action->params['posts'] : array();
			$thread = ! empty( $action->params['thread']) ? $action->params['thread'] : array();
			$forum = ! empty( $action->params['forum']) ? $action->params['forum'] : array();
			if (! empty( $forum) && !empty($posts))
			{
				$reputationModel = $this->_getReputationModel();
				$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
				$reputation = $reputationModel->getReputationById( $reputationId);

				$reputation = !empty($reputation)?$reputation:array();
				$action->params['posts'] = $reputationModel->mergeLastReputations($reputation, $posts, $thread, $forum);
			}
		}
		return $action;
	}
	
	public function actionShowPosts()
	{
		$action = parent::actionShowPosts();
	
		$posts = ! empty( $action->params['posts']) ? $action->params['posts'] : array();
		$thread = ! empty( $action->params['thread']) ? $action->params['thread'] : array();
		$forum = ! empty( $action->params['forum']) ? $action->params['forum'] : array();
	
		$reputationModel = $this->_getReputationModel();
		$action->params['posts'] = $reputationModel->mergeLastReputations(array(), $posts, $thread, $forum);
		return $action;
	}
	
	protected function _getReputationModel()
	{
		return $this->getModelFromCache( 'Brivium_AdvancedReputationSystem_Model_Reputation');
	}
} 
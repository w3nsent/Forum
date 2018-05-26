<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerPublic_Forum extends XFCP_Brivium_AdvancedReputationSystem_ControllerPublic_Forum
{
	public function actionIndex()
	{
	    $parent = parent::actionIndex();
		
		//Display the most reputation users in the sidebar
        $options = XenForo_Application::get('options');
		
        $limit = $options->reputatedshow;
		
			$criteria = array(
			'user_state' => 'valid',
			'is_banned' => 0,
			'reputation_count' => array('>', 0)
		    );
			
			$reputedUsers = XenForo_Model::create('Brivium_AdvancedReputationSystem_Model_Reputation')->getMostReputatedUsers($criteria, array('limit' => $limit));
                                                                                       
        $parent->params['reputedUsers'] = $reputedUsers;
		
		//Rep points to enter forums
		$forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
		$forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumById($forumId);
		
		$visitor = XenForo_Visitor::getInstance();
		
	   //Set up the rep points requirement to enter forums and exlude groups from the limit as well
	   if($forum['reputation_count_entrance'] != 0)
	   {
	       //Guests must register
	      if (!$visitor['user_id'])
	   	  {
	   	  	return $this->responseError(new XenForo_Phrase('must_be_registered'));
	   	  }
		  
	      if($visitor['reputation_count'] <= $forum['reputation_count_entrance'] AND !XenForo_Visitor::getInstance()->hasPermission('reputation', 'exlude_forum_rep'))
	      {
	        return $this->responseError(new XenForo_Phrase('num_rep_forum', array('username' => $visitor['username'],
			'repforum' => $forum['reputation_count_entrance'],
			'repcount' => $visitor['reputation_count'])));
	      }
	   }
		
		return $parent;
	}
}
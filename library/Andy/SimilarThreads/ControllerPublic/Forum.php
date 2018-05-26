<?php

class Andy_SimilarThreads_ControllerPublic_Forum extends XFCP_Andy_SimilarThreads_ControllerPublic_Forum
{
    public function actionCreateThread()
    {
		//########################################
		// Create loadJavaScript variable and
		// add to params.
		//########################################
		
        // get parent     
        $parent = parent::actionCreateThread();		
		
		// check for user group permission
		if (!XenForo_Visitor::getInstance()->hasPermission('similarThreadsGroupID', 'similarThreadsID'))
		{
			return $parent;
		}
		
        // get options from Admin CP -> Options -> Similar Threads -> Show Create Thread
        $showCreateThread = XenForo_Application::get('options')->similarThreadsShowCreateThread;				
		
		// declare variable
		$loadJavaScript = '';
		
		// run if showCreateThread
		if ($showCreateThread)
		{
			// get nodeId       
			$forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
			$forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
			$ftpHelper = $this->getHelper('ForumThreadPost');
			$forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);
			$nodeId = $forum['node_id'];       
	   
			// get options from Admin CP -> Options -> Similar Threads -> Exclude Forums  
			$excludeForumsArray = XenForo_Application::get('options')->similarThreadsExcludeForums;  
			  
			// check for excluded forums
			if (!in_array($nodeId, $excludeForumsArray))
			{
				// showCreateThread is enabled and forum is not excluded
				$loadJavaScript = true;
			   
				// prepare viewParams
				if ($parent instanceOf XenForo_ControllerResponse_View)
				{
					// prepare viewParams
					$viewParams = array(
						'loadJavaScript' => $loadJavaScript
					);
				   
					// add viewParams to parent params
					$parent->params += $viewParams;
				}
			}
		}
      
        // return parent
        return $parent;
	}
	
    public function actionSimilarThreads()
    {
		//########################################
		// Show similar threads when member is
		// creating a new thread.
		//########################################
		
		//########################################
		// get options
		//########################################
		
		// get options from Admin CP -> Options -> Similar Threads -> Exclude Forums
		$excludeForumsArray = XenForo_Application::get('options')->similarThreadsExcludeForums;
		
		// get options from Admin CP -> Options -> Similar Threads -> Miniumum Common Word Length    
		$minimumCommonWordLength = XenForo_Application::get('options')->similarThreadsMinimumCommonWordLength;
		
		// get options from Admin CP -> Options -> Similar Threads -> Multibyte
		$multibyte = XenForo_Application::get('options')->similarThreadsMultibyte;												
		
		// get options from Admin CP -> Options -> Similar Threads -> Show Below First Post
		$showBelowFirstPost = XenForo_Application::get('options')->similarThreadsShowBelowFirstPost;	
			
		// get options from Admin CP -> Options -> Similar Threads -> Show Below Quick Reply
		$showBelowQuickReply = XenForo_Application::get('options')->similarThreadsShowBelowQuickReply;	
		
		// get options from Admin CP -> Options -> Similar Threads -> Stop Characters
		$stopCharacters = XenForo_Application::get('options')->similarThreadsStopCharacters;	
		
		// get options from Admin CP -> Options -> Similar Threads -> Stop Words    
		$stopWords = XenForo_Application::get('options')->similarThreadsStopWords;				
		
		//########################################
		// misc variables
		//########################################
		
		// declare variables
		$similarThreads = array();
		$searchWords = array();
		$searchWord1 = '';
		$searchWord2 = '';
		$searchWord3 = '';
		$threadIds = array();
		$sameForum = 0;
		
		// get userId
		$visitor = XenForo_Visitor::getInstance();
        $userId = $visitor['user_id'];									
	
		// get nodeId
		$currentNodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);		
	
		// set currentThreadId
		$currentThreadId = 0;		

		//########################################
		// prepare search words
		//########################################
		
		// get thread title
		$threadTitle = $this->_input->filterSingle('title', XenForo_Input::STRING);		
	
		// replace double quote
		$threadTitle = str_replace('"', "", $threadTitle);
		
		// replace backslash
		$threadTitle = str_replace('\\', "", $threadTitle);
		
		// replace first 32 ascii characters and DEL	
		$threadTitle = preg_replace('/[\x00-\x1F\x7F]/', '', $threadTitle);			
		
		// create stopCharactersArray
		$stopCharactersArray = explode(' ', $stopCharacters);			
		
		// remove characters			
		$threadTitle = str_replace($stopCharactersArray, ' ', $threadTitle);
		
		if ($multibyte == 0)
		{
			// put into array
			$threadTitleArray = explode(' ', $threadTitle);
		}
		
		if ($multibyte == 1)
		{			
			// put into array
			$threadTitleArray = mb_split(' ', $threadTitle);
		} 
		
		// create stopWordsArray
		$stopWordsArray = explode(' ', $stopWords);								
		
		// remove words from array
		foreach ($threadTitleArray as $var)
		{
			if ($multibyte == 0)
			{
				if (!in_array(strtolower($var), $stopWordsArray))
				{		
					// verify minimumCommonWordLength			
					if (strlen($var) >= $minimumCommonWordLength)
					{
						$searchWords[] = $var;
					}
				}
			}

			if ($multibyte == 1)
			{
				if (!in_array(mb_strtolower($var), $stopWordsArray))
				{	
					// verify minimumCommonWordLength				
					if (mb_strlen($var) >= $minimumCommonWordLength)
					{
						$searchWords[] = $var;
					}
				}
			}
		}
		
		// get count
		$count = count($searchWords);			
		
		// continue we have a search word
		if ($count > 0)
		{				
			// get first search word
			$searchWord1 = $searchWords[0];
			
			if ($count > 1)
			{	
				// get second search word
				$searchWord2 = $searchWords[1];	
			}
			
			if ($count > 2)
			{	
				// get third search word
				$searchWord3 = $searchWords[2];	
			}						
		}
		
		//########################################
		// run query in model
		//########################################		
   
		$similarThreads = $this->getModelFromCache('Andy_SimilarThreads_Model')->getThreads($searchWord1,$searchWord2,$searchWord3,$currentNodeId,$currentThreadId);  
			
		//########################################
		// prepare viewParams
		//########################################		
		
		// get discussionPreviewLength	
		$discussionPreviewLength = XenForo_Application::get('options')->discussionPreviewLength;		
		
		// declare variables
		$similarThreadsNew = array();
		$i = 0;
		
		// add to multidimensional array
		foreach ($similarThreads as $k => $v)
		{ 
			// prepare array
			$forumArray = array('forum' => array(
				'node_id' => $v['node_id'],
				'title' => $v['nodeTitle']
			));	
			
			// prepare array
			$lastPostInfoArray = array('lastPostInfo' => array(
				'user_id' => $v['last_post_user_id'],
				'username' => $v['last_post_username']
			));	
			
			// prepare hasPreview
			if ($discussionPreviewLength)
			{
				// prepare array
				$hasPreviewArray = array('hasPreview' => true);
			}
			else
			{
				// prepare array
				$hasPreviewArray = array('hasPreview' => false);
			}
			
			// censor title
			$similarThreads[$i]['title'] = XenForo_Helper_String::censorString($v['title']);
			
			// merge arrays
			$similarThreadsNew[] = array_merge($similarThreads[$i], $forumArray, $lastPostInfoArray, $hasPreviewArray);				
			
			$i = $i + 1;	
		}
		
		// rename variable
		$similarThreads = $similarThreadsNew;	
	
		// prepare viewParams
		$viewParams = array(
			'searchWord1' => $searchWord1,
			'searchWord2' => $searchWord2,
			'searchWord3' => $searchWord3,
			'similarThreads' => $similarThreads
		);
	
		// send to template
		return $this->responseView('Andy_SimilarThreads_ViewPublic_SimilarThreads', 'andy_similarthreads_create_thread', $viewParams);	
	}
}
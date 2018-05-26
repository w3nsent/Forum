<?php

//######################## Reputation System By Brivium ###########################
class Brivium_AdvancedReputationSystem_ControllerPublic_Post extends XFCP_Brivium_AdvancedReputationSystem_ControllerPublic_Post 
{
	//View reputations in postbit and posts
	public function actionReputationView() 
	{
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		if(empty($post['canViewReptations']))
		{
			return $this->responseNoPermission();
		}

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
        $perPage = XenForo_Application::getOptions()->BRARS_perPage;
		
        $reputationModel =  $this->_getReputationModel();
        $conditions =  array(
        	'post_id' => $postId,
			'reputation_state' => $reputationModel->reputationStateViews()
		);
        
		$fetchOptions = array(
			'join' => Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER |  Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST,
			'page' => $page,
            'perPage' => $perPage
		);
		
		//Get all reputations for this post
		$reputations = $reputationModel->getReputations($conditions, $fetchOptions);

		$totalReputations = 0;
		if(!empty($reputations))
		{
			$reputations = $reputationModel->prepareReputations($reputations);
			$totalReputations = $reputationModel->countReputations($conditions);
		}
		
		foreach ($reputations as $key=>$reputation)
		{
			if(empty($reputation['canView']))
			{
				$totalReputations--;
				unset($reputations[$key]);
			}
		}
		
		//Register variables for use in our template
		$viewParams = array(
			'post' => $post,
			'thread' => $thread,
			'forum' => $forum,
			'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			'reputations' => $reputations,
			'totalReputations' => max(0, $totalReputations),
			
			'startOffset' => ($page-1)*$perPage +1,
			'endOffset' => ($page-1)*$perPage + count($reputations),
			'page' => $page,
			'perPage' => $perPage,
			
			'useStarInterface' => $reputationModel->canUseStarInterface()
		);
		
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Post_ReputationView', 'BRARS_post_reputation_view', $viewParams);
	}
	
	public function actionPreview()
	{
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);
		$postModel = $this->_getPostModel();
		if (!$postModel->canViewPost($post, $thread, $forum))
		{
			return $this->responseNoPermission();
		}
		
		$viewParams = array(
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum
		);
		
		return $this->responseView('XenForo_ViewPublic_Thread_Preview', 'thread_list_item_preview', $viewParams);
	}
	
	public function actionEditInline()
	{
		$action = parent::actionEditInline();
	
		$post = ! empty( $action->params['post']) ? $action->params['post'] : array();
		$thread = ! empty( $action->params['thread']) ? $action->params['thread'] : array();
		$forum = ! empty( $action->params['forum']) ? $action->params['forum'] : array();
	
		$reputationModel = $this->_getReputationModel();
	
		if (!empty($post['canViewReptations']))
		{
			$posts = $reputationModel->mergeLastReputations( array(), array($post['post_id'] => $post), $thread, $forum);
			$action->params['post'] = reset( $posts);
		}
		return $action;
	}
	
	
	public function actionBrAddReputation()
	{
		$postId = $this->_input->filterSingle( 'post_id', XenForo_Input::UINT);
		
		$ftpHelper = $this->getHelper( 'ForumThreadPost');
		list ($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);
		
		$guest = array(
			'email_address' => $this->_input->filterSingle('email_address', XenForo_Input::STRING),
			'username' =>  $this->_input->filterSingle('giver_username', XenForo_Input::STRING),
		);
		$this->_assertCanGiveReputation( $post, $thread, $forum, $guest);
	
		if ($this->isConfirmedPost())
		{
			if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
			{
				return $this->responseCaptchaFailed();
			}
			
			$user = XenForo_Visitor::getInstance()->toArray();
			
			if (!$user['user_id'])
			{
				$user['username'] = $this->_input->filterSingle('giver_username', XenForo_Input::STRING);
				$user['email'] = $this->_input->filterSingle('email_address', XenForo_Input::STRING);
				
				if (!Zend_Validate::is($user['email'], 'EmailAddress') )
				{
					return $this->responseError ( new XenForo_Phrase ( 'please_enter_valid_email' ) );
				}
					
				if (XenForo_Helper_Email::isEmailBanned($user['email']))
				{
					return $this->responseError(new XenForo_Phrase('email_address_you_entered_has_been_banned_by_administrator'));
				}
					
				$userRegistered = $this->getModelFromCache('XenForo_Model_User')->getUserByEmail($user['email']);
				if(!empty($userRegistered))
				{
					$errorString = new XenForo_Phrase('BRARS_email_is_already_registered');
					return $this->responseError($errorString);
				}
				$encode = XenForo_Application::generateRandomString(36);
			}
			
			$inputs = $this->_input->filter( array(
					'points' => XenForo_Input::INT,
					'comment' => XenForo_Input::STRING,
					'is_anonymous' => XenForo_Input::BOOLEAN,
			));
			
			if($post['canUseStarInterface'])
			{
				$rating = $this->_input->filterSingle('rating', XenForo_Input::UINT);
				$starOptions = XenForo_Application::getOptions()->BRARS_starInterface;
				$points = isset($starOptions['star'][$rating])?$starOptions['star'][$rating]:$post['maxReputationPoints'];
				if($points <= $post['minReputationPoints'])
				{
					$points = $post['minReputationPoints'];
				}elseif ($points >= $post['maxReputationPoints'])
				{
					$points = $post['maxReputationPoints'];
				}
				
			}elseif($inputs['points'] < $post['minReputationPoints'] || $inputs['points'] >  $post['maxReputationPoints'])
			{
				return $this->responseError(new XenForo_Phrase('BRARS_rep_points_to_give', array('min' => $post['minReputationPoints'], 'max' => $post['maxReputationPoints'])));
			}else 
			{
				$points = $inputs['points'];
			}
			
			$writer = XenForo_DataWriter::create('Brivium_AdvancedReputationSystem_DataWriter_Reputation');
			
			$reputationModel=  $this->_getReputationModel();
			$data = array(
					'post_id' => $post['post_id'],
					'receiver_user_id' => $post['user_id'],
					'receiver_username' => $post['username'],
					'giver_user_id' => $user['user_id'],
					'giver_username' => $user['username'],
					'points' => $points,
					'comment' => $inputs['comment'],
					'is_anonymous' => $inputs['is_anonymous'],
					'reputation_state' => !empty($encode)?'moderated':$reputationModel->getReputationInsertState(),
					'email_address' => $user['email'],
					'encode' => !empty($encode)?$encode:'',
			);
			$writer->bulkSet($data);
			$writer->save();
			
			$message ='';
			$reputation = $writer->getMergedData();
			$isSendEmail = $reputationModel->sendEmail($user, $reputation, $post, $thread);
			if($isSendEmail)
			{
				$message =  new XenForo_Phrase('BRARS_please_confirm_your_email_to_rated_this_thread');
			}
			
			if(!empty($GLOBALS['BRARS_reponses']))
			{
				$redirect = $GLOBALS['BRARS_reponses']['redirect'];
				$message = $GLOBALS['BRARS_reponses']['message'];
			}else 
			{
				$redirect = XenForo_Link::buildPublicLink('brars-reputations', $writer->getMergedData());
			}
			
			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect, $message);
		}
		
		$presetModel = $this->_getPresetCommentModel();
		$conditions = array('active'=>1);
		$presetComments = $presetModel->getPresetComments($conditions);
		
		$viewParams = array(
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum,
				'presetComments' => !empty($presetComments)?$presetComments:array(),
				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
				'captcha' => XenForo_Captcha_Abstract::createDefault()
		);
		
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Reputation_Edit', 'BRARS_reputation_edit', $viewParams);
	}
	
	public function actionBrReputations()
	{
		$postId = $this->_input->filterSingle( 'post_id', XenForo_Input::UINT);
	
		$ftpHelper = $this->getHelper( 'ForumThreadPost');
		list ($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable( $postId);
	
		$beforeDate = $this->_input->filterSingle('before', XenForo_Input::UINT);
		$afterDate = $this->_input->filterSingle('after', XenForo_Input::UINT);
		
		$reputationModel = $this->_getReputationModel();
	
		$optionMaxReputationDisplay = XenForo_Application::getOptions()->BRARS_maxReputationsDisplay;
		$fetchOptions = array(
				'join' => Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER|Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST,
				'limit' => $optionMaxReputationDisplay +1,
		);
		
		$conditions =  array(
			'reputation_state' => $reputationModel->reputationStateViews()
		);
	
		$hadPerviewOrAfter = false;
		if (! empty( $beforeDate))
		{
			$reputations = $reputationModel->getReputationsInPostBefore( $postId, $beforeDate, $conditions, $fetchOptions);
			if(count($reputations) > $optionMaxReputationDisplay)
			{
				array_pop($reputations);
				$hadPerviewOrAfter =  true;
			}
			
		} elseif (! empty( $afterDate))
		{
			$fetchOptions['order'] = 'reputation_date';
			$fetchOptions['direction'] = 'asc';
			$reputations = $reputationModel->getReputationsInPostAfter( $postId, $afterDate, $conditions, $fetchOptions);
			
			if(count($reputations) > $optionMaxReputationDisplay)
			{
				array_pop($reputations);
				$hadPerviewOrAfter =  true;
			}
			$reputations = array_reverse($reputations);
		}
		
		if (!empty( $reputations))
		{
			$reputations = $reputationModel->prepareReputations( $reputations, $post, $thread, $forum);

			if($hadPerviewOrAfter)
			{
				$firstReputationShown = reset( $reputations);
				$lastReputationShown = end( $reputations);
			}
		}
	
		$viewParams = array(
				'reputations' => !empty($reputations)?$reputations:array(),
				'firstReputationShown' => !empty($firstReputationShown)?$firstReputationShown:array(),
				'lastReputationShown' => !empty($lastReputationShown)?$lastReputationShown:array(),
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum,
		);
	
		if (! empty( $beforeDate))
		{
			return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewPreviousReputations', 'BRARS_reputation_items', $viewParams);
		}
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewAfterReputations', 'BRARS_reputation_items', $viewParams);
	}
	
	
	protected function _assertCanGiveReputation(array $post, array $thread, array $forum, array $guest = array())
	{
		if(empty($post['canGiveReputation']))
		{
			$errorPhraseKey = '';
			$this->_getPostModel()->canGiveReputation($post, $thread, $forum, $errorPhraseKey);
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		$reputationModel = $this->_getReputationModel();
		if (!$reputationModel->dailyLimit($post, $thread, $forum, $errorPhraseKey, array(), array(), $guest))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		return true;
	}
	/**
	 * @return Brivium_AdvancedReputationSystem_Model_Thread
	 */
	protected function _getAnonymousDeleteAlerts()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Thread');
	}
	
	protected function _getReputationModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
	}
	
	protected function _getPresetCommentModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_PresetComment');
	}
}
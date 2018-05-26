<?php
class Brivium_AdvancedReputationSystem_ControllerPublic_Reputation extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($reputation['post_id']);
		
		return $this->getReputationSpecificRedirect( $reputation, $post, $thread, XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT);
	}
	
	public function getReputationSpecificRedirect(array $reputation, array $post, array $thread, $redirectType = XenForo_ControllerResponse_Redirect::SUCCESS)
	{
		$page = floor( $post['position'] / XenForo_Application::get( 'options')->messagesPerPage) + 1;
	
		return $this->responseRedirect( $redirectType, XenForo_Link::buildPublicLink( 'threads', $thread, array(
				'page' => $page,
				'reputation_id' => $reputation['reputation_id']
		)) . '#reputation-' . $reputation['reputation_id']);
	}
	
	public function actionViewDelete()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId);
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($reputation['post_id']);
		
		$viewParams = array(
				'reputation' => $reputation,
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum,
				'isViewReputationDeleted' => true
		);
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewDelete','', $viewParams);
	}
	
	public function actionModeration()
	{
		if(!$this->_getPostModel()->canManagerModeration())
		{
			return $this->responseNoPermission();
		}
		
		$input = $this->_getReputationFilterParams();
		
		$dateInput = $this->_input->filter(array(
				'start' => XenForo_Input::DATE_TIME,
				'end' => array(XenForo_Input::DATE_TIME, 'dayEnd' => true),
		));
		
		$page = max( 1, $this->_input->filterSingle( 'page', XenForo_Input::UINT));
		$perPage = XenForo_Application::getOptions()->BRARS_perPage;
		
		$pageParams = array();
		if (!empty($input['giver_username']))
		{
			$pageParams['giver_username'] = $input['giver_username'];
		}
		if (!empty($input['receiver_username']))
		{
			$pageParams['receiver_username'] = $input['receiver_username'];
		}
		if (!empty($input['reputation_state']))
		{
			$pageParams['reputation_state'] = $input['reputation_state'];
		}
		if (!empty($input['start']))
		{
			$pageParams['start'] = $input['start'];
		}
		if (!empty($input['end']))
		{
			$pageParams['end'] = $input['end'];
		}

		$reputationModel = $this->_getReputationModel();
		
		$conditions = array(
				'giver_username_like' => $input['giver_username'],
				'receiver_username_like' => $input['receiver_username'],
				'reputation_state' => 'moderated',
				'start' => $dateInput['start'],
				'end' => $dateInput['end'],
		);
		
		if(!empty($input['reputation_state']))
		{
			$conditions['reputation_state'] = ($input['reputation_state']=='all_state')?'':$input['reputation_state'];
		}
		
		$fetchOptions = array(
				'page' => $page,
				'perPage' => $perPage,
				'join' => 	Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST|
							Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER
		);
		$reputations = $reputationModel->getReputations($conditions, $fetchOptions);
		if(!empty($reputations))
		{
			$totalReputations = $reputationModel->countReputations($conditions, array('join' => $fetchOptions['join']) );
			$reputations = $reputationModel->prepareReputations($reputations);
		}
		
		$viewParams = array(
				'reputations' => $reputations,
				'totalReputations' => !empty($totalReputations)?$totalReputations:0,
				'datePresets' 	=> XenForo_Helper_Date::getDatePresets(),
		
				'startOffset' => ($page-1)*$perPage +1,
				'endOffset' => ($page-1)*$perPage + count($reputations),
				'page' => $page,
				'perPage' => $perPage,
				'pageParams' => $pageParams,
				'useStarInterface' => $reputationModel->canUseStarInterface()
		);
		$viewParams +=$input;
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ModerationList', 'BRARS_reputation_moderation_list', $viewParams);
	}
	
	protected function _getReputationFilterParams()
	{
		return $this->_input->filter(array(
				'giver_username'	=> XenForo_Input::STRING,
				'receiver_username' => XenForo_Input::STRING,
				'reputation_state'	=> XenForo_Input::STRING,
				'start' 			=> XenForo_Input::STRING,
				'end' 				=> XenForo_Input::STRING
		));
	}
	
	public function actionStatic()
	{
		$reputationModel = $this->_getReputationModel();
		$visitor = XenForo_Visitor::getInstance();
		if (!$reputationModel->canViewReputationStatistics() )
		{
			return $this->responseError(new XenForo_Phrase('BRARS_rep_stats_error_message', array('username' => $visitor['username'])));
		}
		$maxResults = XenForo_Application::get('options')->maxMostPositiveNegativePosts;
		
		//Prevent users who do no thave permisisons to use reputations to see the view reputation link
		$canuserep = $visitor->hasPermission('reputation', 'can_use_rep');
		
		$limit = XenForo_Application::get('options')->maxMostPositiveNegativeUsers;
		
		//Display the most positive reputation received users in the sidebar
		$criteria = array(
				'user_state' => 'valid',
				'is_banned' => 0,
				'reputation_count' => array('>', 0)
		);
			
		$positiveUsers = $reputationModel->getMostReputatedUsers($criteria, array('limit' => $limit));
		 
		//Display the most negative reputation received users in the sidebar
		$negcriteria = array(
				'user_state' => 'valid',
				'is_banned' => 0,
				'reputation_count' => array('<', 0)
		);
			
		$negativeUsers = $reputationModel->getMostReputatedUsers($negcriteria, array('limit' => $limit));
		
		//Get posts with most positive reputations
		$positivePosts = $reputationModel->getMostPositivePosts($maxResults);
		
		//Get posts with most negative reputations
		$negativePosts = $reputationModel->getMostNegativePosts($maxResults);
		
		//Show the online users sidebar at the Reputation Statistics
		$sessionModel = $this->getModelFromCache('XenForo_Model_Session');
		
		$onlineUsers = $sessionModel->getSessionActivityQuickList(
				$visitor->toArray(),
				array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
				($visitor['user_id'] ? $visitor->toArray() : null)
		);
		
		//Show the board stats in the sidebar at the Reputation Statistics to make the sidebar complete there
		$boardTotals = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('boardTotals');
		if (!$boardTotals)
		{
			$boardTotals = $this->getModelFromCache('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
		}
		
		$viewParams = array(
				'positivePosts' => $positivePosts,
				'negativePosts' => $negativePosts,
				'positiveUsers' => $positiveUsers,
				'canuserep'     => $canuserep,
				'negativeUsers' => $negativeUsers,
				'onlineUsers' => $onlineUsers,
				'boardTotals' => $boardTotals
		);
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Reputation_Static', 'BRARS_reputation_statistics', $viewParams);
	}
	public function actionEdit()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId);
		
		$this->_assertCanEditReputation($reputation);
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		list ($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($reputation['post_id']);
		
		if ($this->isConfirmedPost())
		{
			if($post['canUseStarInterface'])
			{
				$rating = $this->_input->filterSingle('rating', XenForo_Input::UINT);
				$starOptions = XenForo_Application::getOptions()->BRARS_starInterface;
				$points = !empty($starOptions['star'][$rating])?$starOptions['star'][$rating]:$post['maxReputationPoints'];
				if($points <= $post['minReputationPoints'])
				{
					$points = $post['minReputationPoints'];
				}elseif ($points >= $post['maxReputationPoints'])
				{
					$points = $post['maxReputationPoints'];
				}
			}else 
			{
				$points = $this->_input->filterSingle('points', XenForo_Input::INT);
			}
			$comment = $this->_input->filterSingle('comment', XenForo_Input::STRING);
			$isAnonymous = $this->_input->filterSingle('is_anonymous', XenForo_Input::UINT);
			
			$reputationDw = XenForo_DataWriter::create('Brivium_AdvancedReputationSystem_DataWriter_Reputation');
			$reputationDw->setExistingData($reputationId);
			$reputationDw->set('points', $points);
			$reputationDw->set('comment', $comment);
			$reputationDw->set('is_anonymous', $isAnonymous);
			$reputationDw->save();
		
			$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
			
			//return $this->getReputationSpecificRedirect($reputation, $post, $thread);
			return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $redirect?$redirect:$this->getDynamicRedirect());
		}
		
		$viewParams = array(
				'reputation' => $reputation,
				'post'		=> $post,
				'thread'	=> $thread,
				'forum'		=> $forum,
				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs( $forum),
				'redirect' => $this->getDynamicRedirect(),
		);
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Reputation_Edit', 'BRARS_reputation_edit', $viewParams);
	}
	
	public function actionApprove()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId);
		
		$this->_assertCanApproveOrUnapproveReputation();
		$this->_getReputationModel()->_updateReputationState( $reputation, 'visible', 'moderated');
		return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $this->getDynamicRedirect());
	}
	
	public function actionUnapprove()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
	
		$reputation = $this->_getReputationOrError($reputationId);
		$this->_assertCanApproveOrUnapproveReputation();
		$this->_getReputationModel()->_updateReputationState( $reputation, 'moderated', 'visible');
		return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $this->getDynamicRedirect());
	}
	
	public function actionDelete()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId);
		
		$hardDelete = $this->_input->filterSingle( 'hard_delete', XenForo_Input::UINT);
		$deleteType = ($hardDelete ? 'hard' : 'soft');
	
		$this->_assertCanDeleteReputation( $reputation, $deleteType);
	
		$reputationModel = $this->_getReputationModel();
	
		if ($this->isConfirmedPost()) // delete the reputation
		{
			$options = array(
					'reason' => $this->_input->filterSingle( 'reason', XenForo_Input::STRING),
					'authorAlert' => $this->_input->filterSingle( 'send_author_alert', XenForo_Input::BOOLEAN),
					'authorAlertReason' => $this->_input->filterSingle( 'author_alert_reason', XenForo_Input::STRING)
			);
			if ($reputation['giver_user_id'] == XenForo_Visitor::getUserId())
			{
				$options['authorAlert'] = false;
			}
				
			$dw = $reputationModel->deleteReputation( $reputationId, $deleteType, $options);
			
			$actionParams = array();
			if(!empty($options['reason']) && $deleteType != 'hard')
			{
				$actionParams['reason'] = $options['reason'];
			}
			
			XenForo_Model_Log::logModeratorAction( 'brivium_reputation_system', $reputation, 'delete_' . $deleteType, $actionParams);
			
			$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
			return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $redirect?$redirect:$this->getDynamicRedirect());
		} else // show a deletion confirmation dialog
		{
			$ftpHelper = $this->getHelper('ForumThreadPost');
			list ($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($reputation['post_id']);
			
			$presetDeleteModel = $this->_getPresetDeleteModel();
			$conditions = array('active'=>1);
			$presetDeletes = $presetDeleteModel->getPresetDeletes($conditions);
			
			$viewParams = array(
					'reputation' => $reputation,
					'post' => $post,
					'thread' => $thread,
					'forum' => $forum,
					'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs( $forum),
					'canHardDelete' => $reputationModel->canDeleteReputation( $reputation, 'hard'),
					'presetDeletes' => !empty($presetDeletes)?$presetDeletes:array(),
					'redirect' => $this->getDynamicRedirect(),
			);
			
			return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewPublic_ReputationDelete', 'BRARS_reputation_delete', $viewParams);
		}
	}
	
	public function actionUndelete()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId);

		$this->_assertCanDeleteReputation( $reputation);
	
		$reputationModel = $this->_getReputationModel();
		$reputationModel->_updateReputationState( $reputation, 'visible', 'deleted');
	
		return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $this->getDynamicRedirect());
	}
	
	
	public function actionReport()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$reputation = $this->_getReputationOrError($reputationId, array('join' => Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST));
	
		if ($this->_request->isPost())
		{
			$message = $this->_input->filterSingle( 'message', XenForo_Input::STRING);
			if (! $message)
			{
				return $this->responseError( new XenForo_Phrase( 'please_enter_reason_for_reporting_this_message'));
			}
			
			$this->assertNotFlooding( 'report');
				
			$reportModel = XenForo_Model::create( 'XenForo_Model_Report');
			$reportModel->reportContent( 'brivium_reputation_system', $reputation, $message);
			
			$redirectMessage = new XenForo_Phrase( 'thank_you_for_reporting_this_message');
			$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
			return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $redirect?$redirect:$this->getDynamicRedirect(), $redirectMessage);
		} 
		$viewParams = array(
				'reputation' => $reputation,
				'redirect' => $this->getDynamicRedirect()
		);
		
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewPublic_ReputationReport', 'BRARS_reputation_report', $viewParams);
	}
	
	public function actionViewGuest()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		
		$fetchOptions = array(
			'join' => 	Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER|
						Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST
		);
		$reputation = $this->_getReputationOrError($reputationId, $fetchOptions);
	
		$viewParams = array(
			'reputation' => $reputation
		);
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewPublic_Reputation_ViewGuest', 'BRARS_view_guest', $viewParams);
	}
	
	
	public function actionConfirm()
	{
		$reputationId = $this->_input->filterSingle( 'reputation_id', XenForo_Input::UINT);
		$encode = $this->_input->filterSingle('c', XenForo_Input::STRING);
		
		$reputation = $this->_getReputationOrError($reputationId);
	
		if(!empty($reputation['encode']) && $reputation['encode'] != $encode)
		{
			$errorString = new XenForo_Phrase('BRARS_confirmation_code_wrong');
			return $this->responseError($errorString);
		}
	
		if(!empty($reputation['encode']))
		{
			$reputationDw = XenForo_DataWriter::create( 'Brivium_AdvancedReputationSystem_DataWriter_Reputation');
			$reputationDw->setExistingData( $reputation['reputation_id']);
			$reputationDw->set( 'reputation_state', 'visible');
			$reputationDw->set( 'encode', '');
			$reputationDw->save();
		}
		$redirect = XenForo_Link::buildPublicLink( 'brars-reputations', $reputation);
		$message = new XenForo_Phrase('BRARS_this_rating_had_been_confirmation');
		return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $redirect, $message);
	}
	
	protected function _getReputationOrError($reputationId, $fetchOptions = array())
	{
		if(empty($fetchOptions['join']))
		{
			$fetchOptions['join'] = Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST;
		}
		$reputationModel = $this->_getReputationModel();
		$reputation = $reputationModel->getReputationById($reputationId, $fetchOptions);
		
		if(empty($reputation))
		{
			$errorString = new XenForo_Phrase('BRARS_requied_reputation_not_found');
			throw new XenForo_Exception($errorString, true);
		}
		return $reputationModel->prepareReputation($reputation);
	}
	
	protected function _assertCanEditReputation(array $reputation)
	{
		$errorPhraseKey = '';
		if (! $this->_getReputationModel()->canEditReputation($reputation, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException( $errorPhraseKey);
		}
		return true;
	}
	
	protected function _assertCanApproveOrUnapproveReputation()
	{
		if (! $this->_getReputationModel()->canApproveUnapproveReputation())
		{
			return $this->getNoPermissionResponseException();
		}
		return true;
	}
	
	protected function _assertCanDeleteReputation(array $reputation,  $deleteType = 'soft')
	{
		$errorPhraseKey = '';
		if (! $this->_getReputationModel()->canDeleteReputation($reputation, $deleteType, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException( $errorPhraseKey);
		}
		return true;
	}
	
	protected function _getReputationModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_Reputation');
	}
	protected function _getPostModel()
	{
		return $this->getModelFromCache('XenForo_Model_Post');
	}
	
	protected function _getPresetDeleteModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_PresetDelete');
	}
	
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('viewing_rep_stats');
	}
}
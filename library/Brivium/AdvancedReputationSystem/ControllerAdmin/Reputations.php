<?php
class Brivium_AdvancedReputationSystem_ControllerAdmin_Reputations extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		return $this->responseReroute( __CLASS__, 'List');
	}
	
	public function actionList()
	{
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
		
		/* $receiverUserId = 0;
		if (!empty($input['receiver_username']))
		{
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$user = $userModel->getUserByName($input['receiver_username']);
			if (!empty($user))
			{
				$receiverUserId = $user['user_id'];
				$pageParams['receiver_username'] = $input['receiver_username'];
			}
			else
			{
				$input['receiver_username'] = '';
			}
		}
		
		$giverUserId = 0;
		if (!empty($input['giver_username']))
		{
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$giverUser = $userModel->getUserByName($input['giver_username']);
			if (!empty($giverUser))
			{
				$giverUserId = $giverUser['user_id'];
				$pageParams['giver_username'] = $input['giver_username'];
			}
			else
			{
				$input['giver_username'] = '';
			}
		} */
		
		$reputationModel = $this->_getReputationModel();
		
		$conditions = array(
				'giver_username_like' => $input['giver_username'],
				'receiver_username_like' => $input['receiver_username'],
				'reputation_state' => $input['reputation_state'],
				'start' => $dateInput['start'],
				'end' => $dateInput['end'],
		);
		
		$fetchOptions = array(
				'page' => $page,
				'perPage' => $perPage,
				'join' => 	Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST|
							Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_RECEIVER|
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
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewAdmin_ReputationList', 'BRARS_reputation_list', $viewParams);
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

			$reputationDw = XenForo_DataWriter::create('Brivium_AdvancedReputationSystem_DataWriter_Reputation');
			$reputationDw->setExistingData($reputationId);
			$reputationDw->set('points', $points);
			$reputationDw->set('comment', $comment);
			$reputationDw->save();
		
			$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
			return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, $redirect?$redirect:$this->getDynamicRedirect());
		}
		
		$viewParams = array(
				'reputation' => $reputation,
				'post'		=> $post,
				'thread'	=> $thread,
				'forum'		=> $forum,
				'redirect' => $this->getDynamicRedirect(),
		);
		
		return $this->responseView('Brivium_AdvancedReputationSystem_ViewAdmin_ReputationEdit', 'BRARS_reputaion_edit', $viewParams);
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
			$presetDeleteModel = $this->_getPresetDeleteModel();
			$conditions = array('active'=>1);
			$presetDeletes = $presetDeleteModel->getPresetDeletes($conditions);
			
			$viewParams = array(
					'reputation' => $reputation,
					'canHardDelete' => $reputationModel->canDeleteReputation( $reputation, 'hard'),
					'redirect' => $this->getDynamicRedirect(),
					'presetDeletes' => !empty($presetDeletes)?$presetDeletes:array(),
			);
			return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_ReputationDelete', 'BRARS_reputation_delete', $viewParams);
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
	
	public function actionClear()
	{
		if ($this->isConfirmedPost()) //clear all reputations
		{
			$this->_getReputationModel()->clearReputations();
			
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brars-reputations')
			);			
		}
		else
		{
			$viewParams = array();
			
			return $this->responseView('Brivium_AdvancedReputationSystem_ViewAdmin_Reputation_Clear', 'BRARS_clear', $viewParams);
		}
	}
	
	public function actionRecount()
	{
		if ($this->isConfirmedPost())
		{
			$this->_getReputationModel()->deleteReputationEmptyPost();
				
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brars-reputations')
			);
		}
	
		else
		{
			return $this->responseView('Brivium_AdvancedReputationSystem_ViewAdmin_Reputation_Recount', 'BRARS_recount', array());
		}
	}
	
	protected function _getReputationOrError($reputationId, $fetchOptions = array())
	{
		if(empty($fetchOptions['join']))
		{
			$fetchOptions['join'] = Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST;
		}
		
		$reputation = $this->_getReputationModel()->getReputationById($reputationId, $fetchOptions);
		if(empty($reputation))
		{
			$errorString = new XenForo_Phrase('BRARS_requied_reputation_not_found');
			throw new XenForo_Exception($errorString, true);
		}
		return $this->_getReputationModel()->prepareReputation($reputation);
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
	
	protected function _getPresetDeleteModel()
	{
		return $this->getModelFromCache('Brivium_AdvancedReputationSystem_Model_PresetDelete');
	}
}
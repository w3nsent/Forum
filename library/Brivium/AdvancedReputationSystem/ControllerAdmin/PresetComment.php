<?php
class Brivium_AdvancedReputationSystem_ControllerAdmin_PresetComment extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$presetCommentModel = $this->_getPresetCommentModel();
		$presetComments = $presetCommentModel->getPresetComments();
		$viewParams = array(
				'presetComments' => $presetComments
		);
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_PresetComment_Index', 'BRARS_preset_reputation_comment_list', $viewParams);
	}
	
	public function actionAdd()
	{
		return $this->responseReroute( __CLASS__, 'Edit');
	}
	
	public function actionEdit()
	{
		$presetCommentId = $this->_input->filterSingle( 'preset_id', XenForo_Input::UINT);
		if ($presetCommentId)
		{
			$presetComment = $this->_getPresetCommentOrError( $presetCommentId);
		} else
		{
			$presetComment = array();
		}
	
		$viewParams = array(
				'presetComment' => $presetComment
		);
	
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_PresetComment_Edit', 'BRARS_preset_reputation_comment_edit', $viewParams);
	}
	
	public function actionSave()
	{
		$this->_assertPostOnly();
	
		$presetCommentId = $this->_input->filterSingle( 'preset_id', XenForo_Input::UINT);
		$inputs = $this->_input->filter( array(
			'title' => XenForo_Input::STRING,
			'message' => XenForo_Input::STRING,
			'active' => XenForo_Input::BOOLEAN,
		));
	
		$dw = XenForo_DataWriter::create('Brivium_AdvancedReputationSystem_DataWriter_PresetComment');
		if(!empty($presetCommentId))
		{
			$dw->setExistingData($presetCommentId);
		}
		$dw->bulkSet($inputs);
		$dw->save();
	
		return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink( 'brars-preset'));
	}
	
	public function actionDelete()
	{
		$presetCommentId = $this->_input->filterSingle( 'preset_id', XenForo_Input::UINT);
		$presetComment = $this->_getPresetCommentOrError( $presetCommentId);
		
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create( 'Brivium_AdvancedReputationSystem_DataWriter_PresetComment');
			$dw->setExistingData( $presetCommentId);
			$dw->delete();
				
			return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink( 'brars-preset'));
		} else
		{
			$viewParams = array(
					'presetComment' => $presetComment
			);
			return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_PresetComment_Delete', 'BRARS_preset_reputation_comment_delete', $viewParams);
		}
	}
	
	public function actionToggle()
	{
		$presetCommentModel = $this->_getPresetCommentModel();
		$presetComments = $presetCommentModel->getPresetComments();
		
		return $this->_getToggleResponse(
			$presetComments,
			'Brivium_AdvancedReputationSystem_DataWriter_PresetComment',
			'brars-preset',
			'active'
		);
	}
	
	protected function _getPresetCommentOrError($presetCommentId)
	{
		$info = $this->_getPresetCommentModel()->getPresetCommentById($presetCommentId);
		if (! $info)
		{
			$errorString = new XenForo_Phrase('BRARS_requested_preset_reputation_comment_not_found');
			throw new XenForo_Exception($errorString, 404);
		}
		return $info;
	}
	
	
	protected function _getPresetCommentModel()
	{
		return $this->getModelFromCache( 'Brivium_AdvancedReputationSystem_Model_PresetComment');
	}
}
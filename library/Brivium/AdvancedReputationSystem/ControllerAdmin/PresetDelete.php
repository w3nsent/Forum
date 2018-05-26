<?php
class Brivium_AdvancedReputationSystem_ControllerAdmin_PresetDelete extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$presetDeleteModel = $this->_getPresetDeleteModel();
		$presetDeletes = $presetDeleteModel->getPresetDeletes();
		$viewParams = array(
				'presetDeletes' => $presetDeletes
		);
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_PresetDelete_Index', 'BRARS_preset_delete_list', $viewParams);
	}
	
	public function actionAdd()
	{
		return $this->responseReroute( __CLASS__, 'Edit');
	}
	
	public function actionEdit()
	{
		$presetDeleteId = $this->_input->filterSingle( 'preset_delete_id', XenForo_Input::UINT);
		if ($presetDeleteId)
		{
			$presetDelete = $this->_getPresetDeleteOrError( $presetDeleteId);
		} else
		{
			$presetDelete = array();
		}
	
		$viewParams = array(
				'presetDelete' => $presetDelete
		);
	
		return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_PresetDelete_Edit', 'BRARS_preset_delete_edit', $viewParams);
	}
	
	public function actionSave()
	{
		$this->_assertPostOnly();
	
		$presetDeleteId = $this->_input->filterSingle( 'preset_delete_id', XenForo_Input::UINT);
		$inputs = $this->_input->filter( array(
			'reason' => XenForo_Input::STRING,
			'message' => XenForo_Input::STRING,
			'active' => XenForo_Input::BOOLEAN,
		));
	
		$dw = XenForo_DataWriter::create('Brivium_AdvancedReputationSystem_DataWriter_PresetDelete');
		if(!empty($presetDeleteId))
		{
			$dw->setExistingData($presetDeleteId);
		}
		$dw->bulkSet($inputs);
		$dw->save();
	
		return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink( 'brars-preset-del'));
	}
	
	public function actionDelete()
	{
		$presetDeleteId = $this->_input->filterSingle( 'preset_delete_id', XenForo_Input::UINT);
		$presetDelete = $this->_getPresetDeleteOrError( $presetDeleteId);
		
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create( 'Brivium_AdvancedReputationSystem_DataWriter_PresetDelete');
			$dw->setExistingData( $presetDeleteId);
			$dw->delete();
				
			return $this->responseRedirect( XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink( 'brars-preset-del'));
		} else
		{
			$viewParams = array(
					'presetDelete' => $presetDelete
			);
			return $this->responseView( 'Brivium_AdvancedReputationSystem_ViewAdmin_PresetDelete_Delete', 'BRARS_preset_delete_delete', $viewParams);
		}
	}
	
	public function actionToggle()
	{
		$presetDeleteModel = $this->_getPresetDeleteModel();
		$presetDeletes = $presetDeleteModel->getPresetDeletes();
		
		return $this->_getToggleResponse(
			$presetDeletes,
			'Brivium_AdvancedReputationSystem_DataWriter_PresetDelete',
			'brars-preset-del',
			'active'
		);
	}
	
	protected function _getPresetDeleteOrError($presetDeleteId)
	{
		$info = $this->_getPresetDeleteModel()->getPresetDeleteById($presetDeleteId);
		if (! $info)
		{
			$errorString = new XenForo_Phrase('BRARS_requested_preset_delete_not_found');
			throw new XenForo_Exception($errorString, 404);
		}
		return $info;
	}
	
	
	protected function _getPresetDeleteModel()
	{
		return $this->getModelFromCache( 'Brivium_AdvancedReputationSystem_Model_PresetDelete');
	}
}
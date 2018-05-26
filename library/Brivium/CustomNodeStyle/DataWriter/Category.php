<?php

class Brivium_CustomNodeStyle_DataWriter_Category extends XFCP_Brivium_CustomNodeStyle_DataWriter_Category
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$this->_getNodeModel()->getNodeExtraFields($fields);
		return $fields;
	}

	public function save()
	{
		if(!empty($GLOBALS['BRCNS_ControllerAdmin_Node'])){
			$controller = $GLOBALS['BRCNS_ControllerAdmin_Node'];
			$this->_getNodeModel()->writeNodeExtraData($this, $controller);
			unset($GLOBALS['BRCNS_ControllerAdmin_Node']);
		}
		return parent::save();
	}

	protected function _postSave()
	{
		parent::_postSave();
		$this->getModelFromCache('XenForo_Model_Style')->updateAllStylesLastModifiedDate();
	}

	protected function _getDataRegistryModel()
	{
		return $this->getModelFromCache('XenForo_Model_DataRegistry');
	}
}
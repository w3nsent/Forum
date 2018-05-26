<?php

class Brivium_CustomNodeStyle_DataWriter_Page extends XFCP_Brivium_CustomNodeStyle_DataWriter_Page
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$this->_getNodeModel()->getNodeExtraFields($fields);
		return $fields;
	}

	public function getTablesDataFromArray(array $dataArray)
	{
		if(!empty($GLOBALS['brcnsImporting'])){
			$GLOBALS['brcnsImporting'] = $dataArray;
		}
		return parent::getTablesDataFromArray($dataArray);
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
}
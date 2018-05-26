<?php

class Brivium_CustomNodeStyle_DataWriter_Node extends XFCP_Brivium_CustomNodeStyle_DataWriter_Node
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
}
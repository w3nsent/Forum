<?php

class Brivium_CustomNodeStyle_Deferred_Import extends XenForo_Deferred_Abstract
{
	public function canTriggerManually()
	{
		return true;
	}

	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge(array(
			'position' => 0,
			'batch' => 200
		), $data);

		$data['batch'] = max(1, $data['batch']);

		/* @var $nodeModel XenForo_Model_Node */
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');
		$nodes = $nodeModel->brcnsGetNodesInRange($data['position'], $data['batch']);
		if (sizeof($nodes) == 0)
		{
			XenForo_Application::setSimpleCacheData('brcnsImported', true);
			return true;
		}
		$nodeTypes = $nodeModel->getAllNodeTypes();

		foreach ($nodes as $nodeId => $nodeTypeId)
		{
			$data['position'] = $nodeId;
			if(!empty($nodeTypes[$nodeTypeId])){
				$hander = $nodeTypes[$nodeTypeId];
			}
			if(empty($hander)){
				continue;
			}
			$writer = XenForo_DataWriter::create($hander['datawriter_class']);
			$GLOBALS['brcnsImporting'] = true;
			$writer->setExistingData($nodeId);

			if($GLOBALS['brcnsImporting'] === true){
				$existingNode = $writer->getMergedData();
				if(!empty($existingNode['xf_node'])){
					$existingNode = $existingNode['xf_node'];
				}
			}else{
				$existingNode = $GLOBALS['brcnsImporting'];
			}
			unset($GLOBALS['brcnsImporting']);
			if(!isset($existingNode['brcns_select'])){
				continue;
			}
			$brcnsIconData = array();
			if(!empty($existingNode['brcns_icon_data'])){
				$brcnsIconData = @unserialize($existingNode['brcns_icon_data']);
			}
			$brcnsStyleData = array();
			if(!empty($existingNode['brcns_style_data'])){
				$brcnsStyleData = @unserialize($existingNode['brcns_style_data']);
			}

			if(!empty($existingNode['brcns_select'])){
				$brcnsIconData['brcns_select'] = $existingNode['brcns_select'];
			}
			if(!empty($existingNode['brcns_url'])){
				$brcnsIconData['brcns_url'] = $existingNode['brcns_url'];
			}
			if(!empty($existingNode['brcns_url_1'])){
				$brcnsIconData['brcns_url'] = $existingNode['brcns_url_1'];
			}
			if(!empty($existingNode['brcns_fa'])){
				$brcnsIconData['brcns_font_class'] = $existingNode['brcns_fa'];
			}
			if(!empty($existingNode['brcns_fa_1'])){
				$brcnsIconData['brcns_font_class'] = $existingNode['brcns_fa_1'];
			}
			if(!empty($existingNode['brcns_position'])){
				$brcnsIconData['brcns_position'] = $existingNode['brcns_position'];
			}
			if(!empty($existingNode['brcns_position_1'])){
				$brcnsIconData['brcns_position'] = $existingNode['brcns_position_1'];
			}
			if(!empty($existingNode['brcns_position_left'])){
				$brcnsIconData['brcns_position_left'] = $existingNode['brcns_position_left'];
			}
			if(!empty($existingNode['brcns_position_left_1'])){
				$brcnsIconData['brcns_position_left'] = $existingNode['brcns_position_left_1'];
			}
			if(!empty($existingNode['brcns_position_top'])){
				$brcnsIconData['brcns_position_top'] = $existingNode['brcns_position_top'];
			}
			if(!empty($existingNode['brcns_position_top_1'])){
				$brcnsIconData['brcns_position_top'] = $existingNode['brcns_position_top_1'];
			}
			if(!empty($existingNode['brcns_url_2'])){
				$brcnsIconData['brcns_url_2'] = $existingNode['brcns_url_2'];
			}
			if(!empty($existingNode['brcns_fa_2'])){
				$brcnsIconData['brcns_font_class_2'] = $existingNode['brcns_fa_2'];
			}
			if(!empty($existingNode['brcns_position_2'])){
				$brcnsIconData['brcns_position_2'] = $existingNode['brcns_position_2'];
			}
			if(!empty($existingNode['brcns_position_left_2'])){
				$brcnsIconData['brcns_position_left_2'] = $existingNode['brcns_position_left_2'];
			}
			if(!empty($existingNode['brcns_position_top_2'])){
				$brcnsIconData['brcns_position_top_2'] = $existingNode['brcns_position_top_2'];
			}

			if(!empty($existingNode['brcns_pixel'])){
				$brcnsIconData['brcns_pixel'] = array('height'=>$existingNode['brcns_pixel'], 'width'=>$existingNode['brcns_pixel']);
			}
			if(!empty($existingNode['brcns_pixel_1'])){
				$brcnsIconData['brcns_pixel'] = array('height'=>$existingNode['brcns_pixel_1'], 'width'=>$existingNode['brcns_pixel_1']);
			}
			if(!empty($existingNode['brcns_pixel_2'])){
				$brcnsIconData['brcns_pixel_2'] = array('height'=>$existingNode['brcns_pixel_2'], 'width'=>$existingNode['brcns_pixel_2']);
			}

			if(!empty($existingNode['brcns_icon_date'])){
				$brcnsIconData['brcns_icon_date'] = $existingNode['brcns_icon_date'];
			}
			if(!empty($existingNode['brcns_icon_date_1'])){
				$brcnsIconData['brcns_icon_date'] = $existingNode['brcns_icon_date_1'];
			}
			if(!empty($existingNode['brcns_icon_date_2'])){
				$brcnsIconData['brcns_icon_date_2'] = $existingNode['brcns_icon_date_2'];
			}

			if(!empty($existingNode['brcns_column'])){
				$brcnsStyleData['brcns_column'] = $existingNode['brcns_column'];
			}
			if(!empty($existingNode['brcns_min_width'])){
				$brcnsStyleData['brcns_min_width'] = $existingNode['brcns_min_width'];
			}
			if(!empty($existingNode['brcns_collapse'])){
				$brcnsStyleData['brcns_collapse'] = $existingNode['brcns_collapse'];
			}
			if(!empty($existingNode['brcns_search'])){
				$brcnsStyleData['brcns_search'] = $existingNode['brcns_search'];
			}
			if(!empty($existingNode['brcns_default_collapse'])){
				$brcnsStyleData['brcns_default_collapse'] = $existingNode['brcns_default_collapse'];
			}

			$styleData['brcns_column'] = max(1, $brcnsStyleData['brcns_column']);
			$writer->set('brcns_icon_data', $brcnsIconData);
			$writer->set('brcns_style_data', $brcnsStyleData);
			$writer->save();
		}

		$actionPhrase = new XenForo_Phrase('BRCNS_importing');
		//$typePhrase = $action['title'];
		$status = sprintf('%s... (%s)', $actionPhrase, XenForo_Locale::numberFormat($data['position']));

		return $data;
	}

	public function canCancel()
	{
		return true;
	}
}

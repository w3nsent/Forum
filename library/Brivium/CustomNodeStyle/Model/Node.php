<?php

class Brivium_CustomNodeStyle_Model_Node extends XFCP_Brivium_CustomNodeStyle_Model_Node
{
	public function getNodeExtraFields(&$fields)
	{
		$fields['xf_node']['brcns_icon_data']  = array('type' => XenForo_DataWriter_Node::TYPE_SERIALIZED, 'default' => 'a:0:{}');
		$fields['xf_node']['brcns_style_data'] = array('type' => XenForo_DataWriter_Node::TYPE_SERIALIZED, 'default' => 'a:0:{}');
	}

	public function brcnsGetNodesInRange($start, $limit)
	{
		$db = $this->_getDb();
		return $db->fetchPairs($db->limit('
			SELECT node_id, node_type_id
			FROM xf_node
			WHERE node_id > ?
			ORDER BY node_id
		', $limit), $start);
	}

	public function writeNodeExtraData($dataWriter, $controller)
	{
		$iconData = $controller->getInput()->filter(array(
			'brcns_select'          => XenForo_Input::STRING,
			'brcns_url'             => XenForo_Input::STRING,
			'brcns_font_class'      => XenForo_Input::STRING,
			'brcns_position'        => XenForo_Input::INT,
			'brcns_position_left'   => XenForo_Input::INT,
			'brcns_position_top'    => XenForo_Input::INT,
			'brcns_url_2'           => XenForo_Input::STRING,
			'brcns_font_class_2'    => XenForo_Input::STRING,
			'brcns_position_2'      => XenForo_Input::INT,
			'brcns_position_left_2' => XenForo_Input::INT,
			'brcns_position_top_2'  => XenForo_Input::INT,
		));
		$styleData = $controller->getInput()->filter(array(
			'brcns_column'           => XenForo_Input::INT,
			'brcns_min_width'        => XenForo_Input::INT,
			'brcns_collapse'         => XenForo_Input::INT,
			'brcns_search'           => XenForo_Input::INT,
			'brcns_default_collapse' => XenForo_Input::INT,
		));

		$brcnsIconData = array();
		$existing = $dataWriter->get('brcns_icon_data');
		if(!empty($existing)){
			$brcnsIconData = @unserialize($existing);
		}
		$iconData = array_merge($brcnsIconData, $iconData);
		$styleData['brcns_column'] = max(1, $styleData['brcns_column']);
		$dataWriter->set('brcns_icon_data', $iconData);
		$dataWriter->set('brcns_style_data', $styleData);
	}

	public function prepareNodesWithHandlers(array $nodes, array $nodeHandlers)
	{
		$nodes = parent::prepareNodesWithHandlers($nodes, $nodeHandlers);
		foreach ($nodes as &$node)
		{
			$node = $this->brcnsPrepareNode($node);
		}

		return $nodes;
	}

	public function brcnsPrepareNode($node)
	{
		if(!empty($node['brcns_icon_data'])){
			$node['brcnsIconData'] = @unserialize($node['brcns_icon_data']);
			if(!empty($node['brcnsIconData'])){
				$iconData = $node['brcnsIconData'];
				/*if(!empty($iconData['brcns_pixel'])){
					if(is_array($iconData['brcns_pixel'])){
						$width = !empty($iconData['brcns_pixel']['width'])?$iconData['brcns_pixel']['width']:0;
						$height = !empty($iconData['brcns_pixel']['height'])?$iconData['brcns_pixel']['height']:0;
					}else{
						$width = $height = $iconData['brcns_pixel'];
					}
				}else{
					$width = 0;
					$height = 0;
				}

				if(!empty($iconData['brcns_pixel_2'])){
					if(is_array($iconData['brcns_pixel'])){
						$width2 = !empty($iconData['brcns_pixel_2']['width'])?$iconData['brcns_pixel_2']['width']:0;
						$height2 = !empty($iconData['brcns_pixel_2']['height'])?$iconData['brcns_pixel_2']['height']:0;
					}else{
						$width2 = $height2 = $iconData['brcns_pixel_2'];
					}
				}else{
					$width2 = 0;
					$height2 = 0;
				}
				$iconData['brcns_width'] = $width;
				$iconData['brcns_height'] = $height;
				$iconData['brcns_width2'] = $width2;
				$iconData['brcns_height2'] = $height2;

				$options = XenForo_Application::getOptions();
				if(!empty($node['depth'])){
					if($node['depth'] == 0 && $node['node_type_id'] != 'Forum'){
						$dimensions = $options->BRCNS_iconLv1Dimension;
					}else if($node['depth'] == 1 || ($node['depth'] == 0 && $node['node_type_id'] == 'Forum')){
						$dimensions = $options->BRCNS_iconLv2Dimension;
					}else{
						$dimensions = $options->BRCNS_iconLv3Dimension;
					}
				}else{
					$dimensions = $options->BRCNS_iconLv1Dimension;
				}
				if(!$width && !$height){
					$width = $dimensions['width'];
					$height = $dimensions['height'];
				}

				if($width && ($width < $height) || !$height){
					$minSize = $width;
				}else{
					$minSize = $height;
				}

				if(!$width2 && !$height2){
					$width2 = $dimensions['width'];
					$height2 = $dimensions['height'];
				}

				if($width2 && ($width2 < $height2) || !$height2){
					$minSize2 = $width2;
				}else{
					$minSize2 = $height2;
				}
				$iconData['brcns_font_size'] = round($minSize * 0.8);
				$iconData['brcns_font_size2'] = round($minSize2 * 0.8);*/

				$node['brcnsIconData'] = $iconData;
				$node = array_merge($node, $node['brcnsIconData']);
			}
		}
		if(!empty($node['brcns_style_data'])){
			$node['brcnsStyleData'] = @unserialize($node['brcns_style_data']);
			$node = array_merge($node, $node['brcnsStyleData']);
		}
		return $node;
	}
}
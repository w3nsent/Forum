<?php

class Brivium_CustomNodeStyle_ControllerAdmin_Node extends XFCP_Brivium_CustomNodeStyle_ControllerAdmin_Node
{
	public function actionIcon()
	{
		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::INT);
		$number = $this->_input->filterSingle('number', XenForo_Input::INT);
		$nodeModel = $this->_getNodeModel();
		$node = $nodeModel->getNodeById($nodeId);
		if (!$node)
		{
			return $this->responseError(new XenForo_Phrase('requested_node_not_found'), 404);
		}
		if(!empty($node['brcns_icon_data'])){
			$node['brcnsIconData'] = @unserialize($node['brcns_icon_data']);
		}
		if(!empty($node['brcns_style_data'])){
			$node['brcnsStyleData'] = @unserialize($node['brcns_style_data']);
		}
		if ($this->isConfirmedPost())
		{
			$icons = XenForo_Upload::getUploadedFiles('icon');
			$icon = reset($icons);

			$iconModel = $this->getModelFromCache('Brivium_CustomNodeStyle_Model_Icon');

			if ($icon)
			{
				$iconModel->uploadIcon($icon, $node, $number, $node['node_type_id']);
			}
			else if ($this->_input->filterSingle('delete', XenForo_Input::UINT))
			{
				$iconModel->deleteIcon($node['node_id'], $number, true);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('nodes/edit', $node)
			);
		}
		else
		{
			if ($nodeId && $nodeType = $this->_getNodeModel()->getNodeTypeByNodeId($nodeId))
			{
				$viewParams = array(
					'node'   => $node,
					'number' => $number,
				);
				if(!empty($node['brcnsIconData'])){
					$viewParams['brcnsIconData'] = $node['brcnsIconData'];
				}
				if(!empty($node['brcnsStyleData'])){
					$viewParams['brcnsStyleData'] = $node['brcnsStyleData'];
				}
				return $this->responseView('Brivium_CustomNodeStyle_ViewAdmin_Node_Icon', 'BRCNS_node_icon', $viewParams);
			}
			else
			{
				return $this->responseError(new XenForo_Phrase('requested_node_not_found'), 404);
			}
		}
	}
}
<?php
class Audentio_UIX_ControllerPublic_Search extends XFCP_Audentio_UIX_ControllerPublic_Search
{
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && $response->viewName == 'XenForo_ViewPublic_Search_Form') {
            foreach ($response->params['nodes'] as $nodeId=>$node) {
                if ($node['node_type_id'] == 'uix_nodeLayoutSeparator') {
                    unset($response->params['nodes'][$nodeId]);
                }
            }
        }

        return $response;
    }
}

if (false) {
    class XFCP_Audentio_UIX_ControllerPublic_Search extends XenForo_ControllerPublic_Search {}
}
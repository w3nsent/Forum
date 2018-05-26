<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Error extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Error
{
    public function actionLoginrequired()
    {
        return $this->responseView('phc_ACPPlus_ViewAdmin_DBList', 'acpp_dbtools_password', array());
    }
}
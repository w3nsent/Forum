<?php

class phc_ACPPlus_Extend_XenForo_ControllerPublic_Error extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerPublic_Error
{
    public function actionErrorNotFound()
    {
        /*
         * ToDo: Will not be developed further!
            $NFDetection = new phc_ACPPlus_Helper_404Detection();
            $NFDetection->checkUser();
         */

        return $this->getNotFoundResponse();
    }
}
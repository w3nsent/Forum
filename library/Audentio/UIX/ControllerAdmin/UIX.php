<?php

class Audentio_UIX_ControllerAdmin_UIX extends XenForo_ControllerAdmin_Abstract
{
    public function actionIndex()
    {
        return $this->responseView('Audentio_UIX_ViewAdmin_UIX_Splash', 'uix_splash');
    }

    public function actionDismiss()
    {
        $update = $this->getModelFromCache('XenForo_Model_DataRegistry')->get('uix_update');
        $update['show_notice'] = 0;

        $this->getModelFromCache('XenForo_Model_DataRegistry')->set('uix_update', $update);

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, XenForo_Link::buildAdminLink('index'));
    }
}

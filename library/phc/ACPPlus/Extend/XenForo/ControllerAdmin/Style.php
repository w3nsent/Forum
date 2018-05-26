<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Style extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Style
{
	public function actionTemplates()
	{
        $addonId = $this->_input->filterSingle('addon', XenForo_Input::STRING);

        phc_ACPPlus_Helper_ACPPlus::getFilterData($addonId, 'acpp_template_filter_addon');

	    $res = parent::actionTemplates();

        if(!$res instanceOf XenForo_ControllerResponse_View)
            return $res;

        $res->params['addons'] = $this->_getAddOnModel()->getAddOnOptionsListExt();

        if(!empty($GLOBALS['acpp_template_filter_addon']))
        {
            $selAddon = $GLOBALS['acpp_template_filter_addon'];

            if(!empty($res->params['addons'][$selAddon]))
            {
                $res->params['selected_addons'] = $res->params['addons'][$selAddon];
            }
        }

        return $res;
    }

    /**
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }
}
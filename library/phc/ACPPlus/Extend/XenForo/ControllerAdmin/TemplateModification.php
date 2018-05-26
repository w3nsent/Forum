<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_TemplateModification extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_TemplateModification
{
    public function actionIndex()
    {
        $GLOBALS['bypassAddOnList'] = true;
        $res = parent::actionIndex();

        if($res instanceof XenForo_ControllerResponse_View)
        {
            $listType = $this->_input->filterSingle('listType', XenForo_Input::STRING);

            if(!$listType)
                $listType = 'active';

            $addons = $res->params['addOns'];
            $res->params['listType'] = $listType;

            foreach($res->params['groupedModifications'] as $key => $group)
            {
                if(!$key && $listType == 'active')
                {
                    continue;
                }
                elseif(!$key && $listType == 'disabled')
                {
                    unset($res->params['groupedModifications'][$key]);
                }

                if(!isset($addons[$key]['active']))
                    continue;

                if($listType == 'active')
                {
                    if(!$addons[$key]['active'])
                    {
                        unset($res->params['groupedModifications'][$key]);
                    }
                }
                elseif($listType == 'disabled')
                {
                    if($addons[$key]['active'])
                    {
                        unset($res->params['groupedModifications'][$key]);
                    }
                }
            }
        }

        return $res;
    }
}
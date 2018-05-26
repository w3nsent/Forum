<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Language extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Language
{
	public function actionPhrases()
	{
        $addonId = $this->_input->filterSingle('addon', XenForo_Input::STRING);
        $phrasenType = $this->_input->filterSingle('phrase', XenForo_Input::STRING);

        phc_ACPPlus_Helper_ACPPlus::getFilterData($addonId, 'acpp_phrase_filter_addon');
        phc_ACPPlus_Helper_ACPPlus::getFilterData($phrasenType, 'acpp_phrase_filter_phrases_by');

        $res = parent::actionPhrases();

        if(!$res instanceOf XenForo_ControllerResponse_View)
            return $res;

        $filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
        if(isset($filter['value']))
        {
            $res->params['_filter']['value'] = $filter['value'];
        }
        if(isset($filter['prefix']))
        {
            $res->params['_filter']['prefix'] = $filter['prefix'];
        }

        $res->params['addons'] = $this->_getAddOnModel()->getAddOnOptionsListExt();

        if(!empty($GLOBALS['acpp_phrase_filter_addon']))
        {
            $selAddon = $GLOBALS['acpp_phrase_filter_addon'];

            if(!empty($res->params['addons'][$selAddon]))
            {
                $res->params['selected_addons'] = $res->params['addons'][$selAddon];
            }
        }

        if(!empty($GLOBALS['acpp_phrase_filter_phrases_by']))
        {
            $res->params['selected_by'] = $GLOBALS['acpp_phrase_filter_phrases_by'];
        }

        if(!empty($res->params['phrases']))
        {
            $languageId = $this->_input->filterSingle('language_id', XenForo_Input::UINT);

            $titles = [];

            foreach($res->params['phrases'] as $phrase)
            {
                $titles[] = $phrase['title'];
            }

            $origPhrases = $this->_getACPPlusModel()->fetchPhraseInLanguageByTitles($titles, 0);

            $specificPhrases = $this->_getACPPlusModel()->fetchPhraseInLanguageByTitles($titles, $languageId);


            foreach($res->params['phrases'] as $key => $phrase)
            {
                if(isset($specificPhrases[$phrase['title']]))
                {
                    $res->params['phrases'][$key]['phrase_text'] = $specificPhrases[$phrase['title']]['phrase_text'];
                }
                else
                {
                    $res->params['phrases'][$key]['phrase_text'] = $origPhrases[$phrase['title']]['phrase_text'];
                }
            }
        }

        return $res;
	}

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }

    /**
     * @return XenForo_Model_AddOn
     */
    protected function _getAddOnModel()
    {
        return $this->getModelFromCache('XenForo_Model_AddOn');
    }
}
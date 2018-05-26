<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Phrase extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Phrase
{
    protected function _getPhraseAddEditResponse(array $phrase, $inputLanguageId, $inputPhraseId = 0)
    {
        $res = parent::_getPhraseAddEditResponse($phrase, $inputLanguageId, $inputPhraseId);

        $quickEdit = $this->_input->filterSingle('quick_edit', XenForo_Input::BOOLEAN);

        if(!$quickEdit)
        {
            return $res;
        }

        return $this->responseView('XenForo_ViewAdmin_Phrase_Edit', 'acpp_phrase_edit', $res->params);
    }


    public function actionSave()
    {
        $this->_assertPostOnly();

        $quickEdit = $this->_input->filterSingle('quick_edit', XenForo_Input::BOOLEAN);

        if($quickEdit)
        {
            $data = $this->_input->filter(array(
                'title' => XenForo_Input::STRING,
                'phrase_text' => array(XenForo_Input::STRING, 'noTrim' => true),
                'language_id' => XenForo_Input::UINT,
                'global_cache' => XenForo_INPUT::UINT,
                'addon_id' => XenForo_Input::STRING
            ));

            if (!$this->_getPhraseModel()->canModifyPhraseInLanguage($data['language_id']))
            {
                return $this->responseError(new XenForo_Phrase('this_phrase_can_not_be_modified'));
            }

            $writer = XenForo_DataWriter::create('XenForo_DataWriter_Phrase');
            if ($phraseId = $this->_input->filterSingle('phrase_id', XenForo_Input::UINT))
            {
                $writer->setExistingData($phraseId);
            }

            $writer->bulkSet($data);

            if ($writer->get('language_id') > 0)
            {
                $writer->updateVersionId();
            }

            $writer->save();

            $language = $this->_getLanguageModel()->getLanguageById($writer->get('language_id'), true);

            $view = $this->responseView('', 'acpp_phrase_helper', array(
                'phrase' => $writer->getMergedData(),
                'language' => $language,
            ));

            $view->jsonParams = array(
                'IsPhrase' => true,
                'redirect' => XenForo_Link::buildAdminLink('languages/phrases', $language)
            );
            return $view;
        }

        return parent::actionSave();
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
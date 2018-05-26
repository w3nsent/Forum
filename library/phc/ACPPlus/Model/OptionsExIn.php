<?php
class phc_ACPPlus_Model_OptionsExIn extends XenForo_Model
{
    public function getXML($optionGroups)
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $rootNode = $document->createElement('acpp_options_export');
        $document->appendChild($rootNode);

        foreach($optionGroups as $title => $group)
        {
            $dataNote = $rootNode->appendChild($document->createElement($title));
            $this->appendOptionsAddOnXml($dataNote, $group);
        }

        return $document;
    }

    public function appendOptionsAddOnXml(DOMElement $rootNode, $options)
    {
        $document = $rootNode->ownerDocument;

        foreach ($options AS $option)
        {
            $optionNode = $document->createElement('option');

            $optionNode->setAttribute('option_id', $option['option_id']);
            $optionNode->setAttribute('edit_format', $option['edit_format']);
            $optionNode->setAttribute('data_type', $option['data_type']);

            if($option['validation_class'])
            {
                $optionNode->setAttribute('validation_class', $option['validation_class']);
                $optionNode->setAttribute('validation_method', $option['validation_method']);
            }

            XenForo_Helper_DevelopmentXml::createDomElements($optionNode, array(
                'option_value' => str_replace("\r\n", "\n", $option['option_value'])
            ));

            $rootNode->appendChild($optionNode);
        }

        return $document;
    }

    public function insertXML(SimpleXMLElement $xml, &$vaildOptions)
    {
        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $optionModel = $this->_getOptionModel();

        // Check XML Group!
        if((string)$xml->getName() != 'acpp_options_export')
            return 'fail';

        foreach($xml as $group)
        {
            $groupId = (string)$group->getName();

            $groupData = $this->_getOptionModel()->getOptionGroupById($groupId);

            if(!$groupData)
            {
                $vaildOptions['not_exists_group_id'][$groupId] = $groupData;
                continue;
            }

            if(empty($group->option))
                return 'fail';

            foreach($group->option AS $data)
            {
                $optionId = (string)$data['option_id'];
                $options = $optionModel->getOptionsInGroup($groupId);

                $newData = self::xml2array($data);

                if(isset($options[$optionId]))
                {
                    $origOption = $options[$optionId];

                    // Prüfen ob es die gleiche Option Werte sind!
                    if($newData['edit_format'] != $origOption['edit_format'])
                    {
                        $vaildOptions['edit_format'][$optionId] = $newData;
                        continue;
                    }
                    if($newData['data_type'] != $origOption['data_type'])
                    {
                        $vaildOptions['data_type'][$optionId] = $newData;
                        continue;
                    }

                    if(!empty($origOption['validation_class']) && $newData['validation_class'] != $origOption['validation_class'])
                    {
                        $vaildOptions['validation_class'][$optionId] = $newData;
                        continue;
                    }
                    if(!empty($origOption['validation_method']) && $newData['validation_method'] != $origOption['validation_method'])
                    {
                        $vaildOptions['validation_method'][$optionId] = $newData;
                        continue;
                    }

                    // update the Option!
                    if(isset($newData['option_value']))
                        $optionModel->updateOption($optionId, (string)$newData['option_value']);
                }
                else
                {
                    $vaildOptions['not_exists'][$optionId] = $newData;
                }
            }
        }

        XenForo_Db::commit($db);

        $optionModel->rebuildOptionCache();
        $optionModel->getModelFromCache('XenForo_Model_Style')->updateAllStylesLastModifiedDate();
        $optionModel->getModelFromCache('XenForo_Model_AdminTemplate')->updateAdminStyleLastModifiedDate();

        if($vaildOptions)
            return 'errors';

        return false;
    }

    protected static function xml2array($xmlObject, $out = array())
    {
        if(isset($xmlObject->option_value))
            $out['option_value'] = (string)$xmlObject->option_value;

        foreach((array)$xmlObject as $index => $node)
        {
            if($index == '@attributes')
            {
                return self::xml2array($node, $out);
                continue;
            }

            $out[$index] = (is_object($node)) ? self::xml2array($node) : $node;
        }

        return $out;
    }

    public static function generateImportErrors($errors)
    {
        $finalErrorText = '';
        $keys = array_keys($errors);

        foreach($keys as $key)
        {
            $text = new XenForo_Phrase('acpp_options_import_error_' . $key, array(
                'error_string' => implode('<br />', self::getErrorsFromKey($key, $errors)),
            ), false);

            $finalErrorText .= $text->render();
        }

        return $finalErrorText;
    }

    protected static function getErrorsFromKey($key, $errors)
    {
        $data = array();
        foreach($errors[$key] as $id => $error)
        {
            $data[] = $id;
        }

        return $data;
    }

    /**
     * @return XenForo_Model_Option
     */
    protected function _getOptionModel()
    {
        return $this->getModelFromCache('XenForo_Model_Option');
    }
}
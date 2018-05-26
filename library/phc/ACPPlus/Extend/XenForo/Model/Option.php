<?php

class phc_ACPPlus_Extend_XenForo_Model_Option extends XFCP_phc_ACPPlus_Extend_XenForo_Model_Option
{
    public function importOptionsAddOnXml(SimpleXMLElement $xml, $addOnId)
    {
        $GLOBALS['acppGroupUpdate'] = true;
        return parent::importOptionsAddOnXml($xml, $addOnId);
    }

    public function appendOptionsAddOnXml(DOMElement $rootNode, $addOnId)
    {
        $GLOBALS['acppGroupExport'] = true;
        return parent::appendOptionsAddOnXml($rootNode, $addOnId);
    }

    public function prepareOptionGroupFetchOptions(array $fetchOptions)
    {
        $res = parent::prepareOptionGroupFetchOptions($fetchOptions);

        if(!empty($GLOBALS['acppGroupExport']))
        {
            $res['selectFields'] .=  ', option_group.default_display_order as display_order';
        }

        return $res;
    }
}
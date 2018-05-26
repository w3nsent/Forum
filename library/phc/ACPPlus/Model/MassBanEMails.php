<?php
class phc_ACPPlus_Model_MassBanEMails extends XenForo_Model
{
    public function getXML($mails)
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $document->formatOutput = true;

        $rootNode = $document->createElement('MassBanEMails');
        $document->appendChild($rootNode);

        $this->appendXML($rootNode, $mails, 'emails');
        $document->appendChild($rootNode);

        return $document;
    }

    public function appendXML(DOMElement $rootNode, array $datas, $mode)
    {
        $document = $rootNode->ownerDocument;

        foreach($datas AS $data)
        {
            $keys = array_keys($data);
            $kwmNode = $document->createElement($mode);

            foreach($keys as $key)
            {
                $kwmNode->setAttribute($key, $data[$key]);
            }

            $rootNode->appendChild($kwmNode);
        }
    }

    public function insertXML(SimpleXMLElement $xml)
    {
        $db = $this->_getDb();
        XenForo_Db::beginTransaction($db);

        $emails = $xml->emails;
        foreach($emails AS $email)
        {
            $this->_getBanningModel()->banEmail((string)$email['banned_email']);
        }

        XenForo_Db::commit($db);
    }

    protected function _getBanningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Banning');
    }
}
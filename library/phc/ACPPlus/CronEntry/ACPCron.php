<?php

class phc_ACPPlus_CronEntry_ACPCron
{
	public static function generateAttachmentTotals()
	{
		XenForo_Model::create('phc_ACPPlus_Model_ACPPlus')->generateAttachmentTotals();
	}

    public static function getXfVersions()
    {
        $userAgent = 'ACPPlus XF Versions Checker';
        $versions = array();

        try
        {
            $client = XenForo_Helper_Http::getClient('https://xen-hilfe.de/getxfversions', array(
                'useragent' => $userAgent
            ));

            $response = $client->request('GET');
            $xfVersions = $response->getBody();
            $status = $response->getStatus();

            if(!empty($xfVersions) && $status == 200)
            {
                $xml = @simplexml_load_string($xfVersions);

                if($xml instanceof SimpleXMLElement)
                {
                    if($xml->children() instanceof SimpleXMLElement)
                    {
                        foreach($xml->children() as $child)
                        {
                            $product = (string)$child->getName();
                            $versions[$product] = (string)$child['version'];
                        }
                    }
                }
            }
        }
        catch (Zend_Http_Client_Exception $e) { }
        catch (Zend_Uri_Exception $e) { }
        catch (Exception $e) { }

        XenForo_Application::setSimpleCacheData('acpp_xf_versions', serialize($versions));
    }
}
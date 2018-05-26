<?php

class phc_ACPPlus_ViewAdmin_Export extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		$this->setDownloadFileName( (!empty($this->_params['fileName']) ? $this->_params['fileName'] : 'unknow') . '.xml');
		return $this->_params['xml']->saveXml();
	}

	public function renderRaw()
    {
        $csvFile = $this->_params;

        if (!headers_sent() && function_exists('header_remove'))
        {
            header_remove('Expires');
            header('Cache-control: private');
        }

        $this->_response->setHeader('Content-type', 'application/octet-stream', true);
        $this->setDownloadFileName( (!empty($this->_params['fileName']) ? $this->_params['fileName'] : 'unknow') . '.csv');

        $this->_response->setHeader('Content-Length', filesize($this->_params['filePath']), true);
        $this->_response->setHeader('X-Content-Type-Options', 'nosniff');

        return new XenForo_FileOutput($this->_params['filePath']);
    }
}
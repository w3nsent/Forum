<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to http://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Ads Manager Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: http://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_AdsManager_ViewPublic_Download extends XenForo_ViewPublic_Base
{
	public function renderRaw()
	{
		$invoice = $this->_params['invoice'];

		if (!headers_sent() && function_exists('header_remove'))
		{
			header_remove('Expires');
			header('Cache-control: private');
		}

		$this->_response->setHeader('Content-type', 'application/octet-stream', true);
		$this->setDownloadFileName($invoice['download']);

		$file = XenForo_Helper_File::getExternalDataPath() . "/Siropu/invoices/{$invoice['transaction_id']}/{$invoice['download']}";

		$this->_response->setHeader('Content-Length', filesize($file), true);
		$this->_response->setHeader('X-Content-Type-Options', 'nosniff');

		return new XenForo_FileOutput($file);
	}
}

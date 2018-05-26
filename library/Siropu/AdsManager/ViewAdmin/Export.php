<?php

class Siropu_AdsManager_ViewAdmin_Export extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		if ($this->_params['packageId'])
		{
			$export = 'package_' . $this->_params['packageId'];
		}
		else
		{
			$export = 'ad_' . $this->_params['adId'];
		}

		$this->setDownloadFileName('siropu_ads_manager_export_' . $export . '.xml');
		return $this->_params['xml']->saveXml();
	}
}

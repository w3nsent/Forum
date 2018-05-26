<?php

class Brivium_ModernStatistic_ViewPublic_ModernStatistic extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$output = array();
		$userId = !empty($this->_params['userId'])?$this->_params['userId']:0;
		if(!empty($this->_params['tabParams']) && !empty($this->_params['modernStatistic']) && isset($this->_params['tabId'])){
			$tabParams = $this->_params['tabParams'];
			$modernStatistic = $this->_params['modernStatistic'];
			$tabId = $this->_params['tabId'];
			$tabHtml = '';
			if(!empty($tabParams['renderedHtml'])){
				$tabHtml = $tabParams['renderedHtml'];
				unset($tabParams['renderedHtml']);
			}else if(!empty($tabParams['items']) && !empty($tabParams['template'])){
				$template = $this->createTemplateObject($tabParams['template'], $this->getParams());
				$template->setParams($tabParams);
				$tabHtml = $template->render();
			}

			$cachedStatistic = array(
				'cache_html'	=> '',
				'cache_params'	=> array(),
				'tabCacheHtmls'	=> array(),
				'tabCacheParams'=> array(),
			);
			if(!empty($this->_params['cachedStatistic'])){
				$cachedStatistic = $this->_params['cachedStatistic'];
				$cachedStatistic = array_merge(array(
					'cache_html'	=> '',
					'cache_params'	=> array(),
					'tabCacheHtmls'	=> array(),
					'tabCacheParams'=> array(),
				), $cachedStatistic);
			}

			if(!empty($modernStatistic['enable_cache']) && !empty($modernStatistic['cache_time'])){
				unset($tabParams['cachedStatistic']);
				$cachedStatistic['tabCacheHtmls'][$tabId] = $tabHtml;
				$cachedStatistic['tabCacheParams'][$tabId] = $tabParams;
				$modernModel = XenForo_Model::create('Brivium_ModernStatistic_Model_ModernStatistic');
				$saveData = array(
					'cache_html' => $cachedStatistic['cache_html'],
					'cache_params' => $cachedStatistic['cache_params'],
					'tab_cache_htmls' => $cachedStatistic['tabCacheHtmls'],
					'tab_cache_params' => $cachedStatistic['tabCacheParams']
				);
				$modernModel->saveCacheForStatistic($modernStatistic['modern_statistic_id'], $userId, $saveData);
			}
			$output['tabContentHtml'] = $tabHtml;

			if (!empty($this->_params['limit'])) {
				$output['limit'] = $this->_params['limit'];
			}
			if (isset($this->_params['tabId'])) {
				$output['tabId'] = $this->_params['tabId'];
			}
			$output['pageTime'] = microtime(true) - XenForo_Application::get('page_start_time');
		}

		$output = XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
		return $output;
	}
	/*
	public function renderHtml()
	{
		$output = array();
		if (!empty($this->_params['items'])&&!empty($this->_params['template'])) {
			$template = $this->createTemplateObject($this->_params['template'], $this->_params);
			$template->setParams($this->_params);
			$output['tabContentHtml'] = $template->render();
		}
		if (!empty($this->_params['limit'])) {
			$output['limit'] = $this->_params['limit'];
		}
		return array();
	}
	*/
}

<?php

class CopyPasteReadMore_Listener {
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{	
		switch ($hookName) {
			case 'CopyPasteReadMore_listener':
				$results_tags = '';
				$results = '';
				$options = Xenforo_Application::get('options');
				
				$append_text = $options->CopyPasteReadMore_text;
				
				$search_tags = array('{page_url}', '{page_title}', '{boardTitle}');
				$replace_tags = array('"+document.location.href+"', '"+document.title+"', $options->boardTitle);
				$results_tags = str_replace($search_tags, $replace_tags, $append_text);
				
				$search = "{APPEND_TEXT}";
				$replace = $results_tags;
				$results = str_replace($search, $replace, $contents);
				
				$contents = $results;
				break;
			case 'body':
				$ourTemplate = $template->create('CopyPasteReadMore_listener_js', $template->getParams());
				$rendered = $ourTemplate->render();
				$contents = $contents.$rendered;
				break;
			case 'footer_links_legal':
				$ourTemplate = $template->create('CopyPasteReadMore_footer', $template->getParams());
				$rendered = $ourTemplate->render();
				$contents = $contents.$rendered;
				break;

		}
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName) {
			case 'body':
				$template->preloadTemplate('CopyPasteReadMore_listener_js');
				break;
			case 'footer_links_legal':
				$template->preloadTemplate('CopyPasteReadMore_footer');
				break;
		}
	}
}
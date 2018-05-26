<?php

/**
 * Helper Brivium Addon for EventListener.
 *
 * @package Brivium_BriviumHelper
 * Version 1.2.0
 */
class Brivium_BriviumHelper_EventListeners
{
	protected static $_copyrightNotice = null;
	protected static $_templateList = array(
		'admin'	=> array(
			'_all'	=> array()
		),
		'public'	=> array(
			'_all'	=> array()
		)
	);
	public static $setCopyright = null;
	public static $listenerClasses = null;
	public static $briviumAddOns = null;
	public static $addOns = null;

	/**
	 * Array to cache model objects
	 *
	 * @var array
	 */
	protected static $_modelCache = array();

	public static function initListenerClass(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		$cacheData = self::getModelFromCache('XenForo_Model_DataRegistry')->getMulti(array('brListenerClasses', 'brBriviumAddOns'));
		if(!empty($cacheData['brListenerClasses']) && is_array($cacheData['brListenerClasses'])){
			self::$listenerClasses = $cacheData['brListenerClasses'];
		}else{
			self::$listenerClasses = self::getModelFromCache('Brivium_BriviumHelper_Model_ListenerClass')->rebuildListenerClassCache();
		}
		if(!empty($cacheData['brBriviumAddOns']) && is_array($cacheData['brBriviumAddOns'])){
			self::$briviumAddOns = $cacheData['brBriviumAddOns'];
		}else{
			self::$briviumAddOns = self::getModelFromCache('Brivium_BriviumHelper_Model_ListenerClass')->rebuildBriviumAddOnsCache();
		}
		XenForo_Application::set('brBriviumAddOns', self::$briviumAddOns);
		if (XenForo_Application::isRegistered('addOns'))
		{
			self::$addOns = XenForo_Application::get('addOns');
		}
		XenForo_Application::set('brListenerClasses', self::$listenerClasses);
	}

	public static function loadClassExtend($classType, $class, &$extend)
	{
		if(!empty(self::$listenerClasses) &&
			!empty(self::$listenerClasses[$classType][$class]) &&
			is_array(self::$listenerClasses[$classType][$class])
		)
		{
			foreach(self::$listenerClasses[$classType][$class] as $extendClass){
				if(is_array($extendClass)){
					if(!empty($extendClass['class_extend']) && !empty($extendClass['addon_id']) && !empty(self::$addOns[$extendClass['addon_id']])){
						$extend[] = $extendClass['class_extend'];
					}
				}else{
					$extend[] = $extendClass;
				}
			}
			$extend = array_unique($extend);
		}
	}

	public static function loadClass($class, &$extend)
	{
		$classType = 'load_class';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassBbCode($class, &$extend)
	{
		$classType = 'load_class_bb_code';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassController($class, &$extend)
	{
		$classType = 'load_class_controller';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassDatawriter($class, &$extend)
	{
		$classType = 'load_class_datawriter';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassImporter($class, &$extend)
	{
		$classType = 'load_class_importer';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassMail($class, &$extend)
	{
		$classType = 'load_class_mail';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassModel($class, &$extend)
	{
		$classType = 'load_class_model';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassRoutePrefix($class, &$extend)
	{
		$classType = 'load_class_route_prefix';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassSearchData($class, &$extend)
	{
		$classType = 'load_class_search_data';
		self::loadClassExtend($classType, $class, $extend);
	}

	public static function loadClassView($class, &$extend)
	{
		$classType = 'load_class_view';
		self::loadClassExtend($classType, $class, $extend);
	}

	protected static function _setCopyrightNotice($copyrightNotice = '')
	{
		self::$_copyrightNotice = (string) '<div id="BRCopyright" class="concealed muted" style="float:left;margin-left: 10px;"><style>@media (max-width:480px){.Responsive #BRCopyright span{display: none;}}</style><div class="muted"><a href="http://brivium.com/" class="concealed" title="Brivium Limited"><span>XenForo </span>Add-ons by Brivium<span> &trade;  &copy; 2012-'.date("Y").' Brivium LLC.</span></a></div></div>';
	}

	protected static $_needCopyright = null;

	protected static function _checkCopyrightRequire()
	{
		if(self::$_needCopyright===null){
			self::$_needCopyright = false;
			foreach(self::$briviumAddOns AS $brAddon){
				if(!empty($brAddon['addon_id']) && !empty(self::$addOns[$brAddon['addon_id']])){
					self::$_needCopyright = true;
					return false;
				}
			}
		}
		return false;
	}

	public static function _setTemplateCreate()
	{
	}

	public static function initTemplateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if(!empty(self::$_templateList)){
			$templateList = self::$_templateList;
			if($template instanceof XenForo_Template_Admin){
				if(!empty($templateList['admin'][$templateName]) && is_array($templateList['admin'][$templateName])){
					foreach($templateList['admin'][$templateName] as $key => $templateTitle){
						$template->preloadTemplate($templateTitle);
					}
					unset($templateList['admin'][$templateName], $templateTitle);
				}
				if(!empty($templateList['admin']['_all']) && is_array($templateList['admin']['_all'])){
					foreach($templateList['admin']['_all'] as $key => $templateTitle){
						$template->preloadTemplate($templateTitle);
					}
					unset($templateList['admin']['_all'], $templateTitle);
				}
			}else{
				if(!empty($templateList['public'][$templateName]) && is_array($templateList['public'][$templateName])){
					foreach($templateList['public'][$templateName] as $key => $templateTitle){
						$template->preloadTemplate($templateTitle);
					}
					unset($templateList['public'][$templateName], $templateTitle);
				}
				if(!empty($templateList['public']['_all']) && is_array($templateList['public']['_all'])){
					foreach($templateList['public']['_all'] as $key => $templateTitle){
						$template->preloadTemplate($templateTitle);
					}
					unset($templateList['public']['_all'], $templateTitle);
				}
			}
			self::$_templateList = $templateList;
		}
	}

	public static function initTemplateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if(!self::$setCopyright){
			switch ($hookName) {
				case 'page_container_breadcrumb_bottom':
					if($contents && (strpos($contents, 'BRCopyright') !== false)){
						self::$setCopyright = true;
					}
					break;
				case 'footer_after_copyright':
					if(self::_checkCopyrightRequire()){
						if(self::$_copyrightNotice===null){
							self::_setCopyrightNotice();
						}
						if(self::$_copyrightNotice && self::$setCopyright===null){
							if(!$contents || !strpos($contents, 'BRCopyright')){
								$contents = $contents . self::$_copyrightNotice;
							}
							self::$setCopyright = true;
						}
					}
					break;
			}
		}
	}

	public static function frontControllerPreRoute(XenForo_FrontController $fc)
	{
		$preRoute = XenForo_Application::getSimpleCacheData('brPreRoute');
		if($preRoute)
		{
			XenForo_Application::setSimpleCacheData('brPreRoute', false);
			if (!empty($preRoute['class']) &&
				!empty($preRoute['function']) &&
				XenForo_Helper_Php::validateCallbackPhrased($preRoute['class'], $preRoute['function']))
			{
				call_user_func(
					array($preRoute['class'], $preRoute['function']),
					$fc
				);
			}
		}
	}

	// keep this function for security
	public static function _templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
	}

	/**
	 * Fetches a model object from the local cache
	 *
	 * @param string $modelName
	 *
	 * @return XenForo_Model
	 */
	public static function getModelFromCache($modelName)
	{
		if (!isset(self::$_modelCache[$modelName]))
		{
			self::$_modelCache[$modelName] = XenForo_Model::create($modelName);
		}
		return self::$_modelCache[$modelName];
	}
}

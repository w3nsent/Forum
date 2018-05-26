<?php

/**
 * Class Listener Class for EventListener.
 *
 * @package Brivium_BriviumHelper
 * Version 1.0.0
 */
class Brivium_BriviumHelper_Model_ListenerClass extends XenForo_Model
{
	public function getAllBriviumAddOns()
	{
		try
		{
			$addOns = $this->fetchAllKeyed('
				SELECT *
				FROM xf_brivium_addon
				ORDER BY addon_id
			', 'addon_id');
		}
		catch (Zend_Db_Exception $e) {
			return array();
		}
		return $addOns;
	}
	
	public function rebuildBriviumAddOnsCache()
	{
		$addOns = $this->getAllBriviumAddOns();
		$this->_getDataRegistryModel()->set('brBriviumAddOns', $addOns);
		XenForo_Application::set('brBriviumAddOns', $addOns);
		return $addOns;
	}
	
	public function getAllListenerClassesForCache()
	{
		try
		{
			$fields = array();
			$fieldResult = $this->_getDb()->query('
				SELECT *
				FROM xf_brivium_listener_class
			');
			while ($field = $fieldResult->fetch())
			{
				if(!isset($fields[$field['event_id']][$field['class']][$field['class_extend']])){
					$fields[$field['event_id']][$field['class']][$field['class_extend']] = array(
						'addon_id' => $field['addon_id'],
						'class_extend' => $field['class_extend'],
					);
				}
			}
		}
		catch (Zend_Db_Exception $e) {return array();}
		return $fields;
	}
	
	public function rebuildListenerClassCache()
	{
		$listenerClasses = $this->getAllListenerClassesForCache();
		$listenerClasses = $this->rebuildListenerClassCodeListener($listenerClasses);
		$this->_getDataRegistryModel()->set('brListenerClasses', $listenerClasses);
		XenForo_Application::set('brListenerClasses', $listenerClasses);
		return $listenerClasses;
	}
	
	public function getEventListenerArray()
	{
		$output = array();
		$db = $this->_getDb();
		$listenerResult = $this->_getDb()->query('
			SELECT listener.event_listener_id, listener.event_id, listener.callback_method
			FROM xf_code_event_listener AS listener
			WHERE listener.active = 1
				AND (listener.callback_class =  '.$db->quote('Brivium_BriviumHelper_EventListeners') . ')
			ORDER BY listener.event_id ASC, listener.execute_order
		');
		while ($listener = $listenerResult->fetch())
		{
			$output[$listener['event_id']][$listener['callback_method']] = $listener['event_listener_id'];
		}

		return $output;
	}

	public function rebuildListenerClassCodeListener($listenerClasses = array())
	{
		if(!$listenerClasses){
			$listenerClasses = $this->getAllListenerClassesForCache();
		}
		$db = $this->_getDb();
				
		$events = $this->getEventListenerArray();
		
		$rows = array();
		foreach($listenerClasses AS $eventId => &$listenerClass){
			if(!empty($events[$eventId])){
				unset($events[$eventId]);
				continue;
			}
			$class = 'Brivium_BriviumHelper_EventListeners';
			
			$method = str_replace(' ','',ucwords(strtolower(str_replace('_',' ',$eventId))));
			$method = strtolower(substr($method, 0, 1)).substr($method, 1);
			if (!XenForo_Application::autoload($class) || !method_exists($class, $method))
			{
				continue;
			}
			
			$rows[] = '(' . $db->quote($eventId) 
			. ', 10 '
			. ', ' . $db->quote($method)
			. ', ' . $db->quote('Brivium_BriviumHelper_EventListeners')
			. ', ' . $db->quote($method)
			. ', 1 '
			. ')';
		}
		
		if($listenerClasses){
			if(empty($events['init_dependencies']['initListenerClass'])){
				$rows[] = "('init_dependencies', 10, '', 'Brivium_BriviumHelper_EventListeners', 'initListenerClass', 1)";
			}else{
				unset($events['init_dependencies']['initListenerClass']);
			}
			if(empty($events['template_hook']['initTemplateHook'])){
				$rows[] = "('template_hook', 11, '', 'Brivium_BriviumHelper_EventListeners', 'initTemplateHook', 1)";
			}else{
				unset($events['template_hook']['initTemplateHook']);
			}
			if(empty($events['template_create']['initTemplateCreate'])){
				$rows[] = "('template_create', 11, '', 'Brivium_BriviumHelper_EventListeners', 'initTemplateCreate', 1)";
			}else{
				unset($events['template_create']['initTemplateCreate']);
			}
		}
		
		if(!empty($rows)){
			$rows = implode(' , ',$rows);
			$db->query('
				INSERT IGNORE INTO `xf_code_event_listener` 
					(`event_id`, `execute_order`, `description`, `callback_class`, `callback_method`, `active`) 
				VALUES
				'. $rows
			);
		}
		
		foreach($events AS $eventId => $event){
			if(!empty($event) && is_array($event)){
				$db->delete('xf_code_event_listener', 'event_listener_id IN (' . $db->quote($event).')');
			}
		}
		$this->getModelFromCache('XenForo_Model_CodeEvent')->rebuildEventListenerCache();
		return $listenerClasses;
	}
}

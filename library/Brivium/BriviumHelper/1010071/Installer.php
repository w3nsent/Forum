<?php

/**
 * Helper Brivium Addon for EventListener.
 *
 * @package Brivium_BriviumHelper
 * Version 1.1.0
 */
abstract class Brivium_BriviumHelper_Installer
{
	protected $_db = null;
	protected $_tables = null;
	protected $_alters = null;
	protected $_data = null;
	protected $_triggerType = null;
	protected $_queryBeforeTable = null;
	protected $_queryBeforeAlter = null;
	protected $_queryBeforeData = null;
	protected $_queryFinal = null;
	protected $_licenseData = null;
	protected $_versionId = null;
	protected $_existingVersionId = null;
	protected $_preInstallCalled = null;
	protected $_preUninstallCalled = null;
	protected $_modelCache = array();
	protected $_existingAddOn = array();
	protected $_addOnToInstall = array();
	protected $_installHash = null;
	protected $_installerType = 0;
	protected static $_addOnInstaller = null;

	protected function _getDb()
	{
		if($this->_db === null){
			$this->_db = XenForo_Application::get('db');
		}
		return $this->_db;
	}

	public function getAddOnToInstall()
	{
		return $this->_addOnToInstall;
	}

	public function getExistingAddOn()
	{
		return $this->_existingAddOn;
	}

	public function addColumn($table, $field, $attr)
	{
		if(!$this->checkIfExist($table, $field)){
			return $this->_getDb()->query("ALTER TABLE `" . $table . "` ADD `" . $field . "` " . $attr);
		}
	}

	public function removeColumn($table, $field)
	{
		if($this->checkIfExist($table, $field)){
			return $this->_getDb()->query("ALTER TABLE `" . $table . "` DROP `" . $field . "`");
		}
	}

	public function checkIfExist($table, $field)
	{
		if($this->_getDb()->fetchRow('SHOW columns FROM `' . $table . '` WHERE Field = ?', $field)){
			return true;
		}
		else {
			return false;
		}
	}

	public function checkTableExist($table)
	{
		if($this->_getDb()->fetchRow('SHOW TABLES  LIKE ?', $table)){
			return true;
		}
		else {
			return false;
		}
	}

	public function initialize($existingAddOn = array(), $addOnToInstall = array(), $triggerType = 'install')
	{
		$this->_triggerType = $triggerType;
		$this->_existingAddOn = $existingAddOn;
		$this->_addOnToInstall = $addOnToInstall;
		$this->_versionId = !empty($addOnToInstall['version_id'])?$addOnToInstall['version_id']:0;
		$this->_existingVersionId = !empty($existingAddOn['version_id'])?$existingAddOn['version_id']:0;

		if($triggerType=='install' && !$existingAddOn && $this->_installerType==1){
			$this->_checkLicense();
		}else{
			$this->_initData();
		}
	}

	/*
	*
	*	Installer
	*
	*/
	public function installAddOn($existingAddOn, $addOnToInstall)
	{
		$this->initialize($existingAddOn, $addOnToInstall);

		$this->preInstall();

		$this->_beginDbTransaction();

		$this->_install();

		$this->_postInstall();

		$this->_commitDbTransaction();

		$this->_postInstallAfterTransaction();

		return true;
	}

	public function preInstall()
	{
		if($this->_preInstallCalled)
		{
			return;
		}

		$this->_preInstallDefaults();
		$this->_preInstall();

		$this->_preInstallCalled = true;
	}

	protected function _preInstallDefaults()
	{
	}

	protected function _preInstall()
	{
	}

	protected function _install()
	{
		$requiredAddOns = $this->_getPrerequisites();
		if(!empty($requiredAddOns)){
			$this->_checkRequiredAddOns($requiredAddOns);
		}
		$db = $this->_getDb();

		if($this->_queryBeforeTable!==null && is_array($this->_queryBeforeTable)){
			foreach ($this->_queryBeforeTable AS $queryBeforeTable)
			{
				try
				{
					$db->query($queryBeforeTable);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_tables!==null && is_array($this->_tables)){
			foreach ($this->_tables AS $tableSql)
			{
				try
				{
					$db->query($tableSql);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_queryBeforeAlter!==null && is_array($this->_queryBeforeAlter)){
			foreach ($this->_queryBeforeAlter AS $queryBeforeAlter)
			{
				try
				{
					$db->query($queryBeforeAlter);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_alters!==null && is_array($this->_alters)){
			foreach ($this->_alters AS $tableName => $tableAlters)
			{
				if($tableAlters && is_array($tableAlters)){
					foreach ($tableAlters AS $tableColumn => $attributes)
					{
						try
						{
							$this->addColumn($tableName, $tableColumn, $attributes);
						}
						catch (Zend_Db_Exception $e){}
					}
				}
			}
		}

		if($this->_queryBeforeData!==null && is_array($this->_queryBeforeData)){
			foreach ($this->_queryBeforeData AS $queryBeforeData)
			{
				try
				{
					$db->query($queryBeforeData);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_data!==null && is_array($this->_data)){
			foreach ($this->_data AS $dataSql)
			{
				try
				{
					$db->query($dataSql);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_queryFinal!==null && is_array($this->_queryFinal)){
			foreach ($this->_queryFinal AS $queryFinal)
			{
				try
				{
					$db->query($queryFinal);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		$listenerClassModel = $this->getModelFromCache('Brivium_BriviumHelper_Model_ListenerClass');
		$listenerClassModel->rebuildBriviumAddOnsCache();
		$listenerClassModel->rebuildListenerClassCache();
	}

	protected function _postInstall()
	{
	}

	protected function _postInstallAfterTransaction()
	{
	}

	/*
	*
	*	Uninstaller
	*
	*/

	public static function uninstall($addOnToInstall)
	{
		if(self::$_addOnInstaller && class_exists(self::$_addOnInstaller))
		{
			$installer = self::create(self::$_addOnInstaller);
			$installer->uninstallAddOn($addOnToInstall);
		}
	}

	public function uninstallAddOn($addOnToInstall)
	{
		$this->initialize(array(), $addOnToInstall, 'uninstall');
		$this->preUninstall();

		$this->_beginDbTransaction();

		$this->_uninstall();
		$this->_postUninstall();

		$this->_commitDbTransaction();

		return true;
	}

	public function preUninstall()
	{
		if($this->_preUninstallCalled)
		{
			return;
		}

		$this->_preUninstall();

		$this->_preUninstallCalled = true;
	}

	protected function _preUninstall()
	{
	}

	protected function _uninstall()
	{
		$db = $this->_getDb();

		if($this->_queryBeforeTable!==null && is_array($this->_queryBeforeTable)){
			foreach ($this->_queryBeforeTable AS $queryBeforeTable)
			{
				try
				{
					$db->query($queryBeforeTable);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_tables!==null && is_array($this->_tables)){
			foreach ($this->_tables AS $tableName => $tableSql)
			{
				try
				{
					$db->query("DROP TABLE IF EXISTS `$tableName`");
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_queryBeforeAlter!==null && is_array($this->_queryBeforeAlter)){
			foreach ($this->_queryBeforeAlter AS $queryBeforeAlter)
			{
				try
				{
					$db->query($queryBeforeAlter);
				}
				catch (Zend_Db_Exception $e){}
			}
		}

		if($this->_alters!==null && is_array($this->_alters)){
			foreach ($this->_alters AS $tableName => $tableAlters)
			{
				if($tableAlters && is_array($tableAlters)){
					foreach ($tableAlters AS $tableColumn => $attributes)
					{
						try
						{
							$this->removeColumn($tableName, $tableColumn);
						}
						catch (Zend_Db_Exception $e){}
					}
				}
			}
		}

		if($this->_queryFinal!==null && is_array($this->_queryFinal)){
			foreach ($this->_queryFinal AS $queryFinal)
			{
				try
				{
					$db->query($queryFinal);
				}
				catch (Zend_Db_Exception $e){}
			}
		}
		$listenerClassModel = $this->getModelFromCache('Brivium_BriviumHelper_Model_ListenerClass');
		$listenerClassModel->rebuildListenerClassCache();
		$listAddOns = $listenerClassModel->rebuildBriviumAddOnsCache();
		if(empty($listAddOns)){
			$this->removeTables();
		}
	}

	public function removeTables()
	{
		$db = $this->_getDb();
		$table = array(
			'xf_brivium_addon',
			'xf_brivium_listener_class',
		);
		foreach ($table AS $tableName)
		{
			try
			{
				$db->query("DROP TABLE IF EXISTS `$tableName`");
			}
			catch (Zend_Db_Exception $e){}
		}
	}

	/**
	* Method designed to be overridden by child classes to add pre-uninstall behaviors.
	*/
	protected function _postUninstall()
	{
	}


	protected function _initData()
	{
		$this->_tables 			= $this->getDefaultTables($this->getTables());
		$this->_alters 			= $this->getAlters();
		$this->_data 			= $this->getData();
		$this->_queryBeforeTable = $this->getQueryBeforeTable();
		$this->_queryBeforeAlter = $this->getQueryBeforeAlter();
		$this->_queryBeforeData = $this->getQueryBeforeData();
		$this->_queryFinal 		= $this->getQueryFinal();
	}

	protected function _checkLicense()
	{
		if(!$response = $this->_validateLicense($errorString)){
			$this->_initData();
		}

	}

	protected function _validateLicense(&$errorString)
	{
		$addOnToInstall = $this->getAddOnToInstall();

	}

	protected function _getPrerequisites()
	{
		return array();
	}

	protected function _checkRequiredAddOns(array $requiredAddOns)
	{
		$notInstalled = array();
		$notActived = array();
		$outOfDate = array();

		$addOns = null;
		if (XenForo_Application::isRegistered('addOns'))
		{
			$addOns = XenForo_Application::get('addOns');
		}

		$addOnModel = $this->getModelFromCache('XenForo_Model_AddOn');
		foreach($requiredAddOns as $addOnId => $requiredAddOn){
			if(!is_null($addOns)){
				if(isset($addOns[$addOnId])){
					if($requiredAddOn['version_id'] && $addOns[$addOnId] < $requiredAddOn['version_id']){
						$outOfDate[] = $requiredAddOn['title'];
					}
				}else{
					$addOn = $addOnModel->getAddOnById($addOnId);
					if(!empty($addOn)){
						$notActived[] = $requiredAddOn['title'];
					}else{
						$notInstalled[] = $requiredAddOn['title'];
					}
				}
			}else{
				$addOn = $addOnModel->getAddOnById($addOnId);
				if(!empty($addOn)){
					if($requiredAddOn['version_id'] && $addOn['version_id'] < $requiredAddOn['version_id']){
						$outOfDate[] = $requiredAddOn['title'];
					}
					if(empty($addOn['version_id'])){
						$notActived[] = $requiredAddOn['title'];
					}
				}else{
					$notInstalled[] = $requiredAddOn['title'];
				}
			}
		}
		$requiredMessage = '';
		if($notInstalled){
			$requiredMessage[] = 'You must install the following required add-ons: ' . implode(', ', $notInstalled) .'.';
		}
		if($notActived){
			$requiredMessage[] = 'You must active the following required add-ons: ' . implode(', ', $notActived) .'.';
		}
		if($outOfDate){
			$requiredMessage[] = 'You must update the following required add-ons: ' . implode(', ', $outOfDate) .'.';
		}
		if($requiredMessage){
			throw new XenForo_Exception(implode("<br />", $requiredMessage), true);
		}
	}

	protected function _beginDbTransaction()
	{
		XenForo_Db::beginTransaction($this->_db);
		return true;
	}

	/**
	* Commits a new database transaction.
	*/
	protected function _commitDbTransaction()
	{
		XenForo_Db::commit($this->_db);
		return true;
	}

	public function getTables()
	{
		return array();
	}

	public function getAlters()
	{
		return array();
	}

	public function getData()
	{
		return array();
	}

	public function getQueryBeforeTable()
	{
		return array();
	}

	public function getQueryBeforeAlter()
	{
		return array();
	}

	public function getQueryBeforeData()
	{
		return array();
	}

	public function getQueryFinal()
	{
		return array();
	}

	public function getDefaultTables($tables = array())
	{
		if($this->_triggerType != 'uninstall'){
			$tables['xf_brivium_addon'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_addon` (
				  `addon_id` varchar(25) NOT NULL,
				  `title` varchar(75) NOT NULL DEFAULT '',
				  `version_id` int(11) NOT NULL,
				  `copyright_removal` tinyint(3) NOT NULL DEFAULT '0',
				  `start_date` int(10) NOT NULL DEFAULT '0',
				  `end_date` int(10) NOT NULL DEFAULT '0',
				  PRIMARY KEY (`addon_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			";
			$tables['xf_brivium_listener_class'] = "
				CREATE TABLE IF NOT EXISTS `xf_brivium_listener_class` (
				  `class` varchar(75) NOT NULL,
				  `class_extend` varchar(75) NOT NULL,
				  `event_id` varbinary(50) NOT NULL,
				  `addon_id` varbinary(25) NOT NULL DEFAULT '',
				  PRIMARY KEY (`class`,`class_extend`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			";
		}
		return $tables;
	}

	public function getModelFromCache($class)
    {
        if(!isset($this->_modelCache[$class])){
            $this->_modelCache[$class] = XenForo_Model::create($class);
        }

        return $this->_modelCache[$class];
    }

	public static function create($class)
	{
		$createClass = XenForo_Application::resolveDynamicClass($class, 'installer_brivium');
		if(!$createClass)
		{
			throw new XenForo_Exception("Invalid installer '$class' specified");
		}

		return new $createClass;
	}

}
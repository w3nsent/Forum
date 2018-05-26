<?php
class Brivium_CustomNodeStyle_Installer extends Brivium_BriviumHelper_Installer
{
	protected function _postInstall()
	{
		$brcnsImported = XenForo_Application::getSimpleCacheData('brcnsImported');
		if(!$brcnsImported){
			XenForo_Application::defer('Brivium_CustomNodeStyle_Deferred_Import', array(), 'BRSTS_importing', false);
		}
	}

	public function getAlters()
	{
		$alters = array();
		$alters["xf_node"] = array(
			'brcns_icon_data'			=>	"MEDIUMBLOB NULL DEFAULT NULL ",
			'brcns_style_data'			=>	"MEDIUMBLOB NULL DEFAULT NULL ",
		);
		return $alters;
	}

	public function getQueryFinal()
	{
		$query = array();
		$query[] = "
			DELETE FROM `xf_brivium_listener_class` WHERE `addon_id` = 'Brivium_CustomNodeStyle';
		";
		if($this->_triggerType != "uninstall"){
			$query[] = "
				REPLACE INTO `xf_brivium_addon`
					(`addon_id`, `title`, `version_id`, `copyright_removal`, `start_date`, `end_date`)
				VALUES
					('Brivium_CustomNodeStyle', 'Brivium - Custom Node Style', '1030700', 0, 0, 0);
			";
			$query[] = "
				REPLACE INTO `xf_brivium_listener_class`
					(`class`, `class_extend`, `event_id`, `addon_id`)
				VALUES
					('XenForo_ControllerAdmin_Category', 'Brivium_CustomNodeStyle_ControllerAdmin_Category', 'load_class_controller', 'Brivium_CustomNodeStyle'),
					('XenForo_NodeHandler_LinkForum', 'Brivium_CustomNodeStyle_NodeHandler_LinkForum', 'load_class', 'Brivium_CustomNodeStyle'),
					('XenForo_Model_Page', 'Brivium_CustomNodeStyle_Model_Page', 'load_class_model', 'Brivium_CustomNodeStyle'),
					('XenForo_Model_Node', 'Brivium_CustomNodeStyle_Model_Node', 'load_class_model', 'Brivium_CustomNodeStyle'),
					('XenForo_Model_LinkForum', 'Brivium_CustomNodeStyle_Model_LinkForum', 'load_class_model', 'Brivium_CustomNodeStyle'),
					('XenForo_Image_Gd', 'Brivium_CustomNodeStyle_Image_Gd', 'load_class', 'Brivium_CustomNodeStyle'),
					('XenForo_DataWriter_Page', 'Brivium_CustomNodeStyle_DataWriter_Page', 'load_class_datawriter', 'Brivium_CustomNodeStyle'),
					('XenForo_DataWriter_Node', 'Brivium_CustomNodeStyle_DataWriter_Node', 'load_class_datawriter', 'Brivium_CustomNodeStyle'),
					('XenForo_DataWriter_LinkForum', 'Brivium_CustomNodeStyle_DataWriter_LinkForum', 'load_class_datawriter', 'Brivium_CustomNodeStyle'),
					('XenForo_DataWriter_Forum', 'Brivium_CustomNodeStyle_DataWriter_Forum', 'load_class_datawriter', 'Brivium_CustomNodeStyle'),
					('XenForo_DataWriter_Category', 'Brivium_CustomNodeStyle_DataWriter_Category', 'load_class_datawriter', 'Brivium_CustomNodeStyle'),
					('XenForo_ControllerAdmin_Page', 'Brivium_CustomNodeStyle_ControllerAdmin_Page', 'load_class_controller', 'Brivium_CustomNodeStyle'),
					('XenForo_ControllerAdmin_Node', 'Brivium_CustomNodeStyle_ControllerAdmin_Node', 'load_class_controller', 'Brivium_CustomNodeStyle'),
					('XenForo_ControllerAdmin_LinkForum', 'Brivium_CustomNodeStyle_ControllerAdmin_LinkForum', 'load_class_controller', 'Brivium_CustomNodeStyle'),
					('XenForo_ControllerAdmin_Forum', 'Brivium_CustomNodeStyle_ControllerAdmin_Forum', 'load_class_controller', 'Brivium_CustomNodeStyle'),
					('XenForo_NodeHandler_Page', 'Brivium_CustomNodeStyle_NodeHandler_Page', 'load_class', 'Brivium_CustomNodeStyle');
			";
		}else{
			$query[] = "
				DELETE FROM `xf_brivium_addon` WHERE `addon_id` = 'Brivium_CustomNodeStyle';
			";
		}
		return $query;
	}
}

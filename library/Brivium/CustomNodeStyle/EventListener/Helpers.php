<?php
class Brivium_CustomNodeStyle_EventListener_Helpers extends XenForo_Template_Helper_Core
{
	public static function helperGetCustomIcon($node, $number)
	{
		if (!$node && !$number){
			return '';
		}else{
			$url = self::_getCustomNodeIcon($node, $number);
			return htmlspecialchars($url);
		}
	}
	protected static function _getCustomNodeIcon($node, $number)
	{
		if(!empty($node['brcns_icon_data']) && empty($node['brcnsIconData'])){
			$node['brcnsIconData'] = @unserialize($node['brcns_icon_data']);
		}
		if(!empty($node['brcnsIconData'])){
			$node = array_merge($node, $node['brcnsIconData']);
		}
		if(!empty($node['brcns_style_data']) && empty($node['brcnsStyleData'])){
			$node['brcnsStyleData'] = @unserialize($node['brcns_style_data']);
		}
		if(!empty($node['brcnsStyleData'])){
			$node = array_merge($node, $node['brcnsStyleData']);
		}
		if($number==2){
			if (!empty($node['brcns_icon_date_'.$number])){
				return "data/nodeIcons/". $node['node_id'] ."_". $number .".jpg?". $node['brcns_icon_date_'.$number];
			}
		}else{
			$number = 1;
			if (!empty($node['brcns_icon_date'])){
				return "data/nodeIcons/". $node['node_id'] ."_". $number .".jpg?". $node['brcns_icon_date'];
			}
		}
		return "styles/default/xenforo/avatars/avatar_s.png";
	}

}
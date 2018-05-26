<?php
class Brivium_CustomNodeStyle_Model_Page extends XFCP_Brivium_CustomNodeStyle_Model_Page
{
	public function getPages()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_page
		', 'node_id');
	}
}
?>
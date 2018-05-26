<?php
class Brivium_CustomNodeStyle_Model_LinkForum extends XFCP_Brivium_CustomNodeStyle_Model_LinkForum
{
	public function getLinkForums()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_link_forum
		', 'node_id');
	}
}
?>
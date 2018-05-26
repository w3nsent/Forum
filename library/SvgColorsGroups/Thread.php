<?php

class SvgColorsGroups_Thread extends XFCP_SvgColorsGroups_Thread
{
	public function prepareThreadFetchOptions(array $fetchOptions)
	{
		$thread = parent::prepareThreadFetchOptions($fetchOptions);

		$thread['selectFields'] .= ',
			user_rue.display_style_group_id AS last_post_display_style_group_id';
		$thread['joinTables'] .= '
			LEFT JOIN xf_user AS user_rue ON
				(user_rue.user_id = thread.last_post_user_id)';

		return array(
			'selectFields' => $thread['selectFields'],
			'joinTables'   => $thread['joinTables'],
			'orderClause'  => $thread['orderClause']
		);
	}

	public function prepareThread(array $thread, array $forum, array $nodePermissions = null, array $viewingUser = null)
	{
		$thread = parent::prepareThread($thread, $forum, $nodePermissions, $viewingUser);

		if(isset($thread['last_post_display_style_group_id']))
		{
			$thread['lastPostInfo']['display_style_group_id'] = $thread['last_post_display_style_group_id'];
		}
		
		return $thread;
	}
}
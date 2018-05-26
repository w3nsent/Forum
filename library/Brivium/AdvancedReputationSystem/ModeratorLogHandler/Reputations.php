<?php
class Brivium_AdvancedReputationSystem_ModeratorLogHandler_Reputations extends XenForo_ModeratorLogHandler_Abstract
{
	protected $_skipLogSelfActions = array(
		'edit'
	);

	protected function _log(array $logUser, array $content, $action, array $actionParams = array(), $parentContent = null)
	{
		if (is_array( $parentContent) && isset( $parentContent['title']))
		{
			$title = $parentContent['title'];
			$threadId = ! empty( $parentContent['thread_id']) ? $parentContent['thread_id'] : 0;
		} else
		{
			$postId = ! empty( $content['post_id']) ? $content['post_id'] : 0;
			
			$fetchOptions = array(
				'join' => XenForo_Model_Post::FETCH_THREAD
			);
			$post = XenForo_Model::create( 'XenForo_Model_Post')->getPostById( $postId, $fetchOptions);
			
			$title = ! empty( $post['title']) ? $post['title'] : '';
			$threadId = ! empty( $post['thread_id']) ? $post['thread_id'] : 0;
		}
		
		$dw = XenForo_DataWriter::create( 'XenForo_DataWriter_ModeratorLog');
		$dw->bulkSet( array(
			'user_id' => $logUser['user_id'],
			'content_type' => 'brivium_reputation_system',
			'content_id' => $content['reputation_id'],
			'content_user_id' => !empty($content['giver_user_id'])?$content['giver_user_id']:(!empty($content['user_id'])?$content['user_id']:0),
			'content_username' => !empty($content['giver_username'])?$content['giver_username']:$content['username'],
			'content_title' => $title,
			'content_url' => XenForo_Link::buildPublicLink( 'brars-reputations', $content),
			'discussion_content_type' => 'thread',
			'discussion_content_id' => $threadId,
			'action' => $action,
			'action_params' => $actionParams
		));
		$dw->save();
		
		return $dw->get( 'moderator_log_id');
	}

	protected function _prepareEntry(array $entry)
	{
		// will be escaped in template
		$entry['content_title'] = new XenForo_Phrase( 'BRARS_reputation_in_post_at_thread_x', array(
			'title' => $entry['content_title']
		));
		
		return $entry;
	}
}
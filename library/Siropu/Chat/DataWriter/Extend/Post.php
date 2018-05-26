<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Extend_Post extends XFCP_Siropu_Chat_DataWriter_Extend_Post
{
	protected function _postSaveAfterTransaction()
	{
		$options = XenForo_Application::get('options');
		$forum   = $this->_getForumInfo();

		if (!$this->isDiscussionFirstMessage()
			&& $this->get('message_state') == 'visible'
			&& ($this->isInsert() || $this->getExisting('message_state') == 'moderated')
			&& $options->siropu_chat_forum_activity_posts
			&& Siropu_Chat_Helper::checkForSelectedForums($forum['node_id']))
		{
			$post     = $this->getMergedData();
			$thread   = $this->getDiscussionData();
			$userId   = $this->get('user_id');
			$username = $this->get('username');

			$message = new XenForo_Phrase('siropu_chat_bot_new_post', array('user' => '[USER=' . $userId . ']' . $username . '[/USER]', 'post' => XenForo_Link::buildPublicLink('full:posts', array('post_id' => $post['post_id'])), 'thread' => '[URL=' . XenForo_Link::buildPublicLink('full:threads/unread', array('thread_id' => $thread['thread_id'], 'title' => $thread['title'])) . '][PLAIN]' . $thread['title'] . '[/PLAIN][/URL]'), false);

			$displayContent = $options->siropu_chat_forum_activity_content;

			if ($displayContent['enabled'])
			{
				if ($limit = $displayContent['limit'])
				{
					$post['message'] = XenForo_Template_Helper_Core::helperSnippet($post['message'], $limit);
				}

				$message .= ' [QUOTE="' . $username . '"]' . $post['message'] . '[QUOTE]';
			}

			$writer = XenForo_DataWriter::create('Siropu_Chat_DataWriter_Messages');
			$writer->bulkSet(array(
				'message_user_id' => $userId,
				'message_text'    => $message,
				'message_type'    => 'activity'
			));
			$writer->save();
		}

		return parent::_postSaveAfterTransaction();
	}
}
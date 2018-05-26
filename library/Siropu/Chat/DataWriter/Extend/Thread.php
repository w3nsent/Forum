<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_DataWriter_Extend_Thread extends XFCP_Siropu_Chat_DataWriter_Extend_Thread
{
	protected function _postSaveAfterTransaction()
	{
		$options = XenForo_Application::get('options');
		$forumId = $this->get('node_id');
		#$prefix  = XenForo_Template_Helper_Core::helperThreadPrefix(array('prefix_id' => $this->get('prefix_id')));

		if ((($this->isInsert()
				&& $this->get('discussion_state') == 'visible'
				&& $this->get('discussion_type') != 'redirect')
			|| ($this->isUpdate()
				&& $this->get('discussion_state') == 'visible'
				&& $this->getExisting('discussion_state') == 'moderated'))
			&& $options->siropu_chat_forum_activity_threads
			&& Siropu_Chat_Helper::checkForSelectedForums($forumId))
		{
			$forum    = $this->_getForumData();
			$threadId = $this->get('thread_id');
			$title    = $this->get('title');
			$userId   = $this->get('user_id');
			$username = $this->get('username');

			$message = new XenForo_Phrase('siropu_chat_bot_new_thread', array('user' => '[USER=' . $userId . ']' . $username . '[/USER]', 'thread' => '[URL=' . XenForo_Link::buildPublicLink('full:threads/unread', array('thread_id' => $threadId, 'title' => $title)) . '][PLAIN]' . $title . '[/PLAIN][/URL]', 'forum' => '[URL=' . XenForo_Link::buildPublicLink('full:forums', array('node_id' => $forumId, 'title' => $forum['title'])) . ']' . $forum['title'] . '[/URL]'), false);

			$displayContent = $options->siropu_chat_forum_activity_content;

			if ($displayContent['enabled'])
			{
				$post = $this->_getLastMessageInDiscussion();

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
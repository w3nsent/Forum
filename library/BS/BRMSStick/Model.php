<?php

class BS_BRMSStick_Model extends XenForo_Model
{
    public function getSticked()
    {
        $results = array();
        $threadIds = $this->_getDb()->fetchCol("
            SELECT thread.thread_id
            FROM xf_thread AS thread
            WHERE brms_stick = 1
            ORDER BY last_post_date DESC
            ");
        if ($threadIds)
        {
            $threadModel = $this->_getThreadModel();
            $forumModel = $this->_getForumModel();
            $threads = array();
            foreach ($threadIds as $k => $v) {
                $thread = $threadModel->getThreadById($v);
                $forum = $forumModel->getForumById($thread['node_id']);
                $threads[] = $threadModel->prepareThread($thread, $forum);
            }
            $results = $threadModel->modernStatisticPrepareThreads($threads, array('preview_tooltip' => 'thread_preview'));
        }
        return $results;
    }

    public function getLinkDataById($linkId)
    {
        return $this->_getDb()->fetchRow("
            SELECT link.*
            FROM xf_brmsstick_links AS link
            WHERE link_id = ?
            ", $linkId);
    }

    public function getStickStatus($threadId)
    {
        return boolval($this->_getDb()->fetchOne("
            SELECT thread.brms_stick
            FROM xf_thread AS thread
            WHERE thread_id = ?
            ", $threadId));
    }

    public function getStickedLinks()
    {
        return $this->_getDb()->fetchAll("
            SELECT link.*
            FROM xf_brmsstick_links AS link
            ORDER BY link_id DESC 
            ");
    }

    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }

    protected function _getForumModel()
    {
        return $this->getModelFromCache('XenForo_Model_Forum');
    }
}
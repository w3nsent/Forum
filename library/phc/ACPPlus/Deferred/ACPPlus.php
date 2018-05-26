<?php
class phc_ACPPlus_Deferred_ACPPlus extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'position' => 0,
            'user_id' => $data['user_id'],
            'delete_user_content' => $data['delete_user_content'],
        ), $data);

        if(empty($data['user_id']))
        {
            return false;
        }

        if(empty($data['delete_user_content']))
        {
            XenForo_Model::create('XenForo_Model_Counters')->rebuildBoardTotalsCounter();
            return false;
        }

        $db = XenForo_Application::getDb();

        $GLOBALS['bypassAddOnList'] = true;
        $addons = XenForo_Model::create('XenForo_Model_AddOn')->getAllAddOns();

        $modeName = key($data['delete_user_content']);
        switch($modeName)
        {
            /*
             * THREADS
             */
            case 'threads':
                $modeName = 'Threads';

                $conditions = array(
                    'user_id' => $data['user_id']
                );

                $fetchOptions = array(
                    'limit' => 100,
                );

                $threads = $this->_getThreadModel()->getThreads($conditions, $fetchOptions);

                if(!$threads)
                {
                    unset($data['delete_user_content']['threads']);
                    return $data;
                }
                else
                {
                    $data['position'] += count($threads);

                    XenForo_Db::beginTransaction($db);

                    foreach($threads as $thread)
                    {
                        $threadDW = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
                        $threadDW->setExistingData($thread);
                        $threadDW->delete();
                    }
                    XenForo_Db::commit($db);
                }
                break;

            /*
             * POSTINGS
             */
            case 'posts':
                $modeName = 'Posts';

                $posts = $this->_getACPPModel()->getPostsByUserId($data['user_id'], 100);

                if(!$posts)
                {
                    unset($data['delete_user_content']['posts']);
                    return $data;
                }
                else
                {
                    $data['position'] += count($posts);

                    XenForo_Db::beginTransaction($db);

                    foreach($posts as $post)
                    {
                        $postDW = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post', XenForo_DataWriter::ERROR_SILENT);
                        $postDW->setExistingData($post);

                        $postDW->delete();
                    }
                    XenForo_Db::commit($db);
                }
                break;

            /*
             * PROFILE POSTS
             */
            case 'profilePosts':
                $modeName = 'Profile Posts';

                $profilePosts = $this->_getACPPModel()->getProfilePostsByUserId($data['user_id'], 100);

                if(!$profilePosts)
                {
                    unset($data['delete_user_content']['profilePosts']);
                    return $data;
                }
                else
                {
                    $data['position'] += count($profilePosts);

                    XenForo_Db::beginTransaction($db);

                    foreach($profilePosts as $profilePost)
                    {
                        $profilePostDW = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_ProfilePost', XenForo_DataWriter::ERROR_SILENT);
                        $profilePostDW->setExistingData($profilePost);
                        $profilePostDW->delete();
                    }
                    XenForo_Db::commit($db);
                }
                break;

            /*
             * PROFILE POST COMMENTS
             */
            case 'profilePostComments':
                $modeName = 'Profile Post Comments';

                $profilePostComments = $this->_getACPPModel()->getProfilePostCommentsByUserId($data['user_id'], 100);

                if(!$profilePostComments)
                {
                    unset($data['delete_user_content']['profilePostComments']);
                    return $data;
                }
                else
                {
                    $data['position'] += count($profilePostComments);

                    XenForo_Db::beginTransaction($db);

                    foreach($profilePostComments as $profilePostComment)
                    {
                        $profilePostDW = XenForo_DataWriter::create('XenForo_DataWriter_ProfilePostComment', XenForo_DataWriter::ERROR_SILENT);
                        $profilePostDW->setExistingData($profilePostComment);
                        $profilePostDW->delete();
                    }
                    XenForo_Db::commit($db);
                }
                break;

            /*
             * XF MEDIA
             */
            case 'xfmedias':
                $modeName = 'Media';

                if(!isset($addons['XenGallery']))
                {
                    unset($data['delete_user_content']['xfmedias']);
                    return $data;
                }

                $medias = $this->_getACPPModel()->getMediaByUserId($data['user_id'], 100);

                if(!$medias)
                {
                    unset($data['delete_user_content']['xfmedias']);
                    return $data;
                }
                else
                {
                    $data['position'] += count($medias);

                    XenForo_Db::beginTransaction($db);

                    foreach($medias as $media)
                    {
                        $profilePostDW = XenForo_DataWriter::create('XenGallery_DataWriter_Media', XenForo_DataWriter::ERROR_SILENT);
                        $profilePostDW->setExistingData($media);
                        $profilePostDW->delete();
                    }
                    XenForo_Db::commit($db);
                }
                break;

            /*
             * XF RM
             */
            case 'xfrm':
                $modeName = 'Resources';

                if(!isset($addons['XenResource']))
                {
                    unset($data['delete_user_content']['xfrm']);
                    return $data;
                }

                $rms = $this->_getACPPModel()->getResourcesByUserId($data['user_id'], 100);

                if(!$rms)
                {
                    unset($data['delete_user_content']['xfrm']);
                    return $data;
                }
                else
                {
                    $data['position'] += count($rms);

                    XenForo_Db::beginTransaction($db);

                    foreach($rms as $rm)
                    {
                        $profilePostDW = XenForo_DataWriter::create('XenResource_DataWriter_Resource', XenForo_DataWriter::ERROR_SILENT);
                        $profilePostDW->setExistingData($rm);
                        $profilePostDW->delete();
                    }
                    XenForo_Db::commit($db);
                }
                break;
        }

        $status = sprintf('%s (%s)', ' delete ' . $modeName, $data['position']);
        return $data;
    }
    
	public function canCancel()
	{
		return true;
	}

    /**
     * @return XenForo_Model_Thread
     */
    protected function _getThreadModel()
    {
        return XenForo_Model::create('XenForo_Model_Thread');
    }

    /**
     * @return XenForo_Model_Post
     */
    protected function _getPostModel()
    {
        return XenForo_Model::create('XenForo_Model_Post');
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPModel()
    {
        return XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');
    }

}

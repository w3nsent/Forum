<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_Thread extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_Thread
{
    public function actionSoftThreads()
    {
        $acpModel = $this->_getACPPlusModel();

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $perPage = 50;


        $fetchOptions = array(
            'perPage' => $perPage,
            'page' => $page,
        );

        $threads = $acpModel->fetchSoftThreads($fetchOptions);

        foreach($threads as &$thread)
        {
            $thread['log'] = array(
                'user_id' => $thread['delete_user_id'],
                'username' => $thread['delete_username'],
                'reason' => $thread['delete_reason'],
            );
        }

        $viewParams = array(
            'threads' => $threads,
            'total_threads' => $acpModel->countSoftThreads(),

            'page' => $page,
            'perPage' => $perPage,
        );

        return $this->responseView('phc_ACPPlus_Extend_XenForo_ControllerAdmin_Thread_SoftThreads', 'acpp_soft_thread_list', $viewParams);
    }

    public function actionSoftPosts()
    {
        $acpModel = $this->_getACPPlusModel();

        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $perPage = 50;


        $fetchOptions = array(
            'perPage' => $perPage,
            'page' => $page,
        );


        $viewParams = array(
            'posts' => $acpModel->fetchSoftPosts($fetchOptions),
            'total_posts' => $acpModel->countSoftPosts(),

            'page' => $page,
            'perPage' => $perPage,
        );

        return $this->responseView('phc_ACPPlus_Extend_XenForo_ControllerAdmin_Thread_SoftPosts', 'acpp_soft_post_list', $viewParams);
    }


    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
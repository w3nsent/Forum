<?php

class phc_ACPPlus_Extend_XenForo_ControllerPublic_Thread extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerPublic_Thread
{
    public function actionPollShowvoters()
    {
        if(!XenForo_Visitor::getInstance()->hasPermission('acpplus', 'acpp_canUseWhoVoted'))
            throw $this->getNoPermissionResponseException();


        $threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

        $ftpHelper = $this->getHelper('ForumThreadPost');
        list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

        $pollModel = $this->_getPollModel();
        $poll = $pollModel->getPollByContent('thread', $threadId);

        if(!$poll)
        {
            return $this->responseNoPermission();
        }

        $threadModel = $this->_getThreadModel();

        if(!$threadModel->canEditPoll($poll, $thread, $forum, $errorPhraseKey))
        {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }


        $wvModel = $this->_getWVModel();

        $pollData = ($wvModel->fetchPoll($poll['content_id']));

        if(!$pollData)
            return $this->responseError(new XenForo_Phrase('requested_poll_not_found'), 404);

        $response = array();

        if(is_array($pollData['response']))
        {
            foreach($pollData['response'] as $data)
            {
                if(!isset($response[$data['poll_response_id']]))
                {
                    $response[$data['poll_response_id']]['question'] = $data['question'];
                }
            }

            foreach($pollData['users'] as $user)
            {
                if(isset($response[$user['poll_response_id']][$user['user_id']]))
                {
                    continue;
                }

                $response[$user['poll_response_id']]['users'][$user['user_id']] = $user;
            }
        }

        unset($pollData['users'], $pollData['response']);

        $viewParams = array(
            'wv' => $pollData['poll'],
            'response' => $response,

        );
        return $this->responseView('phc_ACPPlus_ViewAdmin_whoVoted', 'acpp_whovoted_view', $viewParams);
    }

    /**
     * @return phc_ACPPlus_Model_whoVoted
     */
    protected function _getWVModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_whoVoted');
    }
}
<?php

class phc_ACPPlus_ControllerAdmin_whoVoted extends XenForo_ControllerAdmin_Abstract
{
    protected function _preDispatch($action)
    {
        if(!XenForo_Visitor::getInstance()->hasPermission('acpplus', 'acpp_canUseWhoVoted'))
            throw $this->getNoPermissionResponseException();
    }

    public function actionIndex()
	{
        $page = $this->_input->filterSingle('page', XenForo_Input::UINT);
        $perPage = 20;

        $fetchOptions = array(
            'page' => $page,
            'perPage' => $perPage
        );

        $wvModel = $this->_getWVModel();

        $pollList = $wvModel->fetchPollList($fetchOptions);

        $viewParams = array(
            'wvList' =>$pollList,
            'page' => $fetchOptions['page'],
            'perPage' => $fetchOptions['perPage'],
            'totalPolls' => $wvModel->countPollList(),

        );
		return $this->responseView('phc_ACPPlus_ViewAdmin_whoVoted', 'acpp_whovoted_list', $viewParams);
	}

    public function actionView()
    {
        $content_id = $this->_input->filterSingle('content_id', XenForo_Input::UINT);

        $wvModel = $this->_getWVModel();

        $pollData = ($wvModel->fetchPoll($content_id));

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
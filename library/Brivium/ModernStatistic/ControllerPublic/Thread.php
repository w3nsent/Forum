<?php

/**
 * Controller for handling actions on threads.
 *
 * @package XenForo_Thread
 */
class Brivium_ModernStatistic_ControllerPublic_Thread extends XFCP_Brivium_ModernStatistic_ControllerPublic_Thread
{
	public function actionIndex()
	{
		$response = parent::actionIndex();
		if(!empty($response->params['forum']) && !empty($response->params['thread']['thread_id'])){
			$response->params['canPromoteThreadBRMS'] = $this->_getThreadModel()->canPromoteThreadBRMS($response->params['forum']);
		}
		return $response;
	}

	public function actionBrmsPromoteThread()
	{
		$this->_assertRegistrationRequired();

		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

		$threadModel = $this->_getThreadModel();

		if (!$threadModel->canPromoteThreadBRMS($forum))
		{
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		}

		if ($this->isConfirmedPost())
		{
			$userInput = $this->_input->filter(array(
				'thread_id' => XenForo_Input::UINT,
				'date' => XenForo_Input::STRING,
				'hour' => XenForo_Input::UINT,
				'mins' => XenForo_Input::UINT,
				'ampm' => XenForo_Input::STRING,
				'zone' => XenForo_Input::STRING,
				'delete' => XenForo_Input::STRING,
			));
			if ($userInput['delete'])
			{
				$dataChanges = array(
					'brms_promote' => 0,
					'brms_promote_date' => 0,
				);
			}
			else
			{
				if ($userInput['ampm'] == 'pm')
				{
					$userInput['hour'] = $userInput['hour']+12;
				}
				$userInput['time'] = $userInput['hour'] . ":" . str_pad($userInput['mins'], 2, "0", STR_PAD_LEFT);

				$datetime = $userInput['date']." ".$userInput['time']." ".$userInput['ampm']." ".$userInput['zone'];

				$dataChanges = array(
					'brms_promote_date' => strtotime($datetime),
					'brms_promote' => 1,
				);
			}


			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$dw->setExistingData($threadId);
			$dw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);

			$dw->bulkSet($dataChanges);

			$dw->preSave();
			$dw->save();

			$this->_updateModeratorLogThreadEdit($thread, $dw);
			$thread = $dw->getMergedData();

			// regular redirect
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('threads', $thread)
			);
		}
		else
		{
			$visitor = XenForo_Visitor::getInstance();
			$datetime = !empty($thread['brms_promote_date']) ? $thread['brms_promote_date'] : $thread['post_date'];
			$datetime = new DateTime(date('r', $datetime));
			$datetime->setTimezone(new DateTimeZone($visitor['timezone']));
			$datetime = explode('.', $datetime->format('Y-m-d.h.i.A.T'));

			$datetime = array(
				'date' => $datetime[0],
				'hour' => $datetime[1],
				'mins' => $datetime[2],
				'meri' => $datetime[3],
				'zone' => $datetime[4]
			);

			$viewParams = array(
				'thread' => $thread,
				'datetime' => $datetime,
				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			);

			return $this->responseView('XenForo_ViewPublic_Thread_EditTitle', 'BRMS_promote_thread', $viewParams);
		}
	}

	protected function _getCreditHelper()
	{
		return $this->getHelper('Brivium_Credits_ControllerHelper_Credit');
	}
}

<?php

/**
 * Handler for reported reputations.
 *
 * @package XenForo_Report
 */
class Brivium_AdvancedReputationSystem_ReportHandler_Reputations extends XenForo_ReportHandler_Abstract
{

	protected $_reputationModel = NULL;
	/**
	 * Gets report details from raw array of content (eg, a reputation record).
	 *
	 * @see XenForo_ReportHandler_Abstract::getReportDetailsFromContent()
	 */
	public function getReportDetailsFromContent(array $content)
	{
		/* @var $reputationModel Brivium_AdvancedReputationSystem_Model_Reputations */
		$reputationModel = $this->_getReputationModel();
		
		$reputation = $reputationModel->getReputationById( $content['reputation_id'], array(
			'join' => 	Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_POST|
						Brivium_AdvancedReputationSystem_Model_Reputation::FETCH_REPUTATION_GIVER
		));
		if (! $reputation)
		{
			return array(
				false,
				false,
				false
			);
		}
		
		return array(
			$content['reputation_id'],
			$content['giver_user_id'],
			array(
				'thread_id' => $reputation['thread_id'],
				'thread_title' => $reputation['title'],
				'username' => $reputation['username'],
				'comment' => $reputation['comment']
			)
		);
	}

	/**
	 * Gets the visible reports of this content type for the viewing user.
	 *
	 * @see XenForo_ReportHandler_Abstract:getVisibleReportsForUser()
	 */
	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$canEdit = XenForo_Visitor::getInstance()->hasPermission('reputation', 'BRARS_editAnyRep');
		$canDelete = XenForo_Visitor::getInstance()->hasPermission('reputation', 'BRARS_deleteAnyRep');
		
		if(!$canEdit || !$canDelete)
		{
			return array();
		}
		return $reports;
	}

	/**
	 * Gets the title of the specified content.
	 *
	 * @see XenForo_ReportHandler_Abstract:getContentTitle()
	 */
	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase( 'BRARS_reputation_in_post_at_thread_x', array(
			'title' => $contentInfo['thread_title']
		));
	}

	/**
	 * Gets the link to the specified content.
	 *
	 * @see XenForo_ReportHandler_Abstract::getContentLink()
	 */
	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink( 'brars-reputations', array(
			'reputation_id' => $report['content_id']
		));
	}

	/**
	 * A callback that is called when viewing the full report.
	 *
	 * @see XenForo_ReportHandler_Abstract::viewCallback()
	 */
	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		$parser = XenForo_BbCode_Parser::create( XenForo_BbCode_Formatter_Base::create( 'Base', array(
			'view' => $view
		)));
		
		return $view->createTemplateObject( 'BRARS_report_reputation_content', array(
			'report' => $report,
			'content' => $contentInfo,
			'bbCodeParser' => $parser
		));
	}

	/**
	 * Prepares the extra content for display.
	 *
	 * @see XenForo_ReportHandler_Abstract::prepareExtraContent()
	 */
	public function prepareExtraContent(array $contentInfo)
	{
		$contentInfo['thread_title'] = XenForo_Helper_String::censorString( $contentInfo['thread_title']);
		
		return $contentInfo;
	}
	
	protected function _getReputationModel()
	{
		if (! $this->_reputationModel)
		{
			$this->_reputationModel = XenForo_Model::create( 'Brivium_AdvancedReputationSystem_Model_Reputation');
		}
	
		return $this->_reputationModel;
	}
}
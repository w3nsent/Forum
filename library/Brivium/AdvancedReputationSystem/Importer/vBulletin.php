<?php

//######################## Reputation System By Brivium ###########################

define('Brivium_AdvancedReputationSystem_Importer_vBulletin_LOADED', true);

class Brivium_AdvancedReputationSystem_Importer_vBulletin extends XFCP_Brivium_AdvancedReputationSystem_Importer_vBulletin 
{
	public function getSteps() 
	{
		$steps = parent::getSteps();
		
		if (!empty($steps['reputation']))
		{
			unset($steps['reputation']);
		}
		
		$steps['reputation'] = array(
			'title' => 'Reputation Points to Advanced Reputation System',
			'depends' => array('threads'),
		);
		
		$steps['reputationValue'] = array(
			'title' => 'Reputation Values to Advanced Reputation System',
			'depends' => array('reputation'),
		);
		
		return $steps;
	}
	
	public function stepReputation($start, array $options) 
	{
		$options = array_merge(array(
			'max' => false,
			'limit' => 200,
			'processed' => 0,
		), $options);

		$sDb = $this->_sourceDb;
		$prefix = $this->_prefix;
		$model = $this->_importModel;
		
		if ($options['max'] === false) 
		{
			$data = $sDb->fetchRow('
				SELECT MAX(reputationid) AS max, COUNT(reputationid) AS rows
				FROM ' . $prefix . 'reputation
			');

			$options = array_merge($options,$data);
		}

		$reputations = $sDb->fetchAll($sDb->limit('
				SELECT *
				FROM ' . $prefix . 'reputation
				WHERE reputationid >= ' . $sDb->quote($start) . '
				ORDER BY reputationid
			', $options['limit']
		));

		if (!$reputations) 
		{
			XenForo_Application::defer( 'Brivium_AdvancedReputationSystem_Deferred_ReputationToPost', array(), 'BRARS_posts', true);
			return true;
		}

		$next = 0;
		$total = 0;

		$userids = array();
		$postids = array();

		foreach ($reputations AS $rep) 
		{
			$userids[] = $rep['userid'];
			$userids[] = $rep['whoadded'];
			$postids[] = $rep['postid'];
		}

		$userIdMap = $model->getImportContentMap('user', $userids);
		$postIdMap = $model->getImportContentMap('post', $postids);
		$users = $model->getModelFromCache('XenForo_Model_User')->getUsersByIds($userIdMap);

		XenForo_Db::beginTransaction();

		foreach ($reputations AS $rep) 
		{
			$receiverUserId = $this->_mapLookUp($userIdMap, $rep['userid']);
			$giverUserId = $this->_mapLookUp($userIdMap, $rep['whoadded']);
			$postId = $this->_mapLookUp($postIdMap, $rep['postid']);

			$rep['reason'] = $this->_convertToUtf8($rep['reason']);
			if (utf8_strlen($rep['reason']) >= 150) {
				$rep['reason'] = '';
				//$rep['reason'] = XenForo_Template_Helper_Core::helperSnippet($rep['reason'], 120);
			}
			
			if ($receiverUserId > 0 && $giverUserId > 0 && $postId > 0) 
			{
				$dw = XenForo_DataWriter::create('Brivium_AdvancedReputationSystem_DataWriter_Reputation', XenForo_DataWriter::ERROR_ARRAY);
				$dw->setExtraData( Brivium_AdvancedReputationSystem_DataWriter_Reputation::IMPORT_TABLE, true);
				$dw->set('post_id', $postId);
				$dw->set('receiver_user_id', $receiverUserId);
				$dw->set('receiver_username', $users[$receiverUserId]['username']);
				$dw->set('giver_user_id', $giverUserId);
				$dw->set('giver_username', $users[$giverUserId]['username']);
				$dw->set('reputation_date', $rep['dateline']);
				$dw->set('points', $rep['reputation']);
				$dw->set('comment', $rep['reason']);
				$dw->save();
			}
			
			$total++;
			$next = $rep['reputationid'] + 1;
		}

		XenForo_Db::commit();

		$options['processed'] += $total; 
		$this->_session->incrementStepImportTotal($total);
		
		return array($next, $options, $this->_getProgressOutput($options['processed'], $options['rows']));
	}
	
	public function stepReputationValue($start, array $options) 
	{
		$options = array_merge(array(
			'max' => false,
			'limit' => 200,
			'processed' => 0,
		), $options);

		$sDb = $this->_sourceDb;
		$prefix = $this->_prefix;
		$model = $this->_importModel;
		
		if ($options['max'] === false) 
		{
			$data = $sDb->fetchRow('
				SELECT MAX(userid) AS max, COUNT(userid) AS rows
				FROM ' . $prefix . 'user
			');

			$options = array_merge($options,$data);
		}

		$users = $sDb->fetchAll($sDb->limit('
				SELECT userid, reputation
				FROM ' . $prefix . 'user
				WHERE userid >= ' . $sDb->quote($start) . '
				ORDER BY userid
			', $options['limit']
		));

		if (!$users) 
		{
			return true;
		}

		$next = 0;
		$total = 0;

		$userids = array();

		foreach ($users AS $user) 
		{
			$userids[] = $user['userid'];
		}

		$userIdMap = $model->getImportContentMap('user', $userids);

		XenForo_Db::beginTransaction();

		foreach ($users AS $user) 
		{
			$importedUserId = $this->_mapLookUp($userIdMap, $user['userid']);

			if ($importedUserId) 
			{
				$this->_db->query('
					UPDATE xf_user
					SET reputation_count = ?
					WHERE user_id = ?
				', array($user['reputation'], $importedUserId));
				
				$total++;
			}
			
			$next = $user['userid'] + 1;
		}

		XenForo_Db::commit();

		$options['processed'] += $total; 
		$this->_session->incrementStepImportTotal($total);
		
		return array($next, $options, $this->_getProgressOutput($options['processed'], $options['rows']));
	}
}
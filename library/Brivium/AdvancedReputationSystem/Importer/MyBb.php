<?php

//######################## Reputation System By Brivium ###########################

define('Brivium_AdvancedReputationSystem_Importer_MyBb_LOADED', true);

class Brivium_AdvancedReputationSystem_Importer_MyBb extends XFCP_Brivium_AdvancedReputationSystem_Importer_MyBb
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
				SELECT MAX(rid) AS max, COUNT(rid) AS rows
				FROM ' . $prefix . 'reputation
			');

			$options = array_merge($options,$data);
		}

		$reputations = $sDb->fetchAll($sDb->limit('
				SELECT *
				FROM ' . $prefix . 'reputation
				WHERE rid >= ' . $sDb->quote($start) . '
				ORDER BY rid
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
			$userids[] = $rep['uid'];
			$userids[] = $rep['adduid'];
			$postids[] = $rep['pid'];
		}

		$userIdMap = $model->getImportContentMap('user', $userids);
		$postIdMap = $model->getImportContentMap('post', $postids);
		$users = $model->getModelFromCache('XenForo_Model_User')->getUsersByIds($userIdMap);

		XenForo_Db::beginTransaction();

		foreach ($reputations AS $rep) 
		{
			$receiverUserId = $this->_mapLookUp($userIdMap, $rep['uid']);
			$giverUserId = $this->_mapLookUp($userIdMap, $rep['adduid']);
			$postId = $this->_mapLookUp($postIdMap, $rep['pid']);

			$rep['comments'] = $this->_convertToUtf8($rep['comments']);
			if (utf8_strlen($rep['comments']) >= 150) {
				$rep['comments'] = '';
				//$rep['comments'] = XenForo_Template_Helper_Core::helperSnippet($rep['comments'], 120);
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
				$dw->set('comment', $rep['comments']);
				$dw->save();
			}
			
			$total++;
			$next = $rep['rid'] + 1;
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
				SELECT MAX(uid) AS max, COUNT(uid) AS rows
				FROM ' . $prefix . 'users
			');

			$options = array_merge($options,$data);
		}

		$users = $sDb->fetchAll($sDb->limit('
				SELECT uid, reputation
				FROM ' . $prefix . 'users
				WHERE uid >= ' . $sDb->quote($start) . '
				ORDER BY uid
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
			$userids[] = $user['uid'];
		}

		$userIdMap = $model->getImportContentMap('user', $userids);

		XenForo_Db::beginTransaction();

		foreach ($users AS $user) 
		{
			$importedUserId = $this->_mapLookUp($userIdMap, $user['uid']);

			if ($importedUserId) 
			{
				$this->_db->query('
					UPDATE xf_user
					SET reputation_count = ?
					WHERE user_id = ?
				', array($user['reputation'], $importedUserId));
				
				$total++;
			}
			
			$next = $user['uid'] + 1;
		}

		XenForo_Db::commit();

		$options['processed'] += $total; 
		$this->_session->incrementStepImportTotal($total);
		
		return array($next, $options, $this->_getProgressOutput($options['processed'], $options['rows']));
	}
}
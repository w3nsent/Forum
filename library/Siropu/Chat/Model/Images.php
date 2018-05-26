<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_Model_Images extends Xenforo_Model
{
	public function getImageById($imageId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_images
			WHERE image_id = ?
		', $imageId);
	}
	public function getImagesByUserId($userId, $page = 0)
	{
		$limitOptions = $this->prepareLimitFetchOptions(array('page' => $page, 'perPage' => 20));

		return $this->_getDb()->fetchAll($this->limitQueryResults('
			SELECT *
			FROM xf_siropu_chat_images
			WHERE image_user_id = ' . $this->_getDb()->quote($userId) . '
			ORDER BY image_date DESC
		', $limitOptions['limit'], $limitOptions['offset']));
	}
	public function getUserImagesByIds($ids, $userId)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_siropu_chat_images
			WHERE image_id IN (' . implode(',', $ids) . ')
			AND image_user_id = ?
		', $userId);
	}
	public function getUserImageByUploadTime($time, $userId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_siropu_chat_images
			WHERE image_user_id = ?
			AND image_date > ?
		', array($userId, $time));
	}
	public function getUserImageCount($userId)
	{
		$result = $this->_getDb()->fetchRow('
			SELECT COUNT(*) AS count
			FROM xf_siropu_chat_images
			WHERE image_user_id = ?
		', $userId);

		return $result['count'];
	}
}
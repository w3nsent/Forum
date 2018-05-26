<?php

/*
	Copyright (c) Siropu
	This is a PREMIUM PAID Add-on. If you obtained this copy illegally, please go to https://www.siropu.com/ and purchase a licence to get the latest version and to receive support.

	Chat Add-on by Siropu
	XenForo Profile: https://xenforo.com/community/members/siropu.92813/
	Website: https://www.siropu.com/
	Contact: contact@siropu.com
*/

class Siropu_Chat_HelperUpload
{
	public static function doUpload(XenForo_Upload $file, $userId)
	{
		$imagePath = self::getImagePath('absolute');
		$fileName  = uniqid($userId) . '.' . XenForo_Helper_File::getFileExtension($file->getFileName());
		$filePath  = $imagePath . '/' . $fileName;
		$thumbPath = $imagePath . '/Thumb/' . $fileName;

		if (!file_exists($imagePath))
		{
			XenForo_Helper_File::createDirectory($imagePath, true);
		}

		if (!file_exists($imagePath . '/Thumb'))
		{
			XenForo_Helper_File::createDirectory($imagePath . '/Thumb', true);
		}

		if (XenForo_Helper_File::safeRename($file->getTempFile(), $filePath))
		{
			if ($image = XenForo_Image_Abstract::createFromFile($filePath, $file->getImageInfoField('type')))
			{
				if ($image->thumbnail(100))
				{
					$image->output($file->getImageInfoField('type'), $thumbPath);
				}
				else
				{
					copy($filePath, $thumbPath);
				}

				unset($image);
			}

			return $fileName;
		}
	}
	public static function getImagePath($path = 'relative')
	{
		switch ($path)
		{
			case 'absolute':
				return XenForo_Helper_File::getExternalDataPath() . '/Siropu/Chat/Images';
				break;
			case 'url':
				return self::_getOptions()->boardUrl . '/data/Siropu/Chat/Images';
				break;
			case 'relative':
				return 'data/Siropu/Chat/Images';
				break;
		}
	}
	public static function deleteImage($image)
	{
		@unlink(self::getImagePath() . '/' . $image);
		@unlink(self::getImagePath() . '/Thumb/' . $image);
	}
	protected static function _getOptions()
	{
		return XenForo_Application::get('options');
	}
}
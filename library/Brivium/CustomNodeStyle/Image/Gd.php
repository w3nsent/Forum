<?php

/**
 * Image processor using GD.
 *
 * @package XenForo_Image
 */
class Brivium_CustomNodeStyle_Image_Gd extends XFCP_Brivium_CustomNodeStyle_Image_Gd
{
	public static function createFromResource($image)
	{
		$class = XenForo_Application::resolveDynamicClass('XenForo_Image_Gd');
		return new $class($image);
	}

	public function thumbnailFixedWidth($widthLength)
	{
		if ($widthLength < 10)
		{
			$widthLength = 10;
		}

		$ratio = $this->_height / $this->_width;
		$height = $widthLength * $ratio;
		$width = $widthLength;

		$newImage = imagecreatetruecolor($width, $height);
		$this->_preallocateBackground($newImage);

		imagecopyresampled(
			$newImage, $this->_image,
			0, 0, 0, 0,
			$width, $height, $this->_width, $this->_height);
		$this->_setImage($newImage);
	}

	public function thumbnailFixedHeight($heightLength)
	{
		if ($heightLength < 10)
		{
			$heightLength = 10;
		}

		$ratio = $this->_width / $this->_height;
		$width = $heightLength * $ratio;
		$height = $heightLength;

		$newImage = imagecreatetruecolor($width, $height);
		$this->_preallocateBackground($newImage);

		imagecopyresampled(
			$newImage, $this->_image,
			0, 0, 0, 0,
			$width, $height, $this->_width, $this->_height);
		$this->_setImage($newImage);
	}

	public function thumbnailFixedLongerSide($longSideLength)
	{
		if ($longSideLength < 10)
		{
			$longSideLength = 10;
		}

		$ratio = $this->_width / $this->_height;
		if ($ratio < 1)
		{
			$width = $longSideLength * $ratio;
			$height = $longSideLength;
		}
		else // landscape
		{
			$width = $longSideLength;
			$height = max(1, $longSideLength / $ratio);
		}

		$newImage = imagecreatetruecolor($width, $height);
		$this->_preallocateBackground($newImage);

		imagecopyresampled(
			$newImage, $this->_image,
			0, 0, 0, 0,
			$width, $height, $this->_width, $this->_height);
		$this->_setImage($newImage);
	}
	public function getImage()
	{
		return $this->_image;
	}

	public function setImage($image)
	{
		return $this->_setImage($image);
	}
}
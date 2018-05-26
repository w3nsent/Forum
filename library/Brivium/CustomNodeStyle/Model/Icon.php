<?php
class Brivium_CustomNodeStyle_Model_Icon extends XenForo_Model
{
	public static $imageQuality = 85;

	public function uploadIcon(XenForo_Upload $upload, $node, $number, $nodeType)
	{
		if($number != 2){
			$number = 1;
		}
		$options = XenForo_Application::get('options');
		if($node['node_type_id'] == 'Category' && $node['depth'] == 0){
			$dimensions = $options->BRCNS_iconLv1Dimension;
		}else{
			$dimensions = $options->BRCNS_iconLv2Dimension;
		}
		/*if($node['depth'] == 0 && $node['node_type_id'] != 'Category'){
			$dimensions = $options->BRCNS_iconLv1Dimension;
		}else if($node['depth'] == 1 || ($node['depth'] == 0 && $node['node_type_id'] == 'Forum')){
			$dimensions = $options->BRCNS_iconLv2Dimension;
		}else{
			$dimensions = $options->BRCNS_iconLv3Dimension;
		}*/
		$iconsProcessed = self::_applyIcon($node, $upload, $number, $dimensions);
		$this->_writeIcon($node['node_id'], $number, $iconsProcessed);
	}

	protected static function _applyIcon($node, $upload, $number, $dimensions)
	{
		if (!$upload->isValid()) {
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		if (!$upload->isImage()) {
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		};

		$imageType = $upload->getImageInfoField('type');
		if (!in_array($imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}

		$outputFiles = array();
		$fileName = $upload->getTempFile();
		$imageType = $upload->getImageInfoField('type');
		$outputType = $imageType;

		$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xfa');
		$image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
		if (!$image) {
			return false;
		}

		$saveDimensions = array();
		if(!empty($dimensions['width']) && empty($dimensions['height'])){
			$image->thumbnailFixedWidth($dimensions['width']);

			$saveDimensions['width'] = $dimensions['width'];
			$saveDimensions['height'] = $dimensions['width'];
		}else if(!empty($dimensions['height']) && empty($dimensions['width'])){
			$image->thumbnailFixedHeight($dimensions['height']);

			$saveDimensions['width'] = $dimensions['height'];
			$saveDimensions['height'] = $dimensions['height'];
		}else if(!empty($dimensions['width']) && !empty($dimensions['height'])){
			$ratio = $image->getWidth() / $image->getHeight();
			$maxWidth = $dimensions['width'];
			$maxHeight = $dimensions['height'];
			$maxRatio = ($maxWidth / $maxHeight);

			if ($maxRatio > $ratio)
			{
				$width = $maxWidth;
				$height = max(1, $maxWidth / $ratio);
			}
			else
			{
				$width = max(1, $maxHeight * $ratio);
				$height = $maxHeight;
			}

			if($width>$height){
				$image->thumbnailFixedHeight($height);
			}else{
				$image->thumbnailFixedWidth($width);
			}
			$x = floor(($image->getWidth() - $dimensions['width']) / 2);
			$y = floor(($image->getHeight() - $dimensions['height']) / 2);
			$image->crop($x, $y, $dimensions['width'], $dimensions['height']);

			$saveDimensions['width'] = $dimensions['width'];
			$saveDimensions['height'] = $dimensions['height'];
		}else if(!is_array($dimensions)){
			$image->thumbnailFixedWidth($dimensions);

			$saveDimensions['width'] = $dimensions;
			$saveDimensions['height'] = $dimensions;
		}

		$image->output($outputType, $newTempFile, self::$imageQuality);
		unset($image);

		$icons = $newTempFile;

		$brcnsIconData = array();
		if(!empty($node['brcnsIconData'])){
			$brcnsIconData = $node['brcnsIconData'];
		}
		if($number == 2){
			$brcnsIconData['brcns_pixel_2'] = $dimensions;
			$brcnsIconData['brcns_icon_date_2'] = XenForo_Application::$time;
		}else{
			$brcnsIconData['brcns_pixel'] = $dimensions;
			$brcnsIconData['brcns_icon_date'] = XenForo_Application::$time;
		}
		$brcnsIconData['brcns_select'] = 'file';

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_Node');
		$dw->setExistingData($node['node_id']);
		$dw->set('brcns_icon_data', $brcnsIconData);
		$dw->save();

		return $icons;
	}

	protected function _writeIcon($nodeId, $number, $tempFile)
	{
		$filePath = $this->getIconFilePath($nodeId, $number);
		$directory = dirname($filePath);

		if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
		{
			if (file_exists($filePath))
			{
				@unlink($filePath);
			}

			return XenForo_Helper_File::safeRename($tempFile, $filePath);
		}
		else
		{
			return false;
		}
	}

	public function getIconFilePath($nodeId, $number, $externalDataPath = null)
	{
		if ($externalDataPath === null)
		{
			$externalDataPath = XenForo_Helper_File::getExternalDataPath();
		}

		return sprintf('%s/nodeIcons/'.$nodeId.'_'.$number.'.jpg', $externalDataPath);
	}

	public function deleteIcon($nodeId, $number, $updateForum = true, $nodeType = '')
	{
		$filePath = $this->getIconFilePath($nodeId, $number);
		if (file_exists($filePath) && is_writable($filePath))
		{
			@unlink($filePath);
		}

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_Node');
		$dw->setExistingData($nodeId);
		$brcnsIconData = array();
		if(!empty($node['brcns_icon_data'])){
			$brcnsIconData = @unserialize($node['brcns_icon_data']);
		}
		if($number == 2){
			$brcnsIconData = array(
				'brcns_pixel_'. $number => 0,
				'brcns_icon_date_'. $number => 0
			);
		}else{
			$brcnsIconData = array(
				'brcns_pixel' => 0,
				'brcns_icon_date' => 0
			);
		}
		$dw->set('brcns_icon_data', $brcnsIconData);
		$dw->save();

		return $dwData;
	}
}

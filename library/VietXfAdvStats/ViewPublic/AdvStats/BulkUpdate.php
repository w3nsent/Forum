<?php
class VietXfAdvStats_ViewPublic_AdvStats_BulkUpdate extends XenForo_ViewPublic_Base {
	public function renderJson() {
		$rendered = array();
		
		foreach ($this->_params['sections'] as $sectionId => $section) {
			$tmp = VietXfAdvStats_Renderer::renderSection($section['typeMajor'], $section['type'], $section['action'], $section['encodedParams'], $this, $this->_params['pseudoInput']);
			if (!empty($tmp)) {
				$rendered[$sectionId] = $tmp;
			}
		}
		
		return array(
			'requested' => $this->_params['sections'],
			'rendered' => $rendered,
		);
	}
}
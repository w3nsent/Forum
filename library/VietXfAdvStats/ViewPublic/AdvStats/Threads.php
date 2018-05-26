<?php
class VietXfAdvStats_ViewPublic_AdvStats_Threads extends XenForo_ViewPublic_Base {
	public function renderHtml() {
		VietXfAdvStats_Renderer::renderSectionThreadFinalize($this->_params, $this);
	}
}
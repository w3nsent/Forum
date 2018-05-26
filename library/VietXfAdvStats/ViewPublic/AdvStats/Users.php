<?php
class VietXfAdvStats_ViewPublic_AdvStats_Users extends XenForo_ViewPublic_Base {
	public function renderHtml() {
		VietXfAdvStats_Renderer::renderSectionUserFinalize($this->_params, $this);
	}
}
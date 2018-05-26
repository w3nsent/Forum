<?php

class CCPLZHideLinks_BbCode_Formatter_Base extends XFCP_CCPLZHideLinks_BbCode_Formatter_Base
{
    protected $_tags;

    public function getTags()
    {
        $this->_tags = parent::getTags();

        $this->_tags['URL'] = array(
            'trimLeadingLinesAfter' => 1,
            'callback' => array($this, 'renderTagUrl')
        );

        return $this->_tags;
    }

    public function renderTagUrl(array $tag, array $rendererStates)
    {
        $visitor = XenForo_Visitor::getInstance();

        if ($visitor['user_id'] == 0)
        {
            return new XenForo_Phrase('hide_link_from_guest_message');
        }

        return parent::renderTagUrl($tag, $rendererStates);
    }

}

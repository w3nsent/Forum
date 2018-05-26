<?php

class XFI_VerifedStatus_Listeners_Listener
{
    /* Override action list to add the tabs */
    public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
    {
        if($templateName == 'option_list')
        {
            $titlePhrase = new XenForo_Phrase('option_group_xfi_verifed_status');

            if(isset($containerData['h1']) && $containerData['h1'] == $titlePhrase->render())
            {
                $content = $template->create('xfi_verifed_status_option_list', $template->getParams());
            }
        }

    }
}
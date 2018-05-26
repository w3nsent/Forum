<?php

class phc_ACPPlus_Listener_Listener extends XenForo_ControllerPublic_Abstract
{
    const TEMPLATE_PLACEHOLDER = '#templateFavoriten#';

    public static function load_class_controller($class, array &$extend)
    {
        switch($class)
        {
            case 'XenForo_ControllerAdmin_AddOn':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_AddOn';
                break;

            case 'XenForo_ControllerAdmin_Home':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Home';
                break;

            case 'XenForo_ControllerAdmin_Tools':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Tools';
                break;

            case 'XenForo_ControllerAdmin_Log':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Log';
                break;

            case 'XenForo_ControllerAdmin_User':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_User';
                break;

            case 'XenForo_ControllerAdmin_Error':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Error';
                break;

            case 'XenForo_ControllerAdmin_TemplateModification':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_TemplateModification';
                break;

            case 'XenForo_ControllerAdmin_AdminTemplateModification':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_TemplateModification';
                break;

            case 'XenForo_ControllerAdmin_Development':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Development';
                break;

            case 'XenForo_ControllerPublic_Online':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerPublic_Online';
                break;

            case 'XenForo_ControllerAdmin_Banning':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Banning';
                break;

            case 'XenForo_ControllerPublic_Misc':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerPublic_Misc';
                break;

            case 'XenForo_ControllerPublic_Error':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerPublic_Error';
                break;

            case 'XenForo_ControllerAdmin_Template':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Template';
                break;

            case 'XenForo_ControllerAdmin_Style':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Style';
                break;

            case 'XenForo_ControllerAdmin_AdminTemplate':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_AdminTemplate';
                break;

            case 'XenForo_ControllerAdmin_Language':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Language';
                break;

            case 'XenForo_ControllerAdmin_Phrase':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Phrase';
                break;

            case 'XenForo_ControllerAdmin_Option':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Option';
                break;

            case 'XenForo_ControllerAdmin_Thread':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Thread';
                break;

            case 'XenForo_ControllerAdmin_Login':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerAdmin_Login';
                break;

            case 'XenForo_ControllerPublic_Login':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerPublic_Login';
                break;

            case 'XenForo_ControllerPublic_Thread':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ControllerPublic_Thread';
                break;
        }
    }

    public static function load_class_model($class, array &$extend)
    {
        switch($class)
        {
            case 'XenForo_Model_AddOn':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_Model_AddOn';
                break;

            case 'XenForo_Model_Template':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_Model_Template';
                break;

            case 'XenForo_Model_AdminTemplate':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_Model_AdminTemplate';
                break;

            case 'XenForo_Model_Phrase':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_Model_Phrase';
                break;

            case 'XenForo_Model_AdminNavigation':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_Model_AdminNavigation';
                break;

            case 'XenForo_Model_Option':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_Model_Option';
                break;
        }
    }

    public static function load_class_view($class, array &$extend)
    {
        switch($class)
        {
            case 'XenForo_ViewAdmin_AddOn_Upgrade':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_ViewAdmin_AddOn_Upgrade';
                break;
        }
    }

    public static function load_class_datawriter($class, array &$extend)
    {
        switch($class)
        {
            case 'XenForo_DataWriter_OptionGroup':
                $extend[] = 'phc_ACPPlus_Extend_XenForo_DataWriter_OptionGroup';
                break;
        }
    }

    public static function controller_pre_dispatch(XenForo_Controller $controller, $action, $controllerName)
    {
        /*
         * ToDo: Will not be developed further!
        if($controller instanceof XenForo_ControllerPublic_Abstract && $controllerName != 'XenForo_ControllerPublic_Attachment')
        {
            $NFDetection = new phc_ACPPlus_Helper_404Detection();

            if($NFDetection->checkUserBlocked())
            {
                throw $controller->responseException($controller->responseError(new XenForo_Phrase('acp_404_you_have_been_banned_temporary'), 403));
            }
        }
        */

        // Bad Referer && Bad UserAgent
        if($controller instanceof XenForo_ControllerPublic_Abstract && !isset($GLOBALS['checkedBadReferrerAgent']))
        {
            $GLOBALS['checkedBadReferrerAgent'] = true;

            $badRefererAgent = new phc_ACPPlus_Helper_BadReferrerAgent();
            if($badRefererAgent->checkBadReferrer() || $badRefererAgent->checkBadAgent())
            {
                throw $controller->responseException($controller->responseError(new XenForo_Phrase('acp_you_have_been_banned'), 403));
            }

            unset($badRefererAgent);
        }


        // override  XF Robots ;)
        try
        {
            if(XenForo_Application::isRegistered('session'))
            {
                $session = XenForo_Application::getSession();

                if($session instanceof XenForo_Session)
                {
                    if (!empty($_SERVER['HTTP_USER_AGENT']))
                    {
                        $session->set('robotId', phc_ACPPlus_Helper_ACPPlus::getRobotId($_SERVER['HTTP_USER_AGENT']));

                        /** @var $acpPlusModel phc_ACPPlus_Model_ACPPlus */
                        $acpPlusModel = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');
                        $robot = $acpPlusModel->fetchRobotByRobotId($session->get('robotId'));

                        if($robot)
                        {
                            $routeOrig = rtrim($controller->_request->getParam('_origRoutePath'), '/') . '/';
                            $routeMatch = rtrim($controller->_request->getParam('_matchedRoutePath'), '/') . '/';
                            $controllerName = $controller->getRouteMatch()->getControllerName();

                            if($controllerName == 'XenForo_ControllerPublic_Index')
                                $GLOBALS['isIndex'] = true;

                            $robot['routes'] = @array_flip(@preg_split('/(\r\n|\n|\r)+/', $robot['routes'], -1, PREG_SPLIT_NO_EMPTY));
                            $robot['whiteList'] = @preg_split('/(\r\n|\n|\r)+/', $robot['routes_whitelist'], -1, PREG_SPLIT_NO_EMPTY);

                            $exits = false;
                            $whiteList = false;

                            if($robot['whiteList'])
                            {
                                foreach($robot['whiteList'] as $whitList)
                                {
                                    if(strpos($routeOrig, $whitList) !== false)
                                    {
                                        $whiteList = true;
                                        break;
                                    }
                                }
                            }

                            if(!$whiteList)
                            {
                                if(isset($robot['routes']['index']) && !empty($GLOBALS['isIndex']))
                                {
                                    $exits = true;
                                }
                                else
                                {
                                    if(isset($robot['routes']['register']))
                                        $robot['routes']['login'] = true;

                                    foreach($robot['routes'] as $key => $value)
                                    {
                                        $newKey = rtrim($key, '/') . '/';

                                        if(strpos($routeOrig, $newKey) !== false)
                                        {
                                            $exits = true;
                                            break;
                                        }

                                        if(strpos($routeMatch, $newKey) !== false)
                                        {
                                            $exits = true;
                                            break;
                                        }
                                    }
                                }
                            }

                            if($exits)
                            {
                                $html = '<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <META name="robots" content="noindex, nofollow">
    <title></title>
  </head>
  <body>
  </body>
</html>';
                                @header('Content-Type: text/html; charset=utf-8', true, 200);
                                @header("X-Robots-Tag: noindex, nofollow", true);
                                @header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
                                @header('Cache-control: private, max-age=0, no-cache, must-revalidate');
                                @header('Pragma: no-cache');
                                print $html;
                                exit;
                            }
                        }
                    }
                }
            }
        }
        catch (Exception $e) {}

    }

    public static function controller_post_dispatch(XenForo_Controller $controller, $controllerResponse, $controllerName, $action)
    {
        if(XenForo_Application::isRegistered('session'))
        {
            $session = XenForo_Application::getSession();

            if($session instanceof XenForo_Session)
            {
                if(!empty($session->get('robotId')))
                {
                    /** @var $acpPlusModel phc_ACPPlus_Model_ACPPlus */
                    $acpPlusModel = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');
                    $acpPlusModel->updateRobotActivity($session->get('robotId'));
                }
            }
        }
    }

    public static function visitor_setup(XenForo_Visitor &$visitor)
    {
        /**
         *@var $acpPlusModel phc_ACPPlus_Model_ACPPlus
         */
        $acpPlusModel = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');

        if(!$acpPlusModel->canViewDebugMode())
        {
            XenForo_Application::setDebugMode(false);
            @ini_set('display_errors', false);
        }
    }

    public static function front_controller_post_view(XenForo_FrontController $fc, &$output)
    {
        /**
         *@var $acpPlusModel phc_ACPPlus_Model_ACPPlus
         */
        $acpPlusModel = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');

        $requests = $fc->getRequest();

        if($acpPlusModel->canViewDebugMode() && $requests->getParam('_debug'))
        {
            $output = $fc->renderDebugOutput($output);
        }
    }

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        $debugMode = XenForo_Application::getSimpleCacheData('acpp_debug_mode');

        if($debugMode)
        {
            XenForo_Application::setDebugMode(true);
            XenForo_Application::getDb()->setProfiler(true);
        }
    }

    public static function template_hook($name, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {

        /*
         * ToDo: discontinue
        if($name === 'thread_view_tools_links')
        {
            if(XenForo_Visitor::getInstance()->hasPermission('acpplus', 'acpp_canUseWhoVoted'))
            {
                $params = $template->getParams();

                if(!empty($params['thread']['discussion_type']) && $params['thread']['discussion_type'] == 'poll')
                {
                    $contents .= $template->create('acpp_thread_view_tools_links', $params);
                }
            }
        }
        */

        if($name == 'admin_sidebar_appearance' && XenForo_Visitor::getInstance()->hasAdminPermission('style'))
        {
            $acpPlusModel = XenForo_Model::create('phc_ACPPlus_Model_ACPPlus');

            $params = $template->getParams();
            $params += $hookParams;
            $params += array('favorites' => $acpPlusModel->getTemplateFavorites());

            $contents .= $template->create('acpp_admin_sidebar_appearance_favorites', $params);
        }
    }

    public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
    {
        if($templateName == 'option_list')
        {
            $titlePhrase = new XenForo_Phrase('option_group_acpp_options');

            if(isset($containerData['h1']) && $containerData['h1'] == $titlePhrase->render())
            {
                $content = $template->create('acpp_options', $template->getParams());
            }
        }

        if($templateName == 'home' && $template instanceof XenForo_Template_Admin)
        {
            $content = preg_replace_callback('#(<div class="sidebar">)(.*)(<\/div>)#ism', array('self', 'setFavoritenTemplateBox'), $content);

            $ourTemplate = $template->create('acpp_admin_sidebar_appearance_favorites', $template->getParams());
            $content = str_replace(self::TEMPLATE_PLACEHOLDER, $ourTemplate->render(), $content);
        }
    }

    private static function setFavoritenTemplateBox($content)
    {
        if(!empty($content[2]))
        {
            $position = XenForo_Application::getOptions()->acpp_favoritenBlock;

            switch($position)
            {
                case 'after_member_stats':
                    $content[0] = str_replace('<!-- slot:  pre_forum_stats -->', '<!-- slot:  pre_forum_stats -->' . self::TEMPLATE_PLACEHOLDER, $content[0]);
                    break;

                case 'befor_member_stats':
                    $content[0] = str_replace('<!-- slot: pre_member_stats -->', '<!-- slot: pre_member_stats -->' . self::TEMPLATE_PLACEHOLDER, $content[0]);
                    break;

                case 'after_forum_stats':
                    $content[0] = str_replace('<!-- slot: pre_add_ons -->', '<!-- slot: pre_add_ons -->' . self::TEMPLATE_PLACEHOLDER, $content[0]);
                    break;

                default:
                case 'after_addons':
                    $content[0] = str_replace('<!-- slot:  after_addon_list -->', '<!-- slot:  after_addon_list -->' . self::TEMPLATE_PLACEHOLDER, $content[0]);
                    break;
            }
        }

        return $content[0];
    }
}
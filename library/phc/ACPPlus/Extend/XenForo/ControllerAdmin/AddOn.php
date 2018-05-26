<?php

class phc_ACPPlus_Extend_XenForo_ControllerAdmin_AddOn extends XFCP_phc_ACPPlus_Extend_XenForo_ControllerAdmin_AddOn
{
    public function actionIndex()
    {
        $GLOBALS['bypassAddOnList'] = true;

        $addOnModel = $this->_getAddOnModel();

        // AddOn Builder
        $aobActive = false;
        $aob = $addOnModel->getAddOnById('phc_AddonBuilder');

        if(!empty($aob) && $aob['active'])
        {
            $aobActive = true;
        }

        $listType = $this->_input->filterSingle('list', XenForo_Input::STRING);

        if(!$listType)
            $listType = 'uncategorized';


        $addOns = $addOnModel->getAllAddOns($listType);

        $viewParams = array(
            'aob_active' => $aobActive,
            'listType' => $listType,
            'addOns' => $addOns,
            'canAccessDevelopment' => $addOnModel->canAccessAddOnDevelopmentAreas(),
            'disabledAddOns' => $addOnModel->getDisabledAddOnsCache(),
            'addon_cats' => $addOnModel->fetchAllAddonCats(),
        );

        return $this->responseView('XenForo_ViewAdmin_AddOn_List', 'acpp_addon_list', $viewParams);
    }

    public function actionPositionReset()
    {
        $acpPlusModel = $this->_getACPPlusModel();

        $order = $this->_input->filterSingle('order', XenForo_Input::ARRAY_SIMPLE);

        if($order)
        {
            $acpPlusModel->updatePositionen($order, 'addons');
        }

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('add-ons')
        );
    }

    public function actionAbcOrder()
    {
        $acpPlusModel = $this->_getACPPlusModel();
        $addOnModel = $this->_getAddOnModel();

        $addons = $addOnModel->getAllAddOnsRaw();

        if($addons)
        {
            $acpPlusModel->updatePositionen($addons, 'addons');
        }

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('add-ons')
        );
    }

    public function actionOrder()
    {
        $acpPlusModel = $this->_getACPPlusModel();
        $addOnModel = $this->_getAddOnModel();

        if($this->_request->isPost())
        {
            $addOns = $this->_input->filterSingle('cat', XenForo_Input::ARRAY_SIMPLE);
            $addOnModel->updateAddOnCategorie($addOns);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('add-ons')
            );
        }
        else
        {
            $GLOBALS['bypassAddOnList'] = true;
            $viewParams = array(
                'addOns' => $addOnModel->getAllAddOnsDefault(),
                'addon_cats' => $addOnModel->fetchAllAddonCats(),
            );

            return $this->responseView('phc_ACPPlus_Extend_XenForo_AddOn_Order', 'acpp_addon_order', $viewParams);
        }
    }

    public function actionAddonCategorieAdd()
    {
        return $this->responseReroute(__CLASS__, 'AddonCategorieEdit');
    }

    public function actionAddonCategorieEdit()
    {
        $addOnModel = $this->_getAddOnModel();

        $addOnCatId = $this->_input->filterSingle('cat_id', XenForo_Input::UINT);

        $cat = $addOnModel->fetchAddonCategoryById($addOnCatId);

        if($this->isConfirmedPost())
        {
            $title = $this->_input->filterSingle('title', XenForo_Input::STRING);
            $postion = $this->_input->filterSingle('position', XenForo_Input::UINT);

            if(!$title)
                throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_edit_addon_category_title_missing')));

            $addOnModel->updateAddonCategiory($addOnCatId, $title, $postion);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('add-ons/addon-categorie-list')
            );
        }
        else
        {
            $viewParams = array(
                'cat' => $cat,
            );

            return $this->responseView('phc_ACPPlus_Extend_XenForo_AddOn_Category_Delete', 'acpp_addon_category_edit', $viewParams);
        }
    }

    public function actionAddonCategorieDelete()
    {
        $addOnModel = $this->_getAddOnModel();

        $addOnCatId = $this->_input->filterSingle('cat_id', XenForo_Input::UINT);

        $cat = $addOnModel->fetchAddonCategoryById($addOnCatId);

        if(!$cat)
            throw $this->responseException($this->responseError(new XenForo_Phrase('acpp_requested_addon_category_not_found'), 404));

        if($this->isConfirmedPost())
        {
            $addOnModel->deleteAddonCategiory($addOnCatId);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('add-ons/addon-categorie-list')
            );
        }
        else
        {
            $viewParams = array(
                'cat' => $cat,
            );

            return $this->responseView('phc_ACPPlus_Extend_XenForo_AddOn_Category_Delete', 'acpp_addon_category_delete', $viewParams);
        }
    }

    public function actionAddonCategorieList()
    {
        $addOnModel = $this->_getAddOnModel();

        $viewParams = array(
            'addon_cats' => $addOnModel->fetchAllAddonCats(),
        );

        return $this->responseView('phc_ACPPlus_Extend_XenForo_AddOn_Order', 'acpp_addon_category_list', $viewParams);
    }

    public function actionToggle()
    {
        $GLOBALS['bypassAddOnList'] = true;

        return parent::actionToggle();
    }

    public function actionNote()
    {
        $addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
        $text = $this->_input->filterSingle('note', XenForo_Input::STRING);
        $addOn = $this->_getAddOnOrError($addOnId);

        if($this->isConfirmedPost())
        {
            $this->_getACPPlusModel()->updateNote($addOn, $text);

            return $this->responseRedirect(
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildAdminLink('add-ons')
            );
        }
        else
        {
            $viewParams = array(
                'addOn' => $addOn
            );

            return $this->responseView('phc_ACPPlus_Extend_XenForo_ControllerAdmin_AddOn_Note', 'acpp_addon_note', $viewParams);
        }
    }

    /**
     * @return phc_ACPPlus_Model_ACPPlus
     */
    protected function _getACPPlusModel()
    {
        return $this->getModelFromCache('phc_ACPPlus_Model_ACPPlus');
    }
}
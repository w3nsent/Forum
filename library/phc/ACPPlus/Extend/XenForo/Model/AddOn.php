<?php

class phc_ACPPlus_Extend_XenForo_Model_AddOn extends XFCP_phc_ACPPlus_Extend_XenForo_Model_AddOn
{
    public function getAllAddOns($listType = null)
    {
        $where = '';

        if(empty($GLOBALS['bypassAddOnList']) && XenForo_Application::getOptions()->acpp_viewOnlyActiveAddons)
        {
           $where = 'WHERE active = 1';
        }

        if($listType)
        {
            if(preg_match('#^[0-9]+$#', $listType))
            {
                $where = 'WHERE cat_id = ' . $listType;
            }
            else
            {
                switch($listType)
                {
                    case 'active':
                        $where = 'WHERE active = 1';
                        break;

                    case 'disabled':
                        $where = 'WHERE active = 0';
                        break;

                    case 'uncategorized':
                        $where = 'WHERE cat_id = 0';
                        break;
                }
            }
        }

        return $this->fetchAllKeyed('
			SELECT *
			FROM xf_addon
			' . $where . '
			ORDER BY position
		', 'addon_id');
    }

    public function getAllAddOnsDefault()
    {
        return $this->fetchAllKeyed('
			SELECT *
			FROM xf_addon
			ORDER BY title
		', 'addon_id');
    }

    public function getAllAddOnsRaw()
    {
        return $this->_getDb()->fetchCol('
			SELECT addon_id
			FROM xf_addon
			ORDER BY CONVERT(title USING utf8)
		');
    }

    public function getAddOnOptionsListExt()
    {
        $options = array();
        $options['all'] = new XenForo_Phrase('acpp_list_all');

        $options['XenForo'] = 'XenForo';

        $addOns = $this->getAllAddOns();
        foreach ($addOns AS $addOn)
        {
            $options[$addOn['addon_id']] = $addOn['title'];
        }

        return $options;
    }

    public function fetchAllAddonCats()
    {
        return $this->fetchAllKeyed('
			SELECT *
			FROM phc_acpp_addon_cats
			ORDER BY position
		', 'cat_id');
    }

    public function fetchAddonCategoryById($id)
    {
        return $this->_getDb()->fetchRow('
			SELECT *
			FROM phc_acpp_addon_cats
			WHERE cat_id = ?
		', $id);
    }

    public function fetchAddonCategoryByTitle($title)
    {
        return $this->_getDb()->fetchRow('
			SELECT *
			FROM phc_acpp_addon_cats
			WHERE title = ?
		', $title);
    }

    public function updateAddonCategiory($id, $title, $position)
    {
        $db = $this->_getDb();

        if($id)
        {
            $db->update('phc_acpp_addon_cats', array(
                'title' => $title,
                'position' => $position,

            ), 'cat_id = ' . $id);
        }
        else
        {
            $this->_getDb()->insert('phc_acpp_addon_cats', array(
                'title' => $title,
                'position' => $position
            ));
        }
    }

    public function deleteAddonCategiory($id)
    {
        $db = $this->_getDb();

        $db->delete('phc_acpp_addon_cats', 'cat_id = ' . $id);

        $db->update('xf_addon', array(
            'cat_id' => 0
        ), 'cat_id = ' . $id);
    }

    public function fetchAddOnsCatsWithAddOns()
    {
        $addOnCats =  array(
            0 => array(
                'cat_id' => 0,
                'addons' => array()
            )
        );

        $cats = $this->fetchAllAddonCats();
        foreach($cats AS $id => $cat)
        {
            $addOnCats[$id] = $cat;
        }

        $addOns = $this->getAllAddOns('disabled');

        foreach($addOns AS $addOnId => $addOn)
        {
            $addOnCats[$addOn['cat_id']]['addons'][$addOnId] = $addOn['title'];
        }

        return $addOnCats;
    }

    public function updateAddOnCategorie($addons)
    {
        $db = $this->_getDb();

        XenForo_Db::beginTransaction($db);

        foreach($addons as $key => $val)
        {
            $db->update('xf_addon',
                array(
                    'cat_id' => $val
                ), "addon_id = '$key'");
        }

        XenForo_Db::commit($db);
    }
}
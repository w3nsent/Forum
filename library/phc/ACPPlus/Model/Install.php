<?php

class phc_ACPPlus_Model_Install extends XenForo_Model
{
    public function checkIfReorderExists()
    {
        $db = $this->_getDb();

        if($db->fetchOne('SELECT active FROM xf_addon WHERE addon_id = \'phc_ReorderAddons\''))
        {
            return true;
        }

        return false;
    }

    public function checkIfWhoVotedExists()
    {
        $db = $this->_getDb();

        if($db->fetchOne('SELECT active FROM xf_addon WHERE addon_id = \'phc_whoVoted\''))
        {
            return true;
        }

        return false;
    }

    public function checkIfEMailMassBanExists()
    {
        $db = $this->_getDb();

        if($db->fetchOne('SELECT active FROM xf_addon WHERE addon_id = \'phc_MassBanEMails\''))
        {
            return true;
        }

        return false;
    }

    public function checkIfDenyByHtaccessExists()
    {
        $db = $this->_getDb();

        if($db->fetchOne('SELECT active FROM xf_addon WHERE addon_id = \'phc_denyByHtaccess\''))
        {
            return true;
        }

        return false;
    }


    public function checkIfLiamWAdminCPFirewall()
    {
        $db = $this->_getDb();

        if($db->fetchOne('SELECT active FROM xf_addon WHERE addon_id = \'liam_cpfirewall\''))
        {
            return true;
        }

        return false;
    }
}
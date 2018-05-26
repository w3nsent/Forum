<?php

class phc_ACPPlus_Extend_XenForo_Model_Phrase extends XFCP_phc_ACPPlus_Extend_XenForo_Model_Phrase
{
	public function preparePhraseConditions(array $conditions, array &$fetchOptions)
	{
	    $res = parent::preparePhraseConditions($conditions, $fetchOptions);
        $sqlConditions = array($res);

        if(!empty($GLOBALS['acpp_phrase_filter_addon']))
        {
            $db = $this->_getDb();

            $addonString = $GLOBALS['acpp_phrase_filter_addon'];
            $sqlConditions[] = ' phrase.addon_id = ' . $db->quote($addonString);
        }

		return $this->getConditionsForClause($sqlConditions);
	}

    public function prepareHaving()
    {
        $having = '';
        if(!empty($GLOBALS['acpp_phrase_filter_phrases_by']))
        {
            switch($GLOBALS['acpp_phrase_filter_phrases_by'])
            {
                case 'default':
                    $having = ' HAVING phrase_state = "default"';
                    break;

                case 'custom':
                    $having = ' HAVING phrase_state = "custom"';
                    break;
            }
        }

        return $having;
    }


    public function getEffectivePhraseListForLanguage($languageId, array $conditions = array(), array $fetchOptions = array())
    {
        if(empty($GLOBALS['acpp_phrase_filter_phrases_by']))
        {
            return parent::getEffectivePhraseListForLanguage($languageId, $conditions, $fetchOptions);
        }

        $whereClause = $this->preparePhraseConditions($conditions, $fetchOptions);
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        return $this->_getDb()->fetchAll($this->limitQueryResults(
            '
				SELECT phrase_map.phrase_map_id,
					phrase_map.language_id AS map_language_id,
					phrase.phrase_id,
					phrase_map.title,
					IF(phrase.language_id = 0, \'default\', IF(phrase.language_id = phrase_map.language_id, \'custom\', \'inherited\')) AS phrase_state,
					IF(phrase.language_id = phrase_map.language_id, 1, 0) AS canDelete,
					addon.addon_id, addon.title AS addonTitle
				FROM xf_phrase_map AS phrase_map
				INNER JOIN xf_phrase AS phrase ON
					(phrase_map.phrase_id = phrase.phrase_id)
				LEFT JOIN xf_addon AS addon ON
					(addon.addon_id = phrase.addon_id)
				WHERE phrase_map.language_id = ?
					AND ' . $whereClause . '	
				' . $this->prepareHaving() . '
				ORDER BY CONVERT(phrase_map.title USING utf8)
			', $limitOptions['limit'], $limitOptions['offset']
        ), $languageId);
    }

    public function countEffectivePhrasesInLanguage($languageId, array $conditions = array())
    {
        if(empty($GLOBALS['acpp_phrase_filter_phrases_by']))
        {
            return parent::countEffectivePhrasesInLanguage($languageId, $conditions);
        }

        $fetchOptions = array();
        $whereClause = $this->preparePhraseConditions($conditions, $fetchOptions);

        return count($this->_getDb()->fetchAll('
			SELECT phrase.phrase_id,
			IF(phrase.language_id = 0, \'default\', IF(phrase.language_id = phrase_map.language_id, \'custom\', \'inherited\')) AS phrase_state
			FROM xf_phrase_map AS phrase_map
			INNER JOIN xf_phrase AS phrase ON
				(phrase_map.phrase_id = phrase.phrase_id)
			WHERE phrase_map.language_id = ?
				AND ' . $whereClause . '
				' . $this->prepareHaving() . '
		', $languageId));
    }
}
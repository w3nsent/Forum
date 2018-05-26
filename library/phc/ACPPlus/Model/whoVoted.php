<?php

class phc_ACPPlus_Model_whoVoted extends XenForo_Model
{
    public function fetchPollList($fetchOptions)
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

        $db = $this->_getDb();

        return $db->fetchAll($this->limitQueryResults('
                                   SELECT
                                      *
                                   FROM
                                      xf_poll

                                      WHERE content_type = \'thread\'

                                   ORDER BY poll_id ASC
                                ', $limitOptions['limit'], $limitOptions['offset']));
    }

    public function countPollList()
    {
        return $this->_getDb()->fetchOne('
                      SELECT  COUNT(*)
                            FROM xf_poll
                            WHERE content_type = \'thread\'
		');
    }

    public function fetchPoll($content_id)
    {
        $db = $this->_getDb();

        $poll = $db->fetchRow('
                               SELECT
                                  *
                               FROM
                                  xf_poll

                               LEFT JOIN xf_thread ON (xf_thread.thread_id = xf_poll.content_id)

                                  WHERE content_type = \'thread\'
                                  AND content_id = ' . $content_id . '
                                ');

        if($poll)
        {
            $response = $this->fetchAllKeyed('
                                    SELECT
                                        pr.response as question,
                                        pr.poll_response_id
    
                                    FROM
                                      xf_poll_response as pr
    
                                    WHERE
                                      pr.poll_id = ' . $poll['poll_id'] . '
		                        ', 'poll_response_id');

            if($poll && $response)
            {
                $users = $db->fetchAll('
                    SELECT
                      user.user_id, user.username,
                      pv.poll_response_id

                    FROM
                      xf_poll_vote as pv

                    INNER JOIN
                        xf_user as user USING(user_id)

                    WHERE
                      pv.poll_id = ' . $poll['poll_id'] . '

                    ORDER BY
                        user.username ASC
                        ');

                return array('poll' => $poll, 'users' => $users, 'response' => $response);
            }
        }

        return false;
    }
}



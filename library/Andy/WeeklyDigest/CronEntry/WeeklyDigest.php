<?php

class Andy_WeeklyDigest_CronEntry_WeeklyDigest
{
	public static function runWeeklyDigest()
	{						
		// calculate dateline
		$dateline = time() - (86400 * 7);	
		
		//########################################
		// exclude forums
		//########################################	
		
		// define variable;
		$whereclause1 = '';			
		
		// get options from Admin CP -> Options -> Weekly Digest -> Exclude Forums
		$excludeForumsArray = XenForo_Application::get('options')->weeklyDigestExcludeForums;
		
		// if not empty
		if (!empty($excludeForumsArray))
		{		
			// create whereclause1 of excluded forums
			$whereclause1 = 'AND (xf_thread.node_id <> ' . implode(' AND xf_thread.node_id <> ', $excludeForumsArray);
			$whereclause1 = $whereclause1 . ')';
		}	

		//########################################
		// get threads
		//########################################
		
		// get options from Admin CP -> Options -> Weekly Digest -> Limit
		$limit = XenForo_Application::get('options')->weeklyDigestLimit;
		
		// get options from Admin CP -> Options -> Weekly Digest -> Sort Forum Titles
		$sortForumTitles = XenForo_Application::get('options')->weeklyDigestSortForumTitles;			
		
		// get database
		$db = XenForo_Application::get('db');			

		if ($sortForumTitles == 0)
		{	
			// run query
			$threads = $db->fetchAll("
			SELECT xf_thread.thread_id,
			xf_thread.title,
			xf_thread.username,
			xf_node.title AS forumTitle
			FROM xf_thread
			INNER JOIN xf_node ON xf_node.node_id = xf_thread.node_id
			WHERE xf_thread.post_date > ?
			AND xf_thread.discussion_state = 'visible'
			AND xf_thread.discussion_type <> 'redirect'
			$whereclause1
			ORDER BY xf_thread.view_count DESC
			LIMIT ?
			", array($dateline, $limit));	
		}
		
		if ($sortForumTitles == 1)
		{	
			// run query
			$threads = $db->fetchAll("
			SELECT xf_thread.thread_id,
			xf_thread.title,
			xf_thread.username,
			xf_node.title AS forumTitle
			FROM xf_thread
			INNER JOIN xf_node ON xf_node.node_id = xf_thread.node_id
			WHERE xf_thread.post_date > ?
			AND xf_thread.discussion_state = 'visible'
			AND xf_thread.discussion_type <> 'redirect'
			$whereclause1
			ORDER BY forumTitle ASC, xf_thread.view_count DESC
			LIMIT ?
			", array($dateline, $limit));	
		}
		
		// continue only if we have threads
		if (!empty($threads))
		{
			//########################################
			// test userId
			//########################################	
			
			// define variable;
			$whereclause2 = '';			
			
			// get options from Admin CP -> Options -> Weekly Digest -> Test User ID
			$testUserId = XenForo_Application::get('options')->weeklyDigestTestUserId;	
			
			if ($testUserId > 0)
			{
				$whereclause2 = 'AND xf_user.user_id = ' . $testUserId;
			}					
			
			//########################################
			// get users
			//########################################
			
			// calculate dateline
			$dateline = time() - (86400 * 30);											
			
			// run query
			$users = $db->fetchAll("
			SELECT xf_user.username, 
			xf_user.email
			FROM xf_user
			INNER JOIN xf_user_option ON xf_user_option.user_id = xf_user.user_id
			WHERE xf_user.last_activity > ?
			AND xf_user.user_state = 'valid'
			AND xf_user.is_banned = 0
			AND xf_user.weekly_digest_opt_out = 0
			AND xf_user_option.receive_admin_email = 1
			AND xf_user_option.is_discouraged = 0
			$whereclause2
			", $dateline);	
		
			//########################################
			// start message
			//########################################
			
			// get options from Admin CP -> Options -> Basic Board Information -> Board Title
			$boardTitle = XenForo_Application::get('options')->boardTitle;	
			
			// get options from Admin CP -> Options -> Weekly Digest -> Language ID
			$languageId = XenForo_Application::get('options')->weeklyDigestLanguageId;	
			
			// run query
			$languageId = $db->fetchOne("
			SELECT language_id 
			FROM xf_language
			WHERE language_id = ?
			", $languageId);	
			
			// check languageId is valid
			if ($languageId == '')
			{
				echo 'Error. Invalid Language ID set in Options page.';
				exit();
			}
			
			// set language
			XenForo_Phrase::setLanguageId($languageId);
			
			// get subject
			$subject = new XenForo_Phrase('weeklydigest_subject');
			
			// get webRoot
			$webRoot = XenForo_Link::buildPublicLink('full:index');
	
			// remove index.php if not using full friendly url's
			$replace_src = 'index.php';
			$replace_str = '';
			$text = $webRoot;					
			$webRoot = str_replace($replace_src, $replace_str, $text);
			
			// define variable
			$message = '';			
			
			//########################################
			// add image 
			//########################################			
			
			// get options from Admin CP -> Options -> Weekly Digest -> Image URL
			$imageUrl = XenForo_Application::get('options')->weeklyDigestImageUrl;			
	
			if ($imageUrl != '')
			{
				// add to message
				$message .=  "<img src='" . $imageUrl . "' alt='' /><br /><br />";
			}
			
			//########################################
			// add author 
			//########################################				
			
			// get options from Admin CP -> Options -> Weekly Digest -> Show Author
			$showAuthor = XenForo_Application::get('options')->weeklyDigestShowAuthor;
	
			if (!$showAuthor)
			{	
				// foreach threads
				foreach ($threads as $thread)
				{											
					// build link
					$link = XenForo_Link::buildPublicLink('threads', $thread);
					
					// censor title
					$thread['title'] = XenForo_Helper_String::censorString($thread['title']);
					
					// censor link
					$link = XenForo_Helper_String::censorString($link);								
					
					// message details
					$message .= $thread['forumTitle'] . '<br />';
					$message .= '<a href="' . $webRoot . $link . '">' . $thread['title'] . '</a><br /><br />';		
				}			
			}
			
			if ($showAuthor)
			{
				// foreach threads
				foreach ($threads as $thread)
				{											
					// build link
					$link = XenForo_Link::buildPublicLink('threads', $thread);
					
					// censor title
					$thread['title'] = XenForo_Helper_String::censorString($thread['title']);
					
					// censor link
					$link = XenForo_Helper_String::censorString($link);								
					
					// message details
					$message .= $thread['forumTitle'] . '<br />';
					$message .= '<a href="' . $webRoot . $link . '">' . $thread['title'] . '</a><br />';
					$message .= $thread['username'] . '<br /><br />';		
				}			
			}
			
			//########################################
			// add unsubscribe link
			//########################################
	
			// message
			$message .= new XenForo_Phrase('weeklydigest_unsubscribe_link');
			
			// create unsubscribeLink
			$unsubscribeLink = $webRoot . 'weeklydigest/manage/';
			
			// replace unsubscribe_link
			$message = str_replace('{unsubscribe_link}', $unsubscribeLink, $message);	
			
			//########################################
			// add sent to username in message
			//########################################	
			
			// get options from Admin CP -> Options -> Weekly Digest -> Show Username
			$showUsername = XenForo_Application::get('options')->weeklyDigestShowUsername;		
			
			// only add if option selected
			if ($showUsername)
			{
				// message
				$message .= '<br /><br />' . new XenForo_Phrase('weeklydigest_sent_to_username');
			}
	
			//########################################
			// send email
			//########################################
			
			// add line breaks
			$message .= '<br /><br />';
			
			// set to zero, no PHP time limit is imposed
			set_time_limit(0);
	
			// foreach users
			foreach ($users as $user)
			{
				// replace username
				$newMessage = str_replace('{username}', $user['username'], $message);			
				
				// prepare params                    
				$params = array(
					'subject' => $subject,
					'message' => $newMessage
				);
					
				// prepare mail variable
				$mail = XenForo_Mail::create('weeklydigest_contact', $params);
				
				// send mail
				$mail->queue($user['email'], $user['username']);
			}
		}
	}
}
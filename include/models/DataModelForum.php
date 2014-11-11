<?php
	require_once('data/DataModel.php');
	require_once('login.php');
	require_once('models/DataIterForum.php');
	
	define('ACL_READ', 1);
	define('ACL_WRITE', 2);
	define('ACL_REPLY', 4);
	define('ACL_POLL', 8);
	
	/**
	  * A class implementing forum data
	  */
	class DataModelForum extends DataModel {
		var $dataiter = 'DataIterForum';
		var $threads_per_page = 15;
		var $messages_per_page = 15;
		var $current_page = 0;

		/**
		  * Create a new DataModelForum object
		  * @db the database to use
		  *
		  * @result a new DataModelForum object
		  */
		function __construct($db) {
			parent::__construct($db, 'forums');
		}
		
		/**
		  * Get the bitmasks of the possible permission types (ACL_READ,
		  * ACL_WRITE, ACL_REPLY, ACL_POLL)
		  *
		  * @result an array containing the possible permission type bitmasks
		  */
		function get_acls() {
			return array(ACL_READ, ACL_WRITE, ACL_REPLY, ACL_POLL);
		}
		
		/**
		  * Get ACL bitmask from id
		  * @id the id of the ACL bitmask to get
		  *
		  * @result the ACL bitmask
		  */
		function get_acl($id) {
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_acl
					WHERE
						id = ' . intval($id));
			
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Check whether a certain commissie has permission to do
		  * something in some forum
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for
		  * @commissieid the commissie to check the permissions for
		  *
		  * @result true if the commissie has the correct permissions,
		  * false otherwise
		  */
		function check_acl_commissie($forumid, $acl, $commissieid) {
			/* Check for general commissie perms */
			$num = $this->db->query('
					SELECT
						*
					FROM
						forum_acl
					WHERE
						forumid = ' . intval($forumid) . ' AND
						(permissions & ' . intval($acl) . ') <> 0 AND
						(uid = ' . intval($commissieid) . ' OR uid = -1) AND
						type = 2');
			
			if ($num)
				return true;
				
			/* Check for commissie in a group */

			$num = $this->db->query('
					SELECT
						*
					FROM
						forum_acl,
						forum_group_member
					WHERE
						forum_acl.type = 3 AND 
						(forum_acl.uid = forum_group_member.guid OR forum_acl.uid = -1) AND
						forum_group_member.type = 2 AND (forum_group_member.uid = ' . intval($commissieid) . ' OR forum_group_member.uid = -1)');
			
			if ($num)
				return true;
			
			return false;
		}

		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum by permissions of a commissie he's in
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for
		  * @memberid the member to check the permissions for
		  * @member_info optional; the member info of the member (only
		  * there for performance)
		  *
		  * @result true if member has the correct permissions by
		  * the commissies he's in, false otherwise
		  */
		function _check_acl_commissies($forumid, $acl, $memberid, $member_info = null) {
			if ($member_info)
				$commissies = $member_info['commissies'];
			else {
				$member_model = get_model('DataModelMember');
				$commissies = $member_model->get_commissies($memberid);
			}
			
			foreach ($commissies as $commissie) {
				if ($this->check_acl_commissie($forumid, $acl, $commissie))
					return true;
			}
			
			return false;
		}
		
		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum by permissions of a group he's in
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for 
		  * @memberid the member to check the permissions for
		  *
		  * @result true if member has the correct permissions by
		  * the groups he's in, false otherwise
		  */		
		function _check_acl_group($forumid, $acl, $memberid) {
			$num = $this->db->query_value('
					SELECT 
						COUNT(*)
					FROM 
						forum_acl,
						forum_group_member
					WHERE
						forum_acl.forumid = ' . intval($forumid) . ' AND
						(forum_acl.permissions & ' . intval($acl) . ') <> 0 AND 
						forum_acl.type = 3 AND
						forum_acl.uid = forum_group_member.guid AND
						(forum_group_member.type = -1 OR (
						(forum_group_member.type = 1 AND
						forum_group_member.uid = ' . intval($memberid) . ')))');
			
			if ($num)
				return true;
			else
				return false;
		}
		
		/**
		  * The default permissions which applies to forums that don't 
		  * have any specific permissions set
		  *
		  * @result the default permission bitmask
		  */
		function get_default_acl() {
			return ACL_READ | ACL_WRITE | ACL_REPLY;
		}
		
		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum by member permissions
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for (a value of -1
		  * always succeeds)
		  * @memberid optional; the member to check the permissions for 
		  * (or -1 to check for the currently logged in member, which
		  * is also the default)
		  *
		  * @result true if member has the correct permissions, false 
		  * otherwise
		  */
		function check_acl_member($forumid, $acl, $memberid = -1) {
			if ($acl == -1)
				return true;

			$member_info = null;

			if ($memberid == -1) {
				$member_info = logged_in();
				
				if ($member_info)
					$memberid = $member_info['id'];
				elseif ($acl & (ACL_WRITE | ACL_REPLY | ACL_POLL))
					return false;
			}

			$num = $this->db->query_first('
					SELECT
						id
					FROM
						forum_acl
					WHERE
						forum_acl.forumid = ' . intval($forumid) . '
					LIMIT
						1');
			
			if (!$num) {
				/* Return the default ACL (which is read only) */
				return $acl & ($this->get_default_acl());
			}
			
			/* Check permissions for everyone (type == -1) and member (type == 1 AND uid = member) */
			$num = $this->db->query_value('
					SELECT 
						COUNT(*)
					FROM 
						forum_acl
					WHERE
						forumid = ' . intval($forumid) . ' AND
						(permissions & ' . intval($acl) . ') <> 0 AND 
						(type = -1 OR
						(uid = ' . intval($memberid) . ' AND type = 1))');
			
			/* Permission granted */
			if ($num)
				return true;
			
			return false;
		}

		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum (either personal, commissie or group)
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for (a value of -1
		  * always succeeds)
		  * @memberid optional; the member to check the permissions for 
		  * (or -1 to check for the currently logged in member, which
		  * is also the default)
		  *
		  * @result true if member has the correct permissions, false
		  * otherwise
		  */
		function check_acl($forumid, $acl, $memberid = -1) {
			if ($this->check_acl_member($forumid, $acl, $memberid))
				return true;

			if ($memberid == -1 && !logged_in() && ($acl & (ACL_WRITE | ACL_REPLY | ACL_POLL)))
				return false;

			if ($memberid == -1) {
				$member_data = logged_in();
				$memberid = $member_data['id'];
			} else {
				$member_data = null;
			}
			
			/* Check commissie perms */
			if ($this->_check_acl_commissies($forumid, $acl, $memberid, $member_data))
				return true;
			
			/* Check forum group perms */
			if ($this->_check_acl_group($forumid, $acl, $memberid, $member_data))
				return true;			
			
			return false;
		}
		
		/**
		  * Get the forum headers (separators)
		  *
		  * @result an array of #DataIter
		  */
		function get_headers() {
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_header
					ORDER BY
						position');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get a forum with optional permission checking
		  * @forumid the forum to get
		  * @acl optional; the permissions to check (defaults to -1
		  * in which case the permission check always succeeds)
		  *
		  * @result a #DataIter if the forum could be found and
		  * if permissions were met, false otherwise
		  */
		function get_iter($forumid, $acl = -1) {
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forums
					WHERE
						id = ' . intval($forumid));
			
			if (!$row || !$this->check_acl($forumid, intval($acl)))
				return null;

			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get all forums
		  * @readable optional; if true then only return the forums
		  * that are readable by the current user, returns all forums
		  * otherwise. Defaults to true
		  *
		  * @result an array of #DataIter
		  */
		function get($readable = true) {
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forums
					ORDER BY
						position,
						name');
			
			if (!$rows)
				return null;

			if ($readable) {
				$items = $rows;
				$rows = array();

				foreach ($items as $row) {
					/* Check forum readability */
					if ($this->check_acl($row['id'], ACL_READ))
						$rows[] = $row;
				}
			}

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get a forum group
		  * @id the forum group id
		  *
		  * @result a #DataIter
		  */
		function get_group($id) {
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_group
					WHERE
						id = ' . intval($id) . '
					LIMIT 1');

			return $this->_row_to_iter($row);		
		}
		
		/**
		  * Get a group member
		  * @id the group member id
		  *
		  * @result a #DataIter
		  */
		function get_group_member($id) {
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_group_member
					WHERE
						id = ' . intval($id));
			
			return $this->_row_to_iter($row);		
		}
		
		/**
		  * Get all members of a certain group
		  * @id the group id
		  *
		  * @result an array of #DataIter
		  */
		function get_group_members($id) {
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_group_member
					WHERE
						guid = ' . intval($id));
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get all member groups
		  *
		  * @result an array of #DataIter
		  */
		function get_groups() {
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_group
					ORDER BY
						name');

			return $this->_rows_to_iters($rows);
		}

		/**
		  * Get a thread. The thread is only returned if the current
		  * user has read permissions for the thread
		  * @id the id of the thread
		  *
		  * @result a #DataIter
		  */
		function get_thread($id) {
			$row = $this->db->query_first('
					SELECT
						*,
						to_char(date, \'DD-MM-YYYY, HH24:MI\') AS datum,
						date_part(\'day\', CURRENT_TIMESTAMP - date) AS since
					FROM
						forum_threads
					WHERE
						id = ' . intval($id));

			if ($row && !$this->check_acl($row['forum'], ACL_READ))
				return null;

			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get threads from a forum on a certain page
		  * @forum a #DataIter representing a forum
		  * @page reference; specifies the page of the forum to
		  * get the threads for. It will be changed to fall inside the
		  * bounds of the pages in the forum if necessary.
		  * @max reference; will contain the maximum number of pages
		  * in the forum
		  *
		  * @result an array of #DataIter containing the threads on
		  * the specified page of the forum. It returns null when the
		  * forum is not readable by the current user
		  */
		function get_threads($forum, &$page, &$max) {
			if (!$this->check_acl($forum->get('id'), ACL_READ))
				return null;

			$max = max($forum->get_num_forum_pages() - 1, 0);
			$page = min($max, max(0, intval($page)));
			
			$this->current_page = $page;
			
			return $forum->get_last_thread(($page * $this->threads_per_page), $this->threads_per_page);
		}
		
		/**
		  * Get a forum message
		  * @id the id of the message
		  *
		  * @result a #DataIter
		  */
		function get_message($id) {
			$row = $this->db->query_first('
					SELECT
						*,
						to_char(date, \'DD-MM-YYYY, HH24:MI\') AS datum
					FROM
						forum_messages
					WHERE
						id = ' . intval($id) . '
					LIMIT
						1');
			
			return $this->_row_to_iter($row);
		}

		/**
		  * Get the number of posts for a certain author
		  * @authorid optional; the id of the author. Defaults to
		  * all authors
		  * @author_type optional; the type of author. Defaults to
		  * all types of authors
		  *
		  * @result the number of posts
		  */
		function _get_num_messages($authorid = -1, $author_type = -1) {
			static $posts = array();
			
			if (isset($posts[$author_type][$authorid]))
				return $posts[$author_type][$authorid];
			
			$num = $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_messages' .
					($authorid != -1 ? ('
					WHERE
						forum_messages.author = ' . intval($authorid) . ' AND
						forum_messages.author_type = ' . intval($author_type)) : ''));

			$posts[$author_type][$authorid] = $num;

			return $num;
		}
		
		/**
		  * Get author statistics for a certain message
		  * @message the message to get the statistics for
		  * @total reference; the total number of posts made
		  *
		  * @result the number of posts made by the author of the
		  * message
		  */
		function get_author_stats($message, &$total) {
			$total = $this->_get_num_messages();

			return $this->_get_num_messages($message->get('author'), $message->get('author_type'));
		}
		
		/**
		  * Get author info (name, avatar, email) for a certain message
		  * @message a #DataIter representing a message
		  * @field the field (author, last_author) to get the info for
		  *
		  * @result an associative array containing the author
		  * information
		  */
		function _get_author_info_real($message, $field) {
			static $authors = array();
			
			$id = $message->get($field);
			$type = intval($message->get($field . '_type'));
			
			if (isset($authors[$type][$id][$field]))
				return $authors[$type][$id][$field];
			
			switch ($type) {
				case 1: /* Person */
					$member_model = get_model('DataModelMember');
					$member = $member_model->get_iter($id);
					
					if (!$member)
						return null;

					$name = member_nick_name($member);

					if ($name == '')
						$name = member_full_name($member, false, true);
					
					$authors[$type][$id][$field] = array(
						'name' => $name,
						'avatar' => $member->get('avatar'),
						'email' => $member->get('email')
					);
				break;
				case 2: /* Commissie */
					$commissie_model = get_model('DataModelCommissie');
					$commissie = $commissie_model->get_iter($id);
					
					if (!$commissie)
						return null;

					$avatar_file = 'images/avatars/' . $commissie->get('nocaps') . '.png';
					
					$authors[$type][$id][$field] = array(
						'name' => $commissie->get('naam'),
						'avatar' => file_exists($avatar_file) ? $avatar_file : null,
						'email' => $commissie_model->get_email($commissie->get_id())
					);
				break;
			}
			
			if (!isset($authors[$type][$id][$field]))
				$authors[$type][$id][$field] = array('name' => '', 'avatar' => null, 'email' => null);
			
			return $authors[$type][$id][$field];		
		}

		/**
		  * Get author info for a message
		  * @message a #DataIter representing a message
		  *
		  * @result an associative array containing author information
		  */
		function get_author_info($message) {
			$info = $this->_get_author_info_real($message, 'author');
			$info_last = $this->_get_author_info_real($message, 'last_author');
			
			$info['last_name'] = $info_last['name'];
			$info['last_avatar'] = $info_last['avatar'];
			
			return $info;
		}
		
		/**
		  * Get the name of the person/group/commissie of a certain
		  * permission
		  * @acl a #DataIter representing a permission
		  *
		  * @result a string with the name of the person/group/commissie
		  * the permission belongs to
		  */
		function get_acl_name($acl) {
			switch ($acl->get('type')) {
				case -1:
					return __('Iedereen');
				case 1:
					if ($acl->get('uid') == -1)
						return __('Alle leden');

					$member_model = get_model('DataModelMember');
					$member_data = $member_model->get_iter($acl->get('uid'));
					
					if ($member_data)
						return member_full_name($member_data);
				break;
				case 2:
					if ($acl->get('uid') == -1)
						return __('Alle commissies');

					$commissie_model = get_model('DataModelCommissie');
					$commissie_data = $commissie_model->get_iter($acl->get('uid'));
					
					if ($commissie_data)
						return $commissie_data->get('naam');
				break;
				case 3:
					if ($acl->get('uid') == -1)
						return __('Alle groepen');

					return $this->db->query_value('
							SELECT
								name
							FROM
								forum_group
							WHERE
								id = ' . intval($acl->get('uid')));
				break;
			}

			return __('Onbekend');
		}
		
		/**
		  * Get the type of a certain permission
		  * @acl a #DataIter representing a permission
		  *
		  * @result a string with the name of the type of permission
		  */
		function get_acl_type($acl) {
			switch ($acl->get('type')) {
				case -1:
					return __('Iedereen');
				case 1:
					return __('Lid');
				break;
				case 2:
					return __('Commissie');
				break;
				case 3:
					return __('Groep');
				break;
				default:
					return __('Onbekend');
				break;
			}
		}

		/**
		  * Insert a thread
		  * @iter an #DataIter representing a thread
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		function insert_thread($iter) {
			return $this->_insert('forum_threads', $iter, true);
		}
		
		/**
		  * Get visit info
		  * @forumid the id of the forum to get visit info for
		  * @memberid the id of the member to get visit info for
		  *
		  * @result a #DataIter or null if no visit info could be found
		  */
		function _get_visit_info_real($forumid, $memberid) {
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_visits
					WHERE
						forum = ' . intval($forumid) . ' AND
						lid = ' . intval($memberid) . '
					LIMIT
						1');
			
			return $this->_row_to_iter($row);
		}
		
		/** 
		  * Returns a #DataIter of the special forum ('weblog','news', or 'poll')
		  * @name the name of the special forum
		  *
		  * @result a #DataIter of the specified forum
		  */
		function get_special_forum($name) {
			$specials = array('poll', 'news', 'weblog');
			
			if (!in_array($name, $specials))
				return false;
			else {
				$config_model = get_model('DataModelConfiguratie');
				$config_iter = $config_model->get_iter($name . '_forum');
				
				if ($config_iter === null)
					return false;
					
				$iter = $this->get_iter($config_iter->get('value'));
				return $iter;
			}
		}

		/**
		  * Get visit info. Creates a visit info entry if none exists
		  * yet
		  * @forumid the id of the forum to get visit info for
		  * @memberid the id of the member to get visit info for
		  *
		  * @result a #DataIter
		  */
		function _get_visit_info($forumid, $memberid) {
			$iter = $this->_get_visit_info_real($forumid, $memberid);
			
			if (!$iter) {
				$iter = new $this->dataiter(-1, $this, array(
						'forum' => intval($forumid),
						'lid' => intval($memberid)));
				$this->_insert('forum_visits', $iter);
				$iter = $this->_get_visit_info_real($forumid, $memberid);
			}

			return $iter;
		}
		
		/**
		  * Returns whether a forum contains unread messages for the
		  * current user
		  * @forumid the id of the forum to check unread messages for
		  *
		  * @result true if the forum as unread messages, false 
		  * otherwise
		  */
		function forum_unread($forumid) {
			/* Returns whether the forum contains unread messages.
			   The function checks if there are any new messages
			   since the last visit to the forum (forum_visits). And if so if the
			   messages have been read since the last visit (forum_sessionreads) */
			$member_data = logged_in();
			
			if (!$member_data)
				return;
			
			/* Get visit info */
			$visit = $this->_get_visit_info($forumid, $member_data['id']);
			
			/* Check the number of unread threads in the forum by last visit */
			$num_visit_unread = $this->db->query_value('
					SELECT
						COUNT(DISTINCT forum_threads.id)
					FROM
						forum_threads
					LEFT JOIN forum_messages ON 
						(forum_threads.id = forum_messages.thread)
					WHERE
						forum_threads.forum = ' . intval($forumid) . ' AND 
						forum_messages.date > TIMESTAMP \'' . $visit->get('lastvisit') . '\'');

			/* No unread threads, return false */
			if (!$num_visit_unread)
				return false;

			/* Check the number of read threads in the forum by session */
			$num_session_read = $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_sessionreads
					WHERE
						lid = ' . intval($member_data['id']) . ' AND
						forum = ' . intval($forumid));

			return $num_visit_unread > $num_session_read;
		}
		
		/**
		  * Returns whether a thread contains unread messages for the
		  * current user
		  * @threadid the id of the thread to check unread messages for
		  *
		  * @result true if the thread as unread messages, false 
		  * otherwise
		  */
		function thread_unread($threadid) {
			$member_data = logged_in();
			
			if (!$member_data)
				return;

			$thread = $this->get_thread($threadid);
			
			if (!$thread)
				return;

			/* Get visit info */
			$visit = $this->_get_visit_info($thread->get('forum'), $member_data['id']);
			
			/* Check if the thread is unread by last visit */
			$num_visit_unread = $this->db->query_value('
					SELECT
						COUNT(DISTINCT forum_threads.id)
					FROM
						forum_threads
					LEFT JOIN forum_messages ON 
						(forum_threads.id = forum_messages.thread)
					WHERE
						forum_threads.id = ' . $threadid . ' AND 
						forum_messages.date > TIMESTAMP \'' . $visit->get('lastvisit') . '\'');
			
			/* Thread isn't older then last visit, return false */
			if (!$num_visit_unread)
				return false;
			
			/* Check if the thread has been read by session */
			$read = $this->db->query_value('
					SELECT
						1
					FROM
						forum_sessionreads
					WHERE
						thread = ' . intval($threadid) . ' AND
						lid = ' . intval($member_data['id']));
			
			return !$read;
		}
		
		/**
		  * Mark a thread to be read
		  * @threadid the id of the thread to mark as read
		  */
		function mark_read($threadid) {
			$member_data = logged_in();
			
			if (!$member_data)
				return;

			$thread = $this->get_thread($threadid);
			
			if (!$thread)
				return;
			
			$val = $this->db->query('
					SELECT
						1
					FROM
						forum_sessionreads
					WHERE
						thread = ' . intval($threadid) . ' AND
						lid = ' . intval($member_data['id']) . '
					LIMIT
						1');
			
			if ($val)
				return;
				
			$iter = new $this->dataiter(-1, null, array(
					'thread' => intval($threadid),
					'lid' => intval($member_data['id']),
					'forum' => intval($thread->get('forum'))));

			$this->_insert('forum_sessionreads', $iter);
		}
		
		/**
		  * Mark a thread as unread
		  * @threadid the id of the thread to mark as unread
		  */
		function mark_unread($threadid) {
			/* Deletes all session reads for this thread so that
			   the thread becomes unread for everyone again */
			$this->db->delete('forum_sessionreads', 'thread = ' . intval($threadid));
		}
		
		/**
		  * Update the last time a user has visited a forum
		  * @forumid optional; the id of the forum the user has visited 
		  * or 0 to update all the forums (defaults to 0)
		  */
		function update_last_visit($forumid = 0) {
			$member_data = logged_in();
			
			if (!$member_data)
				return;
			
			if ($forumid == 0) {
				$forums = $this->get();
				
				foreach ($forums as $forum)
					$this->update_last_visit($forum->get('id'));
			} else {
				/* Set last visit date to the session date, and set the session date to null
				   for all visits that are older then 15 minutes */
				$forum = $this->get_iter($forumid);
				
				if (!$forum)
					return;
				
				$this->db->update('forum_visits', array('lastvisit' => 'sessiondate', 'sessiondate' => null), 'lid = ' . intval($member_data['id']) . ' AND forum = ' . intval($forumid) . ' AND sessiondate+INTERVAL \'15 minutes\' < CURRENT_TIMESTAMP', array('lastvisit'));
				
				if ($this->db->get_affected_rows()) {
					/* Delete all obsolete session reads */
					$this->db->delete('forum_sessionreads', 'lid = ' . intval($member_data['id']) . ' AND forum = ' . intval($forumid));
				}
			}
		}
		
		/**
		  * Set a forum to be read
		  * @forumid the id of the forum to set to be read
		  */
		function set_forum_session_read($forumid) {
			$member_data = logged_in();
			
			if (!$member_data)
				return;

			$visit = $this->_get_visit_info($forumid, $member_data['id']);
			$this->update_last_visit($forumid);
			
			$visit->set_literal('sessiondate', 'CURRENT_TIMESTAMP');
			$this->db->update('forum_visits', $visit->get_changed_values(), 'lid = ' . intval($member_data['id']) . ' AND forum = ' . intval($forumid), $visit->get_literals());
		}
		
		/**
		  * Insert a message
		  * @iter a #DataIter representing a message
		  *
		  * @result the id of the message if succesful, null otherwise
		  */
		function insert_message($iter) {
			/* Mark the thread as unread */
			$this->mark_unread($iter->get('thread'));
			return $this->_insert('forum_messages', $iter, true);
		}
		
		/**
		  * Delete a forum. This function will also delete any
		  * threads and replies in this forum as well as any 
		  * permissions associated with it
		  * @iter a #DataIter representing a forum
		  */
		function delete($iter) {
			parent::delete($iter);
			
			$id = intval($iter->get('id'));

			/* delete all messages */
			$threads = $this->db->query('SELECT id FROM forum_threads WHERE forum = ' . $id);
			
			if ($threads) {
				foreach ($threads as $thread)
					$this->db->delete('forum_messages', 'thread = ' . intval($thread['id']));
			}
			
			$this->db->delete('forum_threads', 'forum = ' . $id);
			
			/* delete acl */
			$this->db->delete('forum_acl', 'forumid = ' . $id);
			
			/* last visits */
			$this->db->delete('forum_lastvisits', 'forum = ' . $id);
			
			/* session read */
			$this->db->delete('forum_sessionreads', 'forum = ' . $id);
		}
		
		/**
		  * Delete a permission
		  * @iter a #DataIter representing a permission
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		function delete_acl($iter) {
			return $this->_delete('forum_acl', $iter);
		}

		/**
		  * Insert a permission. It will update the bitmask if 
		  * a permission with the same forumid, type and uid already
		  * exists
		  * @iter a #DataIter representing a permission
		  *
		  * @result true if the insert was succesful, false otherwise
		  */		
		function insert_acl($iter) {
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_acl
					WHERE
						forumid = ' . intval($iter->get('forumid')) . ' AND
						type = ' . intval($iter->get('type')) . ' AND
						uid = ' . intval($iter->get('uid')));

			if ($row) {
				/* Acl already exist, overwrite perms */
				$acl = $this->_row_to_iter($row);
				$acl->set('permissions', intval($iter->get('permissions')));

				return $this->update_acl($acl);
			} else {
				return $this->_insert('forum_acl', $iter);
			}
		}
		
		/**
		  * Update a permission
		  * @iter a #DataIter representing a permission
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		function update_acl($iter) {
			return $this->_update('forum_acl', $iter);
		}
		
		/**
		  * Insert a group
		  * @iter a #DataIter representing a group
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		function insert_group($iter) {
			return $this->_insert('forum_group', $iter);
		}
		
		/**
		  * Delete a group. This function will also delete any 
		  * members and permissions associated with the group
		  * @iter a #DataIter representing a group
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		function delete_group($iter) {
			$result = $this->_delete('forum_group', $iter);
			$this->db->delete('forum_group_member', 'guid = ' . intval($iter->get('id')));
			$this->db->delete('forum_acl', 'type = 3 AND uid = ' . intval($iter->get('id')));
			
			return $result;
		}
		
		/**
		  * Update a group
		  * @iter a #DataIter representing a group
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		function update_group($iter) {
			return $this->_update('forum_group', $iter);
		}
		
		/**
		  * Insert a group member
		  * @iter a #DataIter representing a group member
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		function insert_group_member($iter) {
			return $this->_insert('forum_group_member', $iter);
		}
		
		/**
		  * Delete a group member
		  * @iter a #DataIter representing a group member
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		function delete_group_member($iter) {
			return $this->_delete('forum_group_member', $iter);
		}
		
		/**
		  * Perform actions when a commissie has been deleted. This
		  * function will delete any associated permission or group
		  * member with this commissie
		  * @iter a #DataIter representing a commissie
		  */
		function commissie_deleted($iter) {
			$this->db->delete('forum_acl', 'type = 2 AND uid = ' . intval($iter->get('id')));
			$this->db->delete('forum_group_member', 'type = 2 AND uid = ' . intval($iter->get('id')));
		}
		
		/**
		  * Delete a thread. This function will delete any replies
		  * in the thread as well
		  * @iter a #DataIter representing a thread
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		function delete_thread($iter) {
			$this->_delete('forum_threads', $iter);
			
			/* Delete all replies */
			$this->db->delete('forum_messages', 'thread = ' . $iter->get('id'));
		}
		
		/**
		  * Update a thread
		  * @iter a #DataIter representing a thread
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		function update_thread($iter) {
			return $this->_update('forum_threads', $iter);
		}
		
		/**
		  * Delete a message
		  * @iter a #DataIter representing a message
		  *
		  * @result true if the delete was succesful, the id of the 
		  * thread if the last message in a thread was removed, false
		  * otherwise
		  */
		function delete_message($iter) {
			$ret = $this->_delete('forum_messages', $iter);
			$thread = $this->get_thread($iter->get('thread'));

			/* Check if last message was removed */
			if ($thread && $thread->get_num_messages() == 0)
			{
				$ret = intval($thread->get('forum'));
				$this->delete_thread($thread);
			}
			
			return $ret;
		}
		
		/**
		  * Update a message
		  * @iter a #DataIter representing a message
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		function update_message($iter) {
			return $this->_update('forum_messages', $iter);
		}
		
		/**
		  * Update a forum header
		  * @iter a #DataIter representing a forum header
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		function update_header($iter) {
			return $this->_update('forum_header', $iter);
		}
		
		/**
		  * Insert a forum header
		  * @iter a #DataIter representing a forum header
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		function insert_header($iter) {
			return $this->_insert('forum_header', $iter);
		}
		
		/**
		  * Delete a forum header
		  * @iter a #DataIter representing a forum header
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		function delete_header($iter) {
			return $this->_delete('forum_header', $iter);
		}
	}
?>

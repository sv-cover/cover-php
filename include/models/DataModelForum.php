<?php
	require_once 'include/data/DataModel.php';

	trait UnifiedAuthor {
		public function get_unified_author()
		{
			return $this['author_type'] . '_' . $this['author_id'];
		}

		public function set_unified_author($author)
		{
			list($author_type, $author_id) = explode('_', $author, 2);

			switch ($author_type)
			{
				case DataModelForum::TYPE_PERSON:
					try {
						get_model('DataModelMember')->get_iter($author_id);
					} catch (DataIterNotFoundException $e) {
						throw new InvalidArugmentException("No member with id '$author_id' found.", 0, $e);
					}
					break;

				case DataModelForum::TYPE_COMMITTEE:
					try {
						get_model('DataModelCommissie')->get_iter($author_id);
					} catch (DataIterNotFoundException $e) {
						throw new InvalidArugmentException("No committee with id '$author_id' found.", 0, $e);
					}
					break;

				case DataModelForum::TYPE_GROUP:
					try {
						get_model('DataModelForum')->get_group($author_id);
					} catch (DataIterNotFoundException $e) {
						throw new InvalidArugmentException("No group with id '$author_id' found.", 0, $e);
					}
					break;

				default:
					throw new InvalidArugmentException("Invalid author type");
			}

			$this->set('author_id', $author_id);
			$this->set('author_type', $author_type);
		}
	}

	class DataIterForum extends DataIter
	{
		static public function fields()
		{
			return [
				'id',
				'name',
				'description',
				'position',
			];
		}

		public function new_thread()
		{
			return new DataIterForumThread($this->model, null, [
				'forum_id' => $this['id'],
				'author_id' => null,
				'author_type' => null,
				'subject' => null,
				'date' => new DateTime(),
				'poll' => 0
			]);
		}

		public function new_poll()
		{
			// Also here for easiness in the template. Now you can write
			// ''user_can_create forum.new_poll'' instead of
			// ''user_can_create models.Poll.new_poll(forum)'' which I doubt
			// actually works...

			return get_model('DataModelPoll')->new_poll($this);
		}

		/**
		  * Get the number of threads in a forum
		  * @iter a #DataIter representing a forum
		  *
		  * @result the number of threads in the forum
		  */
		public function get_num_threads() {
			return (int) $this->db->query_value('
				SELECT
					COUNT(*)
				FROM
					forum_threads
				WHERE
					forum_id = ' . intval($this['id']));
		}
		
		/**
		  * Get the number of pages in a forum
		  * @forum a #DataIter representing a forum
		  *
		  * @result the number of pages in the forum
		  */
		public function get_num_forum_pages()
		{
			return intval(ceil($this->get_num_threads() / floatval($this->model->threads_per_page)));
		}
		
		
		/**
		  * Get the number of messages in the forum
		  *
		  * @result the number of messages in the forum
		  */		
		public function get_num_forum_messages()
		{
			return (int) $this->db->query_value('
				SELECT
					COUNT(*)
				FROM
					forum_threads,
					forum_messages
				WHERE
					forum_messages.thread_id = forum_threads.id AND
					forum_threads.forum_id = ' . intval($this['id']));
		}

		
		/**
		  * Get permissions for a certain forum
		  *
		  * @result an array of #DataIter
		  */
		public function get_rights()
		{
			$rows = $this->db->query('
				SELECT
					*
				FROM
					forum_acl
				WHERE
					forum_id = ' . intval($this['id']) . '
				ORDER BY
					id');

			return $this->model->_rows_to_iters($rows, 'DataIterForumPermission');
		}
					
		/**
		  * Get a number of last written threads in a forum
		  * @iter a #DataIter representing a forum
		  * @offset optional; the offset from which to get the last 
		  * written threads (defaults to no offset)
		  * @limit optional; the number of threads to get. The 
		  * default returns only the last thread
		  *
		  * @result if no limit is specified it returns the last
		  * thread in a forum as a #DataIter. It returns the last
		  * threads as an array of #DataIter otherwise
		  */
		public function get_last_thread($offset = -1, $limit = -1, $last_reply = true)
		{
			$rows = $this->db->query('
				SELECT
					forum_threads.*,

					forum_messages.date AS last_date,
					forum_messages.id AS last_id,
					
					forum_messages.author_id AS last_author_id,
					forum_messages.author_type AS last_author_type,
					date_part(\'day\', CURRENT_TIMESTAMP - forum_threads.date) AS since
				FROM
					forum_threads
				LEFT JOIN forum_messages ON (forum_messages.thread_id = forum_threads.id AND
					forum_messages.id IN (SELECT ' . ($last_reply ? 'MAX' : 'MIN') . '(forum_messages.id) FROM forum_threads, forum_messages WHERE forum_threads.forum_id = ' . intval($this['id']) . ' AND forum_messages.thread_id = forum_threads.id GROUP BY forum_messages.thread_id))
				WHERE
					forum_id = ' . intval($this['id']) . '
				ORDER BY
					last_date DESC' .
				($offset != -1 ? (' OFFSET ' . intval($offset)) : '') .
				' LIMIT ' . ($limit != -1 ? intval($limit) : '1'));

			if ($rows && $limit == -1) {
				if (count($rows) > 0)
					return $this->model->_row_to_iter($rows[0], 'DataIterForumThread');
				else
					return null;
			}

			return $this->model->_rows_to_iters($rows, 'DataIterForumThread');
		}
		
		/**
		  * returns the last created thread in the forum
		  *
		  * @result the last thread as DataIterForum, or
		  * null if there is no such thread
		  */
		public function get_newest_thread()
		{
			$row = $this->db->query('
					SELECT
						*, 
						date_part(\'day\', CURRENT_TIMESTAMP - forum_threads.date) AS since
					FROM
						forum_threads
					WHERE 
						forum_id = ' . intval($this['id']) .'
					ORDER BY 
						id DESC
					LIMIT 
						1');

			if (!$row)
				return null;
			
			return $this->model->_row_to_iter($row[0], 'DataIterForumThread');
		}
	}

	class DataIterForumMessage extends DataIter
	{
		use UnifiedAuthor;

		static public function fields()
		{
			return [
				'id',
				'thread_id',
				'author_id',
				'author_type',
				'message',
				'date',
			];
		}

		/**
		  * Returns whether this message is the only message in the thread
		  *
		  * @return bool true if the message is the only message in the thread
		  */
		public function is_only_message()
		{
			$thread = $this->model->get_thread($this['thread_id']);

			return $thread['num_messages'] === 1;
		}

		/**
		 * Returns on which page in a thread this message will appear.
		 */
		public function get_thread_page()
		{
			$position = $this->db->query_value(sprintf('
				WITH thread_messages AS (
					SELECT
						f_m.id,
						ROW_NUMBER() OVER (ORDER BY f_m.date ASC) as position
					FROM
						forum_messages f_m
					WHERE f_m.thread_id = %d
				) 
				SELECT
					position
				FROM
					thread_messages
				WHERE
					id = %d', $this['thread_id'], $this['id']));

			return floor($position / $this->model->messages_per_page);
		}
	}

	class DataIterForumPermission extends DataIter
	{
		static public function fields()
		{
			return [
				'id',
				'forum_id',
				'author_id',
				'author_type',
				'permissions'
			];
		}
	}

	class DataIterForumGroup extends DataIter
	{
		static public function fields()
		{
			return [
				'id',
				'name'
			];
		}
	}

	class DataIterForumThread extends DataIter
	{
		use UnifiedAuthor;

		static public function fields()
		{
			return [
				'id',
				'forum_id',
				'author_id',
				'author_type',
				'subject',
				'date',
				'poll',
			];
		}

		public function new_message()
		{
			return new DataIterForumMessage($this->model, null, [
				'thread_id' => $this['id'],
				'author_id' => null,
				'author_type' => null,
				'message' => null,
				'date' => date('Y-m-d H:i:s'),
			]);
		}

		/**
		  * Get the number of replies in a thread
		  * @iter a #DataIter representing a thread
		  *
		  * @result the number of replies in the thread
		  */
		public function get_num_messages()
		{
			return intval($this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_messages
					WHERE
						forum_messages.thread_id = ' . intval($this['id'])));
		}
		
		/**
		  * Get the number of pages in a thread
		  * @iter a #DataIter representing a thread
		  *
		  * @result the number of pages in the thread
		  */
		public function get_num_thread_pages()
		{
			return intval(ceil($this->get_num_messages() / floatval($this->model->messages_per_page)));
		}
		
		/**
		  * Get the first (initial) thread message
		  *
		  * @result a #DataIter
		  */
		public function get_first_message()
		{
			$row = $this->db->query_first('
					SELECT
						*,
						to_char(date, \'DD-MM-YYYY, HH24:MI\') AS datum
					FROM
						forum_messages
					WHERE
						thread_id = ' . intval($this['id']) . '
					ORDER BY
						id
					LIMIT 1');
			
			return $this->model->_row_to_iter($row, 'DataIterForumMessage');
		}
		/**
		  * Get replies from a thread on a certain page
		  * @page reference; specifies the page of the thread to
		  * get the messages for. It will be changed to fall inside the
		  * bounds of the pages in the thread if necessary.
		  * @max reference; will contain the maximum number of pages
		  * in the thread
		  *
		  * @result an array of #DataIter containing the replies on
		  * the specified page of the thread. It returns null when the
		  * thread is not readable by the current user
		  */
		public function get_messages($page, &$max)
		{
			$max = $this->get_num_thread_pages() - 1;
			$page = min($max, max(0, intval($page)));

			$this->model->current_page = $page;

			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_messages
					WHERE
						thread_id = ' . intval($this['id']) . '
					ORDER BY
						id
					OFFSET
						' . ($page * $this->model->messages_per_page) . '
					LIMIT ' . $this->model->messages_per_page);

			return $this->model->_rows_to_iters($rows, 'DataIterForumMessage');
		}

		/**
		  * Returns whether a thread contains unread messages for the
		  * current user
		  *
		  * @result true if the thread as unread messages, false 
		  * otherwise
		  */
		public function has_unread_messages()
		{
			// Todo: Booo! get_identity() in a Model method instead of passed in as a parameter!
			$member_id = get_identity()->get('id', null);
			
			if ($member_id === null)
				return;

			/* Get visit info */
			$visit = $this->model->get_visit_info($this['forum'], $member_id);
			
			/* Check if the thread is unread by last visit */
			$num_visit_unread = $this->db->query_value('
					SELECT
						COUNT(DISTINCT forum_threads.id)
					FROM
						forum_threads
					LEFT JOIN forum_messages ON 
						(forum_threads.id = forum_messages.thread_id)
					WHERE
						forum_threads.id = ' . $this['id'] . ' AND 
						forum_messages.date > TIMESTAMP \'' . $visit['lastvisit'] . '\''); // Todo: is this safe to assume safe? Only way to get user data in that value is through sql injection, so in that case we are compromised anyway.
			
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
						thread_id = ' . intval($this['id']) . ' AND
						lid_id = ' . intval($member_id));
			
			return !$read;
		}
	}

	class DataIterForumHeader extends DataIter 
	{
		static public function fields()
		{
			return [
				'id',
				'name',
				'position',
			];
		}	
	}
	
	/**
	  * A class implementing forum data
	  */
	class DataModelForum extends DataModel
	{
		// Author types
		const TYPE_EVERYONE = -1;
		const TYPE_PERSON = 1;
		const TYPE_COMMITTEE = 2;
		const TYPE_GROUP = 3;

		// ACL rights
		const ACL_READ = 1;
		const ACL_WRITE = 2;
		const ACL_REPLY = 4;
		const ACL_POLL = 8;
		
		public $threads_per_page = 15;

		public $messages_per_page = 15;

		public $current_page = 0;

		/**
		  * Create a new DataModelForum object
		  * @db the database to use
		  *
		  * @result a new DataModelForum object
		  */
		public function __construct($db) {
			parent::__construct($db, 'forums');
		}
		
		/**
		  * Get the bitmasks of the possible permission types (ACL_READ,
		  * ACL_WRITE, ACL_REPLY, ACL_POLL)
		  *
		  * @result an array containing the possible permission type bitmasks
		  */
		public function get_acls()
		{
			return array(self::ACL_READ, self::ACL_WRITE, self::ACL_REPLY, self::ACL_POLL);
		}
		
		/**
		  * Get ACL bitmask from id
		  * @id the id of the ACL bitmask to get
		  *
		  * @result the ACL bitmask
		  */
		function get_acl($id)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_acl
					WHERE
						id = ' . intval($id));

			if (!$row)
				throw new DataIterNotFoundException('Could not found ACL rule.');
			
			return $this->_row_to_iter($row, 'DataIterForumPermission');
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
		public function check_acl_commissie(DataIterForum $forum, $acl, $committee_id)
		{
			/* Check for general commissie perms */
			$num = $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_acl
					WHERE
						forum_id = ' . intval($forum['id']) . ' AND
						(permissions & ' . intval($acl) . ') <> 0 AND
						(author_id = ' . intval($committee_id) . ' OR author_id = -1) AND
						author_type = ' . self::TYPE_COMMITTEE);
			
			if ($num > 0)
				return true;
				
			/* Check for commissie in a group */

			$num = $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_acl,
						forum_group_member
					WHERE
						forum_acl.type = ' . self::TYPE_GROUP . ' AND 
						(forum_acl.author_id = forum_group_member.group_id OR forum_acl.author_id = -1) AND
						forum_group_member.author_type = ' . self::TYPE_COMMITTEE . ' AND (forum_group_member.author_id = ' . intval($committee_id) . ' OR forum_group_member.author_id = -1)');
			
			return $num > 0;
		}

		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum by permissions of a commissie he's in
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for
		  * @member_id the member to check the permissions for
		  * @member_info optional; the member info of the member (only
		  * there for performance)
		  *
		  * @result true if member has the correct permissions by
		  * the commissies he's in, false otherwise
		  */
		protected function check_acl_commissies(DataIterForum $forum, $acl, IdentityProvider $identity)
		{
			foreach ($identity->get('committees', []) as $committee) {
				if ($this->check_acl_commissie($forum, $acl, $committee))
					return true;
			}
			
			return false;
		}
		
		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum by permissions of a group he's in
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for 
		  * @member_id the member to check the permissions for
		  *
		  * @result true if member has the correct permissions by
		  * the groups he's in, false otherwise
		  */		
		protected function check_acl_group(DataIterForum $forum, $acl, IdentityProvider $identity)
		{
			$sql_user_specific = $identity->member() !== null
				? sprintf('OR (forum_group_member.type = %d AND forum_group_member.uid = %d)',
					self::TYPE_PERSON, $identity->get('id'))
				: '';

			// Todo: seriously check this or rewrite it to be better readable DNF!
			$num = $this->db->query_value('
					SELECT 
						COUNT(*)
					FROM 
						forum_acl,
						forum_group_member
					WHERE
						forum_acl.forum_id = ' . intval($forum['id']) . ' AND
						(forum_acl.permissions & ' . intval($acl) . ') <> 0 AND 
						forum_acl.type = ' . self::TYPE_GROUP . ' AND
						forum_acl.uid = forum_group_member.guid AND
						(forum_group_member.type = ' . self::TYPE_EVERYONE . ' ' . $sql_user_specific . ' )');
			
			return $num > 0;
		}
		
		/**
		  * The default permissions which applies to forums that don't 
		  * have any specific permissions set
		  *
		  * @result the default permission bitmask
		  */
		public function get_default_acl()
		{
			return self::ACL_READ | self::ACL_WRITE | self::ACL_REPLY;
		}
		
		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum by member permissions
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for 
		  * @member the member to check the permissions for 
		  *
		  * @result true if member has the correct permissions, false 
		  * otherwise
		  */
		protected function check_acl_member(DataIterForum $forum, $acl, IdentityProvider $identity)
		{
			// Fetch the forum specific ACL policies
			$num = $this->db->query_first('
					SELECT
						id
					FROM
						forum_acl
					WHERE
						forum_acl.forum_id = ' . intval($forum['id']) . '
					LIMIT
						1');
			
			// No specific policies? Then use the default
			if (!$num)
				return $acl & $this->get_default_acl();

			// Check permissions for everyone (type == -1) and member (type == 1 AND uid = member)
			$sql_where = $identity->member() !== null
				? '(type = -1 OR (uid = ' . intval($identity->get('id')) . ' AND type = 1))'
				: '(type = -1)';
			
			$num = $this->db->query_value('
					SELECT 
						COUNT(*)
					FROM 
						forum_acl
					WHERE
						forum_id = ' . intval($forum['id']) . ' AND
						(permissions & ' . intval($acl) . ') <> 0 AND 
						' . $sql_where);
			
			// Permission granted
			return $num > 0;
		}

		/**
		  * Check if a certain member has permissions to do something
		  * in a certain forum (either personal, commissie or group)
		  * @forumid the forum to check the permissions for
		  * @acl the permission bitmask to check for (a value of -1
		  * always succeeds)
		  *
		  * @result true if member has the correct permissions, false
		  * otherwise
		  */
		public function check_acl(DataIterForum $forum, $acl, IdentityProvider $identity)
		{
			return $this->check_acl_member($forum, $acl, $identity)
				|| $this->check_acl_commissies($forum, $acl, $identity)
				|| $this->check_acl_group($forum, $acl, $identity);
		}
		
		/**
		  * Get the forum headers (separators)
		  *
		  * @result an array of #DataIter
		  */
		public function get_headers()
		{
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_header
					ORDER BY
						position');
			
			return $this->_rows_to_iters($rows, 'DataIterForumHeader');
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
		public function get_iter($forumid)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forums
					WHERE
						id = ' . intval($forumid));
			
			return $this->_row_to_iter($row, 'DataIterForum');
		}
		
		/**
		  * Get all forums
		  * @readable optional; if true then only return the forums
		  * that are readable by the current user, returns all forums
		  * otherwise. Defaults to true
		  *
		  * @result an array of #DataIter
		  */
		public function get()
		{
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forums
					ORDER BY
						position,
						name');
			
			return $this->_rows_to_iters($rows, 'DataIterForum');
		}
		
		/**
		  * Get a forum group
		  * @id the forum group id
		  *
		  * @result a #DataIter
		  */
		public function get_group($id)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_group
					WHERE
						id = ' . intval($id) . '
					LIMIT 1');

			if (!$row)
				throw new DataIterNotFoundException('Forum group not found');

			return $this->_row_to_iter($row, 'DataIterForumGroup');		
		}
		
		/**
		  * Get a group member
		  * @id the group member id
		  *
		  * @result a #DataIter
		  */
		public function get_group_member($id)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_group_member
					WHERE
						id = ' . intval($id));

			if (!$row)
				throw new DataIterNotFoundException('Forum group member not found');
			
			return $this->_row_to_iter($row, 'DataIterForumGroupMember');
		}
		
		/**
		  * Get all members of a certain group
		  * @id the group id
		  *
		  * @result an array of #DataIter
		  */
		public function get_group_members($id)
		{
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_group_member
					WHERE
						guid = ' . intval($id));
			
			return $this->_rows_to_iters($rows, 'DataIterForumGroupMember');
		}
		
		/**
		  * Get all member groups
		  *
		  * @result an array of #DataIter
		  */
		public function get_groups()
		{
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_group
					ORDER BY
						name');

			return $this->_rows_to_iters($rows, 'DataIterForumGroup');
		}

		/**
		  * Get a thread. The thread is only returned if the current
		  * user has read permissions for the thread
		  * @id the id of the thread
		  *
		  * @result a #DataIter
		  */
		public function get_thread($id)
		{
			$row = $this->db->query_first('
					SELECT
						*,
						date_part(\'day\', CURRENT_TIMESTAMP - date) AS since
					FROM
						forum_threads
					WHERE
						id = ' . intval($id));

			if (!$row)
				throw new DataIterNotFoundException('Forum thread could not be found.');

			return $this->_row_to_iter($row, 'DataIterForumThread');
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
		public function get_threads($forum, &$page, &$max)
		{
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
		public function get_message($id)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_messages
					WHERE
						id = ' . intval($id));

			if (!$row)
				throw new DataIterNotFoundException('Forum message not found.');
			
			return $this->_row_to_iter($row, 'DataIterForumMessage');
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
		protected function _get_num_messages($author_id = -1, $author_type = -1)
		{
			static $posts = array();
			
			if (isset($posts[$author_type][$author_id]))
				return $posts[$author_type][$author_id];
			
			$num = $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_messages' .
					($author_id != -1 ? ('
					WHERE
						forum_messages.author_id = ' . intval($author_id) . ' AND
						forum_messages.author_type = ' . intval($author_type)) : ''));

			$posts[$author_type][$author_id] = $num;

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
		public function get_author_stats($message)
		{
			$stats = new stdClass();
			$stats->total = $this->_get_num_messages();
			$stats->posts = $this->_get_num_messages($message['author_id'], $message['author_type']);
			$stats->percentage_of_total = $stats->posts / $stats->total * 100;
			return $stats;
		}
		
		/**
		  * Get author info (name, avatar, email) for a certain message
		  * @message a #DataIter representing a message
		  * @field the field (author, last_author) to get the info for
		  *
		  * @result an associative array containing the author
		  * information
		  */
		protected function _get_author_info_real($message, $field)
		{
			static $authors = array();
			
			$id = intval($message[$field . '_id']);
			$type = intval($message[$field . '_type']);
			
			if (isset($authors[$type][$id][$field]))
				return $authors[$type][$id][$field];

			// Default value when no author is found
			$author = [
				'name' => __('Onbekend'),
				'avatar' => null,
				'email' => null
			];
			
			try {
				switch ($type) {
					case self::TYPE_PERSON: /* Person */
						$member_model = get_model('DataModelMember');
						$member = $member_model->get_iter($id);
						
						$name = member_nick_name($member);

						if ($name == '')
							$name = member_full_name($member, BE_PERSONAL);
						
						$author = array(
							'name' => $name,
							'avatar' => $member['avatar'],
							'email' => $member['email']
						);
					break;
					case self::TYPE_COMMITTEE: /* Commissie */
						$commissie_model = get_model('DataModelCommissie');
						$commissie = $commissie_model->get_iter($id);
						
						$avatar_file = 'images/avatars/' . $commissie['nocaps'] . '.png';
						
						$author = array(
							'name' => $commissie['naam'],
							'avatar' => file_exists($avatar_file) ? $avatar_file : null,
							'email' => $commissie['email']
						);
					break;						
				}
			} catch (DataIterNotFoundException $e) {
				// Too bad Zubat! We'll go with the default value that was set before the switch.
			}
			
			// Cache and return the value!
			return $authors[$type][$id][$field] = $author;
		}

		/**
		  * Get author info for a message
		  * @message a #DataIter representing a message
		  *
		  * @result an associative array containing author information
		  */
		public function get_author_info(DataIter $message)
		{
			$info = $this->_get_author_info_real($message, 'author');
			
			if (isset($message['last_author'])) {
				$info_last = $this->_get_author_info_real($message, 'last_author');
				$info['last_name'] = $info_last['name'];
				$info['last_avatar'] = $info_last['avatar'];
			}
			
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
		public function get_acl_name(DataIterForumPermission $acl)
		{
			switch ($acl->get('type'))
			{
				case self::TYPE_EVERYONE:
					return __('Iedereen');
				case self::TYPE_PERSON:
					if ($acl->get('author_id') == self::TYPE_EVERYONE)
						return __('Alle leden');

					$member_model = get_model('DataModelMember');
					$member_data = $member_model->get_iter($acl->get('author_id'));
					
					if ($member_data)
						return member_full_name($member_data, IGNORE_PRIVACY);
				break;
				case self::TYPE_COMMITTEE:
					if ($acl->get('author_id') == self::TYPE_EVERYONE)
						return __('Alle commissies');

					$commissie_model = get_model('DataModelCommissie');
					$commissie_data = $commissie_model->get_iter($acl->get('author_id'));
					
					if ($commissie_data)
						return $commissie_data->get('naam');
				break;
				case self::TYPE_GROUP:
					if ($acl->get('author_id') == self::TYPE_EVERYONE)
						return __('Alle groepen');

					return $this->db->query_value('
							SELECT
								name
							FROM
								forum_group
							WHERE
								id = ' . intval($acl->get('author_id')));
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
		public function get_acl_type(DataIterForumPermission $acl)
		{
			switch ($acl->get('author_type'))
			{
				case self::TYPE_EVERYONE:
					return __('Iedereen');
				
				case self::TYPE_PERSON:
					return __('Lid');
				
				case self::TYPE_COMMITTEE:
					return __('Commissie');
				
				case self::TYPE_GROUP:
					return __('Groep');
				
				default:
					return __('Onbekend');
			}
		}

		/**
		  * Insert a thread
		  * @iter an #DataIter representing a thread
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		public function insert_thread(DataIterForumThread $thread, DataIterForumMessage $message)
		{
			$this->db->beginTransaction();

			$id = $this->_insert('forum_threads', $thread, true);

			$thread->set_id($id);

			$message['thread_id'] = $thread->get_id();

			$this->insert_message($message);

			$this->db->commit();

			return $id;
		}
		
		/** 
		  * Returns a #DataIter of the special forum ('weblog','news', or 'poll')
		  * @param $name the name of the special forum
		  *
		  * @return a #DataIterForum of the specified forum or throws an exception
		  */
		public function get_special_forum($name)
		{
			$specials = array('poll', 'news', 'weblog');
			
			if (!in_array($name, $specials))
				throw new InvalidArugmentException('Unknown special forum');

			$value = get_config_value($name, null);

			if ($value === null)
				throw new NotFoundException('No value configured for this special forum');
				
			return $this->get_iter($value);
		}

		/**
		  * Get visit info
		  * @forumid the id of the forum to get visit info for
		  * @member_id the id of the member to get visit info for
		  *
		  * @result a #DataIter or null if no visit info could be found
		  */
		private function _get_visit_info_real($forum_id, $member_id)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_visits
					WHERE
						forum_id = ' . intval($forum_id) . ' AND
						lid_id = ' . intval($member_id));
			
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get visit info. Creates a visit info entry if none exists
		  * yet
		  * @forumid the id of the forum to get visit info for
		  * @member_id the id of the member to get visit info for
		  *
		  * @result a #DataIter
		  */
		public function get_visit_info($forum_id, $member_id)
		{
			$iter = $this->_get_visit_info_real($forum_id, $member_id);
			
			if (!$iter)
			{
				$this->db->insert('forum_visits', [
					'forum_id' => intval($forum_id),
					'lid_id' => intval($member_id)
				]);

				$iter = $this->_get_visit_info_real($forum_id, $member_id);
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
		public function forum_unread(DataIterForum $forum)
		{
			/* Returns whether the forum contains unread messages.
			   The function checks if there are any new messages
			   since the last visit to the forum (forum_visits). And if so if the
			   messages have been read since the last visit (forum_sessionreads) */
			$member_data = logged_in();
			
			if (!$member_data)
				return;
			
			/* Get visit info */
			$visit = $this->get_visit_info($forum['id'], $member_data['id']);
			
			/* Check the number of unread threads in the forum by last visit */
			$num_visit_unread = $this->db->query_value('
					SELECT
						COUNT(DISTINCT forum_threads.id)
					FROM
						forum_threads
					LEFT JOIN forum_messages ON 
						(forum_threads.id = forum_messages.thread_id)
					WHERE
						forum_threads.forum_id = ' . intval($forum['id']) . ' AND 
						forum_messages.date > TIMESTAMP \'' . $visit['lastvisit'] . '\'');

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
						lid_id = ' . intval($member_data['id']) . ' AND
						forum_id = ' . intval($forum['id']));

			return $num_visit_unread > $num_session_read;
		}
		
		/**
		  * Mark a thread to be read
		  * @thread_id the id of the thread to mark as read
		  */
		public function mark_read(DataIterMember $member, DataIterForumThread $thread)
		{
			if ($member === null)
				return;

			$val = $this->db->query_value('
					SELECT
						1
					FROM
						forum_sessionreads
					WHERE
						thread_id = ' . intval($thread['id']) . ' AND
						lid_id = ' . intval($member['id']) . '
					LIMIT
						1');
			
			if ($val)
				return;
				
			return $this->db->insert('forum_sessionreads', [
				'thread_id' => intval($thread['id']),
				'lid_id' => intval($member['id']),
				'forum_id' => intval($thread['forum'])
			]);
		}
		
		/**
		  * Mark a thread as unread
		  * @threadid the id of the thread to mark as unread
		  */
		public function mark_unread(DataIterForumThread $thread)
		{
			/* Deletes all session reads for this thread so that
			   the thread becomes unread for everyone again */
			return $this->db->delete('forum_sessionreads', 'thread_id = ' . intval($thread['id']));
		}
		
		/**
		  * Update the last time a user has visited a forum
		  * @param $forum optional; the iter of the forum the user has visited 
		  * or null to update all the forums (defaults to null)
		  */
		public function update_last_visit(DataIterMember $member, DataIterForum $forum = null)
		{
			if ($member === null)
				return;

			if ($forum === null) {
				$forums = $this->get();
				
				foreach ($forums as $forum)
					$this->update_last_visit($member, $forum);
			} else {
				/* Set last visit date to the session date, and set the session date to null
				   for all visits that are older than 15 minutes */
				$this->db->update('forum_visits', array(
					'lastvisit' => new DatabaseLiteral('sessiondate'),
					'sessiondate' => null),
					'lid_id = ' . intval($member['id']) . '
					 AND forum_id = ' . intval($forum['id']) . '
					 AND sessiondate + INTERVAL \'15 minutes\' < CURRENT_TIMESTAMP');
				
				if ($this->db->get_affected_rows()) {
					/* Delete all obsolete session reads */
					$this->db->delete('forum_sessionreads',
						'lid_id = ' . intval($member['id']) . '
						 AND forum_id = ' . intval($forum['id']));
				}
			}
		}
		
		/**
		  * Set a forum to be read
		  * @forum_id the id of the forum to set to be read
		  */
		public function set_forum_session_read(DataIterMember $member, DataIterForum $forum)
		{
			if ($member === null || $forum === null)
				return;

			$visit = $this->get_visit_info($forum, $member);
			$this->update_last_visit($member, $forum);
			
			$visit->set('sessiondate', new DatabaseLiteral('CURRENT_TIMESTAMP'));

			return $this->db->update('forum_visits',
				$visit->get_changed_values(),
				'lid_id = ' . intval($member['id']) . ' AND forum_id = ' . intval($forum['id']));
		}
		
		/**
		  * Insert a message
		  * @iter a #DataIter representing a message
		  *
		  * @result the id of the message if succesful, null otherwise
		  */
		public function insert_message(DataIterForumMessage $iter)
		{
			/* Mark the thread as unread */
			$this->mark_unread($iter['thread']);

			return $this->_insert('forum_messages', $iter, true);
		}
		
		/**
		  * Delete a forum. This function will also delete any
		  * threads and replies in this forum as well as any 
		  * permissions associated with it
		  * @iter a #DataIter representing a forum
		  */
		public function delete(DataIter $iter)
		{
			parent::delete($iter);
			
			$id = intval($iter->get('id'));

			// Todo: the constraints on the database should also have this effect,
			// maybe it is worth just falling back on those, and don't bothering
			// with it here.

			/* delete all messages */
			$threads = $this->db->query('SELECT id FROM forum_threads WHERE forum_id = ' . $id);
			
			if ($threads) {
				foreach ($threads as $thread)
					$this->db->delete('forum_messages', 'thread_id = ' . intval($thread['id']));
			}
			
			$this->db->delete('forum_threads', 'forum_id = ' . $id);
			
			/* delete acl */
			$this->db->delete('forum_acl', 'forum_id = ' . $id);
			
			/* last visits */
			$this->db->delete('forum_lastvisits', 'forum_id = ' . $id);
			
			/* session read */
			$this->db->delete('forum_sessionreads', 'forum_id = ' . $id);
		}
		
		/**
		  * Delete a permission
		  * @iter a #DataIter representing a permission
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		public function delete_acl(DataIterForumPermission $iter)
		{
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
		public function insert_acl(DataIterForumPermission $iter)
		{
			$row = $this->db->query_first('
					SELECT
						*
					FROM
						forum_acl
					WHERE
						forum_id = ' . intval($iter->get('forum_id')) . ' AND
						author_type = ' . intval($iter->get('author_type')) . ' AND
						author_id = ' . intval($iter->get('author_id')));

			if ($row) {
				/* Acl already exist, overwrite perms */
				$acl = $this->_row_to_iter($row, 'DataIterForumPermission');
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
		public function update_acl(DataIterForumPermission $iter)
		{
			return $this->_update('forum_acl', $iter);
		}
		
		/**
		  * Insert a group
		  * @iter a #DataIter representing a group
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		public function insert_group(DataIterForumGroup $iter)
		{
			return $this->_insert('forum_group', $iter);
		}
		
		/**
		  * Delete a group. This function will also delete any 
		  * members and permissions associated with the group
		  * @iter a #DataIter representing a group
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		public function delete_group(DataIterForumGroup $iter)
		{
			$result = $this->_delete('forum_group', $iter);
			$this->db->delete('forum_group_member', 'group_id = ' . intval($iter->get('id')));
			$this->db->delete('forum_acl', sprintf('author_type = %d AND author_id = %d', self::TYPE_GROUP, $iter->get('id')));
			return $result;
		}
		
		/**
		  * Update a group
		  * @iter a #DataIter representing a group
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		public function update_group(DataIterForumGroup $iter)
		{
			return $this->_update('forum_group', $iter);
		}
		
		/**
		  * Insert a group member
		  * @iter a #DataIter representing a group member
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		public function insert_group_member(DataIterForumGroupMember $iter)
		{
			return $this->_insert('forum_group_member', $iter);
		}
		
		/**
		  * Delete a group member
		  * @iter a #DataIter representing a group member
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		public function delete_group_member(DataIterForumGroupMember $iter)
		{
			return $this->_delete('forum_group_member', $iter);
		}
		
		/**
		  * Perform actions when a commissie has been deleted. This
		  * function will delete any associated permission or group
		  * member with this commissie
		  * @iter a #DataIter representing a commissie
		  */
		public function commissie_deleted(DataIterCommissie $iter)
		{	
			// Todo: again, should we bother doing the work the database constraints
			// are already doing for us?

			$this->db->delete('forum_acl', sprintf('author_type = %d AND author_id = %d',
				self::TYPE_COMMITTEE, $iter->get('id')));

			$this->db->delete('forum_group_member', sprintf('author_type = %d AND author_id = ',
				self::TYPE_COMMITTEE, $iter->get('id')));
		}
		
		/**
		  * Delete a thread. This function will delete any replies
		  * in the thread as well
		  * @iter a #DataIter representing a thread
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		public function delete_thread(DataIterForumThread $iter)
		{
			$this->db->beginTransaction();

			$this->_delete('forum_threads', $iter);
			
			/* Delete all replies */
			$this->db->delete('forum_messages', sprintf('thread_id = %d', $iter['id']));

			$this->db->commit();

			return true;
		}
		
		/**
		  * Update a thread
		  * @iter a #DataIter representing a thread
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		public function update_thread(DataIterForumThread $iter)
		{
			return $this->_update('forum_threads', $iter);
		}

		public function move_thread(DataIterForumThread $thread, DataIterForum $target_forum)
		{
			$thread['forum_id'] = $target_forum['id'];
			return $this->update_thread($thread);
		}
		
		/**
		  * Delete a message
		  * @iter a #DataIter representing a message
		  *
		  * @result true if the delete was succesful, the id of the 
		  * thread if the last message in a thread was removed, false
		  * otherwise
		  */
		public function delete_message(DataIterForumMessage $iter)
		{
			$ret = $this->_delete('forum_messages', $iter);
			$thread = $this->get_thread($iter->get('thread_id'));

			/* Check if last message was removed */
			if ($thread && $thread->get_num_messages() == 0)
			{
				$ret = intval($thread->get('forum_id'));
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
		public function update_message(DataIterForumMessage $iter) 
		{
			return $this->_update('forum_messages', $iter);
		}
		
		/**
		  * Update a forum header
		  * @iter a #DataIter representing a forum header
		  *
		  * @result true if the update was succesful, false otherwise
		  */
		public function update_header(DataIterForumHeader $iter)
		{
			return $this->_update('forum_header', $iter);
		}
		
		/**
		  * Insert a forum header
		  * @iter a #DataIter representing a forum header
		  *
		  * @result true if the insert was succesful, false otherwise
		  */
		public function insert_header(DataIterForumHeader $iter)
		{
			return $this->_insert('forum_header', $iter);
		}
		
		/**
		  * Delete a forum header
		  * @iter a #DataIter representing a forum header
		  *
		  * @result true if the delete was succesful, false otherwise
		  */
		public function delete_header(DataIterForumHeader $iter)
		{
			return $this->_delete('forum_header', $iter);
		}
	}

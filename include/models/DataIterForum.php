<?php
	require_once('login.php');

	/**
	  * A class implementing forum data
	  */
	class DataIterForum extends DataIter {
		/**
		  * Create a new DataForumIter
		  * @id the id of the iter
		  * @data the data of the iter (a hashtable)
		  */
		function DataIterForum($model, $id, $data) {
			$this->model = get_model('DataModelForum'); /** The model the iter belongs to */
			$this->db = $this->model->db;
			parent::DataIter($this->model,$id,$data);
		}
								
		/*
		 * Forum functions
		 */
		
		/**
		  * Get the number of threads in a forum
		  * @iter a #DataIter representing a forum
		  *
		  * @result the number of threads in the forum
		  */
		function get_num_threads() {
			return $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_threads
					WHERE
						forum = ' . intval($this->get('id')));
		}
		
		/**
		  * Get the number of pages in a forum
		  * @forum a #DataIter representing a forum
		  *
		  * @result the number of pages in the forum
		  */
		function get_num_forum_pages() {
			return intval(ceil($this->get_num_threads() / floatval($this->model->threads_per_page)));
		}
		
		
		/**
		  * Get the number of messages in the forum
		  *
		  * @result the number of messages in the forum
		  */		
		function get_num_forum_messages() {
			return $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_threads,
						forum_messages
					WHERE
						forum_messages.thread = forum_threads.id AND
						forum_threads.forum = ' . intval($this->get('id')));
		}

		
		/**
		  * Get permissions for a certain forum
		  *
		  * @result an array of #DataIter
		  */
		function get_rights() {
			$rows = $this->db->query('
					SELECT
						*
					FROM
						forum_acl
					WHERE
						forumid = ' . intval($this->get('id')) . '
					ORDER BY
						id');

			return $this->model->_rows_to_iters($rows);
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
		function get_last_thread($offset = -1, $limit = -1, $last_reply = true) {
			$rows = $this->db->query('
					SELECT
						forum_threads.*,

						forum_messages.date AS last_date,
						forum_messages.id AS last_id,
						to_char(forum_messages.date, \'DD-MM-YYYY, HH24:MI\') AS datum,

						forum_messages.author AS last_author,
						forum_messages.author_type AS last_author_type,
						date_part(\'day\', CURRENT_TIMESTAMP - forum_threads.date) AS since
					FROM
						forum_threads
					LEFT JOIN forum_messages ON (forum_messages.thread = forum_threads.id AND
						forum_messages.id IN (SELECT ' . ($last_reply ? 'MAX' : 'MIN') . '(forum_messages.id) FROM forum_threads, forum_messages WHERE forum_threads.forum = ' . intval($this->get('id')) . ' AND forum_messages.thread = forum_threads.id GROUP BY forum_messages.thread))
					WHERE
						forum = ' . intval($this->get('id')) . '
					ORDER BY
						last_date DESC' .
					($offset != -1 ? (' OFFSET ' . intval($offset)) : '') .
					' LIMIT ' . ($limit != -1 ? intval($limit) : '1'));

			if ($rows && $limit == -1) {
				if (count($rows) > 0)
					return $this->model->_row_to_iter($rows[0]);
				else
					return null;
			}

			return $this->model->_rows_to_iters($rows);
		}
		
		/**
		  * returns the last created thread in the forum
		  *
		  * @result the last thread as DataIterForum, or
		  * null if there is no such thread
		  */
		function get_newest_thread() {
			$row = $this->db->query('
					SELECT
						*, 
						date_part(\'day\', CURRENT_TIMESTAMP - forum_threads.date) AS since
					FROM
						forum_threads
					WHERE 
						forum = ' . intval($this->get('id')) .'
					ORDER BY 
						id DESC
					LIMIT 
						1');

			if ($row)
				return $this->model->_row_to_iter($row[0]);

			return null;
		}
			
		/*
		 * Thread functions
		 */
		
		
		/**
		  * Get the number of replies in a thread
		  * @iter a #DataIter representing a thread
		  *
		  * @result the number of replies in the thread
		  */
		function get_num_messages() {
			return $this->db->query_value('
					SELECT
						COUNT(*)
					FROM
						forum_messages
					WHERE
						forum_messages.thread = ' . intval($this->get('id')));
		}
		
		/**
		  * Get the number of pages in a thread
		  * @iter a #DataIter representing a thread
		  *
		  * @result the number of pages in the thread
		  */
		function get_num_thread_pages() {
			return intval(ceil($this->get_num_messages() / floatval($this->model->messages_per_page)));
		}
		
		/**
		  * Get the first (initial) thread message
		  *
		  * @result a #DataIter
		  */
		function get_first_message() {
			if (!$this->model->check_acl($this->get('forum'), ACL_READ))
				return null;
			
			$row = $this->db->query_first('
					SELECT
						*,
						to_char(date, \'DD-MM-YYYY, HH24:MI\') AS datum
					FROM
						forum_messages
					WHERE
						thread = ' . intval($this->get('id')) . '
					ORDER BY
						id
					LIMIT 1');
			
			return $this->model->_row_to_iter($row);
		}
		
		/**
		  * Return whether this message is the first message in the thread
		  *
		  * @result true if the message is the first message in the thread
		  */
		function is_first_message() {
			$thread = $this->model->get_thread($this->thread);
			$first = $thread->get_first_message();
			
			return $first->id == $this->id;
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
		function get_messages($page, &$max) {
			if (!$this->model->check_acl($this->get('forum'), ACL_READ))
				return null;

			$max = $this->get_num_thread_pages() - 1;
			$page = min($max, max(0, intval($page)));

			$this->model->current_page = $page;

			$rows = $this->db->query('
					SELECT
						*,
						to_char(date, \'DD-MM-YYYY, HH24:MI\') AS datum
					FROM
						forum_messages
					WHERE
						thread = ' . intval($this->get('id')) . '
					ORDER BY
						id
					OFFSET
						' . ($page * $this->model->messages_per_page) . '
					LIMIT ' . $this->model->messages_per_page);

			return $this->model->_rows_to_iters($rows);
		}
		
		/** 
		  * Check whether the currently logged in user can edit this
		  * message
		  *
		  * @result true if the message can be edited by the currently
		  * logged in user, false otherwise
		  */
		function editable() {
			$info = logged_in();
			
			if (!$info)
				return false;
			
			if (member_in_commissie(COMMISSIE_BESTUUR))
				return true;
			
			$type = intval($this->author_type);
			
			switch ($type) {
				case 1: /* Person */
					return ($this->author == $info['id']);
				break;
				case 2: /* Commissie */
					return member_in_commissie($this->author);
				break;
			}
		}
	}
?>

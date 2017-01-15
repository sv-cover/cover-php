<?php
	require_once 'include/data/DataModel.php';
	require_once 'include/models/DataModelForum.php';

	class DataIterPollOption extends DataIter
	{
		static public function fields()
		{
			return [
				'id',
				'pollid',
				'optie',
				'stemmen',
			];
		}

		public $thread;

		public function percentage()
		{
			return 100 * $this['stemmen'] / max($this->thread->total_votes, 1);
		}
	}

	class DataIterPoll extends DataIterForumThread
	{
		public function member_has_voted(DataIterMember $member = null)
		{
			return get_model('DataModelPoll')->member_has_voted($this, $member);
		}

		public function new_poll_option()
		{
			return new DataIterPollOption(null, -1, [
				'optie' => '',
				'pollid' => $this['id'],
				'stemmen' => 0
			]);
		}

		public function get_options()
		{
			return get_model('DataModelPoll')->get_options($this);
		}

		public function get_total_votes()
		{
			return array_reduce($this['options'], function($carry, $option) { return $carry + $option['stemmen']; }, 0);
		}
	}

	/**
	  * A class implementing poll data
	  */
	class DataModelPoll extends DataModel
	{
		public function __construct($db)
		{
			parent::__construct($db);
		}

		public function new_poll(DataIterForum $forum)
		{
			$forum_model = get_model('DataModelForum');

			return new DataIterPoll($forum_model, null, [
				'forum' => $forum['id'],
				'author' => null,
				'subject' => null,
				'date' => date('Y-m-d H:i:s'),
				'author_type' => null,
				'poll' => 1
			]);
		}

		public function from_thread(DataIterForumThread $thread)
		{
			return DataIterPoll::from_iter($thread);
		}
		
		public function insert_poll(DataIterPoll $thread, DataIterForumMessage $message, array $options)
		{
			$forum_model = get_model('DataModelForum');

			$this->db->beginTransaction();

			$forum_model->insert_thread($thread, $message);

			foreach ($options as $option) {
				$option['pollid'] = $thread->get_id();
				$this->_insert_poll_option($option);
			}

			$this->db->commit();

			return true;
		}

		private function _insert_poll_option($iter)
		{
			$this->_insert('pollopties', $iter);
		}
		
		public function can_create_new_poll(&$days = null)
		{
			$current_user = logged_in();

			// Not logged in? You can't create a poll
			if (!$current_user)
				return false;

			// EASY? Yes, you can create a poll
			if (member_in_commissie(COMMISSIE_EASY))
				return true;

			// Otherwise look at the last poll and see how many days
			// have passed since it has been posted.
			$prev_thread = $this->get_latest_poll();

			if (!$prev_thread) return true;

			$thread = get_model('DataModelForum')->get_thread($prev_thread->get('id'));

			// Threshold is 7 days by default, unless you where the author of the previous poll
			$threshold = $thread->get('author') == $current_user['id'] ? 14 : 7;

			$days = $threshold - $thread->get('since');

			return $days <= 0;
		}

		public function get_latest_poll()
		{
			$config_model = get_model('DataModelConfiguratie');
			$forum_model = get_model('DataModelForum');
			
			$id = $config_model->get_value('poll_forum');
			
			/* Get last thread */
			if (!$id) return null;
			
			$forum = $forum_model->get_iter($id);
				
			if (!$forum) return null;
			
			$thread = $forum->get_newest_thread();

			if (!$thread)
				return null;

			return DataIterPoll::from_iter($thread);
		}

		public function get_options(DataIterPoll $poll)
		{
			$rows = $this->db->query('SELECT * 
					FROM pollopties
					WHERE pollid = ' . intval($poll->get_id()) . '
					ORDER BY id ASC');
			
			return $this->_rows_to_iters($rows, 'DataIterPollOption');
		}
		
		public function vote($id)
		{
			$row = $this->db->query_first('SELECT *
					FROM pollopties
					WHERE id = ' . intval($id));
			
			if (!$row)
				return false;
			
			$iter = $this->_row_to_iter($row);
			$iter->set('stemmen', $iter->get('stemmen') + 1);
			
			$this->db->update('pollopties',	
					$iter->get_changed_values(), 
					$this->_id_string($iter->get_id()), 
					$iter->get_literals());

			if (!($member_data = logged_in()))
				return true;
			
			$iter = new DataIter($this, -1, 
					array(	'lid' => $member_data['id'],
						'poll' => $iter->get('pollid')));

			$this->db->insert('pollvoters', $iter->data, $iter->get_literals());
		}

		public function voted($iter) {
			if (!($member_data = logged_in()))
				return true;
			
			$config_model = get_model('DataModelConfiguratie');
			$id = $config_model->get_value('poll_forum');
			
			// If this poll is in the Polls forum on the forum, it is
			// closed as soon as there is a newer poll.
			if ($iter->get('forum') == $id) {
				try {
					$thread = $this->get_latest_poll();
						
					if ($thread->get('id') != $iter->get('id'))
						return true;
				} catch (DataIterNotFoundException $e) {
					// Oh shit the configuration is fucked up and we cannot find
					// the polls forum. Well, never mind, not that important.
				}
			}
			
			$row = $this->db->query_first('
					SELECT 
						* 
					FROM 
						pollvoters
					WHERE 
						lid = ' . intval($member_data['id']) . ' AND 
						poll = ' . $iter->get('id'));
			
			return $row !== null;
		}

		public function member_has_voted(DataIter $poll, DataIterMember $member = null)
		{
			$config_model = get_model('DataModelConfiguratie');
			$id = $config_model->get_value('poll_forum');
			
			// If this poll is in the Polls forum on the forum, it is
			// closed as soon as there is a newer poll.
			if ($poll->get('forum') == $id) {
				try {
					$thread = $this->get_latest_poll();
						
					if ($thread->get('id') != $poll->get('id'))
						return true;
				} catch (DataIterNotFoundException $e) {
					// Oh shit the configuration is fucked up and we cannot find
					// the polls forum. Well, never mind, not that important.
				}
			}
			
			$row = $this->db->query_first('
					SELECT 
						* 
					FROM 
						pollvoters
					WHERE 
						lid = ' . intval($member['id']) . ' AND 
						poll = ' . $poll['id']);
			
			return $row !== null;
		}
	}

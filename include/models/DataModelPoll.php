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

		public function get_poll()
		{
			return get_model('DataModelPoll')->get_iter($this['pollid']);
		}

		public function get_percentage()
		{
			return 100 * $this['stemmen'] / max($this['poll']['total_votes'], 1);
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
			], [
				'poll' => $this
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
				'forum_id' => $forum['id'],
				'author_id' => null,
				'author_type' => null,
				'subject' => null,
				'date' => new DateTime(),
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

			$thread = get_model('DataModelForum')->get_thread($prev_thread['id']);

			// Threshold is 7 days by default, unless you where the author of the previous poll
			$threshold = $thread['author_type'] == DataModelForum::TYPE_PERSON
			             && $thread['author_id'] == $current_user['id']
			             ? 14 : 7;

			$days = $threshold - $thread['since'];

			return $days <= 0;
		}

		public function get_latest_poll()
		{
			$forum_model = get_model('DataModelForum');
			
			$id = get_config_value('poll_forum', null);
			
			if (!$id) return null;
			
			$forum = $forum_model->get_iter($id);
				
			if (!$forum) return null;
			
			$thread = $forum->get_newest_thread();

			if (!$thread)
				return null;

			return DataIterPoll::from_iter($thread);
		}

		public function get_iter($id)
		{
			$thread = get_model('DataModelForum')->get_thread($id);

			return DataIterPoll::from_iter($thread);
		}

		public function get_options(DataIterPoll $poll)
		{
			$rows = $this->db->query('SELECT * 
					FROM pollopties
					WHERE pollid = ' . intval($poll['id']) . '
					ORDER BY id ASC');
			
			return $this->_rows_to_iters($rows, 'DataIterPollOption', compact('poll'));
		}
		
		public function vote(DataIterPollOption $option, DataIterMember $member = null)
		{
			$this->db->query(sprintf("UPDATE pollopties SET stemmen = stemmen + 1 WHERE id = %d", $option->get_id()));

			$success = $this->db->get_affected_rows() === 1;

			if ($member !== null) {
				$this->db->insert('pollvoters', [
					'lid' => $member['id'],
					'poll' => $option['pollid']
				]);
			}

			return $success;
		}

		public function voted(DataIterForumThread $iter) {
			if (!($member_data = logged_in()))
				return true;
			
			$config_model = get_model('DataModelConfiguratie');
			$id = $config_model->get_value('poll_forum');
			
			// If this poll is in the Polls forum on the forum, it is
			// closed as soon as there is a newer poll.
			if ($iter['forum_id'] == $id) {
				try {
					$thread = $this->get_latest_poll();
						
					if ($thread['id'] != $iter['id'])
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
						poll = ' . $iter['id']);
			
			return $row !== null;
		}

		public function member_has_voted(DataIter $poll, DataIterMember $member = null)
		{
			$id = get_config_value('poll_forum');
			
			// If this poll is in the Polls forum on the forum, it is
			// closed as soon as there is a newer poll.
			if ($poll['forum_id'] == $id) {
				try {
					$thread = $this->get_latest_poll();
						
					if ($thread['id'] != $poll['id'])
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

<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing poll data
	  */
	class DataModelPoll extends DataModel {
		public function DataModelPoll($db) {
			parent::DataModel($db);
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
			$poll_model = get_model('DataModelPoll');
			$forum_model = get_model('DataModelForum');
			$config_model = get_model('DataModelConfiguratie');
		
			$id = $config_model->get_value('poll_forum');
			
			/* Get last thread */
			if (!$id) return null;
			
			$forum = $forum_model->get_iter($id);
				
			if (!$forum) return null;
			
			return $forum->get_newest_thread();
		}

		public function get_votes($id)
		{
			$rows = $this->db->query('SELECT * 
					FROM pollopties
					WHERE pollid = ' . intval($id) . '
					ORDER BY id ASC');
			
			return $this->_rows_to_iters($rows);
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

		protected function insert_optie($iter) {
			$this->db->insert('pollopties', $iter->data, 
					$iter->get_literals());
		}
		
		public function voted($iter) {
			if (!($member_data = logged_in()))
				return true;
			
			$config_model = get_model('DataModelConfiguratie');
			$id = $config_model->get_value('poll_forum');
			
			if ($iter->get('forum') == $id) {
				$forum_model = get_model('DataModelForum');
				$forum = $forum_model->get_iter($id);
				
				if ($forum) {
					$thread = $forum->get_last_thread();
					
					if ($thread->get('id') != $iter->get('id'))
						return true;
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
			
			if ($row === null)
				return false;
			else
				return true;
		}
	}

<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing poll data
	  */
	class DataModelPoll extends DataModel {
		function DataModelPoll($db) {
			parent::DataModel($db, 'polls');
		}
		
		function get_for_commissie($id) {
			$row = $this->db->query_first("SELECT *,
					date_part('day', CURRENT_TIMESTAMP - date) AS sincelast
					FROM polls
					WHERE commissieid = " . intval($id) . '
					ORDER BY id DESC
					LIMIT 1');
	
			return $this->_row_to_iter($row);
		}
		
		function get_votes($id) {
			$rows = $this->db->query('SELECT * 
					FROM pollopties
					WHERE pollid = ' . intval($id) . '
					ORDER BY id ASC');
			
			return $this->_rows_to_iters($rows);
		}
		
		function vote($id) {
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
		
		function insert($iter, $getid = false) {
			$prev = $this->get_for_commissie($iter->get('commissieid'));

			$this->db->insert($this->table, $iter->data, 
					$iter->get_literals());
			
			if ($getid)
				$lastid = $this->db->get_last_insert_id();
			else
				$lastid = -1;

			/* Remove all the voters data for the previous poll
			   for this commissie */
			$this->db->delete('pollvoters', 'poll = ' . $prev->get_id());
			
			return $lastid;
		}

		function insert_optie($iter) {
			$this->db->insert('pollopties', $iter->data, 
					$iter->get_literals());
		}
		
		function voted($iter) {
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
?>

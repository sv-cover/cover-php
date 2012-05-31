<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing taken data
	  */
	class DataModelTaken extends DataModel {
		function DataModelTaken($db) {
			parent::DataModel($db, 'taken');
		}
		
		/**
		  * Get open tasks
		  *
		  * @result an array of open task #DataIter
		  * (afgehandeld == NULL)
		  */
		function get() {
			$rows = $this->db->query('SELECT *
					FROM taken
					WHERE afgehandeld IS NULL
					ORDER BY prioriteit, toegewezen DESC, id');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get task data for a given member. This includes subscription
		  * information in the subcribed field
		  * @id the task id
		  * @memberid the member id
		  *
		  * @result a #DataIter
		  */
		function get_iter($id, $memberid) {
			$row = $this->db->query_first('SELECT *, taken_subscribe.taakid AS subscribed
					FROM taken
					LEFT JOIN taken_subscribe ON (taken.id = taken_subscribe.taakid AND
					taken_subscribe.lidid = ' . intval($memberid) . ')
					WHERE id = ' . intval($id));
			
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get all closed tasks within a certain period of time
		  * @daysago the number of days ago in which the task was closed
		  *
		  * @result an array of closed task #DataIter (afgehandeld
		  * != NULL)
		  */
		function get_done($daysago) {
			$rows = $this->db->query("SELECT *
					FROM taken
					WHERE date_part('day', CURRENT_TIMESTAMP - afgehandeld) <= $daysago
					ORDER BY id");
			
			return $this->_rows_to_iters($rows);		
		}
		
		/**
		  * Get all priorities
		  *
		  * @result an associative array with 
		  * priorty_nr => priority_name
		  */
		function get_prioriteiten() {
			return array(0 => 'Kri', 1 => 'Hoog', 2 => 'Nor', 3 => 'Min', 4 => 'Enh');
		}
		
		/**
		  * Get members that are subscribed to a certain task
		  * @taakid the task id
		  * @new whether or not the task is a new task
		  *
		  * @result an array of #DataIter
		  */
		function get_subscribers($taakid, $new) {
			$rows = $this->db->query('SELECT DISTINCT lidid 
					FROM taken_subscribe
					WHERE taakid = ' . $taakid . ' OR
					taakid = -1' . ($new ? ' OR taakid = 0' : ''));

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Add subscriber to a task
		  * @iter the #DataIter containing the subscription data
		  *
		  * @result true of insert was successful, false otherwise
		  */
		function insert_subscribe($iter) {
			return $this->db->insert('taken_subscribe', $iter->data, 
					$iter->get_literals());
		}
		
		/**
		  * Delete subscriber from a task
		  * @iter the #DataIter containing the subscription data
		  *
		  * @result true of delete was successful, false otherwise
		  */
		function delete_subscribe($iter) {
			return $this->db->delete('taken_subscribe', 'lidid = ' . intval($iter->get('lidid')) . ' AND taakid = ' . intval($iter->get('taakid')));
		}
	}
?>

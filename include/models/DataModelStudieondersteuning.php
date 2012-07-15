<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing studieondersteuning data
	  */
	class DataModelStudieOndersteuning extends DataModel {
		function DataModelStudieOndersteuning($db) {
			parent::DataModel($db, 'so_documenten');
		}
		
		/**
		  * Get all the courses for a certain year
		  * @year the year to get the courses for (1, 2, 3, 4, 5 or 0
		  * for others)
		  *
		  * @result an array of #DataIter
		  */
		function get_for_year($year) {
			$rows = $this->db->query('SELECT * 
						FROM so_vakken
						WHERE jaar = ' . intval($year) . '
						ORDER BY naam');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get all the documents for a course
		  * @id the course id to get the documents for
		  *
		  * @result an array of #DataIter
		  */
		function get_for_vak($id) {
			$rows = $this->db->query('SELECT *
						FROM so_documenten
						WHERE vak = ' . intval($id) . ' AND
						checked = 1
						ORDER BY titel');

			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Get the number of documents for a certain course
		  * @id the course id to get the number of documents for
		  *
		  * @result the number of documents
		  */
		function get_num_documenten($id) {
			$num = $this->db->query_value('SELECT COUNT(*)
						FROM so_documenten
						WHERE vak = ' . intval($id) . ' AND
						checked = 1');
			
			return $num;
		}
		
		/**
		  * Get a course
		  * @id the course id
		  *
		  * @result a #DataIter
		  */
		function get_vak($id) {
			$row = $this->db->query_first('SELECT *
					FROM so_vakken
					WHERE id = ' . intval($id));
	
			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get all courses
		  *
		  * @result an array of #DataIter
		  */
		function get_vakken() {
			$rows = $this->db->query('SELECT *
						FROM so_vakken
						ORDER BY jaar, naam');
			
			return $this->_rows_to_iters($rows);
		}
		
		/**
		  * Insert a new document
		  * @iter the #DataIter representing a document to insert
		  *
		  * @result the id of the inserted document
		  */
		function insert_document($iter) {
			$this->db->insert('so_documenten', $iter->data,
					$iter->get_literals());
	
			return $this->db->get_last_insert_id();
		}
		
		/**
		  * Get all documents which need moderation
		  *
		  * @result an array of #DataIter
		  */
		function get_moderates() {
			$rows = $this->db->query('SELECT so_documenten.*, 
					so_vakken.naam AS vak 
					FROM so_documenten, so_vakken 
					WHERE so_documenten.vak = so_vakken.id AND 
					so_documenten.checked = 0');

			return $this->_rows_to_iters($rows);
		}
	}
?>

<?php
	require_once('DataIter.php');

	class NotFoundException extends Exception {
		//
	}

	class DataIterNotFoundException extends NotFoundException
	{
		public function __construct($id)
		{
			parent::__construct(sprintf('DataIter with id "%d" was not found', $id));
		}
	}

	/**
	  * This class provides a base class for accessing data. This class can
	  * be used for very simple one-to-one, model-to-table type mappings.
	  * More complex models should inherit from this base class and implement
	  * their own insert, update, delete, get and get_iter functions 
	  */
	class DataModel {
		var $db = null; /** The database backend */
		var $table = null; /** The table to model */
		var $dataiter = 'DataIter';
		
		/**
		  * Create a new DataModel
		  * @db the database backend to use (#DatabasePgsql or #DatabaseMysql)
		  * @table the table to model 
		  * @id optional; the field name to use as unique id
		  */
		function DataModel($db, $table = null, $id = 'id') {
			$this->db = $db;
			$this->table = $table;
			$this->id = $id;
		}

		/**
		  * Insert a new row (syncs with the database backend). This
		  * is a convenient function to be used by descendents of
		  * #DataModel
		  * @table the table to insert the iter in
		  * @iter a #DataIter representing the row
		  * @getid optional; whether to get the last insert id
		  *
		  * @result if getid is true the last insert id is returned, -1 
		  * otherwise
		  */		
		function _insert($table, $iter, $getid = false) {
			if (!$this->db)
				return false;
			
			$this->db->insert($table, $iter->data, $iter->get_literals());
			
			if ($getid)
				return $this->db->get_last_insert_id();
			else
				return -1;
		}
		
		/**
		  * Insert a new row (syncs with the database backend)
		  * @iter a #DataIter representing the row
		  * @getid optional; whether to get the last insert id
		  *
		  * @result if getid is true the last insert id is returned, -1 
		  * otherwise
		  */
		function insert($iter, $getid = false) {
			if (!$this->table)
				return false;

			return $this->_insert($this->table, $iter, $getid);
		}
		
		/**
		  * Generate a id = value string
		  * @value the id value
		  *
		  * @result a id = value string
		  */
		protected function _id_string($value) {
			$result = $this->table . '.' . $this->id . ' = ';
			
			if ($this->id == 'id')
				return $result . intval($value);
			else
				return $result . "'" . $this->escape_string($value) . "'";
		}

		/**
		  * Update a row (sync changes in the database backend). 
		  * Convenient function for descendents of #DataModel
		  * @table the table to update the iter in
		  * @iter a #DataIter representing the row that needs updating
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		function _update($table, $iter) {
			if (!$this->db)
				return false;

			return $this->db->update($table, 
					$iter->get_changed_values(), 
					$this->_id_string($iter->get_id()), 
					$iter->get_literals());
		}
		
		/**
		  * Update a row (sync changes in the database backend)
		  * @iter a #DataIter representing the row that needs updating
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		function update($iter) {
			if (!$this->table)
				return false;

			return $this->_update($this->table, $iter);
		}

		/**
		  * Delete a row (syncs with the database backend). Convenient
		  * function for descendents of #DataModel
		  * @table the table to delete from
		  * @iter a #DataIter representing the row to be deleted
		  *
		  * @result true if the deletion was successful, false otherwise
		  */		
		protected function _delete($table, $iter) {
			if (!$this->db)
				return false;
			
			return $this->db->delete($table, $this->_id_string($iter->get_id()));
		}
		
		/**
		  * Delete a row (syncs with the database backend)
		  * @iter a #DataIter representing the row to be deleted
		  *
		  * @result true if the deletion was successful, false otherwise
		  */
		public function delete($iter) {
			if (!$this->table)
				return false;

			return $this->_delete($this->table, $iter);
		}

		/**
		  * Create a #DataIter from data
		  * @row an array containing the data
		  *
		  * @result a #DataIter
		  */
		function _row_to_iter($row, $dataiter = null) {
			if (!$dataiter)
				$dataiter = $this->dataiter;

			if ($row)
				return new $dataiter($this, isset($row[$this->id]) ? $row[$this->id] : null, $row);
			else
				return $row;
		}
		
		/**
		  * Create array of #DataIter from array of data
		  * @rows an array containing arrays of data
		  *
		  * @result an array of #DataIter
		  */
		function _rows_to_iters($rows, $dataiter = null) {
			if ($rows) {
				$iters = array();
				
				foreach ($rows as $data)
					$iters[] = $this->_row_to_iter($data, $dataiter);

				return $iters;
			} else
				return $rows;		
		}
		
		/**
		  * Get all rows in the model
		  *
		  * @result an array of #DataIter
		  */
		function get()
		{
			return $this->find('');
		}

		/**
		 * Get all rows in the model that satisfy the conditions.
		 * @conditions the SQL 'where' clause that needs to be satisfied
		 *
		 * @result an array of #DataIter
		 */
		function find($conditions)
		{
			if (!$this->db || !$this->table)
				return array();

			$query = $this->_generate_query($conditions);

			$rows = $this->db->query($query);
			
			return $this->_rows_to_iters($rows);			
		}
		
		/**
		  * Get a specific row in the model
		  * @id the id of the row
		  *
		  * @result a #DataIter representing the row
		  */
		function get_iter($id) {
			if (!$this->db || !$this->table)
				return null;

			$data = $this->db->query_first($this->_generate_query($this->_id_string($id)));

			// if ($data === null)
			// 	throw new DataIterNotFound($id);

			return $this->_row_to_iter($data);
		}
		
		/**
		  * Escape a string so it can be used in queries
		  * @s the string to be escaped
		  *
		  * @result the escaped string
		  */
		function escape_string($s) {
			return $this->db->escape_string($s);
		}

		/**
		  * Get the last occurred database error
		  *
		  * @result the last occurred database error
		  */
		function get_last_error() {
			return $this->db->get_last_error();
		}
		
		/**
		  * Get the number of affected rows
		  *
		  * @result the number of affected rows
		  */
		function get_affected_rows() {
			return $this->db->get_affected_rows();
		}

		protected function _generate_query($where)
		{
			return "SELECT * FROM {$this->table}" . ($where ? " WHERE {$where}" : "");
		}
	}

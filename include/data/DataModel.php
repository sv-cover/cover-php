<?php
	require_once('DataIter.php');

	/**
	  * This class provides a base class for accessing data. This class can
	  * be used for very simple one-to-one, model-to-table type mappings.
	  * More complex models should inherit from this base class and implement
	  * their own insert, update, delete, get and get_iter functions 
	  */
	class DataModel
	{
		protected $db = null; /** The database backend */
		protected $table = null; /** The table to model */
		protected $dataiter = 'DataIter';
		
		/**
		  * Create a new DataModel
		  * @db the database backend to use (#DatabasePgsql or #DatabaseMysql)
		  * @table the table to model 
		  * @id optional; the field name to use as unique id
		  */
		public function __construct($db, $table = null, $primary_key = 'id')
		{
			$this->db = $db;
			$this->table = $table;
			$this->primary_key = $primary_key;
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
		protected function _insert($table, DataIter $iter, $getid = false)
		{
			if (!$this->db)
				return false;
			
			$this->db->insert($table, $iter->data);
			
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
		public function insert(DataIter $iter, $getid = false)
		{
			if (!$this->table)
				return false;

			return $this->_insert($this->table, $iter, $getid);
		}
		
		/**
		  * Update a row (sync changes in the database backend). 
		  * Convenient function for descendents of #DataModel
		  * @table the table to update the iter in
		  * @iter a #DataIter representing the row that needs updating
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		protected function _update($table, DataIter $iter) {
			if (!$this->db)
				return false;

			return $this->db->update($table, 
					$iter->get_changed_values(), 
					array($this->primary_key => $iter->get_id()));
		}
		
		/**
		  * Update a row (sync changes in the database backend)
		  * @iter a #DataIter representing the row that needs updating
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		public function update(DataIter $iter) {
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
		protected function _delete($table, DataIter $iter) {
			if (!$this->db)
				return false;

			return $this->db->delete($table, array($this->primary_key => $iter->get_id()));
		}
		
		/**
		  * Delete a row (syncs with the database backend)
		  * @iter a #DataIter representing the row to be deleted
		  *
		  * @result true if the deletion was successful, false otherwise
		  */
		public function delete(DataIter $iter) {
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
		protected function _row_to_iter($row)
		{
			if ($row)
				return new $this->dataiter($this, isset($row[$this->id]) ? $row[$this->id] : null, $row);
			else
				return $row;
		}
		
		/**
		  * Create array of #DataIter from array of data
		  * @rows an array containing arrays of data
		  *
		  * @result an array of #DataIter
		  */
		protected function _rows_to_iters($rows)
		{
			if (!$rows)
				return $rows;

			$iters = array();
				
			foreach ($rows as $data)
				$iters[] = $this->_row_to_iter($data);

			return $iters;
		}
		
		/**
		  * Get all rows in the model
		  *
		  * @result an array of #DataIter
		  */
		public function get()
		{
			return $this->find();
		}

		/**
		 * Get all rows in the model that satisfy the conditions.
		 * @conditions the SQL 'where' clause that needs to be satisfied
		 *
		 * @result an array of #DataIter
		 */
		public function find(array $conditions = array())
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
		public function get_iter($id)
		{
			if (!$this->db || !$this->table)
				return null;

			$data = $this->db->query_first($this->_generate_query(array($this->primary_key => $id)));

			return $this->_row_to_iter($data);
		}
		
		/**
		  * Get the last occurred database error
		  *
		  * @result the last occurred database error
		  */
		public function get_last_error()
		{
			return $this->db->get_last_error();
		}
		
		/**
		  * Get the number of affected rows
		  *
		  * @result the number of affected rows
		  */
		public function get_affected_rows()
		{
			return $this->db->get_affected_rows();
		}

		protected function _generate_query(array $conditions)
		{
			$where = $conditions
				? $this->db->generate_where($conditions)
				: '';

			return "SELECT * FROM {$this->table}" . ($where ? " WHERE {$where}" : "");
		}
	}

<?php
	require_once 'include/data/DataIter.php';

	class DataIterNotFoundException extends NotFoundException
	{
		public function __construct($id, DataModel $source = null)
		{
			parent::__construct(sprintf('%s with id %d was not found',
				$source
					? substr(get_class($source), strlen('DataModel'))
					: 'DataIter',
				$id));
		}
	}

	class DatabaseLiteral 
	{
		protected $sql;

		public function __construct($sql)
		{
			$this->sql = $sql;
		}

		public function toSQL()
		{
			return $this->sql;
		}
	}

	/**
	  * This class provides a base class for accessing data. This class can
	  * be used for very simple one-to-one, model-to-table type mappings.
	  * More complex models should inherit from this base class and implement
	  * their own insert, update, delete, get and get_iter functions 
	  */
	class DataModel
	{
		public $db; /** The database backend */
		public $table; /** The table to model */
		public $id;
		public $dataiter = 'GenericDataIter';
		public $fields = array();
		protected $auto_increment;
		
		/**
		  * Create a new DataModel
		  * @param DatabasePgsql|DatabaseMysql $db the database backend to use (#DatabasePgsql or #DatabaseMysql)
		  * @param string|null $table the table to model 
		  * @param string $id the field name to use as unique id
		  */
		public function __construct($db, $table = null, $id = 'id')
		{
			$this->db = $db;
			$this->table = $table;
			$this->id = $id;

			if ($this->auto_increment === null)
				$this->auto_increment = $this->id == 'id';
		}

		public function new_iter($row = array(), $dataiter = null, $preseed = [])
		{
			if (!$dataiter)
				$dataiter = $this->dataiter;

			if (!is_subclass_of($dataiter, 'DataIter', true))
				throw new LogicException('Calling new_iter with a class name for dataiter that does not extend DataIter');

			return new $dataiter($this, isset($row[$this->id]) ? $row[$this->id] : null, $row, $preseed);
		}

		/**
		  * Insert a new row (syncs with the database backend). This
		  * is a convenient function to be used by descendents of
		  * #DataModel
		  * @table the table to insert the iter in
		  * @iter a #DataIter representing the row
		  * @getid optional; whether to get the last insert id
		  *
		  * @result if getid is true the last insert id is returned, NULL
		  * otherwise
		  */		
		protected function _insert($table, DataIter $iter, $get_id = false)
		{
			$data = array();

			$id = null;

			$fields = $iter::fields();

			foreach ($iter->data as $key => $value)
				if (in_array($key, $fields))
					$data[$key] = $value;

			if (count($data) === 0)
				throw new LogicException('Trying to insert empty iterator into table');
			
			$this->db->insert($table, $data);

			if ($get_id) {
				$id = $this->db->get_last_insert_id();
				$iter->set_id($id);
			}

			return $id;
		}
		
		/**
		  * Insert a new row (syncs with the database backend)
		  * @iter a #DataIter representing the row
		  *
		  * @result the last insert id
		  */
		public function insert(DataIter $iter)
		{
			if (!$this->table)
				throw new RuntimeException(get_class($this) . '::$table is not set');
			
			return $this->_insert($this->table, $iter, $this->auto_increment);
		}
		
		/**
		  * Generate a id = value string
		  * @value the id value
		  *
		  * @result a id = value string
		  */
		protected function _id_string($value, $table = null)
		{
			$result = $this->id . ' = ';

			if ($table)
				$reslt = $table . '.' . $result;
			elseif ($this->table)
				$result = $this->table . '.' . $result;
			
			if ($this->id == 'id')
				return $result . intval($value);
			else
				return $result . "'" . $this->db->escape_string($value) . "'";
		}

		/**
		  * Update a row (sync changes in the database backend). 
		  * Convenient function for descendents of #DataModel
		  * @table the table to update the iter in
		  * @iter a #DataIter representing the row that needs updating
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		protected function _update($table, DataIter $iter)
		{
			$data = array();

			$fields = $iter::fields();

			foreach ($iter->get_changed_values() as $key => $value)
				if (in_array($key, $fields))
					$data[$key] = $value;

			if (count($data) === 0)
				return true;

			return $this->db->update($table, 
					$data, 
					$this->_id_string($iter->get_id(), $table));
		}
		
		/**
		  * Update a row (sync changes in the database backend)
		  * @iter a #DataIter representing the row that needs updating
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		public function update(DataIter $iter)
		{
			if (!$this->table)
				throw new RuntimeException(get_class($this) . '::$table is not set');

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
		protected function _delete($table, DataIter $iter)
		{
			return $this->db->delete($table, $this->_id_string($iter->get_id(), $table));
		}
		
		/**
		  * Delete a row (syncs with the database backend)
		  * @iter a #DataIter representing the row to be deleted
		  *
		  * @result true if the deletion was successful, false otherwise
		  */
		public function delete(DataIter $iter)
		{
			if (!$this->table)
				throw new RuntimeException(get_class($this) . '::$table is not set');
			
			return $this->_delete($this->table, $iter);
		}

		/**
		  * Create a #DataIter from data
		  * TODO: public because often called from DataIter!
		  * @row an array containing the data
		  *
		  * @result a #DataIter
		  */
		/*protected*/ public function _row_to_iter($row, $dataiter = null, array $preseed = [])
		{
			if ($row)
				return $this->new_iter($row, $dataiter, $preseed);
			else
				return $row;
		}
		
		/**
		  * Create array of #DataIter from array of data
		  * TODO: public because often called from DataIter!
		  * @rows an array containing arrays of data
		  *
		  * @result an array of #DataIter
		  */
		/*protected*/ public function _rows_to_iters($rows, $dataiter = null, array $preseed = [])
		{
			return array_map(function ($row) use ($dataiter, $preseed) {
				return $this->_row_to_iter($row, $dataiter, $preseed);
			}, $rows);
		}

		protected function _rows_to_table($rows, $key_field, $value_field)
		{
			if (is_array($value_field))
				$create_value = function($row) use ($value_field) {
					return array_map(function($field) use ($row) {
						return $row[$field];
					}, $value_field);
				};
			else
				$create_value = function($row) use ($value_field) {
					return $row[$value_field]; 
				};

			return array_combine(
				array_map(function($row) use ($key_field) { return $row[$key_field]; }, $rows),
				array_map($create_value, $rows));
		}
		
		/**
		  * Get all rows in the model
		  *
		  * @result an array of #DataIter
		  */
		public function get()
		{
			return $this->find('');
		}

		/**
		 * Get all rows in the model that satisfy the conditions.
		 * @conditions the SQL 'where' clause that needs to be satisfied
		 *
		 * @result an array of #DataIter
		 */
		public function find($conditions)
		{
			$query = $this->_generate_query($conditions);

			$rows = $this->db->query($query);
			
			return $this->_rows_to_iters($rows);
		}

		public function find_one($conditions)
		{
			$results = $this->find($conditions);

			if (count($results) !== 1)
				return null;

			return $results[0];
		}
		
		/**
		  * Get a specific row in the model
		  * @id the id of the row
		  *
		  * @result a #DataIter representing the row
		  */
		public function get_iter($id)
		{
			$data = $this->db->query_first($this->_generate_query($this->_id_string($id)));

			if ($data === null)
				throw new DataIterNotFoundException($id, $this);

			return $this->_row_to_iter($data);
		}

		protected function _generate_conditions_from_array(array $conditions)
		{
			$atoms = []; // Query in CNF

			foreach ($conditions as $key => $value)
			{
				// If the value is just a bit of raw SQL, add it directly to
				// the atoms, and skip the rest of the create-sql-loop.
				if (is_int($key) && $value instanceof DatabaseLiteral) {
					$atoms[] = sprintf('(%s)', $value->toSQL());
					continue;
				}

				if (preg_match('/^(.+?)__(eq|cieq|ne|gt|lt|in|contains|isnull)$/', $key, $match)) {
					$field = $match[1];
					$operator = $match[2];
				} else {
					$field = $key;
					$operator = 'eq';
				}

				switch ($operator)
				{
					case 'lt':
						$format = "%s < '%s'";
						break;

					case 'gt':
						$format = "%s > '%s'";
						break;

					case 'in':
						// If the value is an iterator, make it an array first for easy use
						if ($value instanceof Iterator)
							$value = iterator_to_array($value, false);

						// Check the value
						if (!is_array($value))
							throw new InvalidArgumentException("in-operator in '$field' condition expects an array or iterable.");

						if (count($value) === 0)
							throw new InvalidArgumentException("The value for the condition on '$field' is an empty set.");

						$safe_values = array_map([$this->db, 'escape_string'], $value);
						$format = sprintf('%%s IN (%s)', implode(', ', $safe_values));
						unset($value);
						break;

					case 'contains':
						$format = "%s ILIKE '%%%s%%'";
						break;

					case 'isnull':
						$format = $value ? '%s IS NULL' : '%s IS NOT NULL';
						unset($value);
						break;

					case 'ne':
						$format = "%s <> '%s'";
						break;

					case 'cieq': // Case insensitive equivalence
						$format = "LOWER(%s) = LOWER('%s')";
						break;

					default:
					case 'eq':
						$format = "%s = '%s'";
						break;
				}

				$atoms[] = isset($value)
					? sprintf($format, $field, $this->db->escape_string($value))
					: sprintf($format, $field);
			}

			return implode(' AND ', $atoms);
		}
		
		protected function _generate_query($where)
		{
			if (is_array($where))
				$where = $this->_generate_conditions_from_array($where);

			return "SELECT * FROM {$this->table}" . ($where ? " WHERE {$where}" : "");
		}
	}

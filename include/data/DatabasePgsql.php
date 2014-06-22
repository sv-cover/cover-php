<?php
	require_once dirname(__FILE__) . '/Database.php';

	/**
	  * This class provides a postgresql backend with commonly used functions
	  * like insert, update and delete
	  */
	class DatabasePgsql implements Database
	{
		private $resource = 0;
		private $last_result = null;
		private $last_affected = null;
		private $last_insert_table = null;

		/**
		  * Create new postgresql database
		  * @dbid a hash with database information (host, port, user, password, 
		  * dbname)
		  */
		public function __construct($dbid)
		{
			/* Connect to database */
			$this->_connect($dbid);
		}
		
		/**
		  * Make connection to database
		  */
		private function _connect($dbid)
		{
			if ($this->resource)
				return;

			$params = array();
			/* Add host */
			$params[] = 'host=' . ($dbid['host'] ? $dbid['host'] : 'localhost');
			
			/* Add port if needed */
			if (isset($dbid['port']))
				$params[] = 'port=' . $dbid['port'];
			
			/* Add user */
			$params[] = 'user=' . $dbid['user'];
			
			/* Add password */
			$params[] = 'password=' . $dbid['password'];
			
			/* Add database */
			$params[] = 'dbname=' . $dbid['database'];
			
			/* Open connection */
			$this->resource = pg_connect(implode(' ', $params));
			
			if (!$this->resource)
				trigger_error('Could not connect to database: ' . $php_errormsg);
		}
		
		/**
		  * Get the last occurred error
		  *
		  * @result a string with the last error
		  */
		public function get_last_error()
		{
			return pg_last_error($this->resource);
		}
		
		/**
		  * Query the database with any query
		  * @query a string with the query
		  * @indices optional; true if the returned array should also 
		  * be accessible with indices
		  *
		  * @result an array with for each row a hash with the values (with 
		  * keys being the column names) or null if an error occurred
		  */
		public function query($query, $indices = false)
		{
			if (!$this->resource)
				return;

			/* Query the database */
			$handle = @pg_query($this->resource, $query);

			if ($handle === false) {
				throw new RuntimeException('Query failed: ' . $this->get_last_error());
				/* An error occurred */
				return null;
			} else if ($handle !== true) {
				$this->last_result = array();

				/* Fetch all the rows */
				if (!$indices) {
					while ($row = pg_fetch_assoc($handle))
		    				$this->last_result[] = $row;
		    		} else {
		    			while ($row = pg_fetch_array($handle))
		    				$this->last_result[] = $row;
		    		}
		    		
		    		$this->last_affected = pg_affected_rows($handle);

				/* Free the query handle */
				pg_free_result($handle);
				
				/* Return the results */
				return $this->last_result;        
			}
			
			$this->last_affected = 0;

			return true;
		}
		
		/**
		  * Query the database with any query and return only the first row
		  * @query a string with the query
  		  * @indices optional; true if the returned array should also 
  		  * be accessible with indices
		  *
		  * @result a hash with the values (with keys being the column names)
		  * or null if there are no results (or an error occurred)
		  */
		public function query_first($query, $indices = false)
		{
			/* Execute query */
			$result = $this->query($query, $indices);
			
			if (is_string($result)) {
				/* Result is a string, this means an error occurred */
				return $result;
			} else if (!is_array($result) || count($result) == 0) {
				/* There are no results */
				return null;
			} else {
				/* Return the result */
				return $result[0];
			}
		}
		
		/**
		  * Query the database with any query and return a single value of
		  * the first row 
		  * @query a string with the query
		  * @col optional; the column to get the value from
		  *
		  * @result a value or null if there are no results
		  */
		public function query_value($query, $col = 0)
		{
			/* Execute the query */
			$result = $this->query_first($query, true);
			
			if ($result) {
				/* Return the value */
				return $result[$col];
			} else {
				/* Return the result */
				return $result;
			}
		}
		
		/**
		  * Insert a new row into a table in the database
		  * @table the table to insert the new row in
		  * @values a hash containing the values to insert. The key each item
		  * in the hash is the column name, the value the column value. Strings
		  * will automatically be escaped (except for special SQL functions)
		  * @literals optional; the fields that should be used literally in 
		  * the query
		  */
		public function insert($table, array $values)
		{
			if (!$this->resource) {
				return false;
			}

			$sql_values = array();

			foreach ($values as $key => $value)
			{
				if ($value instanceof DatabaseLiteral)
					$sql_values[] = $value->toSQL();
				elseif ($value === null)
					$sql_values[] = 'NULL';
				elseif (is_int($value))
					$sql_values[] = sprintf('%d', $value);
				else
					$sql_values[] = sprintf("'%s'", $this->escape_string($value));
			}

			$query = sprintf('INSERT INTO "%s" (%s) VALUES (%s)',
				$table,
				implode(', ', array_keys($values)),
				implode(', ', $sql_values));
			
			/* Execute query */
			$this->query($query);
			
			/* Save last insertion table so we can use it in 
			   get_last_insert_id */
			$this->last_insert_table = $table;
		}
		
		/**
		  * Get the last insert id (uses currval("<last_table>_id_sec")
		  *
		  * @result the id of the last inserted row
		  */
		public function get_last_insert_id()
		{
			return $this->query_value("SELECT currval('" . 
					$this->last_insert_table . "_id_seq'::regclass)");
		}
		
		/**
		  * Update an existing row in a table
		  * @table the table to update a row in
		  * @values a hash containing the values to insert. The key each item
		  * in the hash is the column name, the value the column value. Strings
		  * will automatically be escaped (except for special SQL functions)
		  * @condition the WHERE part in the update query, this specifies which
		  * rows will be affected
		  * @literals optional; the fields that should be used literally in 
		  * the query
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		public function update($table, array $values, array $conditions)
		{
			if (!$this->resource)
				return false;

			if (count($values) == 0)
				return true;

			$query = sprintf('UPDATE "%s" SET %s WHERE %s',
				$table,
				$this->_generate_update($values),
				$this->_generate_where($conditions));

			/* Execute query */
			return $this->query($query);
		}

		private function _generate_update(array $values)
		{
			$sql_pairs = array();

			foreach ($values as $key => $value)
			{
				if (is_int($key) && $value instanceof DatabaseLiteral)
					$sql_pairs[] = $value->toSQL();
				elseif ($value === null)
					$sql_pairs[] = sprintf('"%s" = NULL', $key);
				elseif (is_int($value))
					$sql_pairs[] = sprintf('"%s" = %d', $key, $value);
				elseif ($value instanceof DatabaseLiteral)
					$sql_pairs[] = sprintf('"%s" = %s', $key, $value->toSQL());
				else
					$sql_pairs[] = sprintf('"%s" = \'%s\'', $key, $this->escape_string($value));
			}

			return implode(', ', $sql_pairs);
		}

		private function _generate_where(array $conditions)
		{
			$sql_pairs = array();

			foreach ($values as $key => $value)
			{
				if ($value instanceof DatabaseLiteral) {
					$sql_pairs[] = $value->toSQL();
					continue;
				}

				$format = '"%s" = %s';

				if (preg_match('/^(.+)__(gt|lt|is_null)$/', $key, $match))
				{
					$key = $match[1];

					switch ($match[2])
					{
						case 'gt':
							$format = '"%s" > %s';
							break;

						case 'lt':
							$format = '"%s" < %s';
							break;

						case 'is_null':
							$format = $value
								? '"%s" IS NULL'
								: '"%s" IS NOT NULL';
							break;
					}
				}

				if ($value === null) {
					$format = '"%s" IS NULL';
				elseif (is_int($value))
					$value = sprintf('%d', $value);
				else
					$value = sprintf("'%s'", $this->escape_string($value));

				$sql_pairs[] = substr_count($format, '%s') === 2
					? sprintf($format, $key, $value);
					: sprintf($format, $key);
			}

			return implode(' AND ', $sql_pairs);
		}

		/**
		  * Delete one or more rows from a table
		  * @table the table to delete a row from
		  * @condition the WHERE part of the delete query. All matched rows
		  * are deleted
		  * @limit optional; how many rows should be deleted. This doesn't
		  * work for postgresql but is there for compatibility
		  *
		  * @result true if delete was successful, false otherwise
		  */
		public function delete($table, array $conditions) {
			if (!$this->resource)
				return false;

			if (!$condition)
				throw new RuntimeException('Are you really really sure you want to delete everything?');

			return $this->query('DELETE FROM "' . $table . '" WHERE ' . $this->_generate_where($conditions));
		}

		/**
		  * Escape a string so it can be used in queries
		  * @s the string to be escaped
		  *
		  * @result the escaped string
		  */
		public function escape_string($s) {
			return pg_escape_string($s);
		}
		
		/**
		  * Get the number of affected rows
		  *
		  * @result the number of affected rows
		  */
		public function get_affected_rows() {
			if (!$this->resource)
				return;

			return $this->last_affected;
		}

		public function generate_where(array $conditions)
		{
			return $this->_generate_where($conditions);
		}
    }
?>

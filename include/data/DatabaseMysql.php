<?php
	/**
	  * This class provides a MySQL backend with commonly used functions
	  * like insert, update and delete
	  */
	class DatabaseMysql {
		var $resource = 0;
		var $last_result = null;

		/**
		  * Create new mysql database
		  * @dbid a hash with database information (host, port, user, password, 
		  * dbname)
		  */
		function DatabaseMysql($dbid) {
			$this->_connect($dbid);
		}

		/**
		  * Make connection to database
		  */
		function _connect() {
			if ($this->resource)
				return;

			/* Construct server from $dbid['host'] and $dbid['port'] */
			$server = ($dbid['host'] ? $dbid['host'] : 'localhost') . ($dbid['port'] ? (':' . 
					$dbid['port']) : '');

			/* Open connection */
			$this->resource = mysql_connect($server, $dbid['user'], 
					$dbid['password']);
			
			/* Select database */
			if ($this->resource)
				mysql_select_db($dbid['database'], $this->resource);
		}

		/**
		  * Get the last occurred error
		  *
		  * @result a string with the last error
		  */
		function get_last_error() {
			return mysql_error($this->resource);
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
		function query($query, $indices = false) {
			if (!$this->resource)
				return null;

			/* Query the database */
			$handle = mysql_query($query, $this->resource);

			if ($handle === false) {
				/* An error occurred */
				return null;
			} else if ($handle !== true) {
				$this->last_result = array();
				
				/* Fetch all the rows */
				if (!$indices) {
					while ($row = mysql_fetch_assoc($handle))
						$this->last_result[] = $row;
				} else {
					while ($row = mysql_fetch_array($handle))
						$this->last_result[] = $row;				
				}

				/* Free the query handle */
				mysql_free_result($handle);
				
				/* Return the results */
				return $this->last_result;        
			}

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
		function query_first($query, $indices) {
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
		function query_value($query, $col = 0) {
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
		  */
		function insert($table, $values, $literals = null) {
			if (!$this->resource) {
				return false;
			}

			$query = 'INSERT INTO `' . $table . '`';
			$keys = array_keys($values);

			$k = '(';
			$v = 'VALUES(';

			for ($i = 0; $i < count($keys); $i++) {
				if ($i != 0) {
					$k .= ', ';
					$v .= ', ';
				}

				$k .= $keys[$i];

				/* If the value is a string and it's not a 
				 * literal.
				 */
				if (is_string($values[$keys[$i]]) && (!$literals ||
						!in_array($keys[$i], $literals))) {
					/* Escape the string and add quotes */
					$v .= '"' . $this->escape_string($values[$keys[$i]]) . '"';
				} elseif ($values[$keys[$i]] === null) {
					$v .= 'null';
				} else {
					/* Just add the value to the query string */
					$v .= $values[$keys[$i]];
				}
			}

			$query = $query . ' ' . $k . ') ' . $v . ');';
			
			/* Execute query */
			$this->query($query);
		}

		/**
		  * Get the last insert id (uses mysql_insert_id)
		  *
		  * @result the id of the last inserted row
		  */		
		function get_last_insert_id() {
			return mysql_insert_id($this->resource);
		}

		/**
		  * Update an existing row in a table
		  * @table the table to update a row in
		  * @values a hash containing the values to insert. The key each item
		  * in the hash is the column name, the value the column value. Strings
		  * will automatically be escaped (except for special SQL functions)
		  * @condition the WHERE part in the update query, this specifies which
		  * rows will be affected
		  *
		  * @result true if the update was successful, false otherwise 
		  */		
		function update($table, $values, $condition, $literals = null) {
			if (!$this->resource)
				return false;

			$query = 'UPDATE `' . $table . '` SET ';
			$keys = array_keys($values);
			$k = '';

			/* For all values */
			for ($i = 0; $i < count($keys); $i++) {
				if ($i != 0) {
					$k .= ', ';
				}

				/* Add <key>= */
				$k .= $keys[$i] . '=';

				/* If the value is a string and it's not a 
				 * literal
				 */
				if (is_string($values[$keys[$i]]) && (!$literals ||
						!in_array($keys[$i], $literals))) {
					/* Escape the string and add quotes */
					$k .= '"' . $this->escape_string($values[$keys[$i]]) . '"';
				} elseif ($values[$keys[$i]] === null) {
					$k .= 'null';
				} else {
					/* Just add the value to the query string */
					$k .= $values[$keys[$i]];
				}
			}

			$query .= $k;

			/* Add condition */
			if ($condition)
				$query .= ' WHERE ' . $condition;

			/* Execute query */
			return $this->query($query);
		}
		
		/**
		  * Delete one or more rows from a table
		  * @table the table to delete a row from
		  * @condition the WHERE part of the delete query. All matched rows
		  * are deleted
		  * @limit optional; how many rows should be deleted
		  *
		  * @result true if delete was successful, false otherwise
		  */
		function delete($table, $condition, $limit = 1) {
			if (!$this->resource)
				return false;

			return $this->query('DELETE FROM `' . $table . '` ' . 
					($condition ? ('WHERE ' . $condition . ' ') : '') . 
					($limit > 0 ? ('LIMIT ' . $limit) : ''));
		}

		/**
		  * Escape a string so it can be used in queries
		  * @s the string to be escaped
		  *
		  * @result the escaped string
		  */		
		function escape_string($s) {
			return mysql_real_escape_string($s);
		}
		
		/**
		  * Get the number of affected rows
		  *
		  * @result the number of affected rows
		  */
		function get_affected_rows() {
			if (!$this->resource)
				return;

			return mysql_affected_rows($this->resource);
		}
    }
?>

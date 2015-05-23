<?php
/**
  * This class provides a postgresql backend with commonly used functions
  * like insert, update and delete
  */
class DatabasePDO
{
	private $resource;

	private $last_result = null;
	private $last_affected = null;
	private $last_insert_table = null;

	public $history = null;

	/**
	  * Create new postgresql database
	  * @dbid a hash with database information (host, port, user, password, 
	  * dbname)
	  */
	public function __construct($dbid)
	{
		/* Connect to database */
		$this->_connect($dbid);

		if (get_config_value('show_queries', false))
			$this->history = array();
	}
	
	/**
	  * Make connection to database
	  */
	private function _connect($dbid)
	{
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

		/* Add client encoding */
		$params[] = "options='--client_encoding=UTF8'";
		
		/* Open connection */
		$this->resource = new PDO('pgsql:' . implode(';', $params));

		$this->resource->exec("SET NAMES 'UTF-8'; SET DateStyle = 'ISO, DMY'; SET bytea_output=escape");
		
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
		return implode(': ', $this->resource->errorInfo());
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
	function query($query, $indices = false)
	{
		if (!$this->resource)
			return;

		$start = microtime(true);

		/* Query the database */
		$handle = $this->resource->query($query);

		$duration = microtime(true) - $start;

		if ($this->history !== null)
			$this->history[] = array(
				'query' => $query,
				'duration' => $duration,
				'backtrace' => debug_backtrace()
			);

		if ($handle === false) {
			throw new RuntimeException('Query failed: ' . $this->get_last_error());
			/* An error occurred */
			return null;
		} else if ($handle !== true) {
			$this->last_result = array();

			/* Fetch all the rows */
			$this->last_result = $handle->fetchAll($indices ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);

			$this->last_affected = $handle->rowCount();

			/* Free the query handle */
			unset($handle);
			
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
	function query_first($query, $indices = false)
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
	  * @literals optional; the fields that should be used literally in 
	  * the query
	  */
	function insert($table, $values, $literals = null) {
		if (!$this->resource) {
			return false;
		}

		$query = 'INSERT INTO "' . $table . '"';
		$keys = array_keys($values);

		$k = '(';
		$v = 'VALUES(';

		for ($i = 0; $i < count($keys); $i++) {
			if ($i != 0) {
				$k .= ', ';
				$v .= ', ';
			}

			$k .= '"' . $keys[$i] . '"';

			/* If the value is a string and it's not a
			 * literal
			 */
			if (is_string($values[$keys[$i]]) && (!$literals ||
					!in_array($keys[$i], $literals))) {
				/* Escape the string and add quotes */
				$v .= "'" . $this->escape_string($values[$keys[$i]]) . "'";
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
		
		/* Save last insertion table so we can use it in 
		   get_last_insert_id */
		$this->last_insert_table = $table;
	}
	
	/**
	  * Get the last insert id (uses currval("<last_table>_id_sec")
	  *
	  * @result the id of the last inserted row
	  */
	function get_last_insert_id() {
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
	function update($table, $values, $condition, $literals = []) {
		if (!$this->resource)
			return false;

		if (count($values) == 0)
			return true;

		$query = 'UPDATE "' . $table . '" SET ';
		$keys = array_keys($values);
		$k = '';

		/* For all values */
		for ($i = 0; $i < count($keys); $i++) {
			if ($i != 0)
				$k .= ', ';

			/* Add <key>= */
			$k .= '"' . $keys[$i] . '"=';

			/* If the value is a string and it's not a
			 * literal
			 */
			if ($values[$keys[$i]] === null)
				$k .= 'NULL';
			elseif (is_int($values[$keys[$i]]) || in_array($keys[$i], $literals))
				$k .= $values[$keys[$i]];
			else
				$k .= "'" . $this->escape_string($values[$keys[$i]]) . "'";
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
	  * @limit optional; how many rows should be deleted. This doesn't
	  * work for postgresql but is there for compatibility
	  *
	  * @result true if delete was successful, false otherwise
	  */
	function delete($table, $condition) {
		if (!$this->resource)
			return false;

		if (!$condition)
			throw new RuntimeException('Are you really really sure you want to delete everything?');

		$this->query('DELETE FROM "' . $table . '" WHERE ' . $condition);

		return $this->last_affected;
	}

	/**
	  * Escape a string so it can be used in queries
	  * @s the string to be escaped
	  *
	  * @result the escaped string
	  */
	function escape_string($s) {
		return substr($this->resource->quote($s), 1, -1);
	}
	
	/**
	  * Checks whether there is a connection
	  *
	  * @result true if there is a connection, false otherwise
	  */
	function is_connected() {
		return (bool) $this->resource;
	}
	
	/**
	  * Get the number of affected rows
	  *
	  * @result the number of affected rows
	  */
	function get_affected_rows() {
		if (!$this->resource)
			return;

		return $this->last_affected;
	}

	public function read_blob($data)
	{
		if (!is_resource($data))
			throw new InvalidArgumentException('DatabasePDO::read_blob expected resource as argument');

		return stream_get_contents($data);
	}

	public function write_blob($data)
	{
		return substr($this->resource->quote(stream_get_contents($data), PDO::PARAM_LOB), 1, -1);
	}
}

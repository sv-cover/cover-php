<?php
/**
  * This class provides a postgresql backend with commonly used functions
  * like insert, update and delete
  */

class DatabasePDO
{
	private $resource;

	private $last_affected = null;
	private $last_insert_table = null;

	public $history = [];
	public $track_history = false;

	private $transaction_counter = 0;

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
		$params = array();
		/* Add host */
		$params[] = 'host=' . ($dbid['host'] ? $dbid['host'] : 'localhost');
		
		/* Add port if needed */
		if (isset($dbid['port']))
			$params[] = 'port=' . $dbid['port'];
		
		/* Add user */
		$params[] = 'user=' . $dbid['user'];
		
		/* Add password */
		if (!empty($dbid['password']))
			$params[] = 'password=' . $dbid['password'];
		
		/* Add database */
		$params[] = 'dbname=' . $dbid['database'];

		/* Add client encoding */
		$params[] = "options='--client_encoding=UTF8'";

		/* Open connection */
		$this->resource = new PDO('pgsql:' . implode(';', $params));

		$this->resource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$this->resource->exec("SET NAMES 'UTF-8'; SET DateStyle = 'ISO, DMY'; SET bytea_output=escape");
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
	function query($query, $indices = false, array $input_parameters = [])
	{
		$start = microtime(true);

		/* Query the database */
		$statement = $this->resource->prepare($query);

		$statement->execute($input_parameters);

		$duration = microtime(true) - $start;

		if ($this->track_history)
			$this->history[] = array(
				'query' => $query,
				'duration' => $duration,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
			);

		/* Fetch all the rows */
		$this->last_affected = $statement->rowCount();

		/* Return the results */
		return $statement->fetchAll($indices ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
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
	 * Query the database with any query and return the value from a
	 * single column for each row..
	 * @param $query SQL query
	 * @param $col column as integer or name
	 */
	public function query_column($query, $col = 0)
	{
		// Execute query with indices if col index is numeric. If it isn't,
		// then fetch as an associated array.
		$rows = $this->query($query, is_int($col));

		// Create a getter for the col (which is a function that returns
		// $rows[$col]) and apply it to every row.
		// I just love functional programming. #sorry #notsorry
		return array_map(getter($col), $rows);
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
	function insert($table, $values) {
		if (!$this->resource) {
			return false;
		}

		$query = 'INSERT INTO "' . $table . '"';
		$keys = array_keys($values);

		$k = '(';
		$v = 'VALUES(';

		$data = [];

		for ($i = 0; $i < count($keys); $i++) {
			if ($i != 0) {
				$k .= ', ';
				$v .= ', ';
			}

			$k .= '"' . $keys[$i] . '"';

			$placeholder = ':' . $keys[$i];

			$v .= $this->prepare_value($values[$keys[$i]], $placeholder, $data);
		}

		$query = $query . ' ' . $k . ') ' . $v . ');';

		/* Execute query */
		$this->query($query, false, $data);

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
	function update($table, $values, $condition) {
		if (!$this->resource)
			return false;

		if (count($values) == 0)
			return true;

		if ($condition && !is_string($condition))
			throw new InvalidArgumentException('Condition parameter needs to be a string');

		$query = 'UPDATE "' . $table . '" SET ';
		$keys = array_keys($values);
		$data = [];
		$k = '';

		/* For all values */
		for ($i = 0; $i < count($keys); $i++) {
			if ($i != 0)
				$k .= ', ';

			/* Add <key>= */
			try {
				$k .= sprintf('"%s" = %s', $keys[$i], $this->prepare_value($values[$keys[$i]], ':' . $keys[$i], $data));
			} catch (InvalidArgumentException $e) {
				throw new InvalidArgumentException("Cannot encode the value of field '{$keys[$i]}'", null, $e);
			}
		}

		$query .= $k;

		/* Add condition */
		if ($condition)
			$query .= ' WHERE ' . $condition;

		/* Execute query */
		$this->query($query, false, $data);

		return $this->last_affected;
	}

	/**
	  * Escape a string so it can be used in queries
	  * @s the string to be escaped (not surrounded by quotes)
	  *
	  * @result the escaped string
	  */
	function escape_string($s) {
		return substr($this->resource->quote($s), 1, -1);
	}

	/**
	 * Quote the string (including surrounding quotes)
	 */
	public function quote($s) {
		return $this->resource->quote($s);
	}
	
	/**
	 * Escape any type of value (or get an InvalidArgumentException)
	 * @param $value mixed
	 * @return string SQL
	 */
	public function escape_value($value)
	{
		if ($value === null)
			return 'NULL';
		elseif ($value instanceof DateTime)
			return sprintf("'%s'", $value->format('Y-m-d H:i:s'));
		elseif ($value instanceof DatabaseLiteral)
			return $value->toSQL();
		elseif (is_int($value))
			return sprintf('%d', $value);
		elseif (is_bool($value))
			return $value ? '1' : '0';
		elseif (is_string($value))
			return $this->resource->quote($value);
		else
			throw new InvalidArgumentException('Unsupported datatype ' . gettype($value));
	}

	protected function prepare_value($value, $placeholder, array &$values = [])
	{
		if ($value === null)
			return 'NULL';
		elseif ($value instanceof DatabaseLiteral)
			return $value->toSQL();
		elseif (is_int($value))
			return sprintf('%d', $value);
		elseif (is_bool($value))
			return $value ? '1' : '0';
		elseif ($value instanceof DateTime)
			$values[$placeholder] = $value->format('Y-m-d H:i:s');
		elseif (is_string($value))
			$values[$placeholder] = $value;
		else
			throw new InvalidArgumentException('Unsupported datatype ' . gettype($value));

		return $placeholder;
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
	public function get_affected_rows() {
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

	public function beginTransaction()
	{
		if ($this->transaction_counter++ === 0)
			$this->resource->beginTransaction();


		// TODO: Maybe use SAVEPOINT to make nested transactions actually support rollback
		// See https://www.postgresql.org/docs/9.1/static/sql-savepoint.html
	}

	public function commit()
	{
		--$this->transaction_counter;

		if ($this->transaction_counter < 0)
			throw new Exception('Cannot commit this transaction: no open transaction');

		if ($this->transaction_counter === 0)
			$this->resource->commit();
	}

	public function rollback()
	{
		$this->resource->rollback();
		$this->transaction_counter = 0;
	}
}

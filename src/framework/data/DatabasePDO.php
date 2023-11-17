<?php
/**
 * This class provides a postgresql backend with commonly used functions
 * like insert, update and delete
 */

class DatabasePDO
{
	private $resource;

	private $last_insert_table = null;

	public $history = [];
	public $track_history = false;

	private $transaction_counter = 0;

	/**
	 * Create new postgresql database
	 * @dbid a hash with database information (host, port, user, password, 
	 * dbname)
	 */
	public function __construct(array $dbid)
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
	public function query($query, $indices = false, array $input_parameters = [])
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

		/* Return the results */
		return $statement->fetchAll($indices ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
	}

	public function execute($query, array $input_parameters = [])
	{
		$start = microtime(true);

		/* Query the database */
		$statement = $this->resource->prepare($query);

		/* Bind parameters (default is same default as PHP: String) */
		foreach ($input_parameters as $placeholder => $value)
			if (is_resource($value) && get_resource_type($value) === 'stream')
				$statement->bindValue($placeholder, $value, PDO::PARAM_LOB);
			else
				$statement->bindValue($placeholder, $value, PDO::PARAM_STR);

		$statement->execute();

		$duration = microtime(true) - $start;

		if ($this->track_history)
			$this->history[] = array(
				'query' => $query,
				'duration' => $duration,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
			);

		return $statement->rowCount();
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
	public function query_first($query, $indices = false, array $input_parameters = [])
	{
		$rows = $this->query($query, $indices, $input_parameters);
		return count($rows) > 0 ? $rows[0] : null;
	}
	
	/**
	 * Query the database with any query and return a single value of
	 * the first row 
	 * @param query a string with the query
	 * @return result a value or null if there are no results
	 */
	public function query_value($query, array $input_parameters = [])
	{
		$row = $this->query_first($query, true, $input_parameters);
		return $row ? $row[0] : null;
	}

	/**
	 * Query the database with any query and return the value from a
	 * single column for each row..
	 * @param $query SQL query
	 * @param $col column as integer or name
	 */
	public function query_column($query, $col = 0, array $input_parameters = [])
	{
		// Execute query with indices if col index is numeric. If it isn't,
		// then fetch as an associated array.
		$rows = $this->query($query, is_int($col), $input_parameters);

		// Create a getter for the col (which is a function that returns
		// $rows[$col]) and apply it to every row.
		// I just love functional programming. #sorry #notsorry
		return array_map(fn($row) => $row[$col] ?? null, $rows);
	}
	
	/**
	 * Insert a new row into a table in the database
	 * @param table the table to insert the new row in
	 * @param values a hash containing the values to insert. The key each item
	 * in the hash is the column name, the value the column value. Strings
	 * will automatically be escaped (except for special SQL functions)
	 */
	public function insert($table, array $values)
	{
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

		/* Save last insertion table so we can use it in 
			 get_last_insert_id */
		$this->last_insert_table = $table;

		/* Execute query */
		return $this->execute($query, $data);
	}
	
	/**
	 * Get the last insert id (uses currval("<last_table>_id_sec")
	 * This is not done automatically because not every table has a
	 * auto increment (serial) column, and calling it on a table
	 * which has none causes an error to occur.
	 * @return mixed the id of the last inserted row
	 */
	public function get_last_insert_id()
	{
		return $this->query_value(sprintf("SELECT currval('%s_id_seq'::regclass)", $this->last_insert_table));
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
	public function update($table, array $values, $condition)
	{
		// Is there anything to update? Otherwise, hey, easy job!
		if (count($values) == 0)
			return true;

		if (empty($condition))
			throw new InvalidArgumentException('Calling DatabasePDO::update without conditions');

		if ($condition && !is_string($condition))
			throw new InvalidArgumentException('Condition parameter needs to be a string');

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

		$query = sprintf('UPDATE "%s" SET %s WHERE %s', $table, $k, $condition);

		/* Execute query */
		return $this->execute($query, $data);
	}

	/**
	 * Escape a string so it can be used in queries
	 * @s the string to be escaped (not surrounded by quotes)
	 *
	 * @result the escaped string
	 */
	public function escape_string($s)
	{
		return substr($this->resource->quote($s ?? ''), 1, -1);
	}

	/**
	 * Quote the string (including surrounding quotes)
	 */
	public function quote($s)
	{
		return $this->resource->quote($s ?? '');
	}
	
	/**
	 * Escape any type of value (or get an InvalidArgumentException)
	 * @param $value mixed
	 * @return string SQL
	 */
	public function quote_value($value)
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
			return $value ? 'TRUE' : 'FALSE';
		elseif (is_string($value))
			return $this->resource->quote($value);
		elseif (is_array($value))
			return implode(', ', array_map([$this, 'quote_value'], $value));
		else
			throw new InvalidArgumentException('Unsupported datatype ' . gettype($value));
	}

	protected function prepare_value($value, $placeholder, array &$values = [])
	{
		// Raw values
		if ($value === null)
			return 'NULL';
		elseif ($value instanceof DatabaseLiteral)
			return $value->toSQL();
		elseif (is_int($value))
			return sprintf('%d', $value);
		elseif (is_bool($value))
			return $value ? 'TRUE' : 'FALSE';

		// Values that actually fill placeholders
		elseif ($value instanceof DateTime)
			$values[$placeholder] = $value->format('Y-m-d H:i:s');
		elseif (is_string($value))
			$values[$placeholder] = $value;
		elseif (is_resource($value) && get_resource_type($value) === 'stream')
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
	public function delete($table, $condition, array $input_parameters = [])
	{
		if (!$condition)
			throw new RuntimeException('Are you really really sure you want to delete everything?');

		if (!is_string($condition))
			throw new InvalidArgumentException('condition parameter has to be a SQL query string');

		return $this->execute(sprintf('DELETE FROM "%s" WHERE %s', $table, $condition), $input_parameters);
	}

	public function read_blob($data)
	{
		if (!is_resource($data))
			throw new InvalidArgumentException('DatabasePDO::read_blob expected resource as argument');

		return stream_get_contents($data);
	}

	public function write_blob($data)
	{
		if (!is_resource($data))
			throw new InvalidArgumentException('DatabasePDO::write_blob expected resource as argument');

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
		$this->resource->rollBack();
		$this->transaction_counter = 0;
	}
}

<?php

interface Database
{
	public function get_last_error();

	public function query($query, $indices = false);

	public function query_first($query, $indices = false);

	public fucntion query_value($query, $col = 0);

	public function insert($table, array $values);

	public function update($table, array $values, array $conditions);

	public function delete($table, array $conditions);

	public function get_last_insert_id();

	public function get_affected_rows();

	public function escape_string($string);

	public function generate_where(array $conditions);
}

class DatabaseLiteral
{
	protected $literal;

	public function __construct($literal)
	{
		$this->literal = $literal;
	}

	public function toSQL()
	{
		return $this->literal;
	}
}

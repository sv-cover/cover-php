<?php
require_once 'src/framework/data/DataModel.php';

class DataIterConfiguratie extends DataIter
{
	static public function fields()
	{
		return [
			'key',
			'value'
		];
	}
}

/**
  * A class implementing configuration data
  */
class DataModelConfiguratie extends DataModel
{
	private $_cache = array();

	public $dataiter = 'DataIterConfiguratie';

	public function __construct($db)
	{
		parent::__construct($db, 'configuratie', 'key');

		$this->_populate_cache();
	}

	private function _populate_cache()
	{
		$rows = $this->db->query("SELECT key, value FROM configuratie");

		foreach ($rows as $row)
			$this->_cache[$row['key']] = $row['value'];
	}
	
	/**
	  * Get a configuration value
	  * @key the name of the configuration value
	  *
	  * @result the configuration value
	  */
	public function get_value($key, $default = null)
	{
		if (array_key_exists($key, $this->_cache))
			$value = $this->_cache[$key];
		else
			$value = null;
		
		return $value === null ? $default : $value;
	}

	/**
	 * Override DataModel::_insert because that implementation relies on
	 * Database::get_last_insert_id, which won't work on a non-numerical
	 * non-automatic primary key used by the configuratie table.
	 */
	protected function _insert($table, DataIter $iter, $get_id = false)
	{
		parent::_insert($table, $iter, false);
		
		return $get_id ? $iter['key'] : -1;
	}
	
	/**
	  * Set the value of a configuration parameter
	  * @key the name of the configuration parameter
	  * @value the new value of the parameter
	  *
	  * @result void
	  */
	public function set_value($key, $value)
	{
		$this->_cache[$key] = $value;

		// Todo: You cannot set a value to null using this set-up

		if (!is_null($this->get_value($key)))
			$this->db->query_value('UPDATE configuratie SET value = \'' . $this->db->escape_string($value) . '\' WHERE key = \'' . $this->db->escape_string($key) . '\';');
		else
			$this->db->query_value('INSERT INTO configuratie (key, value) VALUES(\'' . $this->db->escape_string($key) . '\', \'' . $this->db->escape_string($value) . '\')');
	}
}

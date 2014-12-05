<?php
	require_once 'include/data/DataModel.php';

	/**
	  * A class implementing configuration data
	  */
	class DataModelConfiguratie extends DataModel
	{
		function __construct($db)
		{
			parent::__construct($db, 'configuratie', 'key');
		}
		
		/**
		  * Get a configuration value
		  * @key the name of the configuration value
		  *
		  * @result the configuration value
		  */
		function get_value($key, $default = null)
		{
			$value = $this->db->query_value('SELECT value
						FROM configuratie
						WHERE key = \'' . $this->db->escape_string($key) . '\'');
						
			return $value === null ? $default : $value;
		}

		/**
		 * Override DataModel::_insert because that implementation relies on
		 * Database::get_last_insert_id, which won't work on a non-numerical
		 * non-automatic primary key used by the configuratie table.
		 */
		function _insert($table, $iter, $getid = false)
		{
			if (!$this->db)
				return false;
			
			$this->db->insert($table, $iter->data, $iter->get_literals());
			
			return $getid ? $key : -1;
		}
		
		/**
		  * Set the value of a configuration parameter
		  * @key the name of the configuration parameter
		  * @value the new value of the parameter
		  *
		  * @result void
		  */
		function set_value($key, $value) {
			if (!is_null($this->get_value($key)))
				$this->db->query_value('UPDATE configuratie SET value = \'' . $this->escape_string($value) . '\' WHERE key = \'' . $this->escape_string($key) . '\';');
			else
				$this->db->query_value('INSERT INTO configuratie (key, value) VALUES(\'' . $this->escape_string($key) . '\', \'' . $this->escape_string($value) . '\')');
		}
	}

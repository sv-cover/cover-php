<?php
	/**
	  * This class provides access to a data row in a #DataModel
	  */
	class DataIter {
		var $model = null; /** The model the iter belongs to */
		var $data = null; /** The data of the iter */
		var $_id = 0; /** The id of the iter */
		var $changes = null; /** Array containing the fields that have changed */
		var $literals = null; /** Array containing the fields that should be used literally */
		var $db = null;
		
		/**
		  * Create a new DataIter
		  * @model the model the iter belongs to
		  * @id the id of the iter
		  * @data the data of the iter (a hashtable)
		  */
		function DataIter($model, $id, $data) {
			$this->model = $model;
			$this->data = $data;
			$this->_id = $id;			
			$this->db = $model->db;
			
			$this->changes = array();
			$this->literals = array();
		}
		
		/**
		  * Get the id of the iter
		  *
		  * @result the id of the iter
		  */
		function get_id() {
			return $this->_id;
		}
		
		/**
		  * Get iter data
		  * @field the data field name
		  *
		  * @result the data in the field
		  */
		function get($field) {
			return $this->data[$field];
		}
		
		/**
		  * Set iter data
		  * @field the data field name
		  * @value the data value
		  */
		function set($field, $value) {
			$index = array_search($field, $this->literals);
			
			if ($index !== false)
				unset($this->literals[$index]);

			/* Return if value hasn't really changed */
			if ($this->data[$field] == $value && $this->_id != -1)
				return;

			/* Add field to changes if it's not already changed */
			if (!in_array($field, $this->changes))
				$this->changes[] = $field;

			/* Store new value */
			$this->data[$field] = $value;
		}
		
		/**
		  * Set literal iter data
		  * @field the data field name
		  * @value the data value
		  */
		function set_literal($field, $value) {
			/* Return if value hasn't really changed */
			if ($this->data[$field] == $value && $this->_id != -1)
				return;

			/* Add field to changes if it's not already changed */
			if (!in_array($field, $this->changes))
				$this->changes[] = $field;
	
			/* Add field to literals */
			if (!in_array($field, $this->literals))
				$this->literals[] = $field;

			/* Store new value */
			$this->data[$field] = $value;
		}
		
		/**
		  * Set iter data for multiple fields
		  * @values a hashtable where keys are the data field names and the 
		  * values are the data values 
		  */
		function set_all($values) {
			foreach ($values as $field => $value)
				$this->set($field, $value);
		}
		
		/**
		  * Process changes up into the model
		  * 
		  * @result true if update was succesful, false otherwise
		  */
		function update() {
			return $this->model->update($this);
		}
		
		/**
		  * Returns whether the iter has been changed
		  *
		  * @result true if the iter has been changed, false otherwise
		  */		
		function has_changes() {
			return (count($this->changes) != 0);
		}
		
		/**
		  * Returns the field names that have been changed
		  *
		  * @result an array with the data field names that have been changed
		  */
		function get_changes() {
			return $this->changes;
		}
		
		/**
		  * Returns the field names and values that have been changed
		  *
		  * @result a hash with the data field names as the keys and data values
		  * as the values
		  */
		function get_changed_values() {
			$changes = array();

			foreach ($this->changes as $change)
				$changes[$change] = $this->data[$change];
			
			return $changes;
		}
		
		/**
		  * Returns the field names of the fields which should be used literally
		  *
		  * @result an array with field names
		  */
		function get_literals() {
			return $this->literals;
		}
		
		function __get($get) {
			return $this->get($get);
		}
		
		function __set($key, $value) {
			return $this->set($key, $value);
		}
	}
?>

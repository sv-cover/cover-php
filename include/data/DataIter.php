<?php
	/**
	  * This class provides access to a data row in a #DataModel
	  */
	class DataIter implements JsonSerializable, ArrayAccess {
		var $model = null; /** The model the iter belongs to */
		var $data = null; /** The data of the iter */
		var $_id = 0; /** The id of the iter */
		var $changes = null; /** Array containing the fields that have changed */
		var $literals = null; /** Array containing the fields that should be used literally */
		var $db = null;
		var $namespace = '';

		/**
		 * Clones a DataIter. Useful for transforming one iter to another.
		 */
		static public function from_iter(DataIter $iter)
		{
			$class_name = get_called_class();
			$instance = new $class_name($iter->model, $iter->get_id(), $iter->data, $iter->namespace);
			return $instance;
		}

		static public function is_same(DataIter $a, DataIter $b)
		{
			return $a->get_id() == $b->get_id();
		}
		
		/**
		  * Create a new DataIter
		  * @model the model the iter belongs to
		  * @id the id of the iter
		  * @data the data of the iter (a hashtable)
		  */
		public function __construct(DataModel $model = null, $id, $data, $namespace = '') {
			$this->model = $model;
			$this->data = $data;
			$this->_id = $id;			
			$this->db = $model ? $model->db : null;
			$this->namespace = $namespace;
			
			$this->changes = array();
			$this->literals = array();
		}

		public function __debugInfo()
		{
			return [
				'_id' => $this->_id,
				'namespace' => $this->namespace,
				'data' => $this->data
			];
		}

		public function model()
		{
			return $this->model;
		}
		
		/**
		  * Get the id of the iter
		  *
		  * @result the id of the iter
		  */
		public function get_id()
		{
			return $this->_id;
		}

		public function has_id()
		{
			return $this->_id !== null && $this->_id !== -1;
		}

		public function set_id($id)
		{
			$this->_id = $id;
		}

		/**
		 * Check whether there is some value set for a field.
		 * @return boolean
		 */
		public function has($field)
		{
			return isset($this->data[$this->namespace . $field]);
		}

		/**
		 * Check whether this iter has a field named $field.
		 * @return boolean
		 */
		public function has_field($field)
		{
			return array_key_exists($this->namespace . $field, $this->data);
		}

		public function has_getter($field)
		{
			return method_exists($this, 'get_' . $field);
		}
		
		/**
		  * Get iter data
		  * @field the data field name
		  *
		  * @result the data in the field
		  */
		public function get($field)
		{
			if ($field == 'id')
				return $this->get_id();

			if ($this->has_field($field))
				return $this->data[$this->namespace . $field];
			
			if ($this->has_getter($field))
				return call_user_func(array($this, 'get_' . $field));

			trigger_error('DataIter has no field named ' . $field, E_USER_WARNING);
			return null;
		}
		
		/**
		  * Set iter data
		  * @field the data field name
		  * @value the data value
		  */
		public function set($field, $value)
		{
			/* Remove the literal if set at the moment */
			if (($index = array_search($field, $this->literals)) !== false)
				unset($this->literals[$index]);

			/* Return if value hasn't really changed */
			if (isset($this->data[$this->namespace . $field])
				&& $this->data[$this->namespace . $field] === $value
				&& $this->_id != -1)
				return;

			/* Add field to changes if it's not already changed */
			if (!in_array($field, $this->changes))
				$this->changes[] = $field;

			/* Store new value */
			$this->data[$this->namespace . $field] = $value;
		}
		
		/**
		  * Set literal iter data
		  * @field the data field name
		  * @value the data value
		  */
		public function set_literal($field, $value)
		{
			/* Return if value hasn't really changed */
			if ($this->data[$this->namespace . $field] == $value && $this->_id != -1)
				return;

			/* Add field to changes if it's not already changed */
			if (!in_array($field, $this->changes))
				$this->changes[] = $field;
	
			/* Add field to literals */
			if (!in_array($field, $this->literals))
				$this->literals[] = $field;

			/* Store new value */
			$this->data[$this->namespace . $field] = $value;
		}
		
		/**
		  * Set iter data for multiple fields
		  * @values a hashtable where keys are the data field names and the 
		  * values are the data values 
		  */
		public function set_all($values) {
			foreach ($values as $field => $value)
				$this->set($field, $value);
		}

		public function unset_field($field)
		{
			// Remove it from the data
			unset($this->data[$this->namespace . $field]);

			// Remove the literal if set at the moment
			if (($index = array_search($field, $this->literals)) !== false)
				unset($this->literals[$index]);
		}
		
		/**
		  * Process changes up into the model
		  * 
		  * @result true if update was succesful, false otherwise
		  */
		public function update() {
			return $this->model->update($this);
		}
		
		/**
		  * Returns whether the iter has been changed
		  *
		  * @result true if the iter has been changed, false otherwise
		  */		
		public function has_changes() {
			return (count($this->changes) != 0);
		}
		
		/**
		  * Returns the field names that have been changed
		  *
		  * @result an array with the data field names that have been changed
		  */
		public function get_changes() {
			return $this->changes;
		}
		
		/**
		  * Returns the field names and values that have been changed
		  *
		  * @result a hash with the data field names as the keys and data values
		  * as the values
		  */
		public function get_changed_values() {
			$changes = array();

			foreach ($this->changes as $change)
				$changes[$change] = $this->data[$this->namespace . $change];
			
			return $changes;
		}

		public function getIter($field, $type = 'DataIter')
		{
			$id = isset($this->data[$field . '__id'])
				? $this->data[$field . '__id']
				: -1;
			
			$class = new ReflectionClass($type);
			return $class->newInstance(null, $id, $this->data, $field . '__');
		}
		
		/**
		  * Returns the field names of the fields which should be used literally
		  *
		  * @result an array with field names
		  */
		public function get_literals() {
			return $this->literals;
		}
		
		public function __get($get)
		{
			trigger_error('Propery access is deprecated. Use Array access or DataIter::get', E_USER_NOTICE);
			return $this->get($get);
		}
		
		public function __set($key, $value)
		{
			trigger_error('Propery access is deprecated. Use Array access or DataIter::set', E_USER_NOTICE);
			return $this->set($key, $value);
		}

		public function __unset($key)
		{
			trigger_error('Propery access is deprecated. Use Array access or DataIter::unset_field', E_USER_NOTICE);
			return $this->unset_field($key);
		}

		public function __isset($key)
		{
			return $this->has_field($key) || $this->has_getter($key);
		}

		/* ArrayAccess */
		public function offsetGet($offset)
		{
			return $this->get($offset);
		}

		public function offsetSet($offset, $value)
		{
			return $this->set($offset, $value);
		}

		public function offsetExists($offset)
		{
			return $this->has_field($offset);
		}

		public function offsetUnset($offset)
		{
			return $this->unset_field($key);
		}

		public function jsonSerialize()
		{
			return $this->data;
		}
	}

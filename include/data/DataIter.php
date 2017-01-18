<?php
	/**
	  * This class provides access to a data row in a #DataModel
	  */
	abstract class DataIter implements JsonSerializable, ArrayAccess
	{
		protected $model = null; /** The model the iter belongs to */
		
		public $data = null; /** The data of the iter */
		
		private $_id = 0; /** The id of the iter */
		
		private $_changes = []; /** Array containing the fields that have changed */

		private $_getter_cache = [];
		
		protected $db = null;

		/**
		 * Returns an instance of the DataModel that can fetch these
		 * specific DataIter types.
		 */
		static public function model()
		{
			$class_name = get_called_class();

			return get_model(preg_replace('{^DataIter}', 'DataModel', $class_name));
		}

		abstract static public function fields();

		/**
		 * Clones a DataIter. Useful for transforming one iter to another.
		 */
		static public function from_iter(DataIter $iter)
		{
			$class_name = get_called_class();
			$instance = new $class_name($iter->model, $iter->get_id(), $iter->data);
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
		public function __construct(DataModel $model = null, $id, $data, array $preseed = array())
		{
			$this->model = $model;
			$this->data = $data;
			$this->_id = $id;			
			$this->db = $model ? $model->db : null;

			$this->_getter_cache = $preseed;
			
			$this->_changes = array();
		}

		public function __debugInfo()
		{
			return [
				'_id' => $this->_id,
				'data' => $this->data
			];
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

		public function has($field)
		{
			return $this->has_field($field) || $this->has_value($field);
		}

		/**
		 * Check whether there is some value set for a field.
		 * @return boolean
		 */
		public function has_value($field)
		{
			return isset($this->data[$field]);
		}

		/**
		 * Check whether this iter has a field named $field.
		 * @return boolean
		 */
		public function has_field($field)
		{
			return in_array($field, static::fields());
		}

		public function has_getter($field)
		{
			return method_exists($this, 'get_' . $field);
		}

		public function has_setter($field)
		{
			return method_exists($this, 'set_'. $field);
		}
		
		/**
		  * Get iter data
		  * @field the data field name
		  *
		  * @result the data in the field
		  */
		public function get($field)
		{
			// ID is just special
			if ($field == 'id')
				return $this->get_id();

			// We have the field in our data array
			if ($this->has_value($field))
				return $this->data[$field];
			
			// The field exists, we just don't have data for it
			if ($this->has_field($field))
				return null;
			
			// We don't have it as a table field, but we do have a getter
			if ($this->has_getter($field)) {
				if (isset($this->_getter_cache[$field]))
					return $this->_getter_cache[$field];
				else
					return $this->_getter_cache[$field] = call_user_func(array($this, 'get_' . $field));
			}

			// Nope.
			trigger_error(get_class($this) . ' has no field named ' . $field, E_USER_WARNING);
			return null;
		}
		
		/**
		  * Set iter data
		  * @field the data field name
		  * @value the data value
		  */
		public function set($field, $value)
		{
			if ($field == 'id')
				throw new InvalidArgumentException('id field can only be altered using DataIter::set_id');

			/* if there is a setter for this field, delegate to that one */
			if ($this->has_setter($field))
				return call_user_func_array([$this, 'set_' . $field], [$value]);

			/* Return if value hasn't really changed */
			if (isset($this->data[$field])
				&& $this->data[$field] === $value
				&& $this->_id != -1)
				return;

			/* Add field to changes if it's not already changed */
			if (!in_array($field, $this->_changes))
				$this->_changes[] = $field;

			/* Store new value */
			$this->data[$field] = $value;
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
			unset($this->data[$field]);
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
			return (count($this->_changes) != 0);
		}
		
		/**
		  * Returns the field names that have been changed
		  *
		  * @result an array with the data field names that have been changed
		  */
		public function get_changes() {
			return $this->_changes;
		}
		
		/**
		  * Returns the field names and values that have been changed
		  *
		  * @result a hash with the data field names as the keys and data values
		  * as the values
		  */
		public function get_changed_values() {
			return array_combine(
				$this->_changes,
				array_map(function($key) {
					return $this->data[$key];
				}, $this->_changes)
			);
		}

		/**
		 * Return a dataiter for all fields queried from a certain subresource.
		 * @return instance of <$type extends DataIter>
		 */
		protected function getIter($field, $type)
		{
			$id = isset($this->data[$field . '__id'])
				? $this->data[$field . '__id']
				: -1;

			// Call DataIter::model() on the specific DataIter type
			$model = call_user_func([$type, 'model']);

			$row = array();

			foreach ($this->data as $k => $v)
				if (strpos($k, $field . '__') === 0)
					$row[substr($k, strlen($field) + 2)] = $v;

			return $model->new_iter($row);
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
			return $this->has_field($offset) || $this->has_value($offset) || $this->has_getter($offset);
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

	class GenericDataIter extends DataIter
	{
		static public function fields()
		{
			return [];
		}

		public function has_field($field)
		{
			return isset($this->data[$field]);
		}
	}
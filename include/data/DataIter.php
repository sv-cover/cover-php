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

		private $_getter_stack = [];
		
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
		 * Defines the set of rules applied during validation.
		 */
		static public function rules()
		{
			$rules = [];

			foreach (static::fields() as $field)
				$rules[$field] = [];

			return $rules;
		}

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
		public function __construct(DataModel $model = null, $id, $data, array $seed = array())
		{
			$this->model = $model;
			$this->data = $data;
			$this->_id = $id;
			$this->db = $model ? $model->db : null;

			$this->_getter_cache = array_map(function($entry) { return [$entry, []]; }, $seed);
			
			$this->_changes = array();
		}

		public function __debugInfo()
		{
			return [
				'_id' => $this->_id,
				'_getter_cache' => array_map(function($entry) {
					return [
						'value' => is_object($entry[0]) ? "instance of " . get_class($entry[0]) : gettype($entry[0]), // Don't go too deep
						'depend_on' => $entry[1] // list of dependencies
					];
				}, $this->_getter_cache),
				'data' => $this->data,
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
			return static::has_field($field) || $this->has_value($field);
		}

		/**
		 * Check whether there is some value set for a field.
		 * @return boolean
		 */
		public function has_value($field)
		{
			return array_key_exists($field, $this->data);
		}

		/**
		 * Check whether this iter has a field named $field.
		 * @return boolean
		 */
		static public function has_field($field)
		{
			return in_array($field, static::fields());
		}

		static public function has_getter($field)
		{
			return method_exists(get_called_class(), 'get_' . $field);
		}

		static public function has_setter($field)
		{
			return method_exists(get_called_class(), 'set_'. $field);
		}

		public function call_getter($field)
		{
			if (!isset($this->_getter_cache[$field]))
			{
				foreach ($this->_getter_stack as $frame)
					if ($frame['field'] == $field)
						throw new Exception('Infinite loop while trying to calculate ' . get_class($this) . '::' . $field);

				array_push($this->_getter_stack, ['field' => $field, 'dependencies' => []]);

				$value = $this->call_getter_nocache($field);

				$frame = array_pop($this->_getter_stack);

				assert($frame['field'] == $field);
				
				$this->_getter_cache[$field] = [$value, array_unique($frame['dependencies'])];
			}

			return $this->_getter_cache[$field][0];
		}

		public function call_getter_nocache($field)
		{
			return call_user_func([$this, 'get_' . $field]);
		}
		
		public function call_setter($field, $value)
		{
			return call_user_func([$this, 'set_' . $field], $value);
		}

		private function _clear_getter_cache($changed_field)
		{
			$to_clear = [];

			foreach ($this->_getter_cache as $field => list($value, $dependencies))
				if ($field == $changed_field || in_array($changed_field, $dependencies))
					$to_clear[] = $field;

			foreach ($to_clear as $field)
				unset($this->_getter_cache[$field]);
		}

		/**
		  * Get iter data
		  * @field the data field name
		  *
		  * @result the data in the field
		  */
		public function get($field)
		{
			// Track getter dependencies
			for ($i = 0; $i < count($this->_getter_stack); ++$i)
				$this->_getter_stack[$i]['dependencies'][] = $field;

			// ID is just special
			if ($field == 'id')
				return $this->get_id();

			// Do we have a getter? Use that one.
			if (static::has_getter($field))
				return $this->call_getter($field);

			// We have the field in our data array
			if ($this->has_value($field))
				return $this->data[$field];
			
			// The field exists, we just don't have data for it
			if (static::has_field($field))
				return null;
			
			// Nope.
			trigger_error(get_class($this) . ' has no field named ' . $field, E_USER_WARNING);
			return null;
		}
		
		/**
		  * Set iter data
		  * @param string $field the data field name
		  * @param mixed $value the data value
		  */
		public function set($field, $value)
		{
			if ($field == 'id')
				throw new InvalidArgumentException('id field can only be altered using DataIter::set_id');

			$this->_clear_getter_cache($field);

			/* Add field to changes if it's not already changed */
			$this->mark_changed($field);

			/* if there is a setter for this field, delegate to that one */
			if (static::has_setter($field))
				return $this->call_setter($field, $value);

			/* Return if value hasn't really changed */
			if (isset($this->data[$field])
				&& $this->data[$field] === $value
				&& $this->_id != -1)
				return;

			/* Store new value */
			$this->data[$field] = $value;
		}

		/**
		  * Set iter data for multiple fields
		  * @param array $values a hashtable where keys are the data field names and the 
		  * values are the data values 
		  */
		public function set_all(array $values) {
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
		 * Mark a data field as changed.
		 * @param string $field field
		 */
		protected function mark_changed($field) {
			if (static::has_field($field) && !in_array($field, $this->_changes))
				$this->_changes[] = $field;
		}
		
		/**
		  * Returns the field names that have been changed
		  *
		  * @result an array with the data field names that have been changed
		  */
		public function changed_fields() {
			return $this->_changes;
		}
		
		/**
		  * Returns the field names and values that have been changed
		  *
		  * @result a hash with the data field names as the keys and data values
		  * as the values
		  */
		public function changed_values() {
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
			// Call DataIter::model() on the specific DataIter type
			$model = call_user_func([$type, 'model']);

			$row = array();

			foreach ($this->data as $k => $v)
				if (strpos($k, $field . '__') === 0)
					$row[substr($k, strlen($field) + 2)] = $v;

			return $model->new_iter($row, $type);
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
			return static::has_field($offset) || static::has_getter($offset) || $this->has_value($offset);
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
	}
<?php

require_once 'include/data/DataModel.php';

interface SignUpFieldType
{
	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(array $post_data, &$error);

	// Render the form field
	public function render($renderer, $value, $error);

	public function process_configuration(array $post_data, ErrorSet $errors);

	public function render_configuration($renderer, ErrorSet $errors);

	// Store the current configuration as an associative array
	public function configuration();

	// Export it to a CSV (as an array with column => text value)
	public function export($value);
}

class ErrorSet implements ArrayAccess
{
	public function __construct(array $namespace = [], &$errors = null)
	{
		$this->namespace = $namespace;

		$this->errors =& $errors ? $errors : [];
	}

	protected function key($field)
	{
		return implode('.', array_merge($this->namespace, [$field]));
	}

	public function namespace($namespace)
	{
		return new ErrorSet(array_merge($this->namespace, [$namespace]), $this->errors);
	}

	public function offsetSet($field, $error)
	{
		$this->errors[$this->key($field)] = $error;
	}

	public function offsetGet($field)
	{
		$key = $this->key($field);
		return isset($this->errors[$key])
			? $this->errors[$key]
			: null;
	}

	public function offsetExists($field)
	{
		return isset($this->errors[$this->key($field)]);
	}

	public function offsetUnset($field)
	{
		unset($this->errors[$this->key($field)]);
	}
}

class SignUpValidationError
{
	public function __construct($message)
	{
		$this->message = $message;
	}
}

class TextField implements SignUpFieldType
{
	protected $name;
	
	protected $label;

	protected $required;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? $name;

		$this->required = $configuration['required'] ?? false;
	}

	public function configuration()
	{
		return [
			'label' => $this->label,
			'required' => (bool) $this->required
		];
	}

	public function process(array $post_data, &$error)
	{
		$value = trim($post_data[$this->name] ?? '');

		if ($this->required && $value == '')
			$error = __('Value required');

		return $value;
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/textfield.twig', [
			'name' => $this->name,
			'data' => [$this->name => $value],
			'configuration' => $this->configuration(),
			'errors' => $error ? [$this->name => $error] : []
		]);
	}

	public function process_configuration(array $post_data, ErrorSet $errors)
	{
		$this->label = strval($post_data['label']);
		$this->required = !empty($post_data['required']);
		return true;
	}

	public function render_configuration($renderer, ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/textfield.twig', [
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function export($value)
	{
		return [$this->name => $value];
	}
}

class Checkbox implements SignUpFieldType
{
	protected $description;

	protected $required;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->description = $configuration['description'] ?? '';
	}

	public function configuration()
	{
		return [
			'required' => $this->required,
			'description' => $this->description
		];
	}

	public function process(array $post_data, &$error)
	{
		$checked = isset($post_data[$this->name]) && $post_data[$this->name] == 'yes';

		if ($this->required && !$checked)
			$error = 'Required';

		return $checked ? '0' : '1';
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/checkbox.twig', [
			'name' => $this->name,
			'data' => [$this->name => (bool) $value],
			'configuration' => $this->configuration(),
			'errors' => $error ? [$this->name => $error] : []
		]);
	}

	public function process_configuration(array $post_data, ErrorSet $errors)
	{
		$this->description = strval($post_data['description']);
		$this->required = !empty($post_data['required']);
		return true;
	}

	public function render_configuration($renderer, ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/checkbox.twig', [
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function export($value)
	{
		return [$this->name => $value ? 0 : 1];
	}
}

class DataIterSignUpField extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'form_id',
			'name',
			'type',
			'properties',
			'sort_index'
		];
	}

	public function get_properties()
	{
		try {
			return json_decode($this->data['properties'], true);
		} catch (Exception $e) {
			return [];
		}
	}

	public function set_properties(array $properties)
	{
		$this->data['properties'] = json_encode($properties);
	}

	public function get_form()
	{
		return get_model('DataModelSignUpForm')->get_iter($this['form_id']);
	}

	public function process(array $post_data, &$error)
	{
		return $this->widget()->process($post_data, $error);
	}

	public function render($renderer, DataIterSignUpEntry $entry)
	{
		return $this->widget()->render($renderer, $entry->value_for_field($this), $entry->error_for_field($this));
	}

	public function process_configuration(array $post_data, ErrorSet $errors)
	{
		$widget = $this->widget();

		if (!$widget->process_configuration($post_data, $errors))
			return false;
		
		$this['properties'] = $widget->configuration();

		return true;
	}

	public function render_configuration($renderer, ErrorSet $errors)
	{
		return $this->widget()->render_configuration($renderer, $errors);
	}

	public function export(DataIterSignUpEntry $entry)
	{
		return $this->widget()->export($entry->value_for_field($this));
	}

	private function widget()
	{
		return get_model('DataModelSignUpField')->instantiate($this['type'], $this['name'], $this['properties']);
	}
}

class DataModelSignUpField extends DataModel
{
	public $dataiter = 'DataIterSignUpField';

	public $field_types = [
		'checkbox' => Checkbox::class,
		'text' => TextField::class
	];

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_fields');
	}

	public function update_order(array $fields)
	{
		$values = [];

		foreach (array_values($fields) as $n => $field)
			$values[] = sprintf('(%d, %d)', $field['id'], $n);

		$sql_values = implode(', ', $values);
		
		$this->db->query("
			UPDATE {$this->table} as t 
			SET sort_index = index
			FROM (VALUES $sql_values) as v(id, index)
			WHERE v.id = t.id");
	}

	public function instantiate($type, string $name, array $properties)
	{
		$class = new ReflectionClass($this->field_types[$type]);
		return $class->newInstance($name, $properties);
	}

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY sort_index ASC NULLS LAST';
	}
}
<?php

require_once 'include/data/DataModel.php';

interface SignUpFieldType
{
	// Store the current configuration as an associative array
	public function serialize();

	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(array $post_data);

	// Render the form field
	public function render();

	// Export it to a CSV (as an array with column => text value)
	public function export();
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
	protected $required;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;
	}

	public function serialize()
	{
		return ['required' => $this->required];
	}

	public function process(array $post_data)
	{
		$this->value = trim($post_data[$this->name] ?? '');

		if ($this->required && $this->value == '')
			return new SignUpValidationError(__('Value required'));

		return $this->value;
	}

	public function render()
	{
		return sprintf('<input type="text" name="%s" value="%s"%s>',
			$this->name,
			$this->value,
			$this->required ? ' required' : '');
	}

	public function export()
	{
		return [$this->name => $this->value];
	}
}

class Checkbox implements SignUpFieldType
{
	protected $description;

	protected $required;

	protected $checked;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->description = $configuration['description'] ?? '';
	}

	public function serialize()
	{
		return [
			'required' => (bool) $this->required,
			'description' => (string) $this->description
		];
	}

	public function process(array $post_data)
	{
		if (isset($post_data[$this->name]))
			$this->checked = $post_data[$this->name];

		if ($this->required && !$this->checked)
			return new SignUpValidationError('Required');

		return $this->checked ? '0' : '1';
	}

	public function render()
	{
		return sprintf('<input type="checkbox" id="%s_field" name="%1$s" value="on"%s%s><label id="%1$s_field">%s</field>',
			$this->name,
			$this->checked ? ' checked' : '',
			$this->required ? ' required' : '',
			$this->description);
	}

	public function export()
	{
		return [$this->name => $this->value ? 0 : 1];
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

	public function get_widget()
	{
		return get_model('DataModelSignUpField')->instantiate($this['type'], $this['name'], $this['properties']);
	}

	public function process($post_value, &$error)
	{

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

	public function instantiate($type, string $name, array $properties)
	{
		$class = new ReflectionClass($this->field_types[$type]);
		return $class->newInstance($name, $properties);
	}
}
<?php

require_once 'include/data/DataModel.php';

interface SignUpFieldType
{
	// The name of this type as stored in the database (string)
	public function type();

	// Store the current configuration as an associative array
	public function serialze();

	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(array $post_data);

	// Render the form field
	public function render();

	// Export it to a CSV (as an array with column => text value)
	public function export();
}

class Error
{
	public function __construct($message)
	{
		$this->message = $message;
	}
}

class TextField implements SignUpFieldType
{
	protected $required;

	public function __construct($name, array $configuration, $value)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->value = $value;
	}

	public function serialze()
	{
		return ['required' => $this->required];
	}

	public function process(array $post_data)
	{
		$this->value = trim($post_data[$this->name] ?? '');

		if ($this->required && $this->value == '')
			return new Error(__('Value required'));

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

	public function __construct($name, array $configuration, $value)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->description = $configuration['description'] ?? '';

		$this->checked = (bool) $value;
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
			return new Error('Required');

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

	public function get_form()
	{
		return get_model('DataModelSignUpForm')->get_iter($this['form_id']);
	}

	public function process($post_value, &$error)
	{

	}
}

class DataModelSignUpField extends DataModel
{
	public $dataiter = 'DataIterSignUpField';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_fields');
	}
}
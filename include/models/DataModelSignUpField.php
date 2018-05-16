<?php

require_once 'include/data/DataModel.php';

interface SignUpFieldType
{
	// Store the current configuration as an associative array
	public function options();

	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(array $post_data, &$error);

	// Render the form field
	public function render($renderer, $value, $error);

	// Export it to a CSV (as an array with column => text value)
	public function export($value);
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
	public $name;
	
	public $label;

	public $required;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? $name;

		$this->required = $configuration['required'] ?? false;
	}

	public function options()
	{
		return [
			'label' => [
				'type' => 'string',
				'label' => __('Label')
			],
			'required' => [
				'type' => 'boolean',
				'label' => __('Verplicht in te vullen')
			]
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
		return $renderer->render('@form/textfield.twig', [
			'field' => $this,
			'data' => [$this->name => $value],
			'errors' => $error ? [$this->name => $error] : []
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

	public function options()
	{
		return [
			'required' => [
				'type' => 'boolean',
				'label' => __('Verplicht om aan te vinken')
			],
			'description' => [
				'type' => 'string',
				'label' => __('Omschrijving bij checkbox')
			]
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
		return $renderer->render('@form/checkbox.twig', [
			'field' => $this,
			'data' => [$this->name => (bool) $value],
			'errors' => $error ? [$this->name => $error] : []
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

	public function instantiate($type, string $name, array $properties)
	{
		$class = new ReflectionClass($this->field_types[$type]);
		return $class->newInstance($name, $properties);
	}

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY sort_index ASC';
	}
}
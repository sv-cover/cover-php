<?php

require_once 'include/data/DataModel.php';

interface SignUpFieldType
{
	// Store the current configuration as an associative array
	public function options();

	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, return an error.
	public function process(array $post_data);

	// Render the form field
	public function render($renderer);

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
	public $name;
	
	public $label;

	public $required;

	public $value;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? $name;

		$this->required = $configuration['required'] ?? false;

		$this->value = '';
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

	public function process(array $post_data)
	{
		$this->value = trim($post_data[$this->name] ?? '');

		if ($this->required && $this->value == '')
			return new SignUpValidationError(__('Value required'));

		return $this->value;
	}

	public function render($renderer)
	{
		return $renderer->render('@form/textfield.twig', ['field' => $this]);
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

		$this->checked = false;
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

	public function process(array $post_data)
	{
		if (isset($post_data[$this->name]))
			$this->checked = $post_data[$this->name];

		if ($this->required && !$this->checked)
			return new SignUpValidationError('Required');

		return $this->checked ? '0' : '1';
	}

	public function render($renderer)
	{
		return $renderer->render('@form/checkbox.twig', ['field' => $this]);
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

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY sort_index ASC';
	}
}
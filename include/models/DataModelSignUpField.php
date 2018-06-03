<?php

require_once 'include/data/DataModel.php';
require_once 'include/fields.php';

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

	public function configure($callback)
	{
		$widget = $this->widget();
		$callback($widget);
		$this['properties'] = $widget->configuration();
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
		'text' => [
			'class' => \fields\Text::class,
			'label' => 'Text field'
		],
		'checkbox' => [
			'class' => \fields\Checkbox::class,
			'label' => 'Checkbox'
		],
		'choice' => [
			'class' => \fields\Choice::class,
			'label' => 'Multiple choice'
		],
		'address' => [
			'class' => \fields\Address::class,
			'label' => 'Address field'
		],
		'bankaccount' => [
			'class' => \fields\BankAccount::class,
			'label' => 'Bank account field'
		],
		'editable' => [
			'class' => \fields\Editable::class,
			'label' => 'Heading or text (layout)'
		]
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
		$class = new ReflectionClass($this->field_types[$type]['class']);
		return $class->newInstance($name, $properties);
	}

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY sort_index ASC NULLS LAST';
	}
}
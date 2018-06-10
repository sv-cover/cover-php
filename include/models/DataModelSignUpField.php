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
			'sort_index',
			'deleted'
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
		// First try to get the value for the field
		$value = $entry->value_for_field($this, null);

		// If that wasn't set, try to set it based on the entry's associated member
		if ($value === null && $entry['member'])
			$value = $this->widget()->suggest($entry['member']);

		// Now render it
		return $this->widget()->render($renderer, $value, $entry->error_for_field($this));
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

	public function export(DataIterSignUpEntry $entry = null)
	{
		return $this->widget()->export($entry ? $entry->value_for_field($this) : null);
	}

	private function widget()
	{
		return get_model('DataModelSignUpField')->instantiate($this['type'], $this['name'], $this['properties']);
	}
}

class DataModelSignUpField extends DataModel
{
	public $dataiter = 'DataIterSignUpField';

	public $field_types;

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_fields');

		$this->field_types = [
			'text' => [
				'class' => \fields\Text::class,
				'label' => __('Tekstveld')
			],
			'checkbox' => [
				'class' => \fields\Checkbox::class,
				'label' => __('Vinkje')
			],
			'choice' => [
				'class' => \fields\Choice::class,
				'label' => __('Meerkeuzevraag')
			],
			'name' => [
				'class' => \fields\Name::class,
				'label' => __('Voor- en achternaam')
			],
			'address' => [
				'class' => \fields\Address::class,
				'label' => __('Adresveld')
			],
			'bankaccount' => [
				'class' => \fields\BankAccount::class,
				'label' => __('Rekeningnummer')
			],
			'editable' => [
				'class' => \fields\Editable::class,
				'label' => __('Titels en tekst (lay-out)')
			]
		];
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

	public function find($conditions)
	{
		if (is_array($conditions) && !isset($conditions['deleted']))
			$conditions['deleted'] = false;

		return parent::find($conditions);
	}

	public function insert(DataIter $iter)
	{
		if ($iter['sort_index'] === null)
			$iter['sort_index'] = $this->_next_sort_index($iter['form_id']);

		return parent::insert($iter);
	}

	private function _next_sort_index($form_id)
	{
		return $this->db->query_value("
			SELECT
				COALESCE(MAX(sort_index) + 1, 0)
			FROM
				{$this->table}
			WHERE
				form_id = :form_id
			", [':form_id' => $form_id]);
	}

	public function delete(DataIter $iter)
	{
		$iter['deleted'] = true;
		$this->update($iter);
	}

	public function restore(DataIter $iter)
	{
		$iter['deleted'] = false;
		$this->update($iter);
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
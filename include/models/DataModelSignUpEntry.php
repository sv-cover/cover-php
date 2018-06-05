<?php

require_once 'include/data/DataModel.php';

class DataIterSignUpEntry extends DataIter
{
	public $errors = [];

	static public function fields()
	{
		return [
			'id',
			'form_id',
			'member_id',
			'created_on',
		];
	}

	public function get_form()
	{
		return get_model('DataModelSignUpForm')->get_iter($this['form_id']);
	}

	public function get_member()
	{
		return get_model('DataModelMember')->get_iter($this['member_id']);
	}

	public function set_values(array $field_values)
	{
		$this->data['values'] = array_filter($field_values, function($value) { return $value !== null; });
	}

	public function get_values()
	{
		if (isset($this->data['values']))
			return $this->data['values'];

		if (!$this['id'])
			return [];

		return $this->model->get_values($this);	
	}

	public function get_values_by_name()
	{
		$fields_by_id = array_combine(
			array_select($this['form']['fields'], 'id'),
			array_values($this['form']['fields']));

		$values = $this['values'];

		return array_combine(
			array_map(function($id) use ($fields_by_id) {
				return $fields_by_id[$id];
			}, array_keys($values)),
			array_values($values));
	}

	public function value_for_field(DataIterSignUpField $field, $default = null)
	{
		return $this['values'][$field['id']] ?? $default;
	}

	public function error_for_field(DataIterSignUpField $field)
	{
		return $this->errors[$field['id']] ?? null;
	}

	public function process(array $post_data)
	{
		$this->errors = [];

		$field_values = [];

		foreach ($this['form']['fields'] as $field)
			$field_values[$field['id']] = $field->process($post_data, $this->errors[$field['id']]);

		$this['values'] = $field_values;

		return count(array_filter($this->errors)) == 0;
	}

	public function export()
	{
		$row = [
			'member' => member_full_name($this['member'], IGNORE_PRIVACY),
			'created_on' => $this['created_on']
		];

		foreach ($this['form']['fields'] as $field)
			$row = array_merge($row, $field->export($this));

		return $row;
	}
}

class DataModelSignUpEntry extends DataModel
{
	public $dataiter = 'DataIterSignUpEntry';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_entries');
	}

	public function insert(DataIter $iter)
	{
		$out = parent::insert($iter);
		$this->_save_values($iter);
		return $out;
	}

	public function update(DataIter $iter)
	{
		$out = parent::update($iter);
		$this->_save_values($iter);
		return $out;
	}

	public function get_values(DataIter $iter)
	{
		$rows = $this->db->query("SELECT field_id, value FROM sign_up_entry_values WHERE entry_id = :id", false, [':id' => $iter['id']]);

		return array_combine(
			array_select($rows, 'field_id'),
			array_select($rows, 'value'));
	}

	private function _save_values(DataIter $iter)
	{
		// If the iter did not change the values, ignore this call
		if (!isset($iter->data['values']))
			return;

		if (!$iter->has_id())
			throw new LogicException('_save_values on iter without id');

		$this->db->beginTransaction();

		// Delete the old values
		$this->db->delete('sign_up_entry_values', 'entry_id = :id', [':id' => $iter['id']]);

		// Insert the new values
		foreach ($iter['values'] as $field_id => $value)
			$this->db->insert('sign_up_entry_values', [
				'entry_id' => $iter['id'],
				'field_id' => $field_id,
				'value' => $value
			]);

		$this->db->commit();
	}
}
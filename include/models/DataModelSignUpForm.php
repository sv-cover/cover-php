<?php

require_once 'include/data/DataModel.php';

class DataIterSignUpForm extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'committee_id',
			'created_on',
			'closed_on',
			'agenda_id'
		];
	}

	static public function rules()
	{
		return [
			'committee_id' => [
				'validate' => ['committee']
			],
			'created_on' => [
				'default' => function() {
					return new DateTime('now');
				},
				'validate' => ['datetime']
			],
			'closed_on' => [
				'clean' => function($value) {
					return $value ? $value : null;
				},
				'validate' => ['datetime']
			],
			'agenda_id' => [
				'clean' => function($value) {
					return $value ? intval($value) : null;
				},
				'validate' => [
					function($agenda_id, $field, $iter, $data) {
						// Null is fine
						if ($agenda_id === null)
							return true;

						// But if it is filled in, it has to be an activity organized by the
						// committee that owns this form.
						$committee_id = $data['committee_id'] ?? $iter['committee_id'];

						if ($committee_id === null)
							return false;

						$agenda_item = get_model('DataModelAgenda')->get_iter($agenda_id);

						return $agenda_item['committee_id'] == $committee_id;
					}
				]
			]
		];
	}

	public function get_fields()
	{
		return get_model('DataModelSignUpField')->find(['form_id' => $this['id']]);
	}

	public function get_entries()
	{
		return get_model('DataModelSignUpEntry')->find(['form_id' => $this['id']]);
	}

	public function get_agenda_item()
	{
		return $this['agenda_id'] ? get_model('DataModelAgenda')->get_iter($this['agenda_id']) : null;
	}

	public function get_description()
	{
		return sprintf('Sign-up form #%d', $this['id']);
	}

	public function get_signup_count()
	{
		return $this->data['signup_count'] ?? count($this['entries']);
	}

	public function is_open()
	{
		if (!$this['closed_on'])
			return true;

		if (new DateTime($this['closed_on']) > new DateTime())
			return true;

		return false;
	}

	public function new_entry(DataIterMember $member)
	{
		return get_model('DataModelSignUpEntry')->new_iter([
			'form_id' => $this['id'],
			'member_id' => $member['id'],
			'signed_up_on' => date('Y-m-d H:i:s')
		]);
	}

	public function get_entries_for_member(DataIterMember $member)
	{
		return get_model('DataModelSignUpEntry')->find([
			'form_id' => $this['id'],
			'member_id' => $member['id']
		]);
	}

	public function new_field($type, callable $configure_callback = null)
	{
		$model = get_model('DataModelSignUpField');

		if (!isset($model->field_types[$type]))
			throw new InvalidArgumentException('Unknown form field type');

		$iter = $model->new_iter([
			'form_id' => $this['id'],
			'name' => uniqid(), // for now
			'type' => $type,
			'properties' => '{}'
		]);

		if ($configure_callback)
			$iter->configure($configure_callback);

		return $iter;
	}
}

class DataModelSignUpForm extends DataModel
{
	public $dataiter = 'DataIterSignUpForm';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_forms');
	}

	protected function _generate_query($where)
	{
		if (is_array($where))
			$where = $this->_generate_conditions_from_array($where);

		$WHERE = $where ? " WHERE {$where}" : "";

		return "
			SELECT
				{$this->table}.*,
				COUNT(sign_up_entries.id) as signup_count
			FROM
				{$this->table}
			LEFT JOIN sign_up_entries ON
				sign_up_entries.form_id = {$this->table}.id
			{$WHERE}
			GROUP BY
				{$this->table}.id,
				{$this->table}.committee_id,
				{$this->table}.agenda_id,
				{$this->table}.created_on,
				{$this->table}.closed_on";
	}
}
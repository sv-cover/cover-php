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
			'closed_on'
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

	public function get_description()
	{
		return sprintf('Sign-up form #%d', $this['id']);
	}

	public function get_signup_count()
	{
		return count($this['entries']);
	} 

	public function process_for_member(DataModelMember $member, $post_data, array &$errors = [])
	{
		$field_values = [];

		foreach ($this['field'] as $field)
			$field_values[$field['id']] = $field->process($post_data, $errors);

		if (count($errors) > 0)
			return false;

		$entry_model = get_model('DataModelSignUpEntry');

		$entry = $entry_model->get_or_create_for_member($this, $member);

		$entry->set_field_values($field_values);

		return true;
	}
}

class DataModelSignUpForm extends DataModel
{
	public $dataiter = 'DataIterSignUpForm';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_forms');
	}
}
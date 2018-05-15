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
}

class DataModelSignUpForm extends DataModel
{
	public $dataiter = 'DataIterSignUpForm';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_forms');
	}
}
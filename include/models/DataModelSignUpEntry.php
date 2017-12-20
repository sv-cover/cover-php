<?php

require_once 'include/data/DataModel.php';

class DataIterSignUpEntry extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'form_id',
			'member_id',
			'signed_up_on',
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

	public function set_field_values(array $field_values)
	{
		$this->db->beginTransaction();

		// Delete the old values
		$this->db->delete(['entry_id' => $this['id']]);

		// Insert the new values
		foreach ($field_values as $field_id => $value)
			$this->db->insert('sign_up_entry_values', [
				'entry_id' => $this['id'],
				'field_id' => $field_id,
				'value' => $value
			]);

		$this->db->commit();
	}
}

class DataModelSignUpEntry extends DataModel
{
	public $dataiter = 'DataIterSignUpEntry';

	public function __construct($db)
	{
		parent::__construct($db, 'sign_up_entries');
	}

	public function get_or_create_for_member(DataModelSignUpForm $form, DataModelMember $member)
	{
		$props = [
			'form_id' => $form['id'],
			'member_id' => $member['id']
		];

		$entry = $this->find_one($props);

		if (!$entry) {
			$entry = $this->new_iter($props);
			$this->insert($entry);
		}

		return $entry;
	}
}
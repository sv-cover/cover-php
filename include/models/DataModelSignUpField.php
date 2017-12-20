<?php

require_once 'include/data/DataModel.php';

interface SignUpFieldType
{
	// The name of this type as stored in the database (string)
	public function type();

	// Pick the value from the post_data associative array and, if valid, return
	// the content as how it has to be saved in the database. If it didn't
	// validate, add entries to the error array.
	public function process(array $post_data, array &$error);

	// Export it to a CSV (as an array with column => text value)
	public function export();
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
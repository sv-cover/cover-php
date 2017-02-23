<?php
require_once 'include/data/DataModel.php';

class DataIterApplication extends DataIter
{
	static public function fields()
	{
		return [
			'key',
			'name',
			'secret'
		];
	}
}

class DataModelApplication extends DataModel 
{
	public $dataiter = 'DataIterApplication';

	public function __construct($db)
	{
		parent::__construct($db, 'applications');
	}
}
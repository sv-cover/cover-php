<?php

require_once 'include/data/DataModel.php';

class DataIterBestuur extends DataIter
{
	//
	static public function fields()
	{
		return [
			'id',
			'naam',
			'login',
			'nocaps',
			'page'
		];	
	}
}

class DataModelBesturen extends DataModel
{
	public $dataiter = 'DataIterBestuur';

	public function __construct($db)
	{
		parent::__construct($db, 'besturen');
	}

	public function get_from_page($page_id)
	{
		$hits = $this->find(sprintf('page = %d', $page_id));
		
		return $hits ? current($hits) : null;
	}
}

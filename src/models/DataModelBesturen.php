<?php

require_once 'src/framework/data/DataModel.php';

class DataIterBestuur extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'naam',
			'login',
			'page_id'
		];	
	}

	public function get_page()
	{
		return get_model('DataModelEditable')->get_iter($this['page_id']);
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
		$hits = $this->find(sprintf('page_id = %d', $page_id));
		
		return $hits ? current($hits) : null;
	}
}

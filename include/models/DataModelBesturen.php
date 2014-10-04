<?php

require_once 'data/DataModel.php';

class DataModelBesturen extends DataModel
{
	public function DataModelBesturen($db)
	{
		parent::DataModel($db, 'besturen');
	}

	public function get_from_page($page_id)
	{
		$hits = $this->find(sprintf('page = %d', $page_id));
		
		return $hits ? current($hits) : null;
	}
}

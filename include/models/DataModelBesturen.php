<?php

require_once 'data/DataModel.php';

class DataModelBesturen extends DataModel
{
	public function DataModelBesturen($db)
	{
		parent::DataModel($db, 'besturen');
	}
}
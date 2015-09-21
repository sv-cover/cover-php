<?php
require_once 'include/data/DataModel.php';

class DataIterMessage extends DataIter
{
	
}

class DataModelMessage extends DataModel
{
	public $dataiter = 'DataIterMessage';

	public function __construct($db)
	{
		parent::__construct($db, 'messages');
	}

	public function get_latest($count = 5)
	{
		$query = $this->_generate_query('') . ' LIMIT ' . intval($count);

		$rows = $this->db->query($query);
		
		return $this->_rows_to_iters($rows);
	}
}

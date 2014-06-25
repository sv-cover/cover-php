<?php
require_once 'data/DataModel.php';

/**
  * A class implementing bedrijven data
  */
class DataModelBedrijven extends DataModel
{
	public function __construct($db)
	{
		parent::DataModel($db, 'bedrijven');
	}
	
	protected function _generate_query($where)
	{
		return "SELECT " . $this->_generate_select() . " FROM {$this->table}" . ($where ? " WHERE {$where}" : "");
	}

	protected function _generate_select()
	{
		return "id, naam, slug, website, page, hidden";
	}

	public function get($show_hidden = false)
	{
		return $this->find($show_hidden ? '' : 'hidden = 0');
	}

	public function get_from_name($name)
	{
		return $this->find(sprintf("'%s' IN (naam, slug)", $this->db->escape_string($name)));
	}
}

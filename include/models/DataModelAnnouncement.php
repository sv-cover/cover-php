<?php

require_once 'include/data/DataModel.php';

class DataModelAnnouncement extends DataModel
{
	const VISIBILITY_PUBLIC = 0;
	const VISIBILITY_MEMBERS = 1;
	const VISIBILITY_ACTIVE_MEMBERS = 2;

	public function __construct($db)
	{
		parent::__construct($db, 'announcements');
	}

	protected function _id_string($id)
	{
		return sprintf("%s.id = %d", $this->table, $id);
	}

	/* protected */ function _generate_query($conditions)
	{
		return "SELECT
				{$this->table}.id,
				{$this->table}.committee,
				{$this->table}.subject,
				{$this->table}.message,
				TO_CHAR({$this->table}.created_on, 'DD-MM-YYYY, HH24:MI') AS created_on,
				{$this->table}.visibility,
				c.id as committee__id,
				c.naam as committee__naam,
				c.login as committee__login,
				c.page as committee__page
			FROM
				{$this->table}
			LEFT JOIN commissies c ON
				c.id = {$this->table}.committee"
			. ($conditions ? " WHERE $conditions" : "")
			. " ORDER BY {$this->table}.created_on DESC";
	}

	public function get_latest($count = 5)
	{
		$query = $this->_generate_query('') . ' LIMIT ' . intval($count);

		$rows = $this->db->query($query);
		
		return $this->_rows_to_iters($rows);
	}
}

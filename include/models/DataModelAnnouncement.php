<?php

require_once 'data/DataModel.php';

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
			. ($conditions ? " WHERE $conditions" : "");
	}

	public function get_latest($count = 5, $visibility = null)
	{
		if ($visibility === null)
		{
			if (!logged_in())
				$visibility = self::VISIBILITY_PUBLIC;

			else if (count(logged_in('commissies')) === 0)
				$visibility = self::VISIBILITY_MEMBERS;

			else
				$visibility = self::VISIBILITY_ACTIVE_MEMBERS;
		}

		$query = $this->_generate_query('visibility <= ' . intval($visibility)) . ' ORDER BY created_on DESC LIMIT ' . intval($count);

		$rows = $this->db->query($query);
		
		return $this->_rows_to_iters($rows);
	}
}

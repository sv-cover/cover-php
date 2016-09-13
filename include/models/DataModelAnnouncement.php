<?php
require_once 'include/search.php';
require_once 'include/data/DataModel.php';

class DataIterAnnouncement extends DataIter implements SearchResult
{
	public function get_search_relevance()
	{
		return 0.5;
	}
	
	public function get_search_type()
	{
		return 'announcement';
	}

	public function get_absolute_url()
	{
		return sprintf('announcements.php?view=read&id=%d', $this->get_id());
	}
}

class DataModelAnnouncement extends DataModel implements SearchProvider
{
	const VISIBILITY_PUBLIC = 0;
	const VISIBILITY_MEMBERS = 1;
	const VISIBILITY_ACTIVE_MEMBERS = 2;

	public $dataiter = 'DataIterAnnouncement';

	public function __construct($db)
	{
		parent::__construct($db, 'announcements');
	}

	protected function _id_string($id, $table = null)
	{
		return sprintf("%s.id = %d", $table, $id);
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

	public function search($query, $limit = null)
	{
		$query = $this->db->escape_string($query);

		$query = $this->_generate_query("subject ILIKE '%{$query}%' OR message ILIKE '%{$query}%'");

		if ($limit !== null)
			$query = sprintf('%s LIMIT %d', $query, $limit);

		$rows = $this->db->query($query);

		$iters = $this->_rows_to_iters($rows);

		$policy = get_policy($this);

		return array_filter($iters, function($iter) use ($policy) {
			return $policy->user_can_read($iter);
		});
	}
}

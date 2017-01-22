<?php
require_once 'include/search.php';
require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelCommissie.php';

class DataIterVacancy extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
		    'title',
		    'description',
		    'type',
		    'url',
		    'company',
		    'hours',
		    'experience',
		    'study_year',
		    'created'
		];
	}

	public function get_search_relevance()
	{
		return 0.5;
	}
	
	public function get_search_type()
	{
		return 'vacancy';
	}

	public function get_absolute_url()
	{
		return sprintf('vacancies.php?view=read&id=%d', $this->get_id());
	}
}

class DataModelVacancy extends DataModel implements SearchProvider
{
	public $dataiter = 'DataIterVacancy';

	public function __construct($db)
	{
		parent::__construct($db, 'vacancies');
	}

	protected function _id_string($id, $table = null)
	{
		return sprintf("%s.id = %d", $table !== null ? $table : $this->table, $id);
	}

	/* protected */ function _generate_query($conditions)
	{
		return "SELECT
				{$this->table}.id,
				{$this->table}.title,
		    	{$this->table}.description,
		    	{$this->table}.type,
		    	{$this->table}.url,
		    	{$this->table}.company,
		    	{$this->table}.hours,
		    	{$this->table}.experience,
		    	{$this->table}.study_year,
				TO_CHAR({$this->table}.created, 'DD-MM-YYYY, HH24:MI') AS created
			FROM
				{$this->table}"
			. " ORDER BY {$this->table}.created DESC";
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

		$query = $this->_generate_query("title ILIKE '%{$query}%' 
										 OR description ILIKE '%{$query}%'
										 OR company ILIKE '%{$query}%'");

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

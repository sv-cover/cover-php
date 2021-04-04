<?php
require_once 'src/data/DataModel.php';
require_once 'src/framework/search.php';
require_once 'src/framework/router.php';
require_once 'src/models/DataModelCommissie.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterAnnouncement extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
			'committee_id',
			'subject',
			'message',
			'created_on',
			'visibility',
		];
	}

	static public function rules()
	{
		return array_merge(parent::rules(), [
			'subject' => [
				'required' => true,
				'validate' => ['not_empty']
			],
			'message' => [
				'required' => true,
				'validate' => ['not_empty']
			]
		]);
	}

	public function get_committee()
	{
		return $this->getIter('committee', 'DataIterCommissie');
	}

	public function get_search_relevance()
	{
		return 0.5;
	}
	
	public function get_search_type()
	{
		return 'announcement';
	}

	public function get_absolute_path($url = false)
	{
		$router = get_router();
		$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $router->generate('announcements', ['view' => 'read', 'id' => $this->get_id()], $reference_type);
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
		return sprintf("%s.id = %d", $table !== null ? $table : $this->table, $id);
	}

	/* protected */ function _generate_query($conditions)
	{
		return "SELECT
				{$this->table}.id,
				{$this->table}.committee_id,
				{$this->table}.subject,
				{$this->table}.message,
				TO_CHAR({$this->table}.created_on, 'DD-MM-YYYY, HH24:MI') AS created_on,
				{$this->table}.visibility,
				c.id as committee__id,
				c.naam as committee__naam,
				c.login as committee__login,
				c.page_id as committee__page_id
			FROM
				{$this->table}
			LEFT JOIN commissies c ON
				c.id = {$this->table}.committee_id"
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

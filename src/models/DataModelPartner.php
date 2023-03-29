<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/framework/search.php';
require_once 'src/framework/router.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterPartner extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
		    'name',
		    'type',
		    'url',
		    'logo_url',
		    'logo_dark_url',
		    'profile',
		    'has_banner_visible',
		    'has_profile_visible',
		    'created_on',
		];
	}

	public function get_search_relevance()
	{
		return floatval($this->data['search_relevance']);
	}
	
	public function get_search_type()
	{
		return 'partner';
	}

	public function get_absolute_path($url = false)
	{
		$router = get_router();
		$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $router->generate('partners', ['view' => 'read', 'id' => $this->get_id()], $reference_type);
	}

	public function get_logo($width=null)
	{
		return get_filemanager_url($this['logo_url'], $width);
	}

	public function get_logo_dark($width=null)
	{
		return get_filemanager_url($this['logo_dark_url'], $width);
	}

	public function get_vacancies()
	{
		return get_model('DataModelVacancy')->find(['partner_id' => $this->get_id()]);
	}

	public function get_sort_order()
	{
		switch ($this['type'] ?? DataModelPartner::TYPE_SPONSOR)
		{
			case DataModelPartner::TYPE_MAIN_SPONSOR:
				return 0;
			case DataModelPartner::TYPE_SPONSOR:
				return 1;
			default:
				return 2;
		}
	}
}

class DataModelPartner extends DataModel implements SearchProvider
{
	const TYPE_SPONSOR = 0;
	const TYPE_MAIN_SPONSOR = 1;
	const TYPE_OTHER = 2;

	public $dataiter = 'DataIterPartner';

	public function __construct($db)
	{
		parent::__construct($db, 'partners');
	}

	public function update(DataIter $iter)
	{
		$iter['last_modified'] = new DateTime();

		return parent::update($iter);
	}

	public function search($search_query, $limit = null)
	{
		// More or less analogous to DataModelAgenda
		$query = "
			WITH
				search_items AS (
					SELECT
						id,
						setweight(to_tsvector(name), 'A') || setweight(to_tsvector(profile), 'B') body
					FROM
						{$this->table}
				),
				matching_items AS (
					SELECT
						id,
						body,
						ts_rank_cd(body, query) as search_relevance
					FROM
						search_items,
						plainto_tsquery('english', :keywords) query
					WHERE
						body @@ query
				)
			SELECT
				p.*,
				m.search_relevance
			FROM
				matching_items m
			LEFT JOIN {$this->table} p ON
				p.id = m.id
			";

		if ($limit !== null)
			$query .= sprintf(" LIMIT %d", $limit);

		$rows = $this->db->query($query, false, [':keywords' => $search_query]);
		return $this->_rows_to_iters($rows);
	}

	public function shuffle(&$iters)
	{
		// Shuffle the banners
		shuffle($iters);

		usort($iters, function($a, $b) {
			return $a['sort_order'] <=> $b['sort_order'];
		});
	}
}

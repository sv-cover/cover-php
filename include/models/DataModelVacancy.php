<?php
require_once 'include/search.php';
require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelCommissie.php';

class DataIterVacancy extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
		    'title',
		    'description',
		    'type',
		    'study_phase',
		    'url',
		    'partner_id',
		    'partner_name',
		    'created_on',
		    'updated_on',
		];
	}

	static public function rules()
	{
		return [
			'title' => [
				'required' => true,
				'validate' => ['not_empty'],
			],
			'description' => [
				'required' => true,
			],
			'type' => [
				'required' => true,
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($type) {
						return in_array($type, [
							DataModelVacancy::TYPE_FULL_TIME,
							DataModelVacancy::TYPE_PART_TIME,
							DataModelVacancy::TYPE_INTERNSHIP,
							DataModelVacancy::TYPE_GRADUATION_PROJECT,
							DataModelVacancy::TYPE_OTHER,
						]);
					}
				],
			],
			'study_phase' => [
				'required' => true,
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($type) {
						return in_array($type, [
							DataModelVacancy::STUDY_PHASE_BSC,
							DataModelVacancy::STUDY_PHASE_MSC,
							DataModelVacancy::STUDY_PHASE_BSC_GRADUATED,
							DataModelVacancy::STUDY_PHASE_MSC_GRADUATED,
							DataModelVacancy::STUDY_PHASE_OTHER,
						]);
					}
				],
			],
			'url' => [
				'clean' => 'clean_empty',
				'validate' => [
					'optional',
					function($url) {
						return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
					}
				],
			],
			'partner_id' => [
				// partner_id XOR partner_name is required, this is validated at partner_name
				'clean' => function($value) {
					return $value ? intval($value) : null;
				},
				'validate' => [
					'optional',
					function($partner_id) {
						try {
							$partner = get_model('DataModelPartner')->get_iter($partner_id);
						} catch (DataIterNotFoundException $e) {
							return false;
						}

						return true;
					}
				]
			],
			'partner_name' => [
				// OK, this XOR thing is inconvenient. Ideally, we would use the 'clean_empty'
				// cleaner on this field, but that would break validation, which we need for the XOR
				// thing. Without 'clean_empty' an emptystring would end up in the database, but
				// null would be more desirable. Therefore, the DataIter->set() function is
				// overridden to set empty values on partner_name to null. Ideally, this would be
				// fixed with more robust form handling.
				'required' => true,
				'validate' => [
					function($partner_name, $field, $iter, $data) {
						return empty($partner_name) xor !isset($data['partner_id']);
					}
				]
			],
		];
	}

	public function get_search_relevance()
	{
		return floatval($this->data['search_relevance']);
	}
	
	public function get_search_type()
	{
		return 'vacancy';
	}

	public function get_absolute_url()
	{
		return sprintf('vacancies.php?view=read&id=%d', $this->get_id());
	}

	public function get_partner()
	{
		if (isset($this->data['partner_id']))
			return get_model('DataModelPartner')->get_iter($this->data['partner_id']);
		return null;
	}

	public function set($field, $value)
	{
		// Fix for limitations of the valication chain.
		if ($field == 'partner_name' && empty($value))
			$value = null;
		return parent::set($field, $value);
	}
}

class DataModelVacancy extends DataModel implements SearchProvider
{
	const TYPE_FULL_TIME = 0;
	const TYPE_PART_TIME = 1;
	const TYPE_INTERNSHIP = 2;
	const TYPE_GRADUATION_PROJECT = 3;
	const TYPE_OTHER = 4;

	const STUDY_PHASE_BSC = 0;
	const STUDY_PHASE_MSC = 1;
	const STUDY_PHASE_BSC_GRADUATED = 2;
	const STUDY_PHASE_MSC_GRADUATED = 3;
	const STUDY_PHASE_OTHER = 4;

	const FILTER_FIELDS = ['query', 'partner', 'study_phase', 'type'];

	public $dataiter = 'DataIterVacancy';

	public function __construct($db)
	{
		parent::__construct($db, 'vacancies');
	}

	public function update(DataIter $iter)
	{
		$iter['updated_on'] = new DateTime();

		return parent::update($iter);
	}

	protected function _id_string($id, $table = null)
	{
		return sprintf("%s.id = %d", $table !== null ? $table : $this->table, $id);
	}

	protected function _generate_filter_conditions(array $conditions=[])
	{
		$search = [];
		$filter = [];

		foreach ($conditions as $field => $values) {
			if (!is_array($values))
				$values = [$values];

			foreach ($values as $val) {
				if ($field === 'query') {
					$val =  $this->db->quote_value('%' . $val . '%');
					$search[] = sprintf('title ILIKE %s', $val);
					$search[] = sprintf('description ILIKE %s', $val);
				} elseif ($field === 'partner') {
					if (is_numeric($val))
						$filter[] = sprintf('partner_id =  %s', $this->db->quote_value($val));
					else
						$filter[] = sprintf('partner_name ILIKE %s', $this->db->quote_value($val));
				} else {
					$filter[] = sprintf('%s =  %s', $field, $val);
				}
			}
		}

		$prepared = [];

		if (!empty($search))
			$prepared[] = new DatabaseLiteral('(' . implode(' OR ', $search) . ')');

		if (!empty($filter))
			$prepared[] = new DatabaseLiteral('(' . implode(' OR ', $filter) . ')');

		return $prepared;
	}

	public function filter(array $conditions=[])
	{
		$filter_conditions = $this->_generate_filter_conditions($conditions);

		if (!empty($filter_conditions))
			$filter_conditions = $this->_generate_conditions_from_array($filter_conditions);

		$query = sprintf(
			"SELECT *
			   FROM {$this->table} 
			   %s
			  ORDER BY LOWER(title)
			",
			(!empty($filter_conditions) ? " WHERE {$filter_conditions}" : "")
		);

		$rows = $this->db->query($query);

		return $this->_rows_to_iters($rows);
	}

	public function partners()
	{
		$rows = $this->db->query("
			SELECT NULL AS id
				  ,t1.name AS name
			  FROM (
			  		SELECT DISTINCT partner_name AS name
			  		  FROM {$this->table}
			         WHERE partner_name IS NOT NULL
			       ) AS t1

			UNION

			SELECT t2.id AS id
				  ,p.name AS name
			  FROM partners  AS P JOIN (
			  		SELECT DISTINCT partner_id AS id
			  		  FROM {$this->table}
			  	     WHERE partner_id IS NOT NULL
			       ) t2 ON p.id = t2.id;
		");
		return $rows;
	}

	public function search($search_query, $limit = null)
	{
		// More or less analogous to DataModelAgenda
		$query = "
			WITH
				search_items AS (
					SELECT
						id,
						setweight(to_tsvector(title), 'A') || setweight(to_tsvector(description), 'B') body
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
				v.*,
				m.search_relevance
			FROM
				matching_items m
			LEFT JOIN {$this->table} v ON
				v.id = m.id
			";

		if ($limit !== null)
			$query .= sprintf(" LIMIT %d", $limit);

		$rows = $this->db->query($query, false, [':keywords' => $search_query]);
		return $this->_rows_to_iters($rows);
	}
}

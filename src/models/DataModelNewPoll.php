<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/framework/search.php';
require_once 'src/framework/router.php';
require_once 'src/models/DataModelMember.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterNewPollOption extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'poll_id',
			'option',
			'votes',
		];
	}

	public function get_poll()
	{
		return get_model('DataModelPoll')->get_iter($this['poll_id']);
	}
}

class DataIterNewPoll extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
			'member_id',
			'committee_id',
			'question',
			'created_on',
			'updated_on',
			'closed_on',
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

	public function get_absolute_path($url = false)
	{
		$router = get_router();
		$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $router->generate('vacancies', ['id' => $this->get_id()], $reference_type);
	}

	public function get_options()
	{
		return $this->model->get_options($this);
	}

	public function set_options(array $options)
	{
		return $this->model->set_options($this, $options);
	}

	public function get_likes()
	{
		return [];
	}

	public function get_comments()
	{
		return [];
	}

	public function get_total_votes()
	{
		return $this->model->get_total_votes($this);
	}

	public function get_member_has_voted(DataIterMember $member = null)
	{
		return $this['member_vote'] !== null;
	}

	public function get_member_vote(DataIterMember $member = null)
	{
		return $this->model->get_member_vote($this, $member);
	}

	public function get_is_open()
	{
		return empty($this['closed_on']) || new \DateTime($this['closed_on']) > new \DateTime();
		// 	|| (empty($this['closed_on']) && \DataIter::is_same($this, $this->model->get_current())) // is latest
		// ;
	}
}

class DataModelNewPoll extends DataModel implements SearchProvider
{
	public $dataiter = 'DataIterNewPoll';

	public function __construct($db)
	{
		parent::__construct($db, 'polls');
	}

	public function insert(DataIter $iter)
	{
		$result = parent::insert($iter);

		// Only update after inserting to not break things on error. This does require filtering for the new ID
		if (!empty($result))
			$this->db->query("
				UPDATE polls
				   SET closed_on = DATE_TRUNC('second', NOW()::timestamp)
				 WHERE id != :inserted_id
				   AND closed_on IS NULL
			", false, ['inserted_id' => $result]);

		return $result;
	}

	public function update(DataIter $iter)
	{
		$iter['updated_on'] = new DateTime();
		return parent::update($iter);
	}

	// protected function _id_string($id, $table = null)
	// {
	// 	return sprintf("%s.id = %d", $table !== null ? $table : $this->table, $id);
	// }

	public function get_current()
	{
		$row = $this->db->query_first("SELECT * FROM {$this->table} ORDER BY created_on DESC LIMIT 1");
		return $this->_row_to_iter($row, 'DataIterNewPoll');
	}

	public function get_options(DataIterNewPoll $poll)
	{
		$rows = $this->db->query(
			'SELECT po.*
			       ,COALESCE(pv.votes, 0) AS votes
			   FROM poll_options AS po
			   LEFT JOIN (
			            SELECT poll_option_id AS poll_option_id
			                  ,count(id) AS votes
			              FROM poll_votes
			             WHERE poll_option_id in (SELECT id FROM poll_options WHERE poll_id = :poll_id)
			             GROUP BY poll_option_id
			        ) AS pv on po.id = pv.poll_option_id
			  WHERE po.poll_id = :poll_id
			  ORDER BY po.id
			;',
			false,
			[
				':poll_id' => $poll->get_id(),
			]
		);
		return $this->_rows_to_iters($rows, 'DataIterNewPollOption', compact('poll'));
	}

	public function set_options(DataIterNewPoll $poll, array $options)
	{
		foreach ($options as $option)
			$this->db->insert('poll_options', array(
				'poll_id' => $poll->get_id(),
				'option' => $option));
	}

	public function get_total_votes(DataIterNewPoll $poll)
	{
		$result = $this->db->query_value(
			'SELECT count(*)
			   FROM poll_votes
			  WHERE poll_option_id in (SELECT id FROM poll_options WHERE poll_id = :poll_id)
			;',
			[
				':poll_id' => $poll->get_id(),
			]
		);
		return $result ?? 0;
	}

	public function get_member_vote(DataIterNewPoll $poll, DataIterMember $member = null)
	{
		if (!$member)
			$member = get_identity()->member();

		$row = $this->db->query_first(
			'SELECT poll_option_id
			   FROM poll_votes
			  WHERE member_id = :member_id
				AND poll_option_id in (SELECT id FROM poll_options WHERE poll_id = :poll_id)',
			false,
			[
				':member_id' => $member->get_id(),
				':poll_id' => $poll->get_id(),
			],
		);
		return $row['poll_option_id'] ?? null;
	}

	public function set_member_vote(DataIterNewPollOption $option, DataIterMember $member = null)
	{
		if (!$member)
			$member = get_identity()->member();

		return $this->db->insert('poll_votes', [
			'member_id' => $member->get_id(),
			'poll_option_id' => $option->get_id(),
		]);
	}

	public function search($search_query, $limit = null)
	{
		// More or less analogous to DataModelAgenda
		$query = "
			WITH
				search_items AS (
					SELECT
						id,
						setweight(to_tsvector(question), 'A') body
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

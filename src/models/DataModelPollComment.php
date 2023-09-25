<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/framework/search.php';
require_once 'src/framework/router.php';
require_once 'src/models/DataModelMember.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterPollComment extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [
			'id',
			'poll_id',
			'member_id',
			'comment',
			'question',
			'created_on',
			'updated_on',
		];

	}

	public function get_poll()
	{
		return get_model('DataModelNewPoll')->get_iter($this['poll_id']);
	}

	public function get_search_relevance()
	{
		return floatval($this->data['search_relevance']);
	}
	
	public function get_search_type()
	{
		return 'poll_comment';
	}

	public function get_absolute_path($url = false)
	{
		$router = get_router();
		$reference_type = $url ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $router->generate('poll', ['id' => $this['poll_id']], $reference_type);
	}

	public function get_likes()
	{
		return get_model('DataModelPollCommentLike')->get_for_poll_comment($this);
	}

	public function get_member()
	{
		if (!empty($this['member_id'])) 
			return get_model('DataModelMember')->get_iter($this['member_id']);
		return null;
	}

	public function is_liked_by(DataIterMember $member)
	{
		return get_model('DataModelPollCommentLike')->get_liked_by($this, $member) > 0;
	}
}

class DataModelPollComment extends DataModel implements SearchProvider
{
	public $dataiter = 'DataIterPollComment';

	public function __construct($db)
	{
		parent::__construct($db, 'poll_comments');
	}

	protected function _generate_query($where)
	{
		if (is_array($where))
			$where = $this->_generate_conditions_from_array($where);

		$where = $where ?: "1=1"; // no conditions means where true :)
		return "
			 SELECT poll_comments.*
			       ,COUNT(DISTINCT pl.id) AS like_count
			   FROM poll_comments
			   LEFT JOIN poll_comment_likes AS pl ON pl.poll_comment_id = poll_comments.id
			  WHERE {$where}
			  GROUP BY poll_comments.id
			;"
		;
	}

	public function get_for_poll(DataIter $poll)
	{
		$rows = $this->db->query(
			'SELECT pc.*
			       ,COUNT(DISTINCT pl.id) AS like_count
			   FROM poll_comments AS pc
			   LEFT JOIN poll_comment_likes AS pl ON pl.poll_comment_id = pc.id
			  WHERE poll_id = :poll_id
			  GROUP BY pc.id
			  ORDER BY created_on ASC
			;',
			false,
			[
				'poll_id' => $poll->get_id(),
			],
		);
		return $this->_rows_to_iters($rows);
	}

	public function search($search_query, $limit = null)
	{
		// More or less analogous to DataModelAgenda
		$query = "
			WITH
				search_items AS (
					SELECT
						id,
						setweight(to_tsvector(comment), 'A') body
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

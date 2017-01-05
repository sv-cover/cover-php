<?php
require_once 'include/data/DataModel.php';

class DataIterCommitteeBattleScore extends DataIter
{
	public function get_committees()
	{
		return get_model('DataModelCommissie')->find(['id__in' => $this->get_committee_ids()]);
	}

	public function get_members()
	{
		return get_model('DataModelMember')->find(['id__in' => $this->get_member_ids()]);
	}

	public function get_committee_ids()
	{
		return $this->model->db->query_column(
			sprintf("SELECT committee_id FROM committee_battle_committees WHERE score_id = %d",
				$this->get_id()));
	}

	public function get_member_ids()
	{
		return $this->model->db->query_column(
			sprintf("SELECT member_id FROM committee_battle_users WHERE score_id = %d",
				$this->get_id()));
	}
}

class DataModelCommitteeBattleScore extends DataModel
{
	public $dataiter = 'DataIterCommitteeBattleScore';

	public $fields = [
		'id',
		'points',
		'awarded_on',
		'awarded_for'
	];

	public function __construct($db)
	{
		parent::__construct($db, 'committee_battle_scores');
	}

	protected function _insert($table, DataIter $iter, $get_id = false)
	{
		$iter->set_literal('awarded_on', 'current_timestamp');

		$id = parent::_insert($table, $iter, true);

		if (isset($iter->data['committee_ids']))
			$this->_set_committees($iter->data['committee_ids'], $id);

		if (isset($iter->data['member_ids']))
			$this->_set_members($iter->data['member_ids'], $id);

		return $get_id ? $id : -1;
	}

	protected function _update($table, DataIter $iter)
	{
		$affected_rows = parent::_update($table, $iter);

		if (isset($iter->data['committee_ids']))
			$this->_set_committees($iter->data['committee_ids'], $iter->get_id());

		if (isset($iter->data['member_ids']))
			$this->_set_members($iter->data['member_ids'], $iter->get_id());

		return $affected_rows;
	}

	protected function _set_committees($committee_ids, $score_id)
	{
		$pairs = [];

		foreach ($committee_ids as $committee_id)
			$pairs[] = sprintf('(%d, %d)', $score_id, $committee_id);

		$this->db->query(sprintf("DELETE FROM committee_battle_committees WHERE score_id = %d", $score_id));

		if (count($pairs) > 0)
			$this->db->query(sprintf("INSERT INTO committee_battle_committees (score_id, committee_id) VALUES %s",
			implode(', ', $pairs)));
	}

	protected function _set_members($member_ids, $score_id)
	{
		$pairs = [];

		foreach ($member_ids as $member_id)
			$pairs[] = sprintf('(%d, %d)', $score_id, $member_id);

		$this->db->query(sprintf("DELETE FROM committee_battle_users WHERE score_id = %d", $score_id));

		if (count($pairs) > 0)
			$this->db->query(sprintf("INSERT INTO committee_battle_users (score_id, member_id) VALUES %s",
			implode(', ', $pairs)));
	}

	protected function _generate_conditions_from_array(array $conditions)
	{
		$fallback = [];

		foreach ($conditions as $key => $value) {
			switch ($key) {
				case 'committee_id':
					$atoms[] = sprintf('id IN (SELECT score_id FROM committee_battle_committees WHERE committee_id = %d)', $value);
					break;

				case 'member_id':
					$atoms[] = sprintf('id IN (SELECT member_id FROM committee_battle_users WHERE member_id = %d)', $value);
					break;

				default:
					$fallback[$key] = $value;
					break;
			}
		}

		$fallback_query = parent::_generate_conditions_from_array($fallback);

		if ($fallback_query != '')
			$atoms[] = $fallback_query;

		return implode(' AND ', $atoms);
	}

	public function get_for_committee(DataIterCommissie $committee)
	{
		return $this->find(['committee_id' => $committee['id']]);
	}

	public function get_scores_for_committees($committees)
	{
		$query = "
		SELECT
			c.committee_id,
			SUM(s.points) as score
		FROM
			committee_battle_committees c
		LEFT JOIN committee_battle_scores s ON
			s.id = c.score_id
		WHERE
			c.committee_id IN (%s)
		GROUP BY
			c.committee_id
		";

		$committee_ids = array_filter(array_map(getter('id'), $committees));

		if (count($committee_ids) === 0)
			return [];

		// Fill the score map with zeros
		$committee_scores = array_combine($committee_ids, array_fill(0, count($committee_ids), 0));

		$scores = $this->db->query(sprintf($query,
			implode(',', array_map(getter('id'), $committees))));

		foreach ($scores as $score)
			$committee_scores[$score['committee_id']] = $score['score'];

		return $committee_scores;
	}
}
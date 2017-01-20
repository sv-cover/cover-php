<?php
require_once 'include/data/DataModel.php';

class DataIterCommitteeBattleScore extends DataIter
{
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
	
	public function get_committees()
	{
		return count($this['committee_ids'])
			? get_model('DataModelCommissie')->find(['id__in' => $this['committee_ids']])
			: [];
	}

	public function get_members()
	{
		return count($this['member_ids'])
			? get_model('DataModelMember')->find(['id__in' => $this['member_ids']])
			: [];
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

	/**
	 * Override of DataModel::_insert() to support setting the
	 * committee_ids and member_ids properties. Also sets
	 * the awarded_on field.
	 */
	protected function _insert($table, DataIter $iter, $get_id = false)
	{
		// Let PostgreSQL set the awarded_on field. 
		$iter->set_literal('awarded_on', 'current_timestamp');

		// _insert() will behave thanks to $this->fields not
		// including 'committee_ids' and 'member_ids'.
		$id = parent::_insert($table, $iter, true);

		if (isset($iter->data['committee_ids']))
			$this->_set_committees($iter->data['committee_ids'], $id);

		if (isset($iter->data['member_ids']))
			$this->_set_members($iter->data['member_ids'], $id);

		// Recreate the behaviour of DataModel::_insert()'s third argument
		return $get_id ? $id : -1;
	}

	/**
	 * Override of DataModel::_update() to support setting the
	 * committee_ids and member_ids properties.
	 */
	protected function _update($table, DataIter $iter)
	{
		// _update() won't create a weird query because it will only update 
		// the fields defined in $this->fields.
		$affected_rows = parent::_update($table, $iter);

		// Call these helper methods if the data is present in the score object.
		if (isset($iter->data['committee_ids']))
			$this->_set_committees($iter->data['committee_ids'], $iter->get_id());

		if (isset($iter->data['member_ids']))
			$this->_set_members($iter->data['member_ids'], $iter->get_id());

		return $affected_rows;
	}

	/**
	 * Helper method to link committees who scored the points to a certain score object.
	 * @param int[] $committee_ids
	 * @param int $score_id
	 */
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

	/**
	 * Helper method to link members who helped obtain the points to a certain score object.
	 * @param int[] $member_ids
	 * @param int $score_id
	 */
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

	/**
	 * Override DataIter::_generate_conditions_from_array() to support
	 * filtering on committee_id and member_id, which is done through
	 * subqueries because this data lives in different tables.
	 */
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

	/**
	 * Override DataModel::_generate_query() to change default sort order.
	 */
	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY awarded_on DESC';
	}

	/**
	 * Get all scores for a single committee.
	 * @param DataIterCommissie $committee
	 * @return DataIterCommitteeBattleScore[]
	 */
	public function get_for_committee(DataIterCommissie $committee)
	{
		return $this->find(['committee_id' => $committee['id']]);
	}

	/**
	 * Get score totals for a list of committees.
	 * @param DataIterCommissie[] $committee
	 * @return int[] associated array with commitee id as key and score as value.
	 */ 
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
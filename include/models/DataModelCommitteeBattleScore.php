<?php
require_once 'include/data/DataModel.php';

class DataIterCommitteeBattleScore extends DataIter
{

}

class DataModelCommitteeBattleScore extends DataModel
{
	public $dataiter = 'DataIterCommitteeBattleScore';

	public function __construct($db)
	{
		parent::__construct($db, 'committee_battle_scores');
	}

	protected function _insert($table, DataIter $iter, $get_id = false)
	{
		$iter->set_literal('awarded_on', 'current_timestamp');
		return parent::_insert($table, $iter, $get_id);
	}

	public function get_for_committee(DataIterCommissie $committee)
	{
		return $this->find(['committee_id' => $committee['id']]);
	}

	public function get_scores_for_committees($committees)
	{
		$query = "
		SELECT
			committee_id,
			SUM(points) as score
		FROM
			committee_battle_scores
		WHERE
			committee_id IN (%s)
		GROUP BY
			committee_id
		";

		$committee_scores = array_combine(
			array_map(getter('id'), $committees),
			array_fill(0, count($committees), 0));

		$scores = $this->db->query(sprintf($query,
			implode(',', array_map(getter('id'), $committees))));

		foreach ($scores as $score)
			$committee_scores[$score['committee_id']] = $score['score'];

		return $committee_scores;
	}
}
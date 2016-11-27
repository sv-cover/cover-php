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

	public function get()
	{
		$committee_model = get_model('DataModelCommissie');
		$committee_model->type = DataModelCommissie::TYPE_COMMITTEE;

		$query = "
		SELECT
			committee_id,
			SUM(points) as score
		FROM
			committee_battle_scores
		GROUP BY
			committee_id
		";

		$committees = array();

		foreach ($committee_model->get(false) as $committee) {
			$committee['score'] = 0;
			$committees[$committee['id']] = $committee;
		}

		$scores = $this->db->query($query);

		foreach ($scores as $score)
			if (isset($committees[$score['committee_id']]))
				$committees[$score['committee_id']]['score'] = $score['score'];

		return $committees;
	}
}
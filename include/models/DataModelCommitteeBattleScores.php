<?php
require_once 'include/data/DataModel.php';

class DataModelCommitteeBattleScores extends DataModel
{
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
			if (isset($commitees[$score['committee_id']]))
				$commitees[$score['committee_id']]['score'] = $score['score'];

		return $committees;
	}
}
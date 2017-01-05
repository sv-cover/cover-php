<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';
require_once 'include/controllers/ControllerEditable.php';

class ControllerCommitteeBattle extends ControllerCRUD
{
	protected $committee_model;

	public function __construct()
	{
		$this->model = get_model('DataModelCommitteeBattleScore');

		$this->committee_model = get_model('DataModelCommissie');
		$this->committee_model->type = DataModelCommissie::TYPE_COMMITTEE;
	}

	protected function _index()
	{
		$committees = $this->committee_model->get(false);

		$scores = $this->model->get_scores_for_committees($committees);

		// Attach the scores to the committees for easy access
		foreach ($committees as $committee)
			$committee['score'] = $scores[$committee['id']];

		// Sort the committees by their scores
		usort($committees, function($a, $b) {
			if ($a['score'] == $b['score'])
				return strcasecmp($a['naam'], $b['naam']);
			else
				return $b['score'] - $a['score'];
		});

		// And finally attach the positions (without 0)
		$score_position = array_unique(array_filter(array_values($scores)));
		rsort($score_position, SORT_NUMERIC);

		foreach ($committees as $committee)
			$committee['position'] = $committee['score'] === 0 ? 0 : array_search($committee['score'], $score_position, true) + 1;

		return $committees;
	}

	protected function _get_title($iter)
	{
		return $iter instanceof DataIter ? $iter->get('naam') : __('Committee Battle');
	}

	public function link_to_read(DataIter $iter)
	{
		if ($iter instanceof DataIterCommissie)
			return $this->link([
				$this->_var_view => 'committee',
				'committee' => $iter['id']
			]);
		else
			return $this->link_to_index();
	}

	public function run_committee()
	{
		if (!isset($_GET['committee']))
			throw new DataIterNotFoundException('committee argument empty');

		$committee = $this->committee_model->get_iter($_GET['committee']);

		$scores = $this->model->get_for_committee($committee);

		return $this->get_content('committee', $committee, compact('scores'));
	}
}

$controller = new ControllerCommitteeBattle();
$controller->run();
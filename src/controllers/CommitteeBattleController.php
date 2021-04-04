<?php
namespace App\Controller;

require_once 'src/framework/init.php';
require_once 'src/controllers/ControllerCRUD.php';

class CommitteeBattleController extends \ControllerCRUD
{
	protected $committee_model;

	protected $view_name = 'committeebattle';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelCommitteeBattleScore');

		$this->committee_model = clone get_model('DataModelCommissie');

		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
		{
			$parameters['id'] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		return $this->generate_url('committee_battle', $parameters);
	}

	protected function _index()
	{
		$committees = $this->committee_model->get(\DataModelCommissie::TYPE_COMMITTEE);

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

	public function run_committee()
	{
		if (!isset($_GET['committee']))
			throw new \DataIterNotFoundException('committee argument empty');

		$committee_model = $this->committee_model;

		$committee = $committee_model->get_iter($_GET['committee']);

		$scores = $this->model->get_for_committee($committee);

		return $this->view->render_committee($committee, $scores, $committee_model);
	}
}

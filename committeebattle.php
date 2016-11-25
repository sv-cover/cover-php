<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerCommitteeBattle extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelCommitteeBattleScores');
	}

	protected function run_impl()
	{
		$scores = $this->model->get();

		usort($scores, function($a, $b) {
			if ($a['score'] == $b['score'])
				return strcasecmp($a['naam'], $b['naam']);
			else
				return $a['score'] - $b['score'];
		});

		$this->get_content('committeebattle::index', $scores);
	}	
}

$controller = new ControllerCommitteeBattle();
$controller->run();
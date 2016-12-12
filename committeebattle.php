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

		return $committees;
	}

	protected function _create_iter()
	{
		$iter = parent::_create_iter();

		if (isset($_GET['committee'])) {
			$committee = get_model('DataModelCommissie')->get_iter($_GET['committee']);
			$iter['committee_id'] = $committee->get_id();	
		}

		return $iter;
	}

	protected function _get_title($iter)
	{
		return $iter instanceof DataIter ? $iter->get('naam') : __('Committee Battle');
	}

	public function link_to_create(DataIterCommissie $committee)
	{
		return $this->link([
			$this->_var_view => 'create',
			'committee' => $committee['id']
		]);
	}

	public function link_to_read(DataIter $iter)
	{
		if ($iter instanceof DataIterCommitteeBattleScore)
			$committee_id = $iter['committee_id'];
		elseif ($iter instanceof DataIterCommissie)
			$committee_id = $iter['id'];
		else
			throw new InvalidArgumentException();

		return $this->link([
			$this->_var_view => 'committee',
			'committee' => $committee_id
		]);
	}

	public function run_committee()
	{
		$committee = $this->committee_model->get_iter($_GET['committee']);

		$scores = $this->model->get_for_committee($committee);

		return $this->get_content('committee', $committee, compact('scores'));
	}
}

$controller = new ControllerCommitteeBattle();
$controller->run();
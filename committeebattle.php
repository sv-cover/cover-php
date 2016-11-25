<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerCommitteeBattle extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelCommitteeBattleScores');
	}

	protected function _index()
	{
		$committees = parent::_index();

		usort($committees, function($a, $b) {
			if ($a['score'] == $b['score'])
				return strcasecmp($a['naam'], $b['naam']);
			else
				return $a['score'] - $b['score'];
		});

		return $committees;
	}

	public function link_to_create(DataIterCommissie $committee)
	{
		return $this->link([
			$this->_var_view => 'create',
			'committee' => $committee['id']
		]);
	}
}

$controller = new ControllerCommitteeBattle();
$controller->run();
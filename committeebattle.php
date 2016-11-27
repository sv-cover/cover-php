<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerCommitteeBattle extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelCommitteeBattleScore');
	}

	protected function _index()
	{
		$committees = parent::_index();

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
}

$controller = new ControllerCommitteeBattle();
$controller->run();
<?php
	require_once 'include/init.php';
	require_once 'include/controllers/ControllerCRUD.php';

class ControllerVacancies extends ControllerCRUD{

	public function __construct()
	{
		$this->model = get_model('DataModelVacancy');

		$this->view = View::byName('vacancies', $this);
	}

	protected function _validate(DataIter $iter, array &$data, array &$errors)
	{
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR))
			$errors[] = 'committee_id';

		if(strlen($data['title']) == 0)
		{
			$errors[] = 'title';
		}

		return count($errors) === 0;
	}
}

$controller = new ControllerVacancies();
$controller->run();

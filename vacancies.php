<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerVacancies extends ControllerCRUD{

	public function __construct()
	{
		$this->model = get_model('DataModelVacancy');

		$this->view = View::byName('vacancies', $this);
	}

	public function run_preview()
	{
		return markup_parse($_POST['description']);
	}
}

$controller = new ControllerVacancies();
$controller->run();

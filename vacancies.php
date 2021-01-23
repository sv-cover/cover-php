<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerVacancies extends ControllerCRUD{

	public function __construct()
	{
		$this->model = get_model('DataModelVacancy');

		$this->view = View::byName('vacancies', $this);
	}

    protected function _index()
    {
        $iters = parent::_index();

        usort($iters, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        return $iters;
    }

	public function run_preview()
	{
		return markup_parse($_POST['description']);
	}
}

$controller = new ControllerVacancies();
$controller->run();

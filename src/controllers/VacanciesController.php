<?php
namespace App\Controller;

require_once 'src/framework/controllers/ControllerCRUD.php';

class VacanciesController extends \ControllerCRUD
{
    protected $view_name = 'vacancies';

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelVacancy');

        parent::__construct($request, $router);
	}

    protected function _index()
    {
        $filter_conditions = array_intersect_key($_GET, array_flip($this->model::FILTER_FIELDS));
        return $this->model->filter($filter_conditions);
    }

	public function run_preview()
	{
		return markup_parse($_POST['description']);
	}
}

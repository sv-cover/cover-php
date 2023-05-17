<?php
namespace App\Controller;

use App\Form\Type\VacancyFormType;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class VacanciesController extends \ControllerCRUDForm
{
	protected $view_name = 'vacancies';
	protected $form_type = VacancyFormType::class;

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelVacancy');

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

		return $this->generate_url('vacancies', $parameters);
	}

	protected function _index()
	{
		$filter_conditions = array_intersect_key($_GET, array_flip($this->model::FILTER_FIELDS));
		return $this->model->filter($filter_conditions);
	}
}

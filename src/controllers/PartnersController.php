<?php
namespace App\Controller;

use App\Form\PartnerType;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class PartnersController extends \ControllerCRUDForm
{
	protected $view_name = 'partners';
	protected $form_type = PartnerType::class;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPartner');

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

		return $this->generate_url('partners', $parameters);
	}

	protected function _index()
	{
		$iters = parent::_index();

		usort($iters, function($a, $b) {
			return strcasecmp($a['name'], $b['name']);
		});

		return $iters;
	}

	public function run_autocomplete()
	{
		$partners = $this->model->find(['name__contains' => $_GET['search']]);

		$data = [];

		foreach ($partners as $partner)
			$data[] = [
				'id' => $partner['id'],
				'name' => $partner['name'],
			];

		return $this->view->render_json($data);
	}

	public function run_index()
	{
		if (!get_policy($this->model)->user_can_update($this->new_iter()))
			return $this->view->redirect($this->generate_url('career'));
		return parent::run_index();
	}
}

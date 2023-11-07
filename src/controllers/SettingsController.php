<?php
namespace App\Controller;

use App\Form\SettingsType;

require_once 'src/framework/controllers/ControllerCRUD.php';

class SettingsController extends \ControllerCRUD
{
	protected $view_name = 'settings';
	protected $form_type = SettingsType::class;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelConfiguratie');

		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
			$parameters['id'] = $iter['key'];

		return $this->generate_url('settings', $parameters);
	}

	public function run_read(\DataIter $iter)
	{
		return $this->view->redirect($this->generate_url('settings'));
	}
}

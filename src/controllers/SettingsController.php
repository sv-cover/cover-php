<?php
namespace App\Controller;

require_once 'src/framework/controllers/ControllerCRUD.php';

class SettingsController extends \ControllerCRUD
{
	protected $view_name = 'settings';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelConfiguratie');

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

		return $this->generate_url('settings', $parameters);
	}
}

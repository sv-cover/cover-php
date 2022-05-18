<?php
namespace App\Controller;

require_once 'src/framework/controllers/ControllerCRUD.php';

class DeviceSessionsController extends \ControllerCRUD
{
    protected $view_name = 'devicesessions';

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelSession');

        parent::__construct($request, $router);
	}

    protected function _index()
    {
        return $this->model->find(['type' => 'device']);
    }

    public function run_create()
    {
        if (!get_auth()->logged_in() && !is_a(get_identity(), 'DeviceIdentityProvider')) {
            $response = get_auth()->create_device_session(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
            $this->view = \View::byName($this->view_name, $this);
        }

        return $this->view()->render_create();
    }

    public function run_logout()
    {
        if (is_a(get_identity(), 'DeviceIdentityProvider'))
            get_auth()->logout();
        $this->view->redirect($this->generate_url('device_sessions'));
    }
}

<?php
namespace App\Controller;

use App\Form\Type\DeviceSessionFormType;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class DeviceSessionsController extends \ControllerCRUDForm
{
    protected $view_name = 'devicesessions';
    protected $form_type = DeviceSessionFormType::class;

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelSession');

        parent::__construct($request, $router);
	}

    public function path(string $view, \DataIter $iter = null, bool $json = false)
    {
        $parameters = [];

        if ($json)
            $parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));

        if ($view === 'create')
            return $this->generate_url('device_sessions.create', $parameters);

        if ($view === 'delete')
            return $this->generate_url('device_sessions.delete', $parameters);

        if ($view === 'logout')
            return $this->generate_url('device_sessions.logout', $parameters);

        $parameters = [
            'view' => $view,
        ];

        if (isset($iter))
            $parameters['id'] = $iter->get_id();

        return $this->generate_url('device_sessions', $parameters);
    }

    protected function _index()
    {
        if (!get_identity()->member_in_committee(COMMISSIE_EASY))
            throw new \UnauthorizedException();
        return $this->model->find(['type' => 'device']);
    }

    public function run_create()
    {
        if (!get_auth()->logged_in() && !is_a(get_identity(), 'DeviceIdentityProvider')) {
            $response = get_auth()->create_device_session(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null);
            $this->view = \View::byName($this->view_name, $this);
        }

        return $this->view()->render_create_session();
    }

    public function run_logout()
    {
        if (is_a(get_identity(), 'DeviceIdentityProvider'))
            get_auth()->logout();
        $this->view->redirect($this->generate_url('device_sessions'));
    }

    public function run_read(\DataIter $iter)
    {
        return $this->view()->redirect($this->generate_url('device_sessions'));
    }
}

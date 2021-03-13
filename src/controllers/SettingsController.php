<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class SettingsController extends \ControllerCRUD
{
    protected $view_name = 'settings';

    public function __construct($request, $router)
    {
		$this->model = get_model('DataModelConfiguratie');

        parent::__construct($request, $router);
	}

	public function link_to_read(\DataIter $item)
	{
		return $this->link_to_index();
	}
}

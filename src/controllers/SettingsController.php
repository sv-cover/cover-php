<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class SettingsController extends \ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelConfiguratie');

		$this->view = \View::byName('settings', $this);
	}

	public function link_to_read(\DataIter $item)
	{
		return $this->link_to_index();
	}
}

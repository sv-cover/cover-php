<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerPartners extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelPartner');

		$this->view = View::byName('partners', $this);
	}
}

$controller = new ControllerPartners();
$controller->run();

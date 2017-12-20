<?php

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerSignUpForms extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelSignUpForm');

		$this->view = View::byName('signup', $this);
	}
}

$controller = new ControllerSignUpForms();
$controller->run();

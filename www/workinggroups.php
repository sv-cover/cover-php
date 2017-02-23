<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerWorkingGroups extends Controller
{	
	public function __construct()
	{
		$this->view = new View($this, null);
	}

	public function run()
	{
		if (isset($_GET['id']))
			return $this->view->redirect('commissies.php?id=' . $_GET['id']);
		else
			return $this->view->redirect('commissies.php?view=working_groups');
	}
}

$controller = new ControllerWorkingGroups();
$controller->run();


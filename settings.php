<?php

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerSettings extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelConfiguratie');

		$this->view = View::byName('settings', $this);
	}

	public function link_to_read(DataIter $item)
	{
		return $this->link_to_index();
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
	$controller = new ControllerSettings();
	$controller->run();
}

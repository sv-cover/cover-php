<?php

require_once 'include/init.php';
require_once 'controllers/ControllerCRUD.php';

class ControllerSettings extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelConfiguratie');
	}

	protected function _get_title($iter)
	{
		return $iter instanceof DataIter ? $iter->get('key') : __('Instellingen');
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

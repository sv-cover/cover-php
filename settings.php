<?php

require_once 'include/init.php';
require_once 'controllers/ControllerCRUD.php';

class ControllerSettings extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelConfiguratie');
	}

	function get_content($view, $iter = null, $params = null)
	{
		$params = array_merge(array('controller' => $this), $params !== null ? $params : array());

		$this->run_header(array('title' => $iter instanceof DataIter ? $iter->get('key') : __('Instellingen')));
		run_view('settings::' . $view, $this->model, $iter, $params);
		$this->run_footer();
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

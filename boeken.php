<?php

require_once 'include/init.php';
require_once 'include/controllers/Controller.php';
require_once 'include/controllers/ControllerEditable.php';

class ControllerBoeken extends Controller
{
	public function __construct()
	{
		// Overriding the default constructor because that one doesn't make sense
	}

	protected function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => __('Boeken')));
		run_view('show::single', null, 'Boeken bestellen');
		run_view('boeken::' . $view, null, $iter, $params);
		$this->run_footer();
	}

	function run_impl()
	{
		$config = get_model('DataModelConfiguratie');
		$webshop_link = $config->get_value('boekcie_webshop_link', '#');

		$this->get_content('go_to_webshop', null, compact('webshop_link'));
	}
}

$controller = new ControllerBoeken();
$controller->run();

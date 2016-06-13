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

	function run_impl()
	{
		$config = get_model('DataModelConfiguratie');
		$webshop_link = $config->get_value('boekcie_webshop_link', '#');

		$this->run_header(array('title' => __('Boeken')));

		if (get_auth()->logged_in()) {
			run_view('show::single', null, 'Boeken bestellen');
			run_view('boeken::go_to_webshop', null, null, compact('webshop_link'));
		} else {
			run_view('boeken::login', null, null, array());
		}
		
		$this->run_footer();
	}
}

$controller = new ControllerBoeken();
$controller->run();

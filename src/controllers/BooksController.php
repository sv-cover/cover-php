<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class BooksController extends \Controller
{
    protected $view_name = 'boeken';

	protected function run_impl()
	{
		$config = get_model('DataModelConfiguratie');
		$webshop_link = $config->get_value('boekcie_webshop_link', '#');

		if (get_auth()->logged_in())
			return $this->view->render_call_to_action($webshop_link);
		else
			return $this->view->render_call_to_log_in();
	}
}

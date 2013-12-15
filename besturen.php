<?php

require_once 'include/init.php';
require_once 'controllers/Controller.php';

class ControllerBesturen extends Controller
{
	public function ControllerBesturen()
	{
		$this->model = get_model('DataModelBesturen');
	}

	public function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => _('Besturen')));
		run_view('besturen::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	public function run_impl()
	{
		$iters = $this->model->get();
		$this->get_content('besturen', $iters);
	}
}

$controller = new ControllerBesturen();
$controller->run();
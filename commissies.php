<?php
include 'include/init.php';
include 'controllers/Controller.php';
include 'controllers/ControllerEditable.php';

class ControllerCommissies extends Controller {
	var $model = null;

	function ControllerCommissies() {
		$this->model = get_model('DataModelCommissie');
	}
	
	function get_content($view, $iter = null, $params = null) {
		$this->run_header(array('title' => __('Commissies')));
		run_view($view, $this->model, $iter, $params);
		$this->run_footer();
	}

	function get_page_content(DataIter $commissie)
	{
		$this->run_header(array('title' => $commissie->get('naam')));
		$editable = new ControllerEditable($commissie->get('page'));
		$editable->run();
		$this->run_footer();
	}
	
	function run_impl() {
		if (!empty($_GET['commissie'])) {
			if ($iter = $this->model->get_from_name($_GET['commissie'])) {
				$this->get_page_content($iter);
			} else {
				$this->get_content('common::not_found');
			}
		} else {
			$iters = $this->model->get(false);
			$this->get_content('commissies::commissies', $iters);	
		}
	}
}

$controller = new ControllerCommissies();
$controller->run();


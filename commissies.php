<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	class ControllerCommissies extends Controller {
		var $model = null;

		function ControllerCommissies() {
			$this->model = get_model('DataModelCommissie');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Commissies')));
			run_view('commissies::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			$iters = $this->model->get(false);
			$this->get_content('commissies', $iters);
		}
	}
	
	$controller = new ControllerCommissies();
	$controller->run();
?>

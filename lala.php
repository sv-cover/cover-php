<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('include/member.php');
	require_once('include/login.php');
	
	class ControllerLALA extends Controller {
		var $model = null;

		function ControllerLALA() {
			$this->model = get_model('DataModelLALA');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Kleine LALA 2012/2013')));
			run_view('lala::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			$iters = $this->model->get(false);
			$this->get_content('lala', $iters);
		}
	}
	
	$controller = new ControllerLALA();
	$controller->run();
?>

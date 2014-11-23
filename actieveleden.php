<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once'include/member.php';

	class ControllerActieveLeden extends Controller {
		var $model = null;

		function ControllerActieveLeden() {
			$this->model = get_model('DataModelActieveLeden');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('ActieveLeden')));
			run_view('actieveleden::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$this->get_content('auth');
				return;
			}
			
			$this->get_content('index');
		}
	}
	
	$controller = new ControllerActieveLeden();
	$controller->run();
?>

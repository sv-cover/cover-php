<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerActieveLeden extends Controller
	{
		var $model = null;

		function ControllerActieveLeden() {
			$this->model = get_model('DataModelActieveLeden');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('ActieveLeden')));
			run_view($view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$this->get_content('auth');
				return;
			}

			$view = isset($_GET['view']) ? $_GET['view'] : 'current';

			switch ($view)
			{
				case 'current':
					$this->get_content('actieveleden::index');
					break;

				case 'history':
					$this->get_content('actieveleden::history');
					break;

				default:
					$this->get_content('not_found');
					break;
			}
		}
	}
	
	$controller = new ControllerActieveLeden();
	$controller->run();

<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	class ControllerWeblog extends Controller {
		var $model = null;

		function ControllerWeblog() {
			$this->model = get_model('DataModelForum');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Weblog')));
			run_view('weblog::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			$config_model = get_model('DataModelConfiguratie');
			$forumid = $config_model->get_value('weblog_forum');
			
			if ($forumid === null)
				$iters === null;
			else {
				$forum = $this->model->get_iter($forumid);
				
				if (!$forum)
					$iters = null;
				else
					$iters = $forum->get_last_thread(0, 20);
			}
			
			if ($iters === null)
				$this->get_content('error');
			else
				$this->get_content('weblog', $iters);
		}
	}
	
	$controller = new ControllerWeblog();
	$controller->run();
?>

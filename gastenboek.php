<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('form.php');
	require_once('login.php');

	class ControllerGastenboek extends Controller {
		var $model = null;

		function ControllerGastenboek() {
			$this->model = get_model('DataModelGastenboek');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Gastenboek')));
			run_view('gastenboek::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _view_gastenboek($params = null) {
			if (isset($_GET['page']))
				$page = $_GET['page'];
			else
				$page = 0;

			if (isset($_GET['search']))
				$iters = $this->model->search($_GET['search'], $page);
			else
				$iters = $this->model->get($page);
			
			$this->get_content('gastenboek', $iters, $params);			
		}
		
		/**
		 * Runs the rss view for the gastenboek.
		 *
		 * @return void
		 * @author Pieter de Bie
		 **/
		function _process_rss() {
			$iters = $this->model->get(0);
			run_view('gastenboek::rss', $this->model, $iters, null);
		}
		
		function _process() {
			$check = array(
					'naam',
					'message');
			
			$data = check_values($check, $errors);
			
			if (count($errors) > 0) {
				$this->_view_gastenboek(array('errors' => $errors));
				return;
			}
			
			/* Check spam */
			$data['spam'] = 0;

			if (!logged_in() && get_post('hash') != $this->model->anti_spam_hash()) {
				$this->model->increase_spam_count();
				header('Location: gastenboek.php');
				return;
			}
			
			/* Check lustrum */
			$data['lustrum'] = 0;
			if ((($this->model->get_total_posts()+1) % 5000 ) == 0)
				$data['lustrum'] =1;
		
			$data['email'] = get_post('email');
			$data['url'] = get_post('url');
			$data['ip'] = $_SERVER['REMOTE_ADDR'];

			$iter = new DataIter($this->model, -1, $data);
			$this->model->insert($iter);

			header('Location: gastenboek.php');
		}
		
		function run_impl() {
			if (isset($_POST['submgastenboek']))
				$this->_process();
			elseif (isset($_GET['rss']))
				$this->_process_rss();
			else
				$this->_view_gastenboek();
		}
	}
	
	$controller = new ControllerGastenboek();
	$controller->run();
?>

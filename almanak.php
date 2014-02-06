<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('member.php');
	class ControllerAlmanak extends Controller {
		var $model = null;

		function ControllerAlmanak() {
			$this->model = get_model('DataModelMember');

			if (member_in_commissie(COMMISSIE_BESTUUR))
				$this->model->visible_types = array(
					MEMBER_STATUS_LID,
					MEMBER_STATUS_LID_ONZICHTBAAR,
					MEMBER_STATUS_LID_AF,
					MEMBER_STATUS_DONATEUR
				);
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Almanak')));
			run_view('almanak::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _process_search() {
			$iters = $this->model->get_from_search($_GET['query']);
			$this->get_content('almanak', $iters, array('query' => $_GET['query']));
		}

		/** 
		  * Searches the online almanak for a given year
		  *
		  */
		function _process_year() {
			$iters = $this->model->get_from_search_year($_GET['search_year']);
			$this->get_content('almanak', $iters, array('query' => $_GET['search_year']));
		}

		function _process_status() {
			if (!member_in_commissie(COMMISSIE_BESTUUR))
				return $this->get_content('auth');
			
			$iters = $this->model->get_from_status($_GET['status']);
			$this->get_content('almanak', $iters, array('query' => ''));
		}
		
		function _process_csv() {
			if (member_in_commissie(COMMISSIE_ALMANAKCIE)) {
				$iters = $this->model->get_all();
				run_view('almanak::csv',$this->model,$iters,null);
			}
			else {
				$this->get_content('auth');
			}
		}
		
		function run_impl() {
			if (isset($_GET['query']))
				$this->_process_search();
			elseif (isset($_GET['search_year']))
				$this->_process_year();
			elseif (isset($_GET['status']))
				$this->_process_status();
			elseif (isset($_GET['csv']))
				$this->_process_csv();
			else
				$this->get_content('almanak', null, array('query' => ''));
		}
	}
	
	$controller = new ControllerAlmanak();
	$controller->run();
?>

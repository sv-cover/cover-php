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
			$iters = $this->model->get_from_search_first_last($_GET['search_first'], $_GET['search_last']);
			$this->get_content('almanak', $iters);				
		}
		
		/** 
		  * Searches the online almanak for a given year
		  *
		  */
		function _process_year() {
			$iters = $this->model->get_from_search_year($_GET['search_year']);
			$this->get_content('almanak', $iters);				
		}
		
		function _process_first() {
			$iters = $this->model->get_from_first_character($_GET['first']);
			$this->get_content('almanak', $iters);		
		}
		
		function _process_last() {
			$iters = $this->model->get_from_last_character($_GET['last']);
			$this->get_content('almanak', $iters);
		}
		
		function _process_csv() {
			if (member_in_commissie(COMMISSIE_ALMANAKCIE)) {
				$iters = $this->model->get_from_search_first_last("","");
				run_view('almanak::csv',$this->model,$iters,null);
			}
			else {
				$this->get_content('auth');
			}
		}
		
		function run_impl() {
			if (isset($_GET['search_first']) || isset($_GET['search_last']))
				$this->_process_search();
			elseif (isset($_GET['search_year']))
				$this->_process_year();
			elseif (isset($_GET['first']))
				$this->_process_first();
			elseif (isset($_GET['last']))
				$this->_process_last();
			elseif (isset($_GET['csv']))
				$this->_process_csv();
			else
				$this->get_content('almanak');
		}
	}
	
	$controller = new ControllerAlmanak();
	$controller->run();
?>

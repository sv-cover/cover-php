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
					MEMBER_STATUS_ERELID,
					MEMBER_STATUS_DONATEUR
				);
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Almanak')));
			run_view('almanak::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _process_search_legacy() {
			$iters = $this->model->get_from_search_first_last($_GET['search_first'], $_GET['search_last']);
			$this->get_content('almanak', $iters);				
		}

		function _process_search($query) {
			$iters = $this->model->search_first_last($query, 15);

			$preferred = parse_http_accept($_SERVER['HTTP_ACCEPT'],
				array('application/json', 'text/html', '*/*'));

			if ($preferred == 'application/json')
				echo json_encode(array_map(function($lid) {
					return array(
						'id' => $lid->get_id(),
						'beginjaar' => $lid->get('beginjaar'),
						'name' => member_full_name($lid));
				}, $iters));
			else
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

		function _process_status() {
			if (!member_in_commissie(COMMISSIE_BESTUUR))
				return $this->get_content('auth');
			
			$iters = $this->model->get_from_status($_GET['status']);
			$this->get_content('almanak', $iters);
		}
		
		function _process_csv() {
			if (member_in_commissie(COMMISSIE_ALMANAKCIE)) {
				$iters = $this->model->get_from_search_first_last(null, null);

				// Filter all previous and hidden members
				$this->model->visible_types = array(MEMBER_STATUS_LID,
					MEMBER_STATUS_ERELID, MEMBER_STATUS_DONATEUR);

				$iters = array_filter($iters, array($this->model, 'is_visible'));

				// Filter all hidden information (set the field to null)
				$privacy_fields = $this->model->get_privacy();

				// Remove the fields that have to be exported
				unset($privacy_fields['voornaam'], $privacy_fields['achternaam']);

				foreach ($iters as $iter)
				{
					foreach ($iter->data as $field => $value)
						if (array_key_exists($field, $privacy_fields))
							if (($this->model->get_privacy_for_field($iter, $field) & 1) === 0)
								$iter->data[$field] = null;

					$iter->data['status'] = $this->model->get_status($iter);

					$iter->data['studie'] = implode(', ', $iter->get('studie'));
				}

				run_view('almanak::csv',$this->model,$iters,null);
			}
			else {
				$this->get_content('auth');
			}
		}
		
		function run_impl() {
			if (isset($_GET['search_first']) || isset($_GET['search_last']))
				$this->_process_search_legacy();
			elseif (isset($_GET['search']))
				$this->_process_search($_GET['search']);
			elseif (isset($_GET['search_year']))
				$this->_process_year();
			elseif (isset($_GET['first']))
				$this->_process_first();
			elseif (isset($_GET['last']))
				$this->_process_last();
			elseif (isset($_GET['status']))
				$this->_process_status();
			elseif (isset($_GET['csv']))
				$this->_process_csv();
			else
				$this->get_content('almanak');
		}
	}
	
	$controller = new ControllerAlmanak();
	$controller->run();
?>

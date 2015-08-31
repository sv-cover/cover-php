<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerAlmanak extends Controller
	{
		var $model = null;

		public function __construct() 
		{
			$this->model = get_model('DataModelMember');

			// If current visitor is the board, show all members in the
			// database, including those that are no longer a member
			// and any users that are "deleted" by making them hidden.
			if (member_in_commissie(COMMISSIE_BESTUUR))
				$this->model->visible_types = array(
					MEMBER_STATUS_LID,
					MEMBER_STATUS_LID_ONZICHTBAAR,
					MEMBER_STATUS_UNCONFIRMED,
					MEMBER_STATUS_LID_AF,
					MEMBER_STATUS_ERELID,
					MEMBER_STATUS_DONATEUR
				);
		}
		
		protected function get_content($view, $iter = null, $params = null)
		{
			$this->run_header(array('title' => __('Almanak')));
			run_view('almanak::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		protected function _process_search($query)
		{
			$iters = $this->model->search_name($query,
				isset($_GET['limit']) ? $_GET['limit'] : null);

			$preferred = parse_http_accept($_SERVER['HTTP_ACCEPT'],
				array('application/json', 'text/html', '*/*'));

			// The JSON is mostly used by the text inputs that autosuggest names
			if ($preferred == 'application/json')
				echo json_encode(array_map(function($lid) {
					return array(
						'id' => $lid->get_id(),
						'starting_year' => $lid->get('beginjaar'),
						'name' => member_full_name($lid));
				}, $iters));
			else
				$this->get_content('almanak', $iters);
		}
		
		/** 
		  * Searches the online almanak for a given year
		  *
		  */
		protected function _process_year()
		{
			$iters = $this->model->get_from_search_year($_GET['search_year']);
			$this->get_content('almanak', $iters);				
		}
		
		protected function _process_status() {
			if (!member_in_commissie(COMMISSIE_BESTUUR))
				return $this->get_content('auth');
			
			$iters = $this->model->get_from_status($_GET['status']);
			$this->get_content('almanak', $iters);
		}
		
		protected function _process_csv()
		{
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
		
		protected function run_impl() {
			if (isset($_GET['search']))
				$this->_process_search($_GET['search']);
			elseif (isset($_GET['search_year']))
				$this->_process_year();
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

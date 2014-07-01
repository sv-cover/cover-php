<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('member.php');
	class ControllerAlmanak extends Controller {
		var $model = null;

		function ControllerAlmanak()
		{
			$this->model = get_model('DataModelMember');

			// By default, show everything in the database to
			// the board.
			if (member_in_commissie(COMMISSIE_BESTUUR))
			{
				$this->model->visible_types = array(
					MEMBER_STATUS_LID,
					MEMBER_STATUS_LID_ONZICHTBAAR,
					MEMBER_STATUS_LID_AF,
					MEMBER_STATUS_ERELID,
					MEMBER_STATUS_DONATEUR
				);
			}
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Almanak')));
			run_view('almanak::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		protected function _process_search($query = '')
		{
			if ($query) {
				$iters = $this->model->get_from_search($query);
			}
			else {
				// Tone down all that is visible a bit.
				$this->model->visible_types = array(
					MEMBER_STATUS_LID,
					MEMBER_STATUS_ERELID,
					MEMBER_STATUS_DONATEUR
				);
				$iters = $this->model->get();
			}

			$this->get_content('almanak', $iters, compact('query'));
		}

		protected function _process_status()
		{
			if (!member_in_commissie(COMMISSIE_BESTUUR))
				return $this->get_content('auth');
			
			$iters = $this->model->get_from_status($_GET['status']);

			$this->get_content('almanak', $iters, array('query' => ''));
		}
		
		protected function _process_csv()
		{
			if (member_in_commissie(COMMISSIE_ALMANAKCIE)) {

				// Filter all previous and hidden members
				$this->model->visible_types = array(MEMBER_STATUS_LID,
					MEMBER_STATUS_ERELID, MEMBER_STATUS_DONATEUR);

				$iters = $this->model->get();

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
			if (isset($_GET['status']))
				$this->_process_status();
			elseif (isset($_GET['csv']))
				$this->_process_csv();
			else
				$this->_process_search(isset($_GET['query']) ? $_GET['query'] : '');
		}
	}
	
	$controller = new ControllerAlmanak();
	$controller->run();

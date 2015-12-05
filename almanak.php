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
		
		protected function _process_export_csv()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_ALMANAKCIE))
				throw new UnauthorizedException('Only members of the YearbookCee committee are allowed to download these dumps.');

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

			run_view('almanak::csv', $this->model, $iters, null);
		}

		protected function _process_export_photos()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_ALMANAKCIE))
				throw new UnauthorizedException('Only members of the YearbookCee committee are allowed to download these dumps.');

			// Flush all of the current output and turn of the buffer
			while (ob_get_level() > 0)
				ob_end_clean();

			// Disable PHP's time limit
			set_time_limit(0);

			// Make sure we stop when the user is no longer listening
			ignore_user_abort(false);

			// Set up the output zip stream and just handle all files as large files
			// (meaning no compression, streaming stead of reading into memory.)
			$zip = new ZipStream\ZipStream('almanac-' . date('Y-m-d') . '.zip', [
				ZipStream\ZipStream::OPTION_LARGE_FILE_SIZE => 1,
				ZipStream\ZipStream::OPTION_LARGE_FILE_METHOD => 'store',
				ZipStream\ZipStream::OPTION_OUTPUT_STREAM => fopen('php://output', 'wb')]);

			// Now for each book find all photos and add them to the zip stream
			$iters = $this->model->get_from_search_first_last(null, null);

			// Filter all previous and hidden members
			$this->model->visible_types = [MEMBER_STATUS_LID, MEMBER_STATUS_ERELID, MEMBER_STATUS_DONATEUR];

			$iters = array_filter($iters, [$this->model, 'is_visible']);

			// Filter all hidden information (set the field to null)
			$privacy_fields = $this->model->get_privacy();

			foreach ($iters as $iter)
			{
				// Skip all members that have hidden their photo
				if (($this->model->get_privacy_for_field($iter, 'foto') & 1) === 0)
					continue;

				// Skip members that don't have a photo
				if (($data = $this->model->get_photo($iter)) === null)
					continue;

				$metadata = ['time' => $this->model->get_photo_mtime($iter)];

				// And finally add the photo to the actual stream
				$zip->addFile(sprintf('%d.jpg', $iter->get_id()), $data, $metadata);
			}

			$zip->finish();
		}
		
		protected function run_impl() {
			if (isset($_GET['search']))
				$this->_process_search($_GET['search']);
			elseif (isset($_GET['search_year']))
				$this->_process_year();
			elseif (isset($_GET['status']))
				$this->_process_status();
			elseif (isset($_GET['export']) && $_GET['export'] == 'csv')
				$this->_process_export_csv();
			elseif (isset($_GET['export']) && $_GET['export'] == 'photos')
				$this->_process_export_photos();
			else
				$this->get_content('almanak');
		}
	}
	
	$controller = new ControllerAlmanak();
	$controller->run();
?>

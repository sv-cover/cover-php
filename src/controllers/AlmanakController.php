<?php
	namespace App\Controller;

	use ZipStream\ZipStream;

	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/controllers/Controller.php';
	
	class AlmanakController extends \Controller
	{
		protected $view_name = 'almanak';

		public function __construct($request, $router)
		{
			$this->model = create_model('DataModelMember');

			parent::__construct($request, $router);
		}
		
		public function run_index_search($search)
		{
			$iters = $this->model->search_name($search,
				isset($_GET['limit']) ? $_GET['limit'] : null);

			// Filter out everyone who may not be seen
			$iters = array_filter($iters, [get_policy($this->model), 'user_can_read']);

			// Filter out everyone that doesn't want to be found by their name
			if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR)
				&& !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
				$iters = array_filter($iters, function($iter) {
					return !$iter->is_private('naam');
				});

			$preferred = isset($_SERVER['HTTP_ACCEPT'])
				? parse_http_accept($_SERVER['HTTP_ACCEPT'], array('application/json', 'text/html', '*/*'))
				: 'text/html';

			// The JSON is mostly used by the text inputs that autosuggest names
			if ($preferred == 'application/json')
				echo json_encode(array_map(function($lid) {
					return array(
						'id' => $lid->get_id(),
						'starting_year' => $lid->get('beginjaar'),
						'first_name' => member_first_name($lid),
						'name' => member_full_name($lid));
				}, array_values($iters)));
			else
				return $this->view->render_index($iters, compact('search'));
		}
		
		/** 
		  * Searches the online almanak for a given year
		  *
		  */
		public function run_index_year()
		{
			$year = (int) $_GET['search_year'];

			$iters = $this->model->get_from_search_year($year);

			$iters = array_filter($iters, [get_policy($this->model), 'user_can_read']);

			return $this->view->render_index($iters, compact('year'));
		}
		
		public function run_index_status()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR))
				throw new \UnauthorizedException('Only the Board of Cover is allowed to search by status');

			$status = $_GET['status'];
			
			$iters = $this->model->get_from_status($status);

			$iters = array_filter($iters, [get_policy($this->model), 'user_can_read']);

			return $this->view->render_index($iters, compact('status'));
		}
		
		public function run_export_csv()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_ALMANAKCIE))
				throw new \UnauthorizedException('Only members of the YearbookCee committee are allowed to download these dumps.');

			$iters = $this->model->get_from_search_first_last(null, null);

			$iters = array_filter($iters, [get_policy($this->model), 'user_can_read']);

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

			return $this->view->render_csv($iters);
		}

		public function run_export_photos()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_ALMANAKCIE))
				throw new \UnauthorizedException('Only members of the YearbookCee committee are allowed to download these dumps.');

			// Flush all of the current output and turn of the buffer
			while (ob_get_level() > 0 && ob_end_clean());

			// Disable PHP's time limit
			set_time_limit(0);

			// Make sure we stop when the user is no longer listening
			ignore_user_abort(false);

			// Set up the output zip stream and just handle all files as large files
			// (meaning no compression, streaming stead of reading into memory.)
			$zip = new ZipStream('almanac-' . date('Y-m-d') . '.zip', [
				ZipStream::OPTION_LARGE_FILE_SIZE => 1,
				ZipStream::OPTION_LARGE_FILE_METHOD => 'store',
				ZipStream::OPTION_OUTPUT_STREAM => fopen('php://output', 'wb')]);

			// Now for each book find all photos and add them to the zip stream
			$iters = $this->model->get_from_search_first_last(null, null);

			$iters = array_filter($iters, [get_policy($this->model), 'user_can_read']);

			// Filter all hidden information (set the field to null)
			$privacy_fields = $this->model->get_privacy();

			foreach ($iters as $iter)
			{
				// Skip all members that have hidden their photo
				if (($this->model->get_privacy_for_field($iter, 'foto') & 1) === 0)
					continue;

				// Skip members that don't have a photo
				if (($data = $this->model->get_photo_stream($iter)) === null)
					continue;

				$metadata = ['time' => $this->model->get_photo_mtime($iter)];

				// And finally add the photo to the actual stream
				$zip->addFileFromStream(sprintf('%d.jpg', $iter->get_id()), $data['foto'], $metadata);
			}

			$zip->finish();
		}
		
		protected function run_impl()
		{
			if (isset($_GET['search']))
				return $this->run_index_search($_GET['search']);
			elseif (isset($_GET['search_year']))
				return $this->run_index_year();
			elseif (isset($_GET['status']))
				return $this->run_index_status();
			elseif (isset($_GET['export']) && $_GET['export'] == 'csv')
				return $this->run_export_csv();
			elseif (isset($_GET['export']) && $_GET['export'] == 'photos')
				return $this->run_export_photos();
			else
				return $this->view->render_index();
		}
	}

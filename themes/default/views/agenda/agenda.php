<?php
	require_once 'include/login.php';
	require_once 'include/markup.php';
	require_once 'include/facebook.php';
	
	class AgendaView extends View {
		protected $__file = __FILE__;

		protected $model;

		public function __construct()
		{
			$this->model = get_model('DataModelAgenda');

			$this->facebook = get_facebook();
		}

		public function get_cover_photo($item)
		{
			if (!$item->has('facebook_id'))
				return null;

			try {
				$reponse = $this->facebook->api('/' . $item->get('facebook_id') . '?fields=cover', 'GET');

				if (isset($reponse['cover']))
					return $reponse['cover']['source'];
			} catch (Exception $e) {
				//
			}

			return null;
		}

		public function get_attending($item)
		{
			if (!$item->has('facebook_id'))
				return array();

			try {
				$response = $this->facebook->api('/' . $item->get('facebook_id') . '/attending?fields=name,picture', 'GET');

				if (isset($response['data']))
					return $response['data'];
			} catch (Exception $e) {
				//
			}

			return array();
		}

		public function get_title()
		{
			$title = $this->get_selected_year()
				? sprintf(__('Agenda %d-%d'), $this->get_selected_year(), $this->get_selected_year() + 1)
				: __('Agenda');

			return str_replace('-', '&ndash;', $title);
		}

		public function get_selected_year()
		{
			return isset($_GET['year']) ? intval($_GET['year']) : null;
		}

		public function get_current_year()
		{
			return time() < mktime(0, 0, 9, 1, date('Y'))
				? date('Y') - 1
				: date('Y');
		}

		public function get_previous_year()
		{
			return ($year = $this->get_selected_year()) !== null
				? $year - 1
				: $this->get_current_year();
		}

		public function get_next_year()
		{
			return ($year = $this->get_selected_year()) !== null
				&& $year != $this->get_current_year()
				? $year + 1
				: null;
		}

		public function get_paged_agendapunten()
		{
			$selected_year = $this->get_selected_year();

			if ($selected_year === null)
				return $this->model->get_agendapunten(logged_in());
			
			$from = sprintf('%d-09-01', $selected_year);
			$till = sprintf('%d-08-31', $selected_year + 1);

			$punten = $this->model->get($from, $till, true);

			if (!logged_in())
				$punten = array_filter($punten, array($this, '_test_is_private'));

			return $punten;
		}

		public function _test_is_private($punt)
		{
			return $punt->get('private');
		}
	}

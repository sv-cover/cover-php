<?php
	require_once 'include/login.php';
	require_once 'include/markup.php';
	require_once 'include/cache.php';
	
	class AgendaView extends View {
		protected $__file = __FILE__;

		protected $model;

		protected $facebook;

		protected $facebook_cache;

		public function __construct()
		{
			$this->model = get_model('DataModelAgenda');

			if (get_config_value('enable_facebook', false)) {
				require_once 'include/facebook.php';
				$this->facebook = get_facebook();
			}
		}

		public function get_cover_photo($item)
		{
			if (!$this->facebook)
				return null;

			if (!$item->has('facebook_id'))
				return null;

			
			$response = wrap_cache($this->facebook, 3600, CacheDecorator::CATCH_EXCEPTION)->api('/' . $item->get('facebook_id') . '?fields=cover', 'GET');

			if (isset($response['cover']))
				return array(
					'src' => $response['cover']['source'],
					'x' => $response['cover']['offset_x'],
					'y' => $response['cover']['offset_y']);
			
			return null;
		}

		public function get_attending($item)
		{
			if (!$this->facebook)
				return array();

			if (!$item->has('facebook_id'))
				return array();

			$response = wrap_cache($this->facebook, 300, CacheDecorator::CATCH_EXCEPTION)->api('/' . $item->get('facebook_id') . '/attending?fields=name,picture', 'GET');

			if (isset($response['data']))
				return $response['data'];
			
			return array();
		}

		public function get_rsvp_status($item)
		{
			if (!$this->facebook)
				return null;

			if (!$item->has('facebook_id'))
				return null;

			if (!$this->facebook->getUser())
				return null;

			$response = wrap_cache($this->facebook, 300, CacheDecorator::CATCH_EXCEPTION)->api('/' . $item->get('facebook_id') . '/invited/' . $this->facebook->getUser(), 'GET');

			if (isset($response['data']) && count($response['data']))
				return $response['data'][0];
			
			return null;
		}

		public function get_rsvp_status_text($rsvp_status)
		{
			switch ($rsvp_status['rsvp_status'])
			{
				case 'unsure':
					return __('Ik ga misschien');

				case 'attending':
					return __('Ik ben erbij');

				case 'declined':
					return __('Ik ga niet');

				case '':
				case 'not_replied':
					return __('Neem deel');

				default:
					return markup_format_text($rsvp_status['rsvp_status']);
			}
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
			return time() < mktime(0, 0, 0, 9, 1, date('Y'))
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

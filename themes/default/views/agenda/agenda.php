<?php
	require_once 'include/login.php';
	require_once 'include/markup.php';
	
	class AgendaView extends CRUDView
	{
		public $facebook = null;

		public function __construct(Controller $controller, $path)
		{
			parent::__construct($controller, $path);

			if (get_config_value('enable_facebook', false))
			{
				require_once 'include/facebook.php';
				$this->facebook = get_facebook();
			}
		}

		public function render_index($iters)
		{
			$months = get_months();
				
			$days = get_days();

			$show_year = $this->selected_year() != $this->current_year();

			return $this->twig->render('index.twig', compact('iters', 'months', 'days', 'show_year'));
		}

		public function render_read(DataIter $iter, array $extra = [])
		{
			$mutations = array_filter($iter->get_proposals(), [get_policy($iter), 'user_can_read']);

			$mutation = count($mutations) > 0 ? current($mutations) : null;

			return $this->twig->render('single.twig', array_merge(compact('iter', 'mutation'), $extra));
		}

		public function render_moderate($iters, $highlighted_id)
		{
			return $this->render('moderate.twig', compact('iters', 'highlighted_id'));
		}

		public function render_401_unauthorized(UnauthorizedException $e)
		{
			header('Status: 401 Unauthorized');
			header('WWW-Authenticate: FormBased');
			return $this->render('unauthorized.twig', ['exception' => $e]);
		}

		public function cover_photo(DataIterAgenda $item)
		{
			if (!$this->facebook)
				return null;

			if (!$item['facebook_id'])
				return null;
			
			if ($cover_photo = $this->facebook->getCoverPhoto($item['facebook_id']))
				return $cover_photo;
			else
				return array(
					'src' => get_theme_data('images/default_cover_photo.png'),
					'x' => 0, 'y' => 0);
		}

		public function attendees(DataIterAgenda $item)
		{
			if (!$this->facebook)
				return array();

			if (!$item['facebook_id'])
				return array();

			return $this->facebook->getAttending($item->get('facebook_id'));
		}

		public function rsvp_status(DataIterAgenda $item)
		{
			throw new Exception('Not implemented at this moment');
		}

		public function rsvp_status_text($rsvp_status)
		{
			switch ($rsvp_status['rsvp_status'])
			{
				case 'unsure':
					return __('I might go');

				case 'attending':
					return __('I\'m going');

				case 'declined':
					return __('I\'m not going');

				case '':
				case 'not_replied':
					return __('Attend');

				default:
					return $rsvp_status['rsvp_status'];
			}
		}

		public function available_committees()
		{
			$commissies = array();

			$model = get_model('DataModelCommissie');

			if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
				foreach ($model->get(null, true) as $commissie)
					$commissies[$commissie->get_id()] = $commissie->get('naam');
			else
				foreach (get_identity()->member()->get('committees') as $commissie)
					$commissies[$commissie] = $model->get_naam($commissie);

			return $commissies;
		}

		public function title()
		{
			$title = $this->selected_year()
				? sprintf(__('Calendar %d-%d'), $this->selected_year(), $this->selected_year() + 1)
				: __('Calendar');

			return str_replace('-', '–', $title);
		}

		public function selected_year()
		{
			return isset($_GET['year']) ? intval($_GET['year']) : null;
		}

		public function current_year()
		{
			return time() < mktime(0, 0, 0, 9, 1, date('Y'))
				? date('Y') - 1
				: date('Y');
		}

		public function previous_year()
		{
			return ($year = $this->selected_year()) !== null
				&& $year > 2002
				? $year - 1
				: $this->current_year();
		}

		public function next_year()
		{
			return ($year = $this->selected_year()) !== null
				&& $year != $this->current_year()
				? $year + 1
				: null;
		}

		public function paged_agendapunten()
		{
			$selected_year = $this->selected_year();

			if ($selected_year === null)
				return $this->model->get_agendapunten();
			
			$from = sprintf('%d-09-01', $selected_year);
			$till = sprintf('%d-08-31', $selected_year + 1);

			$punten = $this->model->get($from, $till, true);

			return $punten;
		}
	}

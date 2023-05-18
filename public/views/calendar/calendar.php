<?php
require_once 'src/framework/login.php';
require_once 'src/framework/markup.php';

class CalendarView extends CRUDFormView
{
	public $facebook = null;

	public function __construct(Controller $controller, $path)
	{
		parent::__construct($controller, $path);

		if (get_config_value('enable_facebook', false))
		{
			require_once 'src/services/facebook.php';
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

	public function render_401_unauthorized(UnauthorizedException $e)
	{
		header('Status: 401 Unauthorized');
		header('WWW-Authenticate: FormBased');
		return $this->render('unauthorized.twig', ['exception' => $e]);
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

	public function list_view_mode()
	{
		$cookie = $_COOKIE['cover_calendar_mode'] ?? 'grid';
		// Explicitly test, to prevent weird data
		return $cookie === 'list' ? 'list' : 'grid';
	}
}

<?php
	namespace App\Controller;

	require_once 'src/framework/member.php';
	require_once 'src/framework/login.php';
	require_once 'src/framework/form.php';
	require_once 'src/framework/webcal.php';
	require_once 'src/framework/markup.php';
	require_once 'src/framework/controllers/ControllerCRUDForm.php';

	use App\Form\Type\EventFormType;
	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

	
	class CalendarController extends \ControllerCRUDForm
	{
		protected $_var_id = 'agenda_id';

		protected $view_name = 'calendar';

	    protected $form_type = EventFormType::class;

		public function __construct($request, $router)
		{
			$this->model = get_model('DataModelAgenda');

			parent::__construct($request, $router);
		}

		public function path(string $view, \DataIter $iter = null, bool $json = false)
		{
			$parameters = [
				'view' => $view,
			];

			if (isset($iter))
			{
				$parameters[$this->_var_id] = $iter->get_id();

				if ($json)
					$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
			}

			return $this->generate_url('calendar', $parameters);
		}
		
		public function _check_datum($name, $value)
		{
			/* If this is the tot field and we don't use tot
			 * then set that value to null and return true
			 */
			if ($name == 'tot' && empty(trim($value)))
				return null;
			
			try {
				$date = new \DateTime($value);
				if ($date < new \DateTime())
					return false;
				return $date->format('Y-m-d H:i');
			} catch (\Exception $e) {
				return false;
			}
		}
		
		public function _check_length($name, $value)
		{
			$lengths = array('kop' => 100, 'locatie' => 100);

			if (!$value)
				return false;
			
			if (isset($lengths[$name]) && strlen($value) > $lengths[$name])
				return false;
			
			return $value;
		}
		
		public function _check_locatie($name, $value)
		{
			$locatie = get_post('locatie');

			if (empty(trim($locatie)))
				return null;

			return $this->_check_length('locatie', $locatie);
		}
		
		public function _check_image_url($name, $value)
		{
			// Image is optional
			if (empty(trim($value)))
				return null;

			// Max length == 255
			if (strlen($value) > 255)
				return false;

			// Only accept image file (using naive extension check)
			$ext = pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION);
			$allowed_exts = get_config_value('filemanager_image_extensions', ['jpg', 'jpeg', 'png']);
			if (in_array(strtolower($ext), $allowed_exts))
				return $value;

			return false;
		}

		public function _check_facebook_id($name, $value)
		{
			if (trim($value) == '')
				return null;

			$result = preg_match('/^https:\/\/www\.facebook\.com\/events\/(\d+)\//', $value, $matches);

			if ($result)
				$value = $matches[1];
			
			if (strlen($value) <= 20  && ctype_digit($value))
				return $value;

			return false;
		}

		public function _check_commissie($name, $value)
		{
			if (get_identity()->member_in_committee($value)
				|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
				|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
				return $value;
			
			return false;
		}

		protected function _check_values($iter, &$errors)
		{
			/* Check/format all the items */
			$data = check_values(
				array(
					array('name' => 'kop', 'function' => array($this, '_check_length')),
					'beschrijving',
					array('name' => 'committee_id', 'function' => array($this, '_check_commissie')),
					array('name' => 'van', 'function' => array($this, '_check_datum')),
					array('name' => 'tot', 'function' => array($this, '_check_datum')),
					array('name' => 'locatie', 'function' => array($this, '_check_locatie')),
					array('name' => 'image_url', 'function' => array($this, '_check_image_url')),
					array('name' => 'private', 'function' => 'check_value_checkbox'),
					array('name' => 'extern', 'function' => 'check_value_checkbox'),
					array('name' => 'facebook_id', 'function' => array($this, '_check_facebook_id'))),
				$errors);

			if (count($errors) > 0)
				return false;

			if ($data['tot'] === null)
				$data['tot'] = $data['van'];
			
			if (new \DateTime($data['van']) > new \DateTime($data['tot'])) {
				$errors[] = 'tot';
				return false;
			}
		
			return $data;
		}

		protected function _changed_values($iter, $data)
		{
			$changed = array();

			foreach ($data as $field => $value)
			{
				$current = $iter[$field];

				// Unfortunately, we need to 'normalize' the time fields for this to work
				if ($field == 'van' || $field == 'tot') {
					$current = strtotime($iter[$field]);
					$value = strtotime($value);
				}

				if ($current != $value)
					$changed[] = $field;
			}

			return $changed;
		}

		protected function _process_create(\DataIter $iter)
		{
			// Huh, why are we checking again? Didn't we already check in the run_create() method?
			// Well, yes, but sometimes a policy is picky about how you fill in the data!
			if (!\get_policy($iter)->user_can_create($iter))
				throw new \UnauthorizedException('You are not allowed to create this DataIter according to the policy.');

			// Some things break without end date (tot), so set end date to start date (van)
			if (empty($iter['tot']))
				$iter['tot'] = $iter['van'];

			$id = $this->model->propose_insert($iter);

			$iter->set_id($id);

			$_SESSION['alert'] = __('The new event is now waiting for approval. Once the board has accepted the event, it will be published on the website.');

			$placeholders = [
				'commissie_naam' => get_model('DataModelCommissie')->get_naam($iter['committee_id']),
				'member_naam' => member_full_name(get_identity()->member(), IGNORE_PRIVACY)
			];

			mail(
				get_config_value('defer_email_to', get_config_value('email_bestuur')),
				'New event ' . $iter['kop'],
				parse_email('agenda_add.txt', array_merge($iter->data, $placeholders, ['id' => $id])),
				"From: Study Association Cover <noreply@svcover.nl>\r\n"
			);

			return true;
		}

		public function run_update(\DataIter $iter)
		{
			if (!\get_policy($this->model)->user_can_update($iter))
				throw new \UnauthorizedException('You are not allowed to edit this ' . get_class($iter) . '.');

			$orig = \DataIterAgenda::from_iter($iter);

			$success = false;

			$form = $this->get_form($iter);

			if ($form->isSubmitted() && $form->isValid()) {
				// We could set $skip_confirmation in one statement, but I find this more readable
				$skip_confirmation = false;

				// If you update the facebook-id, description, image or location, no need to reconfirm.
				if (!array_diff($this->_changed_values($orig, $iter->data), ['facebook_id', 'beschrijving', 'image_url', 'locatie']))
					$skip_confirmation = true;

				// Unless the event was in the past, then we need confirmation as we most likely shouldn't be changing things anyway
				if ((empty($orig['tot_datetime']) && $orig['van_datetime'] < new \DateTime()) || $orig['tot_datetime'] < new \DateTime())
					$skip_confirmation = false;

				// Previous exists and there is no need to let the board confirm it
				if ($skip_confirmation)
				{
					$this->model->update($iter);

					$_SESSION['alert'] = __("The changes you've made to this event have been published.");
				}

				// Previous item exists but it needs to be confirmed first.
				else
				{
					$override_id = $this->model->propose_update($iter);

					$_SESSION['alert'] = __('The changes to the event are waiting for approval. Once the board has accepted the changes, they will be published on the website.');

					$placeholders = [
						'commissie_naam' => get_model('DataModelCommissie')->get_naam($iter['committee_id']),
						'member_naam' => member_full_name(get_identity()->member(), IGNORE_PRIVACY)
					];

					mail(
						get_config_value('defer_email_to', get_config_value('email_bestuur')),
						'Updated event ' . $iter['kop'] . ($iter->get('kop') != $orig->get('kop') ? ' was ' . $orig->get('kop') : ''),
						parse_email('agenda_mod.txt', array_merge($iter->data, $placeholders, ['id' => $override_id])),
						"From: Study Association Cover <noreply@svcover.nl>\r\n"
					);
				}

				$success = true;
			}

			return $this->view()->render_update($iter, $form, $success);
		}

		protected function _index()
		{
			$selected_year = isset($_GET['year']) ? intval($_GET['year']) : null;

			// No screwing around with invalid dates anymore
			if ($selected_year < 1993 || $selected_year > date('Y') + 2)
				$selected_year = null;

			if ($selected_year === null)
				return $this->model->get_agendapunten();
			
			$from = sprintf('%d-09-01', $selected_year);
			$till = sprintf('%d-08-31', $selected_year + 1);

			$punten = $this->model->get($from, $till, true);

			return $punten;
		}
		
		public function run_moderate(\DataIterAgenda $item = null)
		{
			if ($this->_form_is_submitted('moderate'))
				if ($this->_moderate())
					return $this->view->redirect($this->generate_url('calendar'));

			$agenda_items = array_filter($this->model->get_proposed(), [get_policy($this->model), 'user_can_moderate']);

			return $this->view->render_moderate($agenda_items, $item ? $item['id'] : null);
		}
		
		protected function _moderate()
		{
			$cancelled = array();

			foreach ($_POST as $field => $value)
			{
				if (!preg_match('/action_([0-9]+)/i', $field, $matches))
					continue;
				
				$id = $matches[1];

				$iter = $this->model->get_iter($id);
				
				if (!get_policy($this->model)->user_can_moderate($iter))
					throw new \UnauthorizedException();

				if ($value == 'accept') {
					/* Accept agendapunt */

					// If it is marked private, set that perference first.
					$iter['private'] = !empty($_POST['private_' . $iter['id']]) ? 1 : 0;
					
					$iter->update();
					
					$this->model->accept_proposal($iter);
				} elseif ($value == 'cancel') {
					/* Remove agendapunt and inform owner of the agendapunt */
					$this->model->reject_proposal($iter);
					
					$data = $iter->data;
					$data['member_naam'] = member_full_name(null, IGNORE_PRIVACY);
					$data['reden'] = get_post('comment_' . $id);

					$subject = 'Agendapunt ' . $iter['kop'] . ' geweigerd';
					$body = parse_email('agenda_cancel.txt', $data);
					
					$commissie_model = get_model('DataModelCommissie');
					$email = get_config_value('defer_email_to', $commissie_model->get_email($iter['committee_id']));

					mail($email, $subject, $body, "From: Study Association Cover <noreply@svcover.nl>\r\n");
					$cancelled[] = $commissie_model->get_naam($iter['committee_id']);
				}
			}
			
			$cancelled_un = array_unique($cancelled);
			$s = implode(', ', $cancelled_un);

			if (count($cancelled_un) == 1)
				if (count($cancelled) == 1) {
					$_SESSION['alert'] = sprintf(__('The committee %s has been notified of the denying of the calendar event.'), $s);
				} else {
					$_SESSION['alert'] = sprintf(__('The committee %s has been notified of the denying of the calendar events.'), $s);
				}
			elseif (count($cancelled_un) > 0)
				$_SESSION['alert'] = sprintf(__('The committees %s have been notified of the denying of the calendar events.'), $s);
			
			return true;
		}

		public function run_rsvp_status($iter)
		{
			// If the id's for agenda items had been consistend, we could have stored attendance locally.
			// Now, that would be a giant hack. Therefore, I defer that to some other moment in time.

			if (!get_config_value('enable_facebook', false))
				return;

			if (!$iter['facebook_id'])
				return;

			require_once 'src/services/facebook.php';
			$facebook = get_facebook();

			if (!$facebook->getUser())
				throw new \Exception('Could not get facebook user. Please try to reconnect your Facebook account.');

			switch ($_POST['rsvp_status'])
			{
				case 'attending':
				case 'maybe':
				case 'declined':
					$result = $facebook->api(sprintf('/%d/%s' , $iter['facebook_id'], $_POST['rsvp_status']), 'POST');
					break;

				default:
					throw new \Exception('Unknown rsvp status');
			}

			return $this->view->redirect($this->generate_url('calendar', [$this->_var_id => $iter['id']]));
		}

		public function run_webcal()
		{
			$cal = new \WebCal_Calendar('Cover');
			$cal->description = __('All activities of study association Cover');

			$fromdate = new \DateTime();
			$fromdate = $fromdate->modify('-1 year')->format('Y-m-d');

			$punten = array_filter($this->model->get($fromdate, null, true), [get_policy($this->model), 'user_can_read']);
			
			$timezone = new \DateTimeZone('Europe/Amsterdam');

			foreach ($punten as $punt)
			{
				if (!get_policy($this->model)->user_can_read($punt))
					continue;

				$event = new \WebCal_Event;
				$event->uid = $punt->get_id() . '@svcover.nl';
				$event->start = new \DateTime($punt['van'], $timezone);

				if (empty($punt['tot']) || $punt['van'] == $punt['tot']) {
					$event->end = new \DateTime($punt['van'], $timezone);
					$event->end->modify('+ 2 hour');
				} else {
					$event->end = new \DateTime($punt['tot'], $timezone);
				}
				
				$event->summary = $punt['extern']
					? $punt['kop']
					: sprintf('%s: %s', $punt['committee__naam'], $punt['kop']);
				$event->description = markup_strip($punt['beschrijving']);
				$event->location = $punt->get('locatie');
				$event->url = $this->generate_url('calendar', ['agenda_id' => $punt->get_id()], UrlGeneratorInterface::ABSOLUTE_URL);
				$cal->add_event($event);
			}

			$external_url = get_config_value('url_to_external_ical');

			if ($external_url){
				try {
					$external = file_get_contents($external_url);
					$cal->inject($external);
				} catch (\Exception $e) {
					// if something goes wrong, just don't merge with external agenda
				}
			}

			$cal->publish('cover.ics');
			return null;
		}

		public function run_suggest_location()
		{
			$limit = isset($_GET['limit'])
				? (int) $_GET['limit']
				: 100;

			$locations = $this->model->find_locations($_GET['search'], $limit);

			return $this->view->render_json($locations, $limit);
		}

		public function run_preview()
		{
			return markup_parse($_POST['beschrijving']);
		}

		public function run_subscribe()
		{
			return $this->view->render('subscribe.twig');
		}

		public function run_slide()
		{
			$events = $this->model->get_agendapunten();
			return $this->view->render_slide($events);
		}

		public function get_parameter($key, $default=null)
		{
			// Compatibility
			if ($key == 'view' && parent::get_parameter('format') == 'webcal')
				return 'webcal';
			return parent::get_parameter($key, $default);
		}
	}

<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/login.php';
	require_once 'include/form.php';
	require_once 'include/webcal.php';
	require_once 'include/markup.php';
	require_once 'include/controllers/ControllerCRUD.php';
	
	class ControllerAgenda extends ControllerCRUD
	{
		protected $_var_id = 'agenda_id';
		
		public function __construct()
		{
			$this->model = get_model('DataModelAgenda');

			$this->policy = get_policy($this->model);

			$this->view = View::byName('agenda', $this);
		}
		
		public function _check_datum($name, $value)
		{
			/* If this is the tot field and we don't use tot
			 * then set that value to null and return true
			 */
			if ($name == 'tot' && !get_post('use_tot'))
				return null;
			
			$fields = array('jaar', 'maand', 'datum');
			
			/* Check for valid numbers */
			$value = '';
			
			for ($i = 0; $i < count($fields); $i++) {
				$field = $fields[$i];

				if (!is_numeric(get_post($name . $field)))
					return false;
				
				if ($value != '')
					$value .= '-';

				$value .= get_post($name . $field);
			}
			
			$value .= ' ' . (is_numeric(get_post($name . 'uur')) ? intval(get_post($name . 'uur')) : '00');
			$value .= ':' . (is_numeric(get_post($name . 'minuut')) ? intval(get_post($name . 'minuut')) : '00');
			
			return $value;
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
			$locatie = get_post('use_locatie');
			check_value_checkbox($name, $locatie);

			if (!$locatie)
				return null;
			
			$locatie = get_post('locatie');

			if (!$locatie)
				return false;

			return $this->_check_length('locatie', $locatie);
		}

		public function _check_facebook_id($name, $value)
		{
			if (trim($value) == '')
				return null;

			if (strlen($value) <= 20  && ctype_digit($value))
				return $value;

			return false;
		}

		public function _check_commissie($name, $value)
		{
			if (member_in_commissie($value)
				|| member_in_commissie(COMMISSIE_BESTUUR)
				|| member_in_commissie(COMMISSIE_KANDIBESTUUR))
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
					array('name' => 'commissie', 'function' => array($this, '_check_commissie')),
					array('name' => 'van', 'function' => array($this, '_check_datum')),
					array('name' => 'tot', 'function' => array($this, '_check_datum')),
					array('name' => 'locatie', 'function' => array($this, '_check_locatie')),
					array('name' => 'private', 'function' => 'check_value_checkbox'),
					array('name' => 'extern', 'function' => 'check_value_checkbox'),
					array('name' => 'facebook_id', 'function' => array($this, '_check_facebook_id'))),
				$errors);

			if (count($errors) > 0)
				return false;

			if ($data['tot'] === null)
				$data['tot'] = $data['van'];

			return $data;
		}

		protected function _changed_values($iter, $data)
		{
			$changed = array();

			foreach ($data as $field => $value)
			{
				$current = $iter->get($field);

				// Unfortunately, we need to 'normalize' the time fields for this to work
				if ($field == 'van' || $field == 'tot') {
					$current = strtotime($iter->get($field));
					$value = strtotime($value);
				}

				if ($current != $value)
					$changed[] = $field;
			}

			return $changed;
		}
		
		protected function _create(DataIter $iter, $data, array &$errors)
		{
			if (($data = $this->_check_values($iter, $errors)) === false)
				return false;

			// Placeholders for e-mail
			$placeholders = array(
				'commissie_naam' => get_model('DataModelCommissie')->get_naam($data['commissie']),
				'member_naam' => member_full_name(null, IGNORE_PRIVACY));

			$iter->data = $data;

			$id = $this->model->propose_insert($iter, true);

			$iter->set_id($id);
				
			$_SESSION['alert'] = __('Het nieuwe agendapunt is in de wachtrij geplaatst. Zodra het bestuur ernaar gekeken heeft zal het punt op de website geplaatst worden');

			mail(
				get_config_value('defer_email_to', get_config_value('email_bestuur')),
				'Nieuw agendapunt ' . $data['kop'],
				parse_email('agenda_add.txt', array_merge($data, $placeholders, array('id' => $id))),
				"From: webcie@ai.rug.nl\r\n");

			return true;
		}

		protected function _update(DataIter $iter, $data, array &$errors)
		{
			if (($data = $this->_check_values($iter, $errors)) === false)
				return false;

			$skip_confirmation = false;

			// If you update the facebook-id, description or location, no need to reconfirm.
			if (!array_diff($this->_changed_values($iter, $data), array('facebook_id', 'beschrijving', 'locatie')))
				$skip_confirmation = true;

			// Placeholders for e-mail
			$placeholders = array(
				'commissie_naam' => get_model('DataModelCommissie')->get_naam($data['commissie']),
				'member_naam' => member_full_name(null, IGNORE_PRIVACY));

			// Previous exists and there is no need to let the board confirm it
			if ($skip_confirmation)
			{
				foreach ($data as $field => $value)
					$iter->set($field, $value);

				$this->model->update($iter);

				$_SESSION['alert'] = __('De wijzigingen voor het agendapunt zijn geplaatst.');
			}

			// Previous item exists but it needs to be confirmed first.
			else
			{
				$mod = new DataIterAgenda($this->model, -1, $data);

				$override_id = $this->model->propose_update($mod, $iter);

				$_SESSION['alert'] = __('De wijzigingen voor het agendapunt zijn opgestuurd. Zodra het bestuur ernaar gekeken heeft zal het punt op de website gewijzigd worden.');

				mail(
					get_config_value('defer_email_to', get_config_value('email_bestuur')),
					'Gewijzigd agendapunt ' . $data['kop'] . ($mod->get('kop') != $iter->get('kop') ? ' was ' . $iter->get('kop') : ''),
					parse_email('agenda_mod.txt', array_merge($data, $placeholders, array('id' => $override_id))),
					"From: webcie@ai.rug.nl\r\n");
			}

			return true;
		}

		protected function _index()
		{
			$selected_year = isset($_GET['year']) ? intval($_GET['year']) : null;

			if ($selected_year === null)
				return $this->model->get_agendapunten();
			
			$from = sprintf('%d-09-01', $selected_year);
			$till = sprintf('%d-08-31', $selected_year + 1);

			$punten = $this->model->get($from, $till, true);

			return $punten;
		}
		
		public function run_moderate($id)
		{
			if ($this->_form_is_submitted('moderate'))
				if ($this->_moderate())
					return $this->view->redirect('agenda.php');

			$agenda_items = array_filter($this->model->get_proposed(), [$this->policy, 'user_can_moderate']);

			return $this->view->render_moderate($agenda_items, $id);
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
				
				if (!$this->policy->user_can_moderate($iter))
					throw new UnauthorizedException();

				if ($value == 'accept') {
					/* Accept agendapunt */

					// If it is marked private, set that perference first.
					$iter['private'] = isset($_POST['private_' . $iter['id']]) ? 1 : 0;
					$iter->update();
					
					$this->model->accept_proposal($iter);
				} elseif ($value == 'cancel') {
					/* Remove agendapunt and inform owner of the agendapunt */
					$this->model->reject_proposal($iter);
					
					$data = $iter->data;
					$data['member_naam'] = member_full_name(null, IGNORE_PRIVACY);
					$data['reden'] = get_post('comment_' . $id);

					$subject = 'Agendapunt ' . $iter->get('kop') . ' geweigerd';
					$body = parse_email('agenda_cancel.txt', $data);
					
					$commissie_model = get_model('DataModelCommissie');
					$email = get_config_value('defer_email_to', $commissie_model->get_email($iter->get('commissie')));

					mail($email, $subject, $body, "From: webcie@ai.rug.nl\r\n");
					$cancelled[] = $commissie_model->get_naam($iter->get('commissie'));
				}
			}
			
			$cancelled_un = array_unique($cancelled);
			$s = implode(', ', $cancelled_un);

			if (count($cancelled_un) == 1)
				if (count($cancelled) == 1) {
					$_SESSION['alert'] = sprintf(__('De commissie %s is op de hoogte gesteld van het weigeren van het agendapunt.'), $s);
				} else {
					$_SESSION['alert'] = sprintf(__('De commissie %s is op de hoogte gesteld van het weigeren van de agendapunten.'), $s);
				}
			elseif (count($cancelled_un) > 0)
				$_SESSION['alert'] = sprintf(__('De commissies %s zijn op de hoogte gesteld van het weigeren van de agendapunten.'), $s);
			
			return true;
		}

		public function run_rsvp_status($iter)
		{
			// If the id's for agenda items had been consistend, we could have stored attendance locally.
			// Now, that would be a giant hack. Therefore, I defer that to some other moment in time.

			if (!get_config_value('enable_facebook', false))
				return;

			if (!$iter->get('facebook_id'))
				return;

			require_once 'include/facebook.php';
			$facebook = get_facebook();

			if (!$facebook->getUser())
				throw new Exception('Could not get facebook user. Please try to reconnect your Facebook account.');

			switch ($_POST['rsvp_status'])
			{
				case 'attending':
				case 'maybe':
				case 'declined':
					$result = $facebook->api(sprintf('/%d/%s' , $iter->get('facebook_id'), $_POST['rsvp_status']), 'POST');
					break;

				default:
					throw new Exception('Unknown rsvp status');
			}

			return $this->view->redirect('agenda.php?id=' . $iter->get('id'));
		}

		public function run_webcal()
		{
			$cal = new WebCal_Calendar('Cover');
			$cal->description = __('Alle activiteiten van studievereniging Cover');

			$punten = array_filter($this->model->get_agendapunten(), [get_policy($this->model), 'user_can_read']);
			
			$timezone = new DateTimeZone('Europe/Amsterdam');

			foreach ($punten as $punt)
			{
				if (!$this->policy->user_can_read($punt))
					continue;

				$event = new WebCal_Event;
				$event->uid = $punt->get_id() . '@svcover.nl';
				$event->start = new DateTime($punt->get('van'), $timezone);

				if ($punt->get('van') != $punt->get('tot')) {
					$event->end = new DateTime($punt->get('tot'), $timezone);
				}
				else {
					$event->end = new DateTime($punt->get('van'), $timezone);
					$event->end->modify('+ 2 hour');
				}
				
				$event->summary = $punt->get('extern')
					? $punt->get('kop')
					: sprintf('%s: %s', $punt->get('commissie__naam'), $punt->get('kop'));
				$event->description = markup_strip($punt->get('beschrijving'));
				$event->location = $punt->get('locatie');
				$event->url = ROOT_DIR_URI . $this->link_to_read($punt);
				$cal->add_event($event);
			}

			$cal->publish('cover.ics');
			return null;
		}
	}
	
	$controller = new ControllerAgenda();
	$controller->run();

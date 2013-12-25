<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('include/member.php');
	require_once('include/login.php');
	require_once('include/form.php');
	require_once 'include/webcal.php';

	class ControllerAgenda extends Controller {
		function ControllerAgenda() {
			$this->model = get_model('DataModelAgenda');
		}
		
		function get_content($view = 'index', $iter = null, $params = null) {
			$this->run_header(array('title' => __('Agenda')));
			run_view('agenda::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _check_datum($name, $value) {
			/* If this is the tot field and we don't use tot
			 * then set that value to null and return true
			 */
			if ($name == 'tot' && !get_post('use_tot'))
				return null;
			
			$fields = array('maand', 'datum', 'jaar');
			
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
		
		function _check_length($name, $value) {
			$lengths = array('kop' => 100, 'locatie' => 100);

			if (!$value)
				return false;
			
			if (isset($lengths[$name]) && strlen($value) > $lengths[$name])
				return false;
			
			return $value;
		}
		
		function _check_locatie($name, $value) {
			$locatie = get_post('use_locatie');
			check_value_checkbox($name, $locatie);

			if (!$locatie)
				return null;
			
			$locatie = get_post('locatie');

			if (!$locatie)
				return false;

			return $this->_check_length('locatie', $locatie);
		}

		function _action_prepare($iter) {
			/* Only logged in members can attempt to do this */
			if (!logged_in()) {
				$this->get_content('login');
				return false;
			}
			
			/* Allow only the commissie of the agendapunt and bestuur to touch the agendapunt */
			if ($iter != null && !member_in_commissie($iter->get('commissie')) && !member_in_commissie(COMMISSIE_BESTUUR)) {
				$this->get_content('commissie');
				return false;
			}
			
			return true;
		}
		
		function _check_values($iter) {
			/* Check/format all the items */
			$errors = array();
			$data = check_values(
				array(
					array('name' => 'kop', 'function' => array(&$this, '_check_length')),
					'beschrijving',
					array('name' => 'commissie', 'function' => 'check_value_toint'),
					array('name' => 'van', 'function' => array(&$this, '_check_datum')),
					array('name' => 'tot', 'function' => array(&$this, '_check_datum')),
					array('name' => 'locatie', 'function' => array(&$this, '_check_locatie')),
					array('name' => 'private', 'function' => 'check_value_checkbox')),
				$errors);

			if (count($errors) != 0) {
				$this->get_content('edit', $iter, array('errors' => $errors));
				return false;
			}

			if ($data['tot'] === null)
				$data['tot'] = $data['van'];

			return $data;
		}
		
		function _do_process($iter) {
			if (!$this->_action_prepare($iter))
				return;
			
			if (($data = $this->_check_values($iter)) === false)
				return;

			if (!$iter) {
				$iter = new DataIter($this->model, -1, $data);
				$id = $this->model->insert($iter, true);
				$override = 0;
			} else {
				if ($data['commissie'] == COMMISSIE_BESTUUR && member_in_commissie(COMMISSIE_BESTUUR, false)) {
					/* Just change it */
					foreach ($data as $field => $value)
						$iter->set($field, $value);

					$this->model->update($iter);
				} else {
					$mod = new DataIter($this->model, -1, $data);
					$id = $this->model->insert($mod, true);
					$override = $iter->get_id();
				}
			}

			/* Check if the post was made by bestuur, if so they
			 * entry doesn't need moderation
			 */
			if ($data['commissie'] == COMMISSIE_BESTUUR && member_in_commissie(COMMISSIE_BESTUUR, false)) {			
				header('Location: ' . get_request('agenda_add', 'agenda_edit'));
				exit();
			}
			
			$this->model->set_moderate($id, $override, true);
	
			$model = get_model('DataModelCommissie');
			
			/* Variables for email substitution */
			$data['commissie_naam'] = $model->get_naam($data['commissie']);
			$data['member_naam'] = member_full_name();
			$data['id'] = $id;

			if ($override) {
				$_SESSION['alert'] = __('De wijzigingen voor het agendapunt zijn opgestuurd. Zodra het bestuur ernaar gekeken heeft zal het punt op de website gewijzigd worden.');
				$body = parse_email('agenda_mod.txt', $data);
				$subject = 'Gewijzigd agendapunt ' . $data['kop'];
				
				if ($data['kop'] != $iter->get('kop'))
					$subject .= ' (was ' .  $iter->get('kop') . ')';
			} else {
				$_SESSION['alert'] = __('Het nieuwe agendapunt is in de wachtrij geplaatst. Zodra het bestuur ernaar gekeken heeft zal het punt op de website geplaatst worden');
				$body = parse_email('agenda_add.txt', $data);
				$subject = 'Nieuw agendapunt ' . $data['kop'];
			}
			
			mail(get_config_value('email_bestuur'), $subject, $body, "From: webcie@ai.rug.nl\r\n");
			header('Location: ' . get_request('agenda_add', 'agenda_edit'));
		}
		
		function _do_del($iter) {
			if (!$this->_action_prepare($iter))
				return;
			
			$this->model->delete($iter);
			header('Location: ' . get_request('agenda_del', 'agenda_id'));
			exit();	
		}

		function _view_edit($iter) {
			if (!logged_in())
				$this->get_content('login', $iter);
			else
				$this->get_content('edit', $iter);
		}
		
		function _view_moderate($id) {
			if (!member_in_commissie(COMMISSIE_BESTUUR))
			{
				$this->get_content('auth_bestuur');
				return;
			}
			
			$params = array('highlight' => $id);
			
			$iter = $this->model->get_moderates();
			$this->get_content('moderate', $iter, $params);
		}
		
		function _process_moderate() {
			if (!member_in_commissie(COMMISSIE_BESTUUR))
			{
				$this->get_content('auth_bestuur');
				return;
			}
			
			$cancelled = array();

			foreach ($_POST as $field => $value) {
				if (!preg_match('/action__([0-9]+)/i', $field, $matches))
					continue;
				
				$id = $matches[1];
				$iter = $this->model->get_iter($id);
				
				if (!$iter)
					continue;

				if ($value == 'accept') {
					/* Accept agendapunt */
					$this->model->set_moderate($id, 0, false);
					
					/* Remove the agendapunt this one overrides */
					if ($iter->get('overrideid') != 0)
						$this->model->delete($this->model->get_iter($iter->get('overrideid')));
					
					$iter = $this->model->get_iter($id);
					$iter->set('private', get_post('private_' . $id) ? 1 : 0);
					$this->model->update($iter);
				} elseif ($value == 'cancel') {
					/* Remove agendapunt and inform owner of the agendapunt */
					$this->model->delete($iter);
					
					$data = $iter->data;
					$data['member_naam'] = member_full_name();
					$data['reden'] = get_post('comment_' . $id);

					$subject = 'Agendapunt ' . $iter->get('kop') . ' geweigerd';
					$body = parse_email('agenda_cancel.txt', $data);
					
					$commissie_model = get_model('DataModelCommissie');
					$email = $commissie_model->get_email($iter->get('commissie'));

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
			
			header('Location: agenda.php');
			exit();
		}

		function get_webcal()
		{
			$cal = new WebCal_Calendar('Cover');

			$punten = $this->model->get_agendapunten(true);

			foreach ($punten as $punt)
			{
				$event = new WebCal_Event;
				$event->start = new DateTime($punt->get('van'));
				$event->end = new DateTime($punt->get('tot'));
				$event->summary = $punt->get('kop');
				$event->description = $punt->get('beschrijving');
				$event->location = $punt->get('locatie');
				$event->url = sprintf('http://www.svcover.nl/agenda.php?agenda_id=%d', $punt->get_id());
				$cal->add_event($event);
			}

			$cal->publish('cover.ics');
			exit;
		}
		
		function run_impl() {
			$iter = null;

			if (isset($_GET['agenda_id'])) {
				$iter = $this->model->get_iter($_GET['agenda_id'], logged_in());
				
				if (!$iter || ($iter->get('moderate') && !member_in_commissie(COMMISSIE_BESTUUR))) {
					$this->get_content('not_found');
					return;
				}
			}

			if (isset($_POST['submagenda']))
				$this->_do_process($iter);
			elseif (isset($_POST['submagenda_moderate']))
				$this->_process_moderate();
			elseif (isset($_GET['agenda_moderate']))
				$this->_view_moderate($_GET['agenda_moderate']);
			elseif (isset($_GET['agenda_add']))
				$this->_view_edit(null);
			elseif (isset($_GET['agenda_del']))
				$this->_do_del($iter);
			elseif (isset($_GET['agenda_edit']))
				$this->_view_edit($iter);
			elseif ($iter)
				$this->get_content('agendapunt', $iter);
			elseif (isset($_GET['format']) && $_GET['format'] == 'webcal')
				$this->get_webcal();
			else
				$this->get_content('index');
		}
	}
	
	$controller = new ControllerAgenda();
	$controller->run();
?>

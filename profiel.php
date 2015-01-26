<?php
	require_once 'include/init.php';
	require_once 'include/form.php';
	require_once 'include/member.php';
	require_once 'include/login.php';
	require_once 'include/facebook.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerProfiel extends Controller
	{
		var $model = null;

		function ControllerProfiel() {
			$this->model = get_model('DataModelMember');
			$this->sizes = array(
				'voornaam' => 255,
				'tussenvoegsel' => 255,
				'achternaam' => 255,
				'adres' => 255,
				'postcode' => 7,
				'woonplaats' => 255,
				'email' => 255,
				'telefoonnummer' => 20,
				'onderschrift' => 200,
				'avatar' => 100,
				'homepage' => 255,
				'nick' => 50);
		}
		
		function get_content($view, $iter = null, $params = null) {
			$title = $iter
				? member_full_name($iter, false, true)
				: __('Profiel');

			$this->run_header(compact('title'));
			run_view('profiel::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _check_size($name, $value) {
			if ($value === null || !isset($this->sizes[$name]))
				return $value;

			if (strlen(trim($value)) > $this->sizes[$name])
				return false;
			else
				return trim($value);
		}

		function _check_geboortedatum($name, $value) {
			if (!preg_match('/([0-9]+)(-|\/)([0-9]+)(-|\/)([0-9]+)/', $value, $matches))
				return false;
			
			return $matches[5] . '-' . $matches[3] . '-' . $matches[1];
		}

		function _check_type($name, $value) {
			return $value !== null && is_numeric($value) && $value >=1 && $value <= 4;
		}

		function _check_language($name, $value) {
			return in_array($value, array('nl', 'en')) ? $value : false;
		}

		function _process_almanak($iter) {
			if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$check = array(
					array('name' => 'voornaam', 'function' => array(&$this, '_check_size')),
					array('name' => 'tussenvoegsel', 'function' => array(&$this, '_check_size')),
					array('name' => 'achternaam', 'function' => array(&$this, '_check_size')),
					array('name' => 'geboortedatum', 'function' => array(&$this, '_check_geboortedatum')),
					array('name' => 'beginjaar', 'function' => 'check_value_toint'));
			} else {
				$check = array();
			}
			
			$check = array_merge($check, array(
				array('name' => 'postcode', 'function' => array(&$this, '_check_size')),
				array('name' => 'telefoonnummer', 'function' => array(&$this, '_check_size')),
				array('name' => 'adres', 'function' => array(&$this, '_check_size')),
				array('name' => 'email', 'function' => array(&$this, '_check_size')),
				array('name' => 'woonplaats', 'function' => array(&$this, '_check_size')))
			);
			
			$data = check_values($check, $errors);

			if (count($errors) > 0) {
				$error = __('De volgende velden zijn onjuist ingevuld: ') . implode(', ', array_map('field_for_display', $errors));
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error));
				return;
			}
			
			if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$fields = array(
					'voornaam',
					'tussenvoegsel',
					'achternaam',
					'geboortedatum',
					'beginjaar');
			} else {
				$fields = array();
			}

			$fields = array_merge($fields, array(
					'adres', 
					'postcode', 
					'woonplaats',
					'telefoonnummer',
					'email'));
			
			$oud = $iter->data;
			
			foreach ($fields as $field) {
				if (isset($data[$field]) && $data[$field] !== null)
					$iter->set($field, $data[$field]);
				else if (!isset($data[$field]) && get_post($field) !== null)
					$iter->set($field, get_post($field));
			}
			
			$this->model->update($iter);
			
			if ($iter->has_changes()
				&& !member_in_commissie(COMMISSIE_BESTUUR, false)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR, false)) {
				/* Inform bestuur that the members information
				 * has been changed
				 */
				$subject = "Lidgegevens gewijzigd";
				$body = 'De gegevens van ' . member_full_name($iter) . " zijn gewijzigd: \n\n";
				
				$changes = $iter->get_changed_values();
				
				foreach ($changes as $field => $value)
					$body .= $field . ":\t" . ($value ? $value : "<verwijderd>") . " (was: " . $oud[$field] . ")\n";
					
				mail('administratie@svcover.nl', $subject, $body, "From: webcie@ai.rug.nl\r\n");
				mail('secretaris@svcover.nl', $subject, 'De gegevens van ' . member_full_name($iter) . " zijn gewijzigd: \n\n De wijzigingen zijn te vinden op administratie@svcover.nl", "From: webcie@ai.rug.nl\r\n");
			}

			header('Location: profiel.php?lid=' . $iter->get('lidid') . '#almanak');
		}
		
		function _process_webgegevens($iter) {
			$check = array(
				array('name' => 'nick', 'function' => array(&$this, '_check_size')),
				array('name' => 'onderschrift', 'function' => array(&$this, '_check_size')),
				array('name' => 'avatar', 'function' => array(&$this, '_check_size')),
				array('name' => 'homepage', 'function' => array(&$this, '_check_size')),
				array('name' => 'taal', 'function' => array($this, '_check_language'))
			);
			
			$data = check_values($check, $errors);
		
			if (count($errors) > 0) {
				$error = __('De volgende velden zijn te lang: ') . implode(', ', array_map('field_for_display', $errors));
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error));
				return;
			}

			$fields = array(
					'nick', 
					'onderschrift', 
					'avatar',
					'homepage', 
					'taal');

			foreach ($fields as $field)
				if (isset($data[$field]))
					$iter->set($field, $data[$field]);
				else
					$iter->set($field, get_post($field));
			
			$this->model->update_profiel($iter);
			header('Location: profiel.php?lid=' . $iter->get('lidid') . '#webgegevens');	
		}
		
		function _process_wachtwoord($iter) {
			$errors = array();
			$message = array();

			// Only test the old password if we are not a member of the board
			if (!member_in_commissie(COMMISSIE_BESTUUR)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				if (!get_post('wachtwoord_oud') || !$this->model->login($iter->get('email'), get_post('wachtwoord_oud'))) {
					$errors[] = 'wachtwoord_oud';
					$message[] = __('Het huidige wachtwoord is onjuist.');
				}
			}
			
			if (!get_post('wachtwoord_nieuw')) {
				$errors[] = 'wachtwoord_nieuw';
				$message[] = __('Het nieuwe wachtwoord is niet ingevuld.');
			} elseif (!get_post('wachtwoord_opnieuw') || get_post('wachtwoord_nieuw') != get_post('wachtwoord_opnieuw')) {
				$errors[] = 'wachtwoord_opnieuw';
				$message[] = __('Het nieuwe wachtwoord is niet twee keer hetzelfde ingevuld.');
			}
			
			if (count($errors) > 0) {
				$error = implode("\n", $message);
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error));
				return;
			}
			
			$this->model->set_password($iter, get_post('wachtwoord_nieuw'));

			header('Location: profiel.php?lid=' . $iter->get('lidid') . '#wachtwoord');
		}
		
		function _process_privacy($iter) {
			/* Built privacy mask */
			$fields = $this->model->get_privacy();
			$mask = 0;
			
			foreach ($fields as $field => $nr) {
				$value = intval(get_post('privacy_' . $nr));
				
				$mask = $mask + ($value << ($nr * 3));
			}
			
			$iter->set('privacy', $mask);
			$this->model->update($iter);
			
			header('Location: profiel.php?lid=' . $iter->get('lidid') . '#privacy');
		}

		function _process_zichtbaarheid($iter) {
			$errors = array();
			$message = array();

			if (!member_in_commissie(COMMISSIE_BESTUUR)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$errors[] = 'type';
				$message[] = __('Jij mag deze gegevens niet aanpassen.');
			} elseif (!get_post('type')) {
				$errors[] = 'type';
				$message[] = __('De zichtbaarheid van het profiel is niet ingevuld.');
			} elseif (get_post('type') < MEMBER_STATUS_LID || get_post('type') > MEMBER_STATUS_DONATEUR) {
				$errors[] = 'type';
				$message[] = __('Er is een ongeldige waarde voor zichtbaarheid ingevuld.');
			}

			if (count($errors) > 0) {
				$error = implode("\n", $message);
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error));
				return;
			}

			$iter->set('type', intval(get_post('type')));
			$this->model->update($iter);

			header('Location: profiel.php?lid=' . $iter->get('lidid') . '#zichtbaarheid');
		}

		protected function _process_photo($iter)
		{
			$error = null;

			if (!member_in_commissie(COMMISSIE_BESTUUR) && !member_in_commissie(COMMISSIE_KANDIBESTUUR))
				return $this->get_content('common::auth');

			else if ($_FILES['photo']['errpr'] == UPLOAD_ERR_INI_SIZE)
				$error = sprintf(__('Het fotobestand is te groot. Het maximum is %s.'),
					ini_get('upload_max_filesize') . ' bytes');

			elseif ($_FILES['photo']['error'] != UPLOAD_ERR_OK)
				$error = sprintf(__('Het bestand is niet geupload. Foutcode %d.'), $_FILES['photo']['error']);

			elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
				$error = __('Bestand is niet een door PHP geupload bestand.');
			
			elseif (!($fh = fopen($_FILES['photo']['tmp_name'], 'rb')))
				$error = __('Het geuploadde bestand kon niet worden gelezen.');

			if ($error)
				return $this->get_content('profiel', $iter, array('errors' => array('photo'), 'error_message' => $error));

			$this->model->set_photo($iter, $fh);

			fclose($fh);

			header('Location: profiel.php?lid=' . $iter->get('lidid') . '&force_reload_photo=true#almanak');
		}

		protected function _process_facebook_link($iter, $action)
		{
			if ($action == 'unlink')
				get_facebook()->destroySession();

			header('Location: profiel.php?lid=' . $iter->get('lidid') . '#fadebook');
		}
		
		function run_impl() {
			$lid = null;

			if (!isset($_GET['lid'])) {
				if (($member_data = logged_in()))
					$lid = $member_data['id'];
			} else
				$lid = $_GET['lid'];
			
			// If the member was not found, return 404
			if ($lid == null || !($iter = $this->model->get_iter($lid)))
				return $this->get_content('not_found');
			
			// If the member was found, but is officially 'deleted', also return a 404
			if ($iter->get('type') == MEMBER_STATUS_LID_AF
				&& !member_in_commissie(COMMISSIE_BESTUUR)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR))
				return $this->get_content('not_found');

			if (isset($_POST['submprofiel_almanak']))
				$this->_process_almanak($iter);
			elseif (isset($_POST['facebook_action']))
				$this->_process_facebook_link($iter, $_POST['facebook_action']);
			elseif (isset($_FILES['photo']))
				$this->_process_photo($iter);
			elseif (isset($_POST['submprofiel_webgegevens']))
				$this->_process_webgegevens($iter);
			elseif (isset($_POST['submprofiel_wachtwoord']))
				$this->_process_wachtwoord($iter);
			elseif (isset($_POST['submprofiel_privacy']))
				$this->_process_privacy($iter);
			elseif (isset($_POST['submprofiel_zichtbaarheid']))
				$this->_process_zichtbaarheid($iter);
			else
				$this->get_content('profiel', $iter);
		}
	}
	
	$controller = new ControllerProfiel();
	$controller->run();

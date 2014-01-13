<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('include/form.php');
	require_once('include/member.php');
	require_once('include/login.php');

	class ControllerProfiel extends Controller {
		var $model = null;

		function ControllerProfiel() {
			$this->model = get_model('DataModelMember');
			$this->sizes = array(
				'voornaam' => 25,
				'tussenvoegsel' => 10,
				'achternaam' => 25,
				'adres' => 50,
				'postcode' => 7,
				'woonplaats' => 25,
				'email' => 50,
				'telefoonnummer_vast' => 11,
				'telefoonnummer_mobiel' => 11,
				'onderschrift' => 200,
				'avatar' => 100,
				'homepage' => 255,
				'msn' => 100,
				'icq' => 15,
				'nick' => 50);
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Profiel')));
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
			if (member_in_commissie(COMMISSIE_BESTUUR)) {
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
				array('name' => 'telefoonnummer_vast', 'function' => array(&$this, '_check_size')),
				array('name' => 'telefoonnummer_mobiel', 'function' => array(&$this, '_check_size')),
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
			
			if (member_in_commissie(COMMISSIE_BESTUUR)) {
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
					'telefoonnummer_vast', 
					'telefoonnummer_mobiel',
					'email'));
			
			$oud = $iter->data;
			
			foreach ($fields as $field) {
				if (isset($data[$field]) && $data[$field] !== null)
					$iter->set($field, $data[$field]);
				else if (!isset($data[$field]) && get_post($field) !== null)
					$iter->set($field, get_post($field));
			}
			
			$this->model->update($iter);
			
			if ($iter->has_changes() && !member_in_commissie(COMMISSIE_BESTUUR, false)) {
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
				array('name' => 'msn', 'function' => array(&$this, '_check_size')),
				array('name' => 'icq', 'function' => array(&$this, '_check_size')),
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
					'msn',
					'icq',
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

			if (!get_post('wachtwoord_oud') || md5(get_post('wachtwoord_oud')) != $iter->get('wachtwoord')) {
				$errors[] = 'wachtwoord_oud';
				$message[] = __('Het huidige wachtwoord is onjuist.');
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
			
			$iter->set('wachtwoord', md5(get_post('wachtwoord_nieuw')));
			$this->model->update_profiel($iter);

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

			if (!member_in_commissie(COMMISSIE_BESTUUR)) {
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
		
		function run_impl() {
			$lid = null;

			if (!isset($_GET['lid'])) {
				if (($member_data = logged_in()))
					$lid = $member_data['id'];
			} else
				$lid = $_GET['lid'];
			
			if ($lid == null || !($iter = $this->model->get_iter($lid)))
				return $this->get_content('not_found');
			
			if (isset($_POST['submprofiel_almanak']))
				$this->_process_almanak($iter);
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
?>

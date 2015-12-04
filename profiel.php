<?php
	require_once 'include/init.php';
	require_once 'include/form.php';
	require_once 'include/member.php';
	require_once 'include/login.php';
	require_once 'include/facebook.php';
	require_once 'include/secretary.php';
	require_once 'include/controllers/Controller.php';
	require_once 'include/email.php';

	use JeroenDesloovere\VCard\VCard;
	
	class ControllerProfiel extends Controller
	{
		public function __construct()
		{
			$this->model = get_model('DataModelMember');

			$this->sizes = array(
				'adres' => 255,
				'postcode' => 7,
				'woonplaats' => 255,
				'email' => 255,
				'telefoonnummer' => 20,
				'avatar' => 100,
				'homepage' => 255,
				'nick' => 50);

			$this->required = array(
				'adres',
				'postcode',
				'woonplaats',
				'email'
			);
		}
		
		protected function get_content($view, $iter = null, $params = null)
		{
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

			if (strlen(trim($value)) === 0 && in_array($name, $this->required))
				return false;

			if (strlen(trim($value)) > $this->sizes[$name])
				return false;
			
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

		protected function _process_almanak(DataIterMember $iter)
		{
			$check = array(
				array('name' => 'postcode', 'function' => array($this, '_check_size')),
				array('name' => 'telefoonnummer', 'function' => array($this, '_check_size')),
				array('name' => 'adres', 'function' => array($this, '_check_size')),
				array('name' => 'email', 'function' => array($this, '_check_size')),
				array('name' => 'woonplaats', 'function' => array($this, '_check_size'))
			);
			
			$data = check_values($check, $errors);

			if (count($errors) > 0) {
				$error = __('De volgende velden zijn onjuist ingevuld: ') . implode(', ', array_map('field_for_display', $errors));
				return $this->get_content('profiel', $iter, [
					'tab' => 'personal',
					'errors' => $errors,
					'error_message' => $error
				]);
			}
			
			// Get all field names that need to be set on the iterator
			$fields = array_map(function($check) { return $check['name']; }, $check);
			
			// Remove the e-mail field, that is a special case
			$fields = array_filter($fields, function($field) { return $field != 'email'; });

			$oud = $iter->data;
			
			foreach ($fields as $field) {
				if (isset($data[$field]) && $data[$field] !== null)
					$iter->set($field, $data[$field]);
				else if (!isset($data[$field]) && get_post($field) !== null)
					$iter->set($field, get_post($field));
			}

			if ($iter->has_changes())
			{
				$this->model->update($iter);
			
				// Inform the board that member info has been changed.
				$subject = "Lidgegevens gewijzigd";
				$body = sprintf("De gegevens van %s zijn gewijzigd:", member_full_name($iter)) . "\n\n";
				
				$changes = $iter->get_changed_values();
				
				foreach ($changes as $field => $value)
					$body .= sprintf("%s:\t%s (was: %s)\n", $field, $value ? $value : "<verwijderd>", $oud[$field]);
					
				mail('administratie@svcover.nl', $subject, $body, "From: webcie@ai.rug.nl\r\nContent-Type: text/plain; charset=UTF-8");
				mail('secretaris@svcover.nl', $subject, sprintf("De gegevens van %s zijn gewijzigd:\n\nDe wijzigingen zijn te vinden op administratie@svcover.nl", member_full_name($iter)), "From: webcie@ai.rug.nl\r\nContent-Type: text/plain; charset=UTF-8");

				try {
					get_secretary()->updatePersonFromIterChanges($iter);
				} catch (RuntimeException $e) {
					error_log($e);
				}
			}

			// If the email address has changed, add a confirmation.
			if (isset($data['email']) && $data['email'] !== null
				&& $data['email'] != $iter['email'])
			{
				$model = new DataModel(get_db(), 'confirm', 'key');
				$key = randstr(32);
				$payload = ['lidid' => $iter->get_id(), 'email' => $data['email']];
				$confirm_iter = new DataIter($model, null, ['key' => $key, 'type' => 'email', 'value' => json_encode($payload)]);
				$model->insert($confirm_iter);

				$language_code = strtolower(i18n_get_language());

				$variables = [
					'naam' => member_full_name($iter),
					'email' => $data['email'],
					'link' => 'https://www.svcover.nl/confirm.php?key=' . urlencode($key)
				];

				// Send the confirmation to the new email address
				parse_email_object("email_confirmation_{$language_code}.txt", $variables)->send($data['email']);
				$_SESSION['alert'] = __('Er is een bevestigingsmailtje naar je nieuwe e-mailadres gestuurd.');
			}

			return $this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=personal');
		}
		
		protected function _process_webgegevens(DataIterMember $iter)
		{
			$check = array(
				array('name' => 'nick', 'function' => array(&$this, '_check_size')),
				array('name' => 'avatar', 'function' => array(&$this, '_check_size')),
				array('name' => 'homepage', 'function' => array(&$this, '_check_size')),
				array('name' => 'taal', 'function' => array($this, '_check_language'))
			);
			
			$data = check_values($check, $errors);
		
			if (count($errors) > 0) {
				$error = __('De volgende velden zijn te lang: ') . implode(', ', array_map('field_for_display', $errors));
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error, 'tab' => 'profile'));
				return;
			}

			$fields = array_map(function($check) { return $check['name']; }, $check);

			foreach ($fields as $field)
				if (isset($data[$field]))
					$iter->set($field, $data[$field]);
				else
					$iter->set($field, get_post($field));
			
			$this->model->update_profiel($iter);
			
			$this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=profile');	
		}
		
		protected function _process_wachtwoord(DataIterMember $iter)
		{
			$errors = array();
			$message = array();

			// Only test the old password if we are not a member of the board
			if (!member_in_commissie(COMMISSIE_BESTUUR) && !member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
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
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error, 'tab' => 'profile'));
				return;
			}
			
			$this->model->set_password($iter, get_post('wachtwoord_nieuw'));

			$this->redirect('Location: profiel.php?lid=' . $iter->get_id() . '&tab=profile');
		}
		
		protected function _process_privacy(DataIterMember $iter)
		{
			/* Built privacy mask */
			$fields = $this->model->get_privacy();
			$mask = 0;
			
			foreach ($fields as $field => $nr) {
				$value = intval(get_post('privacy_' . $nr));
				
				$mask = $mask + ($value << ($nr * 3));
			}
			
			$iter->set('privacy', $mask);
			$this->model->update($iter);
			
			$this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=privacy');
		}

		protected function _process_zichtbaarheid(DataIterMember $iter)
		{
			$errors = array();
			$message = array();

			if (!get_identity()->member_in_committee(COMMISSIE_EASY)) {
				$errors[] = 'type';
				$message[] = __('Jij mag deze gegevens niet aanpassen.');
			} elseif (!get_post('type')) {
				$errors[] = 'type';
				$message[] = __('De zichtbaarheid van het profiel is niet ingevuld.');
			} elseif (get_post('type') < MEMBER_STATUS_MIN || get_post('type') > MEMBER_STATUS_MAX) {
				$errors[] = 'type';
				$message[] = __('Er is een ongeldige waarde voor zichtbaarheid ingevuld.');
			}

			if (count($errors) > 0) {
				$error = implode("\n", $message);
				$this->get_content('profiel', $iter, array('errors' => $errors, 'error_message' => $error, 'tab' => 'system'));
				return;
			}

			$iter->set('type', intval(get_post('type')));
			$this->model->update($iter);

			$this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=system');
		}

		protected function _process_photo(DataIterMember $iter)
		{
			$error = null;

			if (!get_identity()->member_in_committee(COMMISSIE_EASY))
				return $this->get_content('common::auth');

			else if ($_FILES['photo']['error'] == UPLOAD_ERR_INI_SIZE)
				$error = sprintf(__('Het fotobestand is te groot. Het maximum is %s.'),
					ini_get('upload_max_filesize') . ' bytes');

			elseif ($_FILES['photo']['error'] != UPLOAD_ERR_OK)
				$error = sprintf(__('Het bestand is niet geupload. Foutcode %d.'), $_FILES['photo']['error']);

			elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
				$error = __('Bestand is niet een door PHP geupload bestand.');
			
			elseif (!($fh = fopen($_FILES['photo']['tmp_name'], 'rb')))
				$error = __('Het geuploadde bestand kon niet worden gelezen.');

			if ($error)
				return $this->get_content('profiel', $iter, array('errors' => array('photo'), 'error_message' => $error, 'tab' => 'profile'));

			$this->model->set_photo($iter, $fh);

			fclose($fh);

			$this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=profile&force_reload_photo=true');
		}

		protected function _process_photo_suggestion(DataIterMember $iter)
		{
			$error = null;

			if ($iter->get_id() != get_identity()->get('id'))
				return $this->get_content('common::auth');

			else if ($_FILES['photo']['error'] == UPLOAD_ERR_INI_SIZE)
				$error = sprintf(__('Het fotobestand is te groot. Het maximum is %s.'),
					ini_get('upload_max_filesize') . ' bytes');

			elseif ($_FILES['photo']['error'] != UPLOAD_ERR_OK)
				$error = sprintf(__('Het bestand is niet geupload. Foutcode %d.'), $_FILES['photo']['error']);

			elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
				$error = __('Bestand is niet een door PHP geupload bestand.');
			
			elseif (!($image_meta = getimagesize($_FILES['photo']['tmp_name'])))
				$error = __('Het geuploadde bestand kon niet worden gelezen.');

			if ($error)
				$this->get_content('profiel', $iter, ['errors' => array('photo'), 'error_message' => $error, 'tab' => 'profile']);

			$mime = image_type_to_mime_type($image_meta[2]);

			$mail = new \cover\email\MessagePart();
			$mail->addHeader('To', 'acdcee@svcover.nl');
			$mail->addHeader('Subject', 'New yearbook photo for ' . $iter['naam']);
			$mail->addHeader('Reply-To', sprintf('%s <%s>', $iter['naam'], $iter['email']));
			$mail->addBody(
				'text/plain; charset=UTF-8',
				"{$iter['naam']} would like to use the attached photo as their new profile picture.",
				\cover\email\MessagePart::TRANSFER_ENCODING_QUOTED_PRINTABLE);
			$mail->addBody(
				$mime,
				file_get_contents($_FILES['photo']['tmp_name']),
				\cover\email\MessagePart::TRANSFER_ENCODING_BASE64);
			\cover\email\send($mail);

			$_SESSION['alert'] = __('Je foto is ingestuurd. Het kan even duren voordat hij is aangepast.');

			$this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=profile');
		}

		protected function _process_facebook_link(DataIterMember $iter, $action)
		{
			if ($action == 'unlink')
				get_facebook()->destroySession();

			$this->redirect('profiel.php?lid=' . $iter->get_id() . '&tab=facebook');
		}

		public function run_export_vcard(DataIterMember $member)
		{
			$card = new VCard();

			$is_visible = function($field) use ($member) {
				return in_array($this->model->get_privacy_for_field($member, $field),
					[DataModelMember::VISIBLE_TO_EVERYONE, DataModelMember::VISIBLE_TO_MEMBERS]);
			};
			
			if ($is_visible('naam'))
				$card->addName($member['achternaam'], $member['voornaam'], $member['tussenvoegsel']);

			if ($is_visible('email'))
				$card->addEmail($member['email']);

			if ($is_visible('telefoonnummer'))
				$card->addPhoneNumber($member['telefoonnummer'], 'PREF;HOME');
			
			$card->addAddress(null, null,
				$is_visible('adres') ? $member['adres'] : null,
				$is_visible('woonplaats') ? $member['woonplaats'] : null,
				null,
				$is_visible('postcode') ? $member['postcode'] : null,
				null);

			if ($is_visible('geboortedatum'))
				$card->addBirthday($member['geboortedatum']);

			if (!empty($member['homepage']))
				$card->addURL($member['homepage']);

			if ($is_visible('foto') && $this->model->has_picture($member)) {
				$fout = null;

				$imagick = new Imagick();
				$imagick->readImageBlob($this->model->get_photo($member));
				
				$y = 0.05 * $imagick->getImageHeight();
				$size = min($imagick->getImageWidth(), $imagick->getImageHeight());
				
				if ($y + $size > $imagick->getImageHeight())
					$y = 0;

				$imagick->cropImage($size, $size, 0, $y);
				$imagick->scaleImage(128, 0);

				$imagick->setImageFormat('jpeg');

				$tmp_file = tempnam(sys_get_temp_dir(), sprintf('photo%d', $member['id']));
				$fout = fopen($tmp_file, 'wb+');
				$imagick->writeImageFile($fout);
				fclose($fout);

				$imagick->destroy();

				$card->addPhoto($tmp_file);
				// $card->addMedia('PHOTO;ENCODING=b;TYPE=JPEG', stream_get_contents($fout), 'photo');
			}
			
			$card->download();
		}
		
		protected function run_impl()
		{
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

			$tab = isset($_GET['tab']) ? $_GET['tab'] : 'public';

			if (isset($_POST['submprofiel_almanak']))
				$this->_process_almanak($iter);
			elseif (isset($_POST['facebook_action']))
				$this->_process_facebook_link($iter, $_POST['facebook_action']);
			elseif (isset($_POST['submprofiel_foto']))
				$this->_process_photo_suggestion($iter);
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
			elseif (isset($_GET['export']) && $_GET['export'] == 'vcard')
				$this->run_export_vcard($iter);
			else
				$this->get_content('profiel', $iter, ['errors' => [], 'tab' => $tab]);
		}
	}
	
	$controller = new ControllerProfiel();
	$controller->run();

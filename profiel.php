<?php
require_once 'include/init.php';
require_once 'include/form.php';
require_once 'include/member.php';
require_once 'include/login.php';
require_once 'include/facebook.php';
require_once 'include/secretary.php';
require_once 'include/controllers/Controller.php';
require_once 'include/email.php';

class ControllerProfiel extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelMember');

		$this->policy = get_policy($this->model);

		$this->view = View::byName('profiel', $this);

		$this->sizes = [
			'adres' => 255,
			'postcode' => 7,
			'woonplaats' => 255,
			'email' => 255,
			'telefoonnummer' => 20,
			'avatar' => 100,
			'homepage' => 255,
			'nick' => 50,
			'onderschrift' => 200
		];

		$this->required = [
			'adres',
			'postcode',
			'woonplaats',
			'email'
		];
	}
	
	public function _check_size($name, $value)
	{
		if ($value === null || !isset($this->sizes[$name]))
			return $value;

		if (strlen(trim($value)) === 0 && in_array($name, $this->required))
			return false;

		if (strlen(trim($value)) > $this->sizes[$name])
			return false;
		
		return trim($value);
	}

	public function _check_geboortedatum($name, $value)
	{
		if (!preg_match('/([0-9]+)(-|\/)([0-9]+)(-|\/)([0-9]+)/', $value, $matches))
			return false;
		
		return $matches[5] . '-' . $matches[3] . '-' . $matches[1];
	}

	public function _check_type($name, $value)
	{
		return $value !== null && is_numeric($value) && $value >=1 && $value <= 4;
	}

	public function _check_language($name, $value)
	{
		return in_array($value, array('nl', 'en')) ? $value : false;
	}

	public function _check_phone($name, $value) {
		try {
			$phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number = $phone_util->parse($value, 'NL');
			return $phone_util->isValidNumber($phone_number)
				? $phone_util->format($phone_number, \libphonenumber\PhoneNumberFormat::E164)
				: false;
		} catch (\libphonenumber\NumberParseException $e) {
			return false;
		}
	}

	protected function _update_personal(DataIterMember $iter)
	{
		$check = array(
			array('name' => 'postcode', 'function' => array($this, '_check_size')),
			array('name' => 'telefoonnummer', 'function' => array($this, '_check_phone')),
			array('name' => 'adres', 'function' => array($this, '_check_size')),
			array('name' => 'email', 'function' => array($this, '_check_size')),
			array('name' => 'woonplaats', 'function' => array($this, '_check_size'))
		);
		
		$data = check_values($check, $errors);

		if (count($errors) > 0) {
			$error = __('De volgende velden zijn onjuist ingevuld: ') . implode(', ', array_map('field_for_display', $errors));
			return $this->view->render_personal_tab($iter, $error, $errors);
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
			$body = sprintf("De gegevens van %s zijn gewijzigd:", member_full_name($iter, IGNORE_PRIVACY)) . "\n\n";
			
			// Get all/only changed values (but only the actual fields, not the computed cruft)
			$changes = array_filter($iter->get_changed_values(), function($field) { return in_array($field, DataIterMember::fields()); });
			
			foreach ($changes as $field => $value)
				$body .= sprintf("%s:\t%s (was: %s)\n", $field, $value ? $value : "<verwijderd>", $oud[$field]);
				
			mail('administratie@svcover.nl', $subject, $body, "From: webcie@ai.rug.nl\r\nContent-Type: text/plain; charset=UTF-8");
			mail('secretaris@svcover.nl', $subject, sprintf("De gegevens van %s zijn gewijzigd:\n\nDe wijzigingen zijn te vinden op administratie@svcover.nl", member_full_name($iter, IGNORE_PRIVACY)), "From: webcie@ai.rug.nl\r\nContent-Type: text/plain; charset=UTF-8");

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
			$payload = ['lidid' => $iter['id'], 'email' => $data['email']];
			$confirm_iter = new DataIter($model, null, ['key' => $key, 'type' => 'email', 'value' => json_encode($payload)]);
			$model->insert($confirm_iter);

			$language_code = strtolower(i18n_get_language());

			$variables = [
				'naam' => member_first_name($iter, IGNORE_PRIVACY),
				'email' => $data['email'],
				'link' => 'https://www.svcover.nl/confirm.php?key=' . urlencode($key)
			];

			// Send the confirmation to the new email address
			parse_email_object("email_confirmation_{$language_code}.txt", $variables)->send($data['email']);
			$_SESSION['alert'] = __('Er is een bevestigingsmailtje naar je nieuwe e-mailadres gestuurd.');
		}

		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=personal');
	}

	protected function _update_profile(DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter))
			throw new UnauthorizedException();

		$check = array(
			array('name' => 'nick', 'function' => array(&$this, '_check_size')),
			array('name' => 'avatar', 'function' => array(&$this, '_check_size')),
			array('name' => 'onderschrift', 'function' => array(&$this, '_check_size')),
			array('name' => 'homepage', 'function' => array(&$this, '_check_size')),
			array('name' => 'taal', 'function' => array($this, '_check_language'))
		);
		
		$data = check_values($check, $errors);
	
		if (count($errors) > 0) {
			$error = __('De volgende velden zijn te lang: ') . implode(', ', array_map('field_for_display', $errors));
			return $this->view->render_profile_tab($iter, $error, $errors);
		}

		$iter->set_all($data);
		
		$this->model->update($iter);
		
		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=profile');
	}

	protected function _update_password(DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter))
			throw new UnauthorizedException();

		$errors = array();
		$message = array();

		// Only test the old password if we are not a member of the board
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			&& !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)) {
			if (!isset($_POST['wachtwoord_oud']) || !$this->model->test_password($iter, $_POST['wachtwoord_oud'])) {
				$errors[] = 'wachtwoord_oud';
				$message[] = __('Het huidige wachtwoord is onjuist.');
			}
		}

		if (!isset($_POST['wachtwoord_nieuw'])) {
			$errors[] = 'wachtwoord_nieuw';
			$message[] = __('Het nieuwe wachtwoord is niet ingevuld.');
		} elseif (!isset($_POST['wachtwoord_opnieuw']) || $_POST['wachtwoord_nieuw'] !== $_POST['wachtwoord_opnieuw']) {
			$errors[] = 'wachtwoord_opnieuw';
			$message[] = __('Het nieuwe wachtwoord is niet twee keer hetzelfde ingevuld.');
		} elseif (strlen($_POST['wachtwoord_nieuw']) < 4) {
			$errors[] = 'wachtwoord_nieuw';
			$message[] = __('Het nieuwe wachtwoord is te kort.');
		}
		
		if (count($errors) > 0) {
			$error = implode("\n", $message);
			return $this->view->render_profile_tab($iter, $error, $errors);
		}
		
		$this->model->set_password($iter, $_POST['wachtwoord_nieuw']);

		$_SESSION['alert'] = __('Je wachtwoord is gewijzigd.');

		return $this->view->redirect('Location: profiel.php?lid=' . $iter['id'] . '&view=profile');
	}

	protected function _update_privacy(DataIterMember $iter)
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
		
		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=privacy');
	}

	protected function _update_system(DataIterMember $iter)
	{
		$errors = array();
		$message = array();

		if (!get_post('type')) {
			$errors[] = 'type';
			$message[] = __('De zichtbaarheid van het profiel is niet ingevuld.');
		} elseif (get_post('type') < MEMBER_STATUS_MIN || get_post('type') > MEMBER_STATUS_MAX) {
			$errors[] = 'type';
			$message[] = __('Er is een ongeldige waarde voor zichtbaarheid ingevuld.');
		}

		if (count($errors) > 0) {
			$error = implode("\n", $message);
			return $this->view->render_system_tab($iter, $error, $errors);
		}

		$iter->set('type', intval(get_post('type')));
		$this->model->update($iter);

		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=system');
	}

	protected function _update_photo(DataIterMember $iter)
	{
		$error = null;

		if ($_FILES['photo']['error'] == UPLOAD_ERR_INI_SIZE)
			$error = sprintf(__('Het fotobestand is te groot. Het maximum is %s.'),
				ini_get('upload_max_filesize') . ' bytes');

		elseif ($_FILES['photo']['error'] != UPLOAD_ERR_OK)
			$error = sprintf(__('Het bestand is niet geupload. Foutcode %d.'), $_FILES['photo']['error']);

		elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
			$error = __('Bestand is niet een door PHP geupload bestand.');
		
		elseif (!($image_meta = getimagesize($_FILES['photo']['tmp_name'])))
			$error = __('Het geuploadde bestand kon niet worden gelezen.');

		if ($error)
			return $this->view->render_profile_tab($iter, $error, array('photo'));

		if (get_identity()->member_in_committee(COMMISSIE_EASY))
		{
			if (!($fh = fopen($_FILES['photo']['tmp_name'], 'rb')))
				throw new RuntimeException(__('Het geuploadde bestand kon niet worden geopend.'));

			$this->model->set_photo($iter, $fh);

			fclose($fh);
		}
		else
		{
			send_mail_with_attachment(
				'acdcee@svcover.nl',
				'New yearbook photo for ' . $iter['naam'],
				"{$iter['naam']} would like to use the attached photo as their new profile picture.",
				sprintf('Reply-to: %s <%s>', $iter['naam'], $iter['email']),
				[$_FILES['photo']['name'] => $_FILES['photo']['tmp_name']]);

			$_SESSION['alert'] = __('Je foto is ingestuurd. Het kan even duren voordat hij is aangepast.');
		}

		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=profile');
	}

	protected function _update_mailing_lists(DataIterMember $iter)
	{
		$model = get_model('DataModelMailinglist');

		$subscription_model = get_model('DataModelMailinglistSubscription');

		$mailing_list = $model->get_iter($_POST['mailing_list_id']);

		switch ($_POST['action'])
		{
			case 'subscribe':
				if (get_policy($model)->user_can_subscribe($mailing_list))
					$subscription_model->subscribe_member($mailing_list, $iter);
				break;

			case 'unsubscribe':
				if (get_policy($model)->user_can_unsubscribe($mailing_list))
					$subscription_model->unsubscribe_member($mailing_list, $iter);
				break;
		}

		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=mailing_lists');
	}
	
	public function run_personal(DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('personal', $iter))
			return $this->_update_personal($iter);

		return $this->view->render_personal_tab($iter);
	}

	public function run_profile(DataIterMember $iter)
	{
		if (!$this->policy->user_can_read($iter))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('profile', $iter))
			return $this->_update_profile($iter);

		elseif ($this->_form_is_submitted('password', $iter))
			return $this->_update_password($iter);

		return $this->view->render_profile_tab($iter);
	}
	
	public function run_privacy(DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter)
			&& !get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('privacy', $iter))
			return $this->_update_privacy($iter);

		return $this->view->render_privacy_tab($iter);
	}

	public function run_system(DataIterMember $iter)
	{
		if (!get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('system', $iter))
			return $this->_update_system($iter);

		return $this->view->render_system_tab($iter);
	}

	protected function run_photo(DataIterMember $iter)
	{
		// Only members themselves and the AC/DCee can change photos
		if ($iter['id'] != get_identity()->get('id')
			&& !get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('photo', $iter))
			return $this->_update_photo($iter);

		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=profile');
	}

	protected function run_facebook(DataIterMember $iter)
	{
		if ($iter->get('id') != get_identity()->get('id'))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('facebook', $iter))
		{
			if (get_post('facebook_action') == 'unlink')
				get_facebook()->destroySession();

			return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=facebook');
		}
		
		return $this->view->render_facebook_tab($iter);
	}

	public function run_export_vcard(DataIterMember $member)
	{
		if (get_identity()->get('id') != $member['id'])
			throw new UnauthorizedException();

		if (!get_identity()->member_is_active())
			throw new UnauthorizedException();

		return $this->view->render_vcard($member);
	}

	public function run_export_incassocontract(DataIterMember $member)
	{
		if (get_identity()->get('id') != $member['id'])
			throw new UnauthorizedException();

		require_once 'include/incassomatic.php';

		$incasso_api = \incassomatic\shared_instance();

		$fh = $incasso_api->getContractTemplatePDF($member);

		header('Content-Type: application/pdf');
		fpassthru($fh);
		fclose($fh);
	}

	public function run_public(DataIterMember $member)
	{
		if (!$this->policy->user_can_read($member))
			throw new UnauthorizedException('You are not allowed to access this member');

		return $this->view->render_public_tab($member);
	}

	public function run_mailing_lists(DataIterMember $member)
	{
		if ($this->_form_is_submitted('mailing_list', $member))
			return $this->_update_mailing_lists($member);

		return $this->view->render_mailing_lists_tab($member);
	}

	public function run_sessions(DataIterMember $member)
	{
		if ($member['id'] != get_identity()->get('id'))
			throw new UnauthorizedException();

		return $this->view->render_sessions_tab($member);
	}

	public function run_kast(DataIterMember $member)
	{
		if ($member['id'] != get_identity()->get('id'))
			throw new UnauthorizedException();

		return $this->view->render_kast_tab($member);
	}

	public function run_incassomatic(DataIterMember $member)
	{
		if ($member['id'] != get_identity()->get('id'))
			throw new UnauthorizedException();

		return $this->view->render_incassomatic_tab($member);
	}

	public function run_index()
	{
		return $this->view->redirect('almanak.php');
	}

	protected function run_impl()
	{
		if (!isset($_GET['lid']))
			return $this->run_index();
		
		$iter = $this->model->get_iter($_GET['lid']);
		
		$view = isset($_GET['view']) ? $_GET['view'] : 'public';
		
		if (!method_exists($this, 'run_' . $view))
			throw new NotFoundException("View '$view' not implemented by " . get_class($this));

		return call_user_func_array([$this, 'run_' . $view], [$iter]);
	}
}

$controller = new ControllerProfiel();
$controller->run();

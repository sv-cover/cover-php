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

	public function _check_phone($name, $value)
	{
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

	public function _check_email($name, $value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	protected function _update_personal(DataIterMember $iter)
	{
		$check = array(
			array('name' => 'postcode', 'function' => array($this, '_check_size')),
			array('name' => 'telefoonnummer', 'function' => array($this, '_check_phone')),
			array('name' => 'adres', 'function' => array($this, '_check_size')),
			array('name' => 'email', 'function' => array($this, '_check_email')),
			array('name' => 'woonplaats', 'function' => array($this, '_check_size'))
		);
		
		$data = check_values($check, $errors);

		if (count($errors) > 0) {
			$error = __('The following fields are not correctly filled in: ') . implode(', ', array_map('field_for_display', $errors));
			return $this->view->render_personal_tab($iter, $error, $errors);
		}
		
		// Get all field names that need to be set on the iterator
		$fields = array_map(function($check) { return $check['name']; }, $check);
		
		// Remove the e-mail field, that is a special case
		$fields = array_filter($fields, function($field) { return $field != 'email'; });

		$old_values = $iter->data;
		
		foreach ($fields as $field) {
			if (isset($data[$field]) && $data[$field] !== null)
				$iter->set($field, $data[$field]);
			else if (!isset($data[$field]) && get_post($field) !== null)
				$iter->set($field, get_post($field));
		}

		if ($iter->has_changes())
		{
			$changed_fields = $iter->changed_fields();

			$this->model->update($iter);
		
			$this->_report_changes_upstream($iter, $changed_fields, $old_values);
		}

		// If the email address has changed, add a confirmation.
		if (isset($data['email']) && $data['email'] !== null && $data['email'] != $iter['email'])
		{
			$model = get_model('DataModelEmailConfirmationToken');

			$token = $model->create_token($iter, $data['email']);

			$language_code = strtolower(i18n_get_language());

			$variables = [
				'naam' => member_first_name($iter, IGNORE_PRIVACY),
				'email' => $token['email'],
				'link' => $token['link']
			];

			// Send the confirmation to the new email address
			parse_email_object("email_confirmation_{$language_code}.txt", $variables)->send($token['email']);
			$_SESSION['alert'] = __('We’ve sent a confirmation mail to your new email address.');
		}

		return $this->view->redirect('profiel.php?lid=' . $iter['id'] . '&view=personal');
	}

	private function _report_changes_upstream(DataIterMember $iter, array $fields, array $old_values)
	{
		// Inform the board that member info has been changed.
		$subject = "Lidgegevens gewijzigd";
		$body = sprintf("De gegevens van %s zijn gewijzigd:", member_full_name($iter, IGNORE_PRIVACY)) . "\n\n";
		
		foreach ($fields as $field)
			$body .= sprintf("%s:\t%s (was: %s)\n", $field, $iter[$field] ? $iter[$field] : "<verwijderd>", $old_values[$field]);
			
		mail('administratie@svcover.nl', $subject, $body, "From: webcie@ai.rug.nl\r\nContent-Type: text/plain; charset=UTF-8");
		mail('secretaris@svcover.nl', $subject, sprintf("De gegevens van %s zijn gewijzigd:\n\nDe wijzigingen zijn te vinden op administratie@svcover.nl", member_full_name($iter, IGNORE_PRIVACY)), "From: webcie@ai.rug.nl\r\nContent-Type: text/plain; charset=UTF-8");

		try {
			get_secretary()->updatePersonFromIterChanges($iter);
		} catch (RuntimeException $e) {
			// Todo: replace this with a serious more general logging call
			error_log($e, 1, 'webcie@rug.nl', "From: webcie-cover-php@svcover.nl");
		}
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
			$error = __('The following fields are to long: ') . implode(', ', array_map('field_for_display', $errors));
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
				$message[] = __('The current password is incorrect.');
			}
		}

		if (!isset($_POST['wachtwoord_nieuw'])) {
			$errors[] = 'wachtwoord_nieuw';
			$message[] = __('The new password hasn\'t been filled in.');
		} elseif (!isset($_POST['wachtwoord_opnieuw']) || $_POST['wachtwoord_nieuw'] !== $_POST['wachtwoord_opnieuw']) {
			$errors[] = 'wachtwoord_opnieuw';
			$message[] = __('The new password hasn\'t been filled in correctly twice.');
		} elseif (strlen($_POST['wachtwoord_nieuw']) < 6) {
			$errors[] = 'wachtwoord_nieuw';
			$message[] = __('Your new password is too short.');
		}
		
		if (count($errors) > 0) {
			$error = implode("\n", $message);
			return $this->view->render_profile_tab($iter, $error, $errors);
		}
		
		$this->model->set_password($iter, $_POST['wachtwoord_nieuw']);

		$_SESSION['alert'] = __('Your password has been changed.');

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

	protected function _update_photo(DataIterMember $iter)
	{
		$error = null;

		if ($_FILES['photo']['error'] == UPLOAD_ERR_INI_SIZE)
			$error = sprintf(__('The image file is too large. The maximum file size is %s.'),
				ini_get('upload_max_filesize') . ' bytes');

		elseif ($_FILES['photo']['error'] != UPLOAD_ERR_OK)
			$error = sprintf(__('The image hasn’t been uploaded correctly. PHP reports error code %d.'), $_FILES['photo']['error']);

		elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
			$error = __('The image file is not a file uploaded by PHP.');
		
		elseif (!($image_meta = getimagesize($_FILES['photo']['tmp_name'])))
			$error = __('The uploaded file could not be read.');

		if ($error)
			return $this->view->render_profile_tab($iter, $error, array('photo'));

		if (get_identity()->member_in_committee(COMMISSIE_EASY))
		{
			if (!($fh = fopen($_FILES['photo']['tmp_name'], 'rb')))
				throw new RuntimeException(__('The uploaded file could not be opened.'));

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

			$_SESSION['alert'] = __('Your photo has been submitted. It may take a while before it will be updated.');
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
		if (!$this->policy->user_can_update($iter))
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

	protected function run_photo(DataIterMember $iter)
	{
		// Only members themselves and the AC/DCee can change photos
		if (!$this->policy->user_can_update($iter)
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
		if (!get_identity()->is_member())
			throw new UnauthorizedException('You need to log in to be able to export v-cards/');

		if (!$this->policy->user_can_read($member))
			throw new UnauthorizedException('This member is no longer a member of Cover.');

		return $this->view->render_vcard($member);
	}

	public function run_export_incassocontract(DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new UnauthorizedException();

		require_once 'include/incassomatic.php';

		$incasso_api = \incassomatic\shared_instance();

		$fh = $incasso_api->getContractTemplatePDF($member);

		header('Content-Type: application/pdf');
		fpassthru($fh);
		fclose($fh);
	}

	public function run_print_incassocontract(DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new UnauthorizedException();

		require_once 'include/incassomatic.php';

		$incasso_api = \incassomatic\shared_instance();

		$result = $incasso_api->printContractTemplatePDF($member);

		return $this->view->render_incassomatic_tab($member, $result->message);
	}


	public function run_public(DataIterMember $member)
	{
		if (!$this->policy->user_can_read($member))
			throw new UnauthorizedException('This person is no longer a member of Cover, which is why they no longer have a public profile.');

		return $this->view->render_public_tab($member);
	}

	public function run_mailing_lists(DataIterMember $member)
	{

		if (!$this->policy->user_can_update($member)
			&& !get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('mailing_list', $member))
			return $this->_update_mailing_lists($member);

		$model = get_model('DataModelMailinglist');
		$mailing_lists = $model->get_for_member($member);
	
		$lists = array_filter($mailing_lists, function($list) {
			// return true;
			return get_policy($list)->user_can_subscribe($list);
		});

		return $this->view->render('mailing_lists_tab.twig', ['iter' => $member, 'mailing_lists' => $lists]);
		// return $this->view->render_mailing_lists_tab($lists);
	}

	public function run_sessions(DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new UnauthorizedException();

		return $this->view->render_sessions_tab($member);
	}

	public function run_kast(DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new UnauthorizedException();

		return $this->view->render_kast_tab($member);
	}

	public function run_incassomatic(DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new UnauthorizedException();

		return $this->view->render_incassomatic_tab($member);
	}

	public function run_confirm_email()
	{
		$model = get_model('DataModelEmailConfirmationToken');

		try {
			$token = $model->get_iter($_GET['token']);
		} catch (Exception $e) {
			return $this->view->render_confirm_email(false);
		}

		// Update the member's email address
		$member = $token['member'];
		$old_email = $member['email'];
		$member['email'] = $token['email'];
		$this->model->update($member);

		// Report the changes to the secretary and to Secretary (the system...)
		$this->_report_changes_upstream($member, ['email'], ['email' => $old_email]);

		// Delete this and all other tokens for this user
		$model->invalidate_all($token['member']);

		return $this->view->render_confirm_email(true);
	}

	public function run_index()
	{
		return $this->view->redirect('almanak.php');
	}

	protected function run_impl()
	{
		$view = isset($_GET['view']) ? $_GET['view'] : 'public';
		
		if ($view == 'confirm_email')
			return $this->run_confirm_email(); // a bit of a special case: a method that does not need a DataIterMember :O

		if (isset($_GET['lid']))
			$iter = $this->model->get_iter($_GET['lid']);
		elseif (get_auth()->logged_in())
			$iter = get_identity()->member();
		else
			return $this->run_index();

		if (!method_exists($this, 'run_' . $view))
			throw new NotFoundException("View '$view' not implemented by " . get_class($this));

		return call_user_func_array([$this, 'run_' . $view], [$iter]);
	}
}

$controller = new ControllerProfiel();
$controller->run();

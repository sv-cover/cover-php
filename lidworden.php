<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/form.php';
	require_once 'include/controllers/Controller.php';
	require_once 'include/secretary.php';
	
	class ControllerLidWorden extends Controller
	{
		var $model = null;
		var $sizes = null;

		function ControllerLidWorden() {
			$this->model = get_model('DataModelMember');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Lid worden')));
			run_view('lidworden::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}

		function _process_lidworden()
		{
			$non_empty = function($x) {
				return strlen($x) > 0;
			};

			$_POST['birth_date'] = sprintf('%04d-%02d-%02d',
				$_POST['birth_date_year'],
				$_POST['birth_date_month'],
				$_POST['birth_date_day']);

			$fields = array(
				'first_name' => [$non_empty],
				'family_name_preposition' => [],
				'family_name' => [$non_empty],
				'street_name' => [function($x) { return preg_match('/\d+/', $x); }],
				'postal_code' => [function($x) { return preg_match('/^\d{4}\s*[a-z]{2}$/i', $x); }],
				'place' => [$non_empty],
				'phone_number' => [function($x) { return strlen($x) == 0 || preg_match('/^\+?\d+$/', $x); }, function($x) { return str_replace(' ', '', $x); }],
				'email_address' => [function($x) {
					// Check whether the email address is something looking like an email address
					if (!preg_match('/@\w+\.\w+/', $x))
						return false;

					// Check whether the email address is not already in use
					return !$this->model->get_from_email($x);
				}],
				'birth_date' => [function($x) { return preg_match('/^\d{4}\-[01]\d\-[0123]\d$/', $x); }],
				'gender' => [function($x) { return in_array($x, ['b', 'm', 'o']); }],
				'iban' => [function($x) { return preg_match('/^[A-Z]{2}\d{2}[A-Z]{4}\d+$/', $x); }],
				'bic' => [],
				'membership_study_name' => [],
				'membership_study_phase' => [function($x) { return in_array($x, ['b', 'm']); }],
				'membership_student_number' => [
					function($x) { return strlen($x) == 0 || ctype_digit($x); },
					function($x) { return ltrim($x, 's'); }],
				'membership_year_of_enrollment' => [function($x) { return $x > 1900 && $x < 2100; }],
				'authorization' => [function($x) { return $x == 'yes'; }],
				'mailing' => [],
				'spam' => [function($x) { return in_array(strtolower($_POST['spam']), array('groen', 'green', 'coverrood', 'cover red')); }]
			);

			$errors = array();

			foreach ($fields as $field => $properties)
			{
				$data[$field] = isset($_POST[$field]) ? $_POST[$field] : '';

				if (isset($properties[1]))
					$data[$field] = call_user_func_array($properties[1], [$data[$field]]);

				if (isset($properties[0]))
					if (!call_user_func_array($properties[0], [$data[$field]]))
						$errors[] = $field;
			}
			
			if (count($errors) > 0)
				return $this->get_content('lidworden', null, array('errors' => $errors));
			
			$letters = array_merge(range('a', 'z'), range(0, 9));

			$confirmation_code = 'c';

			$confirmation_code_length = 31;

			for ($i = 0; $i < $confirmation_code_length; ++$i)
				$confirmation_code .= $letters[mt_rand(0, count($letters) - 1)];

			// Store this info temporarily in the database and create a confirmation mail
			$db = get_db();

			$db->insert('registrations', [
				'confirmation_code' => $confirmation_code,
				'data' => json_encode($data),
			]);

			$mail = parse_email(
				'lidworden_confirmation_' . strtolower(i18n_get_language()) . '.txt',
				array_merge($data, compact('confirmation_code')));

			mail($data['email_address'], __('Lidmaatschapsaanvraag bevestigen'), $mail, 'From: Cover <board@svcover.nl>');
			
			header('Location: lidworden.php?verzonden=true');
		}

		function _process_confirm($confirmation_code)
		{
			$db = get_db();

			$row = $db->query_first(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s'",
				$db->escape_string($confirmation_code)));

			if (!$row)
				die(__('Kon aanmelding niet meer vinden. Je kan je proberen opnieuw aan te melden, of het bestuur (board@svcover.nl) even mailen.'));

			$data = json_decode($row['data'], true);

			$mail = parse_email('lidworden.txt', $data);

			mail('administratie@svcover.nl', 'Lidaanvraag', $mail, 'From: Cover <board@svcover.nl>');

			$db->delete('registrations', sprintf("confirmation_code = '%s'", $db->escape_string($confirmation_code)));

			try {
				$secretary = new SecretaryApi(
					get_config_value('secretary_root'),
					get_config_value('secretary_user'),
					get_config_value('secretary_password'));

				$response = $secretary->createPerson($data);

				if (!$response->success)
					throw new RuntimeException('Secretary failed with error: ' . $response->errors);
			
				mail('secretaris@svcover.nl',
					'Lidaanvraag',
					"Er is een nieuwe lidaanvraag ingediend.\n"
					. "Je kan de aanvraag bevestigen op " . $response->url . "\n"
					. "De gegevens zijn voor de zekerheid ook te vinden op administratie@svcover.nl.",
					'From: Cover <board@svcover.nl>');

				$this->_create_member(array_merge($data, ['id' => $response->person_id]));
			}
			catch (Exception $e)
			{
				mail('secretaris@svcover.nl',
					'Lidaanvraag',
					"Er is een nieuwe lidaanvraag ingediend.\n"
					. "Helaas kon de aanmelding niet automatisch aan de ledenadmin worden toegevoegd, de WebCie is hierover geïnformeerd.\n"
					. "De gegevens zijn in ieder geval te vinden op administratie@svcover.nl.",
					'From: Cover <board@svcover.nl>');

				mail('webcie@svcover.nl',
					'Fout tijdens lidaanvraag',
					'Er ging iets fout tijdens een lidaanvraag.'
					.' Zie de error log van www voor ' . date('Y-m-d H:i:s') . "\n\n"
					. $e->getMessage() . "\n"
					. $e->getTraceAsString());
			}

			$this->get_content('confirmed');
		}

		protected function _create_member($data)
		{
			$member = new DataIterMember($this->model, $data['id'], [
				'id' => $data['id'],
				'voornaam' => $data['first_name'],
				'tussenvoegsel' => $data['family_name_preposition'],
				'achternaam' => $data['family_name'],
				'adres' => $data['street_name'],
				'postcode' => $data['postal_code'],
				'woonplaats' => $data['place'],
				'email' => $data['email_address'],
				'telefoonnummer' => $data['phone_number'],
				'beginjaar' => $data['membership_year_of_enrollment'],
				'geboortedatum' => $data['birth_date'],
				'geslacht' => $data['gender'],
				'type' => MEMBER_STATUS_UNCONFIRMED
			]);
			$member->set('privacy', 958698063);

			$this->model->insert($member);

			// Create profile for this member
			$nick = member_full_name($member, true, false);
			
			if (strlen($nick) > 50)
				$nick = $member->get('voornaam');
			
			if (strlen($nick) > 50)
				$nick = '';
			
			$iter = new DataIterMember($this->model, -1, array('lidid' => $member->get_id(), 'nick' => $nick));
			
			$this->model->insert_profiel($iter);

			// Create a password
			$passwd = create_pronouncable_password();
			
			$this->model->set_password($member, $passwd);
			
			// Setup e-mail
			$data['wachtwoord'] = $passwd;
			$mail = parse_email('nieuwlid.txt', array_merge($member->data, ['wachtwoord' => $data['wachtwoord']]));

			mail($data['email_address'], 'Website Cover', $mail, 'From: Cover <board@svcover.nl>');
			mail('administratie@svcover.nl', 'Website Cover', $mail, 'From: Cover <board@svcover.nl>');

			// Set up on mailing list
			if (!empty($data['mailing']))
			{
				$mailing_model = get_model('DataModelMailinglijst');
				$mailinglist = $mailing_model->get_lijst('directmailing@svcover.nl');
				$mailing_model->aanmelden($mailinglist, $member->get_id());
			}
		}
		
		function run_impl() {
			if (isset($_POST['submlidworden']))
				$this->_process_lidworden();
			elseif (isset($_GET['confirmation_code']))
				$this->_process_confirm($_GET['confirmation_code']);
			else if (isset($_GET['verzonden']))
				$this->get_content('verzonden');
			else {
				$this->get_content('lidworden');
			}
		}
	}
	
	$controller = new ControllerLidWorden();
	$controller->run();

<?php
	namespace App\Controller;

	require_once 'src/framework/controllers/Controller.php';
	require_once 'src/services/secretary.php';
	
	class MembershipController extends \Controller
	{
		protected $view_name = 'membership';

		public function __construct($request, $router)
		{
			$this->model = get_model('DataModelMember');

			parent::__construct($request, $router);
		}
		
		protected function _process_lidworden()
		{
			$non_empty = function($x) {
				return strlen($x) > 0;
			};

			$fields = array(
				'first_name' => [$non_empty],
				'family_name_preposition' => [function($x) {
					/*
					if (strlen($x) > 0)
						foreach (explode('/\s+/', strtolower($x), PREG_SPLIT_NO_EMPTY) as $part)
							if (!in_array(trim($part), ['van', 'von', 'de', 'der', 'den', "d'", 'het', "'t", 'ten', 'af', 'aan', 'bij', 'het',
								'onder', 'boven', 'in', 'op', 'over', "'s", 'te', 'ten', 'ter', 'tot', 'uit', 'uijt', 'vanden', 'ver', 'voor']))
								return false;
					*/
					return true;
				}, 'trim'],
				'family_name' => [$non_empty],
				'street_name' => [function($x) { return preg_match('/\d+/', $x); }],
				'postal_code' => [function($x) { return preg_match('/^\d{4}\s*[a-z]{2}$/i', $x); }],
				'place' => [$non_empty],
				'phone_number' => [
					function($x) {
						return preg_match('/^\+?\d+$/', $x);
					},
					function($x) {
						return str_replace(' ', '', $x);
					}
				],
				'email_address' => [function($x) {
					// Check whether the email address is something looking like an email address
					return preg_match('/@\w+\.\w+/', $x);
				}],
				'birth_date' => [
					function($x) {
						return preg_match('/^((?:19|20)\d\d)\-([01]?\d)\-([0123]?\d)$/', $x, $match)
							&& checkdate((int) $match[2], (int) $match[3], (int) $match[1])
							&& $match[1] < (int) date('Y') - 9; // You need to be at least 10 years old ;)
					},
					function($x) {
						// Turn date around if passed the other way
						return preg_match('/^([0123]?\d)\-([01]?\d)\-((?:19|20)\d\d)$/', $x, $match)
							? sprintf('%s-%s-%s', $match[3], $match[2], $match[1])
							: $x;
					}],
				'gender' => [function($x) { return in_array($x, ['f', 'm', 'o']); }],
				'iban' => [
					function($x) {
						if (!empty(get_config_value('no_iban_string'))) {
							return $x === get_config_value('no_iban_string') || \IsoCodes\Iban::validate($x);
						}
						return \IsoCodes\Iban::validate($x);
					},
					function($x) {
						return preg_replace('/[^A-Z0-9]/u', '', strtoupper($x));
					}
				],
				'bic' => [
					function($x) { return strlen($x) === 0 || \IsoCodes\SwiftBic::validate($x);},
					function($x) { return trim($x); }
				],
				'membership_study_name' => [],
				'membership_study_phase' => [function($x) { return in_array($x, ['b', 'm']); }],
				'membership_student_number' => [
					function($x) { return strlen($x) == 0 || ctype_digit($x); },
					function($x) { return ltrim($x, 'sS'); }],
				'membership_year_of_enrollment' => [function($x) { return $x > 1900 && $x < 2100; }],
				'authorization' => [function($x) { return $x == 'yes'; }],
				'option_mailing' => [],
				'terms_conditions_agree' => [function($x) { return $x == 'yes'; }],
				'terms_conditions_version' => [$non_empty],
				'spam' => [function($x) { return in_array(strtolower($_POST['spam']), array('groen', 'green', 'coverrood', 'cover red')); }]
			);

			$errors = array();

			$data = [];

			foreach ($fields as $field => $properties)
			{
				$data[$field] = isset($_POST[$field]) ? $_POST[$field] : '';

				if (isset($properties[1]))
					$data[$field] = call_user_func_array($properties[1], [$data[$field]]);

				if (isset($properties[0]))
					if (!call_user_func_array($properties[0], [$data[$field]]))
						$errors[] = $field;
			}
			
			if (count(array_intersect(['first_name', 'family_name', 'family_name_preposition'], $errors)) > 0)
				$errors[] = 'name';

			// Test whether email is already used
			// (already a member? Or previous member?)
			if (!in_array('email_address', $errors)) {
				try {
					$existing_member = $this->model->get_from_email($_POST['email_address']);
					return $this->view->render('known_member.twig', compact('existing_member'));
				} catch (\DataIterNotFoundException $e) {
					// All clear :)
				}
			}

			if (count($errors) > 0)
				return $this->view->render_form($errors);
			
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

			$this->_send_confirmation_mail($confirmation_code);

			return $this->view->redirect($this->generate_url('join', ['verzonden' => 'true']));
		}

		private function _send_confirmation_mail($confirmation_code)
		{
			$db = get_db();

			$data_str = $db->query_value(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s' AND confirmed_on IS NULL", $db->escape_string($confirmation_code)));

			if ($data_str === null)
				throw new \NotFoundException('Could not find registration code');

			$data = json_decode($data_str, true);

			$mail = parse_email(
				'lidworden_confirmation_' . strtolower(i18n_get_language()) . '.txt',
				array_merge($data, compact('confirmation_code')));

			mail($data['email_address'], __('Confirm membership application'), $mail,
				implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));
		}

		protected function _process_confirm($confirmation_code)
		{
			try {
				// First, send a mail to administratie@svcover.nl for archiving purposes
				$this->_process_confirm_mail($confirmation_code);
				
				// If that worked out right, we can mark this registration as confirmed.
				get_db()->update('registrations',
					['confirmed_on' => date('Y-m-d H:i:s')],
					sprintf("confirmation_code = '%s'", get_db()->escape_string($confirmation_code)));

				try
				{
					// Try to add the member to Secretary. If this works out correctly
					// the registration will be deleted (and Secretary will add the
					// member to the leden table through the API).
					$this->_process_confirm_secretary($confirmation_code);
				}
				catch (\Exception $e)
				{
					// Well, that didn't work out. Report the error to everybody.
					// The registration will be marked as confirmed, but not deleted
					// so one can try again later when Secretary is available again.
					sentry_report_exception($e);

					mail('secretaris@svcover.nl',
						'Lidaanvraag (niet verwerkt in Secretary)',
						"Er is een nieuwe lidaanvraag ingediend.\n"
						. "Helaas kon de aanmelding niet automatisch aan de ledenadmin worden toegevoegd, de WebCie is hierover geïnformeerd.\n"
						. "De gegevens zijn in ieder geval te vinden op administratie@svcover.nl.",
						implode("\r\n", ['Content-Type: text/plain; charset=UTF-8']));

					mail('webcie@svcover.nl',
						'Fout tijdens lidaanvraag',
						'Er ging iets fout tijdens een lidaanvraag.'
						.' Zie de error log van www voor ' . date('Y-m-d H:i:s') . "\n\n"
						. $e->getMessage() . "\n"
						. $e->getTraceAsString(),
						implode("\r\n", ['Content-Type: text/plain; charset=UTF-8']));
				}

				return $this->view->redirect($this->generate_url('join', ['confirmed' => 'true']));
			} catch (\NotFoundException $e) {
				return $this->view->render('not_found.twig');
			}
		}

		protected function _process_confirm_mail($confirmation_code)
		{
			$db = get_db();

			$row = $db->query_first(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s' AND confirmed_on IS NULL",
				$db->escape_string($confirmation_code)));

			if (!$row)
				throw new \NotFoundException('Could not find registration code');

			$data = json_decode($row['data'], true);

			$mail = parse_email('lidworden.txt', $data);

			$name = $data['first_name'] . (strlen($data['family_name_preposition']) ? ' ' . $data['family_name_preposition'] : '') . ' ' . $data['family_name'];

			mail('administratie@svcover.nl', 'Lidaanvraag ' . $name, $mail,
				implode("\r\n", ['Content-Type: text/plain; charset=UTF-8']));
		}

		protected function _process_confirm_secretary($confirmation_code)
		{
			$db = get_db();

			$row = $db->query_first(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s'",
				$db->escape_string($confirmation_code)));

			if (!$row)
				throw new \NotFoundException('Could not find registration code');

			$data = json_decode($row['data'], true);

			$response = get_secretary()->createPerson($data);

			$db->delete('registrations', sprintf("confirmation_code = '%s'", $db->escape_string($confirmation_code)));
		}

		public function run_pending_index()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR) &&
				!get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) &&
				!get_identity()->member_in_committee(COMMISSIE_EASY))
				throw new \UnauthorizedException();

			$db = get_db();

			$message = null;

			if ($this->_form_is_submitted('pending'))
			{
				switch (isset($_POST['action']) ? $_POST['action'] : null)
				{
					case 'push':
						$success = 0;
						foreach ($_POST['confirmation_code'] as $confirmation_code) {
							try {
								$this->_process_confirm_secretary($confirmation_code);
								$success++;
							} catch (\Exception $e) {
								sentry_report_exception($e);
							}
						}
						$message = sprintf('Added %d out of %d registrations to Secretary',
							$success,
							count($_POST['confirmation_code']));
						break;

					case 'resend':
						foreach ($_POST['confirmation_code'] as $confirmation_code)
							$this->_send_confirmation_mail($confirmation_code);
						$message = sprintf('Resent %d confirmation emails', count($_POST['confirmation_code']));
						break;

					case 'delete':
						if (count($_POST['confirmation_code']) > 0) {
							$rows = $db->execute(sprintf("DELETE FROM registrations WHERE confirmation_code IN (%s)",
								$db->quote_value($_POST['confirmation_code'])));
							$message = sprintf('Deleted %d registrations', $rows);
						}
						break;
				}
			}

			$registrations = $db->query("
				SELECT
					confirmation_code,
					data,
					registerd_on as registered_on,
					confirmed_on
				FROM
					registrations
				ORDER BY
					registerd_on DESC");

			foreach ($registrations as &$registration)
				$registration['data'] = json_decode($registration['data'], true);

			return $this->view->render_pending($registrations, $message);
		}

		protected function run_pending_update($confirmation_code)
		{
			if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR) &&
				!get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) &&
				!get_identity()->member_in_committee(COMMISSIE_EASY))
				throw new \UnauthorizedException();

			$db = get_db();

			if ($this->_form_is_submitted('update_pending', $confirmation_code)) {
				$db->update('registrations',
					['data' => json_encode($_POST['data'])],
					sprintf('confirmation_code = %s', $db->quote($confirmation_code)));

				return $this->view->redirect($this->generate_url('join', ['view' => 'pending-confirmation']));
			}

			$row = $db->query_first(sprintf("SELECT * FROM registrations WHERE confirmation_code = '%s'",
				$db->escape_string($confirmation_code)));

			if ($row === null)
				throw new \NotFoundException();

			$row['data'] = json_decode($row['data'], true);

			return $this->view->render_pending_form($row);
		}
		
		protected function run_impl()
		{
			if ($this->_form_is_submitted('sign_up'))
				return $this->_process_lidworden();
			elseif (isset($_GET['confirmation_code']) && !isset($_GET['view']))
				return $this->_process_confirm($_GET['confirmation_code']);
			else if (isset($_GET['verzonden']))
				return $this->view->render_submitted();
			else if (isset($_GET['confirmed']))
				return $this->view->render_confirmed();
			else if (isset($_GET['view']) && $_GET['view'] == 'pending-confirmation' && !empty($_GET['confirmation_code']))
				return $this->run_pending_update($_GET['confirmation_code']);
			else if (isset($_GET['view']) && $_GET['view'] == 'pending-confirmation')
				return $this->run_pending_index();
			else {
				return $this->view->render_form();
			}
		}
	}

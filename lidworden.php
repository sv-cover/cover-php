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
				'phone_number' => [function($x) { return strlen($x) == 0 || preg_match('/^\+?\d+$/', $x); }, function($x) { return str_replace(' ', '', $x); }],
				'email_address' => [function($x) {
					// Check whether the email address is something looking like an email address
					if (!preg_match('/@\w+\.\w+/', $x))
						return false;

					// Check whether the email address is not already in use
					return !$this->model->get_from_email($x);
				}],
				'birth_date' => [function($x) {
					return preg_match('/^(\d{4})\-([01]\d)\-([0123]\d)$/', $x, $match)
						&& checkdate($match[2], $match[3], $match[1]);
					}],
				'gender' => [function($x) { return in_array($x, ['f', 'm', 'o']); }],
				'iban' => [
					function($x) {
						// If it looks like IBAN, validate it as IBAN. This allows us to still pass in info like "I don't have any yet"
						$stripped = preg_replace('/\s+|\./', '', strtoupper($x));
						return preg_match('/^[A-Z]{2}\d{2,}/', $stripped)
							? \IsoCodes\Iban::validate($stripped)
							: true;
					},
					function($x) {
						$stripped = preg_replace('/\s+|\./', '', strtoupper($x));
						return \IsoCodes\Iban::validate($stripped) ? $stripped : $x;
					}
				],
				'bic' => [
					function($x) { return strlen($x) === 0 || \IsoCodes\SwiftBic::validate($x);},
					function($x) { return trim($x, ' '); }
				],
				'membership_study_name' => [],
				'membership_study_phase' => [function($x) { return in_array($x, ['b', 'm']); }],
				'membership_student_number' => [
					function($x) { return strlen($x) == 0 || ctype_digit($x); },
					function($x) { return ltrim($x, 'sS'); }],
				'membership_year_of_enrollment' => [function($x) { return $x > 1900 && $x < 2100; }],
				'authorization' => [function($x) { return $x == 'yes'; }],
				'option_mailing' => [],
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

			$this->_send_confirmation_mail($confirmation_code);

			return $this->redirect('lidworden.php?verzonden=true');
		}

		private function _send_confirmation_mail($confirmation_code)
		{
			$db = get_db();

			$data_str = $db->query_value(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s'", $db->escape_string($confirmation_code)));

			if ($data_str === null)
				throw new InvalidArgumentException('Could not find registration code');

			$data = json_decode($data_str, true);

			$mail = parse_email(
				'lidworden_confirmation_' . strtolower(i18n_get_language()) . '.txt',
				array_merge($data, compact('confirmation_code')));

			mail($data['email_address'], __('Lidmaatschapsaanvraag bevestigen'), $mail,
				implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));
		}

		function _process_confirm($confirmation_code)
		{
			$db = get_db();

			$row = $db->query_first(sprintf("SELECT data FROM registrations WHERE confirmation_code = '%s'",
				$db->escape_string($confirmation_code)));

			if (!$row)
				die(__('We konden je aanmelding niet meer vinden. Je kan je proberen opnieuw aan te melden, of het bestuur (board@svcover.nl) even mailen.'));

			$data = json_decode($row['data'], true);

			$mail = parse_email('lidworden.txt', $data);

			$name = $data['first_name'] . (strlen($data['family_name_preposition']) ? ' ' . $data['family_name_preposition'] : '') . ' ' . $data['family_name'];

			mail('administratie@svcover.nl', 'Lidaanvraag ' . $name, $mail,
				implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));

			try {
				$response = get_secretary()->createPerson($data);

				mail('secretaris@svcover.nl',
					'Lidaanvraag',
					"Er is een nieuwe lidaanvraag ingediend.\n"
					. "Je kan de aanvraag bevestigen op " . $response->url . "\n"
					. "De gegevens zijn voor de zekerheid ook te vinden op administratie@svcover.nl.",
					implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));
			}
			catch (Exception $e)
			{
				throw $e;
				
				mail('secretaris@svcover.nl',
					'Lidaanvraag (niet verwerkt in Secretary)',
					"Er is een nieuwe lidaanvraag ingediend.\n"
					. "Helaas kon de aanmelding niet automatisch aan de ledenadmin worden toegevoegd, de WebCie is hierover geïnformeerd.\n"
					. "De gegevens zijn in ieder geval te vinden op administratie@svcover.nl.",
					implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));

				mail('webcie@svcover.nl',
					'Fout tijdens lidaanvraag',
					'Er ging iets fout tijdens een lidaanvraag.'
					.' Zie de error log van www voor ' . date('Y-m-d H:i:s') . "\n\n"
					. $e->getMessage() . "\n"
					. $e->getTraceAsString(),
					implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));
			}

			$db->delete('registrations', sprintf("confirmation_code = '%s'", $db->escape_string($confirmation_code)));

			return $this->redirect('lidworden.php?confirmed=true');
		}

		public function run_pending()
		{
			if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR))
				throw new UnauthorizedException();

			$db = get_db();

			$message = null;

			switch (isset($_POST['action']) ? $_POST['action'] : null) {
				case 'resend':
					foreach ($_POST['confirmation_code'] as $confirmation_code)
						$this->_send_confirmation_mail($confirmation_code);
					$message = sprintf('Resent %d confirmation emails', count($_POST['confirmation_code']));
					break;

				case 'delete':
					if (count($_POST['confirmation_code']) > 0) {
						$db->query(sprintf("DELETE FROM registrations WHERE confirmation_code IN (%s)",
							implode(', ', array_map(function($code) use ($db) {
								return sprintf("'%s'", $db->escape_string($code));
							}, $_POST['confirmation_code']))));

						$message = sprintf('Deleted %d registrations', $db->get_affected_rows());
					}
					break;
			}

			$registrations = $db->query("
				SELECT
					confirmation_code,
					data,
					DATE(registerd_on) as registered_on
				FROM
					registrations
				ORDER BY
					registerd_on DESC");

			foreach ($registrations as &$registration)
				$registration['data'] = json_decode($registration['data'], true);

			return $this->get_content('pending', $registrations, compact('message'));
		}
		
		protected function run_impl() {
			if (isset($_POST['submlidworden']))
				$this->_process_lidworden();
			elseif (isset($_GET['confirmation_code']))
				$this->_process_confirm($_GET['confirmation_code']);
			else if (isset($_GET['verzonden']))
				$this->get_content('verzonden');
			else if (isset($_GET['confirmed']))
				$this->get_content('confirmed');
			else if (isset($_GET['view']) && $_GET['view'] == 'pending-confirmation')
				$this->run_pending();
			else {
				$this->get_content('lidworden');
			}
		}
	}
	
	$controller = new ControllerLidWorden();
	$controller->run();

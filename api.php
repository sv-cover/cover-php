<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';

class ControllerApi extends Controller
{
	static protected $secretary_mapping = [
		'voornaam' => 'first_name',
		'tussenvoegsel' => 'family_name_preposition',
		'achternaam' => 'family_name',
		'adres' => 'street_name',
		'postcode' => 'postal_code',
		'woonplaats' => 'place',
		'email' => 'email_address',
		'telefoonnummer' => 'phone_number',
		'beginjaar' => 'membership_year_of_enrollment',
		'geboortedatum' => 'birth_date',
		'geslacht' => 'gender',
		'member_from' => 'membership_started_on',
		'member_till' => 'membership_ended_on',
		'donor_from' => 'donorship_date_of_authorization',
		'donor_till' => 'donorship_ended_on'
	];
		
	public function __construct()
	{
		// Do nothing.
	}


	public function api_agenda($committees=null)
	{
		if ($committees !== null && !is_array($committees))
			$committees = array($committees);

		/** @var DataModelAgenda $agenda */
		$agenda = get_model('DataModelAgenda');

		$activities = array();

		// TODO logged_in() incidentally works because the session is read from $_GET[session_id] by
		// the session provider. But the current session should be set more explicit.
		foreach ($agenda->get_agendapunten() as $activity){
			if ($committees !== null && !in_array($activity['committee']['login'], $committees))
				continue;
			if (get_policy($agenda)->user_can_read($activity) )
				$activities[] = $activity->data;
		}

		// Add the properties that Newsletter expects
		foreach ($activities as &$activity) {
			$van = strtotime($activity['van']);
			$activity['vandatum'] = date('d', $van);
			$activity['vanmaand'] = date('m', $van);
		}

		return $activities;
	}

	public function api_get_agendapunt()
	{
		/** @var DataModelAgenda $agenda */
		$agenda = get_model('DataModelAgenda');

		if (empty($_GET['id']))
			throw new \InvalidArgumentException('Missing id parameter');

		$agendapunt = $agenda->get_iter($_GET['id']);

		// TODO this incidentally works because the session is read from $_GET[session_id] by
		// the session provider. But the current session should be set more explicit.
		if (!get_policy('DataModelAgenda')->user_can_read($agendapunt))
			throw new UnauthorizedException('You are not authorized to read this event');

		$data = $agendapunt->data;
		
		// Backwards compatibility for consumers of the API
		$data['commissie'] = $data['committee_id'];

		return ['result' => $data];
	}

	public function api_session_create($email, $password, $application)
	{
		/** @var DataModelMember $user_model */
		$user_model = get_model('DataModelMember');

		if (!($member = $user_model->login($email, $password)))
			throw new InvalidArgumentException('Invalid username or password');

		/** @var DataModelSession $session_model */
		$session_model = get_model('DataModelSession');

		$session = $session_model->create($member->get_id(), $application);

		return ['result' => [
			'session_id' => $session->get('session_id'),
			'details' => $member->data
		]];
	}

	public function api_session_destroy($session_id)
	{
		/** @var DataModelSession $session_model */
		$session_model = get_model('DataModelSession');

		$session = $session_model->resume($session_id);

		return $session_model->delete($session);
	}

	public function api_session_get_member($session_id)
	{
		/** @var DataModelSession $session_model */
		$session_model = get_model('DataModelSession');

		$session = $session_model->resume($session_id);

		if (!$session)
			throw new InvalidArgumentException('Invalid session id');

		$auth = new ConstantSessionProvider($session);

		$ident = get_identity_provider($auth);

		$fields = array_merge(DataIterMember::fields(), ['type']);

		// Prepare data for member 
		$member = $ident->member();
		$data = [];
		foreach ($fields as $field)
			$data[$field] = $member[$field];

		// Prepare committee data
		$committee_model = get_model('DataModelCommissie');
		$committee_data = [];

		
		// $committee_ids = $ident->get_override_committees() ?? $member['committees'];
		$committees = $committee_model->find(['id__in' => $member['committees'] ]);

		// For now just return login and committee name
		foreach ($committees as $committee)
			$committee_data[$committee['login']] = $committee['naam'];
		
		return array('result' => array_merge($data, ['committees' => $committee_data]));
	}

	public function api_session_test_committee($session_id, $committees)
	{
		if (!is_array($committees))
			$comittees = array($committees);

		// Get the session
		/** @var DataModelSession $session_model */
		$session_model = get_model('DataModelSession');

		$session = $session_model->get_iter($session_id);

		$auth = new ConstantSessionProvider($session);

		$ident = get_identity_provider($auth);

		/** @var DataModelCommissie $committee_model */
		$committee_model = get_model('DataModelCommissie');

		foreach ($committees as $committee_name)
		{
			// Find the committee id
			$committee = $committee_model->get_from_name($committee_name);

			// And finally, test whether the searched for committee and the member is committees intersect
			if ($ident->member_in_committee($committee->get_id()))
				return array('result' => true, 'committee' => $committee['naam']);
		}

		return array('result' => false);
	}

	public function api_get_member($member_id)
	{
		/** @var DataModelMember $user_model */
		$user_model = get_model('DataModelMember');

		$member = $user_model->get_iter($member_id);

		// Hide all private fields for this user. is_private() uses
		// logged_in() which uses the session_id get variable. So sessions
		// are taken into account ;)
		foreach ($member->data as $field => $value)
			if ($user_model->is_private($member, $field, true))
				$member->data[$field] = null;

		// This one is passed as parameter anyway, it is already known.
		$member->data['id'] = (int) $member_id;

		return array('result' => array_merge(
			$member->data,
			['type' => $member['type']]
		));
	}

	public function api_get_committees($member_id)
	{
		// Find in which committees the member is active
		/** @var DataModelMember $member_model */
		$member_model = get_model('DataModelMember');

		$member_committees = $member_model->get_commissies($member_id);

		/** @var DataModelCommissie $committee_model */
		$committee_model = get_model('DataModelCommissie');

		$committees = array();

		foreach ($member_committees as $committee_id)
		{
			$committee = $committee_model->get_iter($committee_id);

			$committees[$committee['login']] = $committee['naam'];
		}

		return array('result' => $committees);
	}

	public function api_secretary_create_member()
	{
		$model = get_model('DataModelMember');

		if (!isset($_POST['id']))
			throw new InvalidArgumentException('Missing id field in POST');

		$data = [
			'id' => $_POST['id']
		];

		foreach (self::$secretary_mapping as $field => $secretary_field)
		{
			if (isset($_POST[$secretary_field]))
				$data[$field] = $_POST[$secretary_field];
		}
		
		$member = new DataIterMember($model, $data['id'], $data);
		$member['privacy'] = 958698063;

		// Create profile for this member
		$nick = member_full_name($member, IGNORE_PRIVACY);
		
		if (strlen($nick) > 50)
			$nick = $member['voornaam'];
		
		if (strlen($nick) > 50)
			$nick = '';
		
		$member['nick'] = $nick;
		
		$model->insert($member);

		// Only email new members (not historical updates) about their new password 
		if ($member->is_member()) {
			// Create a password
			$token = get_model('DataModelPasswordResetToken')->create_token_for_member($member);
			
			// Setup e-mail
			$data['password_link'] = $token['link'];
			$mail = implode("\n\n", [
					'(For English version see below)',
					parse_email('nieuwlid_nl.txt', $data),
					'------------------',
					parse_email('nieuwlid_en.txt', $data)]);

			mail($data['email'], 'Website Cover', $mail,
				implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));

			mail('administratie@svcover.nl', 'Website Cover (' . member_full_name($member, IGNORE_PRIVACY) . ')', $mail,
				implode("\r\n", ['From: Cover <board@svcover.nl>', 'Content-Type: text/plain; charset=UTF-8']));
		}

		return ['success' => true, 'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $member->get_absolute_url()];
	}

	public function api_secretary_read_member($member_id)
	{
		$model = get_model('DataModelMember');

		$member = $model->get_iter($member_id);

		$data = [];

		foreach (self::$secretary_mapping as $prop => $field)
			$data[$field] = $member[$prop];

		return ['success' => true, 'data' => $data];
	}

	public function api_secretary_update_member($member_id)
	{
		if ($member_id != $_POST['id'])
			throw new InvalidArgumentException('Person ids in GET and POST do not match up');

		$model = get_model('DataModelMember');

		$member = $model->get_iter($member_id);

		$reverse_mapping = array_flip(self::$secretary_mapping);

		foreach ($_POST as $remote_field => $value)
		{
			if (!isset($reverse_mapping[$remote_field]))
				continue;

			$field = $reverse_mapping[$remote_field];
			$member[$field] = $value;
		}

		$model->update($member);

		return ['success' => true, 'url' => ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . $member->get_absolute_url()];
	}

	public function api_secretary_delete_member($member_id)
	{
		if ($member_id != $_POST['id'])
			throw new InvalidArgumentException('Person ids in GET and POST do not match up');

		$model = get_model('DataModelMember');

		$member = $model->get_iter($member_id);

		return ['success' => $model->delete($member)];
	}

	public function api_secretary_subscribe_member_to_mailinglist($member_id, $mailinglist)
	{
		$member_model = get_model('DataModelMember');
		$member = $member_model->get_iter($member_id);

		$mailing_model = get_model('DataModelMailinglist');
		$mailinglist = $mailing_model->get_iter_by_address($mailinglist);

		$subscription_model = get_model('DataModelMailinglistSubscription');

		$subscription_model->subscribe_member($mailinglist, $member);

		return ['success' => true];
	}

	private function assert_auth_api_application()
	{
		$model = get_model('DataModelApplication');
		
		if (empty($_SERVER['HTTP_X_APP']))
			throw new Exception('App name is missing');

		$app = $model->find_one(sprintf("key = '%s'", get_db()->escape_string($_SERVER['HTTP_X_APP'])));

		if (!$app)
			throw new Exception('No app with that name available');

		$raw_post_data = file_get_contents('php://input');
		$post_hash = sha1($raw_post_data . $app['secret']);

		if (empty($_SERVER['HTTP_X_HASH']) || $post_hash != $_SERVER['HTTP_X_HASH'])
			throw new Exception('Checksum does not match');

		return true;
	}

	public function run_impl()
	{
		$method = isset($_GET['method'])
			? $_GET['method']
			: 'main';

		if (preg_match('/^secretary_/i', $method))
			$this->assert_auth_api_application();

		switch ($method)
		{
			// GET api.php?method=agenda[&committee[]={committee}]
			case 'agenda':
				$response = $this->api_agenda(isset($_GET['committee']) ? $_GET['committee'] : null);
				break;

			case 'get_agendapunt':
				$response = $this->api_get_agendapunt();
				break;

			// POST api.php?method=session_create
			case 'session_create':
				$response = $this->api_session_create($_POST['email'], $_POST['password'],
					isset($_POST['application']) ? $_POST['application'] : 'api');
				break;

			// POST api.php?method=session_destroy
			case 'session_destroy':
				$response = $this->api_session_destroy($_POST['session_id']);
				break;

			// GET api.php?method=session_get_member&session_id={session}
			case 'session_get_member':
				// For legacy reasons a post session id is still accepted but this method should be accessed using a GET request.
				$response = $this->api_session_get_member(empty($_POST['session_id']) ? $_GET['session_id'] : $_POST['session_id']);
				break;

			// GET api.php?method=session_test_committee&session_id={session}&committee=webcie
			case 'session_test_committee':
				// Again, legacy reasons.
				$response = $this->api_session_test_committee(
					empty($_POST['session_id']) ? $_GET['session_id'] : $_POST['session_id'],
					empty($_POST['committee']) ? $_GET['committee'] : $_POST['committee']);
				break;

			// GET api.php?method=get_member&member_id=709<&session_id=$session_id>
			case 'get_member':
				$response = $this->api_get_member($_GET['member_id']);
				break;

			// GET api.php?method=get_committees&member_id=709
			case 'get_committees':
				$response = $this->api_get_committees($_GET['member_id']);
				break;

			// POST api.php?method=secretary_read_member
			// Submit POST member_id=709
			case 'secretary_read_member':
				$response = $this->api_secretary_read_member($_POST['member_id']);
				break;

			// POST api.php?method=secretary_create_member
			case 'secretary_create_member':
				$response = $this->api_secretary_create_member();
				break;

			// POST api.php?method=secretary_update_member&member_id=709
			// Note that $_POST['id'] must also match $_GET['member_id']
			case 'secretary_update_member':
				$response = $this->api_secretary_update_member($_GET['member_id']);
				break;

			// POST api.php?method=secretary_delete_member&member_id=709
			// Note that $_POST['id'] must also match $_GET['member_id']
			case 'secretary_delete_member':
				$response = $this->api_secretary_delete_member($_GET['member_id']);
				break;

			// POST api.php?method=secretary_subscribe_member_to_mailinglist
			// Do post member_id and mailinglist, which may be an email address or an id
			case 'secretary_subscribe_member_to_mailinglist':
				$response = $this->api_secretary_subscribe_member_to_mailinglist($_POST['member_id'], $_POST['mailinglist']);
				break;

			default:
				throw new InvalidArgumentException("Unknown method \"$method\".");
				break;
		}

		header('Content-Type: application/json');
		echo json_encode($response);
	}

	protected function run_exception($e)
	{
		if (!($e instanceof InvalidArgumentException) && !($e instanceof UnauthorizedException))
			sentry_report_exception($e);
		
		header('Content-Type: application/json');
		echo json_encode(array('success' => false, 'error' => $e->getMessage()));
	}
}

$controller = new ControllerApi();
$controller->run();

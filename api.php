<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';

class ControllerApi extends Controller
{
	public function __construct()
	{
		// Do nothing.
	}

	public function api_agenda()
	{
		$agenda = get_model('DataModelAgenda');

		$activities = array();

		// TODO logged_in() incidentally works because the session is read from $_GET[session_id] by
		// the session provider. But the current session should be set more explicit.
		foreach ($agenda->get_agendapunten(logged_in()) as $activity)
			$activities[] = $activity->data;

		return $activities;
	}

	public function api_get_agendapunt()
	{
		$agenda = get_model('DataModelAgenda');

		if (empty($_GET['id']))
			throw new InvaliArgumentException('Missing id parameter');

		$agendapunt = $agenda->get_iter($_GET['id']);

		// TODO this incidentally works because the session is read from $_GET[session_id] by
		// the session provider. But the current session should be set more explicit.
		if (!get_policy('DataModelAgenda')->user_can_read($agendapunt))
			throw new UnauthorizedException('You are not authorized to read this event');

		return ['result' => $agendapunt->data];
	}

	public function api_session_create($email, $password, $application)
	{
		$user_model = get_model('DataModelMember');

		if (!$member = $user_model->login($email, $password))
			throw new RuntimeException('Invalid username or password');

		$session_model = get_model('DataModelSession');

		$session = $session_model->create($member->get_id(), $application);

		return ['result' => [
			'session_id' => $session->get('session_id'),
			'details' => $member->data
		]];
	}

	public function api_session_destroy($session_id)
	{
		$session_model = get_model('DataModelSession');

		$session = $session_model->resume($session_id);

		return $session_model->delete($session);
	}

	public function api_session_get_member($session_id)
	{
		// Get the session
		$session_model = get_model('DataModelSession');

		$session = $session_model->resume($session_id);

		if (!$session)
			throw new RuntimeException('Invalid session id');
		
		$user_model = get_model('DataModelMember');

		$member = $user_model->get_iter($session->get('member_id'));

		return array('result' => $member->data);
	}

	public function api_session_test_committee($session_id, $committees)
	{
		if (!is_array($committees))
			$comittees = array($committees);

		// Get the session
		$session_model = get_model('DataModelSession');

		$session = $session_model->get_iter($session_id);

		// Find in which committees the member is active
		$member_model = get_model('DataModelMember');

		$member_committees = $member_model->get_commissies($session->get('member_id'));

		if (empty($member_committees))
			return array('result' => false, 'error' => 'No committees found for this member');

		$committee_model = get_model('DataModelCommissie');

		foreach ($committees as $committee_name)
		{
			// Find the committee id
			$committee = $committee_model->get_from_name($committee_name);

			if (!$committee)
				return array('result' => false, 'error' => 'Committee "' . $committee_name . '" not found');

			// And finally, test whether the searched for committee and the member is committees intersect
			if (in_array($committee->get('id'), $member_committees))
				return array('result' => true, 'committee' => $committee->get('naam'));
		}

		return array('result' => false);
	}

	public function api_get_member($member_id)
	{
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

		return array('result' => $member->data);
	}

	public function api_get_committees($member_id)
	{
		// Find in which committees the member is active
		$member_model = get_model('DataModelMember');

		$member_committees = $member_model->get_commissies($member_id);

		$committee_model = get_model('DataModelCommissie');

		$committees = array();

		foreach ($member_committees as $committee_id)
		{
			$committee = $committee_model->get_iter($committee_id);

			$committees[$committee->get('login')] = $committee->get('naam');
		}

		return array('result' => $committees);
	}

	public function run_impl()
	{
		$method = isset($_GET['method'])
			? $_GET['method']
			: 'main';

		switch ($method)
		{
			// GET api.php?method=agenda
			case 'agenda':
				$response = $this->api_agenda();
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

			default:
				throw new InvalidArgumentException('unknown method "' . $method . '"');
				break;
		}

		header('Content-Type: application/json');
		echo json_encode($response);
	}

	public function run_exception(Exception $e)
	{
		header('Content-Type: application/json');
		echo json_encode(array('error' => $e->getMessage()));
	}
}

$controller = new ControllerApi();
$controller->run();

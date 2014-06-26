<?php

require_once 'include/init.php';
require_once 'controllers/Controller.php';

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

		foreach ($agenda->get_agendapunten(logged_in()) as $activity)
			$activities[] = array(
				'id' => $activity->get_id(),
				'vandatum' => $activity->get('vandatum'),
				'vanmaand' => $activity->get('vanmaand'),
				'kop' => $activity->get('kop'));

		return $activities;
	}

	public function api_session_create($email, $password, $application)
	{
		$user_model = get_model('DataModelMember');

		$member = $user_model->login($email, md5($password));

		if (!$member)
			return array('result' => false, 'error' => 'User not found');

		$session_model = get_model('DataModelSession');

		$session = $session_model->create($member->get('id'), $application);

		$member_data = $user_model->get_iter($member->get('id'));

		return array('result' => array('session_id' => $session->get('session_id'), 'details' => $member_data->data));
	}

	public function api_session_destroy($session_id)
	{
		$session_model = get_model('DataModelSession');

		return $session_model->destroy($session_id);
	}

	public function api_session_get_member($session_id)
	{
		// Get the session
		$session_model = get_model('DataModelSession');

		$session = $session_model->get_iter($session_id);

		if (!$session)
			return array('result' => false, 'error' => 'Session not found');

		$user_model = get_model('DataModelMember');

		$member = $user_model->get_iter($session->get('member_id'));

		if (!$member)
			return array('result' => false, 'error' => 'Member not found');

		return array('result' => $member->data);
	}

	public function api_session_test_committee($session_id, $committee)
	{
		// Get the session
		$session_model = get_model('DataModelSession');

		$session = $session_model->get_iter($session_id);

		if (!$session)
			return array('result' => false, 'error' => 'Session not found');

		// Find the committee id
		$committee_model = get_model('DataModelCommissie');

		$committee = $committee_model->get_from_name($committee);

		if (!$committee)
			return array('result' => false, 'error' => 'Committee not found');

		// Find in which committees the member is active
		$member_model = get_model('DataModelMember');

		$member_committees = $member_model->get_commissies($session->get('member_id'));

		if (empty($member_committees))
			return array('result' => false, 'error' => 'No committees found for this member');

		// And finally, test whether the searched for committee and the member is committees intersect
		$member_in_committee = in_array($committee->get('id'), $member_committees);

		return array('result' => $member_in_committee);
	}

	public function api_get_member($member_id)
	{
		$user_model = get_model('DataModelMember');

		$member = $user_model->get_iter($member_id);

		if (!$member)
			return array('result' => false, 'error' => 'Member not found');

		foreach ($member->data as $field => $value)
			if ($user_model->is_private($member, $field, true))
				$member->data[$field] = null;

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
			$committees[$committee_id] = $committee_model->get_naam($committee_id);

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
				$response = array('error' => 'unknown method "' . $method . '"');
				break;
		}

		header('Content-Type: application/json');
		echo json_encode($response);
	}
}

$controller = new ControllerApi();
$controller->run();

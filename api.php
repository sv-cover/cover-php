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

		foreach ($agenda->get_agendapunten(true) as $activity)
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

	public function session_get_member($session_id)
	{
		// Get the session
		$session_model = get_model('DataModelSession');

		$session = $session_model->get_iter($session_id);

		if (!$session)
			return array('result' => false, 'error' => 'Session not found');

		$user_model = get_model('DataModelMember');

		$member = $user_model->get($session->get('member_id'));

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

	public function run_impl()
	{
		$method = isset($_GET['method'])
			? $_GET['method']
			: 'main';

		switch ($method)
		{
			case 'agenda':
				$response = $this->api_agenda();
				break;

			case 'session_create':
				$response = $this->api_session_create($_POST['email'], $_POST['password'],
					isset($_POST['application']) ? $_POST['application'] : 'api');
				break;

			case 'session_destroy':
				$response = $this->api_session_destroy($_POST['session_id']);
				break;

			case 'session_get_member':
				$response = $this->api_session_get_member($_POST['session_id']);
				break;

			case 'session_test_committee':
				$response = $this->api_session_test_committee($_POST['session_id'], $_POST['committee']);
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
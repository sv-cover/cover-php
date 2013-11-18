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
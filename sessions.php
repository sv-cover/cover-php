<?php

require_once 'include/init.php';
require_once 'controllers/Controller.php';

class ControllerSessions extends Controller
{
	public function ControllerSessions()
	{
		$this->model = get_model('DataModelSession');
	}

	function get_content($view, $iter = null, $params = null) {
		$this->run_header();
		run_view('sessions::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	function run_impl()
	{
		$member = logged_in();

		if (!$member)
		{
			$this->get_content('auth_common');
			exit;
		}

		if (isset($_POST['sessions']))
		{
			foreach ($_POST['sessions'] as $session_id)
			{
				$session = $this->model->get_iter($session_id);

				if ($session && $session->get('member_id') == $member['id'])
					$this->model->destroy($session_id);
			}

			header('Location: sessions.php');
			exit;
		}

		$sessions = $this->model->getActive($member['id']);

		$this->get_content('sessions', $sessions);
	}
}

$controller = new ControllerSessions();
$controller->run();
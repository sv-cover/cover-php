<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/Controller.php';

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

	protected function run_view_overrides()
	{
		if (!(get_identity() instanceof ImpersonatingIdentityProvider))
			throw new UnauthorizedException();

		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if (isset($_POST['override_member']) && !empty($_POST['override_member_id']))
			{
				$member_model = get_model('DataModelMember');
				$override_member = $member_model->get_iter($_POST['override_member_id']);
				get_identity()->override_member($override_member);
			}
			else
				get_identity()->reset_member();

			if (isset($_POST['override_committees']))
				get_identity()->override_committees(isset($_POST['override_committee_ids']) ? $_POST['override_committee_ids'] : []);
			else
				get_identity()->reset_committees();

			return $this->redirect('sessions.php?view=overrides');
		}

		return $this->get_content('overrides');
	}

	protected function run_view_sessions()
	{
		if (isset($_POST['sessions']))
		{
			$member = get_identity()->get_member();

			foreach ($_POST['sessions'] as $session_id)
			{
				$session = $this->model->get_iter($session_id);

				if ($session && $session->get('member_id') == $member->get_id())
					$this->model->delete($session);
			}
		}

		return $this->redirect(isset($_POST['referer']) ? $_POST['referer'] : 'sessions.php');
	}

	function run_impl()
	{
		if (!get_auth()->logged_in())
			return $this->get_content('auth_common');
		
		if (isset($_GET['view']) && $_GET['view'] == 'overrides')
			return $this->run_view_overrides();

		if (isset($_GET['view']) && $_GET['view'] == 'sessions')
			return $this->run_view_sessions();

		$member = get_identity()->get_member();

		$session = get_auth()->get_session();

		$sessions = $this->model->getActive($member->get_id());

		$this->get_content('sessions', $sessions, compact('session', 'member'));
	}
}

$controller = new ControllerSessions();
$controller->run();

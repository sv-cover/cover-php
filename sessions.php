<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/Controller.php';

class ControllerSessions extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelSession');

		$this->view = View::byName('sessions', $this);
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

			return isset($_POST['referrer'])
				? $this->view->redirect($_POST['referrer'])
				: $this->view->redirect('sessions.php?view=overrides');
		}

		return $this->view->render_overrides();
	}

	protected function run_view_sessions()
	{
		if (!get_auth()->logged_in())
			throw new UnauthorizedException('You need to login to manage your sessions');

		if (isset($_POST['sessions']))
		{
			$member = get_identity()->member();

			foreach ($_POST['sessions'] as $session_id)
			{
				$session = $this->model->get_iter($session_id);

				if ($session && $session->get('member_id') == $member->get_id())
					$this->model->delete($session);
			}

			return $this->view->redirect(isset($_POST['referer']) ? $_POST['referer'] : 'sessions.php');
		}

		$member = get_identity()->member();

		$session = get_auth()->get_session();

		$sessions = $this->model->getActive($member->get_id());

		return $this->view->render_sessions($sessions, $member, $session);
	}

	protected function run_view_login()
	{
		$errors = array();

		$error_message = null;

		if (isset($_POST['referrer']))
			$referrer = $_POST['referrer'];
		elseif (isset($_GET['referrer']))
			$referrer = $_GET['referrer'];
		else
			$referrer = 'profiel.php';

		$referrer_host = parse_url($referrer, PHP_URL_HOST);

		if ($referrer_host && !is_same_domain($referrer_host, $_SERVER['HTTP_HOST'], 3))
			$external_domain = parse_url($referrer, PHP_URL_HOST);
		else
			$external_domain = null;

		if (!empty($_POST['email']) && !empty($_POST['password']))
		{
			if (get_auth()->login($_POST['email'], $_POST['password'], !empty($_POST['remember']), !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null))
			{
				return $this->view->redirect($referrer, false, ALLOW_SUBDOMAINS); // Todo: allow us to redirect to other subdomains
			}
			else {
				$errors = ['email', 'password'];
				$error_message = __('Verkeerde combinatie van e-mailadres en wachtwoord');
			}
		}

		return $this->view->render_login(compact('errors', 'error_message', 'referrer', 'external_domain'));
	}

	protected function run_view_logout()
	{
		if (get_auth()->logged_in())
			get_auth()->logout();

		if (isset($_GET['referrer']))
			return $this->view->redirect($_GET['referrer'], false, ALLOW_SUBDOMAINS);
		else
			return $this->view->render_logout();
	}

	function run_impl()
	{
		switch (isset($_GET['view']) ? $_GET['view'] : null) {
			case 'sessions':
				return $this->run_view_sessions();

			case 'overrides':
				return $this->run_view_overrides();

			case 'login':
				return $this->run_view_login();

			case 'logout':
				return $this->run_view_logout();

			default:
				return get_auth()->logged_in()
					? $this->run_view_sessions()
					: $this->run_view_login();
		}
	}
}

$controller = new ControllerSessions();
$controller->run();

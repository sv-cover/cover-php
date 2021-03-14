<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/Controller.php';

class SessionsController extends \Controller
{
	protected $view_name = 'sessions';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelSession');

		parent::__construct($request, $router);
	}

	protected function run_view_overrides()
	{
		if (!(get_identity() instanceof \ImpersonatingIdentityProvider))
			throw new \UnauthorizedException();

		if (isset($_POST['referrer']))
			$referrer = $_POST['referrer'];
		elseif (isset($_GET['referrer']))
			$referrer = $_GET['referrer'];
		else
			$referrer = 'index.php';

		if ($this->_form_is_submitted('session_overrides'))
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
				get_identity()->override_committees(
					isset($_POST['override_committee_ids'])
						? array_filter(
							$_POST['override_committee_ids'],
							function($id) {
								return $id !== ''; // because 0 is a valid id, namely the board (thanks to the idiot that did that)
							})
						: []
				);
			else
				get_identity()->reset_committees();

			return isset($_POST['referrer'])
				? $this->view->redirect($_POST['referrer'])
				: $this->view->redirect('sessions.php?view=overrides');
		}

		return $this->view->render_overrides($referrer);
	}

	protected function run_view_sessions()
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('You need to login to manage your sessions');

		if ($this->_form_is_submitted('delete_sessions'))
		{
			$member = get_identity()->member();

			$current_session = get_auth()->get_session();

			if (isset($_POST['sessions']) && is_array($_POST['sessions']))
			{
				foreach ($_POST['sessions'] as $session_id)
				{
					try {
						$session = $this->model->get_iter($session_id);

						// Make sure we can only delete our own sessions
						if ($session && $session->get('member_id') == $member->get_id())
							$this->model->delete($session);

						// Extra warning after we end the current session of the user
						if ($session && $session->get_id() === $current_session->get_id())
							$_SESSION['alert'] = __('Your session has been ended. You will need to log in again.');
						
					} catch (\DataIterNotFoundException $e) {
						// To bad Zubat!
					}
				}
			}

			return $this->view->redirect(isset($_POST['referer']) ? $_POST['referer'] : 'sessions.php');
		}

		return $this->view->redirect('profiel.php?lid=' . get_identity()->get('id') . '&view=sessions');
	}

	protected function _resolve_referrer($referrer) {
		while (preg_match('/^\/?sessions.php\?view=login&referrer=(.+?)$/', $referrer, $match))
			$referrer = rawurldecode($match[1]);

		return $referrer;
	}

	protected function run_view_login()
	{
		try {
			$errors = array();

			$error_message = null;

			if (isset($_POST['referrer']))
				$referrer = $_POST['referrer'];
			elseif (isset($_GET['referrer']))
				$referrer = $_GET['referrer'];
			else
				$referrer = null;

			$referrer = $this->_resolve_referrer($referrer);

			$referrer_host = parse_url($referrer, PHP_URL_HOST);

			if ($referrer_host && !is_same_domain($referrer_host, $_SERVER['HTTP_HOST'], 3))
				$external_domain = parse_url($referrer, PHP_URL_HOST);
			else
				$external_domain = null;

			// Prevent returning to the logout link
			if ($external_domain === null && $referrer == '/sessions.php?view=logout')
				$referrer = null;



			if (!empty($_POST['email']) && !empty($_POST['password']))
			{
				if (get_auth()->login($_POST['email'], $_POST['password'], !empty($_POST['remember']), !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null))
				{
					// User can apparently login, so invalidate all their password reset tokens
					try {
						$password_reset_model = get_model('DataModelPasswordResetToken');
						$password_reset_model->invalidate_all(get_identity()->member());
					} catch (\Exception $e) {
						throw $e;
						sentry_report_exception($e);
					}

					return $this->view->redirect($referrer ? $referrer : 'index.php', false, ALLOW_SUBDOMAINS);
				}
				else {
					$errors = ['email', 'password'];
					$error_message = __('Wrong combination of e-mail address and password');
				}
			}

			return $this->view->render_login($errors, $error_message, $referrer, $external_domain);
		} catch (\InactiveMemberException $e) {
			return $this->view->render('inactive.twig');
		}
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
		switch ($this->get_parameter('view')) {
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

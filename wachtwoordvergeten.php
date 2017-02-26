<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerWachtwoordVergeten extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelPasswordResetToken');

		$this->member_model = get_model('DataModelMember');

		$this->view = View::byName('wachtwoordvergeten', $this);
	}

	protected function _is_good_password(DataIterMember $member, $password)
	{
		// Remove easy to guess stuff
		$effective_password = str_ireplace([$member['voornaam'],$member['achternaam'],'cover','password'], '', $password);

		// Short passwords, or very common passwords, are stupid.
		if (strlen($effective_password) < 3)
			return false;

		// Anything else is ok for this website :P
		return true;
	}

	protected function run_reset()
	{
		try {
			$token = $this->model->get_iter($_GET['reset_token']);
		} catch (DataIterNotFoundException $e) {
			return $this->run_request();
		}

		$errors = [];

		$success = null;

		if ($this->_form_is_submitted('reset', $token)) {
			if (!$this->_is_good_password($token['member'], $_POST['password'])) {
				$errors[] = 'password';
			}
			elseif ($_POST['password'] !== $_POST['password_repeat']) {
				$errors[] = 'password_repeat';
			}
			else {
				$this->member_model->set_password($token['member'], $_POST['password']);

				$success = true;

				$this->model->invalidate_all($token['member']);
			}
		}

		return $this->view->render('reset_form.twig', compact('token', 'errors', 'success'));
	}

	protected function run_request()
	{
		$success = null;

		$email_address = null;

		try {
			if ($this->_form_is_submitted('request')) {
				$email_address = $_POST['email'];

				$member = $this->member_model->get_from_email($email_address);

				$token = $this->model->create_token_for_member($member);

				$language_code = in_array($member['taal'], ['en', 'nl']) ? $member['taal'] : 'en';

				$variables = array(
					'naam' => $member['voornaam'],
					'link' => $token['link']
				);

				$email = parse_email_object("password_reset_{$language_code}.txt", $variables);

				$email->send($member['email']);

				$success = true;
			}
		} catch (DataIterNotFoundException $e) {
			$success = false;
		}

		return $this->view->render('request_form.twig', compact('email_address', 'success'));
	}

	protected function run_impl()
	{
		if (isset($_GET['reset_token']))
			return $this->run_reset();
		else
			return $this->run_request();	
	}
}

$controller = new ControllerWachtwoordVergeten();
$controller->run();

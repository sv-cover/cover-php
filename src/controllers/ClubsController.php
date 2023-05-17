<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

class ClubsController extends \Controller 
{
	protected $view_name = 'clubs';

	// Copied from profiel
	public function _check_phone($name, $value)
	{
		try {
			$phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number = $phone_util->parse($value, 'NL');
			return $phone_util->isValidNumber($phone_number)
				? $phone_util->format($phone_number, \libphonenumber\PhoneNumberFormat::E164)
				: false;
		} catch (\libphonenumber\NumberParseException $e) {
			return false;
		}
	}

	// Copied from profiel
	public function _check_email($name, $value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	public function run_propose_club()
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException();

		$fields = [
			['name' => 'email', 'function' => [$this, '_check_email']],
			['name' => 'phone', 'function' => [$this, '_check_phone']],
			'club_name',
			'description',
			'members',
			'motivation',
			'communication_platform',
		];

		$member = get_identity()->member();

		$data = [
			'email' => $member['email'],
			'phone' => $member['telefoonnummer'],
			'club_name' => null,
			'description' => null,
			'members' => null,
			'motivation' => null,
			'communication_platform' => null,
		];

		$errors = [];

		if ($this->_form_is_submitted('club')) {
			$data = check_values($fields, $errors);

			if (count($errors) == 0) {
				$mail = parse_email_object("club_proposal.txt", compact('data', 'member'));
				$mail->send(get_config_value('email_bestuur'));
				$_SESSION['alert'] = __('Club proposal submitted! You should hear from the board soon!');
				return $this->view->redirect($this->generate_url('clubs'));
			}
		}

		return $this->view->render_form($data, $errors);
	}

	public function run_index()
	{
		return $this->view->render_index();
	}

	protected function run_impl()
	{
		$view = isset($_GET['view']) ? $_GET['view'] : 'index';
		return call_user_func([$this, 'run_' . $view]);
	}
}

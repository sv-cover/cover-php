<?php
require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/markup.php';
require_once 'include/controllers/Controller.php';

class DreamsparkController extends Controller
{
	public function __construct()
	{
		$this->view = View::byName('dreamspark', $this);
	}

	protected function redirect_to_dreamspark()
	{
		$member = logged_in_member();

		$request = array(
			'username' => $member->get('id'),
			'account' => get_config_value('elms_id'),
			'key' => get_config_value('elms_secret'),
			'academic_statuses' => 'students',
			'email' => $member->get('email'),
			'first_name' => $member->get('voornaam'),
			'last_name' => (trim($member->get('tussenvoegsel')) ? $member->get('tussenvoegsel') . ' ' : '') . $member->get('achternaam'),
			'shopper_ip' => $_SERVER['REMOTE_ADDR']);

		if (member_in_commissie(COMMISSIE_EASY)
			&& !empty($_POST['log_in_as_webcie']))
			$request['username'] = 'webcie@ai.rug.nl';

		$url = get_config_value('elms_endpoint', 'https://e5.onthehub.com/WebStore/Security/AuthenticateUser.aspx');

		$url .= '?' . http_build_query($request);

		$options = array('http' => array(
			'ignore_errors' => true
		));

		$context = stream_context_create($options);

		$fh = fopen($url, 'r', false, $context);

		$metadata = stream_get_meta_data($fh);

		$response = stream_get_contents($fh);

		fclose($fh);

		return $this->view->redirect($response, False, ALLOW_EXTERNAL_DOMAINS);
	}

	protected function run_impl()
	{
		if (!get_auth()->logged_in())
			return $this->view->redirect('sessions.php?view=login&referrer=dreamspark.php');

		if ($this->_form_is_submitted('accept_dreamspark_terms'))
			if (isset($_POST['accept_terms']) && $_POST['accept_terms'] == 'true')
				return $this->redirect_to_dreamspark();

		return $this->view->render_accept();
	}
}

$controller = new DreamsparkController();
$controller->run();

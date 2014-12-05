<?php
require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/markup.php';
require_once 'include/controllers/Controller.php';

class DreamsparkController extends Controller
{
	public function __construct()
	{
		//
	}

	function get_content($view)
	{
		$this->run_header(array('title' => __('Log in op Dreamspark')));

		run_view($view);
		
		$this->run_footer();
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

		if (!isset($metadata['wrapper_data']) || strpos($metadata['wrapper_data'][0], '200 OK') === false)
			throw new RuntimeException('Handshake error: ' . $response);

		if (!$response)
			throw new RuntimeException('Could not get redirect url from the ELMS endpoint');

		header('Location: ' . $response);
		printf('Redirecting to <a href="%s">%1$s</a>',
			markup_format_text($response));

		return true;
	}

	public function run_impl()
	{
		if (!logged_in())
			return $this->get_content('common::auth');

		if (isset($_POST['accept_terms']) && $_POST['accept_terms'] == 'true')
			$this->redirect_to_dreamspark();

		return $this->get_content('dreamspark::accept');
	}
}

$controller = new DreamsparkController();
$controller->run();

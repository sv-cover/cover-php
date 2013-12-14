<?php
function http_json_request($url, array $data)
{
	$options = array(
	    'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
	    ),
	);

	$context  = stream_context_create($options);

	$response = file_get_contents($url, false, $context);

	if (!$response)
		throw new Exception('No response');

	$data = json_decode($response);

	if ($data === null)
		throw new Exception('Response could not be parsed as JSON');

	return $data;
}

class NewsletterSession
{
	static public function login($email, $password)
	{
		$data = http_json_request(
			link_api('session_create'),
			array(
				'email' => $email,
				'password' => $password
			));

		if (!$data->result)
			throw new Exception('Could not create session: ' . $data->error);

		$_SESSION['cover_session_id'] = $data->result->session_id;
		$_SESSION['cover_session_details'] = $data->result->details;

		$data = http_json_request(
			link_api('session_test_committee'),
			array(
				'session_id' => $_SESSION['cover_session_id'],
				'committee' => 'Bestuur'
			));

		if ($data->result != true)
			throw new Exception('User not part of bestuur');

		$_SESSION['user_authorized'] = true;
		
		return true;
	}

	static public function instance()
	{
		static $instance;
		return $instance ? $instance : $instance = new self;
	}

	private function __construct()
	{
		//
	}

	public function get($temp_id)
	{
		if (isset($_SESSION['newsletter_' . $temp_id]))
			return unserialize($_SESSION['newsletter_' . $temp_id]);
		else
			return false;
	}

	public function set($temp_id, $data)
	{
		$_SESSION['newsletter_' . $temp_id] = serialize($data);
	}

	public function currentUser()
	{
		return new NewsletterSessionUser(
			isset($_SESSION['cover_session_details'])
			? $_SESSION['cover_session_details']
			: array());
	}

	public function sessionId()
	{
		return $_SESSION['cover_session_id'];
	}

	public function loggedIn()
	{
		return isset($_SESSION['user_authorized'])
			&& $_SESSION['user_authorized'] === true;
	}

	public function destroy()
	{
		// Try to kill the session at the API-side as well
		http_json_request(
			link_api('session_destroy'),
			array('session_id' => $this->sessionId()));

		$_SESSION['user_authorized'] = false;

		session_destroy();
	}
}

class NewsletterSessionUser
{
	public function __construct($data)
	{
		foreach ($data as $property => $value)
			$this->$property = $value;
	}

	public function __toString()
	{
		return isset($this->voornaam) ? $this->voornaam : '[unknown]';
	}
}

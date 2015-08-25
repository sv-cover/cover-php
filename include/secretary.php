<?php

class SecretaryAPI
{
	private $root;

	private $token;

	public function __construct($root, $user, $password)
	{
		$this->root = $root;

		$config = get_model('DataModelConfiguratie');
		$this->token = $config->get_value('secretary_token', null);

		if (!$this->isValidToken($this->token)) {
			$this->token = $this->requestToken($user, $password);
			$config->set_value('secretary_token', $this->token);
		}
	}

	public function createPerson($data)
	{
		return $this->postJSONWithToken('persons/new.json', $data);
	}

	public function updatePerson($person_id, $data)
	{
		// todo
	}

	protected function isValidToken($user_token_pair)
	{
		if (strpos($user_token_pair, ':') === false)
			return false;

		list($user, $token) = explode(':', $user_token_pair, 2);

		$response = $this->getJSON(sprintf('token/%s/%d.json', $token, $user));

		return (bool) $response->success;
	}

	protected function requestToken($user, $password)
	{
		$response = $this->postJSON('token/new.json', ['username' => $user, 'password' => $password]);

		if (!$response->success)
			throw new RuntimeException('Could not request new token: ' . $response->errors);

		return sprintf('%d:%s', $response->user, $response->token);
	}

	protected function getJSON($url)
	{
		$response = file_get_contents($this->root . $url);

		if (!$response)
			throw new RuntimeException('Could not get ' . $url);

		$data = json_decode($response);

		if (!$data)
			throw new RuntimeException('Could not decode response as JSON');

		return $data;
	}

	protected function postJSONWithToken($url, array $data)
	{
		list($user, $token) = explode(':', $this->token, 2);

		$url .= sprintf('?user=%d&token=%s', $user, $token);

		return $this->postJSON($url, $data);
	}

	protected function postJSON($url, array $data)
	{
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
				'ignore_errors' => true
			)
		);
		$context  = stream_context_create($options);

		$response = file_get_contents($this->root . $url, false, $context);

		if (!preg_match('/^HTTP\/1\.\d\s(\d+)\s/', $http_response_header[0], $match))
			throw new RuntimeException('Could not get HTTP STATUS response header');

		if ($match[1] != '200')
			throw new RuntimeException('Received HTTP status '. $match[1]);

		if (!$response)
			throw new RuntimeException('Could not do post request to ' . $url);

		$data = json_decode($response);

		if (!$data)
			throw new RuntimeException('Could not decode response as JSON');

		return $data;
	}
}
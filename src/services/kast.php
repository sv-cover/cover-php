<?php

class KastAPI
{
	private $api_root;

	private $api_app_id;

	private $api_app_secret;

	private $token;

	public function __construct($api_root, $user, $password)
	{
		$this->api_root = $api_root;

		$config = get_model('DataModelConfiguratie');
		$this->token = json_decode($config->get_value('kast_token', null));

		if (!$this->isValidToken($this->token)) {
			$this->token = $this->requestToken($user, $password);
			$config->set_value('kast_token', json_encode($this->token));
		}
	}

	public function getAccount($cover_id)
	{
		return $this->getJSON(sprintf('users/%d/', $cover_id));
	}

	public function getStatus($cover_id)
	{
		return $this->getJSON(sprintf('users/%d/status/', $cover_id));
	}

	public function getHistory($cover_id, $limit=10)
	{
		return $this->getJSON(sprintf('users/%d/history/?limit=%d', $cover_id, $limit));
	}

	protected function isValidToken($token)
	{
		return isset($token->expiry) && new \DateTime() < new \DateTime($token->expiry);
	}

	protected function requestToken($user, $password)
	{
		$options = array(
			'http' => array(
				'header'  => "Authorization: Basic " . base64_encode("$user:$password") . "\r\n",
				'method'  => 'POST',
				'ignore_errors' => true
			)
		);
		$context  = stream_context_create($options);

		$response = file_get_contents($this->api_root . 'auth/login/', false, $context);

		if (empty($response))
			throw new RuntimeException('Could not request new token: empty response');

		$data = json_decode($response);

		if (isset($data->detail))
			throw new RuntimeException('Could not request new token: ' . $data->detail);

		return $data;
	}

	protected function getJSON($url)
	{
		try {
			$options = array(
				'http' => array(
					'header'  => "Authorization: Token " . $this->token->token . "\r\n",
					'method'  => 'GET',
					'ignore_errors' => true
				)
			);
			$context  = stream_context_create($options);

			$response = file_get_contents($this->api_root . $url, false, $context);
		} catch (ErrorException $e) {
			throw new RuntimeException('Could not send request to host.', 0, $e);
		}

		if (!preg_match('/^HTTP\/1\.\d\s(\d+)\s/', $http_response_header[0], $match))
			throw new RuntimeException('Could not get HTTP STATUS response header');

		if ($match[1] != '200')
			throw new RuntimeException('Received HTTP status '. $match[1] . ': ' . $response);

		if (!$response)
			throw new RuntimeException('Could not do post request to ' . $url);

		$data = json_decode($response);

		if (!$data)
			throw new RuntimeException('Could not decode response as JSON');
		
		return $data;
	}
}

function get_kast()
{
	static $kast;

	if (!$kast)
		$kast = new KastAPI(
			get_config_value('kast_root'),
			get_config_value('kast_user'),
			get_config_value('kast_password'));

	return $kast;
}
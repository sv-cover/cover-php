<?php

class KastAPI
{
	private $api_root;

	private $api_app_id;

	private $api_app_secret;

	private $context;

	public function __construct($api_root, $app_id, $secret)
	{
		$this->api_root = $api_root;
		$this->api_app_id = $app_id;
		$this->api_app_secret = $secret;

		$this->context = new \HttpSignatures\Context([
			'keys' => [$this->api_app_id => $this->api_app_secret],
			'algorithm' => 'hmac-sha256',
			'headers' => ['(request-target)', 'Host', 'Date', 'X-App'],
		]);
	}

	public function getAccount($cover_id)
	{
		return $this->_request(sprintf('%s/users/%d', $this->api_root, $cover_id));
	}

	public function getStatus($cover_id)
	{
		return $this->_request(sprintf('%s/users/%d/status', $this->api_root, $cover_id));
	}

	public function getHistory($cover_id)
	{
		return $this->_request(sprintf('%s/users/%d/history', $this->api_root, $cover_id));
	}

	protected function _request($url)
	{
		global $http_response_header;
		$headers = array(
			'Date' => gmdate('D, d M Y H:i:s T'),
			'Host' => parse_url($url, PHP_URL_HOST),
			'X-App' => $this->api_app_id
		);

		$message = \Symfony\Component\HttpFoundation\Request::create(parse_url($url, PHP_URL_PATH), 'GET');
		$message->headers->replace($headers);

		$this->context->signer()->sign($message);

		$headers['Authorization'] = $message->headers->get('Authorization');

		$options = array(
			'http' => array(
				'header'  => implode("", array_map(
					function($key, $value) {
						return sprintf("%s: %s\r\n", $key, $value);
					},
					array_keys($headers),
					array_values($headers))),
				'method'  => 'GET',
				'ignore_errors' => true
			)
		);
		$context  = stream_context_create($options);

		$response = file_get_contents($url, false, $context);

		if (!preg_match('/^HTTP\/1\.\d\s(\d+)\s/', $http_response_header[0], $match))
			throw new RuntimeException('Could not get HTTP STATUS response header');

		if ($match[1] != '200')
			throw new RuntimeException('Received HTTP status '. $match[1] . ': ' . $response);

		if (!$response)
			throw new RuntimeException('Could not do post request to ' . $url);

		$data = json_decode($response);

		if ($data === null)
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
			get_config_value('kast_app'),
			get_config_value('kast_secret'));

	return $kast;
}
<?php
namespace incassomatic;

class API
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
	}

	public function getIncassos(\DataIterMember $member, $limit = null)
	{
		$data = [
			'cover_id' => $member->get_id(),
			'email' => $member['email']
		];

		if ($limit !== null)
			$data['limit'] = (int) $limit;

		return $this->_get($this->api_root, $data);
	}

	public function getContracts(\DataIterMember $member)
	{
		return $this->_get($this->api_root . 'contracten/', ['cover_id' => $member->get_id()]);
	}

	protected function _get($url, array $data)
	{
		global $http_response_header;

		$query = http_build_query($data);

		$headers = array(
			'Date' => gmdate('D, d M Y H:i:s T'),
			'Host' => parse_url($url, PHP_URL_HOST),
			'X-App' => $this->api_app_id,
			'X-Hash' => sha1($query . $this->api_app_secret)
		);

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

		$context  = \stream_context_create($options);

		$response = \file_get_contents($url . '?' . $query, false, $context);

		if (!preg_match('/^HTTP\/1\.\d\s(\d+)\s/', $http_response_header[0], $match))
			throw new \RuntimeException('Could not get HTTP STATUS response header');

		if ($match[1] != '200')
			throw new \RuntimeException('Received HTTP status '. $match[1] . ': ' . $response);

		if (!$response)
			throw new \RuntimeException('Could not do post request to ' . $url);

		$data = json_decode($response);

		if ($data === null)
			throw new \RuntimeException('Could not decode response as JSON: ' . $response);

		return $data;
	}
}

function shared_instance()
{
	static $incassomatic;

	if (!$incassomatic)
		$incassomatic = new API(
			get_config_value('incassomatic_root'),
			get_config_value('incassomatic_app'),
			get_config_value('incassomatic_secret'));

	return $incassomatic;
}
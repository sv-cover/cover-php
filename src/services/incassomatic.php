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

	public function getDebits(\DataIterMember $member, $limit = null)
	{
		$data = [
			'cover_id' => $member->get_id()
		];

		if ($limit !== null)
			$data['limit'] = (int) $limit;

		$debits = $this->_get($this->api_root, $data);

		return $debits;
	}

	public function getContracts(\DataIterMember $member)
	{
		return $this->_get($this->api_root . 'contracten/', ['cover_id' => $member->get_id()]);
	}

	public function getCurrentContract(\DataIterMember $member)
	{
		$contracts = $this->getContracts($member);

		// Only show valid contracts
		return current(array_filter($contracts, function($contract) { return $contract->is_geldig; }));
	}

	public function getContractTemplatePDF(\DataIterMember $member)
	{
		return $this->_stream(sprintf('%scontracten/templates/%d', $this->api_root, $member['id']));
	}

	public function createContract(\DataIterMember $member)
	{
		$data = [
			'cover_id' => $member->get_id(),
			'start_datum' => date('Y-m-d'),
			'method' => 'digital',
		];
		return $this->_post($this->api_root . 'contracten/', [], $data);
	}

	protected function _createRequest($method, $url, array $params, array $data = [])
	{
		$query = http_build_query($params);
		$body = http_build_query($data);

		$headers = array(
			'Content-type' => 'application/x-www-form-urlencoded',
			'Date' => gmdate('D, d M Y H:i:s T'),
			'Host' => parse_url($url, PHP_URL_HOST),
			'X-App' => $this->api_app_id,
			'X-Hash' => sha1($query . $body . $this->api_app_secret),
		);

		$options = array(
			'http' => array(
				'content' => $body,
				'header'  => implode("", array_map(
					function($key, $value) {
						return sprintf("%s: %s\r\n", $key, $value);
					},
					array_keys($headers),
					array_values($headers))),
				'method'  => $method,
				'ignore_errors' => true
			)
		);

		$context  = \stream_context_create($options);

		return (object) [
			'url' => $url . ($query != '' ? ('?' . $query) : ''),
			'context' => $context
		];
	}

	protected function _request($method, $url, array $params = [], array $data = [])
	{
		$request = $this->_createRequest($method, $url, $params, $data);

		$response = \file_get_contents($request->url, false, $request->context);

		if (!preg_match('/^HTTP\/1\.\d\s(\d+)\s/', $http_response_header[0], $match))
			throw new \RuntimeException('Could not get HTTP STATUS response header');

		if ($match[1] != '200')
			throw new \RuntimeException('Received HTTP status '. $match[1] . ': ' . $response);

		if (!$response)
			throw new \RuntimeException('Could not do post request to ' . $url);

		$responseData = json_decode($response);

		if ($responseData === null)
			throw new \RuntimeException('Could not decode response as JSON: ' . $response);

		return $responseData;
	}

	protected function _get($url, array $params = [])
	{
		return $this->_request('GET', $url, $params);
	}

	protected function _post($url, array $params = [], array $data = [])
	{
		return $this->_request('POST', $url, $params, $data);
	}

	protected function _stream($url, array $params = [])
	{
		$request = $this->_createRequest('GET', $url, $params);
		return fopen($request->url, 'rb', false, $request->context);
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
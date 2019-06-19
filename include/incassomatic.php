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

		$debits = $this->_request($this->api_root, $data);

		return $debits;
	}

	public function getContracts(\DataIterMember $member)
	{
		return $this->_request($this->api_root . 'contracten/', ['cover_id' => $member->get_id()]);
	}

	public function getContractTemplatePDF(\DataIterMember $member)
	{
		return $this->_stream(sprintf('%scontracten/templates/%d', $this->api_root, $member['id']));
	}

	public function printContractTemplatePDF(\DataIterMember $member)
	{
		return $this->_request(
			sprintf('%scontracten/templates/%d', $this->api_root, $member['id']),
			[],
			'POST',
			['X-Document-Destination' => 'printer/cache']
		);
	}

	protected function _getRequest($url, array $data, $method='GET', array $headers=[])
	{
		$query = http_build_query($data);

		$headers = array_merge(
			$headers,
			[
				'Date' => gmdate('D, d M Y H:i:s T'),
				'Host' => parse_url($url, PHP_URL_HOST),
				'X-App' => $this->api_app_id,
				'X-Hash' => sha1($query . $this->api_app_secret)
			]
		);

		$options = array(
			'http' => array(
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

	protected function _request($url, array $data=[], $method='GET', array $headers=[])
	{
		$request = $this->_getRequest($url, $data, $method, $headers);

		$response = \file_get_contents($request->url, false, $request->context);

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

	protected function _stream($url, array $data=[])
	{
		$request = $this->_getRequest($url, $data);
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
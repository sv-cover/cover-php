<?php

class SecretaryAPI
{
	private $root;

	private $token;

	private $mapping = [
		'id' => 'id',
		'voornaam' => 'first_name',
		'tussenvoegsel' => 'family_name_preposition',
		'achternaam' => 'family_name',
		'adres' => 'street_name',
		'postcode' => 'postal_code',
		'woonplaats' => 'place',
		'email' => 'email_address',
		'telefoonnummer' => 'phone_number',
		'beginjaar' => 'membership_year_of_enrollment',
		'geboortedatum' => 'birth_date',
		'geslacht' => 'gender'
	];

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
		return $this->postJSONWithToken(sprintf('persons/%d.json', $person_id), $data);
	}

	public function findPerson($person_id)
	{
		return $this->getJSONWithToken('persons/all.json?id=' . $person_id);
	}

	public function updatePersonFromIterChanges(DataIterMember $iter)
	{
		if (!$iter->has_id())
			throw new InvalidArgumentException('You can only submit updates for iters that have an id');

		if (!$iter->has_changes())
			return null;

		$data = [];
		
		foreach ($iter->changed_values() as $field => $value)
			$data[$this->mapping[$field]] = $value;

		return $this->updatePerson($iter->get_id(), $data);
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
		
		$data = json_decode($response);
		
		return $data;
	}

	protected function getJSONWithToken($url)
	{
		// Splice in the token authentication
		list($user, $token) = explode(':', $this->token, 2);

		// Add token to URL
		$url = edit_url($url, ['user' => $user, 'token' => $token]);

		// Request that JSON
		return $this->getJSON($url);
	}

	protected function postJSONWithToken($url, array $data)
	{
		list($user, $token) = explode(':', $this->token, 2);

		$url .= sprintf('?user=%d&token=%s', $user, $token);

		return $this->postJSON($url, $data);
	}

	protected function postJSON($url, array $data)
	{
		try {
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

function get_secretary()
{
	static $secretary;

	if (!$secretary)
		$secretary = new SecretaryApi(
			get_config_value('secretary_root'),
			get_config_value('secretary_user'),
			get_config_value('secretary_password'));

	return $secretary;
}

<?php

require_once 'include/init.php';


class ApiTest extends PHPUnit_Framework_TestCase
{
	static protected $member_id;

	static protected $member_email;

	static protected $member_password;

	public static function setUpBeforeClass()
	{
		// Set up account
		$model = get_model('DataModelMember');

		self::$member_id = 10000000 + time() % 1000000;

		self::$member_email = sprintf('user%d@example.com', self::$member_id);

		self::$member_password = implode('', array_map(function($n) {
			return chr(mt_rand(ord('a'), ord('z')));
		}, range(1, 20)));

		$member = new DataIterMember($model, self::$member_id, [
			'id' => self::$member_id,
			'voornaam' => 'Unit',
			'achternaam' => 'Test',
			'adres' => 'foo',
			'postcode' => '1111AA',
			'woonplaats' => 'foo',
			'email' => self::$member_email,
			'geboortedatum' => '1988-01-01',
			'geslacht' => 'm',
			'privacy' => 958698063,
			'type' => MEMBER_STATUS_LID
		]);

		$model->insert($member);

		$profiel = new DataIter($model, self::$member_id, ['lidid' => self::$member_id, 'nick' => 'unittest']);

		$model->insert_profiel($profiel);

		$model->set_password($member, self::$member_password);
	}

	public static function tearDownAfterClass()
	{
		// Delete account
		$model = get_model('DataModelMember');

		$member = $model->get_iter(self::$member_id);

		$model->delete($member);
	}

	public function testSessionCreate()
	{
		$response = $this->simulateRequest([
			'GET' => ['method' => 'session_create'],
			'POST' => [
				'email' => self::$member_email,
				'password' => self::$member_password,
				'application' => 'unittest'
			]
		]);

		$this->assertArrayHasKey('result', $response);

		$this->assertArrayHasKey('session_id', $response['result']);
		$this->assertArrayHasKey('details', $response['result']);

		$this->assertEquals($response['result']['details']['id'], self::$member_id);

		return $response['result']['session_id'];
	}

	public function testSessionCreateLoginFailure()
	{
		$response = $this->simulateRequest([
			'GET' => ['method' => 'session_create'],
			'POST' => [
				'email' => self::$member_email,
				'password' => self::$member_password . 'x', // send invalid password
				'application' => 'unittest'
			]
		]);

		$this->assertArrayHasKey('error', $response);
		$this->assertEquals('Invalid username or password', $response['error']);
	}

	/**
	 * @depends testSessionCreate
	 */
	public function testSessionDestroy()
	{
		// Create a specific session for this one
		$session_id = $this->testSessionCreate();

		$response = $this->simulateRequest([
			'GET' => ['method' => 'session_destroy'],
			'POST' => ['session_id' => $session_id]
		]);

		$this->assertEquals(true, $response);
	}

	/**
	 * @depends testSessionCreate
	 */
	public function testSessionGetMember($session_id)
	{
		$response = $this->simulateRequest([
			'GET' => [
				'method' => 'session_get_member',
				'session_id' => $session_id
			]
		]);

		// Expect the returned user to be the test user we made for this test case
		$this->assertArraySubset(['result' => ['id' => self::$member_id]], $response);

		// Expect all the data to be there, but the password hash to be absent
		$this->assertArrayNotHasKey('wachtwoord', $response['result']);
	}

	public function testSessionGetMemberNoSession()
	{
		$response = $this->simulateRequest([
			'GET' => [
				'method' => 'session_get_member',
				'session_id' => 'invalid'
			]
		]);

		$this->assertArraySubset(['error' => 'Invalid session id'], $response);
	}

	public function testAgendaNotLoggedIn()
	{
		$response = $this->simulateRequest(['GET' => ['method' => 'agenda']]);

		foreach ($response as $agendapunt)
		{
			// These are the properties Newsletter.svcover.nl expects
			$this->assertArrayHasKey('id', $agendapunt);
			$this->assertArrayHasKey('kop', $agendapunt);
			$this->assertArrayHasKey('vandatum', $agendapunt);
			$this->assertArrayHasKey('vanmaand', $agendapunt);
		}
	}

	public function testAgendapuntPublic()
	{
		$response = $this->simulateRequest([
			'GET' => [
				'method' => 'get_agendapunt',
				'id' => '2260'
			]
		]);

		$this->assertArraySubset(['result' => ['id' => 2260]], $response);
	}

	public function testAgendapuntPrivate()
	{
		$response = $this->simulateRequest([
			'GET' => [
				'method' => 'get_agendapunt',
				'id' => '2261'
			]
		]);

		$this->assertArraySubset(['error' => 'You are not authorized to read this event'], $response);
	}

	/**
	 * @depends testSessionCreate
	 */
	public function testAgendapuntPrivateLoggedIn($session_id)
	{
		$response = $this->simulateRequest([
			'GET' => [
				'method' => 'get_agendapunt',
				'session_id' => $session_id,
				'id' => '2261'
			]
		]);

		$this->assertArraySubset(['result' => ['id' => 2261]], $response);
	}

	private function simulateRequest($params)
	{
		$env = [
			'REQUEST_URI' => '/api.php',
			'SCRIPT_FILENAME' => realpath(__DIR__ . '/../api.php'),
			'REDIRECT_STATUS' => 'true',
			'REQUEST_METHOD' => 'GET',
			'GATEWAY_INTERFACE' => 'CGI/1.1'
		];

		$post_data = null;

		if (isset($params['POST'])) {
			$post_data = http_build_query($params['POST']);
			$env['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
			$env['CONTENT_LENGTH'] = strlen($post_data);
			$env['REQUEST_METHOD'] = 'POST';
		}

		if (isset($params['GET'])) {
			$env['QUERY_STRING'] = http_build_query($params['GET']);
		}

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['file', 'php://stderr', 'a']
		];

		$proc = proc_open('php-cgi -d always_populate_raw_post_data=-1 api.php', $descriptors, $pipes, getcwd(), $env);

		if (!is_resource($proc))
			throw new RuntimeException('Could not start CGI process');

		if ($post_data !== null)
			fwrite($pipes[0], $post_data);

		// Close STDIN
		fclose($pipes[0]);

		// Read STDOUT
		$response = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		list($headers, $data) = explode("\r\n\r\n", $response, 2);

		// echo "\n>>>\n$data\n<<<\n";
		
		$exit_code = proc_close($proc);

		$json = json_decode($data, true);

		$this->assertNotNull($json);

		return $json;
	}
}
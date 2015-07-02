<?php

require_once 'include/init.php';


class ApiTest extends PHPUnit_Framework_TestCase
{
	public function testApiAgendaNotLoggedIn()
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

		$proc = proc_open('php-cgi api.php', $descriptors, $pipes, getcwd(), $env);

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
		
		$exit_code = proc_close($proc);

		$json = json_decode($data, true);

		$this->assertInternalType('array', $json);

		return $json;
	}
}
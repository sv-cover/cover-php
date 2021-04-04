<?php

require_once 'src/framework/init.php';
require_once 'src/framework/test.php';

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
	use \cover\test\MemberTestTrait;

	public function testSessionCreate()
	{
		$response = \cover\test\simulate_json_request('api.php', [
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
		$response = \cover\test\simulate_json_request('api.php', [
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

		$response = \cover\test\simulate_json_request('api.php', [
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
		$response = \cover\test\simulate_json_request('api.php', [
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
		$response = \cover\test\simulate_json_request('api.php', [
			'GET' => [
				'method' => 'session_get_member',
				'session_id' => 'invalid'
			]
		]);

		$this->assertArraySubset(['error' => 'Invalid session id'], $response);
	}

	public function testAgendaNotLoggedIn()
	{
		$response = \cover\test\simulate_json_request('api.php', ['GET' => ['method' => 'agenda']]);

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
		$response = \cover\test\simulate_json_request('api.php', [
			'GET' => [
				'method' => 'get_agendapunt',
				'id' => '2260'
			]
		]);

		$this->assertArraySubset(['result' => ['id' => 2260]], $response);
	}

	public function testAgendapuntPrivate()
	{
		$response = \cover\test\simulate_json_request('api.php', [
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
		$response = \cover\test\simulate_json_request('api.php', [
			'GET' => [
				'method' => 'get_agendapunt',
				'session_id' => $session_id,
				'id' => '2261'
			]
		]);

		$this->assertArraySubset(['result' => ['id' => 2261]], $response);
	}
}
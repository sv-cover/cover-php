<?php

class ApiTest extends PHPUnit_Framework_TestCase
{
	public function testApiAgendaNotLoggedIn()
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
<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;

class ProcResult
{
	public $exit_code, $stdout, $stderr, $messages;

	public function __construct(int $exit_code, string $stdout, string $stderr, array $messages = [])
	{
		$this->exit_code = $exit_code;
		$this->stdout = $stdout;
		$this->stderr = $stderr;
		$this->messages = $messages;
	}

	public function print($fh = STDOUT)
	{
		fwrite($fh, "Exit code: {$this->exit_code}\n\n");
		fwrite($fh, "Stdout:\n-----\n{$this->stdout}\n-----\n\n");
		fwrite($fh, "Stderr:\n-----\n{$this->stderr}\n-----\n\n");

		$messages = implode("\n", array_map(function($m) {
			return "===\n{$m->buffer}\n===\n";
		}, $this->messages));

		fwrite($fh, "Messages:\n-----\n{$messages}\n-----\n\n");
	}
}

class ProcMail
{
	public $buffer = '';

	public function header($name)
	{
		list($message_header, $message_body) = explode("\n\n", $this->buffer, 2);

		// Very bad way to parse email headers:
		$headers = explode("\n", $message_header);

		foreach ($headers as $header)
		{
			list($header_name, $header_value) = explode(":", $header, 2);

			if ($header_name == $name)
				return trim($header_value);
		}

		return null;
	}
}

class MailinglistTest extends TestCase
{
	public function testMailinglistDoesNotExist()
	{
		$result = $this->simulateMessage('testcase@example.com', 'does-not-exist@svcover.nl', 'Hello world!', ['Subject: test-mail']);

		$this->assertEquals(103, $result->exit_code, 'Expect the script to return the error code RETURN_COULD_NOT_DETERMINE_LIST');

		$this->assertCount(1, $result->messages, 'Expect it also to send a return message to the sender');

		$this->assertEquals($result->messages[0]->header('To'), 'testcase@example.com', 'Expect it to be addressed to the sender');

		$result->print(STDERR);
	}

	private function simulateMessage($from, $to, $message, $additional_headers = [])
	{
		$headers = [
			"From: " . $from,
			"Envelope-To: " . $to
		];

		$headers = array_merge($headers, $additional_headers);

		$email = "First skipped line\n" . implode("\n", $headers) . "\n\n" . $message;

		$socket_file = rtrim(getenv('TMPDIR'), '/') . '/cover-php-test-' . uniqid();

		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

		socket_bind($socket, $socket_file);
		socket_listen($socket);

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];

		$program_options = [];

		$env = ['SENDMAIL' => "nc -U $socket_file --"];

		$cmd = dirname(__FILE__) . '/../cron/send-mailinglist-mail.php';

		if (!is_executable($cmd))
			throw new RuntimeException('Could not locate send-mailinglist-mail script');

		$proc = proc_open(implode(' ', [$cmd] + $program_options), $descriptors, $pipes, getcwd(), $env);

		if (!is_resource($proc))
			throw new RuntimeException('Could not start process');

		fwrite($pipes[0], $email);

		// Close STDIN
		fclose($pipes[0]);

		// Wait for the script to call our stuff
		$read = [$socket];
		$write = [];
		$except = [];

		$messages = [];

		while (true) {
			$n = socket_select($read, $write, $except, 1); // Script has 1 second to start sending emails

			if ($n === 0) // Timeout! No more mails I suppose...
				break;

			$client = socket_accept($socket);

			$message = new ProcMail();
			$messages[] = $message;

			do {
				$buffer = socket_read($client, 2048);
				$message->buffer .= $buffer;
			} while (strlen($buffer) > 0);

			socket_close($client);
		}

		socket_close($socket);

		unlink($socket_file);

		// Read STDOUT
		$response = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// Read STDERR
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$exit_code = proc_close($proc);

		return new ProcResult($exit_code, $response, $stderr, $messages);
	}
}
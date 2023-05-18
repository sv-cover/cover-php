<?php

namespace cover\test;

class MailCatcherMessage
{
	public $buffer = '';

	public function sendmail_args()
	{
		if (!preg_match('/^(?<args>.*?)\r?\n/', $this->buffer, $match))
			throw new \RuntimeException('Could not separate command line args and message in captured output.');

		return str_getcsv($match['args'], ' ');
	}

	public function sendmail_arg($n)
	{
		return $this->sendmail_args()[$n];
	}

	public function header($name)
	{
		// First row are the command line args to sendmail
		if (!preg_match('/^(?<args>.*?)\r?\n(?<stdin>.*)$/s', $this->buffer, $match))
			throw new \RuntimeException('Could not separate command line args and message in captured output.');

		if (!preg_match('/^(?<header>.*?)\r?\n\r?\n/s', $match['stdin'], $match))
			throw new \RuntimeException('Could not separate message header and body in captured output.');

		// Very bad way to parse email headers:
		$headers = array_filter(explode("\n", $match['header']));

		foreach ($headers as $header)
		{
			if (strpos($header, ":") === false)
				throw new \RuntimeException(sprintf('Header without name-value separator: %s', $header));

			list($header_name, $header_value) = explode(":", $header, 2);

			if ($header_name == $name)
				return trim($header_value);
		}

		return null;
	}

	public function body()
	{
		if (!preg_match('/^.*?\r?\n\r?\n(?<body>.*)$/s', $this->buffer, $match))
			throw new \RuntimeException('Could not separate message header and body in captured output.');

		return $match['body'];
	}

	public function write($fh = STDOUT)
	{
		fwrite($fh, "===\n{$this->buffer}\n===\n");
	}
}


class MailCatcher
{
	private $socket_file;

	private $socket;

	private $sendmail;

	public function __construct()
	{
		$this->sendmail = realpath(dirname(__FILE__) . '/../../bin/fake-sendmail.sh');

		$this->socket_file = rtrim(sys_get_temp_dir(), '/') . '/cover-php-test-' . uniqid();

		$this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);

		socket_bind($this->socket, $this->socket_file);
		socket_listen($this->socket);
	}

	public function __destruct()
	{
		socket_close($this->socket);

		unlink($this->socket_file);
	}

	public function catchMail($timeout = 0.25)
	{
		// Wait for the script to call our stuff
		$read = [$this->socket];
		$write = [];
		$except = [];

		$messages= [];

		$tu_sec = floor($timeout);
		$tu_usec = ($timeout - floor($timeout)) * 1000000;

		while (true) {
			$n = socket_select($read, $write, $except, $tu_sec, $tu_usec);

			if ($n === 0) // Timeout! No more mails I suppose...
				break;

			$client = socket_accept($this->socket);

			$message = new MailCatcherMessage();
			$messages[] = $message;

			do {
				$buffer = socket_read($client, 2048);
				$message->buffer .= $buffer;
			} while (strlen($buffer) > 0);

			socket_close($client);
		}

		return $messages;
	}

	public function sendmail_cmd()
	{
		return sprintf('%s "%s"', $this->sendmail, $this->socket_file);
	}
}


class Response
{
	public $location;
	
	public $header;

	public $body;

	public $messages;

	public function __construct($location, $header, $body, $messages = null)
	{
		$this->location = $location;
		$this->header = $header;
		$this->body = $body;
		$this->messages = $messages;
	}
}

function path_to_php_cgi_binary()
{
	// First try the php-cgi binary that is part of this PHP distribution
	if (is_executable(PHP_BINDIR . '/php'))
		return PHP_BINDIR . '/php';

	// If that doens't work, try the globally installed php-cgi
	$php_cgi = exec('which php', $output, $ret_val);

	if ($ret_val !== 0)
		throw new \RuntimeException('Could not locate php-cgi binary');

	return $php_cgi;
}

// TODO: This is not compatible with Symfony router
function simulate_request($path, $params)
{
	$path = ltrim($path, '/');

	$env = [
		'REQUEST_URI' => '/' . $path,
		'SCRIPT_NAME' => $path,
		'SCRIPT_FILENAME' => realpath(__DIR__ . '/../' . $path),
		'REDIRECT_STATUS' => 'true',
		'REQUEST_METHOD' => 'GET',
		'REMOTE_ADDR' => '127.0.0.1',
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

	if (isset($params['ENV'])) {
		$env = array_merge($env, $params['ENV']);
	}

	$descriptors = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['file', 'php://stderr', 'a']
	];

	$mail_catcher = new MailCatcher();

	$program_options = [
		'-d always_populate_raw_post_data=-1',
		// '-d sendmail_path="tee -a ./fake-sendmail-log.txt"',
		'-d sendmail_path="' . escapeshellarg($mail_catcher->sendmail_cmd()) . '"'
	];

	$php_cgi = path_to_php_cgi_binary();
	$proc = proc_open(implode(' ', array_merge([$php_cgi], $program_options, [$path])), $descriptors, $pipes, getcwd(), $env);

	if (!is_resource($proc))
		throw new \RuntimeException('Could not start CGI process');

	if ($post_data !== null)
		fwrite($pipes[0], $post_data);

	// Close STDIN
	fclose($pipes[0]);

	// Read STDOUT
	$response = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	list($headers, $body) = explode("\r\n\r\n", $response, 2);

	$exit_code = proc_close($proc);

	$messages = $mail_catcher->catchMail();

	$location = $path . (isset($env['QUERY_STRING']) ? '?' . $env['QUERY_STRING'] : '');

	return new Response($location, $headers, $body, $messages);
}

function simulate_json_request($path, $params)
{
	$response = simulate_request($path, $params);

	$json = json_decode($response->body, true);

	return $json;
}

class Form
{
	public $action;

	public $method;

	public $fields = [];

	public $origin;

	public function submit($method = '\cover\test\simulate_request')
	{
		$params = [];

		$url = $this->action ?: $this->origin->location;
		
		$url_components = parse_url($url);

		if (isset($url_components['query']))
			parse_str($url_components['query'], $params['GET']);
		else
			$params['GET'] = [];

		switch (strtoupper($this->method))
		{
			case 'POST':
				$params['POST'] = $this->fields;
				break;

			case 'GET':
			default:
				$params['GET'] = array_merge($params['GET'], $this->fields);
				break;
		}

		return call_user_func($method, $url_components['path'], $params);
	}

	static public function fromResponse(Response $response, $xpath)
	{
		$response_document = new \DOMDocument();

		libxml_use_internal_errors(true);
		$response_document->loadHTML($response->body);
		libxml_use_internal_errors(false);

		$query = new \DOMXPath($response_document);

		$form_node = $query->query($xpath)->item(0);

		if (!$form_node)
			throw new \InvalidArgumentException(sprintf('No node at path %s', $xpath));

		$form = new self();

		$form->origin = $response;

		$form->action = $form_node->getAttribute('action');

		$form->method = $form_node->getAttribute('method');

		$fields_query = $query->query('.//input', $form_node);

		foreach ($fields_query as $field_node)
		{
			$name = $field_node->getAttribute('name');
			$value = $field_node->getAttribute('value');
			$form->fields[$name] = $value;
		}

		return $form;
	}
}


trait MemberTestTrait
{
	static public $member_id;

	static public $member_email;

	static public $member_password;

	static public function setUpBeforeClass(): void
	{
		// Set up account
		$model = get_model('DataModelMember');

		self::$member_id = 10000000 + time() % 1000000;

		self::$member_email = sprintf('user%d@example.com', self::$member_id);

		self::$member_password = implode('', array_map(function($n) {
			return chr(mt_rand(ord('a'), ord('z')));
		}, range(1, 20)));

		$member = new \DataIterMember($model, self::$member_id, [
			'id' => self::$member_id,
			'voornaam' => 'Unit',
			'achternaam' => 'Test',
			'adres' => 'foo',
			'postcode' => '1111AA',
			'woonplaats' => 'foo',
			'email' => self::$member_email,
			'geboortedatum' => '1988-01-01',
			'geslacht' => 'm',
			'privacy' => 958435335,
			'type' => MEMBER_STATUS_LID,
			'nick' => 'unittest',
			'member_from' => '2010-10-01',
			'member_till' => null,
			'donor_from' => null,
			'donor_till' => null
		]);

		$model->insert($member);

		$model->set_password($member, self::$member_password);
	}

	public static function tearDownAfterClass(): void
	{
		// Delete account
		$model = get_model('DataModelMember');

		$member = $model->get_iter(self::$member_id);

		$model->delete($member);
	}

	public static function getMemberId()
	{
		return self::$member_id;
	}

	public static function getMemberEmail()
	{
		return self::$member_email;
	}

	public static function getMemberPassword()
	{
		return self::$member_password;
	}
}


trait SessionTestTrait 
{
	use MemberTestTrait {
		MemberTestTrait::setUpBeforeClass as setUpMember;
		MemberTestTrait::tearDownAfterClass as tearDownMember;
	}

	static public $cover_session;

	static public function setUpBeforeClass(): void
	{
		self::setUpMember();

		assert(self::getMemberId() != 0);

		$model = get_model('DataModelSession');

		if (!isset($_SERVER['REMOTE_ADDR']))
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		self::$cover_session = $model->create(self::getMemberId(), 'TestCase', '1 HOUR');
	}

	static public function tearDownAfterClass(): void
	{
		self::tearDownMember();

		assert(self::$cover_session instanceof \DataIter);

		$model = get_model('DataModelSession');
		$model->delete(self::$cover_session);
	}

	public function simulateRequestWithSession($url, $params)
	{
		if (!self::$cover_session)
			throw new \RuntimeException('No session available');

		$params = array_merge($params, ['ENV' => ['HTTP_COOKIE' => 'cover_session_id=' . self::$cover_session->get_id()]]);

		return simulate_request($url, $params);
	}
}


class ProcResult
{
	public $exit_code, $stdout, $stderr, $messages;

	public function __construct($exit_code, $stdout, $stderr, array $messages = [])
	{
		$this->exit_code = $exit_code;
		$this->stdout = $stdout;
		$this->stderr = $stderr;
		$this->messages = $messages;
	}

	public function write($fh = STDOUT)
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


trait EmailTestTrait
{
	protected function simulateEmail($from, $to, $message, $additional_headers = [])
	{
		$headers = [
			"From: " . $from,
			"Envelope-To: " . $to
		];

		$headers = array_merge($headers, $additional_headers);

		$email = "First skipped line\n" . implode("\n", $headers) . "\n\n" . $message;

		$mail_catcher = new MailCatcher();

		$sendmail_cmd = $mail_catcher->sendmail_cmd();

		$program_options = ['-f', dirname(__FILE__) . '/../cron/send-mailinglist-mail.php', '--'];

		$env = ['SENDMAIL' => $sendmail_cmd];

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];

		$proc = proc_open(implode(' ', [PHP_BINARY] + $program_options), $descriptors, $pipes, getcwd(), $env);

		if (!is_resource($proc))
			throw new \RuntimeException('Could not start process');

		fwrite($pipes[0], $email);

		// Close STDIN
		fclose($pipes[0]);

		// Catch all mail for one () second
		$messages = $mail_catcher->catchMail();

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

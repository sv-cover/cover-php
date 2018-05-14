<?php namespace cover\test;

class MailCatcherMessage
{
	public $buffer = '';

	public function sendmail_args()
	{
		$first_row = strpos($this->buffer, "\n", 0);
		$line = substr($this->buffer, 0, $first_row);
		return str_getcsv($line, ' ');
	}

	public function sendmail_arg($n)
	{
		return $this->sendmail_args()[$n];
	}

	public function header($name)
	{
		// First row are the command line args to sendmail
		$first_row = strpos($this->buffer, "\n", 0);

		// Second row is always ignored for some reason
		$second_row = strpos($this->buffer, "\n", $first_row + 1);

		$boundary_pos = strpos($this->buffer, "\n\n", $second_row + 1);

		$message_header = substr($this->buffer, $second_row, $boundary_pos);

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

	public function body()
	{
		$boundary_pos = strpos($this->buffer, "\n\n");
		return trim(substr($this->buffer, $boundary_pos + 1));
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
		$this->sendmail = realpath(dirname(__FILE__) . '/../bin/fake-sendmail.sh');

		$this->socket_file = rtrim(getenv('TMPDIR'), '/') . '/cover-php-test-' . uniqid();

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
	if (is_executable(PHP_BINDIR . '/php-cgi'))
		return PHP_BINDIR . '/php-cgi';

	// If that doens't work, try the globally installed php-cgi
	$php_cgi = exec('which php-cgi', $output, $ret_val);

	if ($ret_val !== 0)
		throw new \RuntimeException('Could not locate php-cgi binary');

	return $php_cgi;
}

function simulate_request($path, $params)
{
	$path = ltrim($path, '/');

	$env = [
		'REQUEST_URI' => '/' . $path,
		'SCRIPT_FILENAME' => realpath(__DIR__ . '/../' . $path),
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

	$proc = proc_open(implode(' ', [$php_cgi] + $program_options + [$path]), $descriptors, $pipes, getcwd(), $env);

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

	static public function setUpBeforeClass()
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
			'privacy' => 958698063,
			'type' => MEMBER_STATUS_LID,
			'nick' => 'unittest'
		]);

		$model->insert($member);

		$model->set_password($member, self::$member_password);
	}

	public static function tearDownAfterClass()
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

	static public function setUpBeforeClass()
	{
		self::setUpMember();

		assert(self::getMemberId() != 0);

		$model = get_model('DataModelSession');
		self::$cover_session = $model->create(self::getMemberId(), 'TestCase', '1 HOUR');
	}

	static public function tearDownAfterClass()
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
<?php namespace cover\test;

function test_sendmail_path()
{
	if ($catchmail_path = `which catchmail`)
		return trim($catchmail_path);

	return 'true';
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

	$program_options = [
		'-d always_populate_raw_post_data=-1',
		'-d sendmail_path=' . test_sendmail_path()
	];

	$proc = proc_open(implode(' ', ['php-cgi'] + $program_options + [$path]), $descriptors, $pipes, getcwd(), $env);

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

	return [$headers, $data];
}

function simulate_json_request($path, $params)
{
	list($headers, $data) = simulate_request($path, $params);

	$json = json_decode($data, true);

	return $json;
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
			'type' => MEMBER_STATUS_LID
		]);

		$model->insert($member);

		$profiel = new \DataIter($model, self::$member_id, ['lidid' => self::$member_id, 'nick' => 'unittest']);

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

		assert('self::getMemberId() != 0');

		$model = get_model('DataModelSession');
		self::$cover_session = $model->create(self::getMemberId(), 'TestCase', '1 HOUR');
	}

	static public function tearDownAfterClass()
	{
		self::tearDownMember();

		assert('self::$cover_session instanceof \DataIter');

		$model = get_model('DataModelSession');
		$model->delete(self::$cover_session);
	}

	public function simulateRequestWithSession($url, $params)
	{
		if (!self::$cover_session)
			throw new RuntimeException('No session available');

		$params = array_merge($params, ['ENV' => ['HTTP_COOKIE' => 'cover_session_id=' . self::$cover_session->get_id()]]);

		return simulate_request($url, $params);
	}
}
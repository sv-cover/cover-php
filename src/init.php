<?php
	if (defined('IN_SITE'))
		return;

	define('IN_SITE', true);

	ini_set('display_errors', true);
	ini_set('magic_quotes_gpc', 0);

	class AssertionException extends RuntimeException
	{
		public function __construct($message, $script, $line)
		{
			parent::__construct('Assertion failed: ' . $message);
		}
	} 

	assert_options(ASSERT_CALLBACK, function($script, $line, $message) {
		throw new AssertionException($message, $script, $line);
	});
	
	set_error_handler(function($severity, $message, $file, $line, $vars) {
		if (error_reporting() & $severity)
			throw new ErrorException($message, 0, $severity, $file, $line);
	});

	if (isset($_SERVER['HTTP_HOST']) && preg_match('/^(www\.)?svcover\.nl$/', $_SERVER['HTTP_HOST']))
		error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE ^ E_DEPRECATED ^ E_STRICT);
	else
		error_reporting(E_ALL);

	/* Import composer packages */
	require_once 'vendor/autoload.php';

	require_once 'src/services/sentry.php';
	require_once 'src/framework/functions.php';
	require_once 'src/framework/i18n.php';
	require_once 'src/framework/constants.php';
	require_once 'src/framework/policy.php';

	date_default_timezone_set('Europe/Amsterdam');


	/* Initialize session */
	session_start();

	init_sentry();

	init_i18n();

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

	if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'www.svcover.dev')
		error_reporting(E_ALL ^ E_STRICT ^ E_USER_NOTICE ^ E_DEPRECATED);
	elseif (version_compare(PHP_VERSION, '5.4.0') < 0)
		error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE);
	else
		error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE ^ E_DEPRECATED ^ E_STRICT);

	require_once 'include/sentry.php';
	require_once 'include/functions.php';
	require_once 'include/i18n.php';
	require_once 'include/constants.php';
	require_once 'include/policies/policy.php';

	date_default_timezone_set('Europe/Amsterdam');

	/* Import composer packages */
	require_once dirname(__FILE__) . '/../../vendor/autoload.php';

	/* Initialize session */
	session_start();

	init_sentry();

	init_i18n();

	if (get_config_value('show_queries', false))
		get_db()->history = [];

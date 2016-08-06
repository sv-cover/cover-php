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
	
	if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'www.svcover.dev')
	{
		error_reporting(E_ALL ^ E_STRICT ^ E_USER_NOTICE);

		set_error_handler(function($number, $message, $file, $line, $vars) {
			echo '<pre style="background:white;padding: 1em; margin: 1em;color:#c60c30;">';
			debug_print_backtrace();
			echo '</pre>';
		},	error_reporting());
	}
	elseif (version_compare(PHP_VERSION, '5.4.0') < 0)
		error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE);
	else
		error_reporting(E_ALL ^ E_NOTICE ^ E_USER_NOTICE ^ E_DEPRECATED ^ E_STRICT);

	require_once 'include/functions.php';
	require_once 'include/i18n.php';
	require_once 'include/constants.php';
	require_once 'include/policies/policy.php';

	date_default_timezone_set('Europe/Amsterdam');

	/* Import composer packages */
	require_once dirname(__FILE__) . '/../vendor/autoload.php';

	/* Initialize session */
	session_start();

	init_i18n();

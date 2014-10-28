<?php
	if (defined('IN_SITE'))
		return;

	define('IN_SITE', true);

	ini_set('display_errors', true);

	if (version_compare(PHP_VERSION, '5.4.0') < 0)
		error_reporting(E_ALL ^ E_NOTICE);
	else
		error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);

	/* Set the include path so we can include these from everywhere */
	set_include_path('.' . PATH_SEPARATOR . dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
	ini_set('magic_quotes_gpc', 0);
	
	require_once('functions.php');
	require_once('i18n.php');
	require_once('constants.php');


	date_default_timezone_set('Europe/Amsterdam');

	/* Import composer packages */
	require_once dirname(__FILE__) . '/../vendor/autoload.php';

	/* Initialize session */
	session_start();

	init_i18n();
?>

<?php
	if (defined('IN_SITE'))
		return;

	define('IN_SITE', true);

	ini_set('display_errors', true);
	ini_set('magic_quotes_gpc', 0);
	
	if (version_compare(PHP_VERSION, '5.4.0') < 0)
		error_reporting(E_ALL ^ E_NOTICE);
	else
		error_reporting(E_ALL | E_NOTICE | E_DEPRECATED | E_STRICT);

	require_once 'include/functions.php';
	require_once 'include/i18n.php';
	require_once 'include/constants.php';

	date_default_timezone_set('Europe/Amsterdam');

	/* Import composer packages */
	require_once dirname(__FILE__) . '/../vendor/autoload.php';

	/* Initialize session */
	session_start();

	init_i18n();

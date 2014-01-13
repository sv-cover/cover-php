<?php
	if (!defined('IN_SITE'))
		return;

	if (defined('COOKIE_KEY'))
		return;

	define('COOKIE_KEY', 'hardnekkig');
	define('ROOT_DIR_PATH', preg_replace('/include$/', '', dirname(__FILE__)));
	define('ROOT_DIR_URI', 'http://www.svcover.nl/');
	define('INCLUDE_PATH', dirname(__FILE__));
	
	define('COMMISSIE_BESTUUR', 0);
	define('COMMISSIE_EASY', 1);
	define('COMMISSIE_BOEKCIE', 3);
	define('COMMISSIE_FOTOCIE', 7);
	define('COMMISSIE_PRCIE', 10);
	define('COMMISSIE_ALMANAKCIE',4);
	
	define('NETWORK_AI', 0);
	define('NETWORK_RUG', 1);
	define('NETWORK_OTHER', 2);
	define('NUM_NETWORK', 3);
	
	define('AUTH_LEVEL_MEMBER', 0);
	define('AUTH_LEVEL_COMMISSIE', 1);
	define('AUTH_LEVEL_BESTUUR', 2);
	define('AUTH_LEVEL_WEBCIE', 3);

	if (in_array($_SERVER['REMOTE_ADDR'], array('129.125.139.247', '129.125.139.237', '129.125.139.236', '129.125.139.248', '129.125.130.218')))
		define('NETWORK', NETWORK_AI);
	elseif (preg_match('/^129.125/', $_SERVER['REMOTE_ADDR']))
		define('NETWORK', NETWORK_RUG);
	else
		define('NETWORK', NETWORK_OTHER);	
?>

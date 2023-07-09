<?php
if (!defined('IN_SITE'))
	return;

if (defined('COOKIE_KEY'))
	return;

define('COOKIE_KEY', 'hardnekkig');
define('ROOT_DIR_URI', 'https://www.svcover.nl/');
define('INCLUDE_PATH', dirname(__FILE__));

define('COMMISSIE_BESTUUR', 0);
define('COMMISSIE_KANDIBESTUUR', 30);
define('COMMISSIE_EASY', 1);
define('COMMISSIE_BOEKCIE', 3);
define('COMMISSIE_FOTOCIE', 7);
define('COMMISSIE_COMEXA', 26);
define('COMMISSIE_ALMANAKCIE',4);

define('NETWORK_AI', 0);
define('NETWORK_RUG', 1);
define('NETWORK_OTHER', 2);
define('NUM_NETWORK', 3);

define('AUTH_LEVEL_MEMBER', 0);
define('AUTH_LEVEL_COMMISSIE', 1);
define('AUTH_LEVEL_BESTUUR', 2);
define('AUTH_LEVEL_WEBCIE', 3);

define('MEMBER_STATUS_LID', 1);
define('MEMBER_STATUS_LID_AF', 2);
define('MEMBER_STATUS_ERELID', 3);
define('MEMBER_STATUS_DONATEUR', 5);
define('MEMBER_STATUS_PENDING', 6);

define('MEMBER_STATUS_MIN', 1);
define('MEMBER_STATUS_MAX', 6);

define('WEBSITE_ENCODING', 'UTF-8');

if (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], array('129.125.139.247', '129.125.139.237', '129.125.139.236', '129.125.139.248', '129.125.130.218')))
	define('NETWORK', NETWORK_AI);
elseif (isset($_SERVER['REMOTE_ADDR']) && preg_match('/^129.125/', $_SERVER['REMOTE_ADDR']))
	define('NETWORK', NETWORK_RUG);
else
	define('NETWORK', NETWORK_OTHER);

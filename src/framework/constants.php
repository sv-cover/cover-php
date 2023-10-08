<?php
if (!defined('IN_SITE'))
	return;

define('ROOT_DIR_URI', 'https://www.svcover.nl/');
define('INCLUDE_PATH', dirname(__FILE__));

define('COMMISSIE_BESTUUR', 0);
define('COMMISSIE_KANDIBESTUUR', 30);
define('COMMISSIE_EASY', 1);
define('COMMISSIE_BOEKCIE', 3);
define('COMMISSIE_FOTOCIE', 7);
define('COMMISSIE_COMEXA', 26);
define('COMMISSIE_ALMANAKCIE',4);

define('MEMBER_STATUS_LID', 1);
define('MEMBER_STATUS_LID_AF', 2);
define('MEMBER_STATUS_ERELID', 3);
define('MEMBER_STATUS_DONATEUR', 5);
define('MEMBER_STATUS_PENDING', 6);

define('MEMBER_STATUS_MIN', 1);
define('MEMBER_STATUS_MAX', 6);

define('WEBSITE_ENCODING', 'UTF-8');

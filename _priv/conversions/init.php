<?php
	ini_set('display_errors', true);
	error_reporting(E_ALL ^ E_NOTICE);

	/* Set the include path so we can include these from everywhere */
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/../../include');

	require_once('data.php');
	require_once('constants.php');
	require_once('config.php');

	function ansi($s, $code) {
		return chr(27) . '[' . $code . 'm' . $s . chr(27) . '[0m';
	}
		
	function bold($s) {
		return ansi($s, 1);
	}
	
	
	function blue($s) {
		return ansi($s, '34');
	}
	
	function green($s) {
		return ansi($s, '32');
	}
	
	function red($s) {
		return ansi($s, '31');
	}
	
	function title($s) {
		echo "\n" . green(str_repeat('#', 79)) . "\n";
		echo green('# ')  . bold($s) . str_repeat(' ', 79 - strlen($s) - 4) . green(" #\n");
		echo green(str_repeat('#', 79)) . "\n\n";
	}
	
	function message($message) {
		echo blue(bold($message))  . str_repeat('.', 72 - strlen($message));
		flush();
	}
	
	function result($result) {
		global $db;
		
		if ($result)
			echo '...[' . green(bold('OK')) . ']';
		else
		{
			echo '[' . red(bold('FALSE')) . ']';
			
			if ($db && $db->get_last_error())
				echo "\n => " . $db->get_last_error();
		}

		echo "\n";
	}
	
	function ok() {
		result(true);
	}
	
	function err() {
		result(false);
	}
?>

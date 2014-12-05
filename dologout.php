<?php
	require_once 'include/init.php';
	require_once 'include/login.php';
	
	logout();
	
	/* CHECK: is this necessary */
	session_destroy();

	header("Location: " . $_GET['referrer']);

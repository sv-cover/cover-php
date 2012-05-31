<?php
/*
 * e-academy ELMS Integrated User Verification
 */

include('include/init.php');
require_once('member.php');

session_start();

if(logged_in()) {
	// Haal data op
	$user = logged_in();
	if($user['id'] == 566) { // Smerige hack. Als Wilco inlogt, log dan in als webcie@svcover.nl (administrator)
		$user['id'] = "webcie@svcover.nl";
	}
	
	// Maak achternaam netjes
	if($user['tussenvoegsel']) {
		$achternaam = $user['tussenvoegsel'] . " " . $user['achternaam'];
	} else {
		$achternaam = $user['achternaam'];
	}
	
	$_SESSION['auth_ccid'] = $user['id'];
	$_SESSION['account_number'] = '100033061';
	$_SESSION['auth_email'] = $user['email'];
	$_SESSION['first_name'] = $user['voornaam'];
	$_SESSION['last_name'] = $achternaam;
	$_SESSION['academic_statuses'] = 'students';
	$_error = '';
	session_write_close();
} else {
	session_write_close();
	die("Je moet inloggen op de website van Cover om toegang te krijgen tot MSDNAA.");
}

// user has been authenticated - handshake
if (isset($_SESSION['auth_ccid'])) 
{
	ob_start();

	$key = '1bed39a8'; // replace with your webstore key
	$host = 'https://e5.onthehub.com/WebStore/Security/AuthenticateUser.aspx';

	// build e5 verification page query string variables
	$vars = 'username='.urlencode($_SESSION['auth_ccid']);
	$vars .= '&account='.urlencode($_SESSION['account_number']);
	$vars .= '&email='.urlencode($_SESSION['auth_email']);
	$vars .= '&first_name='.urlencode($_SESSION['first_name']);
	$vars .= '&last_name='.urlencode($_SESSION['last_name']);
	$vars .= '&academic_statuses='.urlencode($_SESSION['academic_statuses']);
	$vars .= '&shopper_ip='.urlencode($_SERVER['REMOTE_ADDR']);
	$vars .= '&key='.$key;

	$e5verfurl = $host.'?'.$vars;

	$handle = fopen($e5verfurl, 'r');
	$e5LoginRedirectURL = stream_get_contents($handle);
	fclose($handle);

	// check response status code
	$http_status = $http_response_header[0];
	if (strpos($http_status, '200 OK') === false)	// NOTE $http_status may not always be $http_response_header[0]
	{
		// we have an error
		echo '<p><b>A handshake error has occured</b></p>';
		echo '<p><b>Response received:<br><font color="red">'.$http_status.'</font></b></p>';
	}
	else if (strlen($e5LoginRedirectURL) == 0)
	{
		// HTTP status code was OK but server didn't return a redirect URL; may be some other error, such as incorrectly configured server IP for your server
		echo '<p><b>A handshake error has occured; invalid redirection URL</b></p>';
	}
	else 
	{
		// status code looks good and we have a redirection URL; set redirect location in header
		header('Location: '.$e5LoginRedirectURL);
	}

	// clear session variables and destroy
	$_SESSION=array();
	if(isset($_COOKIE[session_name()])) 
	{
		setcookie(session_name(),'',time()-42000,'/');
	}
	@session_destroy();
	ob_end_flush();
	exit;
}
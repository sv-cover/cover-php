<?php
/*
 * e-academy ELMS Integrated User Verification
 */

include('include/init.php');
require_once('member.php');

session_start();

if(!logged_in())
	die("Je moet inloggen op de website van Cover om toegang te krijgen tot MSDNAA.");
	
$user = logged_in();

// Smerige hack. Als Wilco inlogt, log dan in als webcie@svcover.nl (administrator)
if($user['id'] == 566)
	$user['id'] = "webcie@svcover.nl";

// Maak achternaam netjes
$achternaam = empty($user['tussenvoegsel'])
	? $user['achternaam']
	: $user['tussenvoegsel'] . ' ' . $user['achternaam'];

$key = '1bed39a8'; // replace with your webstore key
$host = 'https://e5.onthehub.com/WebStore/Security/AuthenticateUser.aspx';

// build e5 verification page query string variables
$vars = 'username='.urlencode($user['id']);
$vars .= '&account='.urlencode('100033061');
$vars .= '&email='.urlencode($user['email']);
$vars .= '&first_name='.urlencode($user['voornaam']);
$vars .= '&last_name='.urlencode($achternaam);
$vars .= '&academic_statuses='.urlencode('students');
$vars .= '&shopper_ip='.urlencode($_SERVER['REMOTE_ADDR']);
$vars .= '&key='.$key;

$e5verfurl = $host.'?'.$vars;

$handle = fopen($e5verfurl, 'r');
$e5LoginRedirectURL = stream_get_contents($handle);
fclose($handle);

// check response status code
$http_status = $http_response_header[0];

// NOTE $http_status may not always be $http_response_header[0]
if (strpos($http_status, '200 OK') === false)
{
	// we have an error
	echo '<p><b>A handshake error has occured</b></p>';
	echo '<p><b>Response received:<br><font color="red">'.$http_status.'</font></b></p>';
}
else if (strlen($e5LoginRedirectURL) == 0)
{
	// HTTP status code was OK but server didn't return a redirect URL; may be
	// some other error, such as incorrectly configured server IP for your server
	echo '<p><b>A handshake error has occured; invalid redirection URL</b></p>';
}
else 
{
	// status code looks good and we have a redirection URL; set redirect location in header
	header('Location: '.$e5LoginRedirectURL);
}

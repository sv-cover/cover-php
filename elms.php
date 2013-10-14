<?php
include('include/init.php');
require_once('member.php');

if(!isset($_GET['token']) OR !isset($_GET['return_url'])) {
	die("Not allowed");
}
if(!logged_in()) {
	die("Je moet inloggen op de website van Cover om toegang te krijgen tot MSDNAA.");
} else {
	$user = logged_in();
	if($user['id'] == 622 || $user['id'] == 825 || $user['id'] == 855) { // Smerige hack. Als Wilco inlogt, log dan in als webcie@svcover.nl (administrator)
		$user['id'] = "webcie@svcover.nl";
	}
	// Maak achternaam netjes
	if($user['tussenvoegsel']) {
		$achternaam = $user['tussenvoegsel'] . " " . $user['achternaam'];
	} else {
		$achternaam = $user['achternaam'];
	}
	$url  = "https://msdn62.e-academy.com/svcover_it/index.cfm?loc=login/cab_cgi&token=".$_GET['token']."&uid=".urlencode($user['id']);
	$url .= "&groups=leden&department=cover&fname=".urlencode($user['voornaam'])."&lname=".urlencode($achternaam)."&email=".urlencode($user['email']);
	$response = file($url);
	$response = implode("", $response);
	if(strstr($response, "Account created") OR strstr($response, "Account updated")) {
		// Authentication gelukt
		$forward_url = $_GET['return_url']."&token=".$_GET['token']."&uid=".urlencode($user['id']);
		header('Location: ' . $forward_url);		
	} else {
		die("Authentication error");
	}
}